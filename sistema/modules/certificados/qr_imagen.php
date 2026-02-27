<?php
// /modules/certificados/qr_imagen.php
//
// Genera la imagen PNG del código QR a partir de un token (codigo_qr)
// usando la librería phpqrcode (qrlib.php).
// El contenido del QR es la URL absoluta hacia validar_certificado_publico.php.

require_once __DIR__ . '/../../includes/conexion.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if ($token === '') {
    http_response_code(404);
    exit;
}

if (!defined('BASE_URL')) {
    http_response_code(500);
    exit;
}

// URL absoluta a la página pública de validación
$targetUrl = BASE_URL . '/modules/certificados/validar_certificado_publico.php?token=' . rawurlencode($token);

// Ruta de la librería phpqrcode
// Desde /modules/certificados/ vamos un nivel arriba a /modules/ y luego a /phpqrcode/qrlib.php
$qrLibPath = dirname(__DIR__) . '/phpqrcode/qrlib.php';

if (!file_exists($qrLibPath)) {
    // No hay librería QR disponible
    http_response_code(500);
    exit;
}

require_once $qrLibPath;

if (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: image/png');
// Permitir cacheo simple del QR generado
header('Cache-Control: public, max-age=2592000');

\QRcode::png($targetUrl, null, QR_ECLEVEL_L, 4, 2);
