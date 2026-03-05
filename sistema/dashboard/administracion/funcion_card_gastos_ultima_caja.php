<?php
// dashboard/administracion/funcion_card_gastos_ultima_caja.php
// Card: total de egresos de la ultima caja diaria.

if (!function_exists('adm_uge_h')) {
  function adm_uge_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('adm_uge_money')) {
  function adm_uge_money($value): string {
    return 'S/ ' . number_format((float)$value, 2, '.', ',');
  }
}

if (!function_exists('adm_uge_ymd_to_dmy')) {
  function adm_uge_ymd_to_dmy(string $ymd): string {
    $raw = trim($ymd);
    if ($raw === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if ($dt && $dt->format('Y-m-d') === $raw) {
      return $dt->format('d/m/Y');
    }
    return $raw;
  }
}

if (!function_exists('adm_uge_table_exists')) {
  function adm_uge_table_exists(mysqli $db, string $name): bool {
    $name = $db->real_escape_string($name);
    $rs = $db->query("SHOW TABLES LIKE '{$name}'");
    return $rs && $rs->num_rows > 0;
  }
}

$admUgeDb = (isset($mysqli) && $mysqli instanceof mysqli) ? $mysqli : db();
$admUgeEmpresaId = isset($empresaId) ? (int)$empresaId : (int)(currentUser()['empresa']['id'] ?? 0);

$admUge = [
  'ok' => false,
  'msg' => '',
  'caja_codigo' => 'Sin caja',
  'caja_fecha' => '',
  'total' => 0.0,
  'activos' => 0,
  'anulados' => 0,
];

if ($admUgeEmpresaId > 0
  && adm_uge_table_exists($admUgeDb, 'mod_caja_diaria')
  && adm_uge_table_exists($admUgeDb, 'egr_egresos')
) {
  try {
    $sqlCaja = "SELECT id, codigo, fecha
                FROM mod_caja_diaria
                WHERE id_empresa=?
                ORDER BY fecha DESC, id DESC
                LIMIT 1";
    $stCaja = $admUgeDb->prepare($sqlCaja);
    $stCaja->bind_param('i', $admUgeEmpresaId);
    $stCaja->execute();
    $caja = $stCaja->get_result()->fetch_assoc();
    $stCaja->close();

    if ($caja) {
      $cajaId = (int)$caja['id'];
      $admUge['caja_codigo'] = (string)($caja['codigo'] ?? 'Sin caja');
      $admUge['caja_fecha'] = (string)($caja['fecha'] ?? '');

      $sqlTot = "SELECT
                   COALESCE(SUM(CASE WHEN estado='ACTIVO' THEN monto ELSE 0 END),0) AS total_egresos,
                   COALESCE(SUM(CASE WHEN estado='ACTIVO' THEN 1 ELSE 0 END),0) AS cnt_activos,
                   COALESCE(SUM(CASE WHEN estado='ANULADO' THEN 1 ELSE 0 END),0) AS cnt_anulados
                 FROM egr_egresos
                 WHERE id_empresa=? AND id_caja_diaria=?";
      $stTot = $admUgeDb->prepare($sqlTot);
      $stTot->bind_param('ii', $admUgeEmpresaId, $cajaId);
      $stTot->execute();
      $rowTot = $stTot->get_result()->fetch_assoc() ?: [];
      $stTot->close();

      $admUge['total'] = (float)($rowTot['total_egresos'] ?? 0);
      $admUge['activos'] = (int)($rowTot['cnt_activos'] ?? 0);
      $admUge['anulados'] = (int)($rowTot['cnt_anulados'] ?? 0);
      $admUge['ok'] = true;
    } else {
      $admUge['msg'] = 'No existen cajas diarias registradas.';
    }
  } catch (Throwable $e) {
    $admUge['msg'] = 'No se pudo cargar egresos de la última caja.';
  }
} else {
  $admUge['msg'] = 'Faltan tablas para calcular egresos.';
}
?>

<div class="card kpi-card mt-3 adm-kpi-mini adm-kpi-expense">
  <div class="card-body">
    <div class="adm-kpi-mini-head">
      <h6 class="card-title mb-0">Gastos (Egresos)</h6>
      <span class="adm-kpi-mini-badge">Últ. caja</span>
    </div>
    <?php if ($admUge['ok']): ?>
      <div class="adm-kpi-mini-value"><?= adm_uge_h(adm_uge_money($admUge['total'])) ?></div>
      <div class="adm-kpi-mini-meta">
        Caja: <?= adm_uge_h($admUge['caja_codigo']) ?> · Fecha: <?= adm_uge_h(adm_uge_ymd_to_dmy($admUge['caja_fecha'])) ?>
      </div>
      <div class="adm-kpi-mini-note">
        Activos: <?= (int)$admUge['activos'] ?> · Anulados: <?= (int)$admUge['anulados'] ?>
      </div>
    <?php else: ?>
      <div class="adm-kpi-mini-empty"><?= adm_uge_h($admUge['msg']) ?></div>
    <?php endif; ?>
  </div>
</div>

