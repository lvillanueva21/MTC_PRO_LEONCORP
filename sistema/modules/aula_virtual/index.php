<?php
// modules/aula_virtual/index.php (router por rol activo)
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';

acl_require_ids([7,1,4,6]);

$u = currentUser();
$rolActivoId = (int)($u['rol_activo_id'] ?? 0);

// IDs de rol en mtp_roles: 1 Desarrollo, 4 Administracion, 6 Gerente, 7 Cliente
$roleViews = [
  7 => __DIR__ . '/index_cliente.php',
  4 => __DIR__ . '/index_administracion.php',
  6 => __DIR__ . '/index_gerente.php',
  1 => __DIR__ . '/index_desarrollo.php',
];

$target = $roleViews[$rolActivoId] ?? null;
$viewRoleId = $target ? $rolActivoId : 0;

if (!$target) {
  $rolActivo = strtolower(trim((string)($u['rol_activo'] ?? '')));
  if ($rolActivo !== '') {
    if (strpos($rolActivo, 'cliente') !== false) {
      $target = $roleViews[7];
      $viewRoleId = 7;
    } elseif (strpos($rolActivo, 'admin') !== false) {
      $target = $roleViews[4];
      $viewRoleId = 4;
    } elseif (strpos($rolActivo, 'gerente') !== false) {
      $target = $roleViews[6];
      $viewRoleId = 6;
    } elseif (strpos($rolActivo, 'desarrollo') !== false) {
      $target = $roleViews[1];
      $viewRoleId = 1;
    }
  }
}

if (!$target) {
  $userRoles = array_map('intval', (array)($u['roles_ids'] ?? []));
  foreach ([7, 4, 6, 1] as $rid) {
    if (in_array($rid, $userRoles, true) && isset($roleViews[$rid])) {
      $target = $roleViews[$rid];
      $viewRoleId = $rid;
      break;
    }
  }
}

if (!$target || !is_file($target)) {
  http_response_code(403);
  exit('Acceso denegado.');
}

define('AULA_VIRTUAL_ROLE_ROUTED', true);
define('AULA_VIRTUAL_VIEW_ROLE_ID', (int)$viewRoleId);

require $target;
