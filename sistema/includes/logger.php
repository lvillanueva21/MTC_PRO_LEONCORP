<?php
// /includes/logger.php
// Bloquear acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

/**
 * Inicializa logging a un archivo (sin romper si no hay permisos).
 * $logFile: ruta absoluta O relativa al filesystem.
 */
function app_log_init($logFile)
{
    // Reportar TODO, pero NO mostrar en pantalla (evita HTML en APIs)
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
    @error_reporting(E_ALL);

    // Resolver ruta
    $file = (string)$logFile;
    if ($file === '') return;

    $dir = dirname($file);

    // Intentar crear dir si no existe
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    // Si el dir existe, intentamos apuntar error_log ahí
    if (is_dir($dir)) {
        @ini_set('error_log', $file);
    }

    // Loguear fatales al final (parse errors no, pero E_ERROR sí)
    static $shutdownRegistered = false;
    if (!$shutdownRegistered) {
        $shutdownRegistered = true;

        register_shutdown_function(function () {
            $err = error_get_last();
            if (!$err) return;

            // Tipos fatales típicos
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($err['type'], $fatalTypes, true)) return;

            app_log('FATAL', $err['message'], [
                'file' => $err['file'],
                'line' => $err['line'],
                'type' => $err['type'],
            ]);
        });
    }
}

/**
 * Escribe una línea simple al log.
 */
function app_log($level, $message, array $context = [])
{
    $ts = date('Y-m-d H:i:s');
    $lvl = strtoupper((string)$level);
    $msg = (string)$message;

    $ctx = '';
    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE);
        if ($json !== false) $ctx = ' | ' . $json;
    }

    // error_log() respeta ini_set('error_log')
    @error_log("[$ts] [$lvl] $msg$ctx");
}

/**
 * Loguea excepción con trazas.
 */
function app_log_exception($e, array $context = [])
{
    $msg = is_object($e) && method_exists($e, 'getMessage') ? $e->getMessage() : 'Exception';
    $file = is_object($e) && method_exists($e, 'getFile') ? $e->getFile() : '';
    $line = is_object($e) && method_exists($e, 'getLine') ? $e->getLine() : 0;
    $trace = is_object($e) && method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : '';

    $context['ex_file'] = $file;
    $context['ex_line'] = $line;

    app_log('ERROR', $msg, $context);

    if ($trace) {
        app_log('TRACE', $trace);
    }
}
