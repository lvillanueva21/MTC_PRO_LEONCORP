<?php
// /modules/api_hub/apisperu_client.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

/**
 * Carga configuración de ApiHub (APISPERU).
 */
function apihub_apisperu_cfg(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $all = require __DIR__ . '/../../includes/config.php';
    $node = $all['api_hub']['apisperu'] ?? [];

    $cfg = [
        'token'                   => trim((string)($node['token'] ?? '')),
        'base_url'                => rtrim((string)($node['base_url'] ?? 'https://dniruc.apisperu.com/api/v1'), '/'),
        'timeout_seconds'         => max(3, (int)($node['timeout_seconds'] ?? 12)),
        'connect_timeout_seconds' => max(2, (int)($node['connect_timeout_seconds'] ?? 6)),
    ];
    return $cfg;
}

/**
 * Cliente HTTP simple (compatibilidad amplia).
 */
function apihub_http_get_json(string $url, int $timeout, int $connectTimeout): array
{
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'La extensión cURL no está disponible en el servidor.',
        ];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'MTC-ApiHub/1.0',
    ]);

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => $error ?: ('Error cURL #' . $errno),
        ];
    }

    $json = null;
    if (is_string($raw) && $raw !== '') {
        $json = json_decode($raw, true);
    }

    return [
        'ok' => ($status >= 200 && $status < 300),
        'status' => $status,
        'data' => is_array($json) ? $json : null,
        'error' => ($status >= 200 && $status < 300) ? '' : ('HTTP ' . $status),
    ];
}

function apihub_provider_msg(?array $data): string
{
    if (!is_array($data)) {
        return '';
    }

    $keys = ['message', 'mensaje', 'error', 'errors'];
    foreach ($keys as $k) {
        if (!isset($data[$k])) {
            continue;
        }
        if (is_string($data[$k])) {
            return trim($data[$k]);
        }
        if (is_array($data[$k])) {
            $txt = implode(' | ', array_map('strval', $data[$k]));
            return trim($txt);
        }
    }
    return '';
}

function apihub_consultar_dni(string $dni): array
{
    $dni = trim($dni);
    if (!preg_match('/^\d{8}$/', $dni)) {
        return [
            'ok' => false,
            'countable' => false,
            'code' => 'invalid_document',
            'user_message' => 'El DNI debe tener 8 dígitos.',
        ];
    }

    $cfg = apihub_apisperu_cfg();
    if ($cfg['token'] === '') {
        return [
            'ok' => false,
            'countable' => false,
            'code' => 'not_configured',
            'user_message' => 'ApiHub no está configurado para consultas externas.',
        ];
    }

    $url = $cfg['base_url'] . '/dni/' . rawurlencode($dni) . '?token=' . rawurlencode($cfg['token']);
    $res = apihub_http_get_json($url, $cfg['timeout_seconds'], $cfg['connect_timeout_seconds']);
    $providerMsg = apihub_provider_msg($res['data']);

    if (!$res['ok']) {
        return [
            'ok' => false,
            'countable' => true,
            'code' => 'service_unavailable',
            'user_message' => 'No se pudo consultar RENIEC en este momento. Intenta nuevamente.',
            'provider_status' => (int)$res['status'],
            'provider_message' => $providerMsg !== '' ? $providerMsg : (string)$res['error'],
        ];
    }

    $d = is_array($res['data']) ? $res['data'] : [];
    $nombres = trim((string)($d['nombres'] ?? ''));
    $apPat = trim((string)($d['apellidoPaterno'] ?? ''));
    $apMat = trim((string)($d['apellidoMaterno'] ?? ''));

    if ($nombres === '' && $apPat === '' && $apMat === '') {
        return [
            'ok' => false,
            'countable' => true,
            'code' => 'not_found',
            'user_message' => 'No se encontró información para ese DNI.',
            'provider_status' => (int)$res['status'],
            'provider_message' => $providerMsg,
        ];
    }

    return [
        'ok' => true,
        'countable' => true,
        'code' => 'ok',
        'user_message' => '',
        'provider_status' => (int)$res['status'],
        'provider_message' => $providerMsg,
        'data' => [
            'dni' => trim((string)($d['dni'] ?? $dni)),
            'nombres' => $nombres,
            'apellido_paterno' => $apPat,
            'apellido_materno' => $apMat,
            'cod_verifica' => trim((string)($d['codVerifica'] ?? '')),
        ],
    ];
}

function apihub_consultar_ruc(string $ruc): array
{
    $ruc = trim($ruc);
    if (!preg_match('/^\d{11}$/', $ruc)) {
        return [
            'ok' => false,
            'countable' => false,
            'code' => 'invalid_document',
            'user_message' => 'El RUC debe tener 11 dígitos.',
        ];
    }

    $cfg = apihub_apisperu_cfg();
    if ($cfg['token'] === '') {
        return [
            'ok' => false,
            'countable' => false,
            'code' => 'not_configured',
            'user_message' => 'ApiHub no está configurado para consultas externas.',
        ];
    }

    $url = $cfg['base_url'] . '/ruc/' . rawurlencode($ruc) . '?token=' . rawurlencode($cfg['token']);
    $res = apihub_http_get_json($url, $cfg['timeout_seconds'], $cfg['connect_timeout_seconds']);
    $providerMsg = apihub_provider_msg($res['data']);

    if (!$res['ok']) {
        return [
            'ok' => false,
            'countable' => true,
            'code' => 'service_unavailable',
            'user_message' => 'No se pudo consultar SUNAT en este momento. Intenta nuevamente.',
            'provider_status' => (int)$res['status'],
            'provider_message' => $providerMsg !== '' ? $providerMsg : (string)$res['error'],
        ];
    }

    $d = is_array($res['data']) ? $res['data'] : [];
    $razon = trim((string)($d['razonSocial'] ?? ''));

    if ($razon === '') {
        return [
            'ok' => false,
            'countable' => true,
            'code' => 'not_found',
            'user_message' => 'No se encontró información para ese RUC.',
            'provider_status' => (int)$res['status'],
            'provider_message' => $providerMsg,
        ];
    }

    return [
        'ok' => true,
        'countable' => true,
        'code' => 'ok',
        'user_message' => '',
        'provider_status' => (int)$res['status'],
        'provider_message' => $providerMsg,
        'data' => [
            'ruc' => trim((string)($d['ruc'] ?? $ruc)),
            'razon_social' => $razon,
        ],
    ];
}
