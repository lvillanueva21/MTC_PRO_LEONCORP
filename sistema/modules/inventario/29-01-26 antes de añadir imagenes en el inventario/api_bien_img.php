<?php
// modules/inventario/api_bien_img.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/inv_s4.php';

app_log_init(__DIR__ . '/../../logs/inventario_img.log');
header('Content-Type: application/json; charset=utf-8');

function jerr(int $code, string $msg): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}
function jok(array $a): void {
  echo json_encode(['ok' => true] + $a, JSON_UNESCAPED_UNICODE);
  exit;
}

function read_payload(): array {
  $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
  $raw = file_get_contents('php://input');

  // JSON
  if (strpos($ct, 'application/json') !== false) {
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
  }

  // FormData / x-www-form-urlencoded (fallback)
  if (!empty($_POST) && is_array($_POST)) return $_POST;

  // si no hay POST, intenta parsear querystring crudo
  $out = [];
  parse_str($raw ?: '', $out);
  return is_array($out) ? $out : [];
}

try {
  acl_require_ids([1,4,6]);
  verificarPermiso(['Desarrollo','Administración']);

  $u = currentUser();
  $empresaId = (int)($u['empresa']['id'] ?? 0);
  if ($empresaId <= 0) jerr(403, 'Empresa no asignada');

  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') jerr(405, 'Método no permitido');

  $data = read_payload();

  // soporta ambos nombres por si el frontend cambia
  $nombre = trim((string)($data['nombre'] ?? $data['filename'] ?? 'bien'));
  $mime   = trim((string)($data['mime'] ?? $data['content_type'] ?? ''));

  if ($mime === '') jerr(400, 'MIME requerido');

  $signed = inv_s4_sign_put($empresaId, $nombre, $mime, '+60 minutes');

  jok([
    'data' => [
      'key' => $signed['key'],
      'uploadUrl' => $signed['uploadUrl'],
      'headers' => $signed['headers'],
    ]
  ]);

} catch (Throwable $e) {
  app_log_exception($e, ['op' => 'sign_put']);
  jerr(500, 'Error firmando subida');
}
