<?php
// /includes/acl.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';

/** Devuelve IDs de roles del usuario desde sesión o BD */
function acl_user_role_ids(): array {
  if (!isset($_SESSION['user'])) return [];
  if (!empty($_SESSION['user']['roles_ids']) && is_array($_SESSION['user']['roles_ids'])) {
    return array_map('intval', $_SESSION['user']['roles_ids']);
  }
  $uid = (int)($_SESSION['user']['id'] ?? 0);
  if ($uid <= 0) return [];
  $st = db()->prepare("SELECT id_rol FROM mtp_usuario_roles WHERE id_usuario = ?");
  $st->bind_param('i', $uid);
  $st->execute();
  $ids = array_map('intval', array_column($st->get_result()->fetch_all(MYSQLI_ASSOC), 'id_rol'));
  $st->close();
  $_SESSION['user']['roles_ids'] = $ids; // cache
  return $ids;
}

/** ¿El usuario tiene al menos uno de los roles permitidos (por ID)? */
function acl_can_ids(array $allowedIds): bool {
  $allowedIds = array_values(array_unique(array_map('intval', $allowedIds)));
  if (!$allowedIds) return false;
  $userIds = acl_user_role_ids();
  // Llave maestra: Desarrollo (id=1) siempre puede
  if (in_array(1, $userIds, true)) return true;
  return (bool)array_intersect($allowedIds, $userIds);
}

/** Exigir roles por ID: 403 si no cumple */
function acl_require_ids(array $allowedIds): void {
  requireAuth();
  if (!acl_can_ids($allowedIds)) { http_response_code(403); exit('Acceso denegado.'); }
}
