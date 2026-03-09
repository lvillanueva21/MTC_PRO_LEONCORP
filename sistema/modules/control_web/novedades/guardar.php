<?php
// modules/control_web/novedades/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_novedades_admin_json_exit')) {
    function cw_novedades_admin_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_novedades_admin_strlen')) {
    function cw_novedades_admin_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if (!function_exists('cw_novedades_admin_get_upload')) {
    function cw_novedades_admin_get_upload(string $field, $key): ?array
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
    cw_novedades_admin_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloBase = trim((string)($_POST['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($_POST['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($_POST['descripcion_general'] ?? ''));

$itemIds = isset($_POST['item_id']) && is_array($_POST['item_id']) ? $_POST['item_id'] : [];
$itemVisible = isset($_POST['item_visible']) && is_array($_POST['item_visible']) ? $_POST['item_visible'] : [];
$itemTitles = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? $_POST['item_titulo'] : [];
$itemMeta1Icons = isset($_POST['item_meta_1_icono']) && is_array($_POST['item_meta_1_icono']) ? $_POST['item_meta_1_icono'] : [];
$itemMeta1Texts = isset($_POST['item_meta_1_texto']) && is_array($_POST['item_meta_1_texto']) ? $_POST['item_meta_1_texto'] : [];
$itemMeta2Icons = isset($_POST['item_meta_2_icono']) && is_array($_POST['item_meta_2_icono']) ? $_POST['item_meta_2_icono'] : [];
$itemMeta2Texts = isset($_POST['item_meta_2_texto']) && is_array($_POST['item_meta_2_texto']) ? $_POST['item_meta_2_texto'] : [];
$itemBadges = isset($_POST['item_badge_texto']) && is_array($_POST['item_badge_texto']) ? $_POST['item_badge_texto'] : [];
$itemResumes = isset($_POST['item_resumen_texto']) && is_array($_POST['item_resumen_texto']) ? $_POST['item_resumen_texto'] : [];
$itemButtonTexts = isset($_POST['item_boton_texto']) && is_array($_POST['item_boton_texto']) ? $_POST['item_boton_texto'] : [];
$itemButtonUrls = isset($_POST['item_boton_url']) && is_array($_POST['item_boton_url']) ? $_POST['item_boton_url'] : [];
$itemRemoveImage = isset($_POST['item_eliminar_imagen']) && is_array($_POST['item_eliminar_imagen']) ? $_POST['item_eliminar_imagen'] : [];

$keys = array_values(array_unique(array_merge(
    array_keys($itemIds),
    array_keys($itemVisible),
    array_keys($itemTitles),
    array_keys($itemMeta1Icons),
    array_keys($itemMeta1Texts),
    array_keys($itemMeta2Icons),
    array_keys($itemMeta2Texts),
    array_keys($itemBadges),
    array_keys($itemResumes),
    array_keys($itemButtonTexts),
    array_keys($itemButtonUrls)
)));

$errors = [];

if (cw_novedades_admin_strlen($tituloBase) > 40) {
    $errors[] = 'El titulo 1 no puede superar 40 caracteres.';
}
if (cw_novedades_admin_strlen($tituloResaltado) > 40) {
    $errors[] = 'El titulo 2 no puede superar 40 caracteres.';
}
if (cw_novedades_admin_strlen($descripcionGeneral) > 280) {
    $errors[] = 'El texto central no puede superar 280 caracteres.';
}

$itemCount = count($keys);
if ($itemCount < 1) {
    $errors[] = 'Debes registrar al menos 1 novedad.';
}
if ($itemCount > 9) {
    $errors[] = 'Solo se permiten hasta 9 novedades.';
}

$normalizedItems = [];
$visibleCount = 0;
foreach ($keys as $pos => $key) {
    $itemNumber = $pos + 1;
    $base = cw_novedades_item_default_for_position($pos);

    $id = (int)($itemIds[$key] ?? 0);
    $visible = cw_novedades_to_flag($itemVisible[$key] ?? null, (int)($base['visible'] ?? 1));
    $title = trim((string)($itemTitles[$key] ?? ''));
    $meta1IconInput = trim((string)($itemMeta1Icons[$key] ?? ''));
    $meta1Text = trim((string)($itemMeta1Texts[$key] ?? ''));
    $meta2IconInput = trim((string)($itemMeta2Icons[$key] ?? ''));
    $meta2Text = trim((string)($itemMeta2Texts[$key] ?? ''));
    $badgeText = trim((string)($itemBadges[$key] ?? ''));
    $resumeText = trim((string)($itemResumes[$key] ?? ''));
    $buttonText = trim((string)($itemButtonTexts[$key] ?? ''));
    $buttonUrl = trim((string)($itemButtonUrls[$key] ?? ''));

    if (cw_novedades_admin_strlen($title) > 110) {
        $errors[] = "El titulo de la novedad {$itemNumber} supera 110 caracteres.";
    }
    if (cw_novedades_admin_strlen($meta1IconInput) > 120) {
        $errors[] = "El icono 1 de la novedad {$itemNumber} supera 120 caracteres.";
    }
    if (cw_novedades_admin_strlen($meta1Text) > 80) {
        $errors[] = "El texto 1 de la novedad {$itemNumber} supera 80 caracteres.";
    }
    if (cw_novedades_admin_strlen($meta2IconInput) > 120) {
        $errors[] = "El icono 2 de la novedad {$itemNumber} supera 120 caracteres.";
    }
    if (cw_novedades_admin_strlen($meta2Text) > 80) {
        $errors[] = "El texto 2 de la novedad {$itemNumber} supera 80 caracteres.";
    }
    if (cw_novedades_admin_strlen($badgeText) > 50) {
        $errors[] = "El badge de la novedad {$itemNumber} supera 50 caracteres.";
    }
    if (cw_novedades_admin_strlen($resumeText) > 220) {
        $errors[] = "El resumen de la novedad {$itemNumber} supera 220 caracteres.";
    }
    if (cw_novedades_admin_strlen($buttonText) > 50) {
        $errors[] = "El texto del boton de la novedad {$itemNumber} supera 50 caracteres.";
    }
    if (cw_novedades_admin_strlen($buttonUrl) > 255) {
        $errors[] = "El enlace del boton de la novedad {$itemNumber} supera 255 caracteres.";
    }
    if ($buttonUrl !== '' && !cw_novedades_link_valid($buttonUrl)) {
        $errors[] = "El enlace del boton de la novedad {$itemNumber} no es valido.";
    }

    if ($meta1IconInput !== '' && cw_novedades_sanitize_icon_class($meta1IconInput) === '') {
        $errors[] = "El icono 1 de la novedad {$itemNumber} es invalido.";
    }
    if ($meta2IconInput !== '' && cw_novedades_sanitize_icon_class($meta2IconInput) === '') {
        $errors[] = "El icono 2 de la novedad {$itemNumber} es invalido.";
    }

    if ($title === '') {
        $title = (string)($base['titulo'] ?? 'Novedad');
    }

    $meta1Icon = cw_novedades_sanitize_icon_class($meta1IconInput);
    if ($meta1Icon === '') {
        $meta1Icon = cw_novedades_sanitize_icon_class((string)($base['meta_1_icono'] ?? 'fa fa-user text-primary'));
    }

    if ($meta1Text === '') {
        $meta1Text = (string)($base['meta_1_texto'] ?? 'Autor');
    }

    $meta2Icon = cw_novedades_sanitize_icon_class($meta2IconInput);
    if ($meta2Icon === '') {
        $meta2Icon = cw_novedades_sanitize_icon_class((string)($base['meta_2_icono'] ?? 'fa fa-comment-alt text-primary'));
    }

    if ($meta2Text === '') {
        $meta2Text = (string)($base['meta_2_texto'] ?? 'Sin comentarios');
    }

    if ($badgeText === '') {
        $badgeText = (string)($base['badge_texto'] ?? 'Novedad');
    }

    if ($resumeText === '') {
        $resumeText = (string)($base['resumen_texto'] ?? '');
    }

    if ($buttonText === '') {
        $buttonText = (string)($base['boton_texto'] ?? 'Read More');
    }

    if ($buttonUrl === '' || !cw_novedades_link_valid($buttonUrl)) {
        $buttonUrl = (string)($base['boton_url'] ?? '#');
    }
    if ($buttonUrl === '' || !cw_novedades_link_valid($buttonUrl)) {
        $buttonUrl = '#';
    }

    if ($visible === 1) {
        $visibleCount++;
    }

    $normalizedItems[] = [
        'key' => $key,
        'id' => $id,
        'orden' => $itemNumber,
        'visible' => $visible,
        'titulo' => cw_novedades_limit_text($title, 110),
        'meta_1_icono' => cw_novedades_limit_text($meta1Icon, 120),
        'meta_1_texto' => cw_novedades_limit_text($meta1Text, 80),
        'meta_2_icono' => cw_novedades_limit_text($meta2Icon, 120),
        'meta_2_texto' => cw_novedades_limit_text($meta2Text, 80),
        'badge_texto' => cw_novedades_limit_text($badgeText, 50),
        'resumen_texto' => cw_novedades_limit_text($resumeText, 220),
        'boton_texto' => cw_novedades_limit_text($buttonText, 50),
        'boton_url' => cw_novedades_limit_text($buttonUrl, 255),
        'remove_image' => ((string)($itemRemoveImage[$key] ?? '0') === '1'),
    ];
}

if ($visibleCount < 1) {
    $errors[] = 'Debes mantener al menos 1 novedad visible en la web.';
}

if (!empty($errors)) {
    cw_novedades_admin_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion de novedades.',
        'errors' => $errors,
    ]);
}

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_novedades_admin_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $existingRows = cw_novedades_fetch_rows_by_id($cn);
    mysqli_begin_transaction($cn);

    $okConfig = cw_novedades_upsert_config($cn, [
        'titulo_base' => cw_novedades_limit_text($tituloBase, 40),
        'titulo_resaltado' => cw_novedades_limit_text($tituloResaltado, 40),
        'descripcion_general' => cw_novedades_limit_text($descripcionGeneral, 280),
    ]);
    if (!$okConfig) {
        throw new RuntimeException('No se pudo guardar el encabezado del modulo novedades.');
    }

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

        $upload = cw_novedades_admin_get_upload('item_imagen_archivo', $item['key']);
        if (is_array($upload) && (int)$upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error al subir la imagen de la novedad ' . ($pos + 1) . '.');
            }

            if ((int)$upload['size'] > 3 * 1024 * 1024) {
                throw new RuntimeException('La imagen de la novedad ' . ($pos + 1) . ' excede 3MB.');
            }

            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$fi->file((string)$upload['tmp_name']);
            $allowed = ['image/png', 'image/webp', 'image/jpeg'];
            if (!in_array($mime, $allowed, true)) {
                throw new RuntimeException('Formato no permitido en la novedad ' . ($pos + 1) . '. Usa PNG, WEBP o JPEG.');
            }

            $ga = ga_save_upload($cn, $upload, 'img_novedades', 'novedad-blog', 'web', 'novedades', 1);
            $newPath = trim((string)($ga['ruta_relativa'] ?? ''));
            if ($newPath === '') {
                throw new RuntimeException('No se pudo obtener la ruta de imagen de la novedad ' . ($pos + 1) . '.');
            }

            if ($currentPath !== '' && $currentPath !== $newPath) {
                ga_mark_and_delete($cn, $currentPath, 'reemplazado');
            }
            $currentPath = $newPath;
        }

        $savedId = cw_novedades_upsert_item($cn, [
            'id' => $itemId,
            'orden' => (int)$item['orden'],
            'visible' => (int)$item['visible'],
            'titulo' => (string)$item['titulo'],
            'meta_1_icono' => (string)$item['meta_1_icono'],
            'meta_1_texto' => (string)$item['meta_1_texto'],
            'meta_2_icono' => (string)$item['meta_2_icono'],
            'meta_2_texto' => (string)$item['meta_2_texto'],
            'badge_texto' => (string)$item['badge_texto'],
            'resumen_texto' => (string)$item['resumen_texto'],
            'boton_texto' => (string)$item['boton_texto'],
            'boton_url' => (string)$item['boton_url'],
            'imagen_path' => $currentPath,
        ]);

        if ($savedId < 1) {
            throw new RuntimeException('No se pudo guardar la novedad ' . ($pos + 1) . '.');
        }

        $savedIds[] = $savedId;
    }

    $deletedRows = cw_novedades_delete_items_not_in($cn, $savedIds);
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

    cw_novedades_admin_json_exit([
        'ok' => false,
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la configuracion de novedades.',
    ]);
}

cw_novedades_admin_json_exit([
    'ok' => true,
    'message' => 'Modulo novedades actualizado correctamente.',
]);

