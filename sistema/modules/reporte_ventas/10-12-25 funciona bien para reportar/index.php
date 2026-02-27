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
      <?php require_once __DIR__ . '/funciones.php';
$empId   = (int)($u['empresa']['id'] ?? 0);
$ventas  = listar_ventas_con_detalles($db, $empId);
?>

<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header py-2"><strong>Ventas (vista principal)</strong></div>
    <div class="card-body p-2">
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
