<?php
// modules/inventario/qr_public.php (PNG público: inline / download)
// NO requiere sesión. Valida que el bien exista para el código.

require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/inv_lib.php';

$codeRaw = trim((string)($_GET['code'] ?? ''));
$dl      = (int)($_GET['dl'] ?? 0);   // 1 => descarga
$size    = (int)($_GET['s'] ?? 6);    // escala phpqrcode (3..10 recomendado)
if ($size < 3) $size = 3;
if ($size > 10) $size = 10;

function out_err($http, $msg) {
  http_response_code($http);
  header('Content-Type: text/plain; charset=utf-8');
  echo $msg;
  exit;
}

$info = inv_parse_codigo($codeRaw);
if (!$info) out_err(400, 'Código inválido');

$empresaId = (int)$info['empresa'];
$bienId    = (int)$info['id'];
if ($empresaId <= 0 || $bienId <= 0) out_err(400, 'Código inválido');

$mysqli = db();

// Validar existencia y obtener fecha real de creado
$st = $mysqli->prepare("SELECT id, id_empresa, creado FROM inv_bienes WHERE id=? AND id_empresa=? LIMIT 1");
$st->bind_param('ii', $bienId, $empresaId);
$st->execute();
$row = $st->get_result()->fetch_assoc();
if (!$row) out_err(404, 'No encontrado');

// Reconstruir código real desde BD (por consistencia)
$code = inv_codigo($row['id_empresa'], $row['creado'], $row['id']);

// Construir payload ABSOLUTO al detalle público
$payload = inv_qr_payload(defined('BASE_URL') ? BASE_URL : '', $code);

// Cargar phpqrcode
$qrLib = __DIR__ . '/../phpqrcode/qrlib.php';
if (!file_exists($qrLib)) out_err(500, 'phpqrcode no encontrado');
require_once $qrLib;

// Generar PNG en memoria
ob_start();
QRcode::png($payload, null, QR_ECLEVEL_M, $size, 1);
$png = ob_get_clean();

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($dl === 1) {
  header('Content-Disposition: attachment; filename="'.$code.'.png"');
} else {
  header('Content-Disposition: inline; filename="'.$code.'.png"');
}

echo $png;
exit;
