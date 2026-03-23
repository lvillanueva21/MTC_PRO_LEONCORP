<?php
// /modules/api_hub/usage_repo.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

function apihub_trim_message($msg, $max = 255): string
{
    $txt = trim((string)$msg);
    $max = max(8, (int)$max);
    if ($txt === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($txt, 'UTF-8') <= $max) {
            return $txt;
        }
        return rtrim(mb_substr($txt, 0, $max - 3, 'UTF-8')) . '...';
    }
    if (strlen($txt) <= $max) {
        return $txt;
    }
    return rtrim(substr($txt, 0, $max - 3)) . '...';
}

function apihub_usage_empty_provider_counts(): array
{
    return ['apisperu' => 0, 'decolecta' => 0, 'jsonpe' => 0];
}

function apihub_normalize_provider_name(string $provider): string
{
    $p = strtolower(trim($provider));
    return in_array($p, ['apisperu', 'decolecta', 'jsonpe'], true) ? $p : '';
}

function apihub_usage_normalize_provider_counts($raw): array
{
    $out = apihub_usage_empty_provider_counts();
    if (!is_array($raw)) {
        return $out;
    }
    foreach ($out as $k => $_) {
        $out[$k] = max(0, (int)($raw[$k] ?? 0));
    }
    return $out;
}

function apihub_mask_document(string $tipo, string $documento): string
{
    $tipo = strtoupper(trim($tipo));
    $doc = preg_replace('/\D+/', '', trim($documento));
    $len = strlen($doc);
    if ($len === 0) {
        return '';
    }

    if ($tipo === 'DNI') {
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return substr($doc, 0, 4) . str_repeat('*', $len - 4);
    }

    if ($len <= 7) {
        return substr($doc, 0, 2) . str_repeat('*', max(0, $len - 2));
    }
    return substr($doc, 0, 6) . str_repeat('*', $len - 7) . substr($doc, -1);
}

function apihub_get_company_month_provider_calls(mysqli $db, int $empresaId): array
{
    $out = apihub_usage_empty_provider_counts();
    if ($empresaId <= 0) {
        return $out;
    }

    $periodoMes = date('Y-m-01');
    $sql = "SELECT
              dni_calls_apisperu, dni_calls_decolecta, dni_calls_jsonpe,
              ruc_calls_apisperu, ruc_calls_decolecta, ruc_calls_jsonpe
            FROM mod_api_hub_uso_mensual
            WHERE empresa_id = ? AND periodo_mes = ?
            LIMIT 1";
    $st = $db->prepare($sql);
    $st->bind_param('is', $empresaId, $periodoMes);
    $st->execute();
    $row = $st->get_result()->fetch_assoc() ?: [];
    $st->close();

    $out['apisperu'] = (int)($row['dni_calls_apisperu'] ?? 0) + (int)($row['ruc_calls_apisperu'] ?? 0);
    $out['decolecta'] = (int)($row['dni_calls_decolecta'] ?? 0) + (int)($row['ruc_calls_decolecta'] ?? 0);
    $out['jsonpe'] = (int)($row['dni_calls_jsonpe'] ?? 0) + (int)($row['ruc_calls_jsonpe'] ?? 0);
    return $out;
}

function apihub_register_usage(mysqli $db, array $payload): void
{
    $empresaId = (int)($payload['empresa_id'] ?? 0);
    $userId = (int)($payload['user_id'] ?? 0);
    $tipo = strtoupper(trim((string)($payload['tipo'] ?? '')));
    $ok = (bool)($payload['ok'] ?? false);
    $message = apihub_trim_message((string)($payload['message'] ?? ''), 255);
    $providerName = apihub_normalize_provider_name((string)($payload['provider_name'] ?? ''));
    $tokenLabel = trim((string)($payload['provider_token_label'] ?? ''));
    $fallbackUsed = !empty($payload['fallback_used']) ? 1 : 0;
    $providerCalls = apihub_usage_normalize_provider_counts($payload['provider_calls'] ?? []);
    $attempts = is_array($payload['attempts'] ?? null) ? $payload['attempts'] : [];
    $documento = trim((string)($payload['documento'] ?? ''));
    $durationMs = max(0, (int)($payload['duration_ms'] ?? 0));

    if ($empresaId <= 0 || !in_array($tipo, ['DNI', 'RUC'], true)) {
        return;
    }

    $periodoMes = date('Y-m-01');
    $estado = $ok ? 'OK' : 'FAIL';
    $dniOk = ($tipo === 'DNI' && $ok) ? 1 : 0;
    $dniFail = ($tipo === 'DNI' && !$ok) ? 1 : 0;
    $rucOk = ($tipo === 'RUC' && $ok) ? 1 : 0;
    $rucFail = ($tipo === 'RUC' && !$ok) ? 1 : 0;

    $dniCallsApisperu = ($tipo === 'DNI') ? (int)$providerCalls['apisperu'] : 0;
    $dniCallsDecolecta = ($tipo === 'DNI') ? (int)$providerCalls['decolecta'] : 0;
    $dniCallsJsonpe = ($tipo === 'DNI') ? (int)$providerCalls['jsonpe'] : 0;
    $rucCallsApisperu = ($tipo === 'RUC') ? (int)$providerCalls['apisperu'] : 0;
    $rucCallsDecolecta = ($tipo === 'RUC') ? (int)$providerCalls['decolecta'] : 0;
    $rucCallsJsonpe = ($tipo === 'RUC') ? (int)$providerCalls['jsonpe'] : 0;

    $sql = "INSERT INTO mod_api_hub_uso_mensual
            (empresa_id, periodo_mes, dni_ok, dni_fail, ruc_ok, ruc_fail,
             dni_calls_apisperu, dni_calls_decolecta, dni_calls_jsonpe,
             ruc_calls_apisperu, ruc_calls_decolecta, ruc_calls_jsonpe,
             ultima_consulta_at, ultima_tipo, ultima_estado, ultima_proveedor, ultima_token_label, ultima_fallback, ultima_mensaje,
             created_at, updated_at)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NULLIF(?, ''), ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              dni_ok = dni_ok + VALUES(dni_ok),
              dni_fail = dni_fail + VALUES(dni_fail),
              ruc_ok = ruc_ok + VALUES(ruc_ok),
              ruc_fail = ruc_fail + VALUES(ruc_fail),
              dni_calls_apisperu = dni_calls_apisperu + VALUES(dni_calls_apisperu),
              dni_calls_decolecta = dni_calls_decolecta + VALUES(dni_calls_decolecta),
              dni_calls_jsonpe = dni_calls_jsonpe + VALUES(dni_calls_jsonpe),
              ruc_calls_apisperu = ruc_calls_apisperu + VALUES(ruc_calls_apisperu),
              ruc_calls_decolecta = ruc_calls_decolecta + VALUES(ruc_calls_decolecta),
              ruc_calls_jsonpe = ruc_calls_jsonpe + VALUES(ruc_calls_jsonpe),
              ultima_consulta_at = VALUES(ultima_consulta_at),
              ultima_tipo = VALUES(ultima_tipo),
              ultima_estado = VALUES(ultima_estado),
              ultima_proveedor = VALUES(ultima_proveedor),
              ultima_token_label = VALUES(ultima_token_label),
              ultima_fallback = VALUES(ultima_fallback),
              ultima_mensaje = VALUES(ultima_mensaje),
              updated_at = NOW()";
    $st = $db->prepare($sql);
    $st->bind_param(
        'isiiiiiiiiiissssis',
        $empresaId,
        $periodoMes,
        $dniOk,
        $dniFail,
        $rucOk,
        $rucFail,
        $dniCallsApisperu,
        $dniCallsDecolecta,
        $dniCallsJsonpe,
        $rucCallsApisperu,
        $rucCallsDecolecta,
        $rucCallsJsonpe,
        $tipo,
        $estado,
        $providerName,
        $tokenLabel,
        $fallbackUsed,
        $message
    );
    $st->execute();
    $st->close();

    $docMasked = apihub_mask_document($tipo, $documento);
    $docHash = hash('sha256', $documento);
    $attemptsJson = json_encode($attempts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($attemptsJson)) {
        $attemptsJson = '[]';
    }

    $sqlDetalle = "INSERT INTO mod_api_hub_consulta_detalle
                   (empresa_id, usuario_id, periodo_mes, tipo, documento_masked, documento_hash,
                    estado_final, proveedor_final, token_label_final, fallback_usado,
                    intentos_json, mensaje_final, duracion_ms, created_at, updated_at)
                   VALUES
                   (?, NULLIF(?, 0), ?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?, NOW(), NOW())";
    $stDet = $db->prepare($sqlDetalle);
    $stDet->bind_param(
        'iisssssssissi',
        $empresaId,
        $userId,
        $periodoMes,
        $tipo,
        $docMasked,
        $docHash,
        $estado,
        $providerName,
        $tokenLabel,
        $fallbackUsed,
        $attemptsJson,
        $message,
        $durationMs
    );
    $stDet->execute();
    $stDet->close();
}

function apihub_dashboard_month(mysqli $db, string $periodoMes): array
{
    if (!preg_match('/^\d{4}\-\d{2}\-01$/', $periodoMes)) {
        $periodoMes = date('Y-m-01');
    }

    $sqlRows = "SELECT
                  u.empresa_id,
                  COALESCE(e.nombre, CONCAT('Empresa #', u.empresa_id)) AS empresa_nombre,
                  u.dni_ok, u.dni_fail, u.ruc_ok, u.ruc_fail,
                  u.dni_calls_apisperu, u.dni_calls_decolecta, u.dni_calls_jsonpe,
                  u.ruc_calls_apisperu, u.ruc_calls_decolecta, u.ruc_calls_jsonpe,
                  (u.dni_ok + u.dni_fail + u.ruc_ok + u.ruc_fail) AS total_consultas,
                  u.ultima_consulta_at, u.ultima_tipo, u.ultima_estado, u.ultima_mensaje,
                  u.ultima_proveedor, u.ultima_token_label, u.ultima_fallback
                FROM mod_api_hub_uso_mensual u
                LEFT JOIN mtp_empresas e ON e.id = u.empresa_id
                WHERE u.periodo_mes = ?
                ORDER BY total_consultas DESC, empresa_nombre ASC";
    $stRows = $db->prepare($sqlRows);
    $stRows->bind_param('s', $periodoMes);
    $stRows->execute();
    $rows = $stRows->get_result()->fetch_all(MYSQLI_ASSOC);
    $stRows->close();

    $sqlTotal = "SELECT
                   COALESCE(SUM(dni_ok),0) AS dni_ok,
                   COALESCE(SUM(dni_fail),0) AS dni_fail,
                   COALESCE(SUM(ruc_ok),0) AS ruc_ok,
                   COALESCE(SUM(ruc_fail),0) AS ruc_fail,
                   COALESCE(SUM(dni_calls_apisperu + ruc_calls_apisperu),0) AS prov_apisperu,
                   COALESCE(SUM(dni_calls_decolecta + ruc_calls_decolecta),0) AS prov_decolecta,
                   COALESCE(SUM(dni_calls_jsonpe + ruc_calls_jsonpe),0) AS prov_jsonpe
                 FROM mod_api_hub_uso_mensual
                 WHERE periodo_mes = ?";
    $stTotal = $db->prepare($sqlTotal);
    $stTotal->bind_param('s', $periodoMes);
    $stTotal->execute();
    $tot = $stTotal->get_result()->fetch_assoc() ?: [];
    $stTotal->close();

    return [
        'periodo' => $periodoMes,
        'totales' => [
            'dni_ok' => (int)($tot['dni_ok'] ?? 0),
            'dni_fail' => (int)($tot['dni_fail'] ?? 0),
            'ruc_ok' => (int)($tot['ruc_ok'] ?? 0),
            'ruc_fail' => (int)($tot['ruc_fail'] ?? 0),
            'provider_calls' => [
                'apisperu' => (int)($tot['prov_apisperu'] ?? 0),
                'decolecta' => (int)($tot['prov_decolecta'] ?? 0),
                'jsonpe' => (int)($tot['prov_jsonpe'] ?? 0),
            ],
        ],
        'rows' => $rows,
    ];
}
