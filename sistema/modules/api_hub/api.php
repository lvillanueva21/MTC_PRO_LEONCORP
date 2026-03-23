<?php
// /modules/api_hub/api.php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/apisperu_client.php';
require_once __DIR__ . '/usage_repo.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

function api_hub_ok(array $data = []): void
{
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_hub_err(int $status, string $msg, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_hub_current_empresa_id(): int
{
    $u = currentUser();
    return (int)($u['empresa']['id'] ?? 0);
}

function api_hub_current_user_id(): int
{
    $u = currentUser();
    return (int)($u['id'] ?? 0);
}

function api_hub_require_lookup_access(): void
{
    requireAuth();
    // Desarrollo, Recepcion, Administracion o Control con permiso especial para Caja.
    if (!acl_can_ids_or_control_special([1, 3, 4], 'caja')) {
        api_hub_err(403, 'No tienes permiso para consultar este servicio.');
    }
}

function api_hub_require_dashboard_access(): void
{
    requireAuth();
    // Solo Desarrollo
    if (!acl_can_ids([1])) {
        api_hub_err(403, 'No tienes permiso para ver el dashboard de ApiHub.');
    }
}

function api_hub_lookup_response(string $tipo, string $documento, array $res): void
{
    global $db;

    $empresaId = api_hub_current_empresa_id();
    if ($empresaId <= 0) {
        api_hub_err(403, 'No se encontro empresa asociada al usuario.');
    }

    $countable = (bool)($res['countable'] ?? false);
    $ok = (bool)($res['ok'] ?? false);
    $userMessage = trim((string)($res['user_message'] ?? ''));

    if ($countable) {
        try {
            $logMsg = $userMessage !== '' ? $userMessage : (string)($res['provider_message'] ?? '');
            apihub_register_usage($db, [
                'empresa_id' => $empresaId,
                'user_id' => api_hub_current_user_id(),
                'tipo' => $tipo,
                'ok' => $ok,
                'message' => $logMsg,
                'provider_name' => (string)($res['provider_name'] ?? ''),
                'provider_token_label' => (string)($res['provider_token_label'] ?? ''),
                'fallback_used' => !empty($res['fallback_used']),
                'provider_calls' => $res['provider_calls'] ?? [],
                'attempts' => $res['attempts'] ?? [],
                'documento' => $documento,
                'duration_ms' => (int)($res['duration_ms'] ?? 0),
            ]);
        } catch (Throwable $e) {
            // No bloqueamos la operacion de consulta por fallas de logging.
        }
    }

    if ($ok) {
        api_hub_ok([
            'tipo' => $tipo,
            'data' => $res['data'] ?? [],
            'provider' => [
                'name' => (string)($res['provider_name'] ?? ''),
                'token_label' => (string)($res['provider_token_label'] ?? ''),
                'fallback_used' => !empty($res['fallback_used']),
                'status' => (int)($res['provider_status'] ?? 200),
                'message' => (string)($res['provider_message'] ?? ''),
            ],
        ]);
    }

    $code = (string)($res['code'] ?? 'error');
    $status = 400;
    if ($code === 'service_unavailable' || $code === 'limit_reached') {
        $status = 503;
    } elseif ($code === 'not_configured') {
        $status = 500;
    } elseif ($code === 'invalid_document') {
        $status = 422;
    } elseif ($code === 'not_found') {
        $status = 404;
    }

    api_hub_err($status, $userMessage !== '' ? $userMessage : 'No se pudo completar la consulta.', [
        'code' => $code,
        'tipo' => $tipo,
        'provider' => [
            'name' => (string)($res['provider_name'] ?? ''),
            'token_label' => (string)($res['provider_token_label'] ?? ''),
            'fallback_used' => !empty($res['fallback_used']),
            'status' => (int)($res['provider_status'] ?? 0),
            'message' => (string)($res['provider_message'] ?? ''),
        ],
    ]);
}

function api_hub_get_provider_month_calls(mysqli $db, int $empresaId): array
{
    if ($empresaId <= 0) {
        return apihub_empty_provider_counts();
    }
    try {
        return apihub_get_company_month_provider_calls($db, $empresaId);
    } catch (Throwable $e) {
        return apihub_empty_provider_counts();
    }
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? $_POST['accion'] ?? '';
    $action = trim((string)$action);

    if ($action === 'consultar_dni') {
        api_hub_require_lookup_access();
        $dni = trim((string)($_POST['numero'] ?? ''));
        $empresaId = api_hub_current_empresa_id();
        $res = apihub_consultar_dni($dni, [
            'provider_month_calls' => api_hub_get_provider_month_calls($db, $empresaId),
        ]);
        api_hub_lookup_response('DNI', $dni, $res);
    }

    if ($action === 'consultar_ruc') {
        api_hub_require_lookup_access();
        $ruc = trim((string)($_POST['numero'] ?? ''));
        $empresaId = api_hub_current_empresa_id();
        $res = apihub_consultar_ruc($ruc, [
            'provider_month_calls' => api_hub_get_provider_month_calls($db, $empresaId),
        ]);
        api_hub_lookup_response('RUC', $ruc, $res);
    }

    if ($action === 'dashboard_month') {
        api_hub_require_dashboard_access();
        $periodo = trim((string)($_GET['periodo'] ?? ''));
        if (preg_match('/^\d{4}\-\d{2}$/', $periodo)) {
            $periodo .= '-01';
        }
        if (!preg_match('/^\d{4}\-\d{2}\-01$/', $periodo)) {
            $periodo = date('Y-m-01');
        }
        $dash = apihub_dashboard_month($db, $periodo);
        api_hub_ok($dash);
    }

    api_hub_err(400, 'Accion no valida.');
} catch (Throwable $e) {
    api_hub_err(500, 'Error interno de ApiHub.');
}

