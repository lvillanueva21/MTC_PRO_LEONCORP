<?php
// modules/consola/certificados/pdf_preview.php
// Genera un PDF simple para vista/impresión de un certificado con fondo, logo y firma

require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../TCPDF/tcpdf.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

// ---- Parámetros ----
$id = (int)($_GET['id'] ?? 0);
$lx = (float)($_GET['lx'] ?? 50); // %
$ly = (float)($_GET['ly'] ?? 15);
$lw = (float)($_GET['lw'] ?? 30);
$fx = (float)($_GET['fx'] ?? 80);
$fy = (float)($_GET['fy'] ?? 80);
$fw = (float)($_GET['fw'] ?? 25);

$clamp = function($v,$min,$max){ return max($min, min($max, $v)); };
$lx=$clamp($lx,0,100); $ly=$clamp($ly,0,100); $lw=$clamp($lw,5,90);
$fx=$clamp($fx,0,100); $fy=$clamp($fy,0,100); $fw=$clamp($fw,5,90);

// ---- Datos de BD ----
if ($id <= 0) {
  http_response_code(400);
  echo 'ID inválido';
  exit;
}

$st = $db->prepare("
  SELECT pc.id, pc.nombre, pc.fondo_path, pc.logo_path, pc.firma_path, e.nombre AS empresa
  FROM cq_plantillas_certificados pc
  JOIN mtp_empresas e ON e.id = pc.id_empresa
  WHERE pc.id = ?
");
$st->bind_param('i', $id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
if (!$row) {
  http_response_code(404);
  echo 'No encontrado';
  exit;
}

// ---- Rutas (relativas -> absolutas) ----
$ROOT = realpath(__DIR__ . '/../../..'); // raíz del proyecto
function abs_path($root, $rel){
  $rel = ltrim((string)$rel, '/');
  $p = $root . '/' . $rel;
  return $p;
}

// Placeholders (mismas rutas usadas en JS)
$PH_FONDO = abs_path($ROOT, 'modules/consola/certificados/img/placeholder-fondo.png');
$PH_LOGO  = abs_path($ROOT, 'modules/consola/certificados/img/placeholder-logo.png');
$PH_FIRMA = abs_path($ROOT, 'modules/consola/certificados/img/placeholder-firma.png');
$PH_QR    = abs_path($ROOT, 'modules/consola/certificados/img/placeholder-qr.png');

$fondo = $row['fondo_path'] ? abs_path($ROOT, $row['fondo_path']) : $PH_FONDO;
$logo  = $row['logo_path']  ? abs_path($ROOT, $row['logo_path'])  : $PH_LOGO;
$firma = $row['firma_path'] ? abs_path($ROOT, $row['firma_path']) : $PH_FIRMA;
$qr    = $PH_QR;

if (!is_file($fondo)) $fondo = $PH_FONDO;
if (!is_file($logo))  $logo  = $PH_LOGO;
if (!is_file($firma)) $firma = $PH_FIRMA;
if (!is_file($qr))    $qr    = $PH_QR;

// TCPDF puede no soportar WEBP en algunos entornos.
// Convertimos a PNG temporal si es .webp y existe soporte GD.
function ensure_pdf_image($path){
  $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  if ($ext !== 'webp') return $path;
  if (!function_exists('imagecreatefromwebp')) return $path; // lo intentará TCPDF
  $im = @imagecreatefromwebp($path);
  if (!$im) return $path;
  $tmp = sys_get_temp_dir().'/tmp_'.uniqid('img_').'.png';
  imagepng($im, $tmp);
  imagedestroy($im);
  return $tmp;
}

$fondo = ensure_pdf_image($fondo);
$logo  = ensure_pdf_image($logo);
$firma = ensure_pdf_image($firma);
$qr    = ensure_pdf_image($qr);

// ---- Crear PDF ----
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema');
$pdf->SetAuthor('Sistema');
$pdf->SetTitle('Vista previa de certificado');
$pdf->SetMargins(0,0,0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

$PAGE_W = 297.0; // A4 horizontal
$PAGE_H = 210.0;

// Fondo a página completa (manteniendo proporción: usamos ancho total)
$pdf->Image($fondo, 0, 0, $PAGE_W, 0, '', '', '', true, 300, '', false, false, 0, false, false, false);

// Logo
$w_logo = $PAGE_W * ($lw/100.0);
$x_logo = $PAGE_W * ($lx/100.0) - ($w_logo/2.0);
$y_logo = $PAGE_H * ($ly/100.0);
$y_logo = $y_logo - ($w_logo/2.0)*0.5; // ajuste aproximado para centrar
$pdf->Image($logo, $x_logo, $y_logo, $w_logo, 0, '', '', '', true, 300, '', false, false, 0, false, false, true);

// Firma
$w_firma = $PAGE_W * ($fw/100.0);
$x_firma = $PAGE_W * ($fx/100.0) - ($w_firma/2.0);
$y_firma = $PAGE_H * ($fy/100.0);
$y_firma = $y_firma - ($w_firma/2.0)*0.3; // ajuste aproximado para centrar
$pdf->Image($firma, $x_firma, $y_firma, $w_firma, 0, '', '', '', true, 300, '', false, false, 0, false, false, true);

// Textos de ejemplo (elementos distintos de logo/firma) según layout
$st2 = $db->prepare("
  SELECT codigo_elemento, pos_x, pos_y, ancho, ejemplo_texto,
         font_size, font_bold, font_align, font_family
  FROM cq_plantillas_posiciones
  WHERE id_plantilla_certificado = ? AND pagina = 1
");
$st2->bind_param('i', $id);
$st2->execute();
$rs2 = $st2->get_result();

$qr_xPct = null;
$qr_yPct = null;
$qr_wPct = null;

while ($r2 = $rs2->fetch_assoc()) {
  $code = (string)$r2['codigo_elemento'];

  // Logo y firma ya se pintan antes como imágenes
  if ($code === 'logo' || $code === 'firma') {
    continue;
  }

  // QR como imagen independiente
  if ($code === 'qr') {
    $qr_xPct = $clamp((float)$r2['pos_x'], 0.0, 100.0);
    $qr_yPct = $clamp((float)$r2['pos_y'], 0.0, 100.0);
    $qr_wPct = (float)$r2['ancho'];
    if ($qr_wPct <= 0.0) {
      $qr_wPct = 18.0;
    }
    $qr_wPct = $clamp($qr_wPct, 5.0, 90.0);
    continue;
  }

  $xPct = $clamp((float)$r2['pos_x'], 0.0, 100.0);
  $yPct = $clamp((float)$r2['pos_y'], 0.0, 100.0);
  $wPct = (float)$r2['ancho'];
  if ($wPct <= 0.0) {
    $wPct = 40.0;
  }
  $wPct = $clamp($wPct, 5.0, 90.0);

  $w_mm = $PAGE_W * ($wPct / 100.0);
  $x_mm = $PAGE_W * ($xPct / 100.0) - ($w_mm / 2.0);
  $y_mm = $PAGE_H * ($yPct / 100.0);

  $texto = isset($r2['ejemplo_texto']) ? (string)$r2['ejemplo_texto'] : '';
  $texto = trim($texto) !== '' ? $texto : $code;

  // Tamaño de fuente (font_size 50–200 => 10pt base)
  $fs = isset($r2['font_size']) ? (int)$r2['font_size'] : 0;
  if ($fs <= 0) $fs = 100;
  if ($fs < 50)  $fs = 50;
  if ($fs > 200) $fs = 200;
  $fontPt = 10.0 * ($fs / 100.0);

  // Negrita
  $isBold = !empty($r2['font_bold']);

  // Alineación
  $align = strtoupper((string)($r2['font_align'] ?? 'C'));
  if (!in_array($align, ['L','C','R','J'], true)) {
    $align = 'C';
  }

  // Fuente
  $font = strtolower((string)($r2['font_family'] ?? ''));
  if ($font === 'times') {
    $fontName = 'times';
  } elseif ($font === 'courier') {
    $fontName = 'courier';
  } else {
    $fontName = 'helvetica';
  }

  $pdf->SetFont($fontName, $isBold ? 'B' : '', $fontPt);

  $pdf->MultiCell(
    $w_mm,
    0,
    $texto,
    0,
    $align,
    false,
    1,
    $x_mm,
    $y_mm,
    true,
    0,
    false,
    true,
    0,
    'M'
  );
}
$st2->close();

// Pintar QR si hay posición guardada
if ($qr_xPct !== null && $qr_yPct !== null && $qr_wPct !== null) {
  $w_qr = $PAGE_W * ($qr_wPct / 100.0);
  $x_qr = $PAGE_W * ($qr_xPct / 100.0) - ($w_qr / 2.0);
  $y_qr = $PAGE_H * ($qr_yPct / 100.0);

  // pequeño ajuste vertical para centrar
  $y_qr = $y_qr - ($w_qr / 2.0) * 0.5;

  $pdf->Image($qr, $x_qr, $y_qr, $w_qr, 0, '', '', '', true, 300, '', false, false, 0, false, false, true);
}

// Salida
$pdf->Output('certificado_preview.pdf', 'I');
