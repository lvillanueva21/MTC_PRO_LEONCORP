<?php
// modules/reporte_abonos/index.php — Central de Abonos (rastreo de ingresos)

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

$PAGE_TITLE = 'Central de Abonos';
$ALLOWED_ROLE_IDS   = [3,4];                       // Recepción, Administración
$ALLOWED_ROLE_NAMES = ['Recepción','Administración'];

acl_require_ids($ALLOWED_ROLE_IDS);
if (function_exists('verificarPermiso')) {
    verificarPermiso($ALLOWED_ROLE_NAMES);
}

$u = currentUser();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

/* ========= Helpers ========= */
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Cálculo de prefijo relativo a la raíz de la app y helper de enlaces.
 */
$appFolder = basename(dirname(dirname(__DIR__)));              // p.ej. 'ventas'
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // ej. 'ventas/modules/caja' o 'modules/caja'
$parts     = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx       = array_search($appFolder, $parts, true);
$depth     = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1));
$APP_ROOT_REL = str_repeat('../', max(0, $depth));

function rel(string $path) {
    global $APP_ROOT_REL;
    return $APP_ROOT_REL . ltrim($path, '/');
}

require_once __DIR__ . '/funciones.php';

$empId = (int)($u['empresa']['id'] ?? 0);

// Filtros
$q          = trim($_GET['q'] ?? '');
$medioId    = isset($_GET['medio_id']) ? (int)$_GET['medio_id'] : 0;
$usuarioId  = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$aplEstado  = isset($_GET['apl']) ? (string)$_GET['apl'] : '';
$devEstado  = isset($_GET['dev']) ? (string)$_GET['dev'] : '';
$fdesde     = isset($_GET['fdesde']) ? (string)$_GET['fdesde'] : '';
$fhasta     = isset($_GET['fhasta']) ? (string)$_GET['fhasta'] : '';

// Paginación
$perPage   = isset($_GET['pp']) ? (int)$_GET['pp'] : 10;
$allowedPP = [10, 20, 50, 100];
if (!in_array($perPage, $allowedPP, true)) {
    $perPage = 10;
}
$page = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
if ($page < 1) $page = 1;

$opts = [
    'q'                 => $q,
    'medio_id'          => $medioId,
    'usuario_id'        => $usuarioId,
    'aplicacion_estado' => $aplEstado,
    'tiene_devolucion'  => $devEstado,
    'fdesde'            => $fdesde,
    'fhasta'            => $fhasta,
    'pagina'            => $page,
    'por_pagina'        => $perPage,
];

// Combos
$medios   = listar_medios_pago($db);
$usuarios = listar_usuarios_abonos($db, $empId);

// Datos de abonos + stats
$result      = buscar_abonos_con_detalles($db, $empId, $opts);
$abonos      = $result['rows'];
$total       = $result['total'];
$page        = $result['page'];
$perPage     = $result['per_page'];
$totalPages  = $result['total_pages'];
$from        = $result['from'];
$to          = $result['to'];
$sumMonto    = $result['sum_monto'];
$sumDevuelto = $result['sum_devuelto'];
$sumNeto     = $result['sum_neto'];

// Resumen de caja diaria / mensual
$resCaja = obtener_resumen_caja_abonos($db, $empId);

// Query string base para mantener filtros
$qsBase = [
    'q'          => $q !== '' ? $q : null,
    'medio_id'   => $medioId ?: null,
    'usuario_id' => $usuarioId ?: null,
    'apl'        => $aplEstado !== '' ? $aplEstado : null,
    'dev'        => $devEstado !== '' ? $devEstado : null,
    'fdesde'     => $fdesde !== '' ? $fdesde : null,
    'fhasta'     => $fhasta !== '' ? $fhasta : null,
    'pp'         => $perPage,
];
$tmp = [];
foreach ($qsBase as $k => $v) {
    if ($v !== null && $v !== '') $tmp[$k] = $v;
}
$qsBase = $tmp;

$empresaNombre = (string)($u['empresa']['nombre'] ?? '');
$empresaRuc    = (string)($u['empresa']['ruc'] ?? '');
$usuarioNombre = trim((string)($u['nombres'] ?? '') . ' ' . (string)($u['apellidos'] ?? ''));
$usuarioUser   = (string)($u['usuario'] ?? '');
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrapper">
  <!-- Header de la página -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0"><?= h($PAGE_TITLE) ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= h(rel('inicio.php')) ?>">Inicio</a></li>
            <li class="breadcrumb-item active"><?= h($PAGE_TITLE) ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Contenido principal -->
  <section class="content">
    <div class="container-fluid">

      <!-- Cards de contexto (empresa / usuario / caja) -->
      <div class="row">
        <!-- Empresa -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="info-box bg-light">
            <span class="info-box-icon bg-info elevation-1">
              <i class="fas fa-building"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Empresa</span>
              <span class="info-box-number"><?= h($empresaNombre !== '' ? $empresaNombre : '—') ?></span>
              <div class="progress">
                <div class="progress-bar" style="width: 100%"></div>
              </div>
              <span class="progress-description">
                RUC: <?= h($empresaRuc !== '' ? $empresaRuc : '—') ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Usuario -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="info-box bg-light">
            <span class="info-box-icon bg-success elevation-1">
              <i class="fas fa-user-tie"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Usuario actual</span>
              <span class="info-box-number">
                <?= h($usuarioNombre !== '' ? $usuarioNombre : '—') ?>
              </span>
              <div class="progress">
                <div class="progress-bar" style="width: 100%"></div>
              </div>
              <span class="progress-description">
                Usuario: <?= h($usuarioUser !== '' ? $usuarioUser : '—') ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Caja diaria actual -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="info-box bg-light">
            <span class="info-box-icon bg-warning elevation-1">
              <i class="fas fa-cash-register"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Caja diaria actual</span>
              <?php if (!empty($resCaja['diaria'])):
                $cd  = $resCaja['diaria'];
                $cdD = $cd['data'];
                $cdFecha = fmt_date($cdD['fecha'] ?? null);
              ?>
                <span class="info-box-number">
                  <?= h($cdFecha !== '' ? $cdFecha : '—') ?>
                  <?= $cdD['estado'] === 'abierta' ? ' (abierta)' : ' (cerrada)' ?>
                </span>
                <div class="progress">
                  <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                  Neto hoy: <?= h(fmt_money($cd['total_neto'])) ?>
                </span>
              <?php else: ?>
                <span class="info-box-number">Sin caja diaria abierta</span>
                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">&nbsp;</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Caja mensual actual -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="info-box bg-light">
            <span class="info-box-icon bg-primary elevation-1">
              <i class="fas fa-calendar-alt"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text">Caja mensual actual</span>
              <?php if (!empty($resCaja['mensual'])):
                $cm  = $resCaja['mensual'];
                $cmD = $cm['data'];
                $periodoTxt = '';
                if (!empty($cmD['anio']) && !empty($cmD['mes'])) {
                    $periodoTxt = sprintf('%02d/%d', (int)$cmD['mes'], (int)$cmD['anio']);
                }
              ?>
                <span class="info-box-number">
                  <?= h($periodoTxt !== '' ? $periodoTxt : '—') ?>
                  <?= $cmD['estado'] === 'abierta' ? ' (abierta)' : ' (cerrada)' ?>
                </span>
                <div class="progress">
                  <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                  Neto mes: <?= h(fmt_money($cm['total_neto'])) ?>
                </span>
              <?php else: ?>
                <span class="info-box-number">Sin caja mensual abierta</span>
                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">&nbsp;</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Resumen del periodo filtrado -->
      <div class="row mb-3">
        <div class="col-12 col-md-4">
          <div class="card shadow-sm">
            <div class="card-body p-2">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="text-muted text-xs">Total abonado (filtro)</div>
                  <div class="font-weight-bold"><?= h(fmt_money($sumMonto)) ?></div>
                </div>
                <div><i class="fas fa-wallet fa-lg text-info"></i></div>
              </div>
              <div class="text-xs text-muted mt-1">
                Nº abonos: <?= (int)$total ?>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card shadow-sm">
            <div class="card-body p-2">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="text-muted text-xs">Total devuelto (filtro)</div>
                  <div class="font-weight-bold"><?= h(fmt_money($sumDevuelto)) ?></div>
                </div>
                <div><i class="fas fa-undo-alt fa-lg text-danger"></i></div>
              </div>
              <div class="text-xs text-muted mt-1">
                Incluye devoluciones vinculadas a los abonos filtrados.
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card shadow-sm">
            <div class="card-body p-2">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="text-muted text-xs">Neto en caja (filtro)</div>
                  <div class="font-weight-bold"><?= h(fmt_money($sumNeto)) ?></div>
                </div>
                <div><i class="fas fa-balance-scale fa-lg text-success"></i></div>
              </div>
              <div class="text-xs text-muted mt-1">
                Neto = Abonos − Devoluciones.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card principal de abonos -->
      <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <strong>Abonos (rastreo de ingresos)</strong>
          <span class="small text-muted">
            <?= $total > 0
              ? 'Mostrando ' . (int)$from . '–' . (int)$to . ' de ' . (int)$total . ' abonos'
              : 'No se encontraron abonos con los filtros actuales.' ?>
          </span>
        </div>
        <div class="card-body p-2">

          <!-- Filtros -->
          <form class="row g-2 mb-3" method="get" action="">
            <div class="col-12 col-md-4 col-lg-3">
              <label class="mb-1 small">Cliente / Doc. / Ticket / Ref.</label>
              <input type="text"
                     name="q"
                     class="form-control form-control-sm"
                     value="<?= h($q) ?>"
                     placeholder="Nombre, documento, ticket o referencia">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">Medio de pago</label>
              <select name="medio_id" class="form-control form-control-sm">
                <option value="0">Todos</option>
                <?php foreach ($medios as $mp): ?>
                  <option value="<?= (int)$mp['id'] ?>"
                    <?= $medioId === (int)$mp['id'] ? 'selected' : '' ?>>
                    <?= h($mp['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">Registrado por</label>
              <select name="usuario_id" class="form-control form-control-sm">
                <option value="0">Todos</option>
                <?php foreach ($usuarios as $us): 
                  $uid = (int)$us['id'];
                  $nom = trim((string)$us['nombres'] . ' ' . (string)$us['apellidos']);
                  if ($nom === '') $nom = (string)$us['usuario'];
                ?>
                  <option value="<?= $uid ?>" <?= $usuarioId === $uid ? 'selected' : '' ?>>
                    <?= h($nom) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">Fecha abono desde</label>
              <input type="date"
                     name="fdesde"
                     class="form-control form-control-sm"
                     value="<?= h($fdesde) ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">Fecha abono hasta</label>
              <input type="date"
                     name="fhasta"
                     class="form-control form-control-sm"
                     value="<?= h($fhasta) ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">Uso del abono</label>
              <select name="apl" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="sin_venta" <?= $aplEstado === 'sin_venta' ? 'selected' : '' ?>>Sin ventas</option>
                <option value="parcial"   <?= $aplEstado === 'parcial' ? 'selected' : '' ?>>Aplicado parcial</option>
                <option value="completo"  <?= $aplEstado === 'completo' ? 'selected' : '' ?>>Aplicado completo</option>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">Devoluciones</label>
              <select name="dev" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="con_dev" <?= $devEstado === 'con_dev' ? 'selected' : '' ?>>Con devoluciones</option>
                <option value="sin_dev" <?= $devEstado === 'sin_dev' ? 'selected' : '' ?>>Sin devoluciones</option>
              </select>
            </div>

            <div class="col-12 col-md-3 col-lg-2 d-flex align-items-end">
              <div class="btn-group btn-group-sm w-100" role="group">
                <button type="submit" class="btn btn-primary w-100">
                  Buscar
                </button>
                <?php if (
                  $q !== '' || $medioId || $usuarioId ||
                  $aplEstado !== '' || $devEstado !== '' ||
                  $fdesde !== '' || $fhasta !== ''
                ): ?>
                  <a href="<?= h($_SERVER['PHP_SELF'] ?? '') ?>" class="btn btn-outline-secondary">
                    Limpiar
                  </a>
                <?php endif; ?>
              </div>
              <input type="hidden" name="pp" value="<?= (int)$perPage ?>">
            </div>
          </form>

          <!-- Selector de tamaño de página -->
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2">
            <div class="small text-muted mb-2 mb-md-0">
              <?php if ($total > 0): ?>
                Mostrando <?= (int)$from ?>–<?= (int)$to ?> de <?= (int)$total ?> abonos
              <?php else: ?>
                No se encontraron abonos con los filtros actuales.
              <?php endif; ?>
            </div>
            <div class="text-md-end">
              <div class="btn-group btn-group-sm" role="group" aria-label="Abonos por página">
                <?php foreach ([10,20,50,100] as $pp):
                  $qsPP = $qsBase;
                  $qsPP['pp']  = $pp;
                  $qsPP['pag'] = 1;
                  $hrefPP = '?' . http_build_query($qsPP);
                ?>
                  <a href="<?= h($hrefPP) ?>"
                     class="btn btn-<?= $perPage === $pp ? 'primary' : 'outline-secondary' ?>">
                    <?= $pp ?> / pág
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Tabla de abonos -->
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="tblAbonos">
              <thead class="table-light">
                <tr>
                  <th style="width:60px">#</th>
                  <th style="width:170px">Fecha abono</th>
                  <th>Cliente</th>
                  <th style="width:180px">Ticket(s)</th>
                  <th style="width:160px">Medio</th>
                  <th style="width:170px">Caja diaria</th>
                  <th class="text-end" style="width:120px">Monto</th>
                  <th class="text-end" style="width:120px">Aplicado</th>
                  <th class="text-end" style="width:120px">Devuelto</th>
                  <th class="text-end" style="width:120px">Neto caja</th>
                  <th style="width:130px">Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($abonos): ?>
                  <?php $i = 1; foreach ($abonos as $r):
                    $abonoId = (int)$r['id'];
                    $fechaAb = fmt_dt($r['fecha_abono'] ?? null);
                    $cliNom  = trim((string)($r['cliente_nombre'] ?? ''));
                    $cliDoc  = '';
                    if (!empty($r['cliente_doc_tipo']) && !empty($r['cliente_doc_numero'])) {
                        $cliDoc = (string)$r['cliente_doc_tipo'] . ' ' . (string)$r['cliente_doc_numero'];
                    }
                    $cajaParts = [];
                    if (!empty($r['caja_codigo'])) {
                        $cajaParts[] = (string)$r['caja_codigo'];
                    }
                    if (!empty($r['caja_fecha'])) {
                        $cajaParts[] = fmt_date($r['caja_fecha']);
                    }
                    $cajaLbl = $cajaParts ? implode(' • ', $cajaParts) : '—';

                    $ticketPrincipal = trim((string)($r['ticket_principal'] ?? ''));
                    $numVentas       = (int)($r['num_ventas'] ?? 0);
                    $ticketLbl       = $ticketPrincipal !== '' ? $ticketPrincipal : '—';
                    if ($ticketPrincipal !== '' && $numVentas > 1) {
                        $ticketLbl .= ' +' . ($numVentas - 1);
                    }

                    $medio  = trim((string)($r['medio_nombre'] ?? ''));
                    $monto  = (float)($r['monto'] ?? 0);
                    $aplTot = (float)($r['monto_aplicado_total'] ?? 0);
                    $devTot = (float)($r['monto_devuelto_total'] ?? 0);
                    $neto   = $monto - $devTot;

                    $montoFmt = fmt_money($monto);
                    $aplFmt   = fmt_money($aplTot);
                    $devFmt   = fmt_money($devTot);
                    $netoFmt  = fmt_money($neto);

                    $estadoBadge = '';
                    $tol = 0.01;

                    if ($devTot > 0 && $devTot + 0.0001 >= $monto) {
                        $estadoBadge = '<span class="badge badge-danger">Devuelto total</span>';
                    } elseif ($devTot > 0 && $devTot < $monto - $tol) {
                        $estadoBadge = '<span class="badge badge-warning">Devuelto parcial</span>';
                    } elseif ($aplTot <= $tol) {
                        $estadoBadge = '<span class="badge badge-secondary">Sin aplicar</span>';
                    } elseif ($aplTot >= $monto - $tol) {
                        $estadoBadge = '<span class="badge badge-success">Aplicado completo</span>';
                    } else {
                        $estadoBadge = '<span class="badge badge-primary">Aplicado parcial</span>';
                    }
                  ?>
                    <!-- Fila principal -->
                    <tr class="js-row" data-id="<?= $abonoId ?>">
                      <td><?= $i++ ?></td>
                      <td><?= h($fechaAb !== '' ? $fechaAb : '—') ?></td>
                      <td>
                        <?php if ($cliNom !== ''): ?>
                          <div><?= h($cliNom) ?></div>
                          <?php if ($cliDoc !== ''): ?>
                            <div class="text-xs text-muted"><?= h($cliDoc) ?></div>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-muted">— Sin cliente —</span>
                        <?php endif; ?>
                      </td>
                      <td><?= h($ticketLbl) ?></td>
                      <td><?= h($medio !== '' ? $medio : '—') ?></td>
                      <td><?= h($cajaLbl) ?></td>
                      <td class="text-end"><?= h($montoFmt) ?></td>
                      <td class="text-end"><?= h($aplFmt) ?></td>
                      <td class="text-end"><?= h($devFmt) ?></td>
                      <td class="text-end"><?= h($netoFmt) ?></td>
                      <td><?= $estadoBadge ?></td>
                    </tr>

                    <!-- Fila detalle -->
                    <tr id="det-<?= $abonoId ?>" class="js-detail d-none">
                      <td colspan="11">
                        <div class="row g-2">
                          <div class="col-12 col-lg-7">
                            <div class="border rounded p-2">
                              <div class="fw-bold mb-1">Aplicaciones a ventas</div>
                              <?php if (!empty($r['aplicaciones'])): ?>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                      <tr>
                                        <th>Ticket</th>
                                        <th>Fecha venta</th>
                                        <th>Estado venta</th>
                                        <th class="text-end">Aplicado</th>
                                        <th class="text-end">Devuelto</th>
                                        <th class="text-end">Neto</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($r['aplicaciones'] as $ap):
                                        $tck = ticket_string($ap['serie'] ?? '', (int)($ap['numero'] ?? 0));
                                        $fvt = fmt_dt($ap['fecha_emision'] ?? null);
                                        $vst = (string)($ap['venta_estado'] ?? '');
                                        $map = (float)($ap['monto_aplicado'] ?? 0);
                                        $dva = (float)($ap['devuelto_monto'] ?? 0);
                                        $nap = $map - $dva;
                                      ?>
                                        <tr>
                                          <td><?= h($tck !== '' ? $tck : '—') ?></td>
                                          <td><?= h($fvt !== '' ? $fvt : '—') ?></td>
                                          <td><?= h($vst !== '' ? $vst : '—') ?></td>
                                          <td class="text-end"><?= h(fmt_money($map)) ?></td>
                                          <td class="text-end"><?= h(fmt_money($dva)) ?></td>
                                          <td class="text-end"><?= h(fmt_money($nap)) ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    </tbody>
                                  </table>
                                </div>
                              <?php else: ?>
                                <div class="text-muted small">— Sin ventas asociadas —</div>
                              <?php endif; ?>
                            </div>
                          </div>

                          <div class="col-12 col-lg-5">
                            <div class="border rounded p-2 mb-2">
                              <div class="fw-bold mb-1">Cliente</div>
                              <?php if ($cliNom !== ''): ?>
                                <div><strong><?= h($cliNom) ?></strong></div>
                                <?php if ($cliDoc !== ''): ?>
                                  <div class="text-xs text-muted mb-1"><?= h($cliDoc) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($r['cliente_telefono'])): ?>
                                  <div class="text-xs"><i class="fas fa-phone-alt"></i> <?= h($r['cliente_telefono']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($r['cliente_email'])): ?>
                                  <div class="text-xs"><i class="fas fa-envelope"></i> <?= h($r['cliente_email']) ?></div>
                                <?php endif; ?>
                              <?php else: ?>
                                <div class="text-muted small">— Sin datos de cliente —</div>
                              <?php endif; ?>
                            </div>

                            <div class="border rounded p-2">
                              <div class="fw-bold mb-1">Detalle del abono</div>
                              <div class="text-xs">
                                <strong>Referencia:</strong>
                                <?= h($r['referencia'] !== null && $r['referencia'] !== '' ? $r['referencia'] : '—') ?>
                              </div>
                              <div class="text-xs mt-1">
                                <strong>Observación:</strong>
                                <?= h($r['observacion'] !== null && $r['observacion'] !== '' ? $r['observacion'] : '—') ?>
                              </div>
                              <div class="text-xs mt-1">
                                <strong>Registrado por:</strong>
                                <?php
                                  $unom = trim((string)($r['usuario_nombres'] ?? '') . ' ' . (string)($r['usuario_apellidos'] ?? ''));
                                  if ($unom === '' && !empty($r['usuario_username'])) {
                                      $unom = (string)$r['usuario_username'];
                                  }
                                ?>
                                <?= h($unom !== '' ? $unom : '—') ?>
                              </div>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="11" class="text-muted small">Sin abonos registrados.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <?php if ($totalPages > 1): ?>
            <div class="mt-2 d-flex justify-content-center">
              <nav aria-label="Paginación de abonos">
                <ul class="pagination pagination-sm mb-0">
                  <?php
                    $prevPage = $page - 1;
                    $qsPrev = $qsBase;
                    $qsPrev['pag'] = $prevPage;
                    $hrefPrev = '?' . http_build_query($qsPrev);

                    $pagesWindow = [];
                    if ($totalPages <= 7) {
                      for ($i = 1; $i <= $totalPages; $i++) {
                        $pagesWindow[] = $i;
                      }
                    } else {
                      $pagesWindow[] = 1;
                      $fromW = max(2, $page - 2);
                      $toW   = min($totalPages - 1, $page + 2);
                      if ($fromW > 2) {
                        $pagesWindow[] = 'gap';
                      }
                      for ($i = $fromW; $i <= $toW; $i++) {
                        $pagesWindow[] = $i;
                      }
                      if ($toW < $totalPages - 1) {
                        $pagesWindow[] = 'gap';
                      }
                      $pagesWindow[] = $totalPages;
                    }
                  ?>

                  <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $page <= 1 ? '#' : h($hrefPrev) ?>" tabindex="-1">&laquo;</a>
                  </li>

                  <?php foreach ($pagesWindow as $p): ?>
                    <?php if ($p === 'gap'): ?>
                      <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php else:
                      $qsP = $qsBase;
                      $qsP['pag'] = $p;
                      $hrefP = '?' . http_build_query($qsP);
                    ?>
                      <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= h($hrefP) ?>"><?= (int)$p ?></a>
                      </li>
                    <?php endif; ?>
                  <?php endforeach; ?>

                  <?php
                    $nextPage = $page + 1;
                    $qsNext = $qsBase;
                    $qsNext['pag'] = $nextPage;
                    $hrefNext = '?' . http_build_query($qsNext);
                  ?>
                  <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $page >= $totalPages ? '#' : h($hrefNext) ?>">&raquo;</a>
                  </li>
                </ul>
              </nav>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </section>
</div>

<?php if (is_file(__DIR__ . '/index.js')): ?>
  <script type="module" src="<?= h(rel('modules/' . basename(__DIR__) . '/index.js?v=1')) ?>"></script>
<?php endif; ?>

<?php if (is_file(__DIR__ . '/style.css')): ?>
  <link rel="stylesheet" href="<?= h(rel('modules/' . basename(__DIR__) . '/style.css?v=1')) ?>">
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
