<?php
// /modules/api_hub/apisperu_client.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

function apihub_known_providers(): array
{
    return ['apisperu', 'decolecta', 'jsonpe'];
}

function apihub_empty_provider_counts(): array
{
    return ['apisperu' => 0, 'decolecta' => 0, 'jsonpe' => 0];
}

function apihub_normalize_provider_counts($raw): array
{
    $out = apihub_empty_provider_counts();
    if (!is_array($raw)) {
        return $out;
    }
    foreach ($out as $k => $_) {
        $out[$k] = max(0, (int)($raw[$k] ?? 0));
    }
    return $out;
}

function apihub_provider_defaults(string $provider): array
{
    $provider = strtolower(trim($provider));
    if ($provider === 'decolecta') {
        return [
            'enabled' => true,
            'base_url' => 'https://api.decolecta.com/v1',
            'timeout_seconds' => 12,
            'connect_timeout_seconds' => 6,
            'monthly_limit' => 100,
            'tokens' => [],
        ];
    }
    if ($provider === 'jsonpe') {
        return [
            'enabled' => true,
            'base_url' => 'https://api.json.pe',
            'timeout_seconds' => 12,
            'connect_timeout_seconds' => 6,
            'monthly_limit' => 100,
            'tokens' => [],
        ];
    }
    return [
        'enabled' => true,
        'base_url' => 'https://dniruc.apisperu.com/api/v1',
        'timeout_seconds' => 12,
        'connect_timeout_seconds' => 6,
        'monthly_limit' => 0,
        'tokens' => [],
    ];
}

function apihub_normalize_tokens(string $provider, $rawTokens): array
{
    $provider = strtolower(trim($provider));
    $tokens = [];
    $add = function (string $label, string $value) use (&$tokens): void {
        $label = trim($label);
        $value = trim($value);
        if ($value === '') {
            return;
        }
        if ($label === '') {
            $label = 'token_' . (count($tokens) + 1);
        }
        $tokens[] = ['label' => $label, 'value' => $value];
    };

    if (is_string($rawTokens)) {
        $add($provider . '_main', $rawTokens);
    } elseif (is_array($rawTokens)) {
        foreach ($rawTokens as $idx => $item) {
            if (is_string($item)) {
                $add($provider . '_token_' . ($idx + 1), $item);
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $label = (string)($item['label'] ?? ($provider . '_token_' . ($idx + 1)));
            $value = (string)($item['value'] ?? '');
            $add($label, $value);
        }
    }

    return $tokens;
}

function apihub_fixed_fallback_order($rawOrder): array
{
    $fixed = ['apisperu', 'decolecta', 'jsonpe'];
    if (!is_array($rawOrder)) {
        return $fixed;
    }
    $seen = [];
    $out = [];
    foreach ($rawOrder as $p) {
        $k = strtolower(trim((string)$p));
        if (!in_array($k, $fixed, true)) {
            continue;
        }
        if (isset($seen[$k])) {
            continue;
        }
        $seen[$k] = 1;
        $out[] = $k;
    }
    foreach ($fixed as $k) {
        if (!isset($seen[$k])) {
            $out[] = $k;
        }
    }
    return $out;
}

function apihub_runtime_cfg(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $all = require __DIR__ . '/../../includes/config.php';
    $node = is_array($all['api_hub'] ?? null) ? $all['api_hub'] : [];
    $providersNode = is_array($node['providers'] ?? null) ? $node['providers'] : [];
    $providers = [];

    foreach (apihub_known_providers() as $provider) {
        $defaults = apihub_provider_defaults($provider);
        $raw = is_array($providersNode[$provider] ?? null) ? $providersNode[$provider] : [];

        // Compatibilidad con estructura antigua para APISPERU.
        if ($provider === 'apisperu' && !$raw) {
            $legacy = is_array($node['apisperu'] ?? null) ? $node['apisperu'] : [];
            if ($legacy) {
                $raw = [
                    'enabled' => true,
                    'base_url' => $legacy['base_url'] ?? $defaults['base_url'],
                    'timeout_seconds' => $legacy['timeout_seconds'] ?? $defaults['timeout_seconds'],
                    'connect_timeout_seconds' => $legacy['connect_timeout_seconds'] ?? $defaults['connect_timeout_seconds'],
                    'monthly_limit' => 0,
                    'tokens' => [
                        ['label' => 'apisperu_main', 'value' => (string)($legacy['token'] ?? '')],
                    ],
                ];
            }
        }

        $tokens = apihub_normalize_tokens($provider, $raw['tokens'] ?? []);
        if ($provider === 'apisperu' && !$tokens) {
            $legacyToken = trim((string)($node['apisperu']['token'] ?? ''));
            if ($legacyToken !== '') {
                $tokens[] = ['label' => 'apisperu_main', 'value' => $legacyToken];
            }
        }

        $providers[$provider] = [
            'enabled' => !isset($raw['enabled']) ? (bool)$defaults['enabled'] : (bool)$raw['enabled'],
            'base_url' => rtrim((string)($raw['base_url'] ?? $defaults['base_url']), '/'),
            'timeout_seconds' => max(3, (int)($raw['timeout_seconds'] ?? $defaults['timeout_seconds'])),
            'connect_timeout_seconds' => max(2, (int)($raw['connect_timeout_seconds'] ?? $defaults['connect_timeout_seconds'])),
            'monthly_limit' => max(0, (int)($raw['monthly_limit'] ?? $defaults['monthly_limit'])),
            'tokens' => $tokens,
        ];
    }

    $fallbackNode = is_array($node['fallback_order'] ?? null) ? $node['fallback_order'] : [];
    $cfg = [
        'providers' => $providers,
        'fallback_order' => [
            'dni' => apihub_fixed_fallback_order($fallbackNode['dni'] ?? null),
            'ruc' => apihub_fixed_fallback_order($fallbackNode['ruc'] ?? null),
        ],
    ];
    return $cfg;
}

function apihub_http_request_json(
    string $method,
    string $url,
    int $timeout,
    int $connectTimeout,
    array $headers = [],
    ?array $jsonBody = null
): array {
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'La extension cURL no esta disponible en el servidor.',
        ];
    }

    $method = strtoupper(trim($method));
    if ($method === '') {
        $method = 'GET';
    }

    $httpHeaders = ['Accept: application/json'];
    foreach ($headers as $h) {
        $h = trim((string)$h);
        if ($h !== '') {
            $httpHeaders[] = $h;
        }
    }

    $payload = null;
    if ($jsonBody !== null) {
        $payload = json_encode($jsonBody, JSON_UNESCAPED_UNICODE);
        if (!in_array('Content-Type: application/json', $httpHeaders, true)) {
            $httpHeaders[] = 'Content-Type: application/json';
        }
    }

    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $httpHeaders,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'MTC-ApiHub/2.0',
        CURLOPT_CUSTOMREQUEST => $method,
    ];

    if ($payload !== null) {
        $opts[CURLOPT_POSTFIELDS] = $payload;
    }

    $ch = curl_init();
    curl_setopt_array($ch, $opts);
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
            'error' => $error !== '' ? $error : ('Error cURL #' . $errno),
        ];
    }

    $json = null;
    $rawTrim = '';
    if (is_string($raw) && trim($raw) !== '') {
        $rawTrim = trim($raw);
        $json = json_decode($raw, true);
    }

    if ($status >= 200 && $status < 300 && $rawTrim !== '' && !is_array($json)) {
        return [
            'ok' => false,
            'status' => $status,
            'data' => null,
            'error' => 'Respuesta no valida del proveedor.',
        ];
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
    $keys = ['message', 'mensaje', 'error', 'errors', 'detail'];
    foreach ($keys as $k) {
        if (!array_key_exists($k, $data)) {
            continue;
        }
        $v = $data[$k];
        if (is_string($v)) {
            return trim($v);
        }
        if (is_array($v)) {
            $txt = implode(' | ', array_map('strval', $v));
            return trim($txt);
        }
    }
    return '';
}

function apihub_failure_code_from_http(int $status): string
{
    if ($status === 401 || $status === 403) {
        return 'auth_error';
    }
    return 'service_unavailable';
}

function apihub_parse_dni_payload(string $provider, ?array $payload, string $dni): array
{
    $d = is_array($payload) ? $payload : [];
    if ($provider === 'jsonpe' && is_array($d['data'] ?? null)) {
        $d = $d['data'];
    }

    if ($provider === 'decolecta') {
        $nombres = trim((string)($d['first_name'] ?? ''));
        $apPat = trim((string)($d['first_last_name'] ?? ''));
        $apMat = trim((string)($d['second_last_name'] ?? ''));
        $numero = trim((string)($d['document_number'] ?? $dni));
    } elseif ($provider === 'jsonpe') {
        $nombres = trim((string)($d['nombres'] ?? ''));
        $apPat = trim((string)($d['apellido_paterno'] ?? ''));
        $apMat = trim((string)($d['apellido_materno'] ?? ''));
        $numero = trim((string)($d['numero'] ?? $dni));
    } else {
        $nombres = trim((string)($d['nombres'] ?? ''));
        $apPat = trim((string)($d['apellidoPaterno'] ?? ''));
        $apMat = trim((string)($d['apellidoMaterno'] ?? ''));
        $numero = trim((string)($d['dni'] ?? $dni));
    }

    if ($nombres === '' && $apPat === '' && $apMat === '') {
        return ['ok' => false, 'code' => 'not_found', 'data' => []];
    }

    return [
        'ok' => true,
        'code' => 'ok',
        'data' => [
            'dni' => $numero !== '' ? $numero : $dni,
            'nombres' => $nombres,
            'apellido_paterno' => $apPat,
            'apellido_materno' => $apMat,
        ],
    ];
}

function apihub_parse_ruc_payload(string $provider, ?array $payload, string $ruc): array
{
    $d = is_array($payload) ? $payload : [];
    if ($provider === 'jsonpe' && is_array($d['data'] ?? null)) {
        $d = $d['data'];
    }

    if ($provider === 'decolecta') {
        $razon = trim((string)($d['razon_social'] ?? ''));
        $numero = trim((string)($d['numero_documento'] ?? $ruc));
    } elseif ($provider === 'jsonpe') {
        $razon = trim((string)($d['nombre_o_razon_social'] ?? ''));
        $numero = trim((string)($d['ruc'] ?? $ruc));
    } else {
        $razon = trim((string)($d['razonSocial'] ?? ''));
        $numero = trim((string)($d['ruc'] ?? $ruc));
    }

    if ($razon === '') {
        return ['ok' => false, 'code' => 'not_found', 'data' => []];
    }

    return [
        'ok' => true,
        'code' => 'ok',
        'data' => [
            'ruc' => $numero !== '' ? $numero : $ruc,
            'razon_social' => $razon,
        ],
    ];
}

function apihub_provider_request(
    string $provider,
    string $tipo,
    string $numero,
    array $providerCfg,
    string $token
): array {
    $provider = strtolower(trim($provider));
    $tipo = strtoupper(trim($tipo));
    $base = rtrim((string)$providerCfg['base_url'], '/');
    $timeout = max(3, (int)$providerCfg['timeout_seconds']);
    $connect = max(2, (int)$providerCfg['connect_timeout_seconds']);
    $token = trim($token);

    if ($provider === 'decolecta') {
        if ($tipo === 'DNI') {
            $url = $base . '/reniec/dni?numero=' . rawurlencode($numero);
        } else {
            $url = $base . '/sunat/ruc?numero=' . rawurlencode($numero);
        }
        $res = apihub_http_request_json(
            'GET',
            $url,
            $timeout,
            $connect,
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ]
        );
    } elseif ($provider === 'jsonpe') {
        if ($tipo === 'DNI') {
            $url = $base . '/api/dni';
            $body = ['dni' => $numero];
        } else {
            $url = $base . '/api/ruc';
            $body = ['ruc' => $numero];
        }
        $res = apihub_http_request_json(
            'POST',
            $url,
            $timeout,
            $connect,
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            $body
        );
    } else {
        if ($tipo === 'DNI') {
            $url = $base . '/dni/' . rawurlencode($numero) . '?token=' . rawurlencode($token);
        } else {
            $url = $base . '/ruc/' . rawurlencode($numero) . '?token=' . rawurlencode($token);
        }
        $res = apihub_http_request_json('GET', $url, $timeout, $connect);
    }

    $status = (int)($res['status'] ?? 0);
    $payload = is_array($res['data'] ?? null) ? $res['data'] : null;
    $providerMsg = apihub_provider_msg($payload);
    if ($providerMsg === '') {
        $providerMsg = (string)($res['error'] ?? '');
    }

    if (!(bool)($res['ok'] ?? false)) {
        return [
            'ok' => false,
            'status' => $status,
            'code' => apihub_failure_code_from_http($status),
            'provider_message' => $providerMsg,
            'data' => [],
        ];
    }

    $parsed = ($tipo === 'DNI')
        ? apihub_parse_dni_payload($provider, $payload, $numero)
        : apihub_parse_ruc_payload($provider, $payload, $numero);

    if (!(bool)$parsed['ok']) {
        return [
            'ok' => false,
            'status' => $status,
            'code' => (string)$parsed['code'],
            'provider_message' => $providerMsg,
            'data' => [],
        ];
    }

    return [
        'ok' => true,
        'status' => $status > 0 ? $status : 200,
        'code' => 'ok',
        'provider_message' => $providerMsg,
        'data' => $parsed['data'],
    ];
}

function apihub_build_user_message(string $tipo, string $code): string
{
    $tipo = strtoupper(trim($tipo));
    $isDni = ($tipo === 'DNI');
    if ($code === 'not_found') {
        return $isDni
            ? 'No se encontro informacion para ese DNI.'
            : 'No se encontro informacion para ese RUC.';
    }
    if ($code === 'limit_reached') {
        return 'Se alcanzo el limite mensual de consultas de respaldo. Intenta nuevamente mas tarde.';
    }
    if ($code === 'inconclusive') {
        return $isDni
            ? 'No se pudo confirmar el DNI en este momento. Intenta nuevamente en unos minutos.'
            : 'No se pudo confirmar el RUC en este momento. Intenta nuevamente en unos minutos.';
    }
    if ($code === 'not_configured') {
        return 'ApiHub no esta configurado para consultas externas.';
    }
    return $isDni
        ? 'No se pudo consultar RENIEC en este momento. Intenta nuevamente.'
        : 'No se pudo consultar SUNAT en este momento. Intenta nuevamente.';
}

function apihub_consultar_documento(string $tipo, string $numero, array $ctx = []): array
{
    $started = microtime(true);
    $tipo = strtoupper(trim($tipo));
    $numero = trim($numero);

    if ($tipo === 'DNI') {
        if (!preg_match('/^\d{8}$/', $numero)) {
            return [
                'ok' => false,
                'countable' => false,
                'code' => 'invalid_document',
                'user_message' => 'El DNI debe tener 8 digitos.',
                'provider_calls' => apihub_empty_provider_counts(),
                'attempts' => [],
                'duration_ms' => 0,
            ];
        }
    } else {
        if (!preg_match('/^\d{11}$/', $numero)) {
            return [
                'ok' => false,
                'countable' => false,
                'code' => 'invalid_document',
                'user_message' => 'El RUC debe tener 11 digitos.',
                'provider_calls' => apihub_empty_provider_counts(),
                'attempts' => [],
                'duration_ms' => 0,
            ];
        }
    }

    $cfg = apihub_runtime_cfg();
    $order = $cfg['fallback_order'][strtolower($tipo)] ?? apihub_fixed_fallback_order(null);
    $monthCalls = apihub_normalize_provider_counts($ctx['provider_month_calls'] ?? []);
    $providerCalls = apihub_empty_provider_counts();
    $attempts = [];

    $hadHttpAttempt = false;
    $hadNotFound = false;
    $hadServiceIssue = false;
    $hadLimitSkip = false;
    $hadConfiguredProvider = false;
    $providerOutcomes = [];
    $lastFailure = [
        'provider' => '',
        'token_label' => '',
        'status' => 0,
        'code' => 'not_configured',
        'provider_message' => '',
    ];

    foreach ($order as $provider) {
        $p = strtolower(trim((string)$provider));
        if (!isset($cfg['providers'][$p])) {
            continue;
        }
        $providerOutcomes[$p] = 'not_configured';
        $pcfg = $cfg['providers'][$p];
        if (!(bool)$pcfg['enabled']) {
            $attempts[] = [
                'provider' => $p,
                'token_label' => '',
                'status' => 0,
                'code' => 'disabled',
                'message' => 'Proveedor deshabilitado.',
                'ok' => false,
                'skipped' => true,
            ];
            $providerOutcomes[$p] = 'disabled';
            continue;
        }

        $tokens = is_array($pcfg['tokens']) ? $pcfg['tokens'] : [];
        if (!$tokens) {
            $attempts[] = [
                'provider' => $p,
                'token_label' => '',
                'status' => 0,
                'code' => 'not_configured',
                'message' => 'Sin tokens configurados.',
                'ok' => false,
                'skipped' => true,
            ];
            $providerOutcomes[$p] = 'not_configured';
            continue;
        }

        $hadConfiguredProvider = true;
        $limit = max(0, (int)$pcfg['monthly_limit']);
        if ($limit > 0 && (int)($monthCalls[$p] ?? 0) >= $limit) {
            $hadLimitSkip = true;
            $attempts[] = [
                'provider' => $p,
                'token_label' => '',
                'status' => 0,
                'code' => 'limit_reached',
                'message' => 'Limite mensual alcanzado para proveedor.',
                'ok' => false,
                'skipped' => true,
            ];
            $providerOutcomes[$p] = 'limit_reached';
            continue;
        }

        $tokenCount = count($tokens);
        $providerHadHttp = false;
        $providerOnlyNotFound = false;
        for ($i = 0; $i < $tokenCount; $i++) {
            $tokenLabel = (string)($tokens[$i]['label'] ?? ('token_' . ($i + 1)));
            $tokenValue = (string)($tokens[$i]['value'] ?? '');
            if (trim($tokenValue) === '') {
                continue;
            }

            $hadHttpAttempt = true;
            $providerHadHttp = true;
            $providerCalls[$p] = (int)$providerCalls[$p] + 1;
            $monthCalls[$p] = (int)$monthCalls[$p] + 1;

            $res = apihub_provider_request($p, $tipo, $numero, $pcfg, $tokenValue);

            $attempts[] = [
                'provider' => $p,
                'token_label' => $tokenLabel,
                'status' => (int)($res['status'] ?? 0),
                'code' => (string)($res['code'] ?? 'error'),
                'message' => (string)($res['provider_message'] ?? ''),
                'ok' => (bool)($res['ok'] ?? false),
                'skipped' => false,
            ];

            if ((bool)($res['ok'] ?? false)) {
                $duration = (int)round((microtime(true) - $started) * 1000);
                return [
                    'ok' => true,
                    'countable' => true,
                    'code' => 'ok',
                    'user_message' => '',
                    'provider_name' => $p,
                    'provider_token_label' => $tokenLabel,
                    'provider_status' => (int)($res['status'] ?? 200),
                    'provider_message' => (string)($res['provider_message'] ?? ''),
                    'fallback_used' => count($attempts) > 1,
                    'provider_calls' => $providerCalls,
                    'attempts' => $attempts,
                    'data' => (array)($res['data'] ?? []),
                    'duration_ms' => $duration,
                ];
            }

            $code = (string)($res['code'] ?? 'service_unavailable');
            if ($code === 'not_found') {
                $hadNotFound = true;
                $providerOnlyNotFound = true;
            } else {
                $hadServiceIssue = true;
                $providerOnlyNotFound = false;
            }
            $lastFailure = [
                'provider' => $p,
                'token_label' => $tokenLabel,
                'status' => (int)($res['status'] ?? 0),
                'code' => $code,
                'provider_message' => (string)($res['provider_message'] ?? ''),
            ];

            // Solo rotar token del mismo proveedor por 401/403.
            if ($code === 'auth_error') {
                continue;
            }
            break;
        }

        if ($providerHadHttp) {
            $providerOutcomes[$p] = $providerOnlyNotFound ? 'not_found' : 'service_unavailable';
        }
    }

    $finalCode = 'not_configured';
    $consultedProviders = 0;
    $notFoundProviders = 0;
    foreach ($providerOutcomes as $outcome) {
        if ($outcome === 'not_found' || $outcome === 'service_unavailable') {
            $consultedProviders++;
        }
        if ($outcome === 'not_found') {
            $notFoundProviders++;
        }
    }

    // Si los 3 proveedores fueron consultados y todos devolvieron "no encontrado",
    // concluimos como no encontrado de forma clara para negocio.
    if ($consultedProviders >= 3 && $notFoundProviders === $consultedProviders) {
        $finalCode = 'not_found';
    } elseif ($hadHttpAttempt) {
        // Mezcla de "no encontrado" + fallas tecnicas: resultado no concluyente.
        $finalCode = ($hadNotFound && $hadServiceIssue) ? 'inconclusive' : (($hadNotFound && !$hadServiceIssue) ? 'not_found' : 'service_unavailable');
    } elseif ($hadLimitSkip && $hadConfiguredProvider) {
        $finalCode = 'limit_reached';
    }

    $duration = (int)round((microtime(true) - $started) * 1000);
    return [
        'ok' => false,
        'countable' => $hadHttpAttempt,
        'code' => $finalCode,
        'user_message' => apihub_build_user_message($tipo, $finalCode),
        'provider_name' => (string)$lastFailure['provider'],
        'provider_token_label' => (string)$lastFailure['token_label'],
        'provider_status' => (int)$lastFailure['status'],
        'provider_message' => (string)$lastFailure['provider_message'],
        'fallback_used' => count($attempts) > 1,
        'provider_calls' => $providerCalls,
        'attempts' => $attempts,
        'duration_ms' => $duration,
    ];
}

function apihub_consultar_dni(string $dni, array $ctx = []): array
{
    return apihub_consultar_documento('DNI', $dni, $ctx);
}

function apihub_consultar_ruc(string $ruc, array $ctx = []): array
{
    return apihub_consultar_documento('RUC', $ruc, $ctx);
}
