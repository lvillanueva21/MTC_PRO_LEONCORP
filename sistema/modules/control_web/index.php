<?php
// modules/control_web/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$pageTitle = 'Control de Página Web';
$cwBaseUrl = BASE_URL . '/modules/control_web';

include __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo h($cwBaseUrl . '/control_web.css?v=1'); ?>">

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><?php echo h($pageTitle); ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?php echo h(BASE_URL . '/inicio.php'); ?>">Inicio</a></li>
            <li class="breadcrumb-item active">Web</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="card card-outline card-primary">
        <div class="card-body pb-2">
          <div class="cw-actions d-flex flex-wrap">
            <button type="button" class="btn cw-action-btn cw-btn-cabecera" data-target="cabecera">
              Cabecera
            </button>
            <button type="button" class="btn cw-action-btn cw-btn-menu" data-target="menu">
              Menú
            </button>
          </div>
        </div>
      </div>

      <div id="cw-feedback" class="mb-2" style="display:none;"></div>

      <div id="cw-workspace" class="card card-outline card-secondary cw-workspace">
        <div class="card-body text-muted">
          Selecciona una opción para continuar.
        </div>
      </div>
    </div>
  </section>
</div>

<script>
window.CONTROL_WEB = {
    cabeceraUrl: <?php echo json_encode($cwBaseUrl . '/cabecera/index.php'); ?>,
    menuUrl: <?php echo json_encode($cwBaseUrl . '/menu/index.php'); ?>
};
</script>
<script src="<?php echo h($cwBaseUrl . '/control_web.js?v=1'); ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>