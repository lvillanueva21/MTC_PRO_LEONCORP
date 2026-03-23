<?php

// dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php
// Card: ganancia neta real (ingresos - devoluciones - egresos activos) de la ultima caja diaria.

if (!function_exists('adm_ugn_h')) {
    function adm_ugn_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('adm_ugn_money')) {
    function adm_ugn_money($value): string
    {
        return 'S/ ' . number_format((float)$value, 2, '.', ',');
    }
}

if (!function_exists('adm_ugn_ymd_to_dmy')) {
    function adm_ugn_ymd_to_dmy(string $ymd): string
    {
        $raw = trim($ymd);
        if ($raw === '') return '';
        $dt = DateTime::createFromFormat('Y-m-d', $raw);
        return ($dt && $dt->format('Y-m-d') === $raw) ? $dt->format('d/m/Y') : $raw;
    }
}

if (!function_exists('adm_ugn_table_exists')) {
    function adm_ugn_table_exists(mysqli $db, string $name): bool
    {
        $name = $db->real_escape_string($name);
        $rs = $db->query("SHOW TABLES LIKE '{$name}'");
        return $rs && $rs->num_rows > 0;
    }
}

$admUgnDb = (isset($mysqli) && $mysqli instanceof mysqli) ? $mysqli : db();
$admUgnEmpresaId = isset($empresaId) ? (int)$empresaId : (int)(currentUser()['empresa']['id'] ?? 0);

$admUgn = [
    'ok' => false,
    'msg' => '',
    'caja_codigo' => '',
    'caja_fecha' => '',
    'ingresos' => 0.0,
    'devoluciones' => 0.0,
    'egresos' => 0.0,
    'neto' => 0.0,
];

$admUgnHasSchema =
    $admUgnEmpresaId > 0 &&
    adm_ugn_table_exists($admUgnDb, 'mod_caja_diaria') &&
    adm_ugn_table_exists($admUgnDb, 'pos_abonos') &&
    adm_ugn_table_exists($admUgnDb, 'pos_abono_aplicaciones') &&
    adm_ugn_table_exists($admUgnDb, 'egr_egresos');

if ($admUgnHasSchema) {
    try {
        $sqlCaja = "SELECT id, codigo, fecha
                    FROM mod_caja_diaria
                    WHERE id_empresa=?
                    ORDER BY fecha DESC, id DESC
                    LIMIT 1";
        $stCaja = $admUgnDb->prepare($sqlCaja);
        $stCaja->bind_param('i', $admUgnEmpresaId);
        $stCaja->execute();
        $caja = $stCaja->get_result()->fetch_assoc();
        $stCaja->close();

        if ($caja) {
            $cajaId = (int)$caja['id'];
            $admUgn['caja_codigo'] = (string)($caja['codigo'] ?? 'Sin caja');
            $admUgn['caja_fecha'] = (string)($caja['fecha'] ?? '');

            $sqlIng = "SELECT COALESCE(SUM(apl.monto_aplicado),0) AS total
                       FROM pos_abonos a
                       LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
                       WHERE a.id_empresa=? AND a.caja_diaria_id=?";
            $stIng = $admUgnDb->prepare($sqlIng);
            $stIng->bind_param('ii', $admUgnEmpresaId, $cajaId);
            $stIng->execute();
            $rowIng = $stIng->get_result()->fetch_assoc() ?: [];
            $stIng->close();

            $devTotal = 0.0;
            if (adm_ugn_table_exists($admUgnDb, 'pos_devoluciones')) {
                $sqlDev = "SELECT COALESCE(SUM(dv.monto_devuelto),0) AS total
                           FROM pos_devoluciones dv
                           WHERE dv.id_empresa=? AND dv.caja_diaria_id=?";
                $stDev = $admUgnDb->prepare($sqlDev);
                $stDev->bind_param('ii', $admUgnEmpresaId, $cajaId);
                $stDev->execute();
                $rowDev = $stDev->get_result()->fetch_assoc() ?: [];
                $stDev->close();
                $devTotal = (float)($rowDev['total'] ?? 0);
            }

            if (adm_ugn_table_exists($admUgnDb, 'egr_egreso_fuentes')) {
                $sqlEgr = "SELECT
                             (
                               SELECT COALESCE(SUM(f.monto),0)
                               FROM egr_egreso_fuentes f
                               INNER JOIN egr_egresos e
                                 ON e.id = f.id_egreso
                                AND e.id_empresa = f.id_empresa
                               WHERE f.id_empresa=? AND f.id_caja_diaria=? AND e.estado='ACTIVO'
                             ) AS egresos_fuente,
                             (
                               SELECT COALESCE(SUM(e.monto),0)
                               FROM egr_egresos e
                               LEFT JOIN egr_egreso_fuentes f
                                 ON f.id_egreso = e.id
                                AND f.id_empresa = e.id_empresa
                               WHERE e.id_empresa=? AND e.id_caja_diaria=? AND e.estado='ACTIVO' AND f.id IS NULL
                             ) AS egresos_no_distribuidos";
                $stEgr = $admUgnDb->prepare($sqlEgr);
                $stEgr->bind_param('iiii', $admUgnEmpresaId, $cajaId, $admUgnEmpresaId, $cajaId);
                $stEgr->execute();
                $rowEgr = $stEgr->get_result()->fetch_assoc() ?: [];
                $stEgr->close();

                $admUgn['egresos'] = (float)($rowEgr['egresos_fuente'] ?? 0) + (float)($rowEgr['egresos_no_distribuidos'] ?? 0);
            } else {
                $sqlEgr = "SELECT COALESCE(SUM(CASE WHEN estado='ACTIVO' THEN monto ELSE 0 END),0) AS total
                           FROM egr_egresos
                           WHERE id_empresa=? AND id_caja_diaria=?";
                $stEgr = $admUgnDb->prepare($sqlEgr);
                $stEgr->bind_param('ii', $admUgnEmpresaId, $cajaId);
                $stEgr->execute();
                $rowEgr = $stEgr->get_result()->fetch_assoc() ?: [];
                $stEgr->close();
                $admUgn['egresos'] = (float)($rowEgr['total'] ?? 0);
            }

            $admUgn['ingresos'] = (float)($rowIng['total'] ?? 0);
            $admUgn['devoluciones'] = $devTotal;
            $admUgn['neto'] = round($admUgn['ingresos'] - $admUgn['devoluciones'] - $admUgn['egresos'], 2);
            $admUgn['ok'] = true;
        } else {
            $admUgn['msg'] = 'No existen cajas diarias registradas.';
        }
    } catch (Throwable $e) {
        $admUgn['msg'] = 'No se pudo cargar ganancia neta de la ultima caja.';
    }
} else {
    $admUgn['msg'] = 'Faltan tablas para calcular ganancia neta.';
}

$admUgnStateClass = 'adm-kpi-net-neutral';
if ($admUgn['ok']) {
    if ($admUgn['neto'] > 0) {
        $admUgnStateClass = 'adm-kpi-net-positive';
    } elseif ($admUgn['neto'] < 0) {
        $admUgnStateClass = 'adm-kpi-net-negative';
    }
}
?>

<div class="card kpi-card adm-kpi-mini adm-kpi-net">
  <div class="card-body">
    <div class="adm-kpi-mini-head">
      <h6 class="card-title mb-0">Ganancia neta</h6>
      <span class="adm-kpi-mini-badge">Ult. caja</span>
    </div>

    <?php if ($admUgn['ok']): ?>
      <div class="adm-kpi-mini-value <?= adm_ugn_h($admUgnStateClass) ?>">
        <?= adm_ugn_h(adm_ugn_money($admUgn['neto'])) ?>
      </div>

      <div class="adm-kpi-mini-meta">
        Caja: <?= adm_ugn_h($admUgn['caja_codigo']) ?> · Fecha: <?= adm_ugn_h(adm_ugn_ymd_to_dmy($admUgn['caja_fecha'])) ?>
      </div>

      <div class="adm-kpi-mini-note">
        Ingresos: <?= adm_ugn_h(adm_ugn_money($admUgn['ingresos'])) ?>
        · Devol.: <?= adm_ugn_h(adm_ugn_money($admUgn['devoluciones'])) ?>
        · Egresos: <?= adm_ugn_h(adm_ugn_money($admUgn['egresos'])) ?>
      </div>
    <?php else: ?>
      <div class="adm-kpi-mini-empty"><?= adm_ugn_h($admUgn['msg']) ?></div>
    <?php endif; ?>
  </div>
</div>