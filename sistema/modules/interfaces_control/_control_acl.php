<?php
// /modules/interfaces_control/_control_acl.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

require_once __DIR__ . '/_scanner.php';

if (!function_exists('ic_user_has_role_control')) {
    function ic_user_has_role_control($mysqli, $userId)
    {
        $uid = (int)$userId;
        if ($uid <= 0) {
            return false;
        }
        $st = $mysqli->prepare("SELECT 1 FROM mtp_usuario_roles WHERE id_usuario=? AND id_rol=2 LIMIT 1");
        if (!$st) {
            return false;
        }
        $st->bind_param('i', $uid);
        $st->execute();
        $ok = (bool)$st->get_result()->fetch_row();
        $st->close();
        return $ok;
    }
}

if (!function_exists('ic_get_user_assigned_slugs')) {
    function ic_get_user_assigned_slugs($mysqli, $userId)
    {
        $uid = (int)$userId;
        if ($uid <= 0) {
            return array();
        }
        $st = $mysqli->prepare("
            SELECT interface_slug
            FROM mtp_control_interfaces_usuario
            WHERE id_usuario=? AND estado=1
        ");
        if (!$st) {
            return array();
        }
        $st->bind_param('i', $uid);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();

        $slugs = array();
        foreach ($rows as $r) {
            $s = trim((string)($r['interface_slug'] ?? ''));
            if ($s !== '' && ic_slug_is_valid($s)) {
                $slugs[$s] = true;
            }
        }
        return array_keys($slugs);
    }
}

if (!function_exists('ic_build_control_menu_items')) {
    function ic_build_control_menu_items($mysqli, $userId)
    {
        $uid = (int)$userId;
        if ($uid <= 0) {
            return array();
        }
        if (!ic_user_has_role_control($mysqli, $uid)) {
            return array();
        }

        $assigned = array_flip(ic_get_user_assigned_slugs($mysqli, $uid));
        if (!$assigned) {
            return array();
        }

        $interfaces = ic_interfaces_scan();
        $menuItems = array();
        foreach ($interfaces as $it) {
            if ((int)$it['activo'] !== 1) {
                continue;
            }
            $slug = (string)$it['slug'];
            if (!isset($assigned[$slug])) {
                continue;
            }
            $menuItems[] = array(
                'path'  => (string)$it['path'],
                'icon'  => (string)$it['icon'],
                'label' => (string)$it['label'],
                'roles' => array(2),
            );
        }
        return $menuItems;
    }
}

if (!function_exists('ic_require_control_interface')) {
    function ic_require_control_interface($slug)
    {
        $slug = trim((string)$slug);
        if (!ic_slug_is_valid($slug)) {
            http_response_code(403);
            exit('Interfaz invalida.');
        }

        $u = currentUser();
        $uid = (int)($u['id'] ?? 0);
        $rolActivoId = (int)($u['rol_activo_id'] ?? 0);

        // Desarrollo siempre puede
        if ($rolActivoId === 1) {
            return;
        }

        // Este guard solo aplica para rol Control
        if ($rolActivoId !== 2) {
            http_response_code(403);
            exit('Acceso denegado para este rol.');
        }

        $mysqli = db();
        $st = $mysqli->prepare("
            SELECT 1
            FROM mtp_control_interfaces_usuario
            WHERE id_usuario=? AND interface_slug=? AND estado=1
            LIMIT 1
        ");
        if (!$st) {
            http_response_code(403);
            exit('Tabla de permisos de interfaces no disponible.');
        }
        $st->bind_param('is', $uid, $slug);
        $st->execute();
        $ok = (bool)$st->get_result()->fetch_row();
        $st->close();

        if (!$ok) {
            http_response_code(403);
            exit('No tienes acceso a esta interfaz.');
        }
    }
}
