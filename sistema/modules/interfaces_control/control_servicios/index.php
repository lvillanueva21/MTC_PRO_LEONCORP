<?php
// /modules/interfaces_control/control_servicios/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../_control_acl.php';

acl_require_ids(array(1, 2));
verificarPermiso(array('Desarrollo', 'Control'));
ic_require_control_interface('control_servicios');

include __DIR__ . '/../../../includes/header.php';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Control Servicios</h1>
      <div class="text-muted small">Gestion global de servicios y asignacion por empresa.</div>
    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="alert alert-info">
        Interfaz habilitada para crear, editar, listar y asignar servicios a empresas.
      </div>

      <div id="cs-slot">
        <?php include __DIR__ . '/../../consola/servicios/gestion.php'; ?>
      </div>
    </div>
  </section>
</div>
<script type="module">
  const slot = document.getElementById('cs-slot');
  const apiUrl = <?= json_encode(BASE_URL . '/modules/interfaces_control/control_servicios/api.php') ?>;
  const moduleUrl = <?= json_encode(BASE_URL . '/modules/consola/servicios/gestion.js?v=5') ?>;
  if (slot) {
    import(moduleUrl).then(function (mod) {
      if (mod && typeof mod.init === 'function') {
        mod.init(slot, apiUrl);
      }
    }).catch(function () {
      // Silencioso para no romper la pagina si falla el import.
    });
  }
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
