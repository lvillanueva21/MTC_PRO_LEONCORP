<?php
// modules/control_web/cabecera/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_topbar_json_exit')) {
    function cw_topbar_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_topbar_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$direccion = trim((string)($_POST['direccion'] ?? ''));
$telefono = preg_replace('/\D+/', '', (string)($_POST['telefono'] ?? ''));
$correo = trim((string)($_POST['correo'] ?? ''));
$whatsappUrl = cw_topbar_normalize_url((string)($_POST['whatsapp_url'] ?? ''));
$facebookUrl = cw_topbar_normalize_url((string)($_POST['facebook_url'] ?? ''));
$instagramUrl = cw_topbar_normalize_url((string)($_POST['instagram_url'] ?? ''));
$youtubeUrl = cw_topbar_normalize_url((string)($_POST['youtube_url'] ?? ''));

$errors = [];
if ($direccion === '') {
    $errors[] = 'La direccion es obligatoria.';
} elseif (strlen($direccion) > 180) {
    $errors[] = 'La direccion no puede superar 180 caracteres.';
}

if (!preg_match('/^9\d{8}$/', $telefono)) {
    $errors[] = 'El celular debe tener 9 digitos e iniciar con 9.';
}

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El correo no tiene un formato valido.';
}

if ($whatsappUrl === '') {
    $errors[] = 'Debes registrar como minimo el enlace de WhatsApp.';
} elseif (!cw_topbar_is_valid_social_url($whatsappUrl, 'whatsapp')) {
    $errors[] = 'El enlace de WhatsApp no es valido.';
}

if ($facebookUrl !== '' && !cw_topbar_is_valid_social_url($facebookUrl, 'facebook')) {
    $errors[] = 'El enlace de Facebook no es valido.';
}
if ($instagramUrl !== '' && !cw_topbar_is_valid_social_url($instagramUrl, 'instagram')) {
    $errors[] = 'El enlace de Instagram no es valido.';
}
if ($youtubeUrl !== '' && !cw_topbar_is_valid_social_url($youtubeUrl, 'youtube')) {
    $errors[] = 'El enlace de YouTube no es valido.';
}

if (!empty($errors)) {
    cw_topbar_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar. Revisa los campos marcados.',
        'errors' => $errors,
    ]);
}

$payload = [
    'direccion' => $direccion,
    'telefono' => $telefono,
    'correo' => $correo,
    'whatsapp_url' => $whatsappUrl,
    'facebook_url' => $facebookUrl,
    'instagram_url' => $instagramUrl,
    'youtube_url' => $youtubeUrl,
];

try {
    $cn = db();
    $ok = ($cn instanceof mysqli) ? cw_topbar_upsert($cn, $payload) : false;
    if (!$ok) {
        cw_topbar_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_topbar_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar la configuracion.',
    ]);
}

cw_topbar_json_exit([
    'ok' => true,
    'message' => 'Cabecera actualizada correctamente.',
]);
