<?php
// modules/control_web/testimonios/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_testimonios_admin_json_exit')) {
    function cw_testimonios_admin_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_testimonios_admin_strlen')) {
    function cw_testimonios_admin_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}

if (!function_exists('cw_testimonios_admin_get_upload')) {
    function cw_testimonios_admin_get_upload(string $field, $key): ?array
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
    cw_testimonios_admin_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloBase = trim((string)($_POST['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($_POST['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($_POST['descripcion_general'] ?? ''));

$itemNombres = isset($_POST['item_nombre_cliente']) && is_array($_POST['item_nombre_cliente'])
    ? $_POST['item_nombre_cliente']
    : [];
$itemProfesiones = isset($_POST['item_profesion']) && is_array($_POST['item_profesion'])
    ? $_POST['item_profesion']
    : [];
$itemTestimonios = isset($_POST['item_testimonio']) && is_array($_POST['item_testimonio'])
    ? $_POST['item_testimonio']
    : [];
$itemRemoveImage = isset($_POST['item_eliminar_imagen']) && is_array($_POST['item_eliminar_imagen'])
    ? $_POST['item_eliminar_imagen']
    : [];

$errors = [];

if (cw_testimonios_admin_strlen($tituloBase) > 40) {
    $errors[] = 'El titulo 1 no puede superar 40 caracteres.';
}
if (cw_testimonios_admin_strlen($tituloResaltado) > 40) {
    $errors[] = 'El titulo 2 no puede superar 40 caracteres.';
}
if (cw_testimonios_admin_strlen($descripcionGeneral) > 260) {
    $errors[] = 'La descripcion central no puede superar 260 caracteres.';
}

$normalizedItems = [];
for ($i = 0; $i < 2; $i++) {
    $itemNumber = $i + 1;
    $base = cw_testimonios_item_default_for_position($i);

    $nombre = trim((string)($itemNombres[$i] ?? ''));
    $profesion = trim((string)($itemProfesiones[$i] ?? ''));
    $testimonio = trim((string)($itemTestimonios[$i] ?? ''));

    if (cw_testimonios_admin_strlen($nombre) > 80) {
        $errors[] = "El nombre del cliente {$itemNumber} supera 80 caracteres.";
    }
    if (cw_testimonios_admin_strlen($profesion) > 80) {
        $errors[] = "La profesion del cliente {$itemNumber} supera 80 caracteres.";
    }
    if (cw_testimonios_admin_strlen($testimonio) > 280) {
        $errors[] = "El testimonio {$itemNumber} supera 280 caracteres.";
    }

    if ($nombre === '') {
        $nombre = (string)($base['nombre_cliente'] ?? 'Person Name');
    }
    if ($profesion === '') {
        $profesion = (string)($base['profesion'] ?? 'Profession');
    }
    if ($testimonio === '') {
        $testimonio = (string)($base['testimonio'] ?? '');
    }

    $normalizedItems[] = [
        'orden' => $itemNumber,
        'nombre_cliente' => cw_testimonios_limit_text($nombre, 80),
        'profesion' => cw_testimonios_limit_text($profesion, 80),
        'testimonio' => cw_testimonios_limit_text($testimonio, 280),
        'remove_image' => ((string)($itemRemoveImage[$i] ?? '0') === '1'),
        'input_key' => $i,
    ];
}

if (!empty($errors)) {
    cw_testimonios_admin_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion de testimonios.',
        'errors' => $errors,
    ]);
}

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_testimonios_admin_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $existingRows = cw_testimonios_fetch_rows_by_order($cn);
    mysqli_begin_transaction($cn);

    $okConfig = cw_testimonios_upsert_config($cn, [
        'titulo_base' => cw_testimonios_limit_text($tituloBase, 40),
        'titulo_resaltado' => cw_testimonios_limit_text($tituloResaltado, 40),
        'descripcion_general' => cw_testimonios_limit_text($descripcionGeneral, 260),
    ]);

    if (!$okConfig) {
        throw new RuntimeException('No se pudo guardar el encabezado del modulo testimonios.');
    }

    foreach ($normalizedItems as $item) {
        $order = (int)($item['orden'] ?? 0);
        $key = $item['input_key'] ?? 0;

        $currentPath = '';
        if ($order > 0 && isset($existingRows[$order])) {
            $currentPath = trim((string)($existingRows[$order]['imagen_path'] ?? ''));
        }

        if (!empty($item['remove_image']) && $currentPath !== '') {
            ga_mark_and_delete($cn, $currentPath, 'borrado');
            $currentPath = '';
        }

        $upload = cw_testimonios_admin_get_upload('item_imagen_archivo', $key);
        if (is_array($upload) && (int)$upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error al subir la imagen del testimonio ' . $order . '.');
            }

            if ((int)$upload['size'] > 3 * 1024 * 1024) {
                throw new RuntimeException('La imagen del testimonio ' . $order . ' excede 3MB.');
            }

            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$fi->file((string)$upload['tmp_name']);
            $allowed = ['image/png', 'image/webp', 'image/jpeg'];
            if (!in_array($mime, $allowed, true)) {
                throw new RuntimeException('Formato no permitido en testimonio ' . $order . '. Usa PNG, WEBP o JPEG.');
            }

            $ga = ga_save_upload($cn, $upload, 'img_testimonios', 'testimonio-cliente', 'web', 'testimonios', 1);
            $newPath = trim((string)($ga['ruta_relativa'] ?? ''));
            if ($newPath === '') {
                throw new RuntimeException('No se pudo obtener la ruta de imagen del testimonio ' . $order . '.');
            }

            if ($currentPath !== '' && $currentPath !== $newPath) {
                ga_mark_and_delete($cn, $currentPath, 'reemplazado');
            }
            $currentPath = $newPath;
        }

        $okItem = cw_testimonios_upsert_item($cn, [
            'orden' => $order,
            'nombre_cliente' => (string)$item['nombre_cliente'],
            'profesion' => (string)$item['profesion'],
            'testimonio' => (string)$item['testimonio'],
            'imagen_path' => $currentPath,
        ]);

        if (!$okItem) {
            throw new RuntimeException('No se pudo guardar el testimonio ' . $order . '.');
        }
    }

    $deletedRows = cw_testimonios_delete_items_not_in_orders($cn, [1, 2]);
    foreach ($deletedRows as $row) {
        $path = trim((string)($row['imagen_path'] ?? ''));
        if ($path !== '') {
            ga_mark_and_delete($cn, $path, 'borrado');
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

    cw_testimonios_admin_json_exit([
        'ok' => false,
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo guardar la configuracion de testimonios.',
    ]);
}

cw_testimonios_admin_json_exit([
    'ok' => true,
    'message' => 'Modulo testimonios actualizado correctamente.',
]);
