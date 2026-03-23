<?php
// dashboard/administracion/funcion_ingreso_egreso_mensual.php
// Card gerencial del periodo: recaudado, devuelto, egresado y disponible por caja diaria.

if (!function_exists('adm_ie_h')) {
  function adm_ie_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('adm_ie_money')) {
  function adm_ie_money($value): string {
    return 'S/ ' . number_format((float)$value, 2, '.', ',');
  }
}

if (!function_exists('adm_ie_ymd_to_dmy')) {
  function adm_ie_ymd_to_dmy(string $ymd): string {
    $raw = trim($ymd);
    if ($raw === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if ($dt && $dt->format('Y-m-d') === $raw) {
      return $dt->format('d/m/Y');
    }
    return $raw;
  }
}

if (!function_exists('adm_ie_table_exists')) {
  function adm_ie_table_exists(mysqli $db, string $name): bool {
    $name = $db->real_escape_string($name);
    $rs = $db->query("SHOW TABLES LIKE '{$name}'");
    return $rs && $rs->num_rows > 0;
  }
}

$admIeDb = (isset($mysqli) && $mysqli instanceof mysqli) ? $mysqli : db();
$admIeEmpresaId = isset($empresaId) ? (int)$empresaId : (int)(currentUser()['empresa']['id'] ?? 0);

$admIeData = [
  'default_period' => '',
  'months' => [],
  'periods' => [],
  'schema_message' => '',
  'has_data' => false,
];

$admIeSchema = [
  'caja_mensual' => false,
  'caja_diaria' => false,
  'abonos' => false,
  'abono_aplicaciones' => false,
  'devoluciones' => false,
  'egresos' => false,
  'egresos_fuentes' => false,
];

if ($admIeEmpresaId > 0) {
  $admIeSchema['caja_mensual'] = adm_ie_table_exists($admIeDb, 'mod_caja_mensual');
  $admIeSchema['caja_diaria'] = adm_ie_table_exists($admIeDb, 'mod_caja_diaria');
  $admIeSchema['abonos'] = adm_ie_table_exists($admIeDb, 'pos_abonos');
  $admIeSchema['abono_aplicaciones'] = adm_ie_table_exists($admIeDb, 'pos_abono_aplicaciones');
  $admIeSchema['devoluciones'] = adm_ie_table_exists($admIeDb, 'pos_devoluciones');
  $admIeSchema['egresos'] = adm_ie_table_exists($admIeDb, 'egr_egresos');
  $admIeSchema['egresos_fuentes'] = adm_ie_table_exists($admIeDb, 'egr_egreso_fuentes');
}

$admIeCanBuild = $admIeEmpresaId > 0 && $admIeSchema['caja_mensual'] && $admIeSchema['caja_diaria'];

if (!$admIeCanBuild) {
  if ($admIeEmpresaId <= 0) {
    $admIeData['schema_message'] = 'No se pudo detectar empresa para cargar el card.';
  } elseif (!$admIeSchema['caja_mensual'] || !$admIeSchema['caja_diaria']) {
    $admIeData['schema_message'] = 'Faltan tablas de caja (mod_caja_mensual / mod_caja_diaria).';
  }
} else {
  try {
    $admIeDb->set_charset('utf8mb4');

    $ingresosPorDiaria = [];
    if ($admIeSchema['abonos'] && $admIeSchema['abono_aplicaciones']) {
      $sqlIng = "SELECT
                   a.caja_diaria_id,
                   COALESCE(SUM(apl.monto_aplicado),0) AS total
                 FROM pos_abonos a
                 LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
                 WHERE a.id_empresa=?
                 GROUP BY a.caja_diaria_id";
      $stIng = $admIeDb->prepare($sqlIng);
      $stIng->bind_param('i', $admIeEmpresaId);
      $stIng->execute();
      $rowsIng = $stIng->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
      $stIng->close();

      foreach ($rowsIng as $ri) {
        $ingresosPorDiaria[(int)$ri['caja_diaria_id']] = (float)$ri['total'];
      }
    }

    $devolucionesPorDiaria = [];
    if ($admIeSchema['devoluciones']) {
      $sqlDev = "SELECT
                   dv.caja_diaria_id,
                   COALESCE(SUM(dv.monto_devuelto),0) AS total
                 FROM pos_devoluciones dv
                 WHERE dv.id_empresa=?
                 GROUP BY dv.caja_diaria_id";
      $stDev = $admIeDb->prepare($sqlDev);
      $stDev->bind_param('i', $admIeEmpresaId);
      $stDev->execute();
      $rowsDev = $stDev->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
      $stDev->close();

      foreach ($rowsDev as $rd) {
        $devolucionesPorDiaria[(int)$rd['caja_diaria_id']] = (float)$rd['total'];
      }
    }

    $egresosPorDiaria = [];
    if ($admIeSchema['egresos'] && $admIeSchema['egresos_fuentes']) {
      $sqlEgr = "SELECT
                   f.id_caja_diaria,
                   COALESCE(SUM(f.monto),0) AS total
                 FROM egr_egreso_fuentes f
                 INNER JOIN egr_egresos e
                   ON e.id = f.id_egreso
                  AND e.id_empresa = f.id_empresa
                 WHERE f.id_empresa=? AND e.estado='ACTIVO'
                 GROUP BY f.id_caja_diaria";
      $stEgr = $admIeDb->prepare($sqlEgr);
      $stEgr->bind_param('i', $admIeEmpresaId);
      $stEgr->execute();
      $rowsEgr = $stEgr->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
      $stEgr->close();

      foreach ($rowsEgr as $re) {
        $egresosPorDiaria[(int)$re['id_caja_diaria']] = (float)$re['total'];
      }

      // Compatibilidad con egresos históricos sin detalle de fuentes.
      $sqlEgrOld = "SELECT
                      e.id_caja_diaria,
                      COALESCE(SUM(e.monto),0) AS total
                    FROM egr_egresos e
                    LEFT JOIN egr_egreso_fuentes f
                      ON f.id_egreso = e.id
                     AND f.id_empresa = e.id_empresa
                    WHERE e.id_empresa=? AND e.estado='ACTIVO' AND f.id IS NULL
                    GROUP BY e.id_caja_diaria";
      $stEgrOld = $admIeDb->prepare($sqlEgrOld);
      $stEgrOld->bind_param('i', $admIeEmpresaId);
      $stEgrOld->execute();
      $rowsEgrOld = $stEgrOld->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
      $stEgrOld->close();

      foreach ($rowsEgrOld as $ro) {
        $cdId = (int)$ro['id_caja_diaria'];
        $egresosPorDiaria[$cdId] = (float)($egresosPorDiaria[$cdId] ?? 0) + (float)$ro['total'];
      }
    } elseif ($admIeSchema['egresos']) {
      $sqlEgr = "SELECT
                   id_caja_diaria,
                   COALESCE(SUM(CASE WHEN estado='ACTIVO' THEN monto ELSE 0 END),0) AS total
                 FROM egr_egresos
                 WHERE id_empresa=?
                 GROUP BY id_caja_diaria";
      $stEgr = $admIeDb->prepare($sqlEgr);
      $stEgr->bind_param('i', $admIeEmpresaId);
      $stEgr->execute();
      $rowsEgr = $stEgr->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
      $stEgr->close();

      foreach ($rowsEgr as $re) {
        $egresosPorDiaria[(int)$re['id_caja_diaria']] = (float)$re['total'];
      }
    }

    $sqlBase = "SELECT
                  cm.id AS cm_id,
                  cm.codigo AS cm_codigo,
                  cm.anio AS cm_anio,
                  cm.mes AS cm_mes,
                  cm.estado AS cm_estado,
                  cd.id AS cd_id,
                  cd.codigo AS cd_codigo,
                  cd.fecha AS cd_fecha,
                  cd.estado AS cd_estado
                FROM mod_caja_mensual cm
                LEFT JOIN mod_caja_diaria cd
                  ON cd.id_caja_mensual = cm.id
                 AND cd.id_empresa = cm.id_empresa
                WHERE cm.id_empresa=?
                ORDER BY cm.anio DESC, cm.mes DESC, cm.id DESC, cd.fecha ASC, cd.id ASC";
    $stBase = $admIeDb->prepare($sqlBase);
    $stBase->bind_param('i', $admIeEmpresaId);
    $stBase->execute();
    $rowsBase = $stBase->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stBase->close();

    $months = [];

    foreach ($rowsBase as $r) {
      $anio = (int)($r['cm_anio'] ?? 0);
      $mes = (int)($r['cm_mes'] ?? 0);
      if ($anio <= 0 || $mes <= 0) {
        continue;
      }

      $periodKey = sprintf('%04d-%02d', $anio, $mes);
      $cmId = (int)($r['cm_id'] ?? 0);

      if (!isset($months[$periodKey])) {
        $months[$periodKey] = [
          'period' => $periodKey,
          'period_label' => sprintf('%02d/%04d', $mes, $anio),
          'caja_mensual_id' => $cmId,
          'caja_mensual_codigo' => (string)($r['cm_codigo'] ?? 'Sin caja'),
          'caja_mensual_estado' => (string)($r['cm_estado'] ?? ''),
          'rows' => [],
          'total_recaudado' => 0.0,
          'total_devuelto' => 0.0,
          'total_egresado' => 0.0,
          'total_salidas' => 0.0,
          'total_disponible' => 0.0,
        ];
      }

      if ((int)$months[$periodKey]['caja_mensual_id'] !== $cmId) {
        continue;
      }

      $cdId = (int)($r['cd_id'] ?? 0);
      if ($cdId <= 0) {
        continue;
      }

      $recaudado = (float)($ingresosPorDiaria[$cdId] ?? 0.0);
      $devuelto = (float)($devolucionesPorDiaria[$cdId] ?? 0.0);
      $egresado = (float)($egresosPorDiaria[$cdId] ?? 0.0);
      $salidas = $devuelto + $egresado;
      $disponible = $recaudado - $salidas;

      $months[$periodKey]['rows'][] = [
        'caja_diaria_id' => $cdId,
        'caja_diaria_codigo' => (string)($r['cd_codigo'] ?? 'Sin codigo'),
        'fecha' => (string)($r['cd_fecha'] ?? ''),
        'estado' => (string)($r['cd_estado'] ?? ''),
        'recaudado' => round($recaudado, 2),
        'devuelto' => round($devuelto, 2),
        'egresado' => round($egresado, 2),
        'salidas' => round($salidas, 2),
        'disponible' => round($disponible, 2),
      ];

      $months[$periodKey]['total_recaudado'] += $recaudado;
      $months[$periodKey]['total_devuelto'] += $devuelto;
      $months[$periodKey]['total_egresado'] += $egresado;
      $months[$periodKey]['total_salidas'] += $salidas;
      $months[$periodKey]['total_disponible'] += $disponible;
    }

    foreach ($months as $k => $m) {
      $months[$k]['total_recaudado'] = round((float)$m['total_recaudado'], 2);
      $months[$k]['total_devuelto'] = round((float)$m['total_devuelto'], 2);
      $months[$k]['total_egresado'] = round((float)$m['total_egresado'], 2);
      $months[$k]['total_salidas'] = round((float)$m['total_salidas'], 2);
      $months[$k]['total_disponible'] = round((float)$m['total_disponible'], 2);
      $admIeData['periods'][] = $k;
    }

    $admIeData['months'] = $months;
    $admIeData['default_period'] = (string)($admIeData['periods'][0] ?? '');
    $admIeData['has_data'] = !empty($admIeData['months']);

    if (!$admIeSchema['abonos'] || !$admIeSchema['abono_aplicaciones']) {
      $admIeData['schema_message'] = 'Sin tablas de abonos aplicados; recaudado mostrado en cero.';
    } elseif (!$admIeSchema['devoluciones']) {
      $admIeData['schema_message'] = 'Sin tabla pos_devoluciones; devuelto mostrado en cero.';
    } elseif (!$admIeSchema['egresos']) {
      $admIeData['schema_message'] = 'Sin tabla egr_egresos; egresado mostrado en cero.';
    } elseif (!$admIeSchema['egresos_fuentes']) {
      $admIeData['schema_message'] = 'Sin tabla egr_egreso_fuentes; egresado tomado por caja de registro.';
    } elseif (!$admIeData['has_data']) {
      $admIeData['schema_message'] = 'No hay cajas mensuales/diarias registradas para graficar.';
    }
  } catch (Throwable $e) {
    $admIeData['schema_message'] = 'No se pudo cargar el card del período.';
  }
}

$admIeDefaultPeriod = (string)$admIeData['default_period'];
$admIeDefault = ($admIeDefaultPeriod !== '' && isset($admIeData['months'][$admIeDefaultPeriod]))
  ? $admIeData['months'][$admIeDefaultPeriod]
  : [
      'period_label' => 'Sin periodo',
      'caja_mensual_codigo' => 'Sin caja',
      'rows' => [],
      'total_recaudado' => 0.0,
      'total_devuelto' => 0.0,
      'total_egresado' => 0.0,
      'total_salidas' => 0.0,
      'total_disponible' => 0.0,
    ];
?>

<div class="card mt-3 adm-ie-card">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
      <div>
        <strong>Flujo del período</strong>
        <div class="text-muted small" id="admIeSubhead">
          Caja mensual: <?= adm_ie_h($admIeDefault['caja_mensual_codigo'] ?? 'Sin caja') ?>
        </div>
      </div>
      <div class="adm-ie-controls">
        <label for="admIeMesInput" class="mb-0 small text-muted">Mes</label>
        <input
          id="admIeMesInput"
          type="month"
          class="form-control form-control-sm"
          value="<?= adm_ie_h($admIeDefaultPeriod) ?>"
        >
      </div>
    </div>

    <div class="adm-ie-kpi-row">
      <div class="adm-ie-kpi">
        <div class="adm-ie-kpi-label">Recaudado</div>
        <div class="adm-ie-kpi-value" id="admIeTotalRecaudado"><?= adm_ie_h(adm_ie_money($admIeDefault['total_recaudado'] ?? 0)) ?></div>
      </div>
      <div class="adm-ie-kpi">
        <div class="adm-ie-kpi-label">Devuelto</div>
        <div class="adm-ie-kpi-value" id="admIeTotalDevuelto"><?= adm_ie_h(adm_ie_money($admIeDefault['total_devuelto'] ?? 0)) ?></div>
      </div>
      <div class="adm-ie-kpi">
        <div class="adm-ie-kpi-label">Egresado</div>
        <div class="adm-ie-kpi-value adm-ie-kpi-expense" id="admIeTotalEgresado"><?= adm_ie_h(adm_ie_money($admIeDefault['total_egresado'] ?? 0)) ?></div>
      </div>
      <div class="adm-ie-kpi">
        <div class="adm-ie-kpi-label">Disponible</div>
        <div class="adm-ie-kpi-value adm-ie-kpi-income" id="admIeTotalDisponible"><?= adm_ie_h(adm_ie_money($admIeDefault['total_disponible'] ?? 0)) ?></div>
      </div>
      <div class="adm-ie-kpi">
        <div class="adm-ie-kpi-label">Período</div>
        <div class="adm-ie-kpi-value" id="admIePeriodo"><?= adm_ie_h($admIeDefault['period_label'] ?? 'Sin periodo') ?></div>
      </div>
    </div>

    <div class="adm-ie-legend">
      <span><i class="adm-ie-dot adm-ie-dot-income"></i> Disponible</span>
      <span><i class="adm-ie-dot adm-ie-dot-expense"></i> Salidas</span>
    </div>

    <div class="adm-ie-chart-wrap">
      <div class="adm-ie-chart" id="admIeChart"></div>
    </div>

    <div class="adm-ie-note" id="admIeNote">
      <?= adm_ie_h($admIeData['schema_message'] !== '' ? $admIeData['schema_message'] : 'Disponible = Recaudado - Devuelto - Egresado.') ?>
    </div>
  </div>
</div>

<script>
(function(){
  const DATA = <?= json_encode($admIeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const money = (n) => 'S/ ' + Number(n || 0).toFixed(2);
  const h = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const byId = (id) => document.getElementById(id);
  const asDate = (ymd) => {
    if (!ymd) return '';
    const p = String(ymd).split('-');
    if (p.length !== 3) return String(ymd);
    return p[2] + '/' + p[1] + '/' + p[0];
  };

  const monthInput = byId('admIeMesInput');
  const chart = byId('admIeChart');
  const subhead = byId('admIeSubhead');
  const kRec = byId('admIeTotalRecaudado');
  const kDev = byId('admIeTotalDevuelto');
  const kEgr = byId('admIeTotalEgresado');
  const kDis = byId('admIeTotalDisponible');
  const kPer = byId('admIePeriodo');
  const note = byId('admIeNote');

  if (!monthInput || !chart || !subhead || !kRec || !kDev || !kEgr || !kDis || !kPer || !note) return;

  const months = (DATA && DATA.months) ? DATA.months : {};
  const periods = Array.isArray(DATA && DATA.periods) ? DATA.periods : [];

  if (periods.length > 0) {
    const minP = periods[periods.length - 1];
    const maxP = periods[0];
    monthInput.min = minP;
    monthInput.max = maxP;
  }

  const render = (period) => {
    const m = months[period] || null;

    if (!m) {
      chart.innerHTML = '<div class="adm-ie-empty">No hay datos para el mes seleccionado.</div>';
      subhead.textContent = 'Caja mensual: Sin datos';
      kRec.textContent = money(0);
      kDev.textContent = money(0);
      kEgr.textContent = money(0);
      kDis.textContent = money(0);
      kPer.textContent = period ? period.replace('-', '/') : 'Sin periodo';
      note.textContent = 'Prueba con un mes que tenga caja mensual registrada.';
      return;
    }

    const rows = Array.isArray(m.rows) ? m.rows : [];
    subhead.textContent = 'Caja mensual: ' + (m.caja_mensual_codigo || 'Sin caja');
    kRec.textContent = money(m.total_recaudado || 0);
    kDev.textContent = money(m.total_devuelto || 0);
    kEgr.textContent = money(m.total_egresado || 0);
    kDis.textContent = money(m.total_disponible || 0);
    kPer.textContent = m.period_label || period.replace('-', '/');

    if (rows.length === 0) {
      chart.innerHTML = '<div class="adm-ie-empty">Este mes no tiene cajas diarias registradas.</div>';
      note.textContent = 'Cuando existan cajas diarias, aquí se mostrará disponible y salidas por cada caja.';
      return;
    }

    let maxVal = 0;
    rows.forEach(r => {
      maxVal = Math.max(
        maxVal,
        Number(r.salidas || 0),
        Math.max(0, Number(r.disponible || 0))
      );
    });
    if (maxVal <= 0) maxVal = 1;

    const blocks = rows.map((r) => {
      const disponible = Number(r.disponible || 0);
      const salidas = Number(r.salidas || 0);
      const dispVisible = Math.max(0, disponible);
      const hDisp = Math.max(4, Math.round((dispVisible / maxVal) * 100));
      const hSal = Math.max(4, Math.round((salidas / maxVal) * 100));

      return (
        '<div class="adm-ie-col">' +
          '<div class="adm-ie-bars">' +
            '<div class="adm-ie-bar adm-ie-bar-income" style="height:' + hDisp + '%" title="Disponible: ' + h(money(disponible)) + '"></div>' +
            '<div class="adm-ie-bar adm-ie-bar-expense" style="height:' + hSal + '%" title="Salidas: ' + h(money(salidas)) + '"></div>' +
          '</div>' +
          '<div class="adm-ie-col-meta">' +
            '<div class="adm-ie-col-code">' + h(r.caja_diaria_codigo || 'Sin caja') + '</div>' +
            '<div class="adm-ie-col-date">' + h(asDate(r.fecha || '')) + '</div>' +
            '<div class="adm-ie-col-values">' +
              '<span>' + h(money(disponible)) + '</span>' +
              '<span>' + h(money(salidas)) + '</span>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
    });

    chart.innerHTML = blocks.join('');
    note.textContent = 'Disponible = Recaudado - Devuelto - Egresado.';
  };

  monthInput.addEventListener('change', function(){
    render(this.value || '');
  });

  const initialPeriod = monthInput.value || (DATA && DATA.default_period) || '';
  render(initialPeriod);
})();
</script>