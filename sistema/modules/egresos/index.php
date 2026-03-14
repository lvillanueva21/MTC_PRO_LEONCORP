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
                  <div class="text-muted small">Control de salidas por caja diaria.</div>
                </div>
                <div class="eg-filters">
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fas fa-search"></i></span></div>
                    <input id="egQ" class="form-control border-left-0" placeholder="Buscar por codigo, concepto o beneficiario...">
                  </div>
                  <select id="egFiltroTipo" class="form-control form-control-sm"><option value="TODOS">Todos</option><option value="FACTURA">Facturas</option><option value="BOLETA">Boletas</option><option value="RECIBO">Recibos</option></select>
                  <select id="egFiltroEstado" class="form-control form-control-sm"><option value="TODOS">Activos y anulados</option><option value="ACTIVO">Solo activos</option><option value="ANULADO">Solo anulados</option></select>
                </div>
              </div>
              <div class="small text-muted mb-2" id="egResumenListado"></div>
              <div class="table-responsive flex-grow-1">
                <table class="table table-sm table-hover mb-2" id="egTable">
                  <thead class="thead-light"><tr><th>Codigo</th><th>Fecha</th><th>Tipo</th><th>Comp.</th><th>Beneficiario</th><th>Concepto</th><th class="text-right">Monto</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                  <tbody id="egTableBody"><tr><td colspan="9" class="text-muted small">Cargando egresos...</td></tr></tbody>
                </table>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-2">
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


