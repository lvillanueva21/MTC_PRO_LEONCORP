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

$pageTitle = 'Control Web';

include __DIR__ . '/../../includes/header.php';
?>

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
        <div class="card-body">
          <h5 class="mb-2">Sistema web en construcción</h5>
          <p class="text-muted mb-0">
            Este módulo será usado para administrar de forma dinámica el contenido del sitio web.
          </p>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>