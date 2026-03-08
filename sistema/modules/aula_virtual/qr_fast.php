<?php
// Ver 08-03-26
// modules/aula_virtual/qr_fast.php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/formularios_lib.php';

date_default_timezone_set('America/Lima');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

try {
  $code = trim((string)($_GET['c'] ?? ''));
  if ($code === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Parametro c requerido.');
  }

  $form = avf_load_form_public_by_code($db, $code);
  if (!$form) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Formulario FAST no encontrado.');
  }

  $qrLib = __DIR__ . '/../phpqrcode/qrlib.php';
  if (!is_file($qrLib)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('No se encontro phpqrcode.');
  }
  require_once $qrLib;

  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $scriptDir = rtrim($scriptDir, '/');
  if ($scriptDir === '' || $scriptDir === '.') $scriptDir = '/modules/aula_virtual';

  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $target = $scheme . '://' . $host . $scriptDir . '/form_fast.php?c=' . rawurlencode($code);

  header('Content-Type: image/png');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');

  QRcode::png($target, null, QR_ECLEVEL_M, 8, 2);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  exit('No se pudo generar el QR.');
}
