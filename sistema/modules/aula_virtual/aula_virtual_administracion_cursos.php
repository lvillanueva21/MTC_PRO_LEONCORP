<?php
// Ver 07-03-26
// modules/aula_virtual/aula_virtual_administracion_cursos.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';

acl_require_ids([1, 4]);

$u = currentUser();
$rolActivoId = (int)($u['rol_activo_id'] ?? 0);
if ($rolActivoId !== 4) {
  http_response_code(403);
  exit('Acceso denegado.');
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$empresaNombre = trim((string)($u['empresa']['nombre'] ?? ''));
if ($empresaNombre === '') $empresaNombre = 'Empresa';

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Aula Virtual - <?= h($empresaNombre) ?> - Cursos</h1>
    </div>
  </div>

  <section class="content py-3">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-body">
          pagina de cursos en construccion para el usuario de administracion.
        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


