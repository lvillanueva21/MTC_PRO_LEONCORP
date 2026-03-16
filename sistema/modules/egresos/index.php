<?php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([3, 4]);
verificarPermiso([3, 4]);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$appFolder = basename(dirname(dirname(__DIR__)));
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$parts = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx = array_search($appFolder, $parts, true);
$depth = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1));
$APP_ROOT_REL = str_repeat('../', max(0, $depth));

function rel(string $path): string
{
    global $APP_ROOT_REL;
    return $APP_ROOT_REL . ltrim($path, '/');
}

$u = currentUser();
$usrNom = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
if ($usrNom === '') $usrNom = (string)($u['usuario'] ?? 'Usuario');
$empNom = (string)($u['empresa']['nombre'] ?? '-');

include __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="<?= h(rel('modules/egresos/estilo.css?v=4')) ?>">
<style>
  .eg-list-toolbar{display:grid;gap:10px;}
  .eg-list-filter-row{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px 12px;align-items:end;}
  .eg-list-filter-col{min-width:0;}
  .eg-list-filter-col.search{grid-column:span 12;}
  .eg-list-filter-col.estado,.eg-list-filter-col.tipo,.eg-list-filter-col.scope{grid-column:span 12;}
  .eg-list-filter-col.fecha,.eg-list-filter-col.desde,.eg-list-filter-col.hasta{grid-column:span 12;}
  .eg-list-filter-col.actions{grid-column:span 12;}
  .eg-list-actions{display:flex;flex-wrap:wrap;gap:8px;}
  .eg-list-filter-meta{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px 14px;align-items:center;}
  .eg-scope-chip{display:inline-flex;align-items:center;gap:8px;padding:4px 10px;border-radius:999px;background:#f8f7ff;border:1px solid #dad5ff;color:#49437c;}
  .eg-scope-chip .badge{font-weight:600;}
  .eg-list-summary{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}

  .eg-row-main{cursor:pointer;}
  .eg-row-main:hover{background:#f8fafc;}
  .eg-row-toggle{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:20px;
    height:20px;
    border-radius:50%;
    transition:transform .18s ease, background-color .18s ease, color .18s ease;
  }
  .eg-row-toggle i{transition:transform .18s ease;}
  .eg-row-toggle.is-open{
    background:#eef2ff;
    color:#4f46e5 !important;
  }
  .eg-row-toggle.is-open i{transform:rotate(180deg);}

  .eg-type-cell .badge{display:inline-block;}
  .eg-type-ref{
    margin-top:5px;
    font-size:12px;
    line-height:1.25;
    color:#6c757d;
    word-break:break-word;
  }

  .eg-detail-row{display:none;}
  .eg-detail-row.show{display:table-row;}
  .eg-detail-cell{
    padding:0 !important;
    border-top:0 !important;
    background:#fbfcfe;
  }
  .eg-detail-box{
    margin:0 12px 10px;
    padding:14px 16px;
    border:1px solid #e6e9ef;
    border-radius:12px;
    background:#fff;
  }
  .eg-detail-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px 18px;
  }
  .eg-detail-item{min-width:0;}
  .eg-detail-label{
    display:block;
    margin-bottom:6px;
    font-size:12px;
    font-weight:700;
    color:#4b5563;
    text-transform:uppercase;
    letter-spacing:.02em;
  }
  .eg-detail-text{
    font-size:13px;
    line-height:1.45;
    color:#111827;
    white-space:pre-wrap;
    word-break:break-word;
  }
  .eg-detail-placeholder{
    font-size:12px;
    color:#6b7280;
  }

  @media (max-width: 767.98px){
    .eg-detail-grid{grid-template-columns:1fr;}
  }

  @media (min-width: 576px){
    .eg-list-filter-col.estado,.eg-list-filter-col.tipo,.eg-list-filter-col.scope,.eg-list-filter-col.fecha,.eg-list-filter-col.desde,.eg-list-filter-col.hasta{grid-column:span 6;}
  }
  @media (min-width: 1200px){
    .eg-list-filter-col.search{grid-column:span 5;}
    .eg-list-filter-col.estado,.eg-list-filter-col.tipo,.eg-list-filter-col.scope{grid-column:span 2;}
    .eg-list-filter-col.fecha,.eg-list-filter-col.desde,.eg-list-filter-col.hasta{grid-column:span 3;}
    .eg-list-filter-col.actions{grid-column:span 3;}
  }
</style>

<div class="content-wrapper" id="egApp"
     data-api="<?= h(rel('modules/egresos/api.php')) ?>"
     data-emp="<?= h($empNom) ?>"
     data-usr="<?= h($usrNom) ?>">
  <div class="content-header">
    <div class="container-fluid">
      <div class="eg-bar shadow-sm">
        <div class="eg-bar-left">
          <div class="eg-icon"><i class="fas fa-file-invoice-dollar"></i></div>
          <div class="eg-titles">
            <div class="eg-title">Modulo de egresos</div>
            <div class="eg-subtitle">Empresa: <strong>"<?= h($empNom) ?>"</strong>  - Usuario: <strong><?= h($usrNom) ?></strong></div>
            <div class="eg-subtitle small">Registra salidas de dinero vinculadas a la caja diaria abierta.</div>
          </div>
        </div>
        <div class="eg-bar-right">
          <div class="eg-caja-pill"><span class="label">Caja diaria</span><span class="badge badge-pill badge-secondary" id="egCajaBadge">Cargando...</span></div>
          <div class="eg-caja-pill"><span class="label">Caja mensual</span><span class="badge badge-pill badge-secondary" id="egCajaMensualBadge">Cargando...</span></div>
          <div class="eg-bar-meta small text-right">
            <div><strong id="egCajaDiariaCodigo">CD: -</strong></div>
            <div><strong id="egCajaMensualCodigo">CM: -</strong></div>
          </div>
        </div>
      </div>
      <div id="egCajaMsg" class="alert eg-caja-alert mt-3 mb-0" role="alert"></div>
    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="row g-3">
        <div class="col-12 col-lg-5">
          <div class="card eg-card shadow-sm">
            <div class="card-body">
              <div class="eg-card-head mb-2">
                <div class="eg-card-head-main">
                  <h5 class="card-title mb-0">Nuevo egreso</h5>
                  <div class="text-muted small">Operacion real y vinculada a caja diaria.</div>
                </div>
                <span class="badge badge-light eg-card-head-status" id="egFormStateBadge">Verificando caja...</span>
              </div>
              <div id="egSaldoResumen" class="eg-saldo-box mb-2 d-none"></div>
              <div id="egFormAlert"></div>

              <form id="egForm" autocomplete="off">
                <div class="form-group mb-3">
                  <label class="mb-1">Tipo de comprobante</label>
                  <div class="eg-chip-group" id="egTipoChipGroup">
                    <button type="button" class="eg-chip active" data-tipo="RECIBO">Recibo interno</button>
                    <button type="button" class="eg-chip" data-tipo="BOLETA">Boleta</button>
                    <button type="button" class="eg-chip" data-tipo="FACTURA">Factura</button>
                  </div>
                  <input type="hidden" id="egTipo" value="RECIBO">
                  <small class="form-text text-muted">Factura y boleta requieren serie y numero.</small>
                </div>
                <div class="form-row" id="egSerieNumeroGroup">
                  <div class="form-group col-4">
                    <label class="mb-1">Serie<span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="egSerie" maxlength="10" placeholder="F001">
                  </div>
                  <div class="form-group col-8">
                    <label class="mb-1">Numero<span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="egNumero" maxlength="20" placeholder="00012345">
                  </div>
                </div>
                <div class="form-group mb-3 d-none" id="egReciboRefGroup">
                  <label class="mb-1">Referencia (opcional)</label>
                  <input type="text" class="form-control form-control-sm" id="egReferencia" maxlength="120" placeholder="Ej: Recibo manual 001316">
                </div>
                <div class="form-row">
                  <div class="form-group col-6">
                    <label class="mb-1">Monto (S/)<span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                      <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                      <input type="number" step="0.01" min="0" class="form-control" id="egMonto" required>
                    </div>
                  </div>
                  <div class="form-group col-6">
                    <label class="mb-1">Fecha y hora<span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control form-control-sm" id="egFecha" required>
                  </div>
                </div>
                <div class="form-group mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">Distribucion por fuente<span class="text-danger">*</span></label>
                    <button type="button" class="btn btn-outline-primary btn-xs" id="egBtnDistribuir">
                      <i class="fas fa-random mr-1"></i>Distribuir
                    </button>
                  </div>
                  <div id="egFuentesResumen" class="eg-fuentes-resumen">
                    Pendiente de distribucion.
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-6">
                    <label class="mb-1">Beneficiario / Proveedor</label>
                    <input type="text" class="form-control form-control-sm" id="egBeneficiario" maxlength="160" placeholder="Nombre completo o razon social">
                  </div>
                  <div class="form-group col-6">
                    <label class="mb-1">Documento</label>
                    <input type="text" class="form-control form-control-sm" id="egDocumento" maxlength="20" placeholder="DNI / RUC">
                  </div>
                </div>
                <div class="form-group mb-3">
                  <label class="mb-1">Concepto detallado<span class="text-danger">*</span></label>
                  <textarea id="egConcepto" rows="5" class="form-control form-control-sm" required></textarea>
                  <div class="d-flex justify-content-between mt-1 small text-muted">
                    <span>Este texto se imprimira en el recibo.</span><span id="egConceptoCount">0 / 1000</span>
                  </div>
                </div>
                <div class="form-group mb-3">
                  <label class="mb-1">Observaciones internas <span class="text-muted small">(opcional)</span></label>
                  <textarea id="egObs" rows="2" class="form-control form-control-sm"></textarea>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                  <div class="small text-muted"><i class="fas fa-info-circle mr-1"></i>El egreso queda asociado a la caja diaria abierta.</div>
                  <div class="eg-form-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm mr-1" id="egBtnLimpiar"><i class="fas fa-eraser mr-1"></i>Limpiar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="egBtnGuardar"><i class="fas fa-save mr-1"></i>Guardar egreso</button>
                    <button type="button" class="btn btn-outline-primary btn-sm ml-1" id="egBtnVistaPrevia"><i class="fas fa-print mr-1"></i>Vista previa</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-7">
                    <div class="card eg-card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
              <div class="eg-list-head mb-2">
                <div class="eg-list-head-main">
                  <h5 class="card-title mb-0">Egresos registrados</h5>
                  <div class="text-muted small">Vista operativa por caja diaria, con histórico disponible bajo demanda.</div>
                </div>
              </div>

              <div class="eg-list-toolbar mb-2">
                <div class="eg-list-filter-row">
                  <div class="eg-list-filter-col search">
                    <label class="mb-1 small font-weight-bold">Buscar</label>
                    <div class="input-group input-group-sm">
                      <div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fas fa-search"></i></span></div>
                      <input id="egQ" class="form-control border-left-0" placeholder="Codigo, concepto, beneficiario, documento, serie, numero o referencia">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="egClearQ" title="Limpiar busqueda"><i class="fas fa-times"></i></button>
                      </div>
                    </div>
                  </div>

                  <div class="eg-list-filter-col estado">
                    <label class="mb-1 small font-weight-bold">Estado</label>
                    <select id="egFiltroEstado" class="form-control form-control-sm">
                      <option value="TODOS" selected>Todos</option>
                      <option value="ACTIVO">Solo activos</option>
                      <option value="ANULADO">Solo anulados</option>
                    </select>
                  </div>

                  <div class="eg-list-filter-col tipo">
                    <label class="mb-1 small font-weight-bold">Tipo</label>
                    <select id="egFiltroTipo" class="form-control form-control-sm">
                      <option value="TODOS" selected>Todos</option>
                      <option value="FACTURA">Facturas</option>
                      <option value="BOLETA">Boletas</option>
                      <option value="RECIBO">Recibos</option>
                    </select>
                  </div>

                  <div class="eg-list-filter-col scope">
                    <label class="mb-1 small font-weight-bold">Periodo</label>
                    <select id="egScope" class="form-control form-control-sm">
                      <option value="latest" selected>Ultima caja</option>
                      <option value="date">Fecha</option>
                      <option value="range">Rango</option>
                      <option value="all">Historico</option>
                    </select>
                  </div>
                </div>

                <div class="eg-list-filter-row">
                  <div class="eg-list-filter-col fecha d-none" id="egFechaWrap">
                    <label class="mb-1 small font-weight-bold">Fecha</label>
                    <input id="egFechaFiltro" type="date" class="form-control form-control-sm">
                  </div>

                  <div class="eg-list-filter-col desde d-none" id="egDesdeWrap">
                    <label class="mb-1 small font-weight-bold">Desde</label>
                    <input id="egDesde" type="date" class="form-control form-control-sm">
                  </div>

                  <div class="eg-list-filter-col hasta d-none" id="egHastaWrap">
                    <label class="mb-1 small font-weight-bold">Hasta</label>
                    <input id="egHasta" type="date" class="form-control form-control-sm">
                  </div>

                  <div class="eg-list-filter-col actions">
                    <label class="mb-1 small font-weight-bold d-block">&nbsp;</label>
                    <div class="eg-list-actions">
                      <button class="btn btn-sm btn-primary" type="button" id="egApplyScope"><i class="fas fa-filter mr-1"></i>Aplicar</button>
                      <button class="btn btn-sm btn-outline-secondary" type="button" id="egResetScope"><i class="fas fa-history mr-1"></i>Volver a ultima caja</button>
                    </div>
                  </div>
                </div>

                <div class="eg-list-filter-meta">
                  <div id="egScopeInfo" class="small text-muted"></div>
                  <div id="egResumenListado" class="small text-muted"></div>
                </div>
              </div>
<div class="small text-muted mb-2">Haz clic en una fila para ver concepto y observaciones internas.</div>

<div class="table-responsive flex-grow-1">
  <table class="table table-sm table-hover mb-2" id="egTable">
    <thead class="thead-light">
      <tr>
        <th>Codigo</th>
        <th>Fecha</th>
        <th>Tipo</th>
        <th>Beneficiario</th>
        <th class="text-right">Monto</th>
        <th>Estado</th>
        <th class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody id="egTableBody"><tr><td colspan="7" class="text-muted small">Cargando egresos...</td></tr></tbody>
  </table>
</div>

<div class="eg-list-summary mt-2">
  <div class="small text-muted" id="egTotalesDia"></div>
  <nav><ul class="pagination pagination-sm mb-0" id="egPager"></ul></nav>
</div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="egFuentesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header py-2">
              <h5 class="modal-title"><i class="fas fa-layer-group mr-1"></i>Distribucion de egreso por fuente</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <div id="egFuentesModalMsg" class="alert alert-info py-2 mb-2">
                Define una o mas fuentes y valida el total antes de aplicar.
              </div>
              <div class="eg-fuentes-totales mb-2">
                <div><strong>Monto egreso:</strong> <span id="egFuentesMontoObjetivo">S/ 0.00</span></div>
                <div><strong>Total asignado:</strong> <span id="egFuentesMontoAsignado">S/ 0.00</span></div>
                <div><strong>Diferencia:</strong> <span id="egFuentesMontoDiff">S/ 0.00</span></div>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 eg-fuentes-table">
                  <thead class="thead-light">
                    <tr>
                      <th style="width:25%;">Fuente</th>
                      <th style="width:25%;" class="text-right">Disponible</th>
                      <th style="width:25%;" class="text-right">Egresos activos</th>
                      <th style="width:25%;" class="text-right">A extraer</th>
                    </tr>
                  </thead>
                  <tbody id="egFuentesTableBody"></tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer py-2">
              <button type="button" class="btn btn-outline-secondary btn-sm mr-auto" id="egBtnFuentesClear">
                <i class="fas fa-eraser mr-1"></i>Limpiar
              </button>
              <button type="button" class="btn btn-outline-primary btn-sm" id="egBtnFuentesAuto">
                <i class="fas fa-magic mr-1"></i>Auto distribuir
              </button>
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-primary btn-sm" id="egBtnFuentesAplicar">Aplicar distribucion</button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="egresoPrintModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header py-2 bg-dark text-white">
              <h5 class="modal-title"><i class="fas fa-receipt mr-1"></i>Vista previa de recibo de egreso</h5>
              <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"><div id="egVoucher" class="eg-voucher-wrapper"></div></div>
            <div class="modal-footer py-2">
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary btn-sm" id="egBtnPrintReal" disabled><i class="fas fa-print mr-1"></i>Imprimir PDF</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script src="<?= h(rel('modules/egresos/index.js?v=2')) ?>" defer></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


