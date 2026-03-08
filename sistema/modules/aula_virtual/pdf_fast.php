<?php
// Ver 08-03-26
// modules/aula_virtual/pdf_fast.php
require_once __DIR__ . '/../../includes/conexion.php';

date_default_timezone_set('America/Lima');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$token = trim((string)($_GET['t'] ?? ''));
if ($token === '') {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  exit('Parametro t requerido.');
}

try {
  $db = db();
  $db->set_charset('utf8mb4');

  $st = $db->prepare(
    "SELECT
       i.id,
       i.formulario_id,
       i.modo,
       i.tipo_doc_id,
       i.nro_doc,
       i.nombres,
       i.apellidos,
       i.celular,
       i.categorias_json,
       i.intento_nro,
       i.token,
       i.status,
       i.start_at,
       i.submitted_at,
       i.nota_final,
       i.puntaje_obtenido,
       i.aprobado,
       f.titulo AS formulario_titulo,
       f.public_code,
       f.nota_min,
       td.codigo AS tipo_doc_codigo
     FROM cr_formulario_intentos i
     JOIN cr_formularios f ON f.id = i.formulario_id
     LEFT JOIN cq_tipos_documento td ON td.id = i.tipo_doc_id
     WHERE i.token = ?
       AND i.modo = 'FAST'
     LIMIT 1"
  );
  $st->bind_param('s', $token);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if (!$row) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Intento FAST no encontrado.');
  }
  if (strtoupper((string)$row['status']) !== 'ENVIADO') {
    http_response_code(409);
    header('Content-Type: text/plain; charset=utf-8');
    exit('El intento aun no fue enviado.');
  }

  $cats = [];
  $stCat = $db->prepare(
    "SELECT c.codigo
     FROM cr_formulario_intento_categoria ic
     JOIN cq_categorias_licencia c ON c.id = ic.categoria_id
     WHERE ic.intento_id = ?
     ORDER BY c.codigo ASC"
  );
  $attemptId = (int)$row['id'];
  $stCat->bind_param('i', $attemptId);
  $stCat->execute();
  $rsCat = $stCat->get_result();
  while ($r = $rsCat->fetch_assoc()) {
    $cats[] = (string)$r['codigo'];
  }

  $tcpdfFile = __DIR__ . '/../TCPDF/tcpdf.php';
  if (!is_file($tcpdfFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('No se encontro TCPDF.');
  }
  require_once $tcpdfFile;

  $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
  $pdf->SetCreator('Aula Virtual');
  $pdf->SetAuthor('Aula Virtual');
  $pdf->SetTitle('Resultado FAST');
  $pdf->SetSubject('Examen FAST');
  $pdf->SetMargins(12, 14, 12);
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);
  $pdf->AddPage();
  $pdf->SetFont('helvetica', '', 11);

  $nombre = trim((string)$row['nombres'] . ' ' . (string)$row['apellidos']);
  if ($nombre === '') $nombre = 'Participante FAST';

  $nota = ($row['nota_final'] !== null) ? number_format((float)$row['nota_final'], 2) : '-';
  $puntaje = ($row['puntaje_obtenido'] !== null) ? number_format((float)$row['puntaje_obtenido'], 2) : '-';
  $aprob = ((int)($row['aprobado'] ?? 0) === 1) ? 'SI' : 'NO';
  $doc = trim((string)($row['tipo_doc_codigo'] ?? 'DOC') . ' ' . (string)($row['nro_doc'] ?? ''));
  $cel = trim((string)($row['celular'] ?? ''));
  $fecha = trim((string)($row['submitted_at'] ?? ''));
  $catsTxt = $cats ? implode(', ', $cats) : '-';

  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $scriptDir = rtrim($scriptDir, '/');
  if ($scriptDir === '' || $scriptDir === '.') $scriptDir = '/modules/aula_virtual';
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $link = $scheme . '://' . $host . $scriptDir . '/form_fast.php?c=' . rawurlencode((string)$row['public_code']);

  $html = ''
    . '<h2 style="margin:0 0 8px 0;">Resultado de Examen FAST</h2>'
    . '<table cellpadding="4" cellspacing="0" border="1" width="100%">'
    . '<tr><td width="35%"><strong>Formulario</strong></td><td width="65%">' . htmlspecialchars((string)$row['formulario_titulo']) . '</td></tr>'
    . '<tr><td><strong>Participante</strong></td><td>' . htmlspecialchars($nombre) . '</td></tr>'
    . '<tr><td><strong>Documento</strong></td><td>' . htmlspecialchars($doc) . '</td></tr>'
    . '<tr><td><strong>Celular</strong></td><td>' . htmlspecialchars($cel !== '' ? $cel : '-') . '</td></tr>'
    . '<tr><td><strong>Categorias</strong></td><td>' . htmlspecialchars($catsTxt) . '</td></tr>'
    . '<tr><td><strong>Intento</strong></td><td>#' . (int)$row['intento_nro'] . '</td></tr>'
    . '<tr><td><strong>Fecha envio</strong></td><td>' . htmlspecialchars($fecha !== '' ? $fecha : '-') . '</td></tr>'
    . '<tr><td><strong>Puntaje</strong></td><td>' . htmlspecialchars($puntaje) . '</td></tr>'
    . '<tr><td><strong>Nota final</strong></td><td>' . htmlspecialchars($nota) . '</td></tr>'
    . '<tr><td><strong>Aprobado (min ' . number_format((float)$row['nota_min'], 2) . ')</strong></td><td>' . $aprob . '</td></tr>'
    . '<tr><td><strong>Token</strong></td><td>' . htmlspecialchars((string)$row['token']) . '</td></tr>'
    . '<tr><td><strong>Link formulario</strong></td><td>' . htmlspecialchars($link) . '</td></tr>'
    . '</table>';

  $pdf->writeHTML($html, true, false, true, false, '');
  $pdf->Ln(4);
  $pdf->SetFont('helvetica', '', 9);
  $pdf->MultiCell(0, 0, 'Documento generado por Aula Virtual. Este PDF resume el resultado del intento enviado.', 0, 'L', false, 1);

  $name = 'resultado_fast_' . (int)$row['id'] . '.pdf';
  $pdf->Output($name, 'I');
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  exit('No se pudo generar el PDF.');
}
