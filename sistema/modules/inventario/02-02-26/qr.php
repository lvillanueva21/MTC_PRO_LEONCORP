<?php
// modules/inventario/qr.php (PNG inline / download)
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/inv_lib.php';

app_log_init(__DIR__ . '/../../logs/inventario_qr.log');

acl_require_ids([1,4,6]);
verificarPermiso(['Desarrollo','Administración','Gerente']);

$u = currentUser();
$empresaId = (int)($u['empresa']['id'] ?? 0);
if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada'); }

$id = (int)($_GET['id'] ?? 0);
$dl = (int)($_GET['dl'] ?? 0); // 1 => descarga
$size = (int)($_GET['s'] ?? 5); // escala phpqrcode (3..10 recomendado)
if ($size < 3) $size = 3;
if ($size > 10) $size = 10;

$mysqli = db();

try {
  if ($id <= 0) { http_response_code(400); exit('ID inválido'); }

  $st = $mysqli->prepare("SELECT id, id_empresa, creado FROM inv_bienes WHERE id=? AND id_empresa=? LIMIT 1");
  $st->bind_param('ii', $id, $empresaId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if (!$row) { http_response_code(404); exit('No encontrado'); }

  $code = inv_codigo($row['id_empresa'], $row['creado'], $row['id']);
  $payload = inv_qr_payload(BASE_URL, $code);

  $qrLib = __DIR__ . '/../phpqrcode/qrlib.php';
  if (!file_exists($qrLib)) {
    app_log('ERROR', 'phpqrcode no encontrado', ['path'=>$qrLib]);
    http_response_code(500); exit('phpqrcode no encontrado');
  }
  require_once $qrLib;

  ob_start();
  // level M, size = $size, margin=1
  QRcode::png($payload, null, QR_ECLEVEL_M, $size, 1);
  $png = ob_get_clean();

  header('Content-Type: image/png');
  if ($dl === 1) {
    header('Content-Disposition: attachment; filename="'.$code.'.png"');
  } else {
    header('Content-Disposition: inline; filename="'.$code.'.png"');
  }
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  echo $png;
  exit;

} catch (Throwable $e) {
  app_log_exception($e, ['id'=>$id, 'empresa'=>$empresaId]);
  http_response_code(500);
  exit('Error de servidor');
}
