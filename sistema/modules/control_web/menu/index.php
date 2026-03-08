<?php
// modules/control_web/menu/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Menú</h3>
</div>
<div class="card-body">
  <p class="mb-0">Aquí se dinamizará el menú de la página web.</p>
</div>
