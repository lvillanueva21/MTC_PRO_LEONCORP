<?php
// /modules/caja/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

$ALLOWED_ROLE_IDS = [3,4];
$CONTROL_SPECIAL_SLUG = 'caja';

$hasNormalRoleByAcl = acl_can_ids($ALLOWED_ROLE_IDS);
acl_require_ids_or_control_special($ALLOWED_ROLE_IDS, $CONTROL_SPECIAL_SLUG);
if ($hasNormalRoleByAcl) {
    verificarPermiso(['Recepción','Administración']);
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$u      = currentUser();
$usrNom = trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
$empNom = (string)($u['empresa']['nombre'] ?? '—');
// === Logo de empresa para voucher (ruta RELATIVA al archivo actual) ===
$empresaId   = (int)($u['empresa']['id'] ?? 0);
$empLogoRel  = '';

try {
    // 1) Sesión
    $logoFromSession = isset($u['empresa']['logo_path']) ? trim((string)$u['empresa']['logo_path']) : '';
    // 2) Fallback a BD si es necesario
    if ($logoFromSession === '' && $empresaId > 0) {
        $st = db()->prepare("SELECT logo_path FROM mtp_empresas WHERE id=? LIMIT 1");
        $st->bind_param('i', $empresaId);
        $st->execute();
        if ($r = $st->get_result()->fetch_assoc()) {
            $logoFromSession = trim((string)($r['logo_path'] ?? ''));
        }
        $st->close();
    }

    if ($logoFromSession !== '') {
        // Normaliza a relativo desde /modules/caja/index.php
        $rel = '../../' . ltrim($logoFromSession, '/');
        // Verifica que exista físicamente
        if (is_file(__DIR__ . '/../../' . ltrim($logoFromSession, '/'))) {
            $empLogoRel = $rel;
        }
    }

    // Fallback a un logo genérico
    if ($empLogoRel === '') {
        $fallback = '../../dist/img/AdminLTELogo.png';
        if (is_file(__DIR__ . '/../../dist/img/AdminLTELogo.png')) {
            $empLogoRel = $fallback;
        }
    }
} catch (Throwable $e) {
    // silencioso
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/caja/estado.css?v=3">
<!-- ==== Fix tamaño de logo en modal del voucher ==== -->
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
  <div class="content-header">
    <div class="container-fluid">
      <div class="cashbar shadow-sm">
        <div class="cashbar-row top">
          <div class="brand">
            <div class="ico"><i class="fas fa-coins"></i></div>
            <div>
              <div class="title">Sistema de ventas</div>
              <div class="meta">Empresa: <strong>"<?= h($empNom) ?>"</strong> • Usuario: <strong><?= h($usrNom) ?></strong></div>
            </div>
          </div>
          <div class="actions">
            <button class="btn btn-sm btn-dark" id="btnAbrirMensual"><i class="fas fa-unlock me-1"></i>Abrir mensual</button>
            <button class="btn btn-sm btn-outline-dark" id="btnCerrarMensual"><i class="fas fa-lock me-1"></i>Cerrar mensual</button>
            <span class="mx-1"></span>
            <button class="btn btn-sm btn-primary" id="btnAbrirDiaria"><i class="fas fa-calendar-day me-1"></i>Abrir diaria</button>
            <button class="btn btn-sm btn-outline-primary" id="btnCerrarDiaria"><i class="fas fa-calendar-check me-1"></i>Cerrar diaria</button>
          </div>
        </div>

        <div class="cashbar-row bottom">
          <div class="chip-group">
            <div class="chip" id="chipMensual">
              <span class="dot" id="dotCM"></span>
              <div>
                <div><span class="label">Caja mensual</span> <span class="code" id="codeCM">—</span> <span class="aux" id="auxCM">—</span></div>
                <div class="small text-muted" id="timeCM">Disponible para apertura</div>
              </div>
            </div>
            <div class="chip" id="chipDiaria">
              <span class="dot" id="dotCD"></span>
              <div>
                <div><span class="label">Caja diaria</span> <span class="code" id="codeCD">—</span> <span class="aux" id="auxCD">—</span></div>
                <div class="small text-muted" id="timeCD">Disponible para apertura</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="cajaMsg" class="mt-2"></div>
    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="row g-3">
        <div class="col-12 col-lg-8 col-xl-9">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                  <h5 class="mb-0">Servicios disponibles</h5>
                  <div class="text-muted small">Solo se muestran servicios activos y vinculados a esta empresa.</div>
                </div>
                <div class="input-group" style="max-width:420px">
                  <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                  <input id="q" class="form-control border-start-0" placeholder="Buscar por nombre o descripción…">
                  <button class="btn btn-outline-secondary" id="clearQ" title="Limpiar"><i class="fas fa-times"></i></button>
                </div>
              </div>
              <!-- Filtros por etiquetas -->
              <div class="d-flex flex-wrap gap-2 mb-3" id="tagChips">
                <button class="chip chip-btn active" data-tag="*"><span class="label">Todos</span></button>
              </div>

              <!-- Estilos específicos del grid (imagen grande protagonista) -->
              <style>
                .svc-card{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;height:100%}
                .svc-thumb{position:relative;background:#f8fafc}
                .svc-thumb::before{content:"";display:block;padding-top:62%} /* relación aprox 16:10 */
                .svc-thumb img{position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain}
                .svc-body{padding:10px;display:flex;flex-direction:column;gap:6px}
                .svc-title{font-weight:800;line-height:1.2}
                .svc-desc{font-size:.87rem;color:#6b7280;max-height:3.2em;overflow:hidden}
                .svc-price{font-weight:900;font-size:1.15rem}
                .svc-note{font-size:.78rem;color:#374151;background:#eef2ff;border-radius:8px;padding:2px 8px;display:inline-block}
                .svc-actions .btn{border-radius:10px}
              </style>

              <!-- Grid -->
              <div class="row g-3" id="grid"></div>

              <!-- Paginación -->
              <nav class="mt-3"><ul class="pagination pagination-sm" id="pager"></ul></nav>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4 col-xl-3">
          <div class="sticky-right">
            <!-- Carrito / Detalle de venta -->
            <div class="card shadow-sm" id="cartCard">
              <div class="card-body">
                <h5 class="mb-2">Detalle de venta</h5>

                <!-- Aviso de caja diaria no abierta -->
                <div id="cartNotice" class="alert alert-warning d-none"></div>

                <!-- Lista de items -->
                <div id="cartList" class="mb-3">
                  <!-- Mensaje vacío por defecto -->
                  <div class="cart-empty text-center p-3">
                    <i class="fas fa-shopping-basket fa-2x mb-2 text-muted"></i>
                    <div>Tu carrito está vacío.</div>
                    <div class="text-muted small">Usa «Añadir» en un servicio para traerlo aquí.</div>
                  </div>
                </div>

                <hr class="my-2">

                <!-- Total -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="h6 mb-0">Total</div>
                  <div class="h5 mb-0" id="cartTotal">S/ 0.00</div>
                </div>

                <!-- Acciones -->
                <div class="d-grid gap-2">
                  <button class="btn btn-success" id="btnPagar">
                    <i class="fas fa-play-circle me-1"></i> Pagar
                  </button>
                  <button class="btn btn-secondary" id="btnCancelar">
                    <i class="fas fa-times-circle me-1"></i> Cancelar
                  </button>
                </div>
              </div>
            </div>
            
          </div>
        </div>

      </div><!-- /row -->
      <!-- Pagos pendientes (nuevo ubicacion, arriba de Fase de pruebas) -->
<div class="row g-3 mt-1" id="vpRow">
  <div class="col-12">
    <div class="card shadow-sm" id="vpCard">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
  <h5 class="mb-0">Pagos pendientes</h5>
</div>
<div class="vp-filters mb-2">
  <div class="vp-filter-row vp-filter-row-main">
    <div class="row g-3 align-items-end">
      <div class="col-12 col-xl-6">
        <label class="form-label small mb-1">Buscar</label>
        <div class="input-group vp-search-group">
          <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
          <input id="vpQ" class="form-control border-start-0" placeholder="DNI, CE, RUC, nro. documento, nombres, razon social, conductor o ticket">
          <button class="btn btn-outline-secondary" id="vpClear" title="Limpiar busqueda"><i class="fas fa-times"></i></button>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <label class="form-label small mb-1">Estado</label>
        <select id="vpEstado" class="form-control form-control-sm">
          <option value="pending" selected>Pendientes</option>
          <option value="paid">Pagadas</option>
          <option value="refund_partial">Devolucion parcial</option>
          <option value="refund_total">Devolucion total</option>
          <option value="all">Todas</option>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <label class="form-label small mb-1">Periodo</label>
        <select id="vpScope" class="form-control form-control-sm">
          <option value="latest" selected>Ultima caja</option>
          <option value="date">Fecha</option>
          <option value="range">Rango</option>
        </select>
      </div>
    </div>
  </div>

  <div class="vp-filter-row vp-filter-row-extra">
    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-4 col-xl-3 d-none" id="vpFechaWrap">
        <label class="form-label small mb-1">Fecha</label>
        <input id="vpFecha" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-12 col-sm-6 col-xl-3 d-none" id="vpDesdeWrap">
        <label class="form-label small mb-1">Desde</label>
        <input id="vpDesde" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-12 col-sm-6 col-xl-3 d-none" id="vpHastaWrap">
        <label class="form-label small mb-1">Hasta</label>
        <input id="vpHasta" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-12 col-xl">
        <div class="d-flex flex-wrap gap-2 vp-filter-actions">
          <button class="btn btn-sm btn-primary" id="vpApply" type="button"><i class="fas fa-filter me-1"></i>Aplicar</button>
          <button class="btn btn-sm btn-outline-secondary" id="vpResetScope" type="button"><i class="fas fa-history me-1"></i>Volver a ultima caja</button>
        </div>
      </div>
    </div>
  </div>

  <div class="vp-filter-meta">
    <div id="vpScopeInfo" class="small text-muted"></div>
    <div id="vpCounter" class="small text-muted"></div>
  </div>
</div>

        <div class="table-responsive">
          <table class="table table-sm align-middle mb-2" id="vpTable">
            <thead class="table-light">
              <tr>
                <th>Ticket</th>
                <th>Fecha</th>
                <th>Cliente / Contratante / Conductor</th>
                <th class="text-end">Total</th>
                <th class="text-end">Pagado</th>
                <th class="text-end">Saldo</th>
                <th>Estado</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody id="vpTBody">
              <tr><td colspan="8" class="text-muted small">Escribe para buscar…</td></tr>
            </tbody>
          </table>
        </div>

        <nav><ul class="pagination pagination-sm" id="vpPager"></ul></nav>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/prueba.php'; ?>
    </div>
  </section>
</div>

<!-- ==== Modal: elegir precio del servicio ==== -->
<div class="modal fade" id="pxModal" tabindex="-1" role="dialog" aria-labelledby="pxModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-secondary text-white">
        <h5 class="modal-title" id="pxModalTitle">Detalle de servicios</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="pxModalAlert" class="modal-inline-alert"></div>
        <div id="pxBody"><!-- Contenido dinámico --></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success btn-sm" id="pxGuardar">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- ==== Modal: Registrar pago (POS) ==== -->
<div class="modal fade" id="payModal" tabindex="-1" role="dialog" aria-labelledby="payModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-success text-white">
        <h5 class="modal-title" id="payModalTitle"><i class="fas fa-cash-register me-2"></i>Registrar pago</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

<div class="modal-body">
  <div id="payModalAlert" class="modal-inline-alert"></div>
  <div class="row g-3">
          <!-- 1. Detalles de pago -->
          <div class="col-12 col-lg-5">
            <div class="pay-box h-100">
              <div class="h6 mb-2">1. Detalles de pago</div>
              <div id="pmDetalleList" class="mb-2 small"></div>
              <div class="border rounded p-2 bg-light">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="fw-bold">Total</div>
                  <div class="fw-bold" id="pmTotal">S/ 0.00</div>
                </div>
                <div id="pmAbonosResumen" class="mt-1 small text-muted"></div>
                <div id="pmSaldoResumen" class="mt-1 fw-bold"></div>
              </div>
            </div>
          </div>

          <!-- 2. Datos de cliente -->
          <div class="col-12 col-lg-7">
            <div class="pay-box h-100">
              <div class="h6 mb-2">2. Datos de cliente <span class="text-muted small">(persona o empresa que saldrá en comprobante)</span></div>

              <div class="row g-2 align-items-end">
                              <div class="col-12 col-md-6">
                  <label>Tipo Doc.*</label>
                  <select id="pmDocTipo" class="form-control form-control-sm">
                    <option value="DNI">DNI</option>
                    <option value="CE">CE</option>
                    <option value="BREVETE">BREVETE</option>
                    <option value="RUC">RUC</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label small mb-1">Documento*</label>
                  <input id="pmDocNum" class="form-control form-control-sm" maxlength="20">
                </div>
                <div class="col-12">
                  <div class="pm-doc-lookup-wrap">
                    <button type="button" class="btn btn-sm pm-btn-lookup pm-btn-reniec" id="pmLookupReniec">
                      <i class="fas fa-id-card mr-1"></i>RENIEC
                    </button>
                    <button type="button" class="btn btn-sm pm-btn-lookup pm-btn-sunat d-none" id="pmLookupSunat">
                      <i class="fas fa-building mr-1"></i>SUNAT
                    </button>
                    <div id="pmLookupHint" class="small text-muted">
                      Completa el documento y usa RENIEC o SUNAT para autocompletar.
                    </div>
                  </div>
                </div>
                <!-- Campo visible solo cuando es RUC -->
                <div class="col-12 pm-ruc d-none">
                  <label class="form-label small mb-1">Razón social*</label>
                  <input id="pmRazon" class="form-control form-control-sm" maxlength="200">
                </div>
                
                <!-- Documento del contratante (solo cuando es RUC) -->
<div class="col-12 col-md-6 pm-ctr d-none">
  <label>Tipo Doc. contratante*</label>
  <select id="pmCtDocTipo" class="form-control form-control-sm">
    <option value="DNI">DNI</option>
    <option value="CE">CE</option>
    <option value="BREVETE">BREVETE</option>
  </select>
</div>
<div class="col-12 col-md-6 pm-ctr d-none">
  <label class="form-label small mb-1">N° doc. contratante*</label>
  <input id="pmCtDocNum" class="form-control form-control-sm" maxlength="20">
</div>
                <!-- Nombres y apellidos: siempre visibles; en RUC son del contratante -->
                <div class="col-12 col-md-6">
                  <label id="lblNombres" class="form-label small mb-1">Nombres*</label>
                  <input id="pmNombres" class="form-control form-control-sm" maxlength="120">
                </div>
                <div class="col-12 col-md-6">
                  <label id="lblApellidos" class="form-label small mb-1">Apellidos*</label>
                  <input id="pmApellidos" class="form-control form-control-sm" maxlength="120">
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label small mb-1">Teléfono <span class="text-muted">(opcional)</span></label>
                  <input id="pmTelefono" class="form-control form-control-sm" maxlength="30">
                </div>
              </div>

                            <!-- Acciones -->
              <div class="pm-actions mt-3">
                <button class="pm-chip pm-chip-red pm-stub d-none" type="button">
                  <i class="fas fa-users"></i><span>Hay más de un conductor</span>
                </button>

                <button id="pmBtnDriver" class="pm-chip pm-chip-blue" type="button">
                  <i class="fas fa-user-friends"></i><span>El conductor es otra persona</span>
                </button>

                <button id="pmClientMore" class="pm-chip pm-chip-yellow" type="button">
                  <i class="fas fa-id-card"></i><span>Guardar más información del conductor</span>
                </button>

                <button class="pm-chip pm-chip-dark pm-stub d-none" type="button">
                  <i class="fas fa-question-circle"></i><span>Aún no se conoce al conductor</span>
                </button>
              </div>

              <!-- Panel: El conductor es otra persona -->
              <div id="pmDriverBox" class="driver-box d-none mt-3">
                <div class="title"><i class="fas fa-user-friends"></i> El conductor es otra persona</div>
                <div class="row g-2">
                  <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Tipo Doc.*</label>
                    <select id="pmCoDocTipo" class="form-control form-control-sm">
                      <option value="DNI">DNI</option>
                      <option value="CE">CE</option>
                      <option value="BREVETE">BREVETE</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-8">
                    <label class="form-label small mb-1">Documento*</label>
                    <input id="pmCoDocNum" class="form-control form-control-sm" maxlength="20">
                  </div>
                  <div class="col-12">
                    <div class="pm-doc-lookup-wrap">
                      <button type="button" class="btn btn-sm pm-btn-lookup pm-btn-reniec d-none" id="pmCoLookupReniec">
                        <i class="fas fa-id-card mr-1"></i>RENIEC
                      </button>
                      <div id="pmCoLookupHint" class="small text-muted">
                        Selecciona DNI para consultar RENIEC y autocompletar el nombre del conductor.
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Nombres*</label>
                    <input id="pmCoNombres" class="form-control form-control-sm" maxlength="120">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Apellidos*</label>
                    <input id="pmCoApellidos" class="form-control form-control-sm" maxlength="120">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Teléfono <span class="text-muted">(opcional)</span></label>
                    <input id="pmCoTel" class="form-control form-control-sm" maxlength="30">
                  </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <button type="button" id="pmDriverMore" class="btn btn-warning btn-sm">
                    <i class="fas fa-clipboard-list me-1"></i> Guardar más información del conductor
                  </button>
                  <button type="button" id="pmDriverCancel" class="btn btn-danger btn-sm">
                    Cancelar
                  </button>
                </div>
              </div>

              <div id="pmConductorExtraBox" class="driver-box conductor-extra-box d-none mt-3">
                <div class="title"><i class="fas fa-id-card"></i> Más información del conductor</div>
                <div id="pmExtraDocInfo" class="small text-muted mb-2">Documento del conductor: —</div>
                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Canal <span class="text-muted">(opcional)</span></label>
                    <select id="pmExtraCanal" class="form-control form-control-sm"></select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Correo <span class="text-muted">(opcional)</span></label>
                    <input id="pmExtraCorreo" type="email" class="form-control form-control-sm" maxlength="150">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Nacimiento <span class="text-muted">(opcional)</span></label>
                    <input id="pmExtraNacimiento" type="date" class="form-control form-control-sm">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Edad <span class="text-muted">(calculada)</span></label>
                    <input id="pmExtraEdad" class="form-control form-control-sm" readonly>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Categoría Auto <span class="text-muted">(opcional)</span></label>
                    <select id="pmExtraCatAuto" class="form-control form-control-sm"></select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Categoría Moto <span class="text-muted">(opcional)</span></label>
                    <select id="pmExtraCatMoto" class="form-control form-control-sm"></select>
                  </div>
                  <div class="col-12">
                    <label class="form-label small mb-1">Nota <span class="text-muted">(opcional)</span></label>
                    <textarea id="pmExtraNota" class="form-control form-control-sm" rows="3" maxlength="255"></textarea>
                  </div>
                </div>
                <div class="d-flex justify-content-end align-items-center mt-2">
                  <button type="button" id="pmExtraCancel" class="btn btn-danger btn-sm">Cancelar</button>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Registrar abonos -->
          <div class="col-12">
            <div class="pay-box">
              <div class="h6 mb-2">3. Registrar abonos</div>

              <div class="row g-2 align-items-end mb-2">
                <div class="col-12 col-sm-3">
                  <label class="form-label small mb-1">Medio de pago</label>
                  <select id="pmMedio" class="form-control form-control-sm"></select>
                </div>
                <div class="col-12 col-sm-2">
                  <label class="form-label small mb-1">Monto</label>
                  <input id="pmMonto" type="number" step="0.01" min="0" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-sm-3">
                  <label class="form-label small mb-1">Referencia</label>
                  <input id="pmRef" class="form-control form-control-sm" maxlength="80" placeholder="según el medio">
                </div>
                <div class="col-12 col-sm-4">
                  <label class="form-label small mb-1">Detalle / Nota</label>
                  <input id="pmObs" class="form-control form-control-sm" maxlength="255">
                </div>
                <div class="col-12">
                  <button id="pmAddAbono" type="button" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Agregar abono</button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-2">
                  <thead class="table-light">
                    <tr>
                      <th style="width:40%">Medio</th>
                      <th style="width:15%">Monto</th>
                      <th style="width:25%">Referencia</th>
                      <th style="width:15%">Agregado</th>
                      <th style="width:5%"></th>
                    </tr>
                  </thead>
                  <tbody id="pmAbonosBody"><tr><td colspan="5" class="text-muted small">— Sin abonos por el momento —</td></tr></tbody>
                </table>
              </div>

              <div class="d-flex align-items-center justify-content-end gap-3">
                <div class="small">Abonos: <span id="pmAbonosTotal">S/ 0.00</span></div>
                <div class="fw-bold">Saldo: <span id="pmSaldo">S/ 0.00</span></div>
              </div>
            </div>
          </div>
        </div><!-- /.row -->
      </div>

      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success btn-sm" id="pmCompletar"><i class="fas fa-check-circle me-1"></i>Completar venta</button>
      </div>
    </div>
  </div>
</div>

<!-- ==== Modal: Voucher / Recibo de venta ==== -->
<div class="modal fade" id="voucherModal" tabindex="-1" role="dialog" aria-labelledby="voucherModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-dark text-white">
        <h5 class="modal-title" id="voucherModalTitle"><i class="fas fa-receipt me-2"></i>Voucher de venta</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="voucherBody"><!-- render dinámico --></div>
      </div>
      <div class="modal-footer py-2 flex-wrap">
        <div class="me-auto d-flex align-items-center" style="gap:8px;">
          <label for="voucherSize" class="small mb-0">Tamaño</label>
          <select id="voucherSize" class="form-select form-select-sm" style="min-width:160px">
            <option value="a4">A4 (210 × 297 mm)</option>
            <option value="ticket80" selected>Ticket 80 mm</option>
            <option value="ticket58">Ticket 58 mm</option>
          </select>
        </div>
        <div class="d-flex" style="gap:8px;">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary btn-sm" id="voucherPrint"><i class="fas fa-print me-1"></i>Imprimir</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ==== Modal pequeño para mensajes (se reusa) ==== -->
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
<!-- ==== Modal: Motivo de cierre extemporáneo ==== -->
<div class="modal fade" id="motivoModal" tabindex="-1" role="dialog" aria-labelledby="motivoTitle" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-warning">
        <h5 class="modal-title" id="motivoTitle">Cierre fuera de tiempo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="motivoText" class="small mb-2">
          Parece que intentas cerrar una caja fuera de tiempo. Es necesario detallar el motivo del retraso.
        </div>
        <textarea id="motivoInput" class="form-control" rows="3" maxlength="500" placeholder="Escribe el motivo..." required></textarea>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="motivoConfirmBtn">Confirmar cierre</button>
      </div>
    </div>
  </div>
</div>
<script>
// ===== Utilidades =====
const BASE_URL = '<?= BASE_URL ?>';
const API_HUB_ENDPOINT = `${BASE_URL}/modules/api_hub/api.php`;
const qs  = (s,ctx=document)=>ctx.querySelector(s);
const qsa = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
const esc = s => (s??'').toString().replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
const money = v => 'S/ ' + Number(v||0).toFixed(2);

// Contexto de cabecera para voucher
const EMPRESA_NOMBRE = '<?= h($empNom) ?>';
const USUARIO_NOMBRE = '<?= h($usrNom) ?>';
const EMPRESA_LOGO   = '<?= h($empLogoRel ?? "") ?>';
// Imagen placeholder inline (evita petición externa y mejora FCP)
const SVC_IMG_PLACEHOLDER = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="640" height="396"><rect width="100%" height="100%" fill="%23f8fafc"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="20" fill="%2394a3b8">Servicio</text></svg>';
let VOUCHER_CTX = { kind:'venta', venta_id:0, abono_ids:[] };

// Control de peticiones de servicios (cancelación + cache en memoria)
let _svcAbort = null;                                // AbortController actual
const SVC_CACHE = Object.create(null);               // key -> { rows, total }

// ===== API helpers =====
async function apiCaja(action, payload=null){
  const opts = payload
    ? {
        method: 'POST',
        credentials: 'same-origin',
        body: (() => {
          const fd = new FormData();
          fd.append('accion', action);
          for (const k in payload) fd.append(k, payload[k]);
          return fd;
        })()
      }
    : {
        method: 'GET',
        credentials: 'same-origin'
      };
  const url = action === 'estado'
    ? `${BASE_URL}/modules/caja/api.php?action=estado`
    : `${BASE_URL}/modules/caja/api.php`;
  const r = await fetch(url, opts);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || 'Error');
  return j;
}

async function apiGET(params, {signal} = {}){
  const usp = new URLSearchParams(params);
  const url = `${BASE_URL}/modules/caja/api.php?${usp.toString()}`;
  const r = await fetch(url, { credentials: 'same-origin', signal });
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || 'Error');
  return j;
}

async function apiHubLookup(action, numero){
  const fd = new FormData();
  fd.append('action', action);
  fd.append('numero', String(numero || '').trim());
  const r = await fetch(API_HUB_ENDPOINT, {
    method: 'POST',
    credentials: 'same-origin',
    body: fd
  });

  let j = null;
  try{
    j = await r.json();
  }catch(_){
    throw new Error('No se pudo procesar la respuesta del servicio.');
  }

  if (!j.ok) throw new Error(j.error || 'No se pudo completar la consulta.');
  return j;
}

// ===== Mensajes / estado de caja =====
function showMsg(title, html, type='success'){
  const mh = qs('#msgModal .modal-header');
  mh.classList.remove('bg-success','bg-danger','text-white');
  if(type==='success'){ mh.classList.add('bg-success','text-white'); }
  if(type==='danger'){ mh.classList.add('bg-danger','text-white'); }
  qs('#msgModalTitle').textContent = title;
  qs('#msgModalBody').innerHTML   = html;
  if (window.jQuery && jQuery.fn && jQuery.fn.modal) jQuery('#msgModal').modal('show');
  else alert(title + ': ' + qs('#msgModalBody').textContent);
}
// ==== Alertas embebidas dentro de modales ====
function modalAlertContainer(modalId){
  // Busca un contenedor con .modal-inline-alert dentro del modal
  return qs(`#${modalId} .modal-inline-alert`) || qs(`#${modalId}Alert`) || null;
}
function showInlineAlert(modalId, type, html){
  const holder = modalAlertContainer(modalId);
  if(!holder) return;
  const icon = (type==='danger') ? 'fa-exclamation-triangle' : (type==='warning' ? 'fa-exclamation-circle' : 'fa-info-circle');
  holder.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show mb-2" role="alert">
      <i class="fas ${icon} me-1"></i> ${html}
      <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close" onclick="this.closest('.alert').remove()">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>`;
  try{
    holder.scrollIntoView({behavior:'smooth', block:'center'});
  }catch(_){}
}
function clearInlineAlert(modalId){
  const holder = modalAlertContainer(modalId);
  if(holder) holder.innerHTML = '';
}

// Simplificación de errores del backend a mensajes amigables
function isRefRequiredError(msg){
  const s = String(msg||'').toLowerCase();
  return s.includes('referencia obligatoria') || s.includes('requiere una referencia');
}
function mapApiError(msg){
  const s = String(msg||'').toLowerCase();
  if (s.includes('no hay caja diaria abierta')) return 'Debes abrir la caja diaria de hoy para registrar pagos.';
  if (s.includes('medio de pago inválido')) return 'Selecciona un medio de pago válido.';
  if (s.includes('monto inválido')) return 'Ingresa un monto mayor a 0.00.';
  if (s.includes('al menos un abono')) return 'Debes registrar al menos un abono para completar la venta.';
  if (isRefRequiredError(s)) return 'Este medio exige referencia. Complétala para continuar.';
  if (s.includes('excede el total')) return 'El total de abonos supera el total de la venta. Ajusta los montos.';
  if (s.includes('excede el saldo')) return 'El monto ingresado supera el saldo. Ingresa un monto menor o igual al saldo.';
  if (s.includes('venta no encontrada')) return 'No se encontró la venta.';
  if (s.includes('la venta está anulada') || s.includes('la venta ya está anulada')) return 'La venta está anulada. No es posible registrar más operaciones.';
  return 'Ocurrió un error. Revisa los datos e inténtalo nuevamente.';
}

function setBtn(id, enabled){ const b = qs(id); if(!b) return; b.disabled=!enabled; b.classList.toggle('disabled',!enabled); }

// Control de venta (global)
let CAN_SELL = false; // true solo si la DIARIA de hoy está ABIERTA
let SELL_REASON = '';
// Último estado recibido (para decidir flujo de cierre)
let LAST_ESTADO = null;

// Modal de motivo (cierre extemporáneo)
let _motivoCtx = { tipo:'', detalle:'', onConfirm:null };
function openMotivoModal({tipo, detalle, onConfirm}){
  _motivoCtx = {tipo, detalle, onConfirm};
  const t = tipo === 'diaria' ? 'diaria' : 'mensual';
  qs('#motivoTitle').textContent = 'Cierre fuera de tiempo';
  qs('#motivoText').innerHTML = `Parece que intentas cerrar una caja <strong>${esc(t)}</strong> fuera de tiempo.<br>Detalle: ${esc(detalle)}.<br>Es necesario detallar el motivo del retraso.`;
  qs('#motivoInput').value = '';
  if (window.jQuery && jQuery.fn.modal) jQuery('#motivoModal').modal('show');
}
document.addEventListener('click',(e)=>{
  const btn = e.target.closest('#motivoConfirmBtn');
  if(!btn) return;
  const m = (qs('#motivoInput').value||'').trim();
  if (m.length < 3){ showMsg('Faltan datos','Describe brevemente el motivo.','danger'); return; }
  const fn = _motivoCtx.onConfirm;
  _motivoCtx.onConfirm = null;
  if (window.jQuery && jQuery.fn.modal) jQuery('#motivoModal').modal('hide');
  if (typeof fn === 'function') fn(m);
});

function setSellUI(canSell, reasonText){
  CAN_SELL = !!canSell;
  SELL_REASON = reasonText || '';

  // Aviso arriba del carrito
  const notice = qs('#cartNotice');
  if (!CAN_SELL) {
    notice.classList.remove('d-none');
    notice.innerHTML = esc(SELL_REASON || 'Debes abrir la caja diaria de hoy para realizar ventas.');
  } else {
    notice.classList.add('d-none');
    notice.innerHTML = '';
  }

  // Botones del carrito
  setBtn('#btnPagar',    CAN_SELL);
  setBtn('#btnCancelar', CAN_SELL);

  // Botones "Añadir" en tarjetas y +/- del carrito
  qsa('.btn-add').forEach(b=>{
    b.disabled = !CAN_SELL;
    b.classList.toggle('disabled', !CAN_SELL);
  });
  qsa('.qty-btn').forEach(b=>{
    b.disabled = !CAN_SELL;
    b.classList.toggle('disabled', !CAN_SELL);
  });

  // NUEVO: Botones "Completar pago" en la tabla de Ventas pendientes
  qsa('.vp-abonar').forEach(b=>{
    b.disabled = !CAN_SELL;
    b.classList.toggle('disabled', !CAN_SELL);
  });
}

function fmtDT(s){
  // s: 'YYYY-MM-DD HH:MM:SS' -> 'DD/MM/YYYY HH:MM'
  if (!s) return null;
  const t = new Date(String(s).replace(' ','T'));
  const dd = String(t.getDate()).padStart(2,'0');
  const mm = String(t.getMonth()+1).padStart(2,'0');
  const yyyy = t.getFullYear();
  const hh = String(t.getHours()).padStart(2,'0');
  const mi = String(t.getMinutes()).padStart(2,'0');
  return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
}

function paintEstado(data){
  LAST_ESTADO = data;

  // Chips (códigos/chip y dots)
  qs('#codeCM').textContent = data.cm.codigo;
  qs('#auxCM').textContent  = data.cm.chip;
  qs('#dotCM').classList.toggle('on', data.cm.estado==='abierta');
  qs('#codeCM').classList.toggle('disabled', data.cm.estado!=='abierta');

  qs('#codeCD').textContent = data.cd.codigo;
  qs('#auxCD').textContent  = data.cd.chip;
  qs('#dotCD').classList.toggle('on', data.cd.estado==='abierta');
  qs('#dotCD').classList.toggle('muted', data.cm.estado!=='abierta');
  qs('#codeCD').classList.toggle('disabled', data.cm.estado!=='abierta' || data.cd.estado!=='abierta');

  // Timestamps
  const timeCM = qs('#timeCM');
  const timeCD = qs('#timeCD');
  if (data.cm.existe) {
    if (data.cm.estado === 'abierta' && data.cm.abierto_en) timeCM.textContent = 'Abierta en: ' + fmtDT(data.cm.abierto_en);
    else if (data.cm.estado === 'cerrada' && data.cm.cerrado_en) timeCM.textContent = 'Cerrada en: ' + fmtDT(data.cm.cerrado_en);
    else timeCM.textContent = 'Disponible para apertura';
  } else timeCM.textContent = 'Disponible para apertura';

  if (data.cd.existe) {
    if (data.cd.estado === 'abierta' && data.cd.abierto_en) timeCD.textContent = 'Abierta en: ' + fmtDT(data.cd.abierto_en);
    else if (data.cd.estado === 'cerrada' && data.cd.cerrado_en) timeCD.textContent = 'Cerrada en: ' + fmtDT(data.cd.cerrado_en);
    else timeCD.textContent = 'Disponible para apertura';
  } else timeCD.textContent = 'Disponible para apertura';

  // Botones barra superior (servidor ya habilita extemporáneos)
  setBtn('#btnAbrirMensual', data.botones.abrir_mensual);
  setBtn('#btnCerrarMensual', data.botones.cerrar_mensual);
  setBtn('#btnAbrirDiaria',   data.botones.abrir_diaria);
  setBtn('#btnCerrarDiaria',  data.botones.cerrar_diaria);

  // Avisos informativos no bloqueantes
  const msg = [];
  if (data.locks.otra_mensual_abierta) {
    msg.push(`<div class="alert alert-info py-2 mb-2">
      <i class="fas fa-info-circle me-1"></i>
      Hay una caja mensual abierta <strong>${esc(data.locks.mensual_abierta_codigo||'')}</strong>. Puedes cerrarla con “Cerrar mensual”.
    </div>`);
  }
  if (data.locks.otra_diaria_abierta) {
    const det = `${esc(data.locks.diaria_abierta_fecha||'')}${data.locks.diaria_abierta_codigo ? ' ('+esc(data.locks.diaria_abierta_codigo)+')' : ''}`;
    msg.push(`<div class="alert alert-info py-2 mb-2">
      <i class="fas fa-info-circle me-1"></i>
      Hay una caja diaria pendiente de cierre <strong>${det}</strong>. Puedes cerrarla con “Cerrar diaria”.
    </div>`);
  }
  qs('#cajaMsg').innerHTML = msg.join('');

  // Política de ventas: permitir si la diaria de HOY está abierta o existe OTRA diaria abierta
  const canSell = (data.cd.estado === 'abierta') || !!data.locks.otra_diaria_abierta;
  let reason = '';
  if (!canSell) {
    if (!data.cm.existe || data.cm.estado!=='abierta') {
      reason = 'Debes abrir primero la caja mensual del período y luego la caja diaria de hoy.';
    } else {
      reason = 'No hay ninguna caja diaria abierta. Abre la caja diaria de hoy para habilitar ventas.';
    }
  }
  setSellUI(canSell, reason);
}

async function refreshEstado(){
  try{
    const j = await apiCaja('estado');
    paintEstado(j);
  }catch(e){
    showMsg('Error', e.message, 'danger');
  }
}

// ===== POS: Estado de servicios =====
const STATE = {
  q:'', tag:'*',
  page:1, per_page:9,
  total:0, pages:1,
  tags:[],
  rows:[],
  picked:{} // { [servicioId]: {precio, nota, rol, precio_origen, precio_lista_id, precio_lista_base, precio_temporal_motivo} }
};

function toPositiveInt(v){
  const n = parseInt(v, 10);
  return Number.isFinite(n) && n > 0 ? n : null;
}
function normalizePickForService(row, current){
  const base = current || {};
  const origen = (String(base.precio_origen || '').toUpperCase() === 'TEMPORAL') ? 'TEMPORAL' : 'LISTA';
  const rowPrice = Number(row?.precio || 0);
  const precio = Number(base.precio ?? rowPrice ?? 0);
  const precioListaId = toPositiveInt(base.precio_lista_id ?? row?.precio_id ?? null);
  const precioListaBaseRaw = base.precio_lista_base ?? rowPrice ?? precio;
  const precioListaBase = Number(precioListaBaseRaw);
  return {
    precio: Number.isFinite(precio) ? precio : 0,
    nota: String(base.nota ?? row?.nota ?? ''),
    rol: String(base.rol ?? row?.rol ?? ''),
    precio_origen: origen,
    precio_lista_id: (origen === 'LISTA') ? precioListaId : null,
    precio_lista_base: (origen === 'LISTA' && Number.isFinite(precioListaBase)) ? precioListaBase : null,
    precio_temporal_motivo: (origen === 'TEMPORAL') ? String(base.precio_temporal_motivo || '') : ''
  };
}
function ensurePicksForRows(rows){
  (rows || []).forEach(s => {
    const id = String(s.id);
    STATE.picked[id] = normalizePickForService(s, STATE.picked[id]);
  });
}
function pickFromStateOrRow(row){
  const id = String(row.id);
  if (!STATE.picked[id]) STATE.picked[id] = normalizePickForService(row, null);
  return STATE.picked[id];
}



// Chips
function paintTags(){
  const cont = qs('#tagChips'); if(!cont) return;
  const frag = document.createDocumentFragment();
  const add = (id, name, active=false)=>{
    const b = document.createElement('button');
    b.className = 'chip chip-btn'+(active?' active':'');
    b.dataset.tag = id;
    b.innerHTML = `<span class="label">${esc(name)}</span>`;
    frag.appendChild(b);
  };
  add('*','Todos', STATE.tag==='*');
  (STATE.tags||[]).forEach(t=> add(String(t.id), t.nombre, STATE.tag===String(t.id)));
  cont.innerHTML=''; cont.appendChild(frag);
}

// Paginación
function paintPager(){
  const ul = qs('#pager'); if(!ul) return;
  const pages = Math.max(1, Math.ceil(STATE.total / STATE.per_page));
  STATE.pages = pages; STATE.page = Math.min(STATE.page, pages);
  const cur = STATE.page;
  const li = [];
  const add = (p, label, disabled=false, active=false)=>
    li.push(`<li class="page-item ${disabled?'disabled':''} ${active?'active':''}"><a class="page-link" href="#" data-p="${p}">${label}</a></li>`);
  add(cur-1,'«',cur<=1,false);
  let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
  for(let p=s;p<=e;p++) add(p,p,false,p===cur);
  add(cur+1,'»',cur>=pages,false);
  ul.innerHTML = li.join('');
}

// Grid
function paintGrid(){
  const grid = qs('#grid'); if(!grid) return;
  if (!STATE.rows.length) {
    grid.innerHTML = `<div class="col-12 text-muted">Sin resultados</div>`;
    qs('#pager').innerHTML = '';
    grid.style.minHeight = '';
    return;
  }

  grid.innerHTML = STATE.rows.map(r=>{
    const pick = pickFromStateOrRow(r);
    const nota = (pick.precio_origen === 'TEMPORAL')
      ? `Temporal: ${esc(pick.precio_temporal_motivo || 'Sin motivo')}`
      : `Nota: ${pick.nota ? esc(pick.nota) : 'Sin nota'}`;
    const img  = r.imagen_path ? `${BASE_URL}/${esc(r.imagen_path)}` : SVC_IMG_PLACEHOLDER;
    const desc = esc(r.descripcion || '');
    return `
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="svc-card shadow-sm">
          <div class="svc-thumb">
            <img src="${img}" alt="${esc(r.nombre)}" loading="lazy" decoding="async" width="640" height="396">
          </div>
          <div class="svc-body">
            <div class="svc-title text-uppercase small">${esc(r.nombre)}</div>
            <div class="svc-desc">${desc}</div>
            <div class="d-flex align-items-center justify-content-between">
              <div class="svc-price">${money(pick.precio)}</div>
            </div>
            <div><span class="svc-note">${nota}</span></div>
            <div class="svc-actions mt-2 d-flex gap-2">
              <button class="btn btn-sm btn-outline-secondary btn-detalle"
                      data-id="${r.id}" data-nom="${esc(r.nombre)}"
                      data-desc="${desc}" data-img="${img}">
                <i class="fas fa-info-circle me-1"></i>Detalle
              </button>
              <button class="btn btn-sm btn-primary btn-add"
                      data-id="${r.id}" data-nom="${esc(r.nombre)}"
                      data-img="${img}" data-precio="${pick.precio}">
                <i class="fas fa-cart-plus me-1"></i>Añadir
              </button>
            </div>
          </div>
        </div>
      </div>`;
  }).join('');

  paintPager();
  grid.style.minHeight = '';

  // Aplicar estado de venta (deshabilitar .btn-add si corresponde)
  setSellUI(CAN_SELL, SELL_REASON);
}

// Cargar tags/servicios
async function loadTags(){
  try{
    const {tags} = await apiGET({action:'svc_tags'});
    STATE.tags = tags || []; paintTags();
  }catch(_){}
}
async function loadServicios(){
  const grid = qs('#grid');
  // Evita salto visual hacia "Pagos pendientes" mientras carga la nueva página.
  const prevHeight = Math.max(120, Math.round(grid.getBoundingClientRect().height));
  grid.style.minHeight = `${prevHeight}px`;
  grid.innerHTML = `<div class="col-12 text-muted">Cargando…</div>`;

  // Clave única para cache por página/tamaño/búsqueda/tag
  const key = `${STATE.page}|${STATE.per_page}|${STATE.q}|${STATE.tag}`;

  // 1) Cache en memoria
  if (SVC_CACHE[key]) {
    const cached = SVC_CACHE[key];
    STATE.rows  = cached.rows.slice();
    STATE.total = cached.total;
    // Asegura picked para filas nuevas
    ensurePicksForRows(STATE.rows);
    paintGrid();
    return;
  }

  // 2) Cancelar petición anterior si existe
  if (_svcAbort) { try { _svcAbort.abort(); } catch(_) {} }
  _svcAbort = new AbortController();

  try {
    const j = await apiGET(
      { action:'servicios', page:STATE.page, per:STATE.per_page, q:STATE.q, tag:STATE.tag },
      { signal: _svcAbort.signal }
    );

    STATE.rows  = j.data || [];
    STATE.total = Number(j.total || 0);
    ensurePicksForRows(STATE.rows);

    // Guardar en cache (copia superficial)
    SVC_CACHE[key] = { rows: STATE.rows.slice(), total: STATE.total };

    paintGrid();
  } catch (e) {
    if (e.name === 'AbortError') return; // petición cancelada: no mostrar error
    grid.innerHTML = `<div class="col-12 text-danger">${esc(e.message)}</div>`;
    grid.style.minHeight = '';
  } finally {
    _svcAbort = null;
  }
}

// ===== Modal Registrar Pago (POS) =====
const PM = {
  medios: [],                 // catálogo medios de pago
  abonos: [],                 // [{id, medio_id, medio, requiere_ref, monto, ref, obs, ts}]
  total: 0,                   // total carrito
  saldo: 0,                   // total - sum(abonos)
  ventaItems: []              // snapshot de carrito con metadata de precio por item
};
const CONDUCTOR_CANALES = ['WHATSAPP', 'LLAMADA', 'SMS', 'PRESENCIAL', 'OTRO'];
const PM_EXTRA = {
  metaLoaded: false,
  categorias: { auto: [], moto: [] },
  docKey: ''
};

function pm_money(n){ return 'S/ ' + Number(n||0).toFixed(2); }

async function loadMediosPago(){
  const j = await apiGET({action:'pos_medios_pago'});
  PM.medios = j.data || [];
  const sel = qs('#pmMedio'); if (!sel) return;
  sel.innerHTML = PM.medios.map(m => {
    const req = (m.requiere_ref === 1 || m.requiere_ref === '1' || m.requiere_ref === true) ? '1' : '0';
    return `<option value="${m.id}" data-req="${req}">${esc(m.nombre)}</option>`;
  }).join('');
}

function buildDocKey(tipo, numero){
  return `${String(tipo||'').trim()}|${String(numero||'').trim()}`;
}
function calcEdadFromNacimiento(iso){
  const raw = String(iso||'').trim();
  if (!raw) return '';
  const dt = new Date(raw + 'T00:00:00');
  if (Number.isNaN(dt.getTime())) return '';
  const hoy = new Date();
  let edad = hoy.getFullYear() - dt.getFullYear();
  const mm = hoy.getMonth() - dt.getMonth();
  if (mm < 0 || (mm === 0 && hoy.getDate() < dt.getDate())) edad--;
  return edad >= 0 ? String(edad) : '';
}
function refreshExtraEdad(){
  const nac = (qs('#pmExtraNacimiento')?.value || '').trim();
  const edad = calcEdadFromNacimiento(nac);
  if (qs('#pmExtraEdad')) qs('#pmExtraEdad').value = edad;
}
function getConductorTargetDoc(){
  const otro = !qs('#pmDriverBox').classList.contains('d-none');
  if (otro){
    return {
      origen: 'conductor',
      doc_tipo: qs('#pmCoDocTipo')?.value || '',
      doc_numero: (qs('#pmCoDocNum')?.value || '').trim(),
      label: 'conductor'
    };
  }
  const cliDocTipo = qs('#pmDocTipo')?.value || '';
  if (cliDocTipo === 'RUC'){
    return {
      origen: 'contratante',
      doc_tipo: qs('#pmCtDocTipo')?.value || '',
      doc_numero: (qs('#pmCtDocNum')?.value || '').trim(),
      label: 'contratante'
    };
  }
  return {
    origen: 'cliente',
    doc_tipo: cliDocTipo,
    doc_numero: (qs('#pmDocNum')?.value || '').trim(),
    label: 'cliente'
  };
}
function renderConductorExtraSelects(selected = {}){
  const canal = String(selected.canal || '');
  const catAuto = String(selected.categoria_auto_id || '');
  const catMoto = String(selected.categoria_moto_id || '');

  const selCanal = qs('#pmExtraCanal');
  if (selCanal){
    const opts = ['<option value="">Sin canal</option>'].concat(
      CONDUCTOR_CANALES.map(c => `<option value="${esc(c)}">${esc(c)}</option>`)
    );
    selCanal.innerHTML = opts.join('');
    selCanal.value = canal;
  }

  const autoOpts = ['<option value="">Sin categoría</option>'].concat(
    (PM_EXTRA.categorias.auto || []).map(r => `<option value="${r.id}">${esc(r.codigo)}</option>`)
  );
  const motoOpts = ['<option value="">Sin categoría</option>'].concat(
    (PM_EXTRA.categorias.moto || []).map(r => `<option value="${r.id}">${esc(r.codigo)}</option>`)
  );

  const selAuto = qs('#pmExtraCatAuto');
  const selMoto = qs('#pmExtraCatMoto');
  if (selAuto){
    selAuto.innerHTML = autoOpts.join('');
    selAuto.value = catAuto;
  }
  if (selMoto){
    selMoto.innerHTML = motoOpts.join('');
    selMoto.value = catMoto;
  }
}
function clearConductorExtraForm(){
  renderConductorExtraSelects();
  if (qs('#pmExtraCorreo')) qs('#pmExtraCorreo').value = '';
  if (qs('#pmExtraNacimiento')) qs('#pmExtraNacimiento').value = '';
  if (qs('#pmExtraNota')) qs('#pmExtraNota').value = '';
  if (qs('#pmExtraEdad')) qs('#pmExtraEdad').value = '';
}
function fillConductorExtraForm(perfil){
  const p = perfil || {};
  renderConductorExtraSelects({
    canal: p.canal || '',
    categoria_auto_id: p.categoria_auto_id || '',
    categoria_moto_id: p.categoria_moto_id || ''
  });
  if (qs('#pmExtraCorreo')) qs('#pmExtraCorreo').value = p.email || '';
  if (qs('#pmExtraNacimiento')) qs('#pmExtraNacimiento').value = p.nacimiento || '';
  if (qs('#pmExtraNota')) qs('#pmExtraNota').value = p.nota || '';
  refreshExtraEdad();
}
function showConductorExtraBox(show){
  const box = qs('#pmConductorExtraBox');
  if (!box) return;
  box.classList.toggle('d-none', !show);
  if (!show){
    PM_EXTRA.docKey = '';
    clearConductorExtraForm();
    if (qs('#pmExtraDocInfo')) qs('#pmExtraDocInfo').textContent = 'Documento del conductor: —';
  }
}
function readConductorExtraPayload(){
  return {
    canal: (qs('#pmExtraCanal')?.value || '').trim(),
    email: (qs('#pmExtraCorreo')?.value || '').trim(),
    nacimiento: (qs('#pmExtraNacimiento')?.value || '').trim(),
    categoria_auto_id: (qs('#pmExtraCatAuto')?.value || '').trim(),
    categoria_moto_id: (qs('#pmExtraCatMoto')?.value || '').trim(),
    nota: (qs('#pmExtraNota')?.value || '').trim()
  };
}
function validateConductorExtraPayload(extra){
  if (extra.email){
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(extra.email);
    if (!ok) return 'El correo del conductor no es válido.';
  }
  if (extra.nacimiento){
    const dt = new Date(extra.nacimiento + 'T00:00:00');
    if (Number.isNaN(dt.getTime())) return 'La fecha de nacimiento del conductor no es válida.';
    const hoy = new Date();
    hoy.setHours(0,0,0,0);
    if (dt > hoy) return 'La fecha de nacimiento del conductor no puede ser futura.';
  }
  return '';
}
async function loadConductorExtraMeta(){
  if (PM_EXTRA.metaLoaded) return;
  const j = await apiGET({action:'conductor_perfil_meta'});
  PM_EXTRA.categorias = j.categorias || { auto: [], moto: [] };
  PM_EXTRA.metaLoaded = true;
  renderConductorExtraSelects();
}
async function openConductorExtraBox(){
  try{
    await loadConductorExtraMeta();
  }catch(err){
    showInlineAlert('payModal','danger', mapApiError(err.message || ''));
    return;
  }
  const t = getConductorTargetDoc();
  if (!['DNI','CE','BREVETE'].includes(t.doc_tipo) || !t.doc_numero){
    showInlineAlert('payModal','danger','Primero completa el documento del conductor (cliente, contratante o conductor declarado).');
    return;
  }

  showConductorExtraBox(true);
  const info = `Documento del ${t.label}: ${t.doc_tipo} ${t.doc_numero}`;
  if (qs('#pmExtraDocInfo')) qs('#pmExtraDocInfo').textContent = info;

  const key = buildDocKey(t.doc_tipo, t.doc_numero);
  if (PM_EXTRA.docKey === key) return;

  PM_EXTRA.docKey = key;
  clearConductorExtraForm();
  try{
    const j = await apiGET({
      action: 'conductor_perfil_get',
      doc_tipo: t.doc_tipo,
      doc_numero: t.doc_numero
    });
    if (j.perfil) fillConductorExtraForm(j.perfil);
  }catch(err){
    showInlineAlert('payModal','danger', mapApiError(err.message || ''));
  }
}

function computePMFromCart(){
  // snapshot de carrito (ahora con miniaturas)
  PM.ventaItems = Object.keys(CART.items).map(id=>{
    const it = CART.items[id];
    const origen = String(it.precio_origen || 'LISTA').toUpperCase() === 'TEMPORAL' ? 'TEMPORAL' : 'LISTA';
    return {
      servicio_id: parseInt(id,10),
      nombre: it.nom,
      qty: it.qty,
      precio: Number(it.precio||0),
      img: it.img || '',
      precio_origen: origen,
      precio_lista_id: origen === 'LISTA' ? (toPositiveInt(it.precio_lista_id) || null) : null,
      precio_lista_base: origen === 'LISTA' ? Number(it.precio_lista_base ?? it.precio ?? 0) : null,
      precio_temporal_motivo: origen === 'TEMPORAL' ? String(it.precio_temporal_motivo || '') : ''
    };
  });
  PM.total = PM.ventaItems.reduce((a,x)=>a + x.qty*x.precio, 0);
  const abonado = PM.abonos.reduce((a,x)=>a + x.monto, 0);
  PM.saldo = Math.max(0, PM.total - abonado);
}

function renderPayLeft(){
  const cont = qs('#pmDetalleList');
  if(!PM.ventaItems.length){
    cont.innerHTML = `<div class="text-muted small">— No hay ítems —</div>`;
  }else{
    cont.innerHTML = PM.ventaItems.map(x=>`
      <div class="pm-item">
        <div class="pm-left">
          <div class="pm-thumb">
            ${ x.img ? `<img src="${esc(x.img)}" alt="">` : `<i class="fas fa-image text-muted"></i>` }
          </div>
          <div>
            <div class="pm-title">${esc(x.nombre)}</div>
            <div class="pm-sub">Cantidad: ${x.qty}</div>
          </div>
        </div>
        <div class="pm-amount">${pm_money(x.precio * x.qty)}</div>
      </div>
    `).join('');
  }
  qs('#pmTotal').textContent = pm_money(PM.total);
  const abonosText = PM.abonos.length
    ? PM.abonos.map(a=>`${esc(a.medio)}: ${pm_money(a.monto)} <span class="text-muted">(${a.ts})</span>`).join(' • ')
    : '— Sin abonos por el momento —';
  qs('#pmAbonosResumen').innerHTML = abonosText;
  qs('#pmSaldoResumen').innerHTML  = 'Saldo: ' + pm_money(PM.saldo);
}

function renderAbonosTable(){
  const tb = qs('#pmAbonosBody');
  if (!PM.abonos.length){
    tb.innerHTML = `<tr><td colspan="5" class="text-muted small">— Sin abonos por el momento —</td></tr>`;
  }else{
    tb.innerHTML = PM.abonos.map(a=>`
      <tr>
        <td>${esc(a.medio)}</td>
        <td>${pm_money(a.monto)}</td>
        <td>${esc(a.ref||'')}</td>
        <td class="small text-muted">${esc(a.ts)}</td>
        <td><button class="btn btn-link btn-sm text-danger pm-del" data-id="${a.id}" title="Quitar"><i class="fas fa-times"></i></button></td>
      </tr>`).join('');
  }
  qs('#pmAbonosTotal').textContent = pm_money(PM.abonos.reduce((s,x)=>s+x.monto,0));
  qs('#pmSaldo').textContent       = pm_money(PM.saldo);
}

function normalizeVoucherCtx(v){
  const kind = (String(v?.kind || 'venta').toLowerCase() === 'abono') ? 'abono' : 'venta';
  const ventaId = Number(v?.venta_id || 0);
  const abonoIds = Array.isArray(v?.abono_ids)
    ? v.abono_ids.map(x=>parseInt(x,10)).filter(x=>Number.isFinite(x) && x>0)
    : [];
  return { kind, venta_id: (Number.isFinite(ventaId) ? Math.trunc(ventaId) : 0), abono_ids: abonoIds };
}

function buildVoucherPdfUrl(size){
  if (!VOUCHER_CTX || !(VOUCHER_CTX.venta_id > 0)) return '';
  const s = (size === 'a4' || size === 'ticket58' || size === 'ticket80') ? size : 'ticket80';
  const p = new URLSearchParams({
    action: 'voucher_pdf',
    venta_id: String(VOUCHER_CTX.venta_id),
    kind: VOUCHER_CTX.kind === 'abono' ? 'abono' : 'venta',
    presentation: 'cliente',
    size: s
  });
  if (Array.isArray(VOUCHER_CTX.abono_ids) && VOUCHER_CTX.abono_ids.length){
    p.set('abono_ids', VOUCHER_CTX.abono_ids.join(','));
  }
  return `${BASE_URL}/modules/caja/api_ventas.php?${p.toString()}`;
}

function openVoucher(v){
  VOUCHER_CTX = normalizeVoucherCtx(v || {});
  const isAbono = (VOUCHER_CTX.kind === 'abono');

  const el  = qs('#voucherBody');
  const fmt = (n)=>'S/ ' + Number(n||0).toFixed(2);
  const modalTitle = isAbono ? 'Voucher de abono' : 'Voucher de venta';
  const titleEl = qs('#voucherModalTitle');
  if (titleEl) titleEl.innerHTML = `<i class="fas fa-receipt me-2"></i>${modalTitle}`;

  // Logo opcional
  const logoHtml = (EMPRESA_LOGO && EMPRESA_LOGO.trim() !== '')
    ? `<div class="v-head-left"><img class="v-logo" src="${esc(EMPRESA_LOGO)}" alt="Logo"></div>`
    : '';

  // Cliente (natural o jurídica)
  let clienteBlock = '';
  if (v.cliente.tipo_persona === 'JURIDICA') {
    clienteBlock = `
      <div class="v-grid">
        <div class="text-muted">Documento</div> <div>RUC ${esc(v.cliente.doc)}</div>
        <div class="text-muted">Razón social</div> <div>${esc(v.cliente.razon)}</div>
        <div class="text-muted">Teléfono</div> <div>${esc(v.cliente.telefono||'—')}</div>
      </div>`;
  } else {
    clienteBlock = `
      <div class="v-grid">
        <div class="text-muted">Documento</div> <div>${esc(v.cliente.tipo)} ${esc(v.cliente.doc)}</div>
        <div class="text-muted">Nombre</div>    <div>${esc(v.cliente.nombres)} ${esc(v.cliente.apellidos)}</div>
        <div class="text-muted">Teléfono</div>  <div>${esc(v.cliente.telefono||'—')}</div>
      </div>`;
  }

  const hasContratante = !!(v.contratante && (
    (v.contratante.tipo && v.contratante.doc) ||
    v.contratante.nombres || v.contratante.apellidos || v.contratante.telefono
  ));
  const contratanteBlock = hasContratante ? `
    <div class="v-box">
      <div class="v-title">Contratante</div>
      <div class="v-grid">
        <div class="text-muted">Documento</div> <div>${esc(v.contratante.tipo||'')} ${esc(v.contratante.doc||'')}</div>
        <div class="text-muted">Nombre</div>    <div>${esc(v.contratante.nombres||'')} ${esc(v.contratante.apellidos||'')}</div>
        <div class="text-muted">Teléfono</div>  <div>${esc(v.contratante.telefono||'—')}</div>
      </div>
    </div>
  ` : '';

  // Conductor (siempre mostramos sección)
  const condDoc = (v.conductor.tipo && v.conductor.doc) ? `${esc(v.conductor.tipo)} ${esc(v.conductor.doc)}` : '—';
  const conductorBlock = `
    <div class="v-grid">
      <div class="text-muted">Documento</div> <div>${condDoc}</div>
      <div class="text-muted">Nombre</div>    <div>${esc(v.conductor.nombres||'')} ${esc(v.conductor.apellidos||'')}</div>
      <div class="text-muted">Teléfono</div>  <div>${esc(v.conductor.telefono||'—')}</div>
    </div>`;

  const itemsRows = (v.items && v.items.length)
    ? v.items.map(it=>`
      <tr>
        <td>
          ${esc(it.nombre)}
          <div class="text-muted small">x${it.qty} · ${fmt(it.precio)}</div>
        </td>
        <td class="text-end">${fmt(it.qty * it.precio)}</td>
      </tr>
    `).join('')
    : `<tr><td colspan="2" class="text-muted small">— Sin ítems —</td></tr>`;

  const abonoRows = (v.abonos && v.abonos.length)
    ? v.abonos.map(a=>`
        <tr>
          <td>${esc(a.medio)}<div class="text-muted small">${esc(a.ref||'')}</div></td>
          <td class="text-end">${fmt(a.monto)}</td>
        </tr>
      `).join('')
    : `<tr><td colspan="2" class="text-muted small">— Sin abonos —</td></tr>`;

  el.innerHTML = `
    <div class="voucher">
      <div class="v-box">
        <div class="v-head">
          ${logoHtml}
          <div class="v-head-right">
            <div class="fw-bold">${esc(v.empresa)}</div>
            <div class="text-muted small">${esc(v.fecha)}</div>
            <div class="small">Ticket: <strong>${esc(v.ticket)}</strong> • Cajero: <strong>${esc(v.cajero)}</strong></div>
          </div>
        </div>
      </div>

      <div class="v-box">
        <div class="v-title">Cliente</div>
        ${clienteBlock}
      </div>

      ${contratanteBlock}

      <div class="v-box">
        <div class="v-title">Conductor</div>
        ${conductorBlock}
      </div>

      <div class="v-box">
        <div class="v-title">${isAbono ? 'Detalle de venta' : 'Items'}</div>
        <table><tbody>${itemsRows}</tbody></table>
      </div>

      <div class="v-box">
        <div class="v-title">${isAbono ? 'Abonos registrados' : 'Abonos'}</div>
        <table><tbody>${abonoRows}</tbody></table>
      </div>

      <div class="v-box">
        <div class="t"><div class="fw-bold">Total</div>  <div class="v-total">${fmt(v.totales.total)}</div></div>
        <div class="t"><div>Pagado</div>                 <div class="v-total">${fmt(v.totales.pagado)}</div></div>
        <div class="t"><div>Saldo</div>                  <div class="v-total">${fmt(v.totales.saldo)}</div></div>
      </div>
    </div>
  `;

  if (window.jQuery){
    jQuery('#payModal').modal('hide');
    jQuery('#voucherModal').modal('show');
  }
}

function printVoucherNode(node, size){
  // CSS por tamaño
  let pageCSS = '';
  if (size === 'ticket80') {
    pageCSS = `
      @page { size: 80mm auto; margin: 2mm; }
      body{ width:80mm; margin:0; padding:0; }
      .voucher{ width:100%; margin:0; }
    `;
  } else if (size === 'ticket58') {
    pageCSS = `
      @page { size: 58mm auto; margin: 2mm; }
      body{ width:58mm; margin:0; padding:0; }
      .voucher{ width:100%; margin:0; }
    `;
  } else {
    pageCSS = `
      @page { size: A4; margin: 10mm; }
      body{ padding:16px; }
      .voucher{ max-width:760px; margin:0 auto; }
    `;
  }

  const html = `
<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Voucher</title>
<base href="${document.baseURI}">
<style>
body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu,"Helvetica Neue","Noto Sans",Arial;}
.v-box{border:1px solid #e5e7eb; border-radius:12px; padding:10px; margin-bottom:8px;}
.v-title{font-weight:800; text-transform:uppercase; margin-bottom:6px; font-size:12px;}
table{width:100%; border-collapse:collapse;}
td,th{padding:6px 8px; border-bottom:1px dashed #e5e7eb; font-size:12px; line-height:1.25;}
.t{display:flex; justify-content:space-between; align-items:center;}
.v-total{font-weight:900;}
/* Evitar cortes feos */
.v-box{ break-inside: avoid; page-break-inside: avoid; }

/* ==== NUEVO: tamaño fijo del logo en impresión ==== */
.v-head{display:flex; align-items:center; gap:8px; margin-bottom:4px;}
.v-logo{width:48px; height:48px; object-fit:contain; border-radius:50%; border:1px solid #e5e7eb;}

${pageCSS}
</style>
</head><body>${node.innerHTML}</body></html>`;

  const w = window.open('', 'voucher_print');
  w.document.open(); w.document.write(html); w.document.close();
  w.focus(); w.print();
  setTimeout(()=>{ try{ w.close(); }catch(_){ } }, 200);
}

// Listener ÚNICO para imprimir voucher (usa #voucherSize)
document.addEventListener('click', (e)=>{
  const b = e.target.closest('#voucherPrint');
  if (!b) return;
  e.preventDefault();
  e.stopImmediatePropagation(); // evita que otro handler (si quedara) dispare otra impresión
  const size = (qs('#voucherSize')?.value) || 'a4';
  const pdfUrl = buildVoucherPdfUrl(size);
  if (pdfUrl){
    const win = window.open(pdfUrl, 'voucher_pdf');
    if (!win){
      showMsg('Aviso','Tu navegador bloqueó la ventana del PDF. Habilita pop-ups para este sitio.','danger');
    }
    return;
  }
  const node = qs('#voucherBody');
  if (node) printVoucherNode(node, size);
});

function clearDriverBox(){
  qs('#pmCoDocTipo').value = 'DNI';
  qs('#pmCoDocNum').value = '';
  qs('#pmCoNombres').value = '';
  qs('#pmCoApellidos').value = '';
  qs('#pmCoTel').value = '';
  syncDriverLookupButtons();
}
function showDriverBox(v){
  const box = qs('#pmDriverBox');
  box.classList.toggle('d-none', !v);
  if (!v) clearDriverBox();
  else syncDriverLookupButtons();
  if (!qs('#pmConductorExtraBox').classList.contains('d-none')){
    PM_EXTRA.docKey = '';
    const t = getConductorTargetDoc();
    if (['DNI','CE','BREVETE'].includes(t.doc_tipo) && t.doc_numero){
      openConductorExtraBox();
    } else {
      showConductorExtraBox(false);
    }
  }
}

function pmLookupDocType(){
  return (qs('#pmDocTipo')?.value || '').trim().toUpperCase();
}
function setLookupButtonsBusy(isBusy){
  const reniec = qs('#pmLookupReniec');
  const sunat  = qs('#pmLookupSunat');
  if (reniec) reniec.disabled = !!isBusy;
  if (sunat)  sunat.disabled  = !!isBusy;
}
function syncLookupButtons(){
  const t = pmLookupDocType();
  const reniec = qs('#pmLookupReniec');
  const sunat  = qs('#pmLookupSunat');
  const hint   = qs('#pmLookupHint');
  if (reniec) reniec.classList.toggle('d-none', t !== 'DNI');
  if (sunat)  sunat.classList.toggle('d-none', t !== 'RUC');

  if (hint){
    if (t === 'DNI') hint.textContent = 'Consulta DNI en RENIEC para autocompletar nombres y apellidos.';
    else if (t === 'RUC') hint.textContent = 'Consulta RUC en SUNAT para autocompletar la razón social.';
    else hint.textContent = 'Selecciona DNI o RUC para usar consulta automática.';
  }
}

function syncDriverLookupButtons(){
  const t = (qs('#pmCoDocTipo')?.value || '').trim().toUpperCase();
  const reniec = qs('#pmCoLookupReniec');
  const hint   = qs('#pmCoLookupHint');
  if (reniec) reniec.classList.toggle('d-none', t !== 'DNI');

  if (hint){
    if (t === 'DNI') hint.textContent = 'Consulta DNI en RENIEC para autocompletar el nombre del conductor.';
    else hint.textContent = 'Selecciona DNI para usar la consulta automática del conductor.';
  }
}

async function doLookupReniec(){
  clearInlineAlert('payModal');
  const docTipo = pmLookupDocType();
  const dni = (qs('#pmDocNum')?.value || '').trim();
  if (docTipo !== 'DNI'){
    showInlineAlert('payModal','warning','Para usar RENIEC selecciona tipo de documento DNI.');
    return;
  }
  if (!/^\d{8}$/.test(dni)){
    showInlineAlert('payModal','danger','El DNI debe tener 8 dígitos.');
    return;
  }

  try{
    setLookupButtonsBusy(true);
    const j = await apiHubLookup('consultar_dni', dni);
    const d = j.data || {};
    const nombres = String(d.nombres || '').trim();
    const apellidos = [d.apellido_paterno || '', d.apellido_materno || ''].join(' ').replace(/\s+/g,' ').trim();
    if (nombres) qs('#pmNombres').value = nombres;
    if (apellidos) qs('#pmApellidos').value = apellidos;
    showInlineAlert('payModal','success','Datos obtenidos desde RENIEC.');
  }catch(err){
    showInlineAlert('payModal','danger', esc(err.message || 'No se pudo consultar RENIEC.'));
  }finally{
    setLookupButtonsBusy(false);
  }
}

async function doLookupSunat(){
  clearInlineAlert('payModal');
  const docTipo = pmLookupDocType();
  const ruc = (qs('#pmDocNum')?.value || '').trim();
  if (docTipo !== 'RUC'){
    showInlineAlert('payModal','warning','Para usar SUNAT selecciona tipo de documento RUC.');
    return;
  }
  if (!/^\d{11}$/.test(ruc)){
    showInlineAlert('payModal','danger','El RUC debe tener 11 dígitos.');
    return;
  }

  try{
    setLookupButtonsBusy(true);
    const j = await apiHubLookup('consultar_ruc', ruc);
    const d = j.data || {};
    const razon = String(d.razon_social || '').trim();
    if (!razon){
      showInlineAlert('payModal','danger','No se encontró razón social para ese RUC.');
      return;
    }
    qs('#pmRazon').value = razon;
    showInlineAlert('payModal','success','Datos obtenidos desde SUNAT.');
  }catch(err){
    showInlineAlert('payModal','danger', esc(err.message || 'No se pudo consultar SUNAT.'));
  }finally{
    setLookupButtonsBusy(false);
  }
}

async function doLookupReniecDriver(){
  clearInlineAlert('payModal');
  const docTipo = (qs('#pmCoDocTipo')?.value || '').trim().toUpperCase();
  const dni = (qs('#pmCoDocNum')?.value || '').trim();
  if (docTipo !== 'DNI'){
    showInlineAlert('payModal','warning','Para usar RENIEC en el conductor selecciona tipo de documento DNI.');
    return;
  }
  if (!/^\d{8}$/.test(dni)){
    showInlineAlert('payModal','danger','El DNI del conductor debe tener 8 dígitos.');
    return;
  }

  const btn = qs('#pmCoLookupReniec');
  try{
    if (btn) btn.disabled = true;
    const j = await apiHubLookup('consultar_dni', dni);
    const d = j.data || {};
    const nombres = String(d.nombres || '').trim();
    const apellidos = [d.apellido_paterno || '', d.apellido_materno || ''].join(' ').replace(/\s+/g,' ').trim();
    if (nombres) qs('#pmCoNombres').value = nombres;
    if (apellidos) qs('#pmCoApellidos').value = apellidos;
    showInlineAlert('payModal','success','Datos del conductor obtenidos desde RENIEC.');
  }catch(err){
    showInlineAlert('payModal','danger', esc(err.message || 'No se pudo consultar RENIEC.'));
  }finally{
    if (btn) btn.disabled = false;
  }
}

function resetPayModal(){
  PM.abonos = [];
  qs('#pmDocTipo').value = 'DNI';
  qs('#pmDocNum').value  = '';
  qs('#pmCtDocTipo').value = 'DNI';   // NUEVO
  qs('#pmCtDocNum').value  = '';      // NUEVO
  qs('#pmRazon').value   = '';
  qs('#pmNombres').value = '';
  qs('#pmApellidos').value = '';
  qs('#pmTelefono').value = '';
  qs('#pmMonto').value   = '';
  qs('#pmRef').value     = '';
  qs('#pmObs').value     = '';
  clearDriverBox();
  showConductorExtraBox(false);
  showDriverBox(false);
  clearConductorExtraForm();
  setLookupButtonsBusy(false);
  toggleDocUI(); // ajustar UI inicial
}

function toggleDocUI(){
  const isRUC = (qs('#pmDocTipo').value === 'RUC');
  qsa('.pm-ruc').forEach(el=> el.classList.toggle('d-none', !isRUC));
  qsa('.pm-ctr').forEach(el=> el.classList.toggle('d-none', !isRUC)); // NUEVO: doc contratante
  qs('#lblNombres').innerHTML   = isRUC ? 'Nombres* <span class="text-muted small">(contratante)</span>' : 'Nombres*';
  qs('#lblApellidos').innerHTML = isRUC ? 'Apellidos* <span class="text-muted small">(contratante)</span>' : 'Apellidos*';
  syncLookupButtons();
}

async function openPayModal(){
  if (!Object.keys(CART.items).length){
    showMsg('Aviso','Tu carrito está vacío.','danger'); return;
  }
  await loadMediosPago();
  try{
    await loadConductorExtraMeta();
  }catch(err){
    showMsg('Aviso', mapApiError(err.message || ''), 'danger');
  }
  resetPayModal();
  computePMFromCart();
  renderPayLeft();
  renderAbonosTable();
  if (window.jQuery) jQuery('#payModal').modal('show');
}

// Eventos internos del modal
document.addEventListener('change', (e)=>{
  if (e.target && e.target.id === 'pmDocTipo') toggleDocUI();
  if (e.target && e.target.id === 'pmMedio') {
    const opt = e.target.selectedOptions[0];
    const req = opt ? (opt.dataset.req === '1') : false;
    const ref = qs('#pmRef');
    ref.placeholder = req ? 'Obligatoria para este medio' : 'Opcional';
  }
  if (e.target && e.target.id === 'pmExtraNacimiento') {
    refreshExtraEdad();
  }
  if (e.target && ['pmDocTipo','pmDocNum','pmCtDocTipo','pmCtDocNum','pmCoDocTipo','pmCoDocNum'].includes(e.target.id)) {
    if (e.target.id === 'pmCoDocTipo' || e.target.id === 'pmCoDocNum') {
      syncDriverLookupButtons();
    }
    if (!qs('#pmConductorExtraBox').classList.contains('d-none')){
      PM_EXTRA.docKey = '';
      openConductorExtraBox();
    }
  }
});

// Validación en tiempo real (mientras se escribe) para #payModal
document.addEventListener('input', (e)=>{
  if (e.target && e.target.id === 'pmMonto'){
    const montoEl = e.target;
    const v = Number(montoEl.value||0);
    const abonado = PM.abonos.reduce((s,x)=>s+x.monto,0);
    const restante = Math.max(0, PM.total - abonado);
    if (!isFinite(v) || v<=0){ montoEl.classList.add('is-invalid'); return; }
    if (v > restante){ montoEl.classList.add('is-invalid'); return; }
    montoEl.classList.remove('is-invalid');
  }
  if (e.target && e.target.id === 'pmRef'){
    const refEl = e.target;
    const opt = qs('#pmMedio')?.selectedOptions?.[0];
    const requiere = opt ? (opt.dataset.req==='1') : false;
    const has = (refEl.value||'').trim() !== '';
    if (requiere && !has){ refEl.classList.add('is-invalid'); }
    else { refEl.classList.remove('is-invalid'); }
  }
});

document.addEventListener('click',(e)=>{
  if (e.target.closest('#pmLookupReniec')){
    doLookupReniec();
    return;
  }
  if (e.target.closest('#pmLookupSunat')){
    doLookupSunat();
    return;
  }
  if (e.target.closest('#pmCoLookupReniec')){
    doLookupReniecDriver();
    return;
  }
  if (e.target.closest('#pmClientMore') || e.target.closest('#pmDriverMore')){
    openConductorExtraBox();
    return;
  }
  if (e.target.closest('#pmExtraCancel')){
    showConductorExtraBox(false);
    return;
  }

  // Chips informativos sin implementación
  if (e.target.closest('.pm-stub')){
    showMsg('En desarrollo','Esta opción se habilitará más adelante.'); return;
  }

  // Abrir panel de conductor (otra persona)
  if (e.target.closest('#pmBtnDriver')){
    showDriverBox(true); return;
  }

  // Cancelar panel de conductor
  if (e.target.closest('#pmDriverCancel')){
    showDriverBox(false); return;
  }

  // Agregar abono
    if (e.target.closest('#pmAddAbono')){
    const medioSel = qs('#pmMedio');
    const id = parseInt(medioSel.value||'0',10);
    const opt = medioSel.selectedOptions[0];
    const montoEl = qs('#pmMonto');
    const refEl   = qs('#pmRef');
    const obsEl   = qs('#pmObs');

    // Limpieza visual
    montoEl.classList.remove('is-invalid');
    refEl.classList.remove('is-invalid');

    if (!id || !opt){
      showInlineAlert('payModal','danger','Selecciona un medio de pago.');
      return;
    }
    const requiere = (opt.dataset.req === '1');
    const monto = Number(montoEl.value||0);
    if (!isFinite(monto) || monto<=0){
      montoEl.classList.add('is-invalid');
      showInlineAlert('payModal','danger','Ingresa un monto mayor a 0.00.');
      return;
    }
    const abonado = PM.abonos.reduce((s,x)=>s+x.monto,0);
    const restante = Math.max(0, PM.total - abonado);
    if (monto > restante){
      montoEl.classList.add('is-invalid');
      showInlineAlert('payModal','danger','El monto ingresado supera el saldo. Ingresa un monto menor o igual al saldo.');
      return;
    }

    const ref = (refEl.value||'').trim();
    if (requiere && !ref){
      refEl.classList.add('is-invalid');
      showInlineAlert('payModal','danger','Este medio exige referencia. Complétala para continuar.');
      return;
    }
    const obs = (obsEl.value||'').trim();

    PM.abonos.push({ id:Date.now(), medio_id:id, medio:opt.textContent, requiere_ref:requiere?1:0, monto, ref, obs, ts:new Date().toLocaleString() });
    computePMFromCart(); renderPayLeft(); renderAbonosTable();

    montoEl.value=''; refEl.value=''; obsEl.value='';
    return;
  }

  // Quitar abono
  const del = e.target.closest('.pm-del');
  if (del){
    const id = parseInt(del.dataset.id||'0',10);
    PM.abonos = PM.abonos.filter(a=>a.id!==id);
    computePMFromCart(); renderPayLeft(); renderAbonosTable();
    return;
  }

  // Completar venta
    if (e.target.closest('#pmCompletar')){
    (async()=>{
      try{
        // Limpieza alert
        clearInlineAlert('payModal');

        // Lectura de datos de cliente
        const docTipo   = qs('#pmDocTipo').value;
        const docNum    = (qs('#pmDocNum').value||'').trim();
        const razon     = (qs('#pmRazon').value||'').trim();
        const nombres   = (qs('#pmNombres').value||'').trim();
        const apellidos = (qs('#pmApellidos').value||'').trim();
        const tel       = (qs('#pmTelefono').value||'').trim();
        const isRUC     = (docTipo === 'RUC');
        const ctDocTipo = qs('#pmCtDocTipo')?.value || '';
        const ctDocNum  = (qs('#pmCtDocNum')?.value || '').trim();

        if (!docTipo || !docNum){ showInlineAlert('payModal','danger','Completa tipo y número de documento.'); return; }
        if (isRUC){
          if (!razon){ showInlineAlert('payModal','danger','La razón social es obligatoria.'); return; }
          if (!nombres || !apellidos){ showInlineAlert('payModal','danger','Debes indicar nombres y apellidos del contratante.'); return; }
          if (!ctDocTipo || !ctDocNum){ showInlineAlert('payModal','danger','Debes indicar documento del contratante.'); return; }
        } else {
          if (!nombres || !apellidos){ showInlineAlert('payModal','danger','Completa nombres y apellidos.'); return; }
        }
        if (!PM.ventaItems.length){ showInlineAlert('payModal','danger','No hay ítems para vender.'); return; }
        if (!PM.abonos.length){
          showInlineAlert('payModal','danger','Debes registrar al menos un abono para completar la venta.');
          return;
        }

        // Validación final de sobrepago en creación de venta
        const abonado = PM.abonos.reduce((s,x)=>s+x.monto,0);
        if (!(abonado > 0)){
          showInlineAlert('payModal','danger','Debes registrar al menos un abono para completar la venta.');
          return;
        }
        if (abonado > PM.total + 1e-6){
          showInlineAlert('payModal','danger','El total de abonos supera el total de la venta. Ajusta los montos.');
          return;
        }

        // ¿Conductor es otra persona?
        const otro = !qs('#pmDriverBox').classList.contains('d-none');
        let coDocTipo='', coDocNum='', coNom='', coApe='', coTel='';
        if (otro){
          coDocTipo = qs('#pmCoDocTipo').value;
          coDocNum  = (qs('#pmCoDocNum').value||'').trim();
          coNom     = (qs('#pmCoNombres').value||'').trim();
          coApe     = (qs('#pmCoApellidos').value||'').trim();
          coTel     = (qs('#pmCoTel').value||'').trim();
          if (!coDocTipo || !coDocNum || !coNom || !coApe){
            showInlineAlert('payModal','danger','Completa los datos del conductor.');
            return;
          }
        }

        const extraVisible = !qs('#pmConductorExtraBox').classList.contains('d-none');
        const extra = readConductorExtraPayload();
        if (extraVisible){
          const extraErr = validateConductorExtraPayload(extra);
          if (extraErr){
            showInlineAlert('payModal','danger', extraErr);
            return;
          }
        }

        const payload = {
          accion: 'venta_crear',
          cliente_doc_tipo: docTipo,
          cliente_doc_numero: docNum,
          cliente_nombres: nombres,
          cliente_apellidos: apellidos,
          cliente_razon_social: isRUC ? razon : '',
          cliente_telefono: tel,
          contratante_doc_tipo: ctDocTipo,
          contratante_doc_numero: ctDocNum,
          conductor_otro: otro ? '1' : '0',
          conductor_doc_tipo: coDocTipo,
          conductor_doc_numero: coDocNum,
          conductor_nombres: coNom,
          conductor_apellidos: coApe,
          conductor_telefono: coTel,
          conductor_extra_enabled: extraVisible ? '1' : '0',
          conductor_extra_canal: extraVisible ? extra.canal : '',
          conductor_extra_email: extraVisible ? extra.email : '',
          conductor_extra_nacimiento: extraVisible ? extra.nacimiento : '',
          conductor_extra_categoria_auto_id: extraVisible ? extra.categoria_auto_id : '',
          conductor_extra_categoria_moto_id: extraVisible ? extra.categoria_moto_id : '',
          conductor_extra_nota: extraVisible ? extra.nota : '',
          items_json: JSON.stringify(PM.ventaItems.map(x=>({
            servicio_id: x.servicio_id,
            cantidad: x.qty,
            precio_unitario: x.precio,
            precio_origen: x.precio_origen || 'LISTA',
            precio_lista_id: x.precio_lista_id ?? null,
            precio_lista_base: x.precio_lista_base ?? null,
            precio_temporal_motivo: x.precio_temporal_motivo || ''
          })) ),
          abonos_json: JSON.stringify(PM.abonos.map(a=>({ medio_id:a.medio_id, monto:a.monto, referencia:a.ref, observacion:a.obs })) )
        };

        const j = await apiCaja('venta_crear', payload);

        const voucherConductor = otro
          ? { tipo: coDocTipo, doc: coDocNum, nombres: coNom, apellidos: coApe, telefono: coTel }
          : (isRUC
              ? { tipo: ctDocTipo, doc: ctDocNum, nombres: nombres, apellidos: apellidos, telefono: tel }
              : { tipo: docTipo, doc: docNum, nombres: nombres, apellidos: apellidos, telefono: tel }
            );

        openVoucher({
          kind: 'venta',
          venta_id: Number(j.venta_id||0),
          abono_ids: [],
          empresa: EMPRESA_NOMBRE,
          ticket: j.ticket,
          fecha: new Date().toLocaleString(),
          cajero: USUARIO_NOMBRE,
          cliente: {
            tipo_persona: isRUC ? 'JURIDICA' : 'NATURAL',
            tipo: docTipo, doc: docNum,
            razon: isRUC ? razon : '',
            nombres, apellidos, telefono: tel
          },
          contratante: isRUC ? { tipo: ctDocTipo, doc: ctDocNum, nombres, apellidos, telefono: tel } : null,
          conductor: voucherConductor,
          items: PM.ventaItems.slice(),
          abonos: PM.abonos.slice(),
          totales: { total: j.total, pagado: j.pagado, saldo: j.saldo }
        });

        cartClear('Venta completada.');
        if (typeof window.refreshVentasPendientes === 'function') {
          await window.refreshVentasPendientes({ resetPage: true });
        }
        await refreshDebugPanels();
      }catch(e){
        showInlineAlert('payModal','danger', mapApiError(e.message||''));
      }
    })();
    return;
  }

});

// ===== Modal de Precios =====
let MOD = { id:0, nom:'', desc:'', img:'' };
function syncPrecioTemporalInputs(){
  const tempRadio = qs('#pxTempRadio', qs('#pxBody'));
  const enabled = !!(tempRadio && tempRadio.checked);
  const monto = qs('#pxTempMonto', qs('#pxBody'));
  const motivo = qs('#pxTempMotivo', qs('#pxBody'));
  if (monto) monto.disabled = !enabled;
  if (motivo) motivo.disabled = !enabled;
}
function openPreciosModal(svc){
  MOD = svc;
  clearInlineAlert('pxModal');
  qs('#pxModalTitle').textContent = 'Detalle de servicios';
  qs('#pxBody').innerHTML = `<div class="text-muted">Cargando…</div>`;
  if (window.jQuery) jQuery('#pxModal').modal('show');

  apiGET({action:'svc_precios', servicio_id: svc.id})
    .then(j=>{
      const precios = j.data || [];
      const rowRef  = STATE.rows.find(x => Number(x.id) === Number(svc.id)) || {id: svc.id, precio: 0, nota: '', rol: '', precio_id: null};
      const sel     = normalizePickForService(rowRef, STATE.picked[String(svc.id)]);
      STATE.picked[String(svc.id)] = sel;
      const isTempSel = sel.precio_origen === 'TEMPORAL';
      const tempMonto = isTempSel ? Number(sel.precio || 0).toFixed(2) : '';
      const tempMotivo = isTempSel ? esc(sel.precio_temporal_motivo || '') : '';
      const html = `
        <div class="row g-3">
          <div class="col-12 col-md-5">
            <div class="border rounded p-2 text-center">
              <img src="${esc(svc.img)}" alt="${esc(svc.nom)}" style="max-width:100%;max-height:180px;object-fit:contain">
            </div>
            <div class="mt-2 small text-muted">${svc.desc||'—'}</div>
          </div>
          <div class="col-12 col-md-7">
            <div class="fw-bold mb-1">Precios disponibles</div>
            ${
              (precios.length
                ? precios.map((p,idx)=>{
                    const id = `pp_${svc.id}_${idx}`;
                    const sid = toPositiveInt(sel.precio_lista_id);
                    const pid = toPositiveInt(p.id);
                    const byId = (sid && pid) ? sid === pid : false;
                    const byValue = Number(sel.precio)===Number(p.precio) && String(sel.rol||'')===String(p.rol||'');
                    const isSel = (!isTempSel && (byId || byValue)) ? 'checked' : ((!isTempSel && p.es_principal) ? 'checked':'' );
                    const label = p.es_principal ? 'Principal' : `Opción ${p.rol}`;
                    const nota  = p.nota ? esc(p.nota) : 'Sin nota';
                    return `
                      <label for="${id}" class="d-flex align-items-center justify-content-between border rounded p-2 mb-2" style="gap:10px;">
                        <div>
                          <div class="fw-bold">${label}</div>
                          <div class="small text-muted">Nota: ${nota}</div>
                        </div>
                        <div class="d-flex align-items-center" style="gap:12px;">
                          <div class="fw-bold">${money(p.precio)}</div>
                          <input type="radio" name="svcPricePick" id="${id}"
                                 data-origen="LISTA" data-precio="${p.precio}" data-precio-id="${p.id||''}"
                                 data-precio-base="${p.precio}" data-rol="${p.rol}" data-nota="${esc(p.nota||'')}" ${isSel}>
                        </div>
                      </label>`;
                  }).join('')
                : '<div class="text-danger small">No hay precios activos configurados.</div>'
              )
            }
            <label for="pxTempRadio" class="d-block border rounded p-2 mt-3">
              <div class="d-flex align-items-center justify-content-between" style="gap:12px;">
                <div class="fw-bold">Precio temporal</div>
                <input type="radio" name="svcPricePick" id="pxTempRadio" data-origen="TEMPORAL" ${isTempSel ? 'checked' : ''}>
              </div>
              <div class="row g-2 mt-1">
                <div class="col-12 col-md-4">
                  <label class="small mb-1">Monto*</label>
                  <input type="number" class="form-control form-control-sm" id="pxTempMonto" min="0" step="0.01" value="${tempMonto}">
                </div>
                <div class="col-12 col-md-8">
                  <label class="small mb-1">Motivo*</label>
                  <input type="text" class="form-control form-control-sm" id="pxTempMotivo" maxlength="255" value="${tempMotivo}">
                </div>
              </div>
              <div class="alert alert-warning py-1 px-2 small mt-2 mb-0">
                Temporal: no se guarda en catálogo y se perderá al recargar.
              </div>
            </label>
            <div class="small text-muted mt-2">* Solo se muestran precios activos.</div>
          </div>
        </div>`;
      qs('#pxBody').innerHTML = html;
      syncPrecioTemporalInputs();
    })
    .catch(e=>{ qs('#pxBody').innerHTML = `<div class="text-danger">${esc(e.message)}</div>`; });
}
qs('#pxBody').addEventListener('change', (e)=>{
  if (e.target && e.target.name === 'svcPricePick') syncPrecioTemporalInputs();
});
qs('#pxGuardar').addEventListener('click', ()=>{
  clearInlineAlert('pxModal');
  const r = qs('input[name="svcPricePick"]:checked', qs('#pxBody'));
  if(!r){ showInlineAlert('pxModal','danger','Selecciona un precio.'); return; }

  const origen = String(r.dataset.origen || 'LISTA').toUpperCase();
  let pick = null;
  if (origen === 'TEMPORAL'){
    const montoRaw = Number(qs('#pxTempMonto', qs('#pxBody'))?.value || 0);
    const motivo = (qs('#pxTempMotivo', qs('#pxBody'))?.value || '').trim();
    if (!Number.isFinite(montoRaw) || montoRaw < 0){
      showInlineAlert('pxModal','danger','Ingresa un monto temporal válido.');
      return;
    }
    if (!motivo){
      showInlineAlert('pxModal','danger','El motivo del precio temporal es obligatorio.');
      return;
    }
    if (motivo.length > 255){
      showInlineAlert('pxModal','danger','El motivo no puede superar 255 caracteres.');
      return;
    }
    pick = {
      precio: montoRaw,
      nota: 'Precio temporal',
      rol: 'TEMPORAL',
      precio_origen: 'TEMPORAL',
      precio_lista_id: null,
      precio_lista_base: null,
      precio_temporal_motivo: motivo
    };
  } else {
    const precio = Number(r.dataset.precio||0);
    const nota   = r.dataset.nota||'';
    const rol    = r.dataset.rol||'';
    const listaId = toPositiveInt(r.dataset.precioId || null);
    const base = Number(r.dataset.precioBase ?? precio);
    pick = {
      precio,
      nota,
      rol,
      precio_origen: 'LISTA',
      precio_lista_id: listaId,
      precio_lista_base: Number.isFinite(base) ? base : precio,
      precio_temporal_motivo: ''
    };
  }
  STATE.picked[String(MOD.id)] = pick;

  // reflejar en card
  const card = qs(`.btn-detalle[data-id="${MOD.id}"]`)?.closest('.svc-card');
  if(card){
    const priceEl = card.querySelector('.svc-price');
    const noteEl  = card.querySelector('.svc-note');
    const addBtn  = card.querySelector(`.btn-add[data-id="${MOD.id}"]`);
    if(priceEl) priceEl.textContent = money(pick.precio);
    if(noteEl){
      noteEl.textContent = (pick.precio_origen === 'TEMPORAL')
        ? ('Temporal: ' + pick.precio_temporal_motivo)
        : ('Nota: ' + (pick.nota||'Sin nota'));
    }
    if(addBtn)  addBtn.dataset.precio = String(pick.precio); // para añadir veloz
  }

  // si ya está en carrito, actualizar su precio y metadatos
  if (CART.items[MOD.id]) {
    CART.items[MOD.id].precio = Number(pick.precio||0);
    CART.items[MOD.id].precio_origen = pick.precio_origen;
    CART.items[MOD.id].precio_lista_id = pick.precio_lista_id;
    CART.items[MOD.id].precio_lista_base = pick.precio_lista_base;
    CART.items[MOD.id].precio_temporal_motivo = pick.precio_temporal_motivo;
    renderCart();
  }

  if (window.jQuery) jQuery('#pxModal').modal('hide');
});

// ===== Carrito (rápido y local) =====
const CART = {
  items: Object.create(null) // id -> {id, nom, img, precio, qty, precio_origen, precio_lista_id, precio_lista_base, precio_temporal_motivo}
};
function cartAdd({id, nom, img, precio, meta={}}){
  id = String(id);
  const it = CART.items[id];
  if (it){ it.qty += 1; }
  else {
    const origen = String(meta.precio_origen || 'LISTA').toUpperCase() === 'TEMPORAL' ? 'TEMPORAL' : 'LISTA';
    CART.items[id] = {
      id,
      nom,
      img,
      precio: Number(precio||0),
      qty: 1,
      precio_origen: origen,
      precio_lista_id: origen === 'LISTA' ? (toPositiveInt(meta.precio_lista_id) || null) : null,
      precio_lista_base: origen === 'LISTA' ? Number(meta.precio_lista_base ?? precio ?? 0) : null,
      precio_temporal_motivo: origen === 'TEMPORAL' ? String(meta.precio_temporal_motivo || '') : ''
    };
  }
  renderCart();
}
function cartPlus(id){ id=String(id); const it=CART.items[id]; if(!it) return; it.qty+=1; renderCart(); }
function cartMinus(id){ id=String(id); const it=CART.items[id]; if(!it) return; it.qty-=1; if(it.qty<=0) delete CART.items[id]; renderCart(); }
function cartClear(msg=''){
  CART.items = Object.create(null);
  renderCart(msg||'El carrito ha sido vaciado.');
}
function cartTotal(){
  let t=0; for(const id in CART.items){ const it=CART.items[id]; t += it.precio*it.qty; } return t;
}
function renderCart(emptyMsg=null){
  const list = qs('#cartList'); if(!list) return;
  const ids = Object.keys(CART.items);
  if (ids.length===0){
    list.innerHTML = `
      <div class="cart-empty text-center p-3">
        <i class="fas fa-shopping-basket fa-2x mb-2 text-muted"></i>
        <div>${emptyMsg ? esc(emptyMsg) : 'Tu carrito está vacío.'}</div>
        <div class="text-muted small">Usa «Añadir» en un servicio para traerlo aquí.</div>
      </div>`;
    qs('#cartTotal').textContent = money(0);
    return;
  }
  const parts=[];
  ids.forEach(id=>{
    const it=CART.items[id];
    const line = it.precio*it.qty;
    parts.push(`
      <div class="cart-item" data-id="${id}">
        <div class="d-flex align-items-center w-100" style="gap:10px;">
          <div class="cart-thumb">
            <img src="${esc(it.img)}" alt="" width="40" height="40" loading="lazy">
          </div>
          <div class="flex-grow-1">
            <div class="ci-name text-uppercase small">${esc(it.nom)}</div>
            <div class="ci-controls">
              <button class="qty-btn ci-minus" data-id="${id}" title="Menos">−</button>
              <span class="ci-qty" id="ciq_${id}">${it.qty}</span>
              <button class="qty-btn ci-plus" data-id="${id}" title="Más">+</button>
              <span class="ci-price">${money(it.precio)}</span>
            </div>
          </div>
          <div class="ci-line-total">${money(line)}</div>
        </div>
      </div>`);
  });
  list.innerHTML = parts.join('<hr class="my-2">');
  qs('#cartTotal').textContent = money(cartTotal());

  // Asegurar coherencia de bloqueo en controles de cantidad
  setSellUI(CAN_SELL, SELL_REASON);
}

// ======= Unificado: UN SOLO manejador global de clicks =======
document.addEventListener('click',(e)=>{
  // --- Chips de etiquetas ---
  const chip = e.target.closest('#tagChips .chip-btn');
  if(chip){
    STATE.tag = chip.dataset.tag||'*';
    STATE.page=1; paintTags(); loadServicios();
    return;
  }

  // --- Paginación ---
  const a = e.target.closest('#pager a[data-p]');
  if(a){
    e.preventDefault();
    const p = parseInt(a.dataset.p,10);
    if(!isNaN(p) && p>=1){ STATE.page = p; loadServicios(); }
    return;
  }

  // --- Añadir desde un card ---
  const add = e.target.closest('.btn-add');
  if(add){
    if (!CAN_SELL) { showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para añadir ítems.', 'danger'); return; }
    const id  = add.dataset.id;
    const nom = add.dataset.nom || (qs(`.btn-detalle[data-id="${id}"]`)?.dataset.nom||'Servicio');
    const img = add.dataset.img || '';
    const pick = STATE.picked[String(id)] || normalizePickForService({id, precio: add.dataset.precio ?? 0, nota:'', rol:'', precio_id:null}, null);
    const precio = Number(add.dataset.precio ?? (pick.precio ?? 0));
    cartAdd({
      id,
      nom,
      img,
      precio,
      meta: {
        precio_origen: pick.precio_origen || 'LISTA',
        precio_lista_id: pick.precio_lista_id ?? null,
        precio_lista_base: pick.precio_lista_base ?? precio,
        precio_temporal_motivo: pick.precio_temporal_motivo || ''
      }
    });
    return;
  }

  // --- Mas / Menos dentro del carrito ---
  const m = e.target.closest('.ci-minus');
  if(m){
    if (!CAN_SELL) { showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para modificar cantidades.', 'danger'); return; }
    cartMinus(m.dataset.id);
    return;
  }
  const p = e.target.closest('.ci-plus');
  if(p){
    if (!CAN_SELL) { showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para modificar cantidades.', 'danger'); return; }
    cartPlus(p.dataset.id);
    return;
  }

  // --- Abrir detalle de precios (consulta) ---
  const det = e.target.closest('.btn-detalle');
  if(det){
    openPreciosModal({
      id: parseInt(det.dataset.id,10),
      nom: det.dataset.nom||'',
      desc: det.dataset.desc||'',
      img: det.dataset.img||''
    });
    return;
  }

  // --- Botones de barra superior (caja) ---
  if (e.target.closest('#btnAbrirMensual')){
    (async()=>{ try{ const j=await apiCaja('abrir_mensual',{}); await refreshEstado(); showMsg('Listo',j.msg); }catch(err){ showMsg('Error',err.message,'danger'); }})();
    return;
  }
if (e.target.closest('#btnCerrarMensual')){
  (async()=>{
    try{
      const S = LAST_ESTADO;
      if (!S) { await refreshEstado(); return; }
      // Si la mensual ACTUAL está abierta, usamos el cierre normal
      if (S.cm && S.cm.estado === 'abierta'){
        if(!confirm('¿Cerrar caja mensual?')) return;
        const j = await apiCaja('cerrar_mensual',{});
        await refreshEstado(); showMsg('Listo', j.msg);
        return;
      }
      // Si hay OTRA mensual abierta, pedimos motivo y cerramos extemporánea
      if (S.locks && S.locks.otra_mensual_abierta){
        openMotivoModal({
          tipo:'mensual',
          detalle: 'CM ' + (S.locks.mensual_abierta_codigo||''),
          onConfirm: async (motivo)=>{
            try{
              const j = await apiCaja('cerrar_mensual_pendiente', { motivo });
              await refreshEstado(); showMsg('Listo', j.msg);
            }catch(err){ showMsg('Error', err.message, 'danger'); }
          }
        });
        return;
      }
      showMsg('Aviso','No hay caja mensual abierta para cerrar.','danger');
    }catch(err){ showMsg('Error', err.message, 'danger'); }
  })();
  return;
}
  if (e.target.closest('#btnAbrirDiaria')){
    (async()=>{ try{ const j=await apiCaja('abrir_diaria',{}); await refreshEstado(); showMsg('Listo',j.msg); }catch(err){ showMsg('Error',err.message,'danger'); }})();
    return;
  }
if (e.target.closest('#btnCerrarDiaria')){
  (async()=>{
    try{
      const S = LAST_ESTADO;
      if (!S) { await refreshEstado(); return; }
      // Si la diaria de HOY está abierta, cierre normal
      if (S.cd && S.cd.estado === 'abierta'){
        if(!confirm('¿Cerrar caja diaria de hoy?')) return;
        const j = await apiCaja('cerrar_diaria',{});
        await refreshEstado(); showMsg('Listo', j.msg);
        return;
      }
      // Si hay OTRA diaria abierta, pedimos motivo y cerramos extemporánea
      if (S.locks && S.locks.otra_diaria_abierta){
        const det = `${S.locks.diaria_abierta_codigo ? S.locks.diaria_abierta_codigo+' ' : ''}${S.locks.diaria_abierta_fecha||''}`;
        openMotivoModal({
          tipo:'diaria',
          detalle: det,
          onConfirm: async (motivo)=>{
            try{
              const j = await apiCaja('cerrar_diaria_pendiente', { motivo });
              await refreshEstado(); showMsg('Listo', j.msg);
            }catch(err){ showMsg('Error', err.message, 'danger'); }
          }
        });
        return;
      }
      showMsg('Aviso','No hay caja diaria abierta para cerrar.','danger');
    }catch(err){ showMsg('Error', err.message, 'danger'); }
  })();
  return;
}

  // --- Botones del carrito ---
  if (e.target.closest('#btnPagar')){
    if (!CAN_SELL) { showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para pagar.', 'danger'); return; }
    openPayModal();
    return;
  }

  if (e.target.closest('#btnCancelar')){
    if (!CAN_SELL) { showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para usar estas acciones.', 'danger'); return; }
    cartClear('Se vació el carrito.');
    return;
  }
});

// Buscar
const qInput = qs('#q'), qClear = qs('#clearQ');
qInput.addEventListener('input', (()=>{
  let t;
  return ()=>{
    clearTimeout(t);
    t=setTimeout(()=>{
      STATE.q=qInput.value.trim(); STATE.page=1; loadServicios();
    },300);
  };
})());
qClear.addEventListener('click', ()=>{ qInput.value=''; STATE.q=''; STATE.page=1; loadServicios(); });

// Arranque
document.addEventListener('DOMContentLoaded', async ()=>{
  setSellUI(false, 'Cargando estado de caja…');
  await refreshEstado();
  try{ await loadTags(); }catch(_){}
  await loadServicios();
  await refreshDebugPanels();
  renderCart(); // muestra vacío amigable
});
// ===== Fix de apilamiento de múltiples modales (si hay modal de abonos + msgModal) =====
(function(){
  if (!(window.jQuery && jQuery.fn && jQuery.fn.modal)) return;
  // Al abrir un modal, incrementa z-index por cada modal visible
  jQuery(document).on('show.bs.modal', '.modal', function () {
    const $open = jQuery('.modal:visible');
    const z = 1050 + (10 * $open.length);
    jQuery(this).css('z-index', z);
    // Asegurar backdrop por debajo del modal actual
    setTimeout(function(){
      jQuery('.modal-backdrop').not('.modal-stack').css('z-index', z - 1).addClass('modal-stack');
    }, 0);
  });
  // Al cerrar, limpia las clases auxiliares si ya no quedan modales
  jQuery(document).on('hidden.bs.modal', '.modal', function () {
    if (jQuery('.modal:visible').length === 0) {
      jQuery('.modal-backdrop').removeClass('modal-stack');
    }
  });
})();

</script>
<script src="<?= BASE_URL ?>/modules/caja/ventas_pendientes.js?v=1"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
