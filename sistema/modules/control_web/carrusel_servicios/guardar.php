<?php
// modules/control_web/carrusel_servicios/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_cs_admin_json_exit')) {
    function cw_cs_admin_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_cs_admin_strlen')) {
    function cw_cs_admin_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if (!function_exists('cw_cs_admin_get_upload')) {
    function cw_cs_admin_get_upload(string $field, $key): ?array
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
    cw_cs_admin_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloBase = trim((string)($_POST['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($_POST['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($_POST['descripcion_general'] ?? ''));

$itemIds = isset($_POST['item_id']) && is_array($_POST['item_id']) ? $_POST['item_id'] : [];
$itemTitles = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? $_POST['item_titulo'] : [];
$itemReviewTexts = isset($_POST['item_review_text']) && is_array($_POST['item_review_text']) ? $_POST['item_review_text'] : [];
$itemRatings = isset($_POST['item_rating']) && is_array($_POST['item_rating']) ? $_POST['item_rating'] : [];
$itemShowStars = isset($_POST['item_mostrar_estrellas']) && is_array($_POST['item_mostrar_estrellas']) ? $_POST['item_mostrar_estrellas'] : [];
$itemBadges = isset($_POST['item_badge_text']) && is_array($_POST['item_badge_text']) ? $_POST['item_badge_text'] : [];
$itemButtonTexts = isset($_POST['item_boton_texto']) && is_array($_POST['item_boton_texto']) ? $_POST['item_boton_texto'] : [];
$itemButtonUrls = isset($_POST['item_boton_url']) && is_array($_POST['item_boton_url']) ? $_POST['item_boton_url'] : [];
$itemRemoveImage = isset($_POST['item_eliminar_imagen']) && is_array($_POST['item_eliminar_imagen']) ? $_POST['item_eliminar_imagen'] : [];
$itemDetailIcons = isset($_POST['item_detalle_icono']) && is_array($_POST['item_detalle_icono']) ? $_POST['item_detalle_icono'] : [];
$itemDetailTexts = isset($_POST['item_detalle_texto']) && is_array($_POST['item_detalle_texto']) ? $_POST['item_detalle_texto'] : [];
$itemDetailVisible = isset($_POST['item_detalle_visible']) && is_array($_POST['item_detalle_visible']) ? $_POST['item_detalle_visible'] : [];

$keys = array_values(array_unique(array_merge(
    array_keys($itemTitles),
    array_keys($itemIds),
    array_keys($itemReviewTexts),
    array_keys($itemRatings),
    array_keys($itemBadges),
    array_keys($itemButtonTexts),
    array_keys($itemButtonUrls),
    array_keys($itemDetailIcons),
    array_keys($itemDetailTexts),
    array_keys($itemDetailVisible)
)));

$errors = [];

if (cw_cs_admin_strlen($tituloBase) > 40) {
    $errors[] = 'El titulo base no puede superar 40 caracteres.';
}
if (cw_cs_admin_strlen($tituloResaltado) > 40) {
    $errors[] = 'El titulo resaltado no puede superar 40 caracteres.';
}
if (cw_cs_admin_strlen($descripcionGeneral) > 320) {
    $errors[] = 'La descripcion general no puede superar 320 caracteres.';
}

$itemCount = count($keys);
if ($itemCount < 1) {
    $errors[] = 'Debes registrar al menos 1 servicio en el carrusel.';
}
if ($itemCount > 9) {
    $errors[] = 'Solo se permiten hasta 9 servicios en el carrusel.';
}

$normalizedItems = [];
foreach ($keys as $pos => $key) {
    $itemNumber = $pos + 1;
    $base = cw_cs_item_default_for_position($pos);

    $id = (int)($itemIds[$key] ?? 0);
    $title = trim((string)($itemTitles[$key] ?? ''));
    $reviewText = trim((string)($itemReviewTexts[$key] ?? ''));
    $rating = (int)($itemRatings[$key] ?? ($base['rating'] ?? 4));
    $showStars = cw_cs_to_flag($itemShowStars[$key] ?? null, (int)($base['mostrar_estrellas'] ?? 1));
    $badgeText = trim((string)($itemBadges[$key] ?? ''));
    $buttonText = trim((string)($itemButtonTexts[$key] ?? ''));
    $buttonUrl = trim((string)($itemButtonUrls[$key] ?? ''));

    if (cw_cs_admin_strlen($title) > 80) {
        $errors[] = "El titulo del servicio {$itemNumber} supera 80 caracteres.";
    }
    if (cw_cs_admin_strlen($reviewText) > 60) {
        $errors[] = "El texto review del servicio {$itemNumber} supera 60 caracteres.";
    }
    if ($rating < 1 || $rating > 5) {
        $errors[] = "La cantidad de estrellas del servicio {$itemNumber} debe estar entre 1 y 5.";
    }
    if (cw_cs_admin_strlen($badgeText) > 80) {
        $errors[] = "El badge del servicio {$itemNumber} supera 80 caracteres.";
    }
    if (cw_cs_admin_strlen($buttonText) > 50) {
        $errors[] = "El texto del boton del servicio {$itemNumber} supera 50 caracteres.";
    }
    if (cw_cs_admin_strlen($buttonUrl) > 255) {
        $errors[] = "El enlace del boton del servicio {$itemNumber} supera 255 caracteres.";
    }
    if ($buttonUrl !== '' && !cw_cs_link_valid($buttonUrl)) {
        $errors[] = "El enlace del boton del servicio {$itemNumber} no es valido.";
    }

    $detailIcons = (isset($itemDetailIcons[$key]) && is_array($itemDetailIcons[$key]))
        ? array_values($itemDetailIcons[$key])
        : [];
    $detailTexts = (isset($itemDetailTexts[$key]) && is_array($itemDetailTexts[$key]))
        ? array_values($itemDetailTexts[$key])
        : [];
    $detailVisible = (isset($itemDetailVisible[$key]) && is_array($itemDetailVisible[$key]))
        ? array_values($itemDetailVisible[$key])
        : [];

    $rawDetails = [];
    for ($d = 0; $d < 6; $d++) {
        $iconInput = trim((string)($detailIcons[$d] ?? ''));
        $textInput = trim((string)($detailTexts[$d] ?? ''));
        $visibleInput = cw_cs_to_flag($detailVisible[$d] ?? null, 1);

        if ($iconInput !== '' && cw_cs_sanitize_icon_class($iconInput) === '') {
            $errors[] = "El icono del detalle " . ($d + 1) . " del servicio {$itemNumber} es invalido.";
        }
        if (cw_cs_admin_strlen($iconInput) > 120) {
            $errors[] = "El icono del detalle " . ($d + 1) . " del servicio {$itemNumber} supera 120 caracteres.";
        }
        if (cw_cs_admin_strlen($textInput) > 40) {
            $errors[] = "El texto del detalle " . ($d + 1) . " del servicio {$itemNumber} supera 40 caracteres.";
        }

        $rawDetails[] = [
            'visible' => $visibleInput,
            'icono' => cw_cs_limit_text($iconInput, 120),
            'texto' => cw_cs_limit_text($textInput, 40),
        ];
    }

    if ($title === '') {
        $title = (string)($base['titulo'] ?? '');
    }
    if ($reviewText === '') {
        $reviewText = (string)($base['review_text'] ?? '4.0 Review');
    }
    if ($rating < 1 || $rating > 5) {
        $rating = (int)($base['rating'] ?? 4);
    }
    if ($rating < 1 || $rating > 5) {
        $rating = 4;
    }
    if ($badgeText === '') {
        $badgeText = (string)($base['badge_text'] ?? 'Consulta precio');
    }
    if ($buttonText === '') {
        $buttonText = (string)($base['boton_texto'] ?? 'Book Now');
    }
    if ($buttonUrl === '' || !cw_cs_link_valid($buttonUrl)) {
        $buttonUrl = (string)($base['boton_url'] ?? '#');
    }
    if ($buttonUrl === '' || !cw_cs_link_valid($buttonUrl)) {
        $buttonUrl = '#';
    }

    $normalizedItems[] = [
        'key' => $key,
        'id' => $id,
        'orden' => $itemNumber,
        'titulo' => cw_cs_limit_text($title, 80),
        'review_text' => cw_cs_limit_text($reviewText, 60),
        'rating' => $rating,
        'mostrar_estrellas' => $showStars,
        'badge_text' => cw_cs_limit_text($badgeText, 80),
        'detalles' => cw_cs_normalize_details($rawDetails, $pos),
        'boton_texto' => cw_cs_limit_text($buttonText, 50),
        'boton_url' => cw_cs_limit_text($buttonUrl, 255),
        'remove_image' => ((string)($itemRemoveImage[$key] ?? '0') === '1'),
    ];
}

if (!empty($errors)) {
    cw_cs_admin_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar el carrusel de servicios.',
        'errors' => $errors,
    ]);
}

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_cs_admin_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $configPayload = [
        'titulo_base' => cw_cs_limit_text($tituloBase, 40),
        'titulo_resaltado' => cw_cs_limit_text($tituloResaltado, 40),
        'descripcion_general' => cw_cs_limit_text($descripcionGeneral, 320),
    ];

    $existingRows = cw_cs_fetch_rows_by_id($cn);

    mysqli_begin_transaction($cn);

    if (!cw_cs_upsert_config($cn, $configPayload)) {
        throw new RuntimeException('No se pudo guardar la configuracion del encabezado.');
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

        $upload = cw_cs_admin_get_upload('item_imagen_archivo', $item['key']);
        if (is_array($upload) && (int)$upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error al subir la imagen del servicio ' . ($pos + 1) . '.');
            }

            if ((int)$upload['size'] > 3 * 1024 * 1024) {
                throw new RuntimeException('La imagen del servicio ' . ($pos + 1) . ' excede 3MB.');
            }

            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$fi->file((string)$upload['tmp_name']);
            $allowed = ['image/png', 'image/webp', 'image/jpeg'];
            if (!in_array($mime, $allowed, true)) {
                throw new RuntimeException('Formato no permitido en el servicio ' . ($pos + 1) . '. Usa PNG, WEBP o JPEG.');
            }

            $ga = ga_save_upload($cn, $upload, 'img_carrusel_servicios', 'carrusel-servicio', 'web', 'carrusel_servicios', 1);
            $newPath = trim((string)($ga['ruta_relativa'] ?? ''));
            if ($newPath === '') {
                throw new RuntimeException('No se pudo obtener la ruta de imagen del servicio ' . ($pos + 1) . '.');
            }

            if ($currentPath !== '' && $currentPath !== $newPath) {
                ga_mark_and_delete($cn, $currentPath, 'reemplazado');
            }
            $currentPath = $newPath;
        }

        $savedId = cw_cs_upsert_item($cn, [
            'id' => $itemId,
            'orden' => (int)$item['orden'],
            'titulo' => (string)$item['titulo'],
            'review_text' => (string)$item['review_text'],
            'rating' => (int)$item['rating'],
            'mostrar_estrellas' => (int)$item['mostrar_estrellas'],
            'badge_text' => (string)$item['badge_text'],
            'detalles' => $item['detalles'],
            'boton_texto' => (string)$item['boton_texto'],
            'boton_url' => (string)$item['boton_url'],
            'imagen_path' => $currentPath,
        ]);

        if ($savedId < 1) {
            throw new RuntimeException('No se pudo guardar el servicio ' . ($pos + 1) . '.');
        }

        $savedIds[] = $savedId;
    }

    $deletedRows = cw_cs_delete_items_not_in($cn, $savedIds);
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

    cw_cs_admin_json_exit([
        'ok' => false,
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el carrusel de servicios.',
    ]);
}

$responseItems = [];
try {
    if (isset($cn) && $cn instanceof mysqli) {
        $freshItems = cw_cs_fetch_items($cn);
        foreach ($freshItems as $idx => $item) {
            $base = cw_cs_item_default_for_position($idx);
            $responseItems[] = [
                'id' => (int)($item['id'] ?? 0),
                'orden' => (int)($item['orden'] ?? ($idx + 1)),
                'titulo' => (string)($item['titulo'] ?? ''),
                'review_text' => (string)($item['review_text'] ?? ''),
                'rating' => (int)($item['rating'] ?? 4),
                'mostrar_estrellas' => (int)($item['mostrar_estrellas'] ?? 1),
                'badge_text' => (string)($item['badge_text'] ?? ''),
                'boton_texto' => (string)($item['boton_texto'] ?? ''),
                'boton_url' => (string)($item['boton_url'] ?? '#'),
                'imagen_url' => cw_cs_resolve_item_image_url($item, $idx),
                'default_image_url' => cw_cs_default_asset_url((string)($base['default_image'] ?? cw_cs_default_image_for_position($idx))),
            ];
        }
    }
} catch (Throwable $ignore) {
    $responseItems = [];
}

cw_cs_admin_json_exit([
    'ok' => true,
    'message' => 'Carrusel de servicios actualizado correctamente.',
    'items' => $responseItems,
]);
