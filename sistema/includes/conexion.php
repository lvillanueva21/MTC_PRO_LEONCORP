<?php
// Bloquear acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

$cfg = require __DIR__ . '/config.php';

// Logger (global)
require_once __DIR__ . '/logger.php';
app_log_init(__DIR__ . '/../logs/app_error.log');

// Configuración de errores mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Definir BASE_URL
if (!defined('BASE_URL')) {
    if (!empty($cfg['app']['base_url'])) {
        define('BASE_URL', rtrim($cfg['app']['base_url'], '/'));
    } else {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? null) == 443);

        $scheme  = $isHttps ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $appRoot = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        define('BASE_URL', $scheme . '://' . $host . ($appRoot ?: ''));
    }
}

// Conexión a la BD
try {
    $mysqli = new mysqli(
        $cfg['db']['host'],
        $cfg['db']['user'],
        $cfg['db']['pass'],
        $cfg['db']['name'],
        $cfg['db']['port']
    );

    // Charset de la conexión
    $mysqli->set_charset($cfg['db']['charset']);

    // Zona horaria de PHP (aplicación)
    if (!empty($cfg['app']['timezone'])) {
        date_default_timezone_set($cfg['app']['timezone']);
    } else {
        // Fallback explícito a Lima si por alguna razón no hubiera valor en config
        date_default_timezone_set('America/Lima');
    }

    // Zona horaria de MySQL para ESTA conexión
    // 1) Intentamos usar la misma zona que PHP (America/Lima, según config.php)
    // 2) Si MySQL no tiene esa zona cargada, caemos a offset fijo -05:00 (Perú sin DST)
    $phpTimezone = date_default_timezone_get();

    try {
        $safeTz = $mysqli->real_escape_string($phpTimezone);
        $mysqli->query("SET time_zone = '" . $safeTz . "'");
    } catch (Throwable $eTz1) {
        try {
            // Fallback a offset horario de Lima (UTC-5)
            $mysqli->query("SET time_zone = '-05:00'");
        } catch (Throwable $eTz2) {
            // Si también falla, seguimos con la zona por defecto del servidor MySQL
            // (no matamos la app aquí para no romper todo)
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    exit('Error de conexión a la base de datos.');
}

/**
 * Retorna la conexión activa
 */
function db(): mysqli
{
    global $mysqli;
    return $mysqli;
}
