<?php
// modules/inventario/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([1,4,6]);
verificarPermiso(['Desarrollo','Administración','Gerente']);

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$u = currentUser();
$empresaId  = (int)($u['empresa']['id'] ?? 0);
$empresaNom = (string)($u['empresa']['nombre'] ?? '—');
$usrNom     = trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada en sesión.'); }

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/inventario/inventario.css?v=4">

<div class="content-wrapper invx">
  <div class="content-header">
    <div class="container-fluid">

      <div class="invx-hero">
        <div class="d-flex align-items-start justify-content-between flex-wrap">
          <div class="pr-2">
            <div class="invx-title">
              <span class="invx-badge"><i class="fas fa-boxes"></i></span>
              Inventario General
            </div>
            <div class="invx-sub">
              Empresa: <b><?= h($empresaNom) ?></b> • Usuario: <b><?= h($usrNom) ?></b>
            </div>
            <div class="invx-hint">Crea bienes, muévelos, imprime etiquetas QR y filtra por categorías.</div>
          </div>

          <div class="invx-actions mt-2">
            <button class="btn btn-invx btn-invx-primary" id="btnNew">
              <i class="fas fa-plus mr-1"></i> Nuevo bien
            </button>

            <div class="btn-group ml-2">
              <button class="btn btn-invx btn-invx-dark dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="btnPrintToggle">
                <i class="fas fa-qrcode mr-1"></i> Imprimir QRs
              </button>

              <!-- DROPDOWN: ahora tiene Formato + Tamaño QR, y NO se cierra al elegir -->
              <div class="dropdown-menu dropdown-menu-right invx-dd invx-print-dd" id="invPrintMenu">

                <h6 class="dropdown-header">Formato</h6>

                <button type="button" class="dropdown-item invx-opt" data-paper="a4">
                  <i class="far fa-dot-circle mr-2 invx-opt-ico"></i> A4
                </button>
                <button type="button" class="dropdown-item invx-opt" data-paper="t80">
                  <i class="far fa-circle mr-2 invx-opt-ico"></i> Ticket 80 mm
                </button>
                <button type="button" class="dropdown-item invx-opt" data-paper="t58">
                  <i class="far fa-circle mr-2 invx-opt-ico"></i> Ticket 58 mm
                </button>

                <div class="dropdown-divider"></div>

                <h6 class="dropdown-header">Tamaño QR</h6>

                <button type="button" class="dropdown-item invx-opt" data-mm="18">
  <i class="far fa-square mr-2 invx-opt-ico"></i> Pequeño (18mm)
</button>
<button type="button" class="dropdown-item invx-opt" data-mm="24">
  <i class="far fa-check-square mr-2 invx-opt-ico"></i> Mediano (24mm)
</button>
<button type="button" class="dropdown-item invx-opt" data-mm="32">
  <i class="far fa-square mr-2 invx-opt-ico"></i> Grande (32mm)
</button>

                <div class="dropdown-divider"></div>

                <div class="px-3 py-2">
                  <div class="small text-muted mb-1" id="invPrintHint">Tip: selecciona ítems con los checks.</div>
                  <button class="btn btn-sm btn-outline-dark btn-block" id="btnPrintSelected" type="button">
                    <i class="fas fa-print mr-1"></i> Imprimir seleccionados
                  </button>
                </div>

              </div>
              <!-- /dropdown -->

            </div>

          </div>
        </div>

        <!-- Mini dashboard -->
        <div class="row mt-3" id="dashRow">
          <div class="col-6 col-lg-3 mb-2">
            <div class="invx-kpi invx-kpi-a">
              <div class="ico"><i class="fas fa-box"></i></div>
              <div>
                <div class="t">Total</div>
                <div class="n" id="kpiTotal">0</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3 mb-2">
            <div class="invx-kpi invx-kpi-b">
              <div class="ico"><i class="fas fa-bolt"></i></div>
              <div>
                <div class="t">Activos</div>
                <div class="n" id="kpiActivos">0</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3 mb-2">
            <div class="invx-kpi invx-kpi-c">
              <div class="ico"><i class="fas fa-exclamation-triangle"></i></div>
              <div>
                <div class="t">Averiados</div>
                <div class="n" id="kpiAveriados">0</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3 mb-2">
            <div class="invx-kpi invx-kpi-d">
              <div class="ico"><i class="fas fa-cubes"></i></div>
              <div>
                <div class="t">Consumibles</div>
                <div class="n" id="kpiConsumibles">0</div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /hero -->

      <!-- Controles -->
      <div class="invx-controls card shadow-sm mt-3">
        <div class="card-body">

          <div class="row">
            <div class="col-12 col-lg-4 mb-2">
              <div class="input-group invx-search">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input id="q" class="form-control" placeholder="Buscar: nombre, marca, modelo, serie o código E12-000001">
<div class="input-group-append">
  <button class="btn btn-outline-secondary" id="btnClear" title="Limpiar búsqueda">
    <i class="fas fa-times"></i>
  </button>
  <button class="btn btn-outline-secondary" id="btnResetFilters" title="Limpiar TODOS los filtros">
    <i class="fas fa-broom"></i>
  </button>
</div>
              </div>
            </div>

            <div class="col-6 col-lg-2 mb-2">
              <select id="fTipo" class="form-control">
                <option value="">Tipo: Todos</option>
              </select>
            </div>

            <div class="col-6 col-lg-2 mb-2">
              <select id="fEstado" class="form-control">
                <option value="">Estado: Todos</option>
              </select>
            </div>

            <div class="col-6 col-lg-2 mb-2">
              <select id="fActivo" class="form-control">
                <option value="">Visibilidad: Todos</option>
                <option value="1">Solo activos</option>
                <option value="0">Solo inactivos</option>
              </select>
            </div>

            <div class="col-6 col-lg-2 mb-2 text-lg-right">
              <div class="btn-group btn-group-toggle w-100 w-lg-auto" data-toggle="buttons">
                <label class="btn btn-outline-dark active" id="btnViewList">
                  <input type="radio" autocomplete="off" checked> <i class="fas fa-list"></i>
                </label>
                <label class="btn btn-outline-dark" id="btnViewGrid">
                  <input type="radio" autocomplete="off"> <i class="fas fa-th-large"></i>
                </label>
              </div>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between flex-wrap mt-2">
            <div class="d-flex align-items-center flex-wrap">
              <div class="mr-2 mb-2" style="min-width:260px;">
  <div class="input-group input-group-sm">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-tags"></i></span>
    </div>
    <input id="catInput" class="form-control" list="catOptions"
           placeholder="Categoría (escribe y Enter)">
    <div class="input-group-append">
      <button class="btn btn-outline-secondary" type="button" id="btnCatClear" title="Quitar categorías">
        <i class="fas fa-eraser"></i>
      </button>
    </div>
  </div>
  <datalist id="catOptions"></datalist>
</div>
<div class="mb-2" id="catChips"></div>
              <div class="mr-2 mb-2">
                <button class="btn btn-outline-secondary btn-sm" id="btnSelAll">
                  <i class="far fa-check-square mr-1"></i> Seleccionar (página)
                </button>
              </div>

              <div class="mr-2 mb-2">
                <button class="btn btn-outline-danger btn-sm" id="btnSelNone">
                  <i class="far fa-square mr-1"></i> Quitar selección
                </button>
              </div>
            </div>

            <div class="text-muted small mb-2" id="selInfo">0 seleccionados</div>
          </div>

        </div>
      </div>

    </div>
  </div>

  <section class="content pb-5">
    <div class="container-fluid">

      <div id="alert" class="alert alert-danger d-none"></div>

      <!-- LISTA -->
      <div id="wrapList" class="card shadow-sm mt-3">
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0 invx-table">
<thead class="thead-light">
  <tr>
    <th style="width:34px;"><input type="checkbox" id="chkAll"></th>
    <th>Código</th>
    <th style="width:72px;">Foto</th>
    <th>Nombre</th>
    <th>Tipo</th>
    <th>Estado</th>
    <th>Ubicación / Responsable</th>
    <th style="width:210px;">Acciones</th>
  </tr>
</thead>
            <tbody id="tb"></tbody>
          </table>
        </div>
        <div class="card-body py-2">
          <ul class="pagination pagination-sm mb-0" id="pager"></ul>
        </div>
      </div>

      <!-- GRID -->
      <div id="wrapGrid" class="invx-grid d-none"></div>

    </div>
  </section>

  <?php
    // Panel de testing (opcional). Si borras el archivo, no rompe nada.
    $__invResetInc = __DIR__ . '/inventario_reset_tools.php';
    if (file_exists($__invResetInc)) { include $__invResetInc; }
  ?>

</div>

<!-- Modal Bien -->

<div class="modal fade" id="mdlBien" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content invx-modal">
      <div class="modal-header invx-modal-head">
        <h5 class="modal-title">
          <i class="fas fa-magic mr-2"></i><span id="mBienTitle">Nuevo bien</span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="mBienErr" class="alert alert-danger d-none"></div>

        <form id="frmBien" autocomplete="off">
          <input type="hidden" id="bienId">

          <div class="form-row">
            <div class="form-group col-12 col-md-4">
              <label>Tipo</label>
              <select id="bienTipo" class="form-control" required></select>
            </div>
            <div class="form-group col-12 col-md-4">
              <label>Estado</label>
              <select id="bienEstado" class="form-control" required></select>
            </div>
            <div class="form-group col-12 col-md-4">
              <label>Activo</label>
              <select id="bienActivo" class="form-control">
                <option value="1">Sí</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Nombre / Etiqueta *</label>
            <input id="bienNombre" class="form-control" maxlength="160" required>
          </div>

          <div class="form-group">
            <label>Descripción</label>
            <input id="bienDesc" class="form-control" maxlength="255">
          </div>

          <!-- IMAGEN (OPCIONAL) -->
          <div class="form-group">
            <label>Imagen del bien (opcional)</label>
            <input class="form-control" type="file" id="bienImgFile" accept="image/*">
            <input type="hidden" id="bienImgKey">
            <input type="hidden" id="bienImgTouched" value="0">

            <div class="mt-2 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
              <img id="bienImgPreview" alt="Vista previa" style="display:none; width:100%; max-height:220px; object-fit:cover; border-radius:12px; border:1px solid rgba(0,0,0,.08);">
              <button type="button" class="btn btn-sm btn-outline-danger" id="btnImgRemove" style="display:none;">
                <i class="fas fa-trash mr-1"></i> Quitar imagen
              </button>
            </div>

            <div class="progress d-none mt-2" id="bienImgProgWrap">
              <div class="progress-bar" id="bienImgProgBar" role="progressbar" style="width:0%">0%</div>
            </div>

            <small class="text-muted">La imagen se sube directo a S4 (tu servidor no recibe el archivo).</small>
          </div>
          <!-- /IMAGEN -->

          <div class="form-row">
            <div class="form-group col-12 col-md-4">
              <label>Marca</label>
              <input id="bienMarca" class="form-control" maxlength="100">
            </div>
            <div class="form-group col-12 col-md-4">
              <label>Modelo</label>
              <input id="bienModelo" class="form-control" maxlength="100">
            </div>
            <div class="form-group col-12 col-md-4">
              <label>Serie</label>
              <input id="bienSerie" class="form-control" maxlength="120">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-6 col-md-3">
              <label>Cantidad</label>
              <input id="bienCant" type="number" step="0.01" min="0" class="form-control" value="1">
            </div>
            <div class="form-group col-6 col-md-3">
              <label>Unidad</label>
              <select id="bienUnidad" class="form-control"></select>
            </div>
                        <div class="form-group col-12 col-md-6">
              <label>Ubicación</label>
              <select id="bienUbic" class="form-control">
                <option value="">— Sin ubicación —</option>
              </select>

              <div class="d-flex align-items-center justify-content-between flex-wrap mt-2" style="gap:8px;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="collapse" data-target="#invxUbicNewBox" aria-expanded="false" aria-controls="invxUbicNewBox" id="btnUbicToggle">
                  <i class="fas fa-plus mr-1"></i> Crear ubicación
                </button>
                <div id="invxUbicMsg" class="small text-success d-none"></div>
              </div>

              <div class="collapse mt-2" id="invxUbicNewBox">
                <div class="invx-inlinebox p-2">
                  <div class="input-group">
                    <input type="text" class="form-control" id="invxUbicNewName" maxlength="120" placeholder="Ej: Almacén, Oficina 2">
                    <div class="input-group-append">
                      <button class="btn btn-outline-primary" type="button" id="btnUbicCreate">
                        <i class="fas fa-check mr-1"></i> Crear
                      </button>
                      <button class="btn btn-outline-secondary" type="button" id="btnUbicCancel">
                        <i class="fas fa-times mr-1"></i> Cancelar
                      </button>
                    </div>
                  </div>
                  <small class="text-muted d-block mt-1">Se crea y queda seleccionada.</small>
                </div>
              </div>
            </div>
          </div>

          <div class="invx-resp-box">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
              <label class="mb-1">Responsable</label>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="respUser" name="respMode" class="custom-control-input" value="USER" checked>
                <label class="custom-control-label" for="respUser">Usuario</label>
              </div>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="respText" name="respMode" class="custom-control-input" value="TEXT">
                <label class="custom-control-label" for="respText">Texto</label>
              </div>
            </div>

            <div id="respUserWrap" class="mt-2">
              <select id="bienRespUser" class="form-control">
                <option value="">— Sin responsable —</option>
              </select>
            </div>

            <div id="respTextWrap" class="mt-2 d-none">
              <div class="form-row">
                <div class="form-group col-12 col-md-4">
                  <input id="bienRespNom" class="form-control" placeholder="Nombres">
                </div>
                <div class="form-group col-12 col-md-4">
                  <input id="bienRespApe" class="form-control" placeholder="Apellidos">
                </div>
                <div class="form-group col-12 col-md-4">
                  <input id="bienRespDni" class="form-control" placeholder="DNI">
                </div>
              </div>
            </div>
          </div>

          <div class="form-group mt-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
              <label class="mb-1">Categorías</label>
              <div id="invxCatMsg" class="small text-success d-none"></div>
            </div>

            <div class="invx-cat-tools mt-1">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" class="form-control" id="invxCatFilter" placeholder="Buscar categorías…">
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="button" data-toggle="collapse" data-target="#invxCatNewBox" aria-expanded="false" aria-controls="invxCatNewBox" id="btnCatToggle">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>

              <div class="collapse mt-2" id="invxCatNewBox">
                <div class="invx-inlinebox p-2">
                  <div class="input-group">
                    <input type="text" class="form-control" id="invxCatNewName" maxlength="80" placeholder="Nueva categoría (ej: Electrónica)">
                    <div class="input-group-append">
                      <button class="btn btn-outline-primary" type="button" id="btnCatCreate">
                        <i class="fas fa-check mr-1"></i> Crear
                      </button>
                    </div>
                  </div>
                  <small class="text-muted d-block mt-1">Se crea y queda marcada.</small>
                </div>
              </div>
            </div>

            <div id="bienCats" class="invx-cats mt-2"></div>
          </div>

          <div class="form-group">
            <label>Notas</label>
            <textarea id="bienNotas" class="form-control" rows="2" maxlength="400"></textarea>
          </div>

          <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="text-muted small" id="bienCodeHint"></div>
            <button type="submit" class="btn btn-invx btn-invx-primary">
              <i class="fas fa-save mr-1"></i> Guardar
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<!-- Modal Movimiento -->
<div class="modal fade" id="mdlMove" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content invx-modal">
      <div class="modal-header invx-modal-head invx-modal-head2">
        <h5 class="modal-title"><i class="fas fa-random mr-2"></i>Mover / Asignar</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="mMoveErr" class="alert alert-danger d-none"></div>
        <div class="invx-move-meta mb-2">
          <div class="code" id="mvCode">—</div>
          <div class="name" id="mvName">—</div>
        </div>

        <form id="frmMove" autocomplete="off">
          <input type="hidden" id="mvId">

          <div class="form-group">
            <label>Nueva ubicación</label>
            <select id="mvUbic" class="form-control">
              <option value="">— No cambiar —</option>
            </select>
          </div>

          <div class="invx-resp-box">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
              <label class="mb-1">Nuevo responsable</label>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="mvRespUser" name="mvRespMode" class="custom-control-input" value="USER" checked>
                <label class="custom-control-label" for="mvRespUser">Usuario</label>
              </div>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="mvRespText" name="mvRespMode" class="custom-control-input" value="TEXT">
                <label class="custom-control-label" for="mvRespText">Texto</label>
              </div>
            </div>

            <div id="mvRespUserWrap" class="mt-2">
              <select id="mvRespUserSel" class="form-control">
                <option value="">— No cambiar —</option>
              </select>
            </div>

            <div id="mvRespTextWrap" class="mt-2 d-none">
              <div class="form-row">
                <div class="form-group col-12">
                  <input id="mvRespNom" class="form-control mb-2" placeholder="Nombres">
                  <input id="mvRespApe" class="form-control mb-2" placeholder="Apellidos">
                  <input id="mvRespDni" class="form-control" placeholder="DNI">
                </div>
              </div>
            </div>
          </div>

          <div class="form-group mt-2">
            <label>Nota</label>
            <input id="mvNota" class="form-control" maxlength="255" placeholder="Ej: traslado a almacén, asignado a Juan...">
          </div>

          <button type="submit" class="btn btn-invx btn-invx-dark btn-block">
            <i class="fas fa-check mr-1"></i> Registrar movimiento
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal QR -->
<div class="modal fade" id="mdlQR" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content invx-modal">
      <div class="modal-header invx-modal-head invx-modal-head3">
        <h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i>QR del bien</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body">

        <!-- INFO ARRIBA -->
        <div class="invx-resp-box invx-qr-top">
          <div class="invx-qr-top-head">
            <div class="invx-qr-title" id="qrBienNombre">—</div>
            <!-- Código AHORA va aquí (no arriba del modal) -->
            <div class="invx-code" id="qrCode">—</div>
          </div>

          <div class="row invx-qr-top-grid mt-2">
            <div class="col-6 col-md-3 mb-2 mb-md-0">
              <div class="invx-qr-k">Marca</div>
              <div class="invx-qr-v" id="qrBienMarca">—</div>
            </div>
            <div class="col-6 col-md-3 mb-2 mb-md-0">
              <div class="invx-qr-k">Modelo</div>
              <div class="invx-qr-v" id="qrBienModelo">—</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="invx-qr-k">Serie</div>
              <div class="invx-qr-v" id="qrBienSerie">—</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="invx-qr-k">Estado</div>
              <div class="invx-qr-v">
                <span class="invx-pill invx-bueno" id="qrBienEstado">—</span>
              </div>
            </div>
          </div>
        </div>

        <!-- IMAGEN + QR ABAJO -->
        <div class="row mt-3">
          <!-- Imagen -->
          <div class="col-12 col-md-6 mb-3 mb-md-0">
            <div class="invx-qr-card">
              <div class="invx-qr-card-head">
                <div class="t">Imagen</div>
                <div class="acts">
                  <button type="button" class="btn btn-sm btn-outline-secondary invx-iconbtn" id="btnQrImgView" title="Ver imagen">
                    <i class="fas fa-external-link-alt"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary invx-iconbtn" id="btnQrImgDl" title="Descargar imagen">
                    <i class="fas fa-download"></i>
                  </button>
                </div>
              </div>

              <div class="invx-qr-box">
                <img id="qrBienImg" class="invx-qr-photo d-none" alt="Imagen del bien" src="">
                <div id="qrBienImgEmpty" class="invx-qr-empty">
                  <i class="far fa-image"></i>
                  <div class="t">Sin imagen</div>
                </div>
              </div>
            </div>
          </div>

          <!-- QR -->
          <div class="col-12 col-md-6">
            <div class="invx-qr-card">
              <div class="invx-qr-card-head">
                <div class="t">QR</div>
                <div class="acts">
                  <button type="button" class="btn btn-sm btn-outline-secondary invx-iconbtn" id="btnQrPngView" title="Ver PNG">
                    <i class="fas fa-external-link-alt"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary invx-iconbtn" id="btnQrPngDl" title="Descargar PNG">
                    <i class="fas fa-download"></i>
                  </button>
                </div>
              </div>

              <div class="invx-qr-box">
                <img id="qrImg" alt="QR" class="invx-qrimg img-fluid" src="">
              </div>
            </div>
          </div>
        </div>

        <!-- ACCIÓN DETALLE -->
        <div class="mt-3 text-center">
          <a id="qrOpen" class="btn btn-outline-secondary btn-sm" href="#" target="_blank">
            <i class="fas fa-external-link-alt mr-1"></i> Ver detalle
          </a>
        </div>

        <div class="text-muted small mt-2 text-center">El QR se genera al vuelo (no se guarda imagen).</div>

      </div>
    </div>
  </div>
</div>

<!-- Modal Historial -->
<div class="modal fade" id="mdlHist" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content invx-modal">
      <div class="modal-header invx-modal-head invx-modal-head4">
        <h5 class="modal-title"><i class="fas fa-stream mr-2"></i>Historial</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="invx-move-meta mb-2">
          <div class="code" id="histCode">—</div>
          <div class="name" id="histName">—</div>
        </div>
        <div id="histBody" class="invx-hist"></div>
      </div>
    </div>
  </div>
</div>

<script>
window.INV_CFG = {
  base: "<?= h(BASE_URL) ?>",
  api:  "<?= h(BASE_URL) ?>/modules/inventario/api.php",
  qr:   "<?= h(BASE_URL) ?>/modules/inventario/qr.php",
  pdf:  "<?= h(BASE_URL) ?>/modules/inventario/qr_pdf.php",
  detalle: "<?= h(BASE_URL) ?>/modules/inventario/detalle.php",

  sign_img: "<?= h(BASE_URL) ?>/modules/inventario/api_bien_img.php",
  img_public: "<?= h(BASE_URL) ?>/modules/inventario/img.php"
};
</script>

<script src="<?= BASE_URL ?>/modules/inventario/inventario_thumbs.js?v=1"></script>
<script src="<?= BASE_URL ?>/modules/inventario/inventario.js?v=4"></script>
<script src="<?= BASE_URL ?>/modules/inventario/inventario_qr_modal.js?v=1"></script>
<script src="<?= BASE_URL ?>/modules/inventario/inventario_meta_add.js?v=1"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

