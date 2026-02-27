<?php
// modules/inventario/img.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/inv_s4.php';

try {
  acl_require_ids([1,4,6]);
  verificarPermiso(['Desarrollo','Administración','Gerente']);

  $u = currentUser();
  $empresaId = (int)($u['empresa']['id'] ?? 0);
  if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada'); }

  $key = trim((string)($_GET['key'] ?? ''));
  if ($key === '') { http_response_code(400); exit('key requerido'); }

  if (!inv_s4_key_allowed($key, $empresaId)) { http_response_code(403); exit('key no permitido'); }

  $url = inv_s4_presign_get($key, '+2 hours');

  header('Cache-Control: private, max-age=300');
  header('Location: ' . $url, true, 302);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  exit('Error');
}
