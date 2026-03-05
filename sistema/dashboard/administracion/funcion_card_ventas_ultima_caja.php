<?php
// dashboard/administracion/funcion_card_ventas_ultima_caja.php
// Card: total de ingresos (abonos aplicados) de la ultima caja diaria.

if (!function_exists('adm_uci_h')) {
  function adm_uci_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('adm_uci_money')) {
  function adm_uci_money($value): string {
    return 'S/ ' . number_format((float)$value, 2, '.', ',');
  }
}

if (!function_exists('adm_uci_ymd_to_dmy')) {
  function adm_uci_ymd_to_dmy(string $ymd): string {
    $raw = trim($ymd);
    if ($raw === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if ($dt && $dt->format('Y-m-d') === $raw) {
      return $dt->format('d/m/Y');
    }
    return $raw;
  }
}

if (!function_exists('adm_uci_table_exists')) {
  function adm_uci_table_exists(mysqli $db, string $name): bool {
    $name = $db->real_escape_string($name);
    $rs = $db->query("SHOW TABLES LIKE '{$name}'");
    return $rs && $rs->num_rows > 0;
  }
}

$admUciDb = (isset($mysqli) && $mysqli instanceof mysqli) ? $mysqli : db();
$admUciEmpresaId = isset($empresaId) ? (int)$empresaId : (int)(currentUser()['empresa']['id'] ?? 0);

$admUci = [
  'ok' => false,
  'msg' => '',
  'caja_codigo' => 'Sin caja',
  'caja_fecha' => '',
  'total' => 0.0,
  'abonos' => 0,
];

if ($admUciEmpresaId > 0
  && adm_uci_table_exists($admUciDb, 'mod_caja_diaria')
  && adm_uci_table_exists($admUciDb, 'pos_abonos')
  && adm_uci_table_exists($admUciDb, 'pos_abono_aplicaciones')
) {
  try {
    $sqlCaja = "SELECT id, codigo, fecha
                FROM mod_caja_diaria
                WHERE id_empresa=?
                ORDER BY fecha DESC, id DESC
                LIMIT 1";
    $stCaja = $admUciDb->prepare($sqlCaja);
    $stCaja->bind_param('i', $admUciEmpresaId);
    $stCaja->execute();
    $caja = $stCaja->get_result()->fetch_assoc();
    $stCaja->close();

    if ($caja) {
      $cajaId = (int)$caja['id'];
      $admUci['caja_codigo'] = (string)($caja['codigo'] ?? 'Sin caja');
      $admUci['caja_fecha'] = (string)($caja['fecha'] ?? '');

      $sqlTot = "SELECT
                   COUNT(DISTINCT a.id) AS total_abonos,
                   COALESCE(SUM(apl.monto_aplicado),0) AS total_ingresos
                 FROM pos_abonos a
                 LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
                 WHERE a.id_empresa=? AND a.caja_diaria_id=?";
      $stTot = $admUciDb->prepare($sqlTot);
      $stTot->bind_param('ii', $admUciEmpresaId, $cajaId);
      $stTot->execute();
      $rowTot = $stTot->get_result()->fetch_assoc() ?: [];
      $stTot->close();

      $admUci['total'] = (float)($rowTot['total_ingresos'] ?? 0);
      $admUci['abonos'] = (int)($rowTot['total_abonos'] ?? 0);
      $admUci['ok'] = true;
    } else {
      $admUci['msg'] = 'No existen cajas diarias registradas.';
    }
  } catch (Throwable $e) {
    $admUci['msg'] = 'No se pudo cargar ingresos de la última caja.';
  }
} else {
  $admUci['msg'] = 'Faltan tablas para calcular ingresos.';
}
?>

<div class="card kpi-card mt-3 adm-kpi-mini adm-kpi-income">
  <div class="card-body">
    <div class="adm-kpi-mini-head">
      <h6 class="card-title mb-0">Ventas (Ingresos)</h6>
      <span class="adm-kpi-mini-badge">Últ. caja</span>
    </div>
    <?php if ($admUci['ok']): ?>
      <div class="adm-kpi-mini-value"><?= adm_uci_h(adm_uci_money($admUci['total'])) ?></div>
      <div class="adm-kpi-mini-meta">
        Caja: <?= adm_uci_h($admUci['caja_codigo']) ?> · Fecha: <?= adm_uci_h(adm_uci_ymd_to_dmy($admUci['caja_fecha'])) ?>
      </div>
      <div class="adm-kpi-mini-note">
        <?= (int)$admUci['abonos'] ?> abono(s) aplicado(s). No considera egresos.
      </div>
    <?php else: ?>
      <div class="adm-kpi-mini-empty"><?= adm_uci_h($admUci['msg']) ?></div>
    <?php endif; ?>
  </div>
</div>

