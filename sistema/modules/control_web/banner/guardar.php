<?php
// modules/control_web/banner/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_banner_json_exit')) {
    function cw_banner_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_banner_strlen')) {
    function cw_banner_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_banner_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloSuperior = trim((string)($_POST['titulo_superior'] ?? ''));
$tituloPrincipal = trim((string)($_POST['titulo_principal'] ?? ''));
$descripcion = trim((string)($_POST['descripcion'] ?? ''));
$boton1Texto = trim((string)($_POST['boton_1_texto'] ?? ''));
$boton1Url = trim((string)($_POST['boton_1_url'] ?? ''));
$boton2Texto = trim((string)($_POST['boton_2_texto'] ?? ''));
$boton2Url = trim((string)($_POST['boton_2_url'] ?? ''));
$eliminarImagen = !empty($_POST['eliminar_imagen']);

$errors = [];

if (cw_banner_strlen($tituloSuperior) > 60) {
    $errors[] = 'El titulo superior no puede superar 60 caracteres.';
}
if (cw_banner_strlen($tituloPrincipal) > 100) {
    $errors[] = 'El titulo principal no puede superar 100 caracteres.';
}
if (cw_banner_strlen($descripcion) > 220) {
    $errors[] = 'La descripcion no puede superar 220 caracteres.';
}
if (cw_banner_strlen($boton1Texto) > 40) {
    $errors[] = 'El texto del boton 1 no puede superar 40 caracteres.';
}
if (cw_banner_strlen($boton2Texto) > 40) {
    $errors[] = 'El texto del boton 2 no puede superar 40 caracteres.';
}
if (cw_banner_strlen($boton1Url) > 255) {
    $errors[] = 'El enlace del boton 1 no puede superar 255 caracteres.';
}
if (cw_banner_strlen($boton2Url) > 255) {
    $errors[] = 'El enlace del boton 2 no puede superar 255 caracteres.';
}
if ($boton1Url !== '' && !cw_banner_link_valid($boton1Url)) {
    $errors[] = 'El enlace del boton 1 no es valido.';
}
if ($boton2Url !== '' && !cw_banner_link_valid($boton2Url)) {
    $errors[] = 'El enlace del boton 2 no es valido.';
}

if (!empty($errors)) {
    cw_banner_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la configuracion de banner.',
        'errors' => $errors,
    ]);
}

$imagenPath = '';

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_banner_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $prev = cw_banner_fetch($cn);
    $imagenPath = trim((string)($prev['imagen_path'] ?? ''));

    if ($eliminarImagen && $imagenPath !== '') {
        ga_mark_and_delete($cn, $imagenPath, 'borrado');
        $imagenPath = '';
    }

    if (!empty($_FILES['imagen_archivo']) && ($_FILES['imagen_archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $imageFile = $_FILES['imagen_archivo'];
        if (($imageFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            cw_banner_json_exit(['ok' => false, 'message' => 'Error al subir la imagen del banner.']);
        }

        if (($imageFile['size'] ?? 0) > 3 * 1024 * 1024) {
            cw_banner_json_exit(['ok' => false, 'message' => 'La imagen del banner excede 3MB.']);
        }

        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$fi->file((string)$imageFile['tmp_name']);
        $allowed = ['image/png', 'image/webp', 'image/jpeg'];
        if (!in_array($mime, $allowed, true)) {
            cw_banner_json_exit(['ok' => false, 'message' => 'Formato no permitido. Usa PNG, WEBP o JPEG.']);
        }

        $ga = ga_save_upload($cn, $imageFile, 'img_banner', 'banner-web', 'web', 'banner', 1);
        $newImagePath = (string)($ga['ruta_relativa'] ?? '');
        if ($newImagePath === '') {
            cw_banner_json_exit(['ok' => false, 'message' => 'No se pudo obtener la ruta de la imagen del banner.']);
        }

        if ($imagenPath !== '' && $imagenPath !== $newImagePath) {
            ga_mark_and_delete($cn, $imagenPath, 'reemplazado');
        }
        $imagenPath = $newImagePath;
    }

    $payload = [
        'titulo_superior' => cw_banner_limit_text($tituloSuperior, 60),
        'titulo_principal' => cw_banner_limit_text($tituloPrincipal, 100),
        'descripcion' => cw_banner_limit_text($descripcion, 220),
        'boton_1_texto' => cw_banner_limit_text($boton1Texto, 40),
        'boton_1_url' => cw_banner_limit_text($boton1Url, 255),
        'boton_2_texto' => cw_banner_limit_text($boton2Texto, 40),
        'boton_2_url' => cw_banner_limit_text($boton2Url, 255),
        'imagen_path' => $imagenPath,
    ];

    $ok = cw_banner_upsert($cn, $payload);
    if (!$ok) {
        cw_banner_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar la configuracion de banner en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_banner_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar banner.',
    ]);
}

cw_banner_json_exit([
    'ok' => true,
    'message' => 'Banner actualizado correctamente.',
    'imagen_path' => $imagenPath,
    'imagen_url' => cw_banner_resolve_image_url($imagenPath),
]);
