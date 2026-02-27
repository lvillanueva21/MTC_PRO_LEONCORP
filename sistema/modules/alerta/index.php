<?php
// modules/alerta/index.php
require_once __DIR__.'/../../includes/acl.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/conexion.php';

acl_require_ids([1,3,4]);
verificarPermiso(['Desarrollo','Recepción','Administración']);

$u = currentUser();
$empresaId  = (int)($u['empresa']['id'] ?? 0);
$empresaNom = (string)($u['empresa']['nombre'] ?? '—');
if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada en sesión.'); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/alerta/alerta.css?v=1">

<div class="content-wrapper al-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h1 class="m-0 al-title"><i class="fas fa-bell me-2"></i>Alertas — <span class="text-primary"><?= h($empresaNom) ?></span></h1>
          <div class="text-muted small">Crea recordatorios de pagos/documentos y mira los próximos vencimientos.</div>
        </div>
        <div class="d-flex gap-2">
          <button id="btn-new" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Nueva alerta
          </button>
        </div>
      </div>

      <!-- Resumen -->
      <div id="al-cards" class="row g-3 mt-3"></div>
    </div>
  </div>

  <section class="content pb-4">
    <div class="container-fluid">
      <div class="al-toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="input-group" style="max-width:460px;">
          <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
          <input id="al-q" class="form-control border-start-0" placeholder="Buscar (título, categoría, descripción)…">
          <button id="al-clear" class="btn btn-outline-secondary" title="Limpiar"><i class="fas fa-times"></i></button>
        </div>
        <select id="al-estado" class="form-select" style="width:150px;">
          <option value="">Todas</option>
          <option value="1">Activas</option>
          <option value="0">Inactivas</option>
        </select>
        <select id="al-tipo" class="form-select" style="width:180px;">
          <option value="">Todos los tipos</option>
          <option value="ONCE">Una sola vez</option>
          <option value="MONTHLY">Mensual</option>
          <option value="YEARLY">Anual</option>
          <option value="INTERVAL">Cada N días</option>
        </select>
        <div class="ms-auto text-muted small"><i class="far fa-clock me-1"></i>Ordenadas por próxima fecha</div>
      </div>

      <div id="al-alert" class="alert alert-danger d-none"></div>
      <div id="al-list" class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th style="min-width:220px;">Título</th>
                  <th>Categoría</th>
                  <th>Tipo</th>
                  <th>Próxima</th>
                  <th>Anticipación</th>
                  <th>Activo</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody id="al-tbody"></tbody>
            </table>
          </div>
          <nav><ul class="pagination pagination-sm mt-2" id="al-pager"></ul></nav>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Drawer -->
<aside id="al-drawer" class="al-drawer">
  <div class="al-drawer-head">
    <div class="d-flex align-items-center gap-2">
      <i class="fas fa-edit"></i>
      <span id="drawer-title">Nueva alerta</span>
    </div>
    <button id="drawer-close" class="btn btn-sm btn-outline-light"><i class="fas fa-times"></i></button>
  </div>
  <div class="al-drawer-body">
    <form id="al-form" autocomplete="off">
      <input type="hidden" name="id" id="f-id">
      <div class="mb-2">
        <label class="form-label">Título *</label>
        <input type="text" class="form-control" name="titulo" id="f-titulo" maxlength="160" required>
      </div>
      <div class="mb-2">
        <label class="form-label">Categoría</label>
        <input type="text" class="form-control" name="categoria" id="f-categoria" maxlength="80" placeholder="Pagos, Documentos, Servicios…">
      </div>
      <div class="mb-2">
        <label class="form-label">Descripción</label>
        <textarea class="form-control" name="descripcion" id="f-descripcion" maxlength="255" rows="2"></textarea>
      </div>

      <div class="row g-2">
        <div class="col-sm-6">
          <label class="form-label">Tipo de recordatorio *</label>
          <select class="form-select" name="tipo" id="f-tipo" required>
            <option value="ONCE">Una sola vez</option>
            <option value="MONTHLY">Mensual</option>
            <option value="YEARLY">Anual</option>
            <option value="INTERVAL">Cada N días</option>
          </select>
        </div>
        <div class="col-sm-6" id="wrap-intervalo" style="display:none;">
          <label class="form-label">Intervalo (días)</label>
          <input type="number" class="form-control" min="1" name="intervalo_dias" id="f-intervalo">
        </div>
      </div>

      <div class="row g-2 mt-1">
        <div class="col-sm-6">
          <label class="form-label">Fecha base *</label>
          <input type="datetime-local" class="form-control" name="fecha_base" id="f-fecha" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Anticipación (días)</label>
          <input type="number" class="form-control" min="0" name="anticipacion_dias" id="f-anticipacion" value="0">
        </div>
      </div>

      <div class="form-check form-switch mt-2">
        <input class="form-check-input" type="checkbox" id="f-activo" name="activo" checked>
        <label class="form-check-label" for="f-activo">Activo</label>
      </div>

      <div class="d-grid gap-2 mt-3">
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Guardar</button>
        <button type="button" id="btn-cancel" class="btn btn-outline-secondary">Cancelar</button>
      </div>

      <div id="form-err" class="alert alert-danger d-none mt-3"></div>
      <div id="form-ok"  class="alert alert-success d-none mt-3"></div>
    </form>
  </div>
</aside>
<div id="al-mask" class="al-mask"></div>

<script>
  window.AL_CFG = {
    base: "<?= BASE_URL ?>",
    api:  "<?= BASE_URL ?>/modules/alerta/api.php",
    empresa_id: <?= (int)$empresaId ?>,
  };
</script>
<script src="<?= BASE_URL ?>/modules/alerta/alerta.js?v=1"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
