<?php
// modules/reporte_clientes/index.php — Central de reporte de clientes

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

/* ========= Config de la página ========= */
$PAGE_TITLE = 'Central de Clientes';
$ALLOWED_ROLE_IDS   = [3,4];                       // Recepción, Administración
$ALLOWED_ROLE_NAMES = ['Recepción','Administración'];

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

/* ========= Helpers ========= */
function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Cálculo de prefijo relativo a la raíz de la app y helper de enlaces.
 * - No depende de dominios ni de '/ventas/' hardcodeado.
 * - Soporta despliegue en subcarpeta o en subdominio.
 */
$appFolder = basename(dirname(dirname(__DIR__)));              // p.ej. 'ventas'
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // ej. 'ventas/modules/...'
$parts     = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx       = array_search($appFolder, $parts, true);
$depth     = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1));
$APP_ROOT_REL = str_repeat('../', max(0, $depth));

/** Devuelve una ruta relativa portable desde la página actual */
function rel(string $path) {
    global $APP_ROOT_REL;
    return $APP_ROOT_REL . ltrim($path, '/');
}

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
    <?php
    require_once __DIR__ . '/funciones.php';

    $empId = (int)($u['empresa']['id'] ?? 0);

    // --------- Filtros ----------
    $q           = trim($_GET['q'] ?? '');
    $tipoPersona = isset($_GET['tipo_persona']) ? (string)$_GET['tipo_persona'] : '';
    $docTipo     = isset($_GET['doc_tipo']) ? (string)$_GET['doc_tipo'] : '';
    $estado      = isset($_GET['estado']) ? (string)$_GET['estado'] : ''; // activos, inactivos, ''

    $estadoVals = ['activos','inactivos'];
    if (!in_array($estado, $estadoVals, true)) {
        $estado = '';
    }
    $activoFlag = '';
    if ($estado === 'activos') {
        $activoFlag = '1';
    } elseif ($estado === 'inactivos') {
        $activoFlag = '0';
    }

    $fcreadoDesde = isset($_GET['fcreado_desde']) ? (string)$_GET['fcreado_desde'] : '';
    $fcreadoHasta = isset($_GET['fcreado_hasta']) ? (string)$_GET['fcreado_hasta'] : '';

    $tieneVentas = isset($_GET['tiene_ventas']) && $_GET['tiene_ventas'] === '1';
    $tieneAbonos = isset($_GET['tiene_abonos']) && $_GET['tiene_abonos'] === '1';

    // --------- Paginación ----------
    $perPage   = isset($_GET['pp']) ? (int)$_GET['pp'] : 20;
    $allowedPP = [10, 20, 50, 100];
    if (!in_array($perPage, $allowedPP, true)) {
        $perPage = 20;
    }
    $page = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
    if ($page < 1) $page = 1;

    $optsBusqueda = [
        'q'             => $q,
        'tipo_persona'  => $tipoPersona,
        'doc_tipo'      => $docTipo,
        'activo'        => $activoFlag,
        'tiene_ventas'  => $tieneVentas ? 1 : 0,
        'tiene_abonos'  => $tieneAbonos ? 1 : 0,
        'fcreado_desde' => $fcreadoDesde,
        'fcreado_hasta' => $fcreadoHasta,
        'pagina'        => $page,
        'por_pagina'    => $perPage,
    ];

    $empresaInfo = $empId > 0 ? obtener_empresa_info($db, $empId) : [];

    $resumenClientes = [
        'total_clientes'      => 0,
        'clientes_con_ventas' => 0,
        'clientes_sin_ventas' => 0,
        'total_ventas'        => 0.0,
        'total_pagado'        => 0.0,
        'total_devuelto'      => 0.0,
        'total_saldo'         => 0.0,
        'total_abonos'        => 0.0,
    ];

    $resultVacia = [
        'rows'        => [],
        'total'       => 0,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => 0,
        'from'        => 0,
        'to'          => 0,
    ];

    if ($empId > 0) {
        $resumenClientes = obtener_resumen_clientes($db, $empId, $optsBusqueda);
        $result          = buscar_clientes_con_metricas($db, $empId, $optsBusqueda);
    } else {
        $result = $resultVacia;
    }

    $clientes   = $result['rows'];
    $total      = $result['total'];
    $page       = $result['page'];
    $perPage    = $result['per_page'];
    $totalPages = $result['total_pages'];
    $from       = $result['from'];
    $to         = $result['to'];

    // Query string base para mantener filtros en los links
    $qsBase = [
        'q'             => $q !== '' ? $q : null,
        'tipo_persona'  => $tipoPersona !== '' ? $tipoPersona : null,
        'doc_tipo'      => $docTipo !== '' ? $docTipo : null,
        'estado'        => $estado !== '' ? $estado : null,
        'tiene_ventas'  => $tieneVentas ? '1' : null,
        'tiene_abonos'  => $tieneAbonos ? '1' : null,
        'fcreado_desde' => $fcreadoDesde !== '' ? $fcreadoDesde : null,
        'fcreado_hasta' => $fcreadoHasta !== '' ? $fcreadoHasta : null,
        'pp'            => $perPage,
    ];
    $tmp = [];
    foreach ($qsBase as $k => $v) {
        if ($v !== null && $v !== '') {
            $tmp[$k] = $v;
        }
    }
    $qsBase = $tmp;

    $hayFiltros = (
        $q !== '' ||
        $tipoPersona !== '' ||
        $docTipo !== '' ||
        $estado !== '' ||
        $tieneVentas ||
        $tieneAbonos ||
        $fcreadoDesde !== '' ||
        $fcreadoHasta !== ''
    );

    $ventasNeto = (float)$resumenClientes['total_pagado'] - (float)$resumenClientes['total_devuelto'];
    $userNombre = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
    if ($userNombre === '') {
        $userNombre = (string)($u['usuario'] ?? '');
    }
    ?>
    <div class="container-fluid">

      <!-- Cards superiores -->
      <div class="row">
        <!-- Empresa actual -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card shadow-sm clientes-summary-card mb-3">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3">
                <span class="icon text-primary">
                  <i class="fas fa-building"></i>
                </span>
              </div>
              <div>
                <div class="small-label text-muted mb-1">Empresa actual</div>
                <div class="font-weight-bold mb-1">
                  <?= h($empresaInfo['nombre'] ?? ($u['empresa']['nombre'] ?? '')) ?>
                </div>
                <?php if (!empty($empresaInfo['ruc'])): ?>
                  <div class="text-muted small mb-0">RUC: <?= h($empresaInfo['ruc']) ?></div>
                <?php endif; ?>
                <?php if (!empty($empresaInfo['departamento'])): ?>
                  <div class="text-muted small mb-0">
                    <?= h($empresaInfo['departamento']) ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Resumen clientes -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card shadow-sm clientes-summary-card mb-3">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3">
                <span class="icon text-success">
                  <i class="fas fa-users"></i>
                </span>
              </div>
              <div>
                <div class="small-label text-muted mb-1">Clientes (filtro actual)</div>
                <div class="h4 mb-0">
                  <?= (int)$resumenClientes['total_clientes'] ?>
                </div>
                <div class="text-muted small mt-1">
                  Con ventas: <?= (int)$resumenClientes['clientes_con_ventas'] ?>
                  &nbsp;·&nbsp;
                  Sin ventas: <?= (int)$resumenClientes['clientes_sin_ventas'] ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Montos de ventas -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card shadow-sm clientes-summary-card mb-3">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3">
                <span class="icon text-info">
                  <i class="fas fa-file-invoice-dollar"></i>
                </span>
              </div>
              <div>
                <div class="small-label text-muted mb-1">Ventas asociadas</div>
                <div class="font-weight-bold mb-0">
                  <?= h(fmt_money($ventasNeto)) ?>
                </div>
                <div class="text-muted small mt-1">
                  Total emitido: <?= h(fmt_money($resumenClientes['total_ventas'])) ?>
                  <br>
                  Saldo por cobrar: <?= h(fmt_money($resumenClientes['total_saldo'])) ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Abonos y usuario -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card shadow-sm clientes-summary-card mb-3">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3">
                <span class="icon text-warning">
                  <i class="fas fa-wallet"></i>
                </span>
              </div>
              <div>
                <div class="small-label text-muted mb-1">Abonos del filtro</div>
                <div class="font-weight-bold mb-0">
                  <?= h(fmt_money($resumenClientes['total_abonos'])) ?>
                </div>
                <div class="text-muted small mt-1">
                  Usuario actual: <?= h($userNombre) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card principal: filtros + tabla -->
      <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <div>
            <strong><i class="fas fa-user-friends mr-1"></i> Central de clientes</strong>
          </div>
          <div class="text-muted small d-none d-md-inline">
            Vista consolidada de clientes, sus ventas y abonos.
          </div>
        </div>
        <div class="card-body p-2">

          <!-- Filtros -->
          <form class="row g-2 mb-3" method="get" action="">
            <div class="col-12 col-md-4 col-lg-3">
              <label class="mb-1 small">
                <i class="fas fa-search mr-1"></i>Nombre / Doc / Contacto
              </label>
              <input type="text"
                     name="q"
                     class="form-control form-control-sm"
                     value="<?= h($q) ?>"
                     placeholder="Nombre, documento, email o teléfono">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-user-tag mr-1"></i>Tipo de persona
              </label>
              <select name="tipo_persona" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="NATURAL"  <?= $tipoPersona === 'NATURAL'  ? 'selected' : '' ?>>Natural</option>
                <option value="JURIDICA" <?= $tipoPersona === 'JURIDICA' ? 'selected' : '' ?>>Jurídica</option>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-id-card mr-1"></i>Tipo documento
              </label>
              <select name="doc_tipo" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="DNI"      <?= $docTipo === 'DNI'      ? 'selected' : '' ?>>DNI</option>
                <option value="RUC"      <?= $docTipo === 'RUC'      ? 'selected' : '' ?>>RUC</option>
                <option value="CE"       <?= $docTipo === 'CE'       ? 'selected' : '' ?>>CE</option>
                <option value="PAS"      <?= $docTipo === 'PAS'      ? 'selected' : '' ?>>Pasaporte</option>
                <option value="BREVETE"  <?= $docTipo === 'BREVETE'  ? 'selected' : '' ?>>Brevete</option>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-toggle-on mr-1"></i>Estado
              </label>
              <select name="estado" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="activos"   <?= $estado === 'activos'   ? 'selected' : '' ?>>Activos</option>
                <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
              </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-calendar-plus mr-1"></i>Creado desde
              </label>
              <input type="date"
                     name="fcreado_desde"
                     class="form-control form-control-sm"
                     value="<?= h($fcreadoDesde) ?>">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
              <label class="mb-1 small">
                <i class="fas fa-calendar-minus mr-1"></i>Creado hasta
              </label>
              <input type="date"
                     name="fcreado_hasta"
                     class="form-control form-control-sm"
                     value="<?= h($fcreadoHasta) ?>">
            </div>

            <div class="col-12 col-md-6 col-lg-3">
              <label class="mb-1 small d-block">
                <i class="fas fa-filter mr-1"></i>Filtros avanzados
              </label>
              <div class="form-check form-check-inline mr-3">
                <input class="form-check-input"
                       type="checkbox"
                       id="chkVentas"
                       name="tiene_ventas"
                       value="1"
                       <?= $tieneVentas ? 'checked' : '' ?>>
                <label class="form-check-label small" for="chkVentas">
                  Solo con ventas
                </label>
              </div>
              <div class="form-check form-check-inline mr-3">
                <input class="form-check-input"
                       type="checkbox"
                       id="chkAbonos"
                       name="tiene_abonos"
                       value="1"
                       <?= $tieneAbonos ? 'checked' : '' ?>>
                <label class="form-check-label small" for="chkAbonos">
                  Solo con abonos
                </label>
              </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3 d-flex align-items-end">
              <div class="btn-group btn-group-sm w-100" role="group">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-search mr-1"></i>Filtrar
                </button>
                <?php if ($hayFiltros): ?>
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
                Mostrando <?= (int)$from ?>–<?= (int)$to ?> de <?= (int)$total ?> clientes
              <?php else: ?>
                No se encontraron clientes con los filtros actuales.
              <?php endif; ?>
            </div>
            <div class="text-md-end">
              <div class="btn-group btn-group-sm" role="group" aria-label="Clientes por página">
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

          <!-- Tabla principal -->
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="tblClientes">
              <thead class="table-light">
                <tr>
                  <th style="width:60px">#</th>
                  <th style="width:260px">Cliente</th>
                  <th style="width:160px">Tipo / Documento</th>
                  <th style="width:220px">Contacto</th>
                  <th style="width:140px" class="text-end">Ventas netas</th>
                  <th style="width:140px" class="text-end">Abonos</th>
                  <th style="width:140px" class="text-end">Saldo ventas</th>
                  <th style="width:200px">Últimos mov.</th>
                  <th style="width:140px">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($clientes): ?>
                  <?php $i = 1; foreach ($clientes as $cli):
                    $cliId   = (int)$cli['id'];
                    $nombre  = trim((string)$cli['nombre']);
                    $tipoP   = (string)$cli['tipo_persona'];
                    $docT    = (string)$cli['doc_tipo'];
                    $docN    = (string)$cli['doc_numero'];
                    $email   = trim((string)($cli['email'] ?? ''));
                    $tel     = trim((string)($cli['telefono'] ?? ''));
                    $dir     = trim((string)($cli['direccion'] ?? ''));
                    $activo  = (int)($cli['activo'] ?? 0);

                    $ventasTotal    = (float)($cli['ventas_total'] ?? 0);
                    $ventasPagado   = (float)($cli['ventas_pagado'] ?? 0);
                    $ventasDevuelto = (float)($cli['ventas_devuelto'] ?? 0);
                    $ventasSaldo    = (float)($cli['ventas_saldo'] ?? 0);
                    $ventasNetoCli  = $ventasPagado - $ventasDevuelto;
                    if ($ventasNetoCli < 0) $ventasNetoCli = 0;

                    $abonosTotal = (float)($cli['abonos_total'] ?? 0);
                    $ventasCnt   = (int)($cli['ventas_count'] ?? 0);
                    $abonosCnt   = (int)($cli['abonos_count'] ?? 0);

                    $ultimaVenta = fmt_date($cli['ultima_venta'] ?? null);
                    $ultimoAbono = fmt_date($cli['ultimo_abono'] ?? null);

                    if ($activo) {
                      $badgeEstado = '<span class="badge badge-success">Activo</span>';
                    } else {
                      $badgeEstado = '<span class="badge badge-secondary">Inactivo</span>';
                    }
                  ?>
                  <!-- Fila principal -->
                  <tr class="js-row-cliente" data-id="<?= $cliId ?>">
                    <td><?= $i++ ?></td>
                    <td>
                      <div class="font-weight-bold"><?= h($nombre !== '' ? $nombre : '—') ?></div>
                      <div class="text-muted small"><?= $badgeEstado ?></div>
                    </td>
                    <td>
                      <div class="small mb-0">
                        <?= h($tipoP) ?>
                      </div>
                      <div class="text-muted small">
                        <?= h($docT) ?> <?= h($docN) ?>
                      </div>
                    </td>
                    <td>
                      <div class="small mb-0">
                        <i class="fas fa-envelope mr-1"></i><?= h($email !== '' ? $email : '—') ?>
                      </div>
                      <div class="small">
                        <i class="fas fa-phone mr-1"></i><?= h($tel !== '' ? $tel : '—') ?>
                      </div>
                    </td>
                    <td class="text-end">
                      <?= h(fmt_money($ventasNetoCli)) ?>
                      <div class="text-muted small">
                        (<?= (int)$ventasCnt ?> ventas)
                      </div>
                    </td>
                    <td class="text-end">
                      <?= h(fmt_money($abonosTotal)) ?>
                      <div class="text-muted small">
                        (<?= (int)$abonosCnt ?> abonos)
                      </div>
                    </td>
                    <td class="text-end">
                      <?= h(fmt_money($ventasSaldo)) ?>
                    </td>
                    <td>
                      <div class="small mb-0">
                        Últ. venta:
                        <?= $ultimaVenta !== '' ? h($ultimaVenta) : '<span class="text-muted">—</span>' ?>
                      </div>
                      <div class="small">
                        Últ. abono:
                        <?= $ultimoAbono !== '' ? h($ultimoAbono) : '<span class="text-muted">—</span>' ?>
                      </div>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <button type="button"
                                class="btn btn-outline-secondary js-detalle-cliente"
                                data-id="<?= $cliId ?>">
                          <i class="fas fa-info-circle"></i>
                          Detalle
                        </button>
                      </div>
                    </td>
                  </tr>

                  <!-- Fila secundaria (detalle) -->
                  <tr id="det-<?= $cliId ?>" class="js-detail-cliente d-none">
                    <td colspan="9">
                      <div class="row">
                        <div class="col-12 col-lg-6 mb-2">
                          <div class="border rounded p-2">
                            <div class="titulo-bloque mb-1">
                              <i class="fas fa-user mr-1"></i>Datos del cliente
                            </div>
                            <div class="small mb-1">
                              <strong>Nombre:</strong> <?= h($nombre !== '' ? $nombre : '—') ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Documento:</strong> <?= h($docT) ?> <?= h($docN) ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Dirección:</strong> <?= h($dir !== '' ? $dir : '—') ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Email:</strong> <?= h($email !== '' ? $email : '—') ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Teléfono:</strong> <?= h($tel !== '' ? $tel : '—') ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Estado:</strong> <?= $badgeEstado ?>
                            </div>
                          </div>
                        </div>

                        <div class="col-12 col-lg-6 mb-2">
                          <div class="border rounded p-2">
                            <div class="titulo-bloque mb-1">
                              <i class="fas fa-chart-line mr-1"></i>Resumen financiero
                            </div>
                            <div class="small mb-1">
                              <strong>Total ventas:</strong> <?= h(fmt_money($ventasTotal)) ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Total pagado:</strong> <?= h(fmt_money($ventasPagado)) ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Total devuelto:</strong> <?= h(fmt_money($ventasDevuelto)) ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Saldo pendiente:</strong> <?= h(fmt_money($ventasSaldo)) ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Total abonos:</strong> <?= h(fmt_money($abonosTotal)) ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Última venta:</strong>
                              <?= $ultimaVenta !== '' ? h($ultimaVenta) : '<span class="text-muted">—</span>' ?>
                            </div>
                            <div class="small mb-1">
                              <strong>Último abono:</strong>
                              <?= $ultimoAbono !== '' ? h($ultimoAbono) : '<span class="text-muted">—</span>' ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="text-muted small">Sin clientes registrados.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <?php if ($totalPages > 1): ?>
            <div class="mt-2 d-flex justify-content-center">
              <nav aria-label="Paginación de clientes">
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
                      <li class="page-item <?= (int)$p === (int)$page ? 'active' : '' ?>">
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
