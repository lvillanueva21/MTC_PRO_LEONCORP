<?php 
// modules/reporte_ventas/index.php — Reporte avanzado de finanzas (v2)

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

/* ========= Config de la página ========= */
$PAGE_TITLE = 'Reportes de Finanzas (Avanzado)';
$ALLOWED_ROLE_IDS   = [3, 4];                        // Recepción, Administración
$ALLOWED_ROLE_NAMES = ['Recepción', 'Administración'];

/* ========= Guardas de acceso ========= */
acl_require_ids($ALLOWED_ROLE_IDS);
if (function_exists('verificarPermiso')) {
  verificarPermiso($ALLOWED_ROLE_NAMES);
}

/* ========= Usuario y DB ========= */
$u = currentUser();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');
$usrNom = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
$empNom = (string)($u['empresa']['nombre'] ?? '—');
$empresaId = (int)($u['empresa']['id'] ?? 0);
$empLogoRel = '';

try {
  $logoFromSession = isset($u['empresa']['logo_path']) ? trim((string)$u['empresa']['logo_path']) : '';
  if ($logoFromSession === '' && $empresaId > 0) {
    $stLogo = $db->prepare("SELECT logo_path FROM mtp_empresas WHERE id=? LIMIT 1");
    $stLogo->bind_param('i', $empresaId);
    $stLogo->execute();
    if ($rLogo = $stLogo->get_result()->fetch_assoc()) {
      $logoFromSession = trim((string)($rLogo['logo_path'] ?? ''));
    }
    $stLogo->close();
  }

  if ($logoFromSession !== '') {
    $rel = '../../' . ltrim($logoFromSession, '/');
    if (is_file(__DIR__ . '/../../' . ltrim($logoFromSession, '/'))) {
      $empLogoRel = $rel;
    }
  }

  if ($empLogoRel === '') {
    $fallback = '../../dist/img/AdminLTELogo.png';
    if (is_file(__DIR__ . '/../../dist/img/AdminLTELogo.png')) {
      $empLogoRel = $fallback;
    }
  }
} catch (Throwable $e) {
  // Silencioso.
}

/* ========= Helpers ========= */
function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Rutas relativas portables (sin depender de raíz del dominio).
 */
$appFolder = basename(dirname(dirname(__DIR__)));              // p.ej. 'ventas'
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // p.ej. 'ventas/modules/reporte_ventas' o 'modules/reporte_ventas'
$parts     = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx       = array_search($appFolder, $parts, true);
$depth     = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1));
$APP_ROOT_REL = str_repeat('../', max(0, $depth));

function rel(string $path) {
  global $APP_ROOT_REL;
  return $APP_ROOT_REL . ltrim($path, '/');
}

/* ========= Lógica del módulo ========= */
require_once __DIR__ . '/funciones.php';

$empId = (int)($u['empresa']['id'] ?? 0);

// Contexto empresa y cajas
$empresaInfo       = $empId > 0 ? obtener_empresa_info($db, $empId) : null;
$cajaMensualActual = $empId > 0 ? obtener_caja_mensual_actual($db, $empId) : null;
$cajaDiariaActual  = $empId > 0 ? obtener_caja_diaria_actual($db, $empId) : null;

// Combos
$servicios   = $empId > 0 ? listar_servicios_empresa($db, $empId) : [];
$series      = $empId > 0 ? listar_series_empresa($db, $empId) : [];
$usuariosRep = $empId > 0 ? listar_usuarios_empresa_con_ventas($db, $empId) : [];
$mediosPago  = listar_medios_pago_activos($db);

// Filtros
$q          = trim($_GET['q'] ?? '');
$servicioId = isset($_GET['servicio_id']) ? (int)$_GET['servicio_id'] : 0;
$estado     = isset($_GET['estado']) ? (string)$_GET['estado'] : '';
$estadoVals = ['pagado', 'pendiente', 'devolucion_parcial', 'devolucion_total', 'anulada'];
if (!in_array($estado, $estadoVals, true)) {
  $estado = '';
}
$fdesde          = isset($_GET['fdesde']) ? (string)$_GET['fdesde'] : '';
$fhasta          = isset($_GET['fhasta']) ? (string)$_GET['fhasta'] : '';
$serieId         = isset($_GET['serie_id']) ? (int)$_GET['serie_id'] : 0;
$usuarioId       = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$medioId         = isset($_GET['medio_id']) ? (int)$_GET['medio_id'] : 0;
$soloCajaActual  = isset($_GET['caja_actual']) && $_GET['caja_actual'] === '1';
$cajaDiariaId    = 0;
$cajaActualCheck = false;

if ($soloCajaActual && $cajaDiariaActual && isset($cajaDiariaActual['id'])) {
  $cajaDiariaId    = (int)$cajaDiariaActual['id'];
  $cajaActualCheck = true;
}

// Paginación
$perPage   = isset($_GET['pp']) ? (int)$_GET['pp'] : 10;
$allowedPP = [10, 20, 50, 100];
if (!in_array($perPage, $allowedPP, true)) {
  $perPage = 10;
}
$page = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
if ($page < 1) {
  $page = 1;
}

// Opciones para consultas
$opts = [
  'q'             => $q,
  'servicio_id'   => $servicioId,
  'estado'        => $estado,
  'fdesde'        => $fdesde,
  'fhasta'        => $fhasta,
  'pagina'        => $page,
  'por_pagina'    => $perPage,
  'serie_id'      => $serieId,
  'usuario_id'    => $usuarioId,
  'medio_id'      => $medioId,
  'caja_diaria_id'=> $cajaDiariaId,
];

// Resumen de ventas según filtros
$resumenVentas = $empId > 0 ? obtener_resumen_ventas($db, $empId, $opts) : [
  'total_ventas'   => 0,
  'total_emitidas' => 0,
  'total_anuladas' => 0,
  'total_bruto'    => 0.0,
  'total_pagado'   => 0.0,
  'total_devuelto' => 0.0,
  'total_saldo'    => 0.0,
  'total_neto'     => 0.0,
];

// Resumen de caja mensual y diaria actual
$resumenMensual = ['total_ventas' => 0, 'total_bruto' => 0.0, 'total_pagado' => 0.0, 'total_devuelto' => 0.0, 'total_saldo' => 0.0];
if ($cajaMensualActual && isset($cajaMensualActual['id'])) {
  $resumenMensual = obtener_resumen_caja_mensual($db, $empId, (int)$cajaMensualActual['id']);
}

$resumenDiaria = ['total_ventas' => 0, 'total_bruto' => 0.0, 'total_pagado' => 0.0, 'total_devuelto' => 0.0, 'total_saldo' => 0.0];
if ($cajaDiariaActual && isset($cajaDiariaActual['id'])) {
  $resumenDiaria = obtener_resumen_caja_diaria($db, $empId, (int)$cajaDiariaActual['id']);
}

// Datos de ventas paginados
$result     = $empId > 0 ? buscar_ventas_con_detalles($db, $empId, $opts) : [
  'rows'        => [],
  'total'       => 0,
  'page'        => 1,
  'per_page'    => $perPage,
  'total_pages' => 0,
  'from'        => 0,
  'to'          => 0,
];
$ventas     = $result['rows'];
$total      = $result['total'];
$page       = $result['page'];
$perPage    = $result['per_page'];
$totalPages = $result['total_pages'];
$from       = $result['from'];
$to         = $result['to'];

// Query string base para mantener filtros
$qsBase = [
  'q'           => $q !== '' ? $q : null,
  'servicio_id' => $servicioId ?: null,
  'estado'      => $estado !== '' ? $estado : null,
  'fdesde'      => $fdesde !== '' ? $fdesde : null,
  'fhasta'      => $fhasta !== '' ? $fhasta : null,
  'serie_id'    => $serieId ?: null,
  'usuario_id'  => $usuarioId ?: null,
  'medio_id'    => $medioId ?: null,
  'caja_actual' => $cajaActualCheck ? 1 : null,
  'pp'          => $perPage,
];
$tmp = [];
foreach ($qsBase as $k => $v) {
  if ($v !== null && $v !== '') {
    $tmp[$k] = $v;
  }
}
$qsBase = $tmp;

include __DIR__ . '/../../includes/header.php';
?>
<style id="voucher-logo-fix">
  #voucherBody .v-head{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:6px;
  }
  #voucherBody .v-logo{
    width:64px;
    height:64px;
    object-fit:contain;
    border-radius:50%;
    border:1px solid #e5e7eb;
  }
</style>
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
      <!-- Cards resumen superiores -->
      <div class="row mb-3">
        <!-- Empresa actual -->
        <div class="col-md-6 col-lg-3 mb-3">
          <div class="info-box bg-light shadow-sm summary-card">
            <span class="info-box-icon bg-primary elevation-1">
              <i class="fas fa-building"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text text-muted">Empresa actual</span>
              <span class="info-box-number">
                <?= h($empresaInfo['nombre'] ?? 'Sin empresa') ?>
              </span>
              <div class="text-muted small mt-1">
                <?php if (!empty($empresaInfo['ruc'])): ?>
                  RUC <?= h($empresaInfo['ruc']) ?><br>
                <?php endif; ?>
                <?php if (!empty($empresaInfo['departamento'])): ?>
                  <?= h($empresaInfo['departamento']) ?>
                <?php endif; ?>
                <?php if (!empty($empresaInfo['direccion'])): ?>
                  <?= !empty($empresaInfo['departamento']) ? ' · ' : '' ?><?= h($empresaInfo['direccion']) ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Caja mensual actual -->
        <div class="col-md-6 col-lg-3 mb-3">
          <div class="info-box bg-light shadow-sm summary-card">
            <span class="info-box-icon bg-success elevation-1">
              <i class="fas fa-calendar-alt"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text text-muted">Caja mensual actual</span>
              <?php if ($cajaMensualActual): ?>
                <?php
                  $periodoStr = '';
                  if (isset($cajaMensualActual['anio'], $cajaMensualActual['mes'])) {
                    $y = (int)$cajaMensualActual['anio'];
                    $m = (int)$cajaMensualActual['mes'];
                    $periodoStr = sprintf('%04d-%02d', $y, $m);
                  }
                ?>
                <span class="info-box-number">
                  <?= h($periodoStr !== '' ? $periodoStr : '—') ?>
                  <?php if (!empty($cajaMensualActual['codigo'])): ?>
                    <small class="text-muted">(<?= h($cajaMensualActual['codigo']) ?>)</small>
                  <?php endif; ?>
                </span>
                <div class="small mt-1">
                  <span class="badge badge-<?= ($cajaMensualActual['estado'] === 'abierta' ? 'success' : 'secondary') ?>">
                    <?= h(ucfirst($cajaMensualActual['estado'])) ?>
                  </span>
                  <?php if ($resumenMensual['total_ventas'] > 0): ?>
                    <span class="d-block text-muted mt-1">
                      Ventas: <?= (int)$resumenMensual['total_ventas'] ?> ·
                      Bruto: <?= h(fmt_money($resumenMensual['total_bruto'])) ?>
                    </span>
                  <?php else: ?>
                    <span class="d-block text-muted mt-1">Sin ventas registradas</span>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <span class="info-box-number">Sin caja abierta</span>
                <div class="small text-muted mt-1">No se encontró caja mensual en estado abierta.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Caja diaria actual -->
        <div class="col-md-6 col-lg-3 mb-3">
          <div class="info-box bg-light shadow-sm summary-card">
            <span class="info-box-icon bg-warning elevation-1">
              <i class="fas fa-cash-register"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text text-muted">Caja diaria actual</span>
              <?php if ($cajaDiariaActual): ?>
                <span class="info-box-number">
                  <?= h(isset($cajaDiariaActual['fecha']) ? fmt_date($cajaDiariaActual['fecha']) : '—') ?>
                  <?php if (!empty($cajaDiariaActual['codigo'])): ?>
                    <small class="text-muted">(<?= h($cajaDiariaActual['codigo']) ?>)</small>
                  <?php endif; ?>
                </span>
                <div class="small mt-1">
                  <span class="badge badge-<?= ($cajaDiariaActual['estado'] === 'abierta' ? 'success' : 'secondary') ?>">
                    <?= h(ucfirst($cajaDiariaActual['estado'])) ?>
                  </span>
                  <?php if ($resumenDiaria['total_ventas'] > 0): ?>
                    <span class="d-block text-muted mt-1">
                      Ventas: <?= (int)$resumenDiaria['total_ventas'] ?> ·
                      Neto: <?= h(fmt_money($resumenDiaria['total_pagado'])) ?>
                    </span>
                  <?php else: ?>
                    <span class="d-block text-muted mt-1">Sin ventas en la caja actual</span>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <span class="info-box-number">Sin caja abierta</span>
                <div class="small text-muted mt-1">No se encontró caja diaria en estado abierta.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Resumen de ventas del filtro -->
        <div class="col-md-6 col-lg-3 mb-3">
          <div class="info-box bg-light shadow-sm summary-card">
            <span class="info-box-icon bg-info elevation-1">
              <i class="fas fa-chart-line"></i>
            </span>
            <div class="info-box-content">
              <span class="info-box-text text-muted">Resumen del filtro</span>
              <span class="info-box-number">
                <?= (int)$resumenVentas['total_ventas'] ?> ventas
              </span>
              <div class="small mt-1 text-muted">
                Emitidas: <?= (int)$resumenVentas['total_emitidas'] ?> ·
                Devolucion total: <?= (int)$resumenVentas['total_anuladas'] ?><br>
                Bruto: <?= h(fmt_money($resumenVentas['total_bruto'])) ?><br>
                Neto: <?= h(fmt_money($resumenVentas['total_neto'])) ?> ·
                Saldo: <?= h(fmt_money($resumenVentas['total_saldo'])) ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card principal de listado -->
      <div class="card shadow-sm">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
          <strong>Ventas detalladas</strong>
          <span class="small text-muted">
            Reporte avanzado por empresa y filtros seleccionados
          </span>
        </div>
        <div class="card-body p-2">
          <!-- Filtros -->
          <form class="row g-2 mb-3" method="get" action="">
            <div class="col-12 col-md-4 col-lg-3">
              <label class="mb-1 small">
                <i class="fas fa-search mr-1"></i>Nombre y documento
              </label>
              <input type="text"
                     name="q"
                     class="form-control form-control-sm"
                     value="<?= h($q) ?>"
                     placeholder="Cliente o contratante">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-concierge-bell mr-1"></i>Servicio
              </label>
              <select name="servicio_id" class="form-control form-control-sm">
                <option value="0">Todos</option>
                <?php foreach ($servicios as $svc): ?>
                  <option value="<?= (int)$svc['id'] ?>" <?= $servicioId === (int)$svc['id'] ? 'selected' : '' ?>>
                    <?= h($svc['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-ticket-alt mr-1"></i>Serie
              </label>
              <select name="serie_id" class="form-control form-control-sm">
                <option value="0">Todas</option>
                <?php foreach ($series as $s): ?>
                  <option value="<?= (int)$s['id'] ?>" <?= $serieId === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= h($s['serie']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-user mr-1"></i>Usuario
              </label>
              <select name="usuario_id" class="form-control form-control-sm">
                <option value="0">Todos</option>
                <?php foreach ($usuariosRep as $usr): 
                  $nombreUsr = trim(($usr['nombres'] ?? '') . ' ' . ($usr['apellidos'] ?? ''));
                  if ($nombreUsr === '') {
                    $nombreUsr = (string)($usr['usuario'] ?? '');
                  }
                ?>
                  <option value="<?= (int)$usr['id'] ?>" <?= $usuarioId === (int)$usr['id'] ? 'selected' : '' ?>>
                    <?= h($nombreUsr !== '' ? $nombreUsr : 'Usuario ' . (int)$usr['id']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-credit-card mr-1"></i>Medio pago
              </label>
              <select name="medio_id" class="form-control form-control-sm">
                <option value="0">Todos</option>
                <?php foreach ($mediosPago as $mp): ?>
                  <option value="<?= (int)$mp['id'] ?>" <?= $medioId === (int)$mp['id'] ? 'selected' : '' ?>>
                    <?= h($mp['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-flag mr-1"></i>Estado
              </label>
              <select name="estado" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="pagado"   <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                <option value="pendiente"<?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="devolucion_parcial" <?= $estado === 'devolucion_parcial' ? 'selected' : '' ?>>Devolucion parcial</option>
                <option value="devolucion_total"   <?= ($estado === 'devolucion_total' || $estado === 'anulada') ? 'selected' : '' ?>>Devolucion total</option>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-calendar-day mr-1"></i>Fecha venta desde
              </label>
              <input type="date"
                     name="fdesde"
                     class="form-control form-control-sm"
                     value="<?= h($fdesde) ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-calendar-day mr-1"></i>Fecha venta hasta
              </label>
              <input type="date"
                     name="fhasta"
                     class="form-control form-control-sm"
                     value="<?= h($fhasta) ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-2 d-flex align-items-center">
              <div class="form-check mt-3">
                <input type="checkbox"
                       class="form-check-input"
                       id="chkCajaActual"
                       name="caja_actual"
                       value="1"
                       <?= $cajaActualCheck ? 'checked' : '' ?>>
                <label class="form-check-label small" for="chkCajaActual">
                  <i class="fas fa-cash-register mr-1"></i>Solo caja actual
                </label>
              </div>
            </div>

            <div class="col-12 col-md-3 col-lg-2 d-flex align-items-end">
              <div class="btn-group btn-group-sm w-100" role="group">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-filter mr-1"></i>Filtrar
                </button>
                <?php if ($q !== '' || $servicioId || $estado !== '' || $fdesde !== '' || $fhasta !== '' || $serieId || $usuarioId || $medioId || $cajaActualCheck): ?>
                  <a href="<?= h($_SERVER['PHP_SELF'] ?? '') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-eraser"></i>
                  </a>
                <?php endif; ?>
              </div>
              <input type="hidden" name="pp" value="<?= (int)$perPage ?>">
            </div>
          </form>

          <!-- Resumen y tamaño de página -->
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2">
            <div class="small text-muted mb-2 mb-md-0">
              <?php if ($total > 0): ?>
                Mostrando <?= (int)$from ?>–<?= (int)$to ?> de <?= (int)$total ?> ventas
              <?php else: ?>
                No se encontraron ventas con los filtros actuales.
              <?php endif; ?>
            </div>
            <div class="text-md-end">
              <div class="btn-group btn-group-sm" role="group" aria-label="Ventas por página">
                <?php foreach ([10, 20, 50, 100] as $pp): 
                  $qsPP        = $qsBase;
                  $qsPP['pp']  = $pp;
                  $qsPP['pag'] = 1;
                  $hrefPP      = '?' . http_build_query($qsPP);
                ?>
                  <a href="<?= h($hrefPP) ?>"
                     class="btn btn-<?= $perPage === $pp ? 'primary' : 'outline-secondary' ?>">
                    <?= $pp ?> / pág
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0"
                   id="tblVentas">
              <thead class="table-light">
                <tr>
                  <th style="width:60px">#</th>
                  <th style="width:180px">Ticket</th>
                  <th>Cliente</th>
                  <th>Contratante</th>
                  <th>Servicio principal</th>
                  <th style="width:180px">Fecha emisión</th>
                  <th style="width:120px" class="text-end">Total</th>
                  <th style="width:120px" class="text-end">Ingresado neto</th>
                  <th style="width:120px" class="text-end">Saldo</th>
                  <th style="width:160px">Estado</th>
                  <th style="width:180px">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($ventas): ?>
                  <?php $i = $from; foreach ($ventas as $r):
                    $ticket   = ticket_string($r['serie'] ?? '', (int)$r['numero']);
                    $cliente  = trim((string)($r['cliente'] ?? ''));
                    $cliDoc   = '';
                    if (!empty($r['cliente_doc_tipo']) && !empty($r['cliente_doc_numero'])) {
                      $cliDoc = trim($r['cliente_doc_tipo'] . ' ' . $r['cliente_doc_numero']);
                    }
                    $clienteLbl = $cliente !== '' ? $cliente : '—';
                    if ($cliDoc !== '') {
                      $clienteLbl .= ' (' . $cliDoc . ')';
                    }

                    $contrNom = trim((string)(($r['contratante_nombres'] ?? '') . ' ' . ($r['contratante_apellidos'] ?? '')));
                    $contrDoc = '';
                    if (!empty($r['contratante_doc_tipo']) && !empty($r['contratante_doc_numero'])) {
                      $contrDoc = trim($r['contratante_doc_tipo'] . ' ' . $r['contratante_doc_numero']);
                    }
                    $contrLbl = $contrNom !== '' ? $contrNom : '—';
                    if ($contrDoc !== '') {
                      $contrLbl .= ' (' . $contrDoc . ')';
                    }

                    $servicio = trim((string)($r['servicio_principal'] ?? ''));
                    $fecha    = fmt_dt($r['fecha_emision'] ?? null);
                    $total    = fmt_money($r['total'] ?? 0);
                    $ingNetoV = max(0.0, (float)($r['total_pagado'] ?? 0));
                    $ingNeto  = fmt_money($ingNetoV);
                    $saldoNum = (float)($r['saldo'] ?? 0);
                    $saldoFmt = fmt_money($saldoNum);
                    $estadoV  = (string)($r['estado'] ?? '');
                    $devueltoNum = (float)($r['total_devuelto'] ?? 0);
                    $ventaId  = (int)$r['id'];
                    $canAbonar = ($estadoV !== 'ANULADA') && ($saldoNum > 0.000001);

                    if ($estadoV === 'ANULADA') {
                      $badge = '<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Devolucion total</span>';
                    } elseif ($devueltoNum > 0.000001) {
                      $badge = '<span class="badge badge-info"><i class="fas fa-undo-alt mr-1"></i>Devolucion parcial</span>';
                    } elseif ($saldoNum > 0) {
                      $badge = '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Pendiente</span>';
                    } else {
                      $badge = '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Pagado</span>';
                    }

                    $tieneAbonos      = !empty($r['abonos']);
                    $tieneConductores = !empty($r['conductores']);
                    $tieneDet         = !empty($r['detalles']);
                  ?>
                    <!-- Fila principal -->
                    <tr class="js-row" data-id="<?= $ventaId ?>">
                      <td><?= (int)$i++ ?></td>
                      <td>
                        <span class="font-weight-bold"><?= h($ticket) ?></span><br>
                        <span class="small text-muted">
                          <?= h($r['tipo_comprobante'] ?? 'TICKET') ?>
                        </span>
                      </td>
                      <td><?= h($clienteLbl) ?></td>
                      <td><?= h($contrLbl) ?></td>
                      <td>
                        <?= h($servicio !== '' ? $servicio : '—') ?>
                        <br>
                        <span class="small text-muted">
                          <?php if ($tieneDet): ?>
                            <i class="fas fa-list mr-1"></i><?= count($r['detalles']) ?> ítems
                          <?php else: ?>
                            Sin detalle
                          <?php endif; ?>
                        </span>
                      </td>
                      <td><?= h($fecha !== '' ? $fecha : '—') ?></td>
                      <td class="text-end"><?= h($total) ?></td>
                      <td class="text-end"><?= h($ingNeto) ?></td>
                      <td class="text-end"><?= h($saldoFmt) ?></td>
                      <td>
                        <?= $badge ?>
                        <div class="small text-muted mt-1">
                          <?php if ($tieneAbonos): ?>
                            <i class="fas fa-wallet mr-1"></i>Con abonos
                          <?php else: ?>
                            <i class="far fa-wallet mr-1"></i>Sin abonos
                          <?php endif; ?>
                          <?php if ($tieneConductores): ?>
                            · <i class="fas fa-id-card mr-1"></i>Conductores
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                          <button type="button"
                                  class="btn btn-outline-secondary js-detalle"
                                  data-id="<?= $ventaId ?>"
                                  data-ticket="<?= h($ticket) ?>">
                            <i class="fas fa-search mr-1"></i>Detalle
                          </button>
                          <button type="button"
                                  class="btn btn-outline-dark js-voucher-original"
                                  data-id="<?= $ventaId ?>"
                                  title="Comprobante original">
                            <i class="fas fa-receipt"></i>
                          </button>
                          <button type="button"
                                  class="btn btn-outline-info js-voucher-actual"
                                  data-id="<?= $ventaId ?>"
                                  title="Comprobante actual">
                            <i class="fas fa-sync-alt"></i>
                          </button>
                          <?php if ($canAbonar): ?>
                            <button type="button"
                                    class="btn btn-outline-primary js-abonar"
                                    data-id="<?= $ventaId ?>"
                                    data-ticket="<?= h($ticket) ?>">
                              <i class="fas fa-wallet mr-1"></i>Abonar
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>

                    <!-- Fila secundaria (plegable) -->
                    <tr id="det-<?= $ventaId ?>" class="js-detail d-none">
                      <td colspan="11">
                        <div class="row g-2">
                          <!-- Detalle de servicios -->
                          <div class="col-12 col-lg-5">
                            <div class="border rounded p-2">
                              <div class="fw-bold mb-1">
                                <i class="fas fa-list mr-1"></i>Detalle de servicios
                              </div>
                              <?php if (!empty($r['detalles'])): ?>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                      <tr>
                                        <th>Servicio</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">P. Unit</th>
                                        <th class="text-end">Desc.</th>
                                        <th class="text-end">Total</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($r['detalles'] as $d): ?>
                                        <tr>
                                          <td>
                                            <?= h($d['servicio_nombre']) ?>
                                            <?php if (!empty($d['descripcion'])): ?>
                                              <br><span class="small text-muted"><?= h($d['descripcion']) ?></span>
                                            <?php endif; ?>
                                          </td>
                                          <td class="text-center"><?= h($d['cantidad']) ?></td>
                                          <td class="text-end"><?= h(fmt_money($d['precio_unitario'])) ?></td>
                                          <td class="text-end"><?= h(fmt_money($d['descuento'])) ?></td>
                                          <td class="text-end"><?= h(fmt_money($d['total_linea'])) ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    </tbody>
                                  </table>
                                </div>
                              <?php else: ?>
                                <div class="text-muted small">Sin detalle registrado.</div>
                              <?php endif; ?>
                            </div>
                          </div>

                          <!-- Abonos -->
                          <div class="col-12 col-lg-4">
                            <div class="border rounded p-2">
                              <div class="fw-bold mb-1">
                                <i class="fas fa-wallet mr-1"></i>Abonos y devoluciones
                              </div>
                              <?php if (!empty($r['abonos'])): ?>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                      <tr>
                                        <th>Medio</th>
                                        <th>Referencia</th>
                                        <th class="text-end">Monto</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($r['abonos'] as $a): 
                                        $medio = trim((string)($a['medio'] ?? ''));
                                        $ref   = trim((string)($a['referencia'] ?? ''));
                                        $montoAplNum = (float)($a['monto_aplicado'] ?? $a['monto'] ?? 0);
                                        $monto = fmt_money($montoAplNum);
                                        $fec   = fmt_dt($a['fecha'] ?? null);
                                        $devNum = (float)($a['devuelto_monto'] ?? 0);
                                        if ($devNum > 0) {
                                          if ($devNum + 0.00001 >= $montoAplNum) {
                                            $devBadge = '<span class="badge badge-danger">Devuelto total</span> <span class="badge badge-secondary">' . h(fmt_money($devNum)) . '</span>';
                                          } else {
                                            $devBadge = '<span class="badge badge-warning">Devuelto parcial</span> <span class="badge badge-secondary">' . h(fmt_money($devNum)) . '</span>';
                                          }
                                        } else {
                                          $devBadge = '—';
                                        }
                                      ?>
                                        <tr>
                                          <td><?= h($medio !== '' ? $medio : '—') ?></td>
                                          <td><?= h($ref !== '' ? $ref : '—') ?></td>
                                          <td class="text-end"><?= h($monto) ?></td>
                                          <td><?= h($fec !== '' ? $fec : '—') ?></td>
                                          <td><?= $devBadge ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    </tbody>
                                  </table>
                                </div>
                              <?php else: ?>
                                <div class="text-muted small">Sin abonos registrados.</div>
                              <?php endif; ?>
                            </div>
                          </div>

                          <!-- Conductores y otros datos -->
                          <div class="col-12 col-lg-3">
                            <div class="border rounded p-2 mb-2">
                              <div class="fw-bold mb-1">
                                <i class="fas fa-id-card mr-1"></i>Conductores
                              </div>
                              <?php if (!empty($r['conductores'])): ?>
                                <div class="table-responsive">
                                  <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                      <tr>
                                        <th>Doc</th>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($r['conductores'] as $c): 
                                        $doc = trim(($c['doc_tipo'] ?? '') . ' ' . ($c['doc_numero'] ?? ''));
                                        $nom = trim(($c['nombres'] ?? '') . ' ' . ($c['apellidos'] ?? ''));
                                        $tel = trim((string)($c['telefono'] ?? ''));
                                      ?>
                                        <tr>
                                          <td><?= h($doc !== '' ? $doc : '—') ?></td>
                                          <td><?= h($nom !== '' ? $nom : '—') ?></td>
                                          <td><?= h($tel !== '' ? $tel : '—') ?></td>
                                        </tr>
                                      <?php endforeach; ?>
                                    </tbody>
                                  </table>
                                </div>
                              <?php else: ?>
                                <div class="text-muted small">Sin conductores registrados.</div>
                              <?php endif; ?>
                            </div>

                            <div class="border rounded p-2">
                              <div class="fw-bold mb-1">
                                <i class="fas fa-info-circle mr-1"></i>Información adicional
                              </div>
                              <div class="small text-muted">
                                <?php if (!empty($r['caja_diaria_id'])): ?>
                                  Caja diaria ID: <?= (int)$r['caja_diaria_id'] ?><br>
                                <?php endif; ?>
                                <?php if (!empty($r['observacion'])): ?>
                                  Observación: <?= h($r['observacion']) ?><br>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="11" class="text-muted small">Sin ventas registradas.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
              <div class="mt-2 d-flex justify-content-center">
                <nav aria-label="Paginación de ventas">
                  <ul class="pagination pagination-sm mb-0">
                    <?php
                      // Página anterior
                      $prevPage    = $page - 1;
                      $qsPrev      = $qsBase;
                      $qsPrev['pag'] = $prevPage;
                      $hrefPrev    = '?' . http_build_query($qsPrev);

                      // Ventana de páginas
                      $pagesWindow = [];
                      if ($totalPages <= 7) {
                        for ($iP = 1; $iP <= $totalPages; $iP++) {
                          $pagesWindow[] = $iP;
                        }
                      } else {
                        $pagesWindow[] = 1;
                        $fromW = max(2, $page - 2);
                        $toW   = min($totalPages - 1, $page + 2);
                        if ($fromW > 2) {
                          $pagesWindow[] = 'gap';
                        }
                        for ($iP = $fromW; $iP <= $toW; $iP++) {
                          $pagesWindow[] = $iP;
                        }
                        if ($toW < $totalPages - 1) {
                          $pagesWindow[] = 'gap';
                        }
                        $pagesWindow[] = $totalPages;
                      }
                    ?>

                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                      <a class="page-link" href="<?= $page <= 1 ? '#' : h($hrefPrev) ?>">&laquo;</a>
                    </li>

                    <?php foreach ($pagesWindow as $p): ?>
                      <?php if ($p === 'gap'): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                      <?php else:
                        $qsP        = $qsBase;
                        $qsP['pag'] = $p;
                        $hrefP      = '?' . http_build_query($qsP);
                      ?>
                        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                          <a class="page-link" href="<?= h($hrefP) ?>"><?= (int)$p ?></a>
                        </li>
                      <?php endif; ?>
                    <?php endforeach; ?>

                    <?php
                      $nextPage    = $page + 1;
                      $qsNext      = $qsBase;
                      $qsNext['pag'] = $nextPage;
                      $hrefNext    = '?' . http_build_query($qsNext);
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
    </div>
  </section>
</div>

<!-- ==== Modal: Voucher / Recibo de venta ==== -->
<div class="modal fade" id="voucherModal" tabindex="-1" role="dialog" aria-labelledby="voucherModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-dark text-white">
        <h5 class="modal-title" id="voucherModalTitle"><i class="fas fa-receipt mr-2"></i>Voucher de venta</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="voucherBody"><!-- render dinámico --></div>
      </div>
      <div class="modal-footer py-2 flex-wrap">
        <div class="mr-auto d-flex align-items-center" style="gap:8px;">
          <label for="voucherSize" class="small mb-0">Tamaño</label>
          <select id="voucherSize" class="form-select form-select-sm" style="min-width:160px">
            <option value="a4">A4 (210 × 297 mm)</option>
            <option value="ticket80" selected>Ticket 80 mm</option>
            <option value="ticket58">Ticket 58 mm</option>
          </select>
        </div>
        <div class="d-flex" style="gap:8px;">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary btn-sm" id="voucherPrint"><i class="fas fa-print mr-1"></i>Imprimir</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ==== Modal de mensajes (igual a caja) ==== -->
<div class="modal fade" id="msgModal" tabindex="-1" role="dialog" aria-labelledby="msgModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="msgModalTitle">Mensaje</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="msgModalBody">—</div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-primary btn-sm" data-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
window.RV_CTX = {
  baseUrl: <?= json_encode(BASE_URL, JSON_UNESCAPED_UNICODE) ?>,
  empresaNombre: <?= json_encode($empNom, JSON_UNESCAPED_UNICODE) ?>,
  usuarioNombre: <?= json_encode($usrNom, JSON_UNESCAPED_UNICODE) ?>,
  empresaLogo: <?= json_encode($empLogoRel, JSON_UNESCAPED_UNICODE) ?>
};
</script>

<?php if (is_file(__DIR__ . '/index.js')): ?>
  <script type="module" src="<?= h(rel('modules/' . basename(__DIR__) . '/index.js?v=3')) ?>"></script>
<?php endif; ?>

<?php if (is_file(__DIR__ . '/style.css')): ?>
  <link rel="stylesheet" href="<?= h(rel('modules/' . basename(__DIR__) . '/style.css?v=2')) ?>">
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
