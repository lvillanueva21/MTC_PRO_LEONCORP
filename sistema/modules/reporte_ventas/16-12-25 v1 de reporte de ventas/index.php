<?php
// modules/<modulo>/index.php — Plantilla base portable

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
// (Opcional si lo usas) require_once __DIR__ . '/../../includes/auth.php';

/* ========= Config de la página ========= */
$PAGE_TITLE = 'Reportes de Finanzas';                 // ← cámbialo
$ALLOWED_ROLE_IDS   = [3,4];                       // ← IDs permitidos (Recepción, Administración)
$ALLOWED_ROLE_NAMES = ['Recepción','Administración']; // ← Nombres del rol activo

/* ========= Guardas de acceso ========= */
acl_require_ids($ALLOWED_ROLE_IDS);
if (function_exists('verificarPermiso')) verificarPermiso($ALLOWED_ROLE_NAMES);

/* ========= Usuario y DB ========= */
$u = currentUser();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

/* ========= Helpers ========= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * Cálculo de prefijo relativo a la raíz de la app y helper de enlaces.
 * - No depende de dominios ni de '/ventas/' hardcodeado.
 * - Soporta despliegue en subcarpeta o en subdominio.
 */
$appFolder = basename(dirname(dirname(__DIR__)));              // p.ej. 'ventas'
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // ej. 'ventas/modules/caja' o 'modules/caja'
$parts     = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx       = array_search($appFolder, $parts, true);
$depth     = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1));
$APP_ROOT_REL = str_repeat('../', max(0, $depth)); // '../../' o '../' o ''

/** Devuelve una ruta relativa portable desde la página actual */
function rel(string $path) {
  global $APP_ROOT_REL; return $APP_ROOT_REL . ltrim($path, '/');
}

/* ========= (Opcional) Consultas iniciales =========
   Ejemplo:
   $st = $db->prepare("SELECT id,nombre FROM tabla WHERE activo=1 ORDER BY id DESC");
   $st->execute();
   $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
*/

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrapper">
  <!-- Header de la página -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6"><h1 class="m-0"><?= h($PAGE_TITLE) ?></h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <!-- Breadcrumb 100% relativo -->
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

    // Filtros
    $q          = trim($_GET['q'] ?? '');
    $servicioId = isset($_GET['servicio_id']) ? (int)$_GET['servicio_id'] : 0;
    $estado     = isset($_GET['estado']) ? (string)$_GET['estado'] : '';
    $estadoVals = ['pagado','pendiente','anulada'];
    if (!in_array($estado, $estadoVals, true)) {
      $estado = '';
    }
    $fdesde = isset($_GET['fdesde']) ? (string)$_GET['fdesde'] : '';
    $fhasta = isset($_GET['fhasta']) ? (string)$_GET['fhasta'] : '';

    // Paginación
    $perPage   = isset($_GET['pp']) ? (int)$_GET['pp'] : 10;
    $allowedPP = [10, 20, 50, 100];
    if (!in_array($perPage, $allowedPP, true)) {
      $perPage = 10;
    }
    $page = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
    if ($page < 1) $page = 1;

    $opts = [
      'q'          => $q,
      'servicio_id'=> $servicioId,
      'estado'     => $estado,
      'fdesde'     => $fdesde,
      'fhasta'     => $fhasta,
      'pagina'     => $page,
      'por_pagina' => $perPage,
    ];

    // Servicios para el combo
    $servicios = listar_servicios_empresa($db, $empId);

    // Datos de ventas paginados
    $result     = buscar_ventas_con_detalles($db, $empId, $opts);
    $ventas     = $result['rows'];
    $total      = $result['total'];
    $page       = $result['page'];
    $perPage    = $result['per_page'];
    $totalPages = $result['total_pages'];
    $from       = $result['from'];
    $to         = $result['to'];

    // Query string base para mantener filtros en los links
    $qsBase = [
      'q'          => $q !== '' ? $q : null,
      'servicio_id'=> $servicioId ?: null,
      'estado'     => $estado !== '' ? $estado : null,
      'fdesde'     => $fdesde !== '' ? $fdesde : null,
      'fhasta'     => $fhasta !== '' ? $fhasta : null,
      'pp'         => $perPage,
    ];
    $tmp = [];
    foreach ($qsBase as $k => $v) {
      if ($v !== null && $v !== '') $tmp[$k] = $v;
    }
    $qsBase = $tmp;
    ?>

<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header py-2"><strong>Ventas (vista principal)</strong></div>
    <div class="card-body p-2">
            <div class="card-body p-2">

      <!-- Filtros -->
      <form class="row g-2 mb-3" method="get" action="">
        <div class="col-12 col-md-4 col-lg-3">
          <label class="mb-1 small">Nombre &amp; Documento</label>
          <input type="text"
                 name="q"
                 class="form-control form-control-sm"
                 value="<?= h($q) ?>"
                 placeholder="Nombre, apellidos o documento">
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <label class="mb-1 small">Servicio</label>
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
          <label class="mb-1 small">Estado</label>
          <select name="estado" class="form-control form-control-sm">
            <option value="">Todos</option>
            <option value="pagado"   <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
            <option value="pendiente"<?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="anulada"  <?= $estado === 'anulada' ? 'selected' : '' ?>>Anulada</option>
          </select>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <label class="mb-1 small">Fecha venta desde</label>
          <input type="date"
                 name="fdesde"
                 class="form-control form-control-sm"
                 value="<?= h($fdesde) ?>">
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <label class="mb-1 small">Fecha venta hasta</label>
          <input type="date"
                 name="fhasta"
                 class="form-control form-control-sm"
                 value="<?= h($fhasta) ?>">
        </div>

        <div class="col-12 col-md-3 col-lg-1 d-flex align-items-end">
          <div class="btn-group btn-group-sm w-100" role="group">
            <button type="submit" class="btn btn-primary w-100">
              Filtrar
            </button>
            <?php if ($q !== '' || $servicioId || $estado !== '' || $fdesde !== '' || $fhasta !== ''): ?>
              <a href="<?= h($_SERVER['PHP_SELF'] ?? '') ?>" class="btn btn-outline-secondary">
                Limpiar
              </a>
            <?php endif; ?>
          </div>
          <!-- Mantener el tamaño de página actual al filtrar -->
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
            <?php foreach ([10,20,50,100] as $pp): 
              $qsPP = $qsBase;
              $qsPP['pp']  = $pp;
              $qsPP['pag'] = 1; // al cambiar tamaño, volvemos a la primera página
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
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" id="tblVentas">
          <thead class="table-light">
            <tr>
              <th style="width:60px">#</th>
              <th style="width:180px">Ticket</th>
              <th>Cliente</th>
              <th>Servicio</th>
              <th style="width:180px">Fecha</th>
              <th style="width:120px" class="text-end">Total</th>
              <th style="width:120px" class="text-end">Ingresado</th>
              <th style="width:120px" class="text-end">Saldo</th>
              <th style="width:140px">Estado</th>
              <th style="width:180px">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($ventas): ?>
              <?php $i=1; foreach ($ventas as $r):
                $ticket   = ticket_string($r['serie'] ?? '', (int)$r['numero']);
                $cliente  = trim((string)($r['cliente'] ?? ''));
                $servicio = trim((string)($r['servicio_principal'] ?? ''));
                $fecha    = fmt_dt($r['fecha_emision'] ?? null);
                $total    = fmt_money($r['total'] ?? 0);
                $ingNetoV = max(0.0, (float)($r['total_pagado'] ?? 0) - (float)($r['total_devuelto'] ?? 0));
                $ingNeto  = fmt_money($ingNetoV);
                $saldoNum = (float)($r['saldo'] ?? 0);
                $saldoFmt = fmt_money($saldoNum);
                $estado   = (string)($r['estado'] ?? '');

                if ($estado === 'ANULADA') {
                  $badge = '<span class="badge badge-danger">Anulada</span>';
                } elseif ($saldoNum > 0) {
                  $badge = '<span class="badge badge-warning">Pendiente</span>';
                } else {
                  $badge = '<span class="badge badge-success">Pagado</span>';
                }

                $ventaId = (int)$r['id'];
              ?>
                <!-- Fila principal -->
                <tr class="js-row" data-id="<?= $ventaId ?>">
                  <td><?= $i++ ?></td>
                  <td><?= h($ticket) ?></td>
                  <td><?= h($cliente !== '' ? $cliente : '—') ?></td>
                  <td><?= h($servicio !== '' ? $servicio : '—') ?></td>
                  <td><?= h($fecha !== '' ? $fecha : '—') ?></td>
                  <td class="text-end"><?= h($total) ?></td>
                  <td class="text-end"><?= h($ingNeto) ?></td>
                  <td class="text-end"><?= h($saldoFmt) ?></td>
                  <td><?= $badge ?></td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                      <button type="button"
                              class="btn btn-outline-secondary js-detalle"
                              data-id="<?= $ventaId ?>"
                              data-ticket="<?= h($ticket) ?>">
                        Detalle
                      </button>
                      <button type="button"
                              class="btn btn-outline-primary js-abonar"
                              data-id="<?= $ventaId ?>"
                              data-ticket="<?= h($ticket) ?>">
                        Abonar
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Fila secundaria (plegable) -->
                <tr id="det-<?= $ventaId ?>" class="js-detail d-none">
                  <td colspan="10">
                    <div class="row g-2">
                      <div class="col-12 col-lg-6">
                        <div class="border rounded p-2">
                          <div class="fw-bold mb-1">Conductores</div>
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
                                    $doc = trim(($c['doc_tipo'] ?? '').' '.($c['doc_numero'] ?? ''));
                                    $nom = trim(($c['nombres'] ?? '').' '.($c['apellidos'] ?? ''));
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
                            <div class="text-muted small">— Sin conductores —</div>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="col-12 col-lg-6">
                        <div class="border rounded p-2">
                          <div class="fw-bold mb-1">Abonos</div>
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
                                      $devBadge = ($devNum + 0.00001 >= $montoAplNum)
                                        ? '<span class="badge badge-danger">Devuelto total</span> <span class="badge badge-secondary">'.h(fmt_money($devNum)).'</span>'
                                        : '<span class="badge badge-warning">Devuelto parcial</span> <span class="badge badge-secondary">'.h(fmt_money($devNum)).'</span>';
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
                            <div class="text-muted small">— Sin abonos —</div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="10" class="text-muted small">Sin ventas registradas.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
              <?php if ($totalPages > 1): ?>
        <div class="mt-2 d-flex justify-content-center">
          <nav aria-label="Paginación de ventas">
            <ul class="pagination pagination-sm mb-0">
              <?php
                // Página anterior
                $prevPage = $page - 1;
                $qsPrev = $qsBase;
                $qsPrev['pag'] = $prevPage;
                $hrefPrev = '?' . http_build_query($qsPrev);

                // Ventana de páginas cercanas
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
                // Página siguiente
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
</div>
  </section>
</div>
<!-- JS del módulo (solo si existe modules/<modulo>/index.js) -->
<?php if (is_file(__DIR__ . '/index.js')): ?>
  <script type="module" src="<?= h(rel('modules/' . basename(__DIR__) . '/index.js?v=1')) ?>"></script>
<?php endif; ?>
<!-- CSS del módulo (solo si existe modules/<modulo>/style.css) -->
<?php if (is_file(__DIR__ . '/style.css')): ?>
  <link rel="stylesheet" href="<?= h(rel('modules/' . basename(__DIR__) . '/style.css?v=1')) ?>">
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
