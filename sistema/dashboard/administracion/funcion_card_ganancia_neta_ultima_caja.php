<?php
// dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php
// Card: ganancia neta (ingresos - egresos activos) de la ultima caja diaria.

if (!function_exists('adm_ugn_h')) {
  function adm_ugn_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('adm_ugn_money')) {
  function adm_ugn_money($value): string {
    return 'S/ ' . number_format((float)$value, 2, '.', ',');
  }
}

if (!function_exists('adm_ugn_ymd_to_dmy')) {
  function adm_ugn_ymd_to_dmy(string $ymd): string {
    $raw = trim($ymd);
    if ($raw === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if ($dt && $dt->format('Y-m-d') === $raw) {
      return $dt->format('d/m/Y');
    }
    return $raw;
  }
}

if (!function_exists('adm_ugn_table_exists')) {
  function adm_ugn_table_exists(mysqli $db, string $name): bool {
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
  'caja_codigo' => 'Sin caja',
  'caja_fecha' => '',
  'ingresos' => 0.0,
  'egresos' => 0.0,
  'neto' => 0.0,
];

if ($admUgnEmpresaId > 0
  && adm_ugn_table_exists($admUgnDb, 'mod_caja_diaria')
  && adm_ugn_table_exists($admUgnDb, 'pos_abonos')
  && adm_ugn_table_exists($admUgnDb, 'pos_abono_aplicaciones')
  && adm_ugn_table_exists($admUgnDb, 'egr_egresos')
) {
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

      $sqlEgr = "SELECT COALESCE(SUM(CASE WHEN estado='ACTIVO' THEN monto ELSE 0 END),0) AS total
                 FROM egr_egresos
                 WHERE id_empresa=? AND id_caja_diaria=?";
      $stEgr = $admUgnDb->prepare($sqlEgr);
      $stEgr->bind_param('ii', $admUgnEmpresaId, $cajaId);
      $stEgr->execute();
      $rowEgr = $stEgr->get_result()->fetch_assoc() ?: [];
      $stEgr->close();

      $admUgn['ingresos'] = (float)($rowIng['total'] ?? 0);
      $admUgn['egresos'] = (float)($rowEgr['total'] ?? 0);
      $admUgn['neto'] = round($admUgn['ingresos'] - $admUgn['egresos'], 2);
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
        Ingresos: <?= adm_ugn_h(adm_ugn_money($admUgn['ingresos'])) ?> · Egresos: <?= adm_ugn_h(adm_ugn_money($admUgn['egresos'])) ?>
      </div>
    <?php else: ?>
      <div class="adm-kpi-mini-empty"><?= adm_ugn_h($admUgn['msg']) ?></div>
    <?php endif; ?>
  </div>
</div>
