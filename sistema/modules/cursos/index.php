<?php
// modules/cursos/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

// --- Autorización (solo Cliente) ---
acl_require_ids([7]);              // ID de rol "Cliente"
verificarPermiso(['Cliente']);     // nombre de rol activo

$u = currentUser();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Cursos</h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/inicio.php">Inicio</a></li>
            <li class="breadcrumb-item active">Cursos</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <section class="content">
    <div class="container-fluid">
      <div class="alert alert-success">
        Hola <?= h(trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? ''))) ?>, esta es tu página de cursos.
      </div>
    </div>
  </section>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
