<?php
// modules/control_web/proceso/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_process_json_exit')) {
    function cw_process_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_process_strlen')) {
    function cw_process_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_process_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloBase = trim((string)($_POST['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($_POST['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($_POST['descripcion_general'] ?? ''));

$rawTitulos = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? array_values($_POST['item_titulo']) : [];
$rawTextos = isset($_POST['item_texto']) && is_array($_POST['item_texto']) ? array_values($_POST['item_texto']) : [];

$errors = [];

if (cw_process_strlen($tituloBase) > 35) {
    $errors[] = 'El texto base no puede superar 35 caracteres.';
}
if (cw_process_strlen($tituloResaltado) > 35) {
    $errors[] = 'El texto resaltado no puede superar 35 caracteres.';
}
if (cw_process_strlen($descripcionGeneral) > 280) {
    $errors[] = 'La descripcion general no puede superar 280 caracteres.';
}

$itemsCount = max(count($rawTitulos), count($rawTextos));
if ($itemsCount < 3) {
    $errors[] = 'Debes registrar minimo 3 bloques de proceso.';
}
if ($itemsCount > 9) {
    $errors[] = 'Solo se permiten hasta 9 bloques de proceso.';
}

$rawItems = [];
for ($i = 0; $i < $itemsCount; $i++) {
    $num = $i + 1;
    $itemTitulo = trim((string)($rawTitulos[$i] ?? ''));
    $itemTexto = trim((string)($rawTextos[$i] ?? ''));

    if (cw_process_strlen($itemTitulo) > 40) {
        $errors[] = "El titulo del bloque {$num} supera 40 caracteres.";
    }
    if (cw_process_strlen($itemTexto) > 150) {
        $errors[] = "La descripcion del bloque {$num} supera 150 caracteres.";
    }

    $rawItems[] = [
        'titulo' => cw_process_limit_text($itemTitulo, 40),
        'texto' => cw_process_limit_text($itemTexto, 150),
    ];
}

if (!empty($errors)) {
    cw_process_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion de proceso.',
        'errors' => $errors,
    ]);
}

$payload = [
    'titulo_base' => cw_process_limit_text($tituloBase, 35),
    'titulo_resaltado' => cw_process_limit_text($tituloResaltado, 35),
    'descripcion_general' => cw_process_limit_text($descripcionGeneral, 280),
    'items' => cw_process_normalize_items($rawItems),
];

try {
    $cn = db();
    $ok = ($cn instanceof mysqli) ? cw_process_upsert($cn, $payload) : false;
    if (!$ok) {
        cw_process_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar la configuracion de proceso en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_process_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar proceso.',
    ]);
}

cw_process_json_exit([
    'ok' => true,
    'message' => 'Proceso actualizado correctamente.',
]);
