<?php
// modules/control_web/servicios/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_services_json_exit')) {
    function cw_services_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_services_strlen')) {
    function cw_services_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}

if (!function_exists('cw_services_icon_input_valid')) {
    function cw_services_icon_input_valid(string $icon): bool
    {
        $icon = trim($icon);
        if ($icon === '') {
            return true;
        }

        return (bool)preg_match('/^[a-zA-Z0-9 _:\-]+$/', $icon);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_services_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloBase = trim((string)($_POST['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($_POST['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($_POST['descripcion_general'] ?? ''));

$itemIconos = isset($_POST['item_icono']) && is_array($_POST['item_icono']) ? array_values($_POST['item_icono']) : [];
$itemTitulos = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? array_values($_POST['item_titulo']) : [];
$itemTextos = isset($_POST['item_texto']) && is_array($_POST['item_texto']) ? array_values($_POST['item_texto']) : [];

$errors = [];

if (cw_services_strlen($tituloBase) > 40) {
    $errors[] = 'El texto base no puede superar 40 caracteres.';
}
if (cw_services_strlen($tituloResaltado) > 40) {
    $errors[] = 'El texto resaltado no puede superar 40 caracteres.';
}
if (cw_services_strlen($descripcionGeneral) > 320) {
    $errors[] = 'La descripcion general no puede superar 320 caracteres.';
}

$rawItems = [];
for ($i = 0; $i < 6; $i++) {
    $num = $i + 1;
    $icon = trim((string)($itemIconos[$i] ?? ''));
    $itemTitle = trim((string)($itemTitulos[$i] ?? ''));
    $itemText = trim((string)($itemTextos[$i] ?? ''));

    if (!cw_services_icon_input_valid($icon)) {
        $errors[] = "El codigo de icono del servicio {$num} es invalido.";
    }
    if (cw_services_strlen($icon) > 120) {
        $errors[] = "El codigo de icono del servicio {$num} supera 120 caracteres.";
    }
    if (cw_services_strlen($itemTitle) > 55) {
        $errors[] = "El titulo del servicio {$num} supera 55 caracteres.";
    }
    if (cw_services_strlen($itemText) > 170) {
        $errors[] = "El texto del servicio {$num} supera 170 caracteres.";
    }

    $rawItems[] = [
        'icono' => cw_services_limit_text($icon, 120),
        'titulo' => cw_services_limit_text($itemTitle, 55),
        'texto' => cw_services_limit_text($itemText, 170),
    ];
}

if (!empty($errors)) {
    cw_services_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion de servicios.',
        'errors' => $errors,
    ]);
}

$payload = [
    'titulo_base' => cw_services_limit_text($tituloBase, 40),
    'titulo_resaltado' => cw_services_limit_text($tituloResaltado, 40),
    'descripcion_general' => cw_services_limit_text($descripcionGeneral, 320),
    'items' => cw_services_normalize_items($rawItems),
];

try {
    $cn = db();
    $ok = ($cn instanceof mysqli) ? cw_services_upsert($cn, $payload) : false;
    if (!$ok) {
        cw_services_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar la configuracion de servicios en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_services_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar servicios.',
    ]);
}

cw_services_json_exit([
    'ok' => true,
    'message' => 'Servicios actualizados correctamente.',
]);
