<?php
// modules/control_web/carrusel_empresas/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_ce_admin_json_exit')) {
    function cw_ce_admin_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_ce_admin_strlen')) {
    function cw_ce_admin_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if (!function_exists('cw_ce_admin_get_upload')) {
    function cw_ce_admin_get_upload(string $field, $key): ?array
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
    cw_ce_admin_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloBase = trim((string)($_POST['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($_POST['titulo_resaltado'] ?? ''));

$itemIds = isset($_POST['item_id']) && is_array($_POST['item_id']) ? $_POST['item_id'] : [];
$itemTitles = isset($_POST['item_titulo']) && is_array($_POST['item_titulo']) ? $_POST['item_titulo'] : [];
$itemProfessions = isset($_POST['item_profesion']) && is_array($_POST['item_profesion']) ? $_POST['item_profesion'] : [];
$itemRemoveImage = isset($_POST['item_eliminar_imagen']) && is_array($_POST['item_eliminar_imagen']) ? $_POST['item_eliminar_imagen'] : [];
$itemSocialVisible = isset($_POST['item_red_visible']) && is_array($_POST['item_red_visible']) ? $_POST['item_red_visible'] : [];
$itemSocialLinks = isset($_POST['item_red_link']) && is_array($_POST['item_red_link']) ? $_POST['item_red_link'] : [];

$keys = array_values(array_unique(array_merge(
    array_keys($itemTitles),
    array_keys($itemProfessions),
    array_keys($itemIds),
    array_keys($itemSocialVisible),
    array_keys($itemSocialLinks)
)));

$errors = [];

if (cw_ce_admin_strlen($tituloBase) > 40) {
    $errors[] = 'El titulo 1 no puede superar 40 caracteres.';
}
if (cw_ce_admin_strlen($tituloResaltado) > 40) {
    $errors[] = 'El titulo 2 no puede superar 40 caracteres.';
}

$itemCount = count($keys);
if ($itemCount < 1) {
    $errors[] = 'Debes registrar al menos 1 empresa en el carrusel.';
}
if ($itemCount > 15) {
    $errors[] = 'Solo se permiten hasta 15 empresas en el carrusel.';
}

$normalizedItems = [];
foreach ($keys as $pos => $key) {
    $itemNumber = $pos + 1;
    $base = cw_ce_item_default_for_position($pos);
    $baseSocials = cw_ce_normalize_socials($base['redes'] ?? [], $pos);

    $id = (int)($itemIds[$key] ?? 0);
    $title = trim((string)($itemTitles[$key] ?? ''));
    $profession = trim((string)($itemProfessions[$key] ?? ''));

    if (cw_ce_admin_strlen($title) > 80) {
        $errors[] = "El titulo de la empresa {$itemNumber} supera 80 caracteres.";
    }
    if (cw_ce_admin_strlen($profession) > 80) {
        $errors[] = "La profesion de la empresa {$itemNumber} supera 80 caracteres.";
    }

    if ($title === '') {
        $title = (string)($base['titulo'] ?? 'MARTIN DOE');
    }
    if ($profession === '') {
        $profession = (string)($base['profesion'] ?? 'Profession');
    }

    $visibleInput = (isset($itemSocialVisible[$key]) && is_array($itemSocialVisible[$key]))
        ? $itemSocialVisible[$key]
        : [];
    $linkInput = (isset($itemSocialLinks[$key]) && is_array($itemSocialLinks[$key]))
        ? $itemSocialLinks[$key]
        : [];

    $socials = [];
    $visibleCount = 0;
    foreach (cw_ce_social_keys() as $network) {
        $baseRow = (isset($baseSocials[$network]) && is_array($baseSocials[$network]))
            ? $baseSocials[$network]
            : ['visible' => 1, 'link' => '#'];

        $visible = cw_ce_to_flag($visibleInput[$network] ?? null, (int)($baseRow['visible'] ?? 1));
        $link = trim((string)($linkInput[$network] ?? ''));

        if (cw_ce_admin_strlen($link) > 255) {
            $errors[] = 'El enlace de ' . ucfirst($network) . " en la empresa {$itemNumber} supera 255 caracteres.";
        }
        if ($link !== '' && !cw_ce_link_valid($link)) {
            $errors[] = 'El enlace de ' . ucfirst($network) . " en la empresa {$itemNumber} no es valido.";
        }

        if ($link === '' || !cw_ce_link_valid($link)) {
            $link = (string)($baseRow['link'] ?? '#');
        }
        if ($link === '' || !cw_ce_link_valid($link)) {
            $link = '#';
        }

        if ($visible === 1) {
            $visibleCount++;
        }

        $socials[$network] = [
            'visible' => $visible,
            'link' => cw_ce_limit_text($link, 255),
        ];
    }

    if ($visibleCount < 1) {
        $errors[] = "La empresa {$itemNumber} debe mostrar al menos 1 red social.";
    }
    if ($visibleCount > 4) {
        $errors[] = "La empresa {$itemNumber} no puede mostrar mas de 4 redes sociales.";
    }

    $normalizedItems[] = [
        'key' => $key,
        'id' => $id,
        'orden' => $itemNumber,
        'titulo' => cw_ce_limit_text($title, 80),
        'profesion' => cw_ce_limit_text($profession, 80),
        'redes' => $socials,
        'remove_image' => ((string)($itemRemoveImage[$key] ?? '0') === '1'),
    ];
}

if (!empty($errors)) {
    cw_ce_admin_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar el carrusel de empresas.',
        'errors' => $errors,
    ]);
}

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_ce_admin_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $existingRows = cw_ce_fetch_rows_by_id($cn);
    mysqli_begin_transaction($cn);

    if (!cw_ce_upsert_config($cn, [
        'titulo_base' => cw_ce_limit_text($tituloBase, 40),
        'titulo_resaltado' => cw_ce_limit_text($tituloResaltado, 40),
    ])) {
        throw new RuntimeException('No se pudo guardar el encabezado del carrusel de empresas.');
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

        $upload = cw_ce_admin_get_upload('item_imagen_archivo', $item['key']);
        if (is_array($upload) && (int)$upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error al subir la imagen de la empresa ' . ($pos + 1) . '.');
            }

            if ((int)$upload['size'] > 3 * 1024 * 1024) {
                throw new RuntimeException('La imagen de la empresa ' . ($pos + 1) . ' excede 3MB.');
            }

            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$fi->file((string)$upload['tmp_name']);
            $allowed = ['image/png', 'image/webp', 'image/jpeg'];
            if (!in_array($mime, $allowed, true)) {
                throw new RuntimeException('Formato no permitido en la empresa ' . ($pos + 1) . '. Usa PNG, WEBP o JPEG.');
            }

            $ga = ga_save_upload($cn, $upload, 'img_carrusel_empresas', 'carrusel-empresa', 'web', 'carrusel_empresas', 1);
            $newPath = trim((string)($ga['ruta_relativa'] ?? ''));
            if ($newPath === '') {
                throw new RuntimeException('No se pudo obtener la ruta de imagen de la empresa ' . ($pos + 1) . '.');
            }

            if ($currentPath !== '' && $currentPath !== $newPath) {
                ga_mark_and_delete($cn, $currentPath, 'reemplazado');
            }
            $currentPath = $newPath;
        }

        $savedId = cw_ce_upsert_item($cn, [
            'id' => $itemId,
            'orden' => (int)$item['orden'],
            'titulo' => (string)$item['titulo'],
            'profesion' => (string)$item['profesion'],
            'redes' => $item['redes'],
            'imagen_path' => $currentPath,
        ]);

        if ($savedId < 1) {
            throw new RuntimeException('No se pudo guardar la empresa ' . ($pos + 1) . '.');
        }

        $savedIds[] = $savedId;
    }

    $deletedRows = cw_ce_delete_items_not_in($cn, $savedIds);
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

    cw_ce_admin_json_exit([
        'ok' => false,
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar el carrusel de empresas.',
    ]);
}

cw_ce_admin_json_exit([
    'ok' => true,
    'message' => 'Carrusel de empresas actualizado correctamente.',
]);
