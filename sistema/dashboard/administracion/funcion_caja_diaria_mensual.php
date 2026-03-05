<?php
// dashboard/administracion/funcion_caja_diaria_mensual.php
// Widget: ingresos por caja diaria y caja mensual (basado en abonos aplicados).

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

if (!function_exists('adm_caja_widget_pick_diaria')) {
  function adm_caja_widget_pick_diaria(mysqli $db, int $empresaId, string $hoy): array {
    $out = [
      'id'            => 0,
      'codigo'        => 'Sin caja',
      'fecha'         => '',
      'estado'        => 'inexistente',
      'source'        => 'none',
      'source_label'  => 'Sin registros',
      'helper'        => 'No existen cajas diarias.'
    ];

    $st = $db->prepare("SELECT id, codigo, fecha, estado
                        FROM mod_caja_diaria
                        WHERE id_empresa=? AND fecha=?
                        ORDER BY id DESC
                        LIMIT 1");
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
      $out['source_label'] = 'Caja de hoy';
      $out['helper'] = 'Mostrando la caja diaria del día actual.';
      return $out;
    }

    $st = $db->prepare("SELECT id, codigo, fecha, estado
                        FROM mod_caja_diaria
                        WHERE id_empresa=?
                        ORDER BY fecha DESC, id DESC
                        LIMIT 1");
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
      $out['source_label'] = 'Caja anterior';
      $out['helper'] = 'Hoy no hubo caja diaria; se muestra la más reciente.';
    }

    return $out;
  }
}

if (!function_exists('adm_caja_widget_pick_mensual')) {
  function adm_caja_widget_pick_mensual(mysqli $db, int $empresaId, int $anio, int $mes): array {
    $out = [
      'id'            => 0,
      'codigo'        => 'Sin caja',
      'anio'          => 0,
      'mes'           => 0,
      'estado'        => 'inexistente',
      'source'        => 'none',
      'source_label'  => 'Sin registros',
      'helper'        => 'No existen cajas mensuales.'
    ];

    $st = $db->prepare("SELECT id, codigo, anio, mes, estado
                        FROM mod_caja_mensual
                        WHERE id_empresa=? AND anio=? AND mes=?
                        ORDER BY id DESC
                        LIMIT 1");
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
      $out['source_label'] = 'Mes actual';
      $out['helper'] = 'Mostrando la caja mensual del mes actual.';
      return $out;
    }

    $st = $db->prepare("SELECT id, codigo, anio, mes, estado
                        FROM mod_caja_mensual
                        WHERE id_empresa=?
                        ORDER BY anio DESC, mes DESC, id DESC
                        LIMIT 1");
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
      $out['source_label'] = 'Caja anterior';
      $out['helper'] = 'No existe caja del mes actual; se muestra la más reciente.';
    }

    return $out;
  }
}

if (!function_exists('adm_caja_widget_total_diaria')) {
  function adm_caja_widget_total_diaria(mysqli $db, int $empresaId, int $cajaDiariaId): array {
    if ($cajaDiariaId <= 0) {
      return ['abonos' => 0, 'total' => 0.0];
    }

    $sql = "SELECT
              COUNT(DISTINCT a.id) AS total_abonos,
              COALESCE(SUM(apl.monto_aplicado),0) AS total_ingresos
            FROM pos_abonos a
            LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
            WHERE a.id_empresa=? AND a.caja_diaria_id=?";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $empresaId, $cajaDiariaId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc() ?: [];
    $st->close();

    return [
      'abonos' => (int)($row['total_abonos'] ?? 0),
      'total'  => (float)($row['total_ingresos'] ?? 0)
    ];
  }
}

if (!function_exists('adm_caja_widget_total_mensual')) {
  function adm_caja_widget_total_mensual(mysqli $db, int $empresaId, int $cajaMensualId): array {
    if ($cajaMensualId <= 0) {
      return ['abonos' => 0, 'total' => 0.0];
    }

    $sql = "SELECT
              COUNT(DISTINCT a.id) AS total_abonos,
              COALESCE(SUM(apl.monto_aplicado),0) AS total_ingresos
            FROM pos_abonos a
            JOIN mod_caja_diaria cd ON cd.id = a.caja_diaria_id
            LEFT JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
            WHERE a.id_empresa=? AND cd.id_caja_mensual=?";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $empresaId, $cajaMensualId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc() ?: [];
    $st->close();

    return [
      'abonos' => (int)($row['total_abonos'] ?? 0),
      'total'  => (float)($row['total_ingresos'] ?? 0)
    ];
  }
}

$admCajaDb = (isset($mysqli) && $mysqli instanceof mysqli) ? $mysqli : db();
$admCajaEmpresaId = isset($empresaId) ? (int)$empresaId : (int)(currentUser()['empresa']['id'] ?? 0);
$admCajaError = '';

$admCajaDiaria = [
  'id' => 0, 'codigo' => 'Sin caja', 'fecha' => '', 'estado' => 'inexistente',
  'source' => 'none', 'source_label' => 'Sin registros', 'helper' => 'No existen cajas diarias.'
];
$admCajaMensual = [
  'id' => 0, 'codigo' => 'Sin caja', 'anio' => 0, 'mes' => 0, 'estado' => 'inexistente',
  'source' => 'none', 'source_label' => 'Sin registros', 'helper' => 'No existen cajas mensuales.'
];
$admCajaTotDiaria = ['abonos' => 0, 'total' => 0.0];
$admCajaTotMensual = ['abonos' => 0, 'total' => 0.0];

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

    $admCajaTotDiaria = adm_caja_widget_total_diaria($admCajaDb, $admCajaEmpresaId, (int)$admCajaDiaria['id']);
    $admCajaTotMensual = adm_caja_widget_total_mensual($admCajaDb, $admCajaEmpresaId, (int)$admCajaMensual['id']);
  } catch (Throwable $e) {
    $admCajaError = 'No se pudo consultar las cajas por el momento.';
  }
} else {
  $admCajaError = 'Tu usuario no tiene empresa asignada.';
}

$admCajaNoBoxes = ((int)$admCajaDiaria['id'] <= 0) && ((int)$admCajaMensual['id'] <= 0);
$admCajaNoIngresos = ((float)$admCajaTotDiaria['total'] <= 0.000001) && ((float)$admCajaTotMensual['total'] <= 0.000001);

$admCajaDiariaBadge = 'cdm-badge-none';
if ($admCajaDiaria['source'] === 'today') $admCajaDiariaBadge = 'cdm-badge-today';
if ($admCajaDiaria['source'] === 'latest') $admCajaDiariaBadge = 'cdm-badge-latest';

$admCajaMensualBadge = 'cdm-badge-none';
if ($admCajaMensual['source'] === 'current') $admCajaMensualBadge = 'cdm-badge-current';
if ($admCajaMensual['source'] === 'latest') $admCajaMensualBadge = 'cdm-badge-latest';

$admCajaMensualPeriodo = 'Sin periodo';
if ((int)$admCajaMensual['anio'] > 0 && (int)$admCajaMensual['mes'] > 0) {
  $admCajaMensualPeriodo = str_pad((string)$admCajaMensual['mes'], 2, '0', STR_PAD_LEFT) . '/' . $admCajaMensual['anio'];
}
?>

<div class="card cdm-widget-card">
  <div class="card-body">
    <div class="cdm-head">
      <div>
        <div class="cdm-kicker">Dashboard de Caja</div>
        <h6 class="cdm-title">Ingresos por Abonos Aplicados</h6>
      </div>
      <div class="cdm-coin" aria-hidden="true">S/</div>
    </div>

    <div class="cdm-grid">
      <article class="cdm-box cdm-box-day">
        <div class="cdm-box-top">
          <span class="cdm-box-label">Caja diaria</span>
          <span class="cdm-source <?= adm_caja_widget_h($admCajaDiariaBadge) ?>">
            <?= adm_caja_widget_h($admCajaDiaria['source_label']) ?>
          </span>
        </div>
        <div class="cdm-code"><?= adm_caja_widget_h($admCajaDiaria['codigo']) ?></div>
        <div class="cdm-meta"><?= adm_caja_widget_h(adm_caja_widget_human_date($admCajaDiaria['fecha'])) ?></div>
        <div class="cdm-total"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotDiaria['total'])) ?></div>
        <div class="cdm-note">
          <?= adm_caja_widget_h($admCajaDiaria['helper']) ?> <?= (int)$admCajaTotDiaria['abonos'] ?> abono(s).
        </div>
      </article>

      <article class="cdm-box cdm-box-month">
        <div class="cdm-box-top">
          <span class="cdm-box-label">Caja mensual</span>
          <span class="cdm-source <?= adm_caja_widget_h($admCajaMensualBadge) ?>">
            <?= adm_caja_widget_h($admCajaMensual['source_label']) ?>
          </span>
        </div>
        <div class="cdm-code"><?= adm_caja_widget_h($admCajaMensual['codigo']) ?></div>
        <div class="cdm-meta"><?= adm_caja_widget_h($admCajaMensualPeriodo) ?></div>
        <div class="cdm-total"><?= adm_caja_widget_h(adm_caja_widget_money($admCajaTotMensual['total'])) ?></div>
        <div class="cdm-note">
          <?= adm_caja_widget_h($admCajaMensual['helper']) ?> <?= (int)$admCajaTotMensual['abonos'] ?> abono(s).
        </div>
      </article>
    </div>

    <?php if ($admCajaError !== ''): ?>
      <div class="cdm-friendly">
        <?= adm_caja_widget_h($admCajaError) ?>
      </div>
    <?php elseif ($admCajaNoBoxes): ?>
      <div class="cdm-friendly">
        Aún no existen cajas para esta empresa. Cuando se registren, verás aquí el resumen.
      </div>
    <?php elseif ($admCajaNoIngresos): ?>
      <div class="cdm-friendly">
        Tus cajas existen, pero todavía no tienen ingresos por abonos aplicados.
      </div>
    <?php endif; ?>
  </div>
</div>
