<?php
// /modules/interfaces_control/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/interfaces_control/assets/interfaces_control.css?v=2">
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Interfaces Control</h1>
    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="alert alert-info">
        Gestion de accesos por usuario para rol Control.
      </div>

      <div id="icMsg" class="alert d-none"></div>

      <div class="ic-grid">
        <div class="ic-pane">
          <div class="ic-pane-head">Usuarios Control</div>
          <div class="ic-pane-body">
            <label class="form-label">Selecciona usuario</label>
            <select id="icUser" class="form-control ic-user-select"></select>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-3" id="icReload">
              <i class="fas fa-sync-alt mr-1"></i>Recargar
            </button>
          </div>
        </div>

        <div class="ic-pane">
          <div class="ic-pane-head">Interfaces detectadas en carpeta</div>
          <div class="ic-pane-body">
            <div id="icInterfaces" class="ic-iface-list"></div>
            <button type="button" class="btn btn-primary mt-3" id="icSave">
              <i class="fas fa-save mr-1"></i>Guardar asignaciones
            </button>
          </div>
        </div>
      </div>

      <div class="ic-pane mt-3">
        <div class="ic-pane-head">Modulos clasicos elegibles (Control Especial)</div>
        <div class="ic-pane-body">
          <div class="text-muted small mb-2">
            Usa el mismo usuario seleccionado arriba. Esta seccion solo guarda permisos especiales por modulo.
          </div>
          <div id="icClassicModules" class="ic-iface-list"></div>
          <button type="button" class="btn btn-success mt-3" id="icSaveClassic">
            <i class="fas fa-save mr-1"></i>Guardar permisos especiales
          </button>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
  window.IC_CFG = {
    api: <?= json_encode(BASE_URL . '/modules/interfaces_control/api.php') ?>
  };
</script>
<script src="<?= BASE_URL ?>/modules/interfaces_control/assets/interfaces_control.js?v=2"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
