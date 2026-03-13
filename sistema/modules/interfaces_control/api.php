<?php
// /modules/interfaces_control/api.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/_scanner.php';
require_once __DIR__ . '/_control_acl.php';

acl_require_ids(array(1));
verificarPermiso(array('Desarrollo'));

header('Content-Type: application/json; charset=utf-8');

function ic_jerror($code, $msg, $extra = array())
{
    http_response_code((int)$code);
    echo json_encode(array('ok' => false, 'msg' => (string)$msg) + $extra);
    exit;
}

function ic_jok($payload = array())
{
    echo json_encode(array('ok' => true) + (array)$payload);
    exit;
}

function ic_parse_slugs_from_request()
{
    $slugs = array();
    if (isset($_POST['slugs']) && is_array($_POST['slugs'])) {
        $slugs = $_POST['slugs'];
    } elseif (isset($_POST['slugs']) && is_string($_POST['slugs'])) {
        $slugs = explode(',', $_POST['slugs']);
    }

    $clean = array();
    foreach ((array)$slugs as $s) {
        $slug = trim((string)$s);
        if ($slug !== '' && ic_slug_is_valid($slug)) {
            $clean[$slug] = true;
        }
    }
    return array_keys($clean);
}

try {
    $mysqli = db();
    $mysqli->set_charset('utf8mb4');

    $action = isset($_REQUEST['action']) ? trim((string)$_REQUEST['action']) : '';
    if ($action === '') {
        ic_jerror(400, 'Accion requerida.');
    }

    if ($action === 'list_interfaces') {
        $rows = array();
        foreach (ic_interfaces_scan() as $it) {
            if ((int)$it['activo'] !== 1) {
                continue;
            }
            $rows[] = $it;
        }
        ic_jok(array('data' => $rows));
    }

    if ($action === 'list_control_users') {
        $sql = "
            SELECT DISTINCT
                u.id,
                u.usuario,
                u.nombres,
                u.apellidos,
                e.nombre AS empresa
            FROM mtp_usuarios u
            INNER JOIN mtp_usuario_roles ur
                ON ur.id_usuario = u.id
               AND ur.id_rol = 2
            LEFT JOIN mtp_empresas e
                ON e.id = u.id_empresa
            ORDER BY u.nombres, u.apellidos, u.usuario
        ";
        $rs = $mysqli->query($sql);
        ic_jok(array('data' => $rs->fetch_all(MYSQLI_ASSOC)));
    }

    if ($action === 'get_assignments') {
        $uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($uid <= 0) {
            ic_jerror(400, 'Usuario invalido.');
        }
        if (!ic_user_has_role_control($mysqli, $uid)) {
            ic_jerror(400, 'El usuario no tiene rol Control.');
        }
        $slugs = ic_get_user_assigned_slugs($mysqli, $uid);
        ic_jok(array('user_id' => $uid, 'slugs' => $slugs));
    }

    if ($action === 'save_assignments') {
        $uid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($uid <= 0) {
            ic_jerror(400, 'Usuario invalido.');
        }
        if (!ic_user_has_role_control($mysqli, $uid)) {
            ic_jerror(400, 'El usuario no tiene rol Control.');
        }

        $available = array();
        foreach (ic_interfaces_scan() as $it) {
            if ((int)$it['activo'] === 1) {
                $available[(string)$it['slug']] = true;
            }
        }

        $incoming = ic_parse_slugs_from_request();
        $filtered = array();
        foreach ($incoming as $slug) {
            if (isset($available[$slug])) {
                $filtered[] = $slug;
            }
        }

        $updatedBy = (int)($_SESSION['user']['id'] ?? 0);

        $mysqli->begin_transaction();
        try {
            $off = $mysqli->prepare("
                UPDATE mtp_control_interfaces_usuario
                   SET estado=0,
                       actualizado_en=CURRENT_TIMESTAMP,
                       actualizado_por=?
                 WHERE id_usuario=?
            ");
            if (!$off) {
                throw new RuntimeException('Tabla de asignaciones no disponible.');
            }
            $off->bind_param('ii', $updatedBy, $uid);
            $off->execute();
            $off->close();

            if (!empty($filtered)) {
                $ins = $mysqli->prepare("
                    INSERT INTO mtp_control_interfaces_usuario
                        (id_usuario, interface_slug, estado, actualizado_por)
                    VALUES (?, ?, 1, ?)
                    ON DUPLICATE KEY UPDATE
                        estado=1,
                        actualizado_por=VALUES(actualizado_por),
                        actualizado_en=CURRENT_TIMESTAMP
                ");
                if (!$ins) {
                    throw new RuntimeException('No se pudo preparar insercion de asignaciones.');
                }
                foreach ($filtered as $slug) {
                    $ins->bind_param('isi', $uid, $slug, $updatedBy);
                    $ins->execute();
                }
                $ins->close();
            }

            $mysqli->commit();
            ic_jok(array('user_id' => $uid, 'saved' => $filtered));
        } catch (Throwable $e) {
            $mysqli->rollback();
            ic_jerror(500, 'No se pudo guardar asignaciones.', array('dev' => $e->getMessage()));
        }
    }

    ic_jerror(400, 'Accion no valida.');
} catch (Throwable $e) {
    ic_jerror(500, 'Error del servidor.', array('dev' => $e->getMessage()));
}
