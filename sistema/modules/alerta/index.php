<?php
// modules/alerta/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([1, 3, 4]);
verificarPermiso([1, 3, 4]);

$u = currentUser();
$empresaId = (int)($u['empresa']['id'] ?? 0);
$empresaNom = (string)($u['empresa']['nombre'] ?? '-');
if ($empresaId <= 0) {
    http_response_code(403);
    exit('Empresa no asignada en sesion.');
}

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/alerta/alerta.css?v=6">

<div class="content-wrapper al-wrapper">
  <section class="content-header pb-2">
    <div class="container-fluid">
      <div class="al-hero">
        <div class="al-hero-main">
          <p class="al-hero-kicker mb-1">Organizacion y productividad</p>
          <h1 class="m-0 al-title">
            <i class="fas fa-bell mr-2"></i>Alertas - <span class="text-primary"><?= h($empresaNom) ?></span>
          </h1>
          <p class="al-hero-sub mb-0">
            Registra recordatorios claros, reutiliza etiquetas y controla facilmente tus proximos vencimientos.
          </p>
        </div>
        <div class="al-hero-actions">
          <button id="btn-new" class="btn btn-primary al-btn-primary">
            <i class="fas fa-plus-circle mr-1"></i> Nueva alerta
          </button>
        </div>
      </div>

      <div id="al-cards" class="row mt-3"></div>
    </div>
  </section>

  <section class="content pb-4">
    <div class="container-fluid">
      <div class="card al-toolbar-card mb-3">
        <div class="card-body py-3">
          <div class="al-toolbar d-flex flex-wrap align-items-center">
            <div class="input-group al-search-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-right-0"><i class="fas fa-search"></i></span>
              </div>
              <input id="al-q" class="form-control border-left-0" placeholder="Buscar por titulo, categoria o descripcion">
              <div class="input-group-append">
                <button id="al-clear" class="btn btn-outline-secondary" title="Limpiar busqueda">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>

            <div class="al-filter-group ml-auto">
              <select id="al-estado" class="form-control form-control-sm">
                <option value="">Todas</option>
                <option value="1">Activas</option>
                <option value="0">Inactivas</option>
              </select>

              <select id="al-tipo" class="form-control form-control-sm">
                <option value="">Todos los tipos</option>
                <option value="ONCE">Una sola vez</option>
                <option value="MONTHLY">Mensual</option>
                <option value="YEARLY">Anual</option>
                <option value="INTERVAL">Cada N dias</option>
              </select>
            </div>
          </div>
          <div class="al-toolbar-note mt-2">
            <i class="far fa-clock mr-1"></i> Ordenadas por proxima fecha. En amarillo: dentro de ventana de anticipacion.
          </div>
        </div>
      </div>

      <div id="al-alert" class="alert alert-danger d-none mb-3"></div>

      <div id="al-list" class="card shadow-sm">
        <div class="card-body">
          <div class="al-table-head d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">Listado de alertas</h5>
            <small class="text-muted">Haz clic en editar para ajustar fecha, tipo o etiqueta.</small>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle al-table">
              <thead>
                <tr>
                  <th style="min-width:220px;">Titulo</th>
                  <th>Categoria</th>
                  <th>Tipo</th>
                  <th>Proxima</th>
                  <th>Anticipacion</th>
                  <th>Activo</th>
                  <th class="text-right">Acciones</th>
                </tr>
              </thead>
              <tbody id="al-tbody"></tbody>
            </table>
          </div>
          <nav><ul class="pagination pagination-sm mt-2 mb-0" id="al-pager"></ul></nav>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade al-modal" id="al-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg al-modal-dialog" role="document">
    <div class="modal-content al-modal-content">
      <div class="modal-header al-modal-header">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-edit"></i>
          <span id="drawer-title">Nueva alerta</span>
        </div>
        <button id="drawer-close" type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body al-modal-body">
        <form id="al-form" autocomplete="off">
          <input type="hidden" name="id" id="f-id">

          <div class="mb-3">
            <div class="al-field-head">
              <label class="form-label mb-0" for="f-titulo">Titulo *</label>
              <small id="cnt-titulo" class="al-counter">0/160</small>
            </div>
            <input type="text" class="form-control" name="titulo" id="f-titulo" maxlength="160" required>
            <small class="al-field-help">Describe en una frase clara que se debe recordar.</small>
          </div>

          <div class="mb-3">
            <div class="al-field-head">
              <label class="form-label mb-0" for="f-categoria-input">Categoria</label>
              <small id="cnt-categoria" class="al-counter">0/80</small>
            </div>

            <input type="hidden" name="categoria" id="f-categoria" maxlength="80">
            <div class="al-tag-editor" id="f-categoria-editor">
              <div class="al-tag-chip-wrap" id="f-categoria-chip"></div>
              <input type="text" class="form-control" id="f-categoria-input" maxlength="80" placeholder="Escribe y presiona espacio, Enter o coma">
            </div>

            <div class="al-tag-hint">Usa 1 etiqueta por alerta. Si existe una parecida, se sugerira para reutilizarla.</div>
            <div id="f-categoria-suggest" class="al-tag-suggest d-none"></div>
          </div>

          <div class="mb-3">
            <div class="al-field-head">
              <label class="form-label mb-0" for="f-descripcion">Descripcion</label>
              <small id="cnt-descripcion" class="al-counter">0/255</small>
            </div>
            <textarea class="form-control" name="descripcion" id="f-descripcion" maxlength="255" rows="3"></textarea>
            <small class="al-field-help">Opcional. Agrega contexto breve para que la alerta sea facil de entender.</small>
          </div>

          <div class="row">
            <div class="col-sm-6 mb-3">
              <label class="form-label mb-1" for="f-tipo">Tipo de recordatorio *</label>
              <select class="form-control" name="tipo" id="f-tipo" required>
                <option value="ONCE">Una sola vez</option>
                <option value="MONTHLY">Mensual</option>
                <option value="YEARLY">Anual</option>
                <option value="INTERVAL">Cada N dias</option>
              </select>
            </div>
            <div class="col-sm-6 mb-3" id="wrap-intervalo" style="display:none;">
              <label class="form-label mb-1" for="f-intervalo">Intervalo (dias)</label>
              <input type="number" class="form-control" min="1" name="intervalo_dias" id="f-intervalo" placeholder="Ej. 30">
            </div>
          </div>

          <div class="row">
            <div class="col-sm-6 mb-3">
              <label class="form-label mb-1" for="f-fecha">Fecha base *</label>
              <input type="datetime-local" class="form-control" name="fecha_base" id="f-fecha" required>
            </div>
            <div class="col-sm-6 mb-3">
              <label class="form-label mb-1" for="f-anticipacion">Anticipacion (dias)</label>
              <input type="number" class="form-control" min="0" name="anticipacion_dias" id="f-anticipacion" value="0">
            </div>
          </div>

          <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" id="f-activo" name="activo" checked>
            <label class="form-check-label" for="f-activo">
              Activo (aparecera en listados y recordatorios proximos)
            </label>
          </div>

          <div class="al-form-actions mt-3">
            <button id="btn-save" type="submit" class="btn btn-success">
              <i class="fas fa-save mr-1"></i> Guardar alerta
            </button>
            <button type="button" id="btn-cancel" class="btn btn-outline-secondary">Cancelar</button>
          </div>

          <div id="form-err" class="alert alert-danger d-none mt-3 mb-0"></div>
          <div id="form-ok" class="alert alert-success d-none mt-3 mb-0"></div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="al-toast" class="al-toast d-none" role="status" aria-live="polite">
  <i id="al-toast-icon" class="fas fa-check-circle mr-2"></i>
  <span id="al-toast-text">Operacion completada.</span>
</div>

<script>
  window.AL_CFG = {
    base: "<?= BASE_URL ?>",
    api: "<?= BASE_URL ?>/modules/alerta/api.php",
    empresa_id: <?= (int)$empresaId ?>,
  };
</script>
<script src="<?= BASE_URL ?>/modules/alerta/alerta.js?v=6"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
