<?php
// modules/inventario/img_public.php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/inv_lib.php';
require_once __DIR__ . '/inv_s4.php';

$code = trim((string)($_GET['code'] ?? ''));
$info = inv_parse_codigo($code);
if (!$info) { http_response_code(400); exit('Código inválido'); }

$empresaId = (int)$info['empresa'];
$id = (int)$info['id'];

$mysqli = db();

$st = $mysqli->prepare("SELECT img_key FROM inv_bienes WHERE id_empresa=? AND id=? LIMIT 1");
$st->bind_param('ii', $empresaId, $id);
$st->execute();
$row = $st->get_result()->fetch_assoc();

$key = trim((string)($row['img_key'] ?? ''));
if ($key === '') { http_response_code(404); exit('Sin imagen'); }

// Seguridad: solo permitir keys dentro del tenant + empresa
if (!inv_s4_key_allowed($key, $empresaId)) {
  http_response_code(403); exit('Key no autorizada');
}

try {
  $url = inv_s4_presign_get($key, '+2 hours');
  header('Cache-Control: private, max-age=0, no-store');
  header('Location: ' . $url, true, 302);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  exit('Error de imagen');
}
