<?php
// modules/egresos/api.php
// API real para registro/listado/anulacion de egresos y generacion PDF.

header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/finanzas_medios.php';

/**
 * Log de guardia temprana para 403 (antes de entrar a la logica JSON del modulo).
 * Esto ayuda cuando ACL o servidor bloquean la peticion.
 */
$egPreLogFile = __DIR__ . '/egresos_http_' . date('Ymd') . '.log';
if (!is_dir(__DIR__) || !is_writable(__DIR__)) {
    $tmpDir = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    if ($tmpDir !== '' && is_dir($tmpDir) && is_writable($tmpDir)) {
        $egPreLogFile = $tmpDir . DIRECTORY_SEPARATOR . 'egresos_http_' . date('Ymd') . '.log';
    }
}
register_shutdown_function(function () use ($egPreLogFile) {
    $code = (int)http_response_code();
    if ($code !== 403) {
        return;
    }

    $payload = [
        'ts' => date('Y-m-d H:i:s'),
        'scope' => 'egresos.http_403',
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'http_host' => $_SERVER['HTTP_HOST'] ?? null,
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'get' => $_GET ?? [],
        'post_keys' => array_keys($_POST ?? []),
    ];

    $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        $line = '{"ts":"' . date('Y-m-d H:i:s') . '","scope":"egresos.http_403"}';
    }
    $ok = @error_log($line . PHP_EOL, 3, $egPreLogFile);
    if (!$ok) {
        @error_log('[egresos.http_403] ' . $line);
    }
});

acl_require_ids([3, 4]); // Recepcion, Administracion
verificarPermiso([3, 4]);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('America/Lima');
}
try {
    $db->query("SET time_zone = 'America/Lima'");
} catch (Throwable $e) {
    $db->query("SET time_zone = '-05:00'");
}

$u = currentUser();
$uid = (int)($u['id'] ?? 0);
$empId = (int)($u['empresa']['id'] ?? 0);
if ($empId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Empresa no asignada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$accion = strtolower(trim((string)($_REQUEST['accion'] ?? 'estado')));
$isPdf = ($accion === 'egreso_pdf');
if (!$isPdf) {
    header('Content-Type: application/json; charset=utf-8');
}

function eg_json_ok(array $data = []): void
{
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function eg_json_err_code(int $code, string $msg, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg] + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function eg_json_err(string $msg, array $extra = []): void
{
    eg_json_err_code(400, $msg, $extra);
}

function eg_log_file_path(): string
{
    static $path = null;
    if ($path !== null) {
        return $path;
    }

    $filename = 'egresos_error_' . date('Ymd') . '.log';
    $baseDir = __DIR__;
    if (is_dir($baseDir) && is_writable($baseDir)) {
        $path = $baseDir . '/' . $filename;
        return $path;
    }

    $tmp = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    if ($tmp !== '') {
        $path = $tmp . DIRECTORY_SEPARATOR . $filename;
        return $path;
    }

    $path = $baseDir . '/' . $filename;
    return $path;
}

function eg_log_event(string $scope, array $context = []): string
{
    $ref = 'EGR-' . date('YmdHis') . '-' . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $line = [
        'ts' => date('Y-m-d H:i:s'),
        'ref' => $ref,
        'scope' => $scope,
        'context' => $context
    ];

    $json = json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '{"ts":"' . date('Y-m-d H:i:s') . '","ref":"' . $ref . '","scope":"' . $scope . '"}';
    }

    try {
        error_log($json . PHP_EOL, 3, eg_log_file_path());
    } catch (Throwable $e) {
        // El log no debe romper el flujo principal.
    }

    return $ref;
}

function eg_log_exception(string $scope, Throwable $e, array $context = []): string
{
    $trace = $e->getTraceAsString();
    if ($trace !== '') {
        if (function_exists('mb_substr')) {
            $trace = mb_substr($trace, 0, 2500, 'UTF-8');
        } else {
            $trace = substr($trace, 0, 2500);
        }
    }

    $context['exception'] = [
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $trace
    ];

    return eg_log_event($scope, $context);
}

function eg_table_exists(mysqli $db, string $name): bool
{
    $name = $db->real_escape_string($name);
    $rs = $db->query("SHOW TABLES LIKE '{$name}'");
    return $rs && $rs->num_rows > 0;
}

function eg_schema_ready(mysqli $db): bool
{
    return eg_table_exists($db, 'egr_egresos')
        && eg_table_exists($db, 'egr_correlativos')
        && eg_table_exists($db, 'egr_egreso_fuentes');
}

function eg_parse_datetime_input(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    $formats = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $raw);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d H:i:s', $ts);
}

function eg_trim_upper(?string $value): string
{
    return strtoupper(trim((string)$value));
}

function eg_safe_text(?string $value, int $max = 255): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max, 'UTF-8');
    }
    return substr($value, 0, $max);
}

function eg_default_fuentes_rows(): array
{
    return array_values(fin_canonical_rows());
}

function eg_map_fuentes_by_key(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $key = fin_source_key_from_input((string)($row['key'] ?? ''));
        if ($key === '') {
            continue;
        }
        $out[$key] = $row;
    }
    return $out;
}

function eg_catalogo_fuentes_medios(mysqli $db): array
{
    $out = [];
    foreach (fin_catalogo_medios_pago($db) as $m) {
        $key = fin_source_key_from_input((string)($m['key'] ?? $m['nombre'] ?? ''));
        if ($key === '') {
            continue;
        }
        if (!isset($out[$key])) {
            $out[$key] = [
                'medio_id' => (int)($m['id'] ?? 0),
                'medio_nombre' => (string)($m['nombre'] ?? ''),
            ];
        }
    }
    return $out;
}

function eg_parse_fuentes_payload($raw): array
{
    $payload = $raw;
    if (!is_array($payload)) {
        $rawStr = trim((string)$raw);
        if ($rawStr === '') {
            return ['error' => '', 'items' => []];
        }
        $decoded = json_decode($rawStr, true);
        if (!is_array($decoded)) {
            return ['error' => 'La distribucion por fuente tiene formato invalido.', 'items' => []];
        }
        $payload = $decoded;
    }

    $out = [];
    foreach ($payload as $item) {
        if (!is_array($item)) {
            continue;
        }
        $rawKey = (string)($item['key'] ?? $item['fuente_key'] ?? $item['fuente'] ?? '');
        $key = fin_source_key_from_input($rawKey);
        if ($key === '') {
            continue;
        }
        $monto = round((float)($item['monto'] ?? 0), 2);
        if ($monto <= 0) {
            continue;
        }
        if (!isset($out[$key])) {
            $out[$key] = 0.0;
        }
        $out[$key] = round((float)$out[$key] + $monto, 2);
    }
    return ['error' => '', 'items' => $out];
}

function eg_caja_context(mysqli $db, int $empId): array
{
    $out = [
        'diaria_abierta' => false,
        'diaria' => [
            'id' => 0,
            'codigo' => '',
            'fecha' => '',
            'estado' => 'cerrada'
        ],
        'mensual' => [
            'id' => 0,
            'codigo' => '',
            'anio' => 0,
            'mes' => 0,
            'estado' => 'cerrada'
        ],
        'puede_registrar' => false,
        'mensaje' => 'No hay caja diaria abierta.',
    ];

    $sqlOpen = "SELECT
                  cd.id AS diaria_id,
                  cd.codigo AS diaria_codigo,
                  cd.fecha AS diaria_fecha,
                  cd.estado AS diaria_estado,
                  cm.id AS mensual_id,
                  cm.codigo AS mensual_codigo,
                  cm.anio AS mensual_anio,
                  cm.mes AS mensual_mes,
                  cm.estado AS mensual_estado
                FROM mod_caja_diaria cd
                INNER JOIN mod_caja_mensual cm ON cm.id = cd.id_caja_mensual
                WHERE cd.id_empresa=? AND cd.estado='abierta'
                ORDER BY cd.fecha ASC, cd.id ASC
                LIMIT 1";
    $st = $db->prepare($sqlOpen);
    $st->bind_param('i', $empId);
    $st->execute();
    $open = $st->get_result()->fetch_assoc();
    $st->close();

    if ($open) {
        $out['diaria_abierta'] = true;
        $out['diaria'] = [
            'id' => (int)$open['diaria_id'],
            'codigo' => (string)$open['diaria_codigo'],
            'fecha' => (string)$open['diaria_fecha'],
            'estado' => (string)$open['diaria_estado'],
        ];
        $out['mensual'] = [
            'id' => (int)$open['mensual_id'],
            'codigo' => (string)$open['mensual_codigo'],
            'anio' => (int)$open['mensual_anio'],
            'mes' => (int)$open['mensual_mes'],
            'estado' => (string)$open['mensual_estado'],
        ];
        $out['puede_registrar'] = ((string)$open['mensual_estado'] === 'abierta');
        $out['mensaje'] = $out['puede_registrar']
            ? 'Caja diaria abierta. Puedes registrar egresos.'
            : 'La caja mensual asociada no está abierta.';
        return $out;
    }

    $sqlDailyLast = "SELECT id, codigo, fecha, estado
                     FROM mod_caja_diaria
                     WHERE id_empresa=?
                     ORDER BY fecha DESC, id DESC
                     LIMIT 1";
    $st = $db->prepare($sqlDailyLast);
    $st->bind_param('i', $empId);
    $st->execute();
    $lastDaily = $st->get_result()->fetch_assoc();
    $st->close();

    if ($lastDaily) {
        $out['diaria'] = [
            'id' => (int)$lastDaily['id'],
            'codigo' => (string)$lastDaily['codigo'],
            'fecha' => (string)$lastDaily['fecha'],
            'estado' => (string)$lastDaily['estado'],
        ];
    }

    $sqlMonthlyCurrent = "SELECT id, codigo, anio, mes, estado
                          FROM mod_caja_mensual
                          WHERE id_empresa=? AND anio=YEAR(CURDATE()) AND mes=MONTH(CURDATE())
                          ORDER BY id DESC
                          LIMIT 1";
    $st = $db->prepare($sqlMonthlyCurrent);
    $st->bind_param('i', $empId);
    $st->execute();
    $monthly = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$monthly) {
        $sqlMonthlyLast = "SELECT id, codigo, anio, mes, estado
                           FROM mod_caja_mensual
                           WHERE id_empresa=?
                           ORDER BY anio DESC, mes DESC, id DESC
                           LIMIT 1";
        $st = $db->prepare($sqlMonthlyLast);
        $st->bind_param('i', $empId);
        $st->execute();
        $monthly = $st->get_result()->fetch_assoc();
        $st->close();
    }

    if ($monthly) {
        $out['mensual'] = [
            'id' => (int)$monthly['id'],
            'codigo' => (string)$monthly['codigo'],
            'anio' => (int)$monthly['anio'],
            'mes' => (int)$monthly['mes'],
            'estado' => (string)$monthly['estado'],
        ];
    }

    $out['mensaje'] = 'No hay caja diaria abierta. Abre la caja desde el módulo Caja para registrar egresos.';
    return $out;
}

function eg_saldo_diaria(mysqli $db, int $empId, int $cajaDiariaId): array
{
    $sql = "SELECT
              (SELECT COALESCE(SUM(apl.monto_aplicado),0)
               FROM pos_abonos a
               LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
               WHERE a.id_empresa=? AND a.caja_diaria_id=?) AS ingresos,
              (SELECT COALESCE(SUM(dv.monto_devuelto),0)
               FROM pos_devoluciones dv
               WHERE dv.id_empresa=? AND dv.caja_diaria_id=?) AS devoluciones,
              (SELECT COALESCE(SUM(e.monto),0)
               FROM egr_egresos e
               WHERE e.id_empresa=? AND e.id_caja_diaria=? AND e.estado='ACTIVO') AS egresos";
    $st = $db->prepare($sql);
    $st->bind_param('iiiiii', $empId, $cajaDiariaId, $empId, $cajaDiariaId, $empId, $cajaDiariaId);
    $st->execute();
    $r = $st->get_result()->fetch_assoc() ?: [];
    $st->close();

    $ingresos = (float)($r['ingresos'] ?? 0);
    $devoluciones = (float)($r['devoluciones'] ?? 0);
    $egresos = (float)($r['egresos'] ?? 0);
    $saldo = $ingresos - $devoluciones - $egresos;
    $porMedio = fin_disponible_por_fuente_diaria($db, $empId, $cajaDiariaId);
    $egresosFuentes = (float)($porMedio['totales']['egresos_activos'] ?? 0);
    $egresosNoDistrib = $egresos - $egresosFuentes;
    if (abs($egresosNoDistrib) < 0.005) {
        $egresosNoDistrib = 0.0;
    }

    return [
        'ingresos' => round($ingresos, 2),
        'devoluciones' => round($devoluciones, 2),
        'egresos' => round($egresos, 2),
        'egresos_por_fuente' => round($egresosFuentes, 2),
        'egresos_no_distribuidos' => round($egresosNoDistrib, 2),
        'saldo_disponible' => round($saldo, 2),
        'por_medio' => $porMedio['rows'] ?? [],
        'por_medio_totales' => $porMedio['totales'] ?? [
            'ingresos' => 0.0,
            'devoluciones' => 0.0,
            'monto_neto' => 0.0,
            'egresos_activos' => 0.0,
            'saldo_disponible' => 0.0,
        ],
    ];
}

function eg_next_correlativo(mysqli $db, int $empId): int
{
    $st = $db->prepare("SELECT ultimo_numero FROM egr_correlativos WHERE id_empresa=? LIMIT 1 FOR UPDATE");
    $st->bind_param('i', $empId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row) {
        $next = (int)$row['ultimo_numero'] + 1;
        $up = $db->prepare("UPDATE egr_correlativos SET ultimo_numero=?, actualizado=NOW() WHERE id_empresa=? LIMIT 1");
        $up->bind_param('ii', $next, $empId);
        $up->execute();
        $up->close();
        return $next;
    }

    $next = 1;
    $ins = $db->prepare("INSERT INTO egr_correlativos(id_empresa, ultimo_numero, actualizado) VALUES (?, ?, NOW())");
    $ins->bind_param('ii', $empId, $next);
    $ins->execute();
    $ins->close();
    return $next;
}

function eg_codigo(int $empId, int $correlativo): string
{
    $empresa3 = str_pad((string)$empId, 3, '0', STR_PAD_LEFT);
    return 'E' . $empresa3 . '-' . str_pad((string)$correlativo, 6, '0', STR_PAD_LEFT);
}

function eg_logo_rel_web(?string $logoPath): string
{
    $logoPath = trim((string)$logoPath);
    if ($logoPath === '') {
        return '../../dist/img/AdminLTELogo.png';
    }
    $candidate = __DIR__ . '/../../' . ltrim($logoPath, '/');
    if (is_file($candidate)) {
        return '../../' . ltrim($logoPath, '/');
    }
    return '../../dist/img/AdminLTELogo.png';
}

function eg_logo_abs_fs(?string $logoPath): ?string
{
    $logoPath = trim((string)$logoPath);
    if ($logoPath !== '') {
        $candidate = __DIR__ . '/../../' . ltrim($logoPath, '/');
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    $fallback = __DIR__ . '/../../dist/img/AdminLTELogo.png';
    if (is_file($fallback)) {
        return $fallback;
    }
    return null;
}

function eg_fmt_money(float $value): string
{
    return 'S/ ' . number_format($value, 2, '.', ',');
}

function eg_fmt_dt(string $dt): string
{
    $ts = strtotime($dt);
    if ($ts === false) {
        return $dt;
    }
    return date('d/m/Y H:i', $ts);
}

function eg_select_one(mysqli $db, int $empId, int $id): ?array
{
    $sql = "SELECT
              e.id, e.id_empresa, e.id_caja_mensual, e.id_caja_diaria, e.codigo, e.correlativo,
              e.tipo_comprobante, e.serie, e.numero, e.referencia, e.fecha_emision, e.monto,
              e.beneficiario, e.documento, e.concepto, e.observaciones, e.estado,
              e.anulado_por, e.anulado_en, e.anulado_motivo, e.creado_por, e.creado, e.actualizado,
              cd.codigo AS caja_diaria_codigo,
              cd.fecha AS caja_diaria_fecha,
              cm.codigo AS caja_mensual_codigo,
              cm.anio AS caja_mensual_anio,
              cm.mes AS caja_mensual_mes,
              emp.nombre AS empresa_nombre,
              emp.razon_social AS empresa_razon_social,
              emp.ruc AS empresa_ruc,
              emp.logo_path AS empresa_logo_path,
              uc.usuario AS creado_usuario,
              CONCAT(uc.nombres, ' ', uc.apellidos) AS creado_nombre,
              ua.usuario AS anulado_usuario,
              CONCAT(ua.nombres, ' ', ua.apellidos) AS anulado_nombre
            FROM egr_egresos e
            INNER JOIN mod_caja_diaria cd ON cd.id = e.id_caja_diaria
            INNER JOIN mod_caja_mensual cm ON cm.id = e.id_caja_mensual
            INNER JOIN mtp_empresas emp ON emp.id = e.id_empresa
            LEFT JOIN mtp_usuarios uc ON uc.id = e.creado_por
            LEFT JOIN mtp_usuarios ua ON ua.id = e.anulado_por
            WHERE e.id_empresa=? AND e.id=?
            LIMIT 1";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $empId, $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ?: null;
}

function eg_select_fuentes(mysqli $db, int $empId, int $egresoId): array
{
    if (!eg_table_exists($db, 'egr_egreso_fuentes')) {
        return [];
    }

    $sql = "SELECT
              f.fuente_key AS `key`,
              f.medio_id,
              mp.nombre AS medio,
              f.monto
            FROM egr_egreso_fuentes f
            LEFT JOIN pos_medios_pago mp ON mp.id = f.medio_id
            WHERE f.id_empresa=? AND f.id_egreso=?
            ORDER BY FIELD(f.fuente_key, 'EFECTIVO', 'YAPE', 'PLIN', 'TRANSFERENCIA'), f.id ASC";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $empId, $egresoId);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $st->close();

    foreach ($rows as $i => $row) {
        $key = fin_source_key_from_input((string)($row['key'] ?? ''));
        $rows[$i]['key'] = $key;
        $rows[$i]['label'] = (string)(fin_canonical_rows()[$key]['label'] ?? $key);
        $rows[$i]['monto'] = round((float)($row['monto'] ?? 0), 2);
    }
    return $rows;
}

try {
    if ($accion === 'estado') {
        $schemaOk = eg_schema_ready($db);
        $ctx = eg_caja_context($db, $empId);
        $zeroRows = eg_default_fuentes_rows();
        $saldo = [
            'ingresos' => 0.0,
            'devoluciones' => 0.0,
            'egresos' => 0.0,
            'egresos_por_fuente' => 0.0,
            'egresos_no_distribuidos' => 0.0,
            'saldo_disponible' => 0.0,
            'por_medio' => $zeroRows,
            'por_medio_totales' => [
                'ingresos' => 0.0,
                'devoluciones' => 0.0,
                'monto_neto' => 0.0,
                'egresos_activos' => 0.0,
                'saldo_disponible' => 0.0,
            ],
        ];
        if ($schemaOk && (int)$ctx['diaria']['id'] > 0) {
            $saldo = eg_saldo_diaria($db, $empId, (int)$ctx['diaria']['id']);
        }

        eg_json_ok([
            'schema_ok' => $schemaOk,
            'schema_message' => $schemaOk ? '' : 'Falta ejecutar la migracion SQL de egresos (tablas egr_* y egr_egreso_fuentes).',
            'caja' => $ctx,
            'saldo' => $saldo,
        ]);
    }

    if (!eg_schema_ready($db)) {
        eg_json_err_code(500, 'La base de datos aun no tiene las tablas de egresos (egr_* y egr_egreso_fuentes). Ejecuta primero la migracion.');
    }

    if ($accion === 'listar') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = (int)($_GET['per'] ?? 10);
        $per = max(5, min(50, $per));
        $q = trim((string)($_GET['q'] ?? ''));
        $tipo = strtoupper(trim((string)($_GET['tipo'] ?? 'TODOS')));
        $estado = strtoupper(trim((string)($_GET['estado'] ?? 'TODOS')));

        $where = ["e.id_empresa=?"];
        $types = 'i';
        $params = [$empId];

        if ($tipo !== '' && $tipo !== 'TODOS') {
            if (!in_array($tipo, ['RECIBO', 'BOLETA', 'FACTURA'], true)) {
                eg_json_err('Tipo de comprobante invalido.');
            }
            $where[] = "e.tipo_comprobante=?";
            $types .= 's';
            $params[] = $tipo;
        }

        if ($estado !== '' && $estado !== 'TODOS') {
            if (!in_array($estado, ['ACTIVO', 'ANULADO'], true)) {
                eg_json_err('Estado invalido.');
            }
            $where[] = "e.estado=?";
            $types .= 's';
            $params[] = $estado;
        }

        if ($q !== '') {
            $where[] = "(e.codigo LIKE ? OR e.beneficiario LIKE ? OR e.documento LIKE ? OR e.concepto LIKE ? OR e.serie LIKE ? OR e.numero LIKE ? OR e.referencia LIKE ?)";
            $like = '%' . $q . '%';
            $types .= 'sssssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $W = 'WHERE ' . implode(' AND ', $where);

        $sqlCount = "SELECT COUNT(*) c FROM egr_egresos e {$W}";
        $st = $db->prepare($sqlCount);
        $st->bind_param($types, ...$params);
        $st->execute();
        $total = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
        $st->close();

        $offset = ($page - 1) * $per;
        $sql = "SELECT
                  e.id, e.codigo, e.tipo_comprobante, e.serie, e.numero, e.referencia,
                  e.fecha_emision, e.monto, e.beneficiario, e.documento, e.concepto, e.estado,
                  e.id_caja_diaria, cd.codigo AS caja_diaria_codigo
                FROM egr_egresos e
                INNER JOIN mod_caja_diaria cd ON cd.id = e.id_caja_diaria
                {$W}
                ORDER BY e.fecha_emision DESC, e.id DESC
                LIMIT ?, ?";
        $typesRows = $types . 'ii';
        $paramsRows = $params;
        $paramsRows[] = $offset;
        $paramsRows[] = $per;

        $st = $db->prepare($sql);
        $st->bind_param($typesRows, ...$paramsRows);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $st->close();

        $totalPages = (int)max(1, ceil($total / $per));

        eg_json_ok([
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per' => $per,
            'total_pages' => $totalPages,
        ]);
    }

    if ($accion === 'detalle') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            eg_json_err('ID de egreso invalido.');
        }
        $row = eg_select_one($db, $empId, $id);
        if (!$row) {
            eg_json_err_code(404, 'Egreso no encontrado.');
        }
        $row['empresa_logo_web'] = eg_logo_rel_web($row['empresa_logo_path'] ?? '');
        $row['fuentes'] = eg_select_fuentes($db, $empId, $id);
        eg_json_ok(['row' => $row]);
    }

    if ($accion === 'crear') {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            eg_json_err('Metodo no permitido.');
        }

        $tipo = eg_trim_upper($_POST['tipo_comprobante'] ?? '');
        if (!in_array($tipo, ['RECIBO', 'BOLETA', 'FACTURA'], true)) {
            eg_json_err('Tipo de comprobante invalido.');
        }

        $serie = eg_safe_text($_POST['serie'] ?? '', 10);
        $numero = eg_safe_text($_POST['numero'] ?? '', 20);
        $referencia = eg_safe_text($_POST['referencia'] ?? '', 120);
        $fecha = eg_parse_datetime_input((string)($_POST['fecha_emision'] ?? ''));
        $monto = round((float)($_POST['monto'] ?? 0), 2);
        $beneficiario = eg_safe_text($_POST['beneficiario'] ?? '', 160);
        $documento = eg_safe_text($_POST['documento'] ?? '', 20);
        $concepto = eg_safe_text($_POST['concepto'] ?? '', 1000);
        $observaciones = eg_safe_text($_POST['observaciones'] ?? '', 255);
        $fuentesRaw = $_POST['fuentes_json'] ?? ($_POST['fuentes'] ?? '');

        if ($tipo === 'FACTURA' || $tipo === 'BOLETA') {
            if ($serie === null || $numero === null) {
                eg_json_err('Para factura y boleta, serie y numero son obligatorios.');
            }
        } else {
            $serie = null;
            $numero = null;
        }

        if ($fecha === null) {
            eg_json_err('Fecha de egreso invalida.');
        }
        if ($monto <= 0) {
            eg_json_err('El monto debe ser mayor a cero.');
        }
        if ($concepto === null) {
            eg_json_err('El concepto del egreso es obligatorio.');
        }

        $parsedFuentes = eg_parse_fuentes_payload($fuentesRaw);
        if ($parsedFuentes['error'] !== '') {
            eg_json_err((string)$parsedFuentes['error']);
        }
        $fuentesMap = $parsedFuentes['items'] ?? [];
        if (!is_array($fuentesMap) || count($fuentesMap) === 0) {
            eg_json_err('La distribucion por fuente es obligatoria. Selecciona al menos una fuente.');
        }

        $totalFuentes = 0.0;
        foreach ($fuentesMap as $k => $m) {
            $key = fin_source_key_from_input((string)$k);
            if ($key === '') {
                eg_json_err('Hay una fuente invalida en la distribucion.');
            }
            $montoFuente = round((float)$m, 2);
            if ($montoFuente <= 0) {
                eg_json_err('Cada fuente asignada debe tener monto mayor a cero.');
            }
            $fuentesMap[$key] = $montoFuente;
            if ($key !== (string)$k) {
                unset($fuentesMap[$k]);
            }
            $totalFuentes += $montoFuente;
        }
        if (abs(round($totalFuentes, 2) - $monto) > 0.009) {
            eg_json_err('La suma de fuentes no coincide con el monto del egreso.');
        }

        $db->begin_transaction();
        try {
            $sqlCaja = "SELECT
                          cd.id AS diaria_id,
                          cd.codigo AS diaria_codigo,
                          cd.fecha AS diaria_fecha,
                          cd.id_caja_mensual AS mensual_id,
                          cm.codigo AS mensual_codigo,
                          cm.estado AS mensual_estado
                        FROM mod_caja_diaria cd
                        INNER JOIN mod_caja_mensual cm ON cm.id = cd.id_caja_mensual
                        WHERE cd.id_empresa=? AND cd.estado='abierta'
                        ORDER BY cd.fecha ASC, cd.id ASC
                        LIMIT 1
                        FOR UPDATE";
            $st = $db->prepare($sqlCaja);
            $st->bind_param('i', $empId);
            $st->execute();
            $caja = $st->get_result()->fetch_assoc();
            $st->close();

            if (!$caja) {
                $db->rollback();
                eg_json_err_code(409, 'No hay caja diaria abierta. Abre la caja desde el modulo Caja.');
            }
            if ((string)$caja['mensual_estado'] !== 'abierta') {
                $db->rollback();
                eg_json_err_code(409, 'La caja mensual asociada no esta abierta.');
            }

            $cajaDiariaId = (int)$caja['diaria_id'];
            $cajaMensualId = (int)$caja['mensual_id'];

            $saldo = eg_saldo_diaria($db, $empId, $cajaDiariaId);
            if ($saldo['saldo_disponible'] + 0.0001 < $monto) {
                $db->rollback();
                eg_json_err_code(409, 'Saldo insuficiente para registrar el egreso en la caja diaria actual.', [
                    'saldo' => $saldo
                ]);
            }

            $fuentesDisponibles = eg_map_fuentes_by_key($saldo['por_medio'] ?? []);
            foreach ($fuentesMap as $key => $montoFuente) {
                $disp = round((float)($fuentesDisponibles[$key]['saldo_disponible'] ?? 0), 2);
                if ($disp + 0.0001 < $montoFuente) {
                    $db->rollback();
                    eg_json_err_code(409, 'El monto solicitado supera el disponible en la fuente ' . $key . '.', [
                        'saldo' => $saldo,
                        'fuente_error' => [
                            'key' => $key,
                            'monto_solicitado' => round((float)$montoFuente, 2),
                            'disponible' => round($disp, 2),
                        ],
                    ]);
                }
            }

            $catalogoFuentes = eg_catalogo_fuentes_medios($db);
            foreach (array_keys($fuentesMap) as $key) {
                if (!isset($catalogoFuentes[$key]) || (int)($catalogoFuentes[$key]['medio_id'] ?? 0) <= 0) {
                    $db->rollback();
                    eg_json_err_code(409, 'No existe configuracion de medio de pago para la fuente ' . $key . '.');
                }
            }

            $correlativo = eg_next_correlativo($db, $empId);
            $codigo = eg_codigo($empId, $correlativo);

            $sqlIns = "INSERT INTO egr_egresos(
                         id_empresa, id_caja_mensual, id_caja_diaria, codigo, correlativo,
                         tipo_comprobante, serie, numero, referencia, fecha_emision, monto,
                         beneficiario, documento, concepto, observaciones, estado, creado_por
                       ) VALUES (
                         ?, ?, ?, ?, ?,
                         ?, ?, ?, ?, ?, ?,
                         ?, ?, ?, ?, 'ACTIVO', ?
                       )";
            $ins = $db->prepare($sqlIns);
            $ins->bind_param(
                'iiisisssssdssssi',
                $empId,
                $cajaMensualId,
                $cajaDiariaId,
                $codigo,
                $correlativo,
                $tipo,
                $serie,
                $numero,
                $referencia,
                $fecha,
                $monto,
                $beneficiario,
                $documento,
                $concepto,
                $observaciones,
                $uid
            );
            $ins->execute();
            $egresoId = (int)$db->insert_id;
            $ins->close();

            $insF = $db->prepare("INSERT INTO egr_egreso_fuentes(
                                    id_egreso, id_empresa, id_caja_diaria, fuente_key, medio_id, monto
                                  ) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($fuentesMap as $key => $montoFuente) {
                $medioId = (int)$catalogoFuentes[$key]['medio_id'];
                $montoRow = round((float)$montoFuente, 2);
                $insF->bind_param(
                    'iiisid',
                    $egresoId,
                    $empId,
                    $cajaDiariaId,
                    $key,
                    $medioId,
                    $montoRow
                );
                $insF->execute();
            }
            $insF->close();

            $db->commit();

            $row = eg_select_one($db, $empId, $egresoId);
            $fuentes = eg_select_fuentes($db, $empId, $egresoId);
            if ($row) {
                $row['fuentes'] = $fuentes;
            }
            eg_json_ok([
                'msg' => 'Egreso registrado correctamente.',
                'id' => $egresoId,
                'codigo' => $codigo,
                'row' => $row,
                'fuentes' => $fuentes,
            ]);
        } catch (Throwable $e) {
            $db->rollback();
            $ref = eg_log_exception('egresos.crear', $e, [
                'accion' => $accion,
                'empresa_id' => $empId,
                'usuario_id' => $uid,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'post_keys' => array_keys($_POST ?? [])
            ]);
            eg_json_err_code(500, 'No se pudo registrar el egreso.', ['error_ref' => $ref]);
        }
    }

    if ($accion === 'anular') {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            eg_json_err('Metodo no permitido.');
        }

        $id = (int)($_POST['id'] ?? 0);
        $motivo = eg_safe_text($_POST['motivo'] ?? '', 255);
        if ($id <= 0) {
            eg_json_err('ID de egreso invalido.');
        }

        $db->begin_transaction();
        try {
            $st = $db->prepare("SELECT id, codigo, estado FROM egr_egresos WHERE id_empresa=? AND id=? LIMIT 1 FOR UPDATE");
            $st->bind_param('ii', $empId, $id);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();

            if (!$row) {
                $db->rollback();
                eg_json_err_code(404, 'Egreso no encontrado.');
            }
            if ((string)$row['estado'] === 'ANULADO') {
                $db->rollback();
                eg_json_err_code(409, 'El egreso ya estaba anulado.');
            }

            $fuentes = eg_select_fuentes($db, $empId, $id);

            $up = $db->prepare("UPDATE egr_egresos
                                SET estado='ANULADO', anulado_por=?, anulado_en=NOW(), anulado_motivo=?, actualizado=NOW()
                                WHERE id_empresa=? AND id=? LIMIT 1");
            $up->bind_param('isii', $uid, $motivo, $empId, $id);
            $up->execute();
            $up->close();

            $db->commit();
            eg_json_ok([
                'msg' => 'Egreso anulado. Los montos fueron liberados en las mismas fuentes.',
                'id' => $id,
                'codigo' => (string)$row['codigo'],
                'fuentes' => $fuentes,
            ]);
        } catch (Throwable $e) {
            $db->rollback();
            $ref = eg_log_exception('egresos.anular', $e, [
                'accion' => $accion,
                'empresa_id' => $empId,
                'usuario_id' => $uid,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'egreso_id' => $id,
                'post_keys' => array_keys($_POST ?? [])
            ]);
            eg_json_err_code(500, 'No se pudo anular el egreso.', ['error_ref' => $ref]);
        }
    }

    if ($accion === 'egreso_pdf') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID de egreso invalido.';
            exit;
        }

        $eg = eg_select_one($db, $empId, $id);
        if (!$eg) {
            http_response_code(404);
            echo 'Egreso no encontrado.';
            exit;
        }
        $egFuentes = eg_select_fuentes($db, $empId, $id);

        require_once __DIR__ . '/../TCPDF/tcpdf.php';

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('MTC Pro');
        $pdf->SetAuthor('MTC Pro');
        $pdf->SetTitle('Recibo de egreso ' . $eg['codigo']);
        $pdf->SetMargins(3, 3, 3);
        $pdf->SetAutoPageBreak(true, 3);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();

        $logoAbs = eg_logo_abs_fs($eg['empresa_logo_path'] ?? '');
        $logoDataUri = '';
        if ($logoAbs && is_file($logoAbs)) {
            $logoBin = @file_get_contents($logoAbs);
            if ($logoBin !== false && $logoBin !== '') {
                $logoMime = 'image/png';
                if (function_exists('finfo_open')) {
                    $fi = @finfo_open(FILEINFO_MIME_TYPE);
                    if ($fi) {
                        $detected = @finfo_file($fi, $logoAbs);
                        if (is_string($detected) && strpos($detected, 'image/') === 0) {
                            $logoMime = $detected;
                        }
                        @finfo_close($fi);
                    }
                }
                $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoBin);
            }
        }

        $referenciaRaw = trim((string)($eg['referencia'] ?? ''));
        $isReciboInterno = ((string)$eg['tipo_comprobante'] === 'RECIBO')
            && ($referenciaRaw === '' || strtoupper($referenciaRaw) === 'INTERNO');

        $compText = ($eg['tipo_comprobante'] === 'RECIBO')
            ? ('RECIBO ' . ($referenciaRaw !== '' ? $referenciaRaw : 'INTERNO'))
            : ($eg['tipo_comprobante'] . ' ' . trim((string)$eg['serie'] . '-' . (string)$eg['numero'], '-'));

        $estadoTxt = ((string)$eg['estado'] === 'ANULADO') ? 'ANULADO' : 'EMITIDO';
        $montoTxt = number_format((float)$eg['monto'], 2, '.', ',');
        $empresa = trim((string)($eg['empresa_nombre'] ?? ''));
        $fechaTxt = eg_fmt_dt((string)$eg['fecha_emision']);
        $responsable = trim((string)($eg['creado_nombre'] ?? ''));
        if ($responsable === '') {
            $responsable = trim((string)($eg['creado_usuario'] ?? ''));
        }
        if ($responsable === '') {
            $responsable = 'Responsable';
        }

        $beneficiario = htmlspecialchars((string)($eg['beneficiario'] ?? ''));
        $documento = htmlspecialchars((string)($eg['documento'] ?? ''));
        $concepto = nl2br(htmlspecialchars((string)($eg['concepto'] ?? '')));
        $codigo = htmlspecialchars((string)$eg['codigo']);
        $compTextEsc = htmlspecialchars($compText);
        $estadoEsc = htmlspecialchars($estadoTxt);
        $empresaEsc = htmlspecialchars($empresa);
        $fechaEsc = htmlspecialchars($fechaTxt);
        $responsableEsc = htmlspecialchars($responsable);
        $montoEsc = htmlspecialchars($montoTxt);

        $logoHtml = ($logoDataUri !== '')
            ? '<img src="' . htmlspecialchars($logoDataUri, ENT_QUOTES, 'UTF-8') . '" class="logo-img" alt="Logo">'
            : '<div class="logo-empty">SIN LOGO</div>';

        $beneficiarioFirmaEsc = ($beneficiario !== '' ? $beneficiario : 'BENEFICIARIO');

        $fuentesRowsHtml = '';
        foreach ($egFuentes as $f) {
            $fLabel = htmlspecialchars((string)($f['label'] ?? $f['key'] ?? ''));
            $fMonto = htmlspecialchars(eg_fmt_money((float)($f['monto'] ?? 0)));
            $fuentesRowsHtml .= '<tr>
              <td style="padding:0.7mm 1mm;">' . $fLabel . '</td>
              <td style="padding:0.7mm 1mm; text-align:right; font-weight:bold;">' . $fMonto . '</td>
            </tr>';
        }
        if ($fuentesRowsHtml === '') {
            $fuentesRowsHtml = '<tr><td colspan="2" style="padding:0.7mm 1mm;">Sin distribucion registrada</td></tr>';
        }

        $fuentesSectionHtml = '
      <div class="space-2"></div>
      <div class="rule"></div>
      <div class="space-1"></div>
      <div class="lbl">Fuentes de salida</div>
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:9px;">
        <thead>
          <tr>
            <th align="left" style="padding:0.7mm 1mm; border-bottom:1px solid #b7b7b7;">Fuente</th>
            <th align="right" style="padding:0.7mm 1mm; border-bottom:1px solid #b7b7b7;">Monto</th>
          </tr>
        </thead>
        <tbody>' . $fuentesRowsHtml . '</tbody>
      </table>';

        $firmaResponsableHtml = '
          <div class="sig-wrap">
            <div class="sig-space"></div>
            <div class="sig-line"></div>
            <div class="sig-note">Espacio para firma</div>
            <div class="sig-name">' . $responsableEsc . '</div>
            <div class="sig-role">Responsable</div>
          </div>';

        if ($isReciboInterno) {
            $firmaSectionHtml = '
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="49%" style="padding-right:2mm;" valign="top">
            <div class="sig-wrap">
              <div class="sig-space"></div>
              <div class="sig-line"></div>
              <div class="sig-note">Espacio para firma</div>
              <div class="sig-name">' . $beneficiarioFirmaEsc . '</div>
              <div class="sig-role">Beneficiario</div>
            </div>
          </td>
          <td width="2%"></td>
          <td width="49%" style="padding-left:2mm;" valign="top">
            ' . $firmaResponsableHtml . '
          </td>
        </tr>
      </table>';
        } else {
            $firmaSectionHtml = '
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="51%"></td>
          <td width="49%" style="padding-left:2mm;" valign="top">
            ' . $firmaResponsableHtml . '
          </td>
        </tr>
      </table>';
        }

        $html = '
<style>
  * { font-family: dejavusans, sans-serif; color: #111; }
  .doc { font-size: 9.4px; }
  .head-wrap { border: 1px solid #cfcfcf; border-radius: 2mm; overflow: hidden; }
  .head-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
  .head-logo { text-align: center; vertical-align: middle; padding: 0.45mm; }
  .head-company { text-align: center; vertical-align: middle; padding: 0.7mm 1mm; }
  .head-money { text-align: center; vertical-align: middle; padding: 0.65mm 0.9mm; }
  .head-meta { text-align: center; vertical-align: middle; padding: 0.5mm 0.7mm; }
  .sep-r { border-right: 1px solid #cfcfcf; }
  .sep-t { border-top: 1px solid #cfcfcf; }
  .empresa { font-size: 14px; font-weight: bold; text-align: center; letter-spacing: 0.15px; line-height: 1.04; }
  .subtitulo { font-size: 8.3px; text-align: center; color: #4b5563; margin-top: 0.15mm; }
  .logo-wrap { width: 16mm; height: 16mm; text-align: center; vertical-align: middle; }
  .logo-img { width: 15.5mm; height: 15.5mm; }
  .logo-empty { font-size: 7.2px; color: #6b7280; text-align: center; }
  .monto-lbl { font-size: 7.5px; color: #374151; text-align: center; font-weight: bold; letter-spacing: 0.25px; }
  .monto-val { font-size: 16px; font-weight: bold; text-align: center; line-height: 1.0; }
  .codigo-top { font-size: 7.4px; color: #374151; text-align: center; margin-top: 0.15mm; }
  .meta-lbl { font-size: 7.1px; color: #4b5563; font-weight: bold; letter-spacing: 0.1px; text-transform: uppercase; }
  .meta-val { font-size: 9px; color: #111; line-height: 1.1; margin-top: 0.12mm; text-align: center; }
  .rule { border-top: 1px solid #8f8f8f; }
  .space-1 { height: 0.35mm; }
  .space-2 { height: 0.55mm; }
  .lbl { font-size: 7.7px; color: #4b5563; font-weight: bold; letter-spacing: 0.15px; text-transform: uppercase; }
  .val { font-size: 10px; color: #111; line-height: 1.1; margin-top: 0.12mm; }
  .val-strong { font-size: 10px; color: #111; line-height: 1.1; margin-top: 0.12mm; font-weight: bold; }
  .concepto { font-size: 10px; line-height: 1.16; min-height: 4.5mm; margin-top: 0.2mm; }
  .sig-wrap { width: 100%; }
  .sig-space { height: 4.2mm; }
  .sig-line { border-top: 1px solid #333; }
  .sig-note { font-size: 7.4px; color: #4b5563; text-align: center; margin-top: 0.3mm; }
  .sig-name { font-size: 9.8px; font-weight: bold; text-align: center; margin-top: 0.42mm; }
  .sig-role { font-size: 8.2px; color: #374151; text-align: center; margin-top: 0.15mm; }
</style>
<table class="doc" width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td>
      <div class="head-wrap">
        <table class="head-table" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td class="head-logo sep-r" rowspan="2" width="13%" valign="middle">
              <div class="logo-wrap">' . $logoHtml . '</div>
            </td>
            <td class="head-company sep-r" colspan="3" width="61%" valign="middle">
              <div class="empresa">' . $empresaEsc . '</div>
              <div class="subtitulo">RECIBO DE EGRESO</div>
            </td>
            <td class="head-money" rowspan="2" width="26%" valign="middle">
              <div class="monto-lbl">MONTO (S/)</div>
              <div class="monto-val">' . $montoEsc . '</div>
              <div class="codigo-top">Codigo: ' . $codigo . '</div>
            </td>
          </tr>
          <tr>
            <td class="head-meta sep-t sep-r" width="20%" valign="middle">
              <div class="meta-lbl">Fecha y hora</div>
              <div class="meta-val">' . $fechaEsc . '</div>
            </td>
            <td class="head-meta sep-t sep-r" width="24%" valign="middle">
              <div class="meta-lbl">Comprobante</div>
              <div class="meta-val">' . $compTextEsc . '</div>
            </td>
            <td class="head-meta sep-t sep-r" width="17%" valign="middle">
              <div class="meta-lbl">Estado</div>
              <div class="meta-val">' . $estadoEsc . '</div>
            </td>
          </tr>
        </table>
      </div>

      <div class="space-1"></div>
      <div class="rule"></div>
      <div class="space-1"></div>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="68%" style="padding-right:1.2mm;" valign="top">
            <div class="lbl">Beneficiario</div>
            <div class="val-strong">' . ($beneficiario !== '' ? $beneficiario : '---') . '</div>
          </td>
          <td width="32%" valign="top">
            <div class="lbl">Documento</div>
            <div class="val">' . ($documento !== '' ? $documento : '---') . '</div>
          </td>
        </tr>
      </table>

      <div class="space-2"></div>
      <div class="rule"></div>
      <div class="space-1"></div>

      <div class="lbl">Concepto</div>
      <div class="concepto">' . ($concepto !== '' ? $concepto : '---') . '</div>
      ' . $fuentesSectionHtml . '
      <div class="space-2"></div>
      <div class="rule"></div>
      <div class="space-1"></div>
      ' . $firmaSectionHtml . '
    </td>
  </tr>
</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('egreso_' . $eg['codigo'] . '.pdf', 'I');
        exit;
    }

    eg_json_err('Accion no reconocida.');
} catch (Throwable $e) {
    $ref = eg_log_exception('egresos.uncaught', $e, [
        'accion' => $accion,
        'empresa_id' => $empId,
        'usuario_id' => $uid,
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'get' => $_GET ?? [],
        'post_keys' => array_keys($_POST ?? [])
    ]);
    if ($isPdf) {
        http_response_code(500);
        echo 'Error no controlado. Ref: ' . $ref;
        exit;
    }
    eg_json_err_code(500, 'Error no controlado.', ['error_ref' => $ref]);
}
