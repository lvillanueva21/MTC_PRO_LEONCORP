<?php
// modules/inventario/qr_pdf.php (A4 grid + Tickets 80/58 con QRs)
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/inv_lib.php';

app_log_init(__DIR__ . '/../../logs/inventario_qr_pdf.log');

acl_require_ids([1,4,6]);
verificarPermiso(['Desarrollo','Administración','Gerente']);

$u = currentUser();
$empresaId = (int)($u['empresa']['id'] ?? 0);
if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada'); }

$idsRaw = trim((string)($_GET['ids'] ?? ''));

// Tamaño QR (mm)
$mm = (int)($_GET['mm'] ?? 24);
if ($mm < 16) $mm = 16;
if ($mm > 45) $mm = 45;

// Formato papel: A4 | T80 | T58
function norm_paper(string $s): string {
  $s = strtolower(trim($s));
  if ($s === '' || $s === 'a4') return 'A4';
  if (in_array($s, ['t80','80','80mm','ticket80','ticket_80','ticket-80'], true)) return 'T80';
  if (in_array($s, ['t58','58','58mm','ticket58','ticket_58','ticket-58'], true)) return 'T58';
  return 'A4';
}
$paper = norm_paper((string)($_GET['paper'] ?? 'A4'));

// Margen (mm). Si no viene, default: A4=10, Ticket=2
$margin = isset($_GET['m']) ? (int)$_GET['m'] : (($paper === 'A4') ? 10 : 2);
if ($paper === 'A4') {
  if ($margin < 6) $margin = 6;
  if ($margin > 20) $margin = 20;
} else {
  if ($margin < 0) $margin = 0;
  if ($margin > 8) $margin = 8;
}

// Borde: 1 dibuja borde
$showBorder = (int)($_GET['b'] ?? 1);

$mysqli = db();

// phpqrcode
$qrLib = __DIR__ . '/../phpqrcode/qrlib.php';
if (!file_exists($qrLib)) { http_response_code(500); exit('phpqrcode no encontrado'); }
require_once $qrLib;

// TCPDF
$tcpdf = __DIR__ . '/../TCPDF/tcpdf.php';
if (!file_exists($tcpdf)) { http_response_code(500); exit('TCPDF no encontrado'); }
require_once $tcpdf;

function parse_ids($raw) {
  $out = [];
  foreach (explode(',', (string)$raw) as $p) {
    $n = (int)trim($p);
    if ($n > 0) $out[$n] = $n;
  }
  return array_values($out);
}

try {
  $ids = parse_ids($idsRaw);
  if (!$ids) { http_response_code(400); exit('Sin IDs'); }

  // Traer bienes (solo de la empresa)
  $ph = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids) + 1);
  $sql = "SELECT id, id_empresa, creado, nombre FROM inv_bienes WHERE id_empresa=? AND id IN ($ph) ORDER BY id ASC";
  $st = $mysqli->prepare($sql);

  // bind dinámico
  $params = array_merge([$empresaId], $ids);
  $refs = [];
  $refs[] = $types;
  foreach ($params as $k => $v) $refs[] = &$params[$k];
  call_user_func_array([$st,'bind_param'], $refs);

  $st->execute();
  $items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  if (!$items) { http_response_code(404); exit('No hay bienes para imprimir'); }

  // Layout base (celda: QR + texto)
  $labelH = 7;                         // alto texto
  $pad    = ($paper === 'A4') ? 2.5 : 1.8;  // padding interno
  $cellW  = $mm + $pad*2;
  $cellH  = $mm + $labelH + $pad*2;

  // Crear PDF según formato
  if ($paper === 'A4') {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
  } else {
    $pageW = ($paper === 'T80') ? 80 : 58;

    $usableW = $pageW - 2*$margin;

    // cols dinámico por espacio real (esto arregla tu “solo 1 por fila”)
    $cols = (int)floor($usableW / $cellW);
    if ($cols < 1) $cols = 1;

    // límites razonables para tickets (evita que quede ultra apretado)
    if ($paper === 'T80' && $cols > 3) $cols = 3;
    if ($paper === 'T58' && $cols > 2) $cols = 2;

    $rowsNeeded = (int)ceil(count($items) / $cols);

    // separación vertical entre filas en ticket
    $gapY = 2.0;

    // altura exacta del papel (un “ticket” largo)
    $pageH = (float)(2*$margin + $rowsNeeded*$cellH + ($rowsNeeded+1)*$gapY);
    if ($pageH < 40) $pageH = 40;
    if ($pageH > 1200) $pageH = 1200; // safety

    $pdf = new TCPDF('P', 'mm', [$pageW, $pageH], true, 'UTF-8', false);
  }

  $pdf->SetCreator('Inventario');
  $pdf->SetAuthor('Sistema');
  $pdf->SetTitle('Etiquetas QR');
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);

  $pdf->SetMargins($margin, $margin, $margin);

  // En tickets, no queremos “páginas” por defecto (ya calculamos height)
  if ($paper === 'A4') $pdf->SetAutoPageBreak(true, $margin);
  else $pdf->SetAutoPageBreak(false, 0);

  $pdf->AddPage();

  // Bordes más visibles en tickets (térmicas suelen comerse líneas finas)
  $pdf->SetDrawColor(0,0,0);
  $pdf->SetLineWidth(($paper === 'A4') ? 0.20 : 0.35);

  $pdf->SetFont('helvetica', '', ($paper === 'A4') ? 8 : 9);

  $pageW = $pdf->getPageWidth();
  $pageH = $pdf->getPageHeight();
  $usableW = $pageW - 2*$margin;
  $usableH = $pageH - 2*$margin;

  if ($paper === 'A4') {
    // A4: grilla automática por ancho/alto útil
    $cols = (int)floor($usableW / $cellW);
    if ($cols < 1) $cols = 1;

    $rowsPerPage = (int)floor($usableH / $cellH);
    if ($rowsPerPage < 1) $rowsPerPage = 1;

    $perPage = $cols * $rowsPerPage;

    $i = 0;
    foreach ($items as $it) {
      if ($i > 0 && ($i % $perPage) === 0) {
        $pdf->AddPage();
      }

      $idx = $i % $perPage;
      $r = (int)floor($idx / $cols);
      $c = (int)($idx % $cols);

      $x = $margin + $c*$cellW;
      $y = $margin + $r*$cellH;

      $code = inv_codigo($it['id_empresa'], $it['creado'], $it['id']);
      $payload = inv_qr_payload(BASE_URL, $code);

      $tmp = sys_get_temp_dir() . '/invqr_' . $empresaId . '_' . $it['id'] . '_' . uniqid('', true) . '.png';
      QRcode::png($payload, $tmp, QR_ECLEVEL_M, 6, 1);

      if ($showBorder === 1) $pdf->Rect($x, $y, $cellW, $cellH);

      $imgX = $x + $pad;
      $imgY = $y + $pad;
      $pdf->Image($tmp, $imgX, $imgY, $mm, $mm, 'PNG', '', '', true, 300, '', false, false, 0);

      $pdf->SetXY($x + 0.5, $y + $pad + $mm + 1);
      $pdf->MultiCell($cellW - 1, 6, $code, 0, 'C', false, 1);

      @unlink($tmp);
      $i++;
    }

  } else {
    // TICKETS: grilla real + espacios bien repartidos (arregla tus márgenes enormes)
    $cols = (int)floor($usableW / $cellW);
    if ($cols < 1) $cols = 1;
    if ($paper === 'T80' && $cols > 3) $cols = 3;
    if ($paper === 'T58' && $cols > 2) $cols = 2;

    // gapX: reparte el sobrante en (cols+1) espacios (izq + entre + der)
    $leftover = $usableW - $cols*$cellW;
    $gapX = ($leftover > 0) ? ($leftover / ($cols + 1)) : 0.0;

    $gapY = 2.0;

    $i = 0;
    foreach ($items as $it) {
      $r = (int)floor($i / $cols);
      $c = (int)($i % $cols);

      $x = $margin + $gapX + $c*($cellW + $gapX);
      $y = $margin + $gapY + $r*($cellH + $gapY);

      $code = inv_codigo($it['id_empresa'], $it['creado'], $it['id']);
      $payload = inv_qr_payload(BASE_URL, $code);

      $tmp = sys_get_temp_dir() . '/invqr_' . $empresaId . '_' . $it['id'] . '_' . uniqid('', true) . '.png';
      QRcode::png($payload, $tmp, QR_ECLEVEL_M, 6, 1);

      if ($showBorder === 1) $pdf->Rect($x, $y, $cellW, $cellH);

      $imgX = $x + $pad;
      $imgY = $y + $pad;
      $pdf->Image($tmp, $imgX, $imgY, $mm, $mm, 'PNG', '', '', true, 300, '', false, false, 0);

      $pdf->SetXY($x + 0.5, $y + $pad + $mm + 1);
      $pdf->MultiCell($cellW - 1, 6, $code, 0, 'C', false, 1);

      @unlink($tmp);
      $i++;
    }
  }

  $name = 'qrs_empresa_'.$empresaId.'_'.date('Ymd_His').'.pdf';
  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="'.$name.'"');
  $pdf->Output($name, 'I');
  exit;

} catch (Throwable $e) {
  app_log_exception($e, ['empresa'=>$empresaId, 'ids'=>$idsRaw, 'mm'=>$mm, 'paper'=>$paper]);
  http_response_code(500);
  exit('Error de servidor');
}
