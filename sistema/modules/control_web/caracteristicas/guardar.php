<?php
// modules/control_web/caracteristicas/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_features_json_exit')) {
    function cw_features_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_features_strlen')) {
    function cw_features_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}

if (!function_exists('cw_features_icon_input_valid')) {
    function cw_features_icon_input_valid(string $icon): bool
    {
        $icon = trim($icon);
        if ($icon === '') {
            return true;
        }

        return (bool)preg_match('/^[a-zA-Z0-9 _:\-]+$/', $icon);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_features_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloRojo = trim((string)($_POST['titulo_rojo'] ?? ''));
$tituloAzul = trim((string)($_POST['titulo_azul'] ?? ''));
$descripcionGeneral = trim((string)($_POST['descripcion_general'] ?? ''));
$eliminarImagen = !empty($_POST['eliminar_imagen']);

$itemIconos = isset($_POST['item_icono']) && is_array($_POST['item_icono']) ? array_values($_POST['item_icono']) : [];
$itemTitulos = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? array_values($_POST['item_titulo']) : [];
$itemTextos = isset($_POST['item_texto']) && is_array($_POST['item_texto']) ? array_values($_POST['item_texto']) : [];

$errors = [];

if (cw_features_strlen($tituloRojo) > 40) {
    $errors[] = 'El texto rojo del titulo no puede superar 40 caracteres.';
}
if (cw_features_strlen($tituloAzul) > 40) {
    $errors[] = 'El texto azul del titulo no puede superar 40 caracteres.';
}
if (cw_features_strlen($descripcionGeneral) > 320) {
    $errors[] = 'La descripcion general no puede superar 320 caracteres.';
}

$rawItems = [];
for ($i = 0; $i < 4; $i++) {
    $icon = trim((string)($itemIconos[$i] ?? ''));
    $itemTitle = trim((string)($itemTitulos[$i] ?? ''));
    $itemText = trim((string)($itemTextos[$i] ?? ''));
    $num = $i + 1;

    if (!cw_features_icon_input_valid($icon)) {
        $errors[] = "El codigo de icono de la caracteristica {$num} es invalido.";
    }
    if (cw_features_strlen($icon) > 120) {
        $errors[] = "El codigo de icono de la caracteristica {$num} supera 120 caracteres.";
    }
    if (cw_features_strlen($itemTitle) > 70) {
        $errors[] = "El titulo de la caracteristica {$num} supera 70 caracteres.";
    }
    if (cw_features_strlen($itemText) > 220) {
        $errors[] = "El texto de la caracteristica {$num} supera 220 caracteres.";
    }

    $rawItems[] = [
        'icono' => cw_features_limit_text($icon, 120),
        'titulo' => cw_features_limit_text($itemTitle, 70),
        'texto' => cw_features_limit_text($itemText, 220),
    ];
}

$items = cw_features_normalize_items($rawItems);

if (!empty($errors)) {
    cw_features_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion de caracteristicas.',
        'errors' => $errors,
    ]);
}

$imagenPath = '';

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_features_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $prev = cw_features_fetch($cn);
    $imagenPath = trim((string)($prev['imagen_path'] ?? ''));

    if ($eliminarImagen && $imagenPath !== '') {
        ga_mark_and_delete($cn, $imagenPath, 'borrado');
        $imagenPath = '';
    }

    if (!empty($_FILES['imagen_archivo']) && ($_FILES['imagen_archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $imageFile = $_FILES['imagen_archivo'];
        if (($imageFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            cw_features_json_exit(['ok' => false, 'message' => 'Error al subir la imagen central.']);
        }

        if (($imageFile['size'] ?? 0) > 3 * 1024 * 1024) {
            cw_features_json_exit(['ok' => false, 'message' => 'La imagen central excede 3MB.']);
        }

        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$fi->file($imageFile['tmp_name']);
        $allowed = ['image/png', 'image/webp', 'image/jpeg'];
        if (!in_array($mime, $allowed, true)) {
            cw_features_json_exit(['ok' => false, 'message' => 'Formato no permitido. Usa PNG, WEBP o JPEG.']);
        }

        $ga = ga_save_upload($cn, $imageFile, 'img_caracteristica', 'caracteristica-web', 'web', 'caracteristicas', 1);
        $newImagePath = (string)($ga['ruta_relativa'] ?? '');
        if ($newImagePath === '') {
            cw_features_json_exit(['ok' => false, 'message' => 'No se pudo obtener la ruta de la imagen central.']);
        }

        if ($imagenPath !== '' && $imagenPath !== $newImagePath) {
            ga_mark_and_delete($cn, $imagenPath, 'reemplazado');
        }
        $imagenPath = $newImagePath;
    }

    $payload = [
        'titulo_rojo' => cw_features_limit_text($tituloRojo, 40),
        'titulo_azul' => cw_features_limit_text($tituloAzul, 40),
        'descripcion_general' => cw_features_limit_text($descripcionGeneral, 320),
        'imagen_path' => $imagenPath,
        'items' => $items,
    ];

    $ok = cw_features_upsert($cn, $payload);
    if (!$ok) {
        cw_features_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar la configuracion de caracteristicas en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_features_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar caracteristicas.',
    ]);
}

cw_features_json_exit([
    'ok' => true,
    'message' => 'Caracteristicas actualizadas correctamente.',
    'imagen_path' => $imagenPath,
    'imagen_url' => cw_features_resolve_image_url($imagenPath),
]);
