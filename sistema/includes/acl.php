<?php
// /includes/acl.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/control_especial_catalog.php';

/** Devuelve IDs de roles del usuario desde sesion o BD */
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

  $_SESSION['user']['roles_ids'] = $ids;
  return $ids;
}

/** ID del usuario actual */
function acl_current_user_id(): int {
  return (int)($_SESSION['user']['id'] ?? 0);
}

/** El usuario (actual o indicado) tiene un rol especifico */
function acl_user_has_role_id(int $roleId, ?int $userId = null): bool {
  $roleId = (int)$roleId;
  if ($roleId <= 0) return false;

  $uid = (int)($userId ?? acl_current_user_id());
  if ($uid <= 0) return false;

  if ($uid === acl_current_user_id()) {
    return in_array($roleId, acl_user_role_ids(), true);
  }

  $st = db()->prepare("SELECT 1 FROM mtp_usuario_roles WHERE id_usuario = ? AND id_rol = ? LIMIT 1");
  if (!$st) return false;
  $st->bind_param('ii', $uid, $roleId);
  $st->execute();
  $ok = (bool)$st->get_result()->fetch_row();
  $st->close();
  return $ok;
}

/** El usuario tiene al menos uno de los roles permitidos (por ID) */
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
  if (!acl_can_ids($allowedIds)) {
    http_response_code(403);
    exit('Acceso denegado.');
  }
}

/**
 * Slugs de modulos clasicos asignados al usuario Control.
 * Lee solo de tabla dedicada para Control Especial.
 */
function acl_control_special_assigned_slugs(int $userId): array {
  $uid = (int)$userId;
  if ($uid <= 0) return [];

  $currentUid = acl_current_user_id();
  if (
    $uid === $currentUid &&
    !empty($_SESSION['user']['control_especial_modulos']) &&
    is_array($_SESSION['user']['control_especial_modulos'])
  ) {
    return array_values(array_unique(array_map('strval', $_SESSION['user']['control_especial_modulos'])));
  }

  $st = db()->prepare("
    SELECT modulo_slug
    FROM mtp_control_modulos_usuario
    WHERE id_usuario = ? AND estado = 1
  ");
  if (!$st) return [];

  $st->bind_param('i', $uid);
  $st->execute();
  $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  $st->close();

  $slugs = [];
  foreach ($rows as $row) {
    $slug = trim((string)($row['modulo_slug'] ?? ''));
    if ($slug !== '' && control_especial_catalog_has_slug($slug)) {
      $slugs[$slug] = true;
    }
  }

  $list = array_keys($slugs);
  if ($uid === $currentUid) {
    $_SESSION['user']['control_especial_modulos'] = $list;
  }
  return $list;
}

/** Limpia cache de modulos especiales en sesion */
function acl_control_special_cache_forget(): void {
  if (isset($_SESSION['user']['control_especial_modulos'])) {
    unset($_SESSION['user']['control_especial_modulos']);
  }
}

/** Usuario Control (rol 2) con permiso explicito para modulo_slug */
function acl_user_has_control_special_module(string $moduloSlug, ?int $userId = null): bool {
  $slug = trim($moduloSlug);
  if (!control_especial_catalog_has_slug($slug)) return false;

  $uid = (int)($userId ?? acl_current_user_id());
  if ($uid <= 0) return false;
  if (!acl_user_has_role_id(2, $uid)) return false;

  $assigned = acl_control_special_assigned_slugs($uid);
  return in_array($slug, $assigned, true);
}

/** Roles normales permitidos OR Control con permiso especial del modulo */
function acl_can_ids_or_control_special(array $allowedIds, string $moduloSlug): bool {
  if (acl_can_ids($allowedIds)) return true;
  return acl_user_has_control_special_module($moduloSlug);
}

/** Exigir roles normales o permiso especial Control por modulo */
function acl_require_ids_or_control_special(array $allowedIds, string $moduloSlug): void {
  requireAuth();
  if (!acl_can_ids_or_control_special($allowedIds, $moduloSlug)) {
    http_response_code(403);
    exit('Acceso denegado.');
  }
}
