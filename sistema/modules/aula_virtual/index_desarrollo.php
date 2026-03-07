<?php
// Ver 07-03-26
// modules/aula_virtual/index_desarrollo.php
if (!defined('AULA_VIRTUAL_ROLE_ROUTED') || !defined('AULA_VIRTUAL_VIEW_ROLE_ID') || AULA_VIRTUAL_VIEW_ROLE_ID !== 1) {
  http_response_code(403);
  exit('Acceso denegado.');
}

require_once __DIR__ . '/../../includes/acl.php';

acl_require_ids([7,1,4,6]);
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$u = currentUser();
$roleName = trim((string)($u['rol_activo'] ?? 'Desarrollo'));
if ($roleName === '') $roleName = 'Desarrollo';

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0"><?= h($roleName) ?></h1>
    </div>
  </div>

  <section class="content py-3">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-body">
          Sistema en construcci&oacute;n
        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


