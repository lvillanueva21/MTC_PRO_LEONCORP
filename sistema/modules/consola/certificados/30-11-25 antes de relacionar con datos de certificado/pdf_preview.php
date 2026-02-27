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

$fondo = $row['fondo_path'] ? abs_path($ROOT, $row['fondo_path']) : $PH_FONDO;
$logo  = $row['logo_path']  ? abs_path($ROOT, $row['logo_path'])  : $PH_LOGO;
$firma = $row['firma_path'] ? abs_path($ROOT, $row['firma_path']) : $PH_FIRMA;

if (!is_file($fondo)) $fondo = $PH_FONDO;
if (!is_file($logo))  $logo  = $PH_LOGO;
if (!is_file($firma)) $firma = $PH_FIRMA;

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

// Salida
$pdf->Output('certificado_preview.pdf', 'I');
