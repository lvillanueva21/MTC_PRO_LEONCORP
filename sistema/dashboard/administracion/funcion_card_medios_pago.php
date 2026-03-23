<?php
// dashboard/administracion/funcion_card_medios_pago.php
// Widget: resumen por medios de pago en la caja diaria abierta.

if (!function_exists('adm_mp_h')) {
  function adm_mp_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('adm_mp_money')) {
  function adm_mp_money($value): string {
    return 'S/ ' . number_format((float)$value, 2, '.', ',');
  }
}

if (!function_exists('adm_mp_date')) {
  function adm_mp_date(string $ymd): string {
    $raw = trim($ymd);
    if ($raw === '') {
      return 'Sin fecha';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if ($dt && $dt->format('Y-m-d') === $raw) {
      return $dt->format('d/m/Y');
    }
    return $raw;
  }
}

if (!function_exists('adm_mp_table_exists')) {
  function adm_mp_table_exists(mysqli $db, string $name): bool {
    $name = $db->real_escape_string($name);
    $rs = $db->query("SHOW TABLES LIKE '{$name}'");
    return $rs && $rs->num_rows > 0;
  }
}

if (!function_exists('adm_mp_asset_url')) {
  function adm_mp_asset_url(string $file): string {
    $base = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
    return $base . '/assets/img/tipo_pago/' . ltrim($file, '/');
  }
}

$admMpDb = (isset($mysqli) && $mysqli instanceof mysqli) ? $mysqli : db();
$admMpEmpresaId = isset($empresaId) ? (int)$empresaId : (int)(currentUser()['empresa']['id'] ?? 0);
$admMpHelperPath = __DIR__ . '/../../modules/egresos/finanzas_medios.php';

$admMp = [
  'ok' => false,
  'msg' => '',
  'caja_id' => 0,
  'caja_codigo' => 'Sin caja abierta',
  'caja_fecha' => '',
  'rows' => [],
  'egresos_no_distribuidos' => 0.0,
];

$admMpKeyOrder = ['EFECTIVO', 'YAPE', 'PLIN', 'TRANSFERENCIA'];
$admMpImgMap = [
  'EFECTIVO' => 'efectivo_225_225.png',
  'YAPE' => 'yape_225_225.jpg',
  'PLIN' => 'plin_225_225.jpg',
  'TRANSFERENCIA' => 'transferencia_225_225.png',
];
$admMpLabelMap = [
  'EFECTIVO' => 'Efectivo',
  'YAPE' => 'Yape',
  'PLIN' => 'Plin',
  'TRANSFERENCIA' => 'Transferencia',
];

if ($admMpEmpresaId <= 0) {
  $admMp['msg'] = 'Tu usuario no tiene empresa asignada.';
} elseif (!is_file($admMpHelperPath)) {
  $admMp['msg'] = 'No se encontro el helper de medios de pago del modulo egresos.';
} else {
  require_once $admMpHelperPath;

  if (!function_exists('fin_disponible_por_fuente_diaria')) {
    $admMp['msg'] = 'La funcion de calculo por medios no esta disponible.';
  } else {
    try {
      $sqlCaja = "SELECT id, codigo, fecha
                 FROM mod_caja_diaria
                 WHERE id_empresa=? AND estado='abierta'
                 ORDER BY fecha DESC, id DESC
                 LIMIT 1";
      $stCaja = $admMpDb->prepare($sqlCaja);
      $stCaja->bind_param('i', $admMpEmpresaId);
      $stCaja->execute();
      $caja = $stCaja->get_result()->fetch_assoc();
      $stCaja->close();

      if (!$caja) {
        $admMp['msg'] = 'No hay caja diaria abierta.';
      } else {
        $admMp['caja_id'] = (int)$caja['id'];
        $admMp['caja_codigo'] = (string)($caja['codigo'] ?? 'Sin caja abierta');
        $admMp['caja_fecha'] = (string)($caja['fecha'] ?? '');

        $calc = fin_disponible_por_fuente_diaria($admMpDb, $admMpEmpresaId, $admMp['caja_id']);
        $rows = is_array($calc['rows'] ?? null) ? $calc['rows'] : [];
        $rowsByKey = [];
        foreach ($rows as $r) {
          $k = strtoupper((string)($r['key'] ?? ''));
          if ($k !== '') {
            $rowsByKey[$k] = $r;
          }
        }

        $outRows = [];
        foreach ($admMpKeyOrder as $key) {
          $r = $rowsByKey[$key] ?? [];
          $ingresos = (float)($r['ingresos'] ?? 0);
          $devoluciones = (float)($r['devoluciones'] ?? 0);
          $montoNeto = (float)($r['monto_neto'] ?? ($ingresos - $devoluciones));
          $egresosActivos = (float)($r['egresos_activos'] ?? 0);
          $saldoDisponible = (float)($r['saldo_disponible'] ?? ($montoNeto - $egresosActivos));

          $outRows[] = [
            'key' => $key,
            'label' => (string)($r['label'] ?? $admMpLabelMap[$key]),
            'icon_url' => adm_mp_asset_url($admMpImgMap[$key] ?? ''),
            'ingresos' => round($ingresos, 2),
            'devoluciones' => round($devoluciones, 2),
            'monto_neto' => round($montoNeto, 2),
            'egresos_activos' => round($egresosActivos, 2),
            'saldo_disponible' => round($saldoDisponible, 2),
          ];
        }

        $admMp['rows'] = $outRows;
        $admMp['ok'] = true;

        if (adm_mp_table_exists($admMpDb, 'egr_egresos') && adm_mp_table_exists($admMpDb, 'egr_egreso_fuentes')) {
          $sqlEgrAct = "SELECT COALESCE(SUM(monto),0) AS total
                        FROM egr_egresos
                        WHERE id_empresa=? AND id_caja_diaria=? AND estado='ACTIVO'";
          $stEgr = $admMpDb->prepare($sqlEgrAct);
          $stEgr->bind_param('ii', $admMpEmpresaId, $admMp['caja_id']);
          $stEgr->execute();
          $totEgr = (float)(($stEgr->get_result()->fetch_assoc()['total'] ?? 0));
          $stEgr->close();

          $totByFuente = 0.0;
          foreach ($admMp['rows'] as $rw) {
            $totByFuente += (float)($rw['egresos_activos'] ?? 0);
          }
          $gap = round($totEgr - $totByFuente, 2);
          if (abs($gap) < 0.005) {
            $gap = 0.0;
          }
          $admMp['egresos_no_distribuidos'] = $gap;
        }
      }
    } catch (Throwable $e) {
      $admMp['msg'] = 'No se pudo cargar medios de pago de la caja abierta.';
    }
  }
}
?>

<div class="card mt-3 adm-pay-card">
  <div class="card-body">
    <div class="adm-pay-head">
      <div>
        <div class="adm-pay-kicker">Caja en vivo</div>
        <h6 class="card-title mb-1">Medios de pago</h6>
        <?php if ($admMp['ok']): ?>
          <div class="adm-pay-meta">
            Caja: <?= adm_mp_h($admMp['caja_codigo']) ?> · Fecha: <?= adm_mp_h(adm_mp_date((string)$admMp['caja_fecha'])) ?>
          </div>
        <?php endif; ?>
      </div>
      <span class="adm-pay-badge <?= $admMp['ok'] ? 'is-open' : 'is-closed' ?>">
        <?= $admMp['ok'] ? 'Caja abierta' : 'Sin caja abierta' ?>
      </span>
    </div>

    <?php if (!$admMp['ok']): ?>
      <div class="adm-pay-empty"><?= adm_mp_h($admMp['msg']) ?></div>
    <?php else: ?>
      <div class="adm-pay-grid">
        <?php foreach ($admMp['rows'] as $row): ?>
          <article class="adm-pay-item">
            <div class="adm-pay-item-head">
              <img class="adm-pay-icon" src="<?= adm_mp_h((string)$row['icon_url']) ?>" alt="<?= adm_mp_h((string)$row['label']) ?>" loading="lazy" onerror="this.style.display='none'">
              <span class="adm-pay-label"><?= adm_mp_h((string)$row['label']) ?></span>
            </div>

            <div class="adm-pay-main"><?= adm_mp_h(adm_mp_money((float)$row['monto_neto'])) ?></div>
            <div class="adm-pay-sub">Ingreso neto</div>

            <div class="adm-pay-out">Extraido: <?= adm_mp_h(adm_mp_money((float)$row['egresos_activos'])) ?></div>
            <div class="adm-pay-avail">Disponible: <?= adm_mp_h(adm_mp_money((float)$row['saldo_disponible'])) ?></div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if (abs((float)$admMp['egresos_no_distribuidos']) > 0.0001): ?>
        <div class="adm-pay-warning">
          Hay egresos activos sin distribucion por medio en esta caja: <?= adm_mp_h(adm_mp_money((float)$admMp['egresos_no_distribuidos'])) ?>.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
