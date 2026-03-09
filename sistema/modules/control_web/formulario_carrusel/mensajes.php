<?php
// modules/control_web/formulario_carrusel/mensajes.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_fc_messages_json_exit')) {
    function cw_fc_messages_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? 'list'));

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_fc_messages_json_exit([
        'ok' => false,
        'message' => 'No se pudo obtener conexion a base de datos.',
    ]);
}

if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $result = cw_fc_messages_list($cn, $page, 10);
    $statusOptions = cw_fc_status_options();

    $rows = [];
    foreach ($result['rows'] as $row) {
        $status = (string)($row['estado'] ?? 'en_espera');
        $statusLabel = (string)($statusOptions[$status] ?? 'En espera');
        $badgeClass = cw_fc_status_badge_class($status);

        $rows[] = [
            'id' => (int)$row['id'],
            'tipo_solicitante' => (string)$row['tipo_solicitante'],
            'servicio_nombre' => (string)$row['servicio_nombre'],
            'ciudad_nombre' => (string)$row['ciudad_nombre'],
            'escuela_nombre' => (string)$row['escuela_nombre'],
            'documento' => (string)$row['documento'],
            'nombres_apellidos' => (string)$row['nombres_apellidos'],
            'razon_social' => (string)$row['razon_social'],
            'celular' => (string)$row['celular'],
            'correo' => (string)$row['correo'],
            'horario_nombre' => (string)$row['horario_nombre'],
            'estado' => $status,
            'estado_label' => $statusLabel,
            'estado_badge_class' => $badgeClass,
            'fecha_registro' => (string)$row['fecha_registro'],
        ];
    }

    cw_fc_messages_json_exit([
        'ok' => true,
        'message' => 'Listado cargado.',
        'rows' => $rows,
        'pagination' => [
            'page' => (int)$result['page'],
            'per_page' => (int)$result['per_page'],
            'total' => (int)$result['total'],
            'pages' => (int)$result['pages'],
        ],
        'status_options' => $statusOptions,
    ]);
}

if ($action === 'update_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'Metodo no permitido.',
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    $status = trim((string)($_POST['estado'] ?? ''));

    if ($id < 1) {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'Mensaje invalido.',
        ]);
    }

    if (!isset(cw_fc_status_options()[$status])) {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'Estado invalido.',
        ]);
    }

    $ok = cw_fc_update_message_status($cn, $id, $status);
    if (!$ok) {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'No se pudo actualizar el estado del mensaje.',
        ]);
    }

    cw_fc_messages_json_exit([
        'ok' => true,
        'message' => 'Estado actualizado correctamente.',
    ]);
}

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'Metodo no permitido.',
        ]);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'Mensaje invalido.',
        ]);
    }

    $ok = cw_fc_delete_message($cn, $id);
    if (!$ok) {
        cw_fc_messages_json_exit([
            'ok' => false,
            'message' => 'No se pudo eliminar el mensaje.',
        ]);
    }

    cw_fc_messages_json_exit([
        'ok' => true,
        'message' => 'Mensaje eliminado correctamente.',
    ]);
}

cw_fc_messages_json_exit([
    'ok' => false,
    'message' => 'Accion no valida.',
]);