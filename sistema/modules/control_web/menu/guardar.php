<?php
// modules/control_web/menu/guardar.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../consola/gestion_archivos.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_menu_json_exit')) {
    function cw_menu_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_menu_link_valid')) {
    function cw_menu_link_valid(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (preg_match('#^\s*(javascript:|data:)#i', $url)) {
            return false;
        }

        if (preg_match('/^#[a-zA-Z][a-zA-Z0-9\-_:.]*$/', $url)) {
            return true;
        }
        if (preg_match('#^https?://#i', $url)) {
            return (bool)filter_var($url, FILTER_VALIDATE_URL);
        }
        if ($url[0] === '/' || $url[0] === '.') {
            return true;
        }

        // Permite rutas relativas simples como "web/about.html"
        return (bool)preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-._\/#?=&%]*$/', $url);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_menu_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tituloPagina = trim((string)($_POST['titulo_pagina'] ?? ''));
$botonTexto = trim((string)($_POST['boton_texto'] ?? ''));
$botonUrl = trim((string)($_POST['boton_url'] ?? ''));
$menuItemsJson = (string)($_POST['menu_items_json'] ?? '');
$eliminarLogo = !empty($_POST['eliminar_logo']);

$rawItems = json_decode($menuItemsJson, true);
$items = cw_menu_normalize_items($rawItems);

$errors = [];

if ($tituloPagina === '') {
    $errors[] = 'El titulo de pagina es obligatorio.';
} elseif (strlen($tituloPagina) > 120) {
    $errors[] = 'El titulo de pagina no puede superar 120 caracteres.';
}

if ($botonTexto === '') {
    $errors[] = 'El texto del boton de accion es obligatorio.';
} elseif (strlen($botonTexto) > 80) {
    $errors[] = 'El texto del boton no puede superar 80 caracteres.';
}

if ($botonUrl === '' || !cw_menu_link_valid($botonUrl)) {
    $errors[] = 'El enlace del boton de accion no es valido.';
}

if (count($items) < 1) {
    $errors[] = 'Debes registrar minimo una opcion principal.';
}
if (count($items) > 6) {
    $errors[] = 'Solo se permiten hasta 6 opciones principales.';
}

foreach ($items as $i => &$item) {
    $num = $i + 1;
    $item['texto'] = trim((string)($item['texto'] ?? ''));
    $item['url'] = trim((string)($item['url'] ?? ''));
    $item['visible'] = !empty($item['visible']) ? 1 : 0;

    if ($item['texto'] === '') {
        $errors[] = "La opcion {$num} no tiene texto.";
    } elseif (strlen($item['texto']) > 80) {
        $errors[] = "El texto de la opcion {$num} supera 80 caracteres.";
    }

    if ($item['url'] === '' || !cw_menu_link_valid($item['url'])) {
        $errors[] = "La opcion {$num} tiene un enlace invalido.";
    }

    if ($i === 0) {
        $item['visible'] = 1;
    }

    if (!isset($item['submenus']) || !is_array($item['submenus'])) {
        $item['submenus'] = [];
    }

    foreach ($item['submenus'] as $j => &$sub) {
        $sNum = $j + 1;
        $sub['texto'] = trim((string)($sub['texto'] ?? ''));
        $sub['url'] = trim((string)($sub['url'] ?? ''));
        $sub['visible'] = !empty($sub['visible']) ? 1 : 0;

        if ($sub['texto'] === '') {
            $errors[] = "El submenu {$sNum} de la opcion {$num} no tiene texto.";
        } elseif (strlen($sub['texto']) > 80) {
            $errors[] = "El texto del submenu {$sNum} de la opcion {$num} supera 80 caracteres.";
        }

        if ($sub['url'] === '' || !cw_menu_link_valid($sub['url'])) {
            $errors[] = "El submenu {$sNum} de la opcion {$num} tiene un enlace invalido.";
        }
    }
    unset($sub);
}
unset($item);

if (!empty($errors)) {
    cw_menu_json_exit([
        'ok' => false,
        'message' => 'No se pudo guardar el menu. Revisa los campos.',
        'errors' => $errors,
    ]);
}

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_menu_json_exit([
            'ok' => false,
            'message' => 'No se pudo obtener conexion a base de datos.',
        ]);
    }

    $prev = cw_menu_fetch($cn);
    $logoPath = (string)($prev['logo_path'] ?? '');

    if ($eliminarLogo && $logoPath !== '') {
        ga_mark_and_delete($cn, $logoPath, 'borrado');
        $logoPath = '';
    }

    if (!empty($_FILES['logo_archivo']) && ($_FILES['logo_archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $logo = $_FILES['logo_archivo'];
        if (($logo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            cw_menu_json_exit(['ok' => false, 'message' => 'Error al subir el logo.']);
        }

        if (($logo['size'] ?? 0) > 3 * 1024 * 1024) {
            cw_menu_json_exit(['ok' => false, 'message' => 'El logo excede 3MB.']);
        }

        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$fi->file($logo['tmp_name']);
        $allowed = ['image/png', 'image/webp', 'image/jpeg'];
        if (!in_array($mime, $allowed, true)) {
            cw_menu_json_exit(['ok' => false, 'message' => 'Formato no permitido. Usa PNG, WEBP o JPEG.']);
        }

        $ga = ga_save_upload($cn, $logo, 'logo_web', 'logo-web', 'web', 'menu', 1);
        $newLogoPath = (string)($ga['ruta_relativa'] ?? '');
        if ($newLogoPath === '') {
            cw_menu_json_exit(['ok' => false, 'message' => 'No se pudo obtener la ruta del nuevo logo.']);
        }

        if ($logoPath !== '' && $logoPath !== $newLogoPath) {
            ga_mark_and_delete($cn, $logoPath, 'reemplazado');
        }
        $logoPath = $newLogoPath;
    }

    $payload = [
        'titulo_pagina' => $tituloPagina,
        'logo_path' => $logoPath,
        'menu_items' => $items,
        'boton_texto' => $botonTexto,
        'boton_url' => $botonUrl,
    ];

    $ok = cw_menu_upsert($cn, $payload);
    if (!$ok) {
        cw_menu_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar la configuracion de menu en base de datos.',
        ]);
    }
} catch (Throwable $e) {
    cw_menu_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error al guardar el menu.',
    ]);
}

cw_menu_json_exit([
    'ok' => true,
    'message' => 'Menu actualizado correctamente.',
]);
