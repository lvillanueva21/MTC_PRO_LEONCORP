<?php
// sistema/dashboard/administracion/funcion_caja_diaria_mensual.php
// Dashboard de caja: resumen simple, responsive y basado en saldo real.
// Disponible = Ingresos - Devoluciones - Egresos activos.

if (!function_exists('adm_caja_widget_h')) {
  function adm_caja_widget_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('adm_caja_widget_money')) {
  function adm_caja_widget_money($value): string {
    return 'S/ ' . number_format((float)$value, 2, '.', ',');
  }
}

if (!function_exists('adm_caja_widget_amount')) {
  function adm_caja_widget_amount($value): string {
    return number_format((float)$value, 2, '.', ',');
  }
}

if (!function_exists('adm_caja_widget_human_date')) {
  function adm_caja_widget_human_date($ymd): string {
    $raw = trim((string)$ymd);
    if ($raw === '') return 'Sin fecha';

    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if ($dt && $dt->format('Y-m-d') === $raw) {
      return $dt->format('d/m/Y');
    }

    return $raw;
  }
}

if (!function_exists('adm_caja_widget_period_label')) {
  function adm_caja_widget_period_label(int $anio, int $mes): string {
    if ($anio <= 0 || $mes <= 0) return 'Sin periodo';
    return str_pad((string)$mes, 2, '0', STR_PAD_LEFT) . '/' . $anio;
  }
}

if (!function_exists('adm_caja_widget_pick_diaria')) {
  function adm_caja_widget_pick_diaria(mysqli $db, int $empresaId, string $hoy): array {
    $out = [
      'id' => 0,
      'codigo' => 'Sin caja',
      'fecha' => '',
      'estado' => 'inexistente',
      'source' => 'none',
      'source_label' => 'SIN REGISTRO',
    ];

    $st = $db->prepare("
      SELECT id, codigo, fecha, estado
      FROM mod_caja_diaria
      WHERE id_empresa=? AND estado='abierta'
      ORDER BY fecha DESC, id DESC
      LIMIT 1
    ");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row) {
      $out['id'] = (int)$row['id'];
      $out['codigo'] = (string)($row['codigo'] ?? 'Sin caja');
      $out['fecha'] = (string)($row['fecha'] ?? '');
      $out['estado'] = (string)($row['estado'] ?? '');
      $out['source'] = 'open';
      $out['source_label'] = 'ABIERTA';
      return $out;
    }

    $st = $db->prepare("
      SELECT id, codigo, fecha, estado
      FROM mod_caja_diaria
      WHERE id_empresa=? AND fecha=?
      ORDER BY id DESC
      LIMIT 1
    ");
    $st->bind_param('is', $empresaId, $hoy);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row) {
      $out['id'] = (int)$row['id'];
      $out['codigo'] = (string)($row['codigo'] ?? 'Sin caja');
      $out['fecha'] = (string)($row['fecha'] ?? '');
      $out['estado'] = (string)($row['estado'] ?? '');
      $out['source'] = 'today';
      $out['source_label'] = 'HOY';
      return $out;
    }

    $st = $db->prepare("
      SELECT id, codigo, fecha, estado
      FROM mod_caja_diaria
      WHERE id_empresa=?
      ORDER BY fecha DESC, id DESC
      LIMIT 1
    ");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row) {
      $out['id'] = (int)$row['id'];
      $out['codigo'] = (string)($row['codigo'] ?? 'Sin caja');
      $out['fecha'] = (string)($row['fecha'] ?? '');
      $out['estado'] = (string)($row['estado'] ?? '');
      $out['source'] = 'latest';
      $out['source_label'] = 'ÚLTIMA';
    }

    return $out;
  }
}

if (!function_exists('adm_caja_widget_pick_mensual')) {
  function adm_caja_widget_pick_mensual(mysqli $db, int $empresaId, int $anio, int $mes): array {
    $out = [
      'id' => 0,
      'codigo' => 'Sin caja',
      'anio' => 0,
      'mes' => 0,
      'estado' => 'inexistente',
      'source' => 'none',
      'source_label' => 'SIN REGISTRO',
    ];

    $st = $db->prepare("
      SELECT id, codigo, anio, mes, estado
      FROM mod_caja_mensual
      WHERE id_empresa=? AND estado='abierta'
      ORDER BY anio DESC, mes DESC, id DESC
      LIMIT 1
    ");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row) {
      $out['id'] = (int)$row['id'];
      $out['codigo'] = (string)($row['codigo'] ?? 'Sin caja');
      $out['anio'] = (int)($row['anio'] ?? 0);
      $out['mes'] = (int)($row['mes'] ?? 0);
      $out['estado'] = (string)($row['estado'] ?? '');
      $out['source'] = 'open';
      $out['source_label'] = 'ABIERTA';
      return $out;
    }

    $st = $db->prepare("
      SELECT id, codigo, anio, mes, estado
      FROM mod_caja_mensual
      WHERE id_empresa=? AND anio=? AND mes=?
      ORDER BY id DESC
      LIMIT 1
    ");
    $st->bind_param('iii', $empresaId, $anio, $mes);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row) {
      $out['id'] = (int)$row['id'];
      $out['codigo'] = (string)($row['codigo'] ?? 'Sin caja');
      $out['anio'] = (int)($row['anio'] ?? 0);
      $out['mes'] = (int)($row['mes'] ?? 0);
      $out['estado'] = (string)($row['estado'] ?? '');
      $out['source'] = 'current';
      $out['source_label'] = 'ACTUAL';
      return $out;
    }

    $st = $db->prepare("
      SELECT id, codigo, anio, mes, estado
      FROM mod_caja_mensual
      WHERE id_empresa=?
      ORDER BY anio DESC, mes DESC, id DESC
      LIMIT 1
    ");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if ($row) {
      $out['id'] = (int)$row['id'];
      $out['codigo'] = (string)($row['codigo'] ?? 'Sin caja');
      $out['anio'] = (int)($row['anio'] ?? 0);
      $out['mes'] = (int)($row['mes'] ?? 0);
      $out['estado'] = (string)($row['estado'] ?? '');
      $out['source'] = 'latest';
      $out['source_label'] = 'ÚLTIMA';
    }

    return $out;
  }
}


if (!function_exists('adm_caja_widget_stats_diaria')) {
    function adm_caja_widget_stats_diaria(mysqli $db, int $empresaId, int $cajaDiariaId): array
    {
        if ($cajaDiariaId <= 0) {
            return [
                'ingresos' => 0.0,
                'devoluciones' => 0.0,
                'egresos' => 0.0,
                'disponible' => 0.0,
            ];
        }

        $sql = "
        SELECT
          (
            SELECT COALESCE(SUM(apl.monto_aplicado), 0)
            FROM pos_abonos a
            LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
            WHERE a.id_empresa=? AND a.caja_diaria_id=?
          ) AS ingresos,
          (
            SELECT COALESCE(SUM(dv.monto_devuelto), 0)
            FROM pos_devoluciones dv
            WHERE dv.id_empresa=? AND dv.caja_diaria_id=?
          ) AS devoluciones,
          (
            SELECT COALESCE(SUM(f.monto), 0)
            FROM egr_egreso_fuentes f
            INNER JOIN egr_egresos e
              ON e.id = f.id_egreso
             AND e.id_empresa = f.id_empresa
            WHERE f.id_empresa=? AND f.id_caja_diaria=? AND e.estado='ACTIVO'
          ) AS egresos_por_fuente,
          (
            SELECT COALESCE(SUM(e.monto), 0)
            FROM egr_egresos e
            LEFT JOIN egr_egreso_fuentes f
              ON f.id_egreso = e.id
             AND f.id_empresa = e.id_empresa
            WHERE e.id_empresa=? AND e.id_caja_diaria=? AND e.estado='ACTIVO' AND f.id IS NULL
          ) AS egresos_no_distribuidos
        ";

        $st = $db->prepare($sql);
        $st->bind_param(
            'iiiiiiii',
            $empresaId, $cajaDiariaId,
            $empresaId, $cajaDiariaId,
            $empresaId, $cajaDiariaId,
            $empresaId, $cajaDiariaId
        );
        $st->execute();
        $row = $st->get_result()->fetch_assoc() ?: [];
        $st->close();

        $ingresos = round((float)($row['ingresos'] ?? 0), 2);
        $devoluciones = round((float)($row['devoluciones'] ?? 0), 2);
        $egresos = round(
            (float)($row['egresos_por_fuente'] ?? 0) + (float)($row['egresos_no_distribuidos'] ?? 0),
            2
        );

        return [
            'ingresos' => $ingresos,
            'devoluciones' => $devoluciones,
            'egresos' => $egresos,
            'disponible' => round($ingresos - $devoluciones - $egresos, 2),
        ];
    }
}

if (!function_exists('adm_caja_widget_stats_mensual')) {
    function adm_caja_widget_stats_mensual(mysqli $db, int $empresaId, int $cajaMensualId): array
    {
        if ($cajaMensualId <= 0) {
            return [
                'ingresos' => 0.0,
                'devoluciones' => 0.0,
                'egresos' => 0.0,
                'disponible' => 0.0,
            ];
        }

        $sql = "
        SELECT
          (
            SELECT COALESCE(SUM(apl.monto_aplicado), 0)
            FROM pos_abonos a
            INNER JOIN mod_caja_diaria cd ON cd.id = a.caja_diaria_id
            LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
            WHERE a.id_empresa=? AND cd.id_caja_mensual=?
          ) AS ingresos,
          (
            SELECT COALESCE(SUM(dv.monto_devuelto), 0)
            FROM pos_devoluciones dv
            INNER JOIN mod_caja_diaria cd ON cd.id = dv.caja_diaria_id
            WHERE dv.id_empresa=? AND cd.id_caja_mensual=?
          ) AS devoluciones,
          (
            SELECT COALESCE(SUM(f.monto), 0)
            FROM egr_egreso_fuentes f
            INNER JOIN egr_egresos e
              ON e.id = f.id_egreso
             AND e.id_empresa = f.id_empresa
            INNER JOIN mod_caja_diaria cd
              ON cd.id = f.id_caja_diaria
            WHERE f.id_empresa=? AND cd.id_caja_mensual=? AND e.estado='ACTIVO'
          ) AS egresos_por_fuente,
          (
            SELECT COALESCE(SUM(e.monto), 0)
            FROM egr_egresos e
            LEFT JOIN egr_egreso_fuentes f
              ON f.id_egreso = e.id
             AND f.id_empresa = e.id_empresa
            WHERE e.id_empresa=? AND e.id_caja_mensual=? AND e.estado='ACTIVO' AND f.id IS NULL
          ) AS egresos_no_distribuidos
        ";

        $st = $db->prepare($sql);
        $st->bind_param(
            'iiiiiiii',
            $empresaId, $cajaMensualId,
            $empresaId, $cajaMensualId,
            $empresaId, $cajaMensualId,
            $empresaId, $cajaMensualId
        );
        $st->execute();
        $row = $st->get_result()->fetch_assoc() ?: [];
        $st->close();

        $ingresos = round((float)($row['ingresos'] ?? 0), 2);
        $devoluciones = round((float)($row['devoluciones'] ?? 0), 2);
        $egresos = round(
            (float)($row['egresos_por_fuente'] ?? 0) + (float)($row['egresos_no_distribuidos'] ?? 0),
            2
        );

        return [
            'ingresos' => $ingresos,
            'devoluciones' => $devoluciones,
            'egresos' => $egresos,
            'disponible' => round($ingresos - $devoluciones - $egresos, 2),
        ];
    }
}

$admCajaDb = (isset($mysqli) && $mysqli instanceof mysqli) ? $mysqli : db();
$admCajaEmpresaId = isset($empresaId) ? (int)$empresaId : (int)(currentUser()['empresa']['id'] ?? 0);
$admCajaError = '';

$admCajaDiaria = [
  'id' => 0,
  'codigo' => 'Sin caja',
  'fecha' => '',
  'estado' => 'inexistente',
  'source' => 'none',
  'source_label' => 'SIN REGISTRO',
];

$admCajaMensual = [
  'id' => 0,
  'codigo' => 'Sin caja',
  'anio' => 0,
  'mes' => 0,
  'estado' => 'inexistente',
  'source' => 'none',
  'source_label' => 'SIN REGISTRO',
];

$admCajaTotDiaria = [
  'ingresos' => 0.0,
  'devoluciones' => 0.0,
  'egresos' => 0.0,
  'disponible' => 0.0,
];

$admCajaTotMensual = [
  'ingresos' => 0.0,
  'devoluciones' => 0.0,
  'egresos' => 0.0,
  'disponible' => 0.0,
];

if ($admCajaEmpresaId > 0) {
  try {
    $admCajaDb->set_charset('utf8mb4');

    if (function_exists('date_default_timezone_set')) {
      date_default_timezone_set('America/Lima');
    }

    try {
      $admCajaDb->query("SET time_zone = 'America/Lima'");
    } catch (Throwable $e) {
      $admCajaDb->query("SET time_zone = '-05:00'");
    }

    $rsNow = $admCajaDb->query("SELECT CURDATE() AS hoy, YEAR(CURDATE()) AS anio, MONTH(CURDATE()) AS mes");
    $now = $rsNow ? ($rsNow->fetch_assoc() ?: []) : [];

    $hoy = (string)($now['hoy'] ?? date('Y-m-d'));
    $anio = (int)($now['anio'] ?? date('Y'));
    $mes = (int)($now['mes'] ?? date('m'));

    $admCajaDiaria = adm_caja_widget_pick_diaria($admCajaDb, $admCajaEmpresaId, $hoy);
    $admCajaMensual = adm_caja_widget_pick_mensual($admCajaDb, $admCajaEmpresaId, $anio, $mes);

    $admCajaTotDiaria = adm_caja_widget_stats_diaria($admCajaDb, $admCajaEmpresaId, (int)$admCajaDiaria['id']);
    $admCajaTotMensual = adm_caja_widget_stats_mensual($admCajaDb, $admCajaEmpresaId, (int)$admCajaMensual['id']);
  } catch (Throwable $e) {
    $admCajaError = 'No se pudo consultar las cajas por el momento.';
  }
} else {
  $admCajaError = 'Tu usuario no tiene empresa asignada.';
}

$admCajaNoBoxes = ((int)$admCajaDiaria['id'] <= 0) && ((int)$admCajaMensual['id'] <= 0);
$admCajaMensualPeriodo = adm_caja_widget_period_label((int)$admCajaMensual['anio'], (int)$admCajaMensual['mes']);

$admCajaDiariaBadgeClass = 'admcaja-badge-none';
if ($admCajaDiaria['source'] === 'open') $admCajaDiariaBadgeClass = 'admcaja-badge-open';
if ($admCajaDiaria['source'] === 'today') $admCajaDiariaBadgeClass = 'admcaja-badge-info';
if ($admCajaDiaria['source'] === 'latest') $admCajaDiariaBadgeClass = 'admcaja-badge-muted';

$admCajaMensualBadgeClass = 'admcaja-badge-none';
if ($admCajaMensual['source'] === 'open') $admCajaMensualBadgeClass = 'admcaja-badge-open';
if ($admCajaMensual['source'] === 'current') $admCajaMensualBadgeClass = 'admcaja-badge-info';
if ($admCajaMensual['source'] === 'latest') $admCajaMensualBadgeClass = 'admcaja-badge-muted';
?>

<style>
  .admcaja-shell {
    border: 0;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(17, 45, 74, .10);
    container-type: inline-size;
  }

  .admcaja-wrap {
    --admcaja-blue: #173f73;
    --admcaja-blue-soft: #4e677f;
    --admcaja-green: #069b63;
    --admcaja-green-soft: #cfe8d9;
    --admcaja-info-soft: #dbe8f6;
    --admcaja-info: #236cbc;
    --admcaja-muted-soft: rgba(75, 96, 115, .12);
    --admcaja-muted: #61798d;
    --admcaja-danger: #b42318;
    padding: 26px;
    border-radius: 28px;
    background: linear-gradient(118deg, #eee7b6 0%, #d8edc5 52%, #c7ebf4 100%);
  }

  .admcaja-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 22px;
  }

  .admcaja-kicker {
    margin: 0 0 10px;
    font-size: 12px;
    line-height: 1;
    letter-spacing: .16em;
    font-weight: 900;
    color: #0f8977;
  }

  .admcaja-title {
    margin: 0;
    font-size: 2.05rem;
    line-height: 1.02;
    font-weight: 900;
    color: var(--admcaja-blue);
  }

  .admcaja-coin {
    width: 74px;
    height: 74px;
    border-radius: 50%;
    background: #f2d78b;
    color: #0d5e92;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 900;
    flex: 0 0 auto;
    box-shadow: 0 10px 22px rgba(18, 45, 72, .10);
  }

  .admcaja-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));
    gap: 20px;
    align-items: stretch;
  }

  .admcaja-box {
    min-width: 0;
    padding: 24px 20px 20px;
    border-radius: 28px;
    border: 1px solid rgba(82, 116, 146, .20);
    background: rgba(241, 245, 249, .78);
    backdrop-filter: blur(4px);
    overflow: hidden;
  }

  .admcaja-box-month {
    background: rgba(234, 241, 233, .82);
  }

  .admcaja-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
  }

  .admcaja-box-title {
    margin: 0;
    font-size: 1.2rem;
    line-height: 1.15;
    font-weight: 900;
    color: #25445e;
  }

  .admcaja-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0 16px;
    border-radius: 999px;
    font-size: .86rem;
    font-weight: 900;
    letter-spacing: .04em;
    white-space: nowrap;
    flex: 0 0 auto;
  }

  .admcaja-badge-open {
    background: var(--admcaja-green-soft);
    color: var(--admcaja-green);
  }

  .admcaja-badge-info {
    background: var(--admcaja-info-soft);
    color: var(--admcaja-info);
  }

  .admcaja-badge-muted,
  .admcaja-badge-none {
    background: var(--admcaja-muted-soft);
    color: var(--admcaja-muted);
  }

  .admcaja-code {
    margin: 0 0 8px;
    font-size: 1.2rem;
    line-height: 1.22;
    font-weight: 900;
    color: var(--admcaja-blue);
    overflow-wrap: anywhere;
  }

  .admcaja-meta {
    margin: 0 0 22px;
    font-size: 1rem;
    line-height: 1.2;
    color: var(--admcaja-blue-soft);
  }

  .admcaja-label {
    margin: 0 0 6px;
    font-size: .96rem;
    line-height: 1.1;
    font-weight: 900;
  }

  .admcaja-label-main {
    color: var(--admcaja-green);
  }

  .admcaja-label-ing {
    color: #246dcb;
  }

  .admcaja-label-out,
  .admcaja-label-dev {
    color: var(--admcaja-green);
  }

  .admcaja-total-row {
    display: flex;
    align-items: baseline;
    gap: 10px;
    min-width: 0;
    margin: 0 0 22px;
  }

  .admcaja-total-currency {
    flex: 0 0 auto;
    font-size: 2.6rem;
    line-height: 1;
    font-weight: 900;
    color: var(--admcaja-blue);
    font-variant-numeric: tabular-nums;
  }

  .admcaja-total {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 3.2rem;
    line-height: .95;
    font-weight: 900;
    color: var(--admcaja-blue);
    letter-spacing: -.03em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: clip;
    font-variant-numeric: tabular-nums;
  }

  .admcaja-total.is-negative,
  .admcaja-total-currency.is-negative {
    color: var(--admcaja-danger);
  }

  .admcaja-ingresos {
    margin: 0 0 20px;
    font-size: 1.15rem;
    line-height: 1.15;
    color: var(--admcaja-blue);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
  }

  .admcaja-bottom {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .admcaja-mini {
    margin: 0;
    font-size: 1.1rem;
    line-height: 1.15;
    color: var(--admcaja-blue);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
  }

  .admcaja-alert {
    padding: 14px 16px;
    border-radius: 16px;
    background: rgba(255,255,255,.56);
    color: #435b70;
    font-size: 1rem;
    font-weight: 700;
  }

  @container (max-width: 980px) {
    .admcaja-wrap {
      padding: 22px;
    }

    .admcaja-title {
      font-size: 1.8rem;
    }

    .admcaja-grid {
      gap: 16px;
    }

    .admcaja-box {
      padding: 22px 18px 18px;
    }

    .admcaja-total-currency {
      font-size: 2.3rem;
    }

    .admcaja-total {
      font-size: 2.75rem;
    }
  }

  @container (max-width: 760px) {
    .admcaja-head {
      margin-bottom: 18px;
    }

    .admcaja-grid {
      grid-template-columns: 1fr;
    }

    .admcaja-total-currency {
      font-size: 2.1rem;
    }

    .admcaja-total {
      font-size: 2.55rem;
    }
  }

  @container (max-width: 520px) {
    .admcaja-wrap {
      padding: 16px;
      border-radius: 22px;
    }

    .admcaja-head {
      gap: 12px;
      margin-bottom: 16px;
    }

    .admcaja-title {
      font-size: 1.6rem;
    }

    .admcaja-coin {
      width: 58px;
      height: 58px;
      font-size: 1.7rem;
    }

    .admcaja-box {
      padding: 18px 14px 16px;
      border-radius: 22px;
    }

    .admcaja-top {
      align-items: flex-start;
    }

    .admcaja-badge {
      min-height: 30px;
      padding: 0 12px;
      font-size: .78rem;
    }

    .admcaja-code {
      font-size: 1.08rem;
    }

    .admcaja-meta {
      font-size: .95rem;
      margin-bottom: 18px;
    }

    .admcaja-total-row {
      gap: 8px;
      margin-bottom: 18px;
    }

    .admcaja-total-currency {
      font-size: 1.9rem;
    }

    .admcaja-total {
      font-size: 2.2rem;
      white-space: normal;
      line-height: 1;
      overflow: visible;
    }

    .admcaja-ingresos,
    .admcaja-mini {
      font-size: 1rem;
    }

    .admcaja-bottom {
      gap: 14px 12px;
    }
  }

  @media (max-width: 575.98px) {
    .admcaja-bottom {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
</style>

<div class="card admcaja-shell">
  <div class="card-body admcaja-wrap">
    <div class="admcaja-head">
      <div>
        <div class="admcaja-kicker">DASHBOARD DE CAJA</div>
        <h6 class="admcaja-title">Mi Caja</h6>
      </div>
      <div class="admcaja-coin" aria-hidden="true">S/</div>
    </div>

    <?php if ($admCajaError !== ''): ?>
      <div class="admcaja-alert"><?= adm_caja_widget_h($admCajaError) ?></div>
    <?php elseif ($admCajaNoBoxes): ?>
      <div class="admcaja-alert">Aún no existen cajas para esta empresa.</div>
    <?php else: ?>
      <div class="admcaja-grid">
        <article class="admcaja-box admcaja-box-day">
          <div class="admcaja-top">
            <div class="admcaja-box-title">Caja diaria</div>
            <span class="admcaja-badge <?= adm_caja_widget_h($admCajaDiariaBadgeClass) ?>">
              <?= adm_caja_widget_h($admCajaDiaria['source_label']) ?>
            </span>
          </div>

          <div class="admcaja-code"><?= adm_caja_widget_h($admCajaDiaria['codigo']) ?></div>
          <div class="admcaja-meta"><?= adm_caja_widget_h(adm_caja_widget_human_date($admCajaDiaria['fecha'])) ?></div>

          <div class="admcaja-label admcaja-label-main">Disponible</div>
          <div class="admcaja-total-row">
            <span class="admcaja-total-currency <?= ((float)$admCajaTotDiaria['disponible'] < 0) ? 'is-negative' : '' ?>">S/</span>
            <div class="admcaja-total <?= ((float)$admCajaTotDiaria['disponible'] < 0) ? 'is-negative' : '' ?>">
              <?= adm_caja_widget_h(adm_caja_widget_amount($admCajaTotDiaria['disponible'])) ?>
            </div>
          </div>

          <div class="admcaja-label admcaja-label-ing">Ingresado</div>
          <div class="admcaja-ingresos"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotDiaria['ingresos'])) ?></div>

          <div class="admcaja-bottom">
            <div>
              <div class="admcaja-label admcaja-label-out">Egresado</div>
              <div class="admcaja-mini"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotDiaria['egresos'])) ?></div>
            </div>
            <div>
              <div class="admcaja-label admcaja-label-dev">Devuelto</div>
              <div class="admcaja-mini"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotDiaria['devoluciones'])) ?></div>
            </div>
          </div>
        </article>

        <article class="admcaja-box admcaja-box-month">
          <div class="admcaja-top">
            <div class="admcaja-box-title">Caja mensual</div>
            <span class="admcaja-badge <?= adm_caja_widget_h($admCajaMensualBadgeClass) ?>">
              <?= adm_caja_widget_h($admCajaMensual['source_label']) ?>
            </span>
          </div>

          <div class="admcaja-code"><?= adm_caja_widget_h($admCajaMensual['codigo']) ?></div>
          <div class="admcaja-meta"><?= adm_caja_widget_h($admCajaMensualPeriodo) ?></div>

          <div class="admcaja-label admcaja-label-main">Disponible</div>
          <div class="admcaja-total-row">
            <span class="admcaja-total-currency <?= ((float)$admCajaTotMensual['disponible'] < 0) ? 'is-negative' : '' ?>">S/</span>
            <div class="admcaja-total <?= ((float)$admCajaTotMensual['disponible'] < 0) ? 'is-negative' : '' ?>">
              <?= adm_caja_widget_h(adm_caja_widget_amount($admCajaTotMensual['disponible'])) ?>
            </div>
          </div>

          <div class="admcaja-label admcaja-label-ing">Ingresado</div>
          <div class="admcaja-ingresos"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotMensual['ingresos'])) ?></div>

          <div class="admcaja-bottom">
            <div>
              <div class="admcaja-label admcaja-label-out">Egresado</div>
              <div class="admcaja-mini"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotMensual['egresos'])) ?></div>
            </div>
            <div>
              <div class="admcaja-label admcaja-label-dev">Devuelto</div>
              <div class="admcaja-mini"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotMensual['devoluciones'])) ?></div>
            </div>
          </div>
        </article>
      </div>
    <?php endif; ?>
  </div>
</div>