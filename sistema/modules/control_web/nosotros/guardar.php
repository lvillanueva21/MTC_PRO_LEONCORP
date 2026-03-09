<?php
// modules/control_web/nosotros/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_about_json_exit')) {
    function cw_about_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_about_strlen')) {
    function cw_about_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if (!function_exists('cw_about_handle_image_upload')) {
    function cw_about_handle_image_upload(
        mysqli $cn,
        string $fieldName,
        bool $removeRequested,
        string $currentPath,
        string $category,
        string $basename
    ): array {
        $currentPath = trim($currentPath);

        if ($removeRequested && $currentPath !== '') {
            ga_mark_and_delete($cn, $currentPath, 'borrado');
            $currentPath = '';
        }

        if (!empty($_FILES[$fieldName]) && ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$fieldName];
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                return ['ok' => false, 'message' => 'Error al subir archivo: ' . $fieldName];
            }

            if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
                return ['ok' => false, 'message' => 'El archivo "' . $fieldName . '" excede 3MB.'];
            }

            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$fi->file((string)$file['tmp_name']);
            $allowed = ['image/png', 'image/webp', 'image/jpeg'];
            if (!in_array($mime, $allowed, true)) {
                return ['ok' => false, 'message' => 'Formato no permitido en "' . $fieldName . '". Usa PNG, WEBP o JPEG.'];
            }

            $ga = ga_save_upload($cn, $file, $category, $basename, 'web', 'nosotros', 1);
            $newPath = (string)($ga['ruta_relativa'] ?? '');
            if ($newPath === '') {
                return ['ok' => false, 'message' => 'No se pudo obtener la ruta del archivo "' . $fieldName . '".'];
            }

            if ($currentPath !== '' && $currentPath !== $newPath) {
                ga_mark_and_delete($cn, $currentPath, 'reemplazado');
            }

            $currentPath = $newPath;
        }

        return ['ok' => true, 'path' => $currentPath];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_about_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloBase = trim((string)($_POST['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($_POST['titulo_resaltado'] ?? ''));
$descripcionPrincipal = trim((string)($_POST['descripcion_principal'] ?? ''));
$descripcionSecundaria = trim((string)($_POST['descripcion_secundaria'] ?? ''));
$experienciaNumero = trim((string)($_POST['experiencia_numero'] ?? ''));
$experienciaTexto = trim((string)($_POST['experiencia_texto'] ?? ''));
$botonTexto = trim((string)($_POST['boton_texto'] ?? ''));
$botonUrl = trim((string)($_POST['boton_url'] ?? ''));
$fundadorNombre = trim((string)($_POST['fundador_nombre'] ?? ''));
$fundadorCargo = trim((string)($_POST['fundador_cargo'] ?? ''));

$cardTitulos = isset($_POST['card_titulo']) && is_array($_POST['card_titulo']) ? array_values($_POST['card_titulo']) : [];
$cardTextos = isset($_POST['card_texto']) && is_array($_POST['card_texto']) ? array_values($_POST['card_texto']) : [];
$checklistItems = isset($_POST['checklist_item']) && is_array($_POST['checklist_item']) ? array_values($_POST['checklist_item']) : [];

$removeIcono1 = !empty($_POST['eliminar_icono_1']);
$removeIcono2 = !empty($_POST['eliminar_icono_2']);
$removeFundador = !empty($_POST['eliminar_imagen_fundador']);
$removePrincipal = !empty($_POST['eliminar_imagen_principal']);
$removeSecundaria = !empty($_POST['eliminar_imagen_secundaria']);

$errors = [];

if (cw_about_strlen($tituloBase) > 40) {
    $errors[] = 'El texto base del titulo no puede superar 40 caracteres.';
}
if (cw_about_strlen($tituloResaltado) > 40) {
    $errors[] = 'El texto resaltado del titulo no puede superar 40 caracteres.';
}
if (cw_about_strlen($descripcionPrincipal) > 320) {
    $errors[] = 'La descripcion principal no puede superar 320 caracteres.';
}
if (cw_about_strlen($descripcionSecundaria) > 500) {
    $errors[] = 'La descripcion complementaria no puede superar 500 caracteres.';
}
if (cw_about_strlen($experienciaNumero) > 10) {
    $errors[] = 'El numero de experiencia no puede superar 10 caracteres.';
}
if ($experienciaNumero !== '' && !preg_match('/^[0-9+\s]+$/', $experienciaNumero)) {
    $errors[] = 'El numero de experiencia solo admite digitos, espacios y "+".';
}
if (cw_about_strlen($experienciaTexto) > 80) {
    $errors[] = 'El texto de experiencia no puede superar 80 caracteres.';
}
if (cw_about_strlen($botonTexto) > 80) {
    $errors[] = 'El texto del boton no puede superar 80 caracteres.';
}
if (cw_about_strlen($botonUrl) > 255) {
    $errors[] = 'El enlace del boton no puede superar 255 caracteres.';
}
if ($botonUrl !== '' && !cw_about_link_valid($botonUrl)) {
    $errors[] = 'El enlace del boton no es valido.';
}
if (cw_about_strlen($fundadorNombre) > 80) {
    $errors[] = 'El nombre del fundador no puede superar 80 caracteres.';
}
if (cw_about_strlen($fundadorCargo) > 80) {
    $errors[] = 'El cargo del fundador no puede superar 80 caracteres.';
}

$cardsRaw = [];
for ($i = 0; $i < 2; $i++) {
    $num = $i + 1;
    $title = trim((string)($cardTitulos[$i] ?? ''));
    $text = trim((string)($cardTextos[$i] ?? ''));

    if (cw_about_strlen($title) > 70) {
        $errors[] = "El titulo de la tarjeta {$num} supera 70 caracteres.";
    }
    if (cw_about_strlen($text) > 220) {
        $errors[] = "El texto de la tarjeta {$num} supera 220 caracteres.";
    }

    $cardsRaw[] = [
        'icono_path' => '',
        'titulo' => cw_about_limit_text($title, 70),
        'texto' => cw_about_limit_text($text, 220),
    ];
}

$checklistRaw = [];
for ($i = 0; $i < 4; $i++) {
    $num = $i + 1;
    $item = trim((string)($checklistItems[$i] ?? ''));
    if (cw_about_strlen($item) > 90) {
        $errors[] = "El item {$num} del checklist supera 90 caracteres.";
    }
    $checklistRaw[] = cw_about_limit_text($item, 90);
}

if (!empty($errors)) {
    cw_about_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar la seccion Nosotros.',
        'errors' => $errors,
    ]);
}

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_about_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $prev = cw_about_fetch($cn);
    $prevCards = cw_about_normalize_cards($prev['tarjetas'] ?? []);

    for ($i = 0; $i < 2; $i++) {
        $cardsRaw[$i]['icono_path'] = trim((string)($prevCards[$i]['icono_path'] ?? ''));
    }

    $imagenFundadorPath = trim((string)($prev['imagen_fundador_path'] ?? ''));
    $imagenPrincipalPath = trim((string)($prev['imagen_principal_path'] ?? ''));
    $imagenSecundariaPath = trim((string)($prev['imagen_secundaria_path'] ?? ''));

    $icon1Result = cw_about_handle_image_upload($cn, 'icono_archivo_1', $removeIcono1, (string)$cardsRaw[0]['icono_path'], 'img_nosotros', 'nosotros-icono-1');
    if (empty($icon1Result['ok'])) {
        cw_about_json_exit(['ok' => false, 'message' => (string)$icon1Result['message']]);
    }
    $cardsRaw[0]['icono_path'] = (string)$icon1Result['path'];

    $icon2Result = cw_about_handle_image_upload($cn, 'icono_archivo_2', $removeIcono2, (string)$cardsRaw[1]['icono_path'], 'img_nosotros', 'nosotros-icono-2');
    if (empty($icon2Result['ok'])) {
        cw_about_json_exit(['ok' => false, 'message' => (string)$icon2Result['message']]);
    }
    $cardsRaw[1]['icono_path'] = (string)$icon2Result['path'];

    $fundadorResult = cw_about_handle_image_upload($cn, 'imagen_fundador_archivo', $removeFundador, $imagenFundadorPath, 'img_nosotros', 'nosotros-fundador');
    if (empty($fundadorResult['ok'])) {
        cw_about_json_exit(['ok' => false, 'message' => (string)$fundadorResult['message']]);
    }
    $imagenFundadorPath = (string)$fundadorResult['path'];

    $principalResult = cw_about_handle_image_upload($cn, 'imagen_principal_archivo', $removePrincipal, $imagenPrincipalPath, 'img_nosotros', 'nosotros-principal');
    if (empty($principalResult['ok'])) {
        cw_about_json_exit(['ok' => false, 'message' => (string)$principalResult['message']]);
    }
    $imagenPrincipalPath = (string)$principalResult['path'];

    $secundariaResult = cw_about_handle_image_upload($cn, 'imagen_secundaria_archivo', $removeSecundaria, $imagenSecundariaPath, 'img_nosotros', 'nosotros-secundaria');
    if (empty($secundariaResult['ok'])) {
        cw_about_json_exit(['ok' => false, 'message' => (string)$secundariaResult['message']]);
    }
    $imagenSecundariaPath = (string)$secundariaResult['path'];

    $payload = [
        'titulo_base' => cw_about_limit_text($tituloBase, 40),
        'titulo_resaltado' => cw_about_limit_text($tituloResaltado, 40),
        'descripcion_principal' => cw_about_limit_text($descripcionPrincipal, 320),
        'tarjetas' => cw_about_normalize_cards($cardsRaw),
        'descripcion_secundaria' => cw_about_limit_text($descripcionSecundaria, 500),
        'experiencia_numero' => cw_about_limit_text($experienciaNumero, 10),
        'experiencia_texto' => cw_about_limit_text($experienciaTexto, 80),
        'checklist' => cw_about_normalize_checklist($checklistRaw),
        'boton_texto' => cw_about_limit_text($botonTexto, 80),
        'boton_url' => cw_about_limit_text($botonUrl, 255),
        'fundador_nombre' => cw_about_limit_text($fundadorNombre, 80),
        'fundador_cargo' => cw_about_limit_text($fundadorCargo, 80),
        'imagen_fundador_path' => $imagenFundadorPath,
        'imagen_principal_path' => $imagenPrincipalPath,
        'imagen_secundaria_path' => $imagenSecundariaPath,
    ];

    $ok = cw_about_upsert($cn, $payload);
    if (!$ok) {
        cw_about_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar la configuracion de Nosotros en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_about_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar la seccion Nosotros.',
    ]);
}

$tarjetas = cw_about_normalize_cards($payload['tarjetas']);
cw_about_json_exit([
    'ok' => true,
    'message' => 'Seccion Nosotros actualizada correctamente.',
    'icono_1_url' => cw_about_resolve_image_url((string)($tarjetas[0]['icono_path'] ?? ''), '/web/img/about-icon-1.png'),
    'icono_2_url' => cw_about_resolve_image_url((string)($tarjetas[1]['icono_path'] ?? ''), '/web/img/about-icon-2.png'),
    'imagen_fundador_url' => cw_about_resolve_image_url((string)$payload['imagen_fundador_path'], '/web/img/attachment-img.jpg'),
    'imagen_principal_url' => cw_about_resolve_image_url((string)$payload['imagen_principal_path'], '/web/img/about-img.jpg'),
    'imagen_secundaria_url' => cw_about_resolve_image_url((string)$payload['imagen_secundaria_path'], '/web/img/about-img-1.jpg'),
]);
