<?php
// modules/control_web/contadores/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_counter_json_exit')) {
    function cw_counter_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_counter_strlen')) {
    function cw_counter_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_counter_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$rawNumeros = isset($_POST['item_numero']) && is_array($_POST['item_numero']) ? array_values($_POST['item_numero']) : [];
$rawTitulos = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? array_values($_POST['item_titulo']) : [];

$errors = [];
$rawItems = [];

for ($i = 0; $i < 4; $i++) {
    $num = $i + 1;
    $numero = trim((string)($rawNumeros[$i] ?? ''));
    $titulo = trim((string)($rawTitulos[$i] ?? ''));

    if ($numero !== '' && !preg_match('/^\d{1,8}$/', $numero)) {
        $errors[] = "El numero del contador {$num} solo permite digitos (maximo 8).";
    }
    if (cw_counter_strlen($titulo) > 80) {
        $errors[] = "El titulo del contador {$num} supera 80 caracteres.";
    }

    $rawItems[] = [
        'numero' => cw_counter_limit_text($numero, 8),
        'titulo' => cw_counter_limit_text($titulo, 80),
    ];
}

if (!empty($errors)) {
    cw_counter_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion de contadores.',
        'errors' => $errors,
    ]);
}

$payload = [
    'items' => cw_counter_normalize_items($rawItems),
];

try {
    $cn = db();
    $ok = ($cn instanceof mysqli) ? cw_counter_upsert($cn, $payload) : false;
    if (!$ok) {
        cw_counter_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar la configuracion de contadores en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_counter_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar los contadores.',
    ]);
}

cw_counter_json_exit([
    'ok' => true,
    'message' => 'Contadores actualizados correctamente.',
]);
