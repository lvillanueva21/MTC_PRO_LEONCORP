<?php
// modules/control_web/formulario_carrusel/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_fc_admin_json_exit')) {
    function cw_fc_admin_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_fc_admin_strlen')) {
    function cw_fc_admin_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if (!function_exists('cw_fc_admin_get_upload')) {
    function cw_fc_admin_get_upload(string $field, $key): ?array
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return null;
        }

        $bucket = $_FILES[$field];
        if (!isset($bucket['error']) || !is_array($bucket['error']) || !array_key_exists($key, $bucket['error'])) {
            return null;
        }

        return [
            'name' => (string)($bucket['name'][$key] ?? ''),
            'type' => (string)($bucket['type'][$key] ?? ''),
            'tmp_name' => (string)($bucket['tmp_name'][$key] ?? ''),
            'error' => (int)($bucket['error'][$key] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($bucket['size'][$key] ?? 0),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_fc_admin_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$itemIds = isset($_POST['item_id']) && is_array($_POST['item_id']) ? $_POST['item_id'] : [];
$itemTitles = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? $_POST['item_titulo'] : [];
$itemTexts = isset($_POST['item_texto']) && is_array($_POST['item_texto']) ? $_POST['item_texto'] : [];
$itemRemoveImage = isset($_POST['item_eliminar_imagen']) && is_array($_POST['item_eliminar_imagen']) ? $_POST['item_eliminar_imagen'] : [];

$keys = array_values(array_unique(array_merge(
    array_keys($itemTitles),
    array_keys($itemTexts),
    array_keys($itemIds)
)));

$errors = [];

$itemCount = count($keys);
if ($itemCount < 1) {
    $errors[] = 'Debes registrar al menos 1 elemento en el carrusel.';
}
if ($itemCount > 5) {
    $errors[] = 'Solo se permiten hasta 5 elementos en el carrusel.';
}

$normalizedItems = [];
foreach ($keys as $pos => $key) {
    $itemNumber = $pos + 1;
    $base = cw_fc_default_slide_for_position($pos);

    $id = (int)($itemIds[$key] ?? 0);
    $title = trim((string)($itemTitles[$key] ?? ''));
    $text = trim((string)($itemTexts[$key] ?? ''));

    if (cw_fc_admin_strlen($title) > 140) {
        $errors[] = "El titulo del elemento {$itemNumber} supera 140 caracteres.";
    }
    if (cw_fc_admin_strlen($text) > 260) {
        $errors[] = "El texto del elemento {$itemNumber} supera 260 caracteres.";
    }

    if ($title === '') {
        $title = (string)($base['titulo'] ?? '');
    }
    if ($text === '') {
        $text = (string)($base['texto'] ?? '');
    }

    $normalizedItems[] = [
        'key' => $key,
        'id' => $id,
        'orden' => $itemNumber,
        'titulo' => cw_fc_limit_text($title, 140),
        'texto' => cw_fc_limit_text($text, 260),
        'remove_image' => ((string)($itemRemoveImage[$key] ?? '0') === '1'),
    ];
}

if (!empty($errors)) {
    cw_fc_admin_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion del carrusel.',
        'errors' => $errors,
    ]);
}

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_fc_admin_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $existingRows = cw_fc_fetch_carousel_rows_by_id($cn);
    mysqli_begin_transaction($cn);

    $savedIds = [];

    foreach ($normalizedItems as $pos => $item) {
        $itemId = (int)($item['id'] ?? 0);
        $currentPath = '';
        if ($itemId > 0 && isset($existingRows[$itemId])) {
            $currentPath = trim((string)($existingRows[$itemId]['imagen_path'] ?? ''));
        } else {
            $itemId = 0;
        }

        if (!empty($item['remove_image']) && $currentPath !== '') {
            ga_mark_and_delete($cn, $currentPath, 'borrado');
            $currentPath = '';
        }

        $upload = cw_fc_admin_get_upload('item_imagen_archivo', $item['key']);
        if (is_array($upload) && (int)$upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error al subir la imagen del elemento ' . ($pos + 1) . '.');
            }

            if ((int)$upload['size'] > 3 * 1024 * 1024) {
                throw new RuntimeException('La imagen del elemento ' . ($pos + 1) . ' excede 3MB.');
            }

            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$fi->file((string)$upload['tmp_name']);
            $allowed = ['image/png', 'image/webp', 'image/jpeg'];
            if (!in_array($mime, $allowed, true)) {
                throw new RuntimeException('Formato no permitido en el elemento ' . ($pos + 1) . '. Usa PNG, WEBP o JPEG.');
            }

            $ga = ga_save_upload($cn, $upload, 'img_formulario_carrusel', 'slide-formulario-carrusel', 'web', 'formulario_carrusel', 1);
            $newPath = trim((string)($ga['ruta_relativa'] ?? ''));
            if ($newPath === '') {
                throw new RuntimeException('No se pudo obtener la ruta de imagen para el elemento ' . ($pos + 1) . '.');
            }

            if ($currentPath !== '' && $currentPath !== $newPath) {
                ga_mark_and_delete($cn, $currentPath, 'reemplazado');
            }

            $currentPath = $newPath;
        }

        $savedId = cw_fc_upsert_carousel_item($cn, [
            'id' => $itemId,
            'orden' => (int)$item['orden'],
            'titulo' => (string)$item['titulo'],
            'texto' => (string)$item['texto'],
            'imagen_path' => $currentPath,
        ]);

        if ($savedId < 1) {
            throw new RuntimeException('No se pudo guardar el elemento ' . ($pos + 1) . '.');
        }

        $savedIds[] = $savedId;
    }

    $deletedRows = cw_fc_delete_carousel_items_not_in($cn, $savedIds);
    foreach ($deletedRows as $deletedRow) {
        $deletedPath = trim((string)($deletedRow['imagen_path'] ?? ''));
        if ($deletedPath !== '') {
            ga_mark_and_delete($cn, $deletedPath, 'borrado');
        }
    }

    mysqli_commit($cn);
} catch (Throwable $e) {
    try {
        if (isset($cn) && $cn instanceof mysqli) {
            mysqli_rollback($cn);
        }
    } catch (Throwable $ignore) {
    }

    cw_fc_admin_json_exit([
        'ok' => false,
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el carrusel.',
    ]);
}

$responseItems = [];
try {
    if (isset($cn) && $cn instanceof mysqli) {
        $freshItems = cw_fc_fetch_carousel_items($cn);
        foreach ($freshItems as $idx => $item) {
            $default = cw_fc_default_slide_for_position($idx);
            $defaultUrl = cw_fc_default_asset_url((string)($default['default_image'] ?? 'web/img/carousel-1.jpg'));
            $responseItems[] = [
                'id' => (int)($item['id'] ?? 0),
                'orden' => (int)($item['orden'] ?? ($idx + 1)),
                'titulo' => (string)($item['titulo'] ?? ''),
                'texto' => (string)($item['texto'] ?? ''),
                'imagen_url' => cw_fc_resolve_slide_image_url($item, $idx),
                'default_image_url' => $defaultUrl,
            ];
        }
    }
} catch (Throwable $ignore) {
    $responseItems = [];
}

cw_fc_admin_json_exit([
    'ok' => true,
    'message' => 'Carrusel actualizado correctamente.',
    'items' => $responseItems,
]);
