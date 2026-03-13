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

function apihub_register_usage(mysqli $db, int $empresaId, string $tipo, bool $ok, string $message = ''): void
{
    $tipo = strtoupper(trim($tipo));
    if (!in_array($tipo, ['DNI', 'RUC'], true)) {
        return;
    }

    $periodoMes = date('Y-m-01');
    $estado = $ok ? 'OK' : 'FAIL';
    $msg = apihub_trim_message($message, 255);

    $dniOk = 0;
    $dniFail = 0;
    $rucOk = 0;
    $rucFail = 0;

    if ($tipo === 'DNI') {
        if ($ok) {
            $dniOk = 1;
        } else {
            $dniFail = 1;
        }
    } else {
        if ($ok) {
            $rucOk = 1;
        } else {
            $rucFail = 1;
        }
    }

    $sql = "INSERT INTO mod_api_hub_uso_mensual
            (empresa_id, periodo_mes, dni_ok, dni_fail, ruc_ok, ruc_fail,
             ultima_consulta_at, ultima_tipo, ultima_estado, ultima_mensaje, created_at, updated_at)
            VALUES
            (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              dni_ok = dni_ok + VALUES(dni_ok),
              dni_fail = dni_fail + VALUES(dni_fail),
              ruc_ok = ruc_ok + VALUES(ruc_ok),
              ruc_fail = ruc_fail + VALUES(ruc_fail),
              ultima_consulta_at = VALUES(ultima_consulta_at),
              ultima_tipo = VALUES(ultima_tipo),
              ultima_estado = VALUES(ultima_estado),
              ultima_mensaje = VALUES(ultima_mensaje),
              updated_at = NOW()";

    $st = $db->prepare($sql);
    $st->bind_param(
        'isiiiisss',
        $empresaId,
        $periodoMes,
        $dniOk,
        $dniFail,
        $rucOk,
        $rucFail,
        $tipo,
        $estado,
        $msg
    );
    $st->execute();
    $st->close();
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
                  (u.dni_ok + u.dni_fail + u.ruc_ok + u.ruc_fail) AS total_consultas,
                  u.ultima_consulta_at, u.ultima_tipo, u.ultima_estado, u.ultima_mensaje
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
                   COALESCE(SUM(ruc_fail),0) AS ruc_fail
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
        ],
        'rows' => $rows,
    ];
}
