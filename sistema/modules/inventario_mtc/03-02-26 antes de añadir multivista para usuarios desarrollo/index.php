<?php
// modules/inventario/index.php
require_once __DIR__.'/../../includes/acl.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/conexion.php';

acl_require_ids([1,3,4]);                 // Desarrollo (1), Recepción (3), Administración (4)
verificarPermiso(['Desarrollo','Recepción','Administración']);

$u = currentUser();
$empresaId   = (int)($u['empresa']['id'] ?? 0);
$empresaNom  = (string)($u['empresa']['nombre'] ?? '—');
if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada en sesión.'); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/inventario_mtc/inventario.css?v=3">

<div class="content-wrapper inv-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h1 class="m-0 inv-title">
            <i class="fas fa-boxes me-2"></i>Inventario de <span class="text-primary"><?= h($empresaNom) ?></span>
          </h1>
          <div class="text-muted small">Solo ves y editas los ítems de tu empresa.</div>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-secondary" target="_blank"
             href="<?= BASE_URL ?>/modules/inventario_mtc/print.php">
            <i class="fas fa-print me-1"></i> Imprimir / PDF
          </a>
        </div>
      </div>

      <!-- Resumen (se llena por JS) -->
      <div id="inv-cards" class="row g-3 mt-2"></div>
    </div>
  </div>

  <section class="content pb-4">
    <div class="container-fluid">
      <!-- Tabs de categorías -->
      <ul id="inv-tabs" class="nav nav-pills inv-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-cat="computadoras" href="#">Computadoras</a></li>
        <li class="nav-item"><a class="nav-link" data-cat="camaras"       href="#">Cámaras</a></li>
        <li class="nav-item"><a class="nav-link" data-cat="dvrs"          href="#">DVR</a></li>
        <li class="nav-item"><a class="nav-link" data-cat="huelleros"     href="#">Huelleros</a></li>
        <li class="nav-item"><a class="nav-link" data-cat="switches"      href="#">Switches</a></li>
        <li class="nav-item"><a class="nav-link" data-cat="red"           href="#">Red</a></li>
        <li class="nav-item"><a class="nav-link" data-cat="transmision"   href="#">Transmisión</a></li>
      </ul>

      <!-- Barra de herramientas -->
      <div class="inv-toolbar d-flex flex-wrap align-items-center gap-2 mb-2">
        <div class="input-group" style="max-width:420px;">
          <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
          <input id="inv-q" class="form-control border-start-0" placeholder="Buscar... (ambiente, marca, modelo, serie, ip, etc.)">
          <button id="inv-clear" class="btn btn-outline-secondary" title="Limpiar"><i class="fas fa-times"></i></button>
        </div>
        <select id="inv-estado" class="form-select" style="width:160px;">
          <option value="">Todos</option>
          <option value="1">Activos</option>
          <option value="0">Inactivos</option>
        </select>
        <button id="btn-new" class="btn btn-primary ms-auto">
          <i class="fas fa-plus-circle me-1"></i> Nuevo
        </button>
      </div>

      <!-- Lista -->
      <div id="inv-alert" class="alert alert-danger d-none"></div>
      <div id="inv-list" class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead id="inv-thead"></thead>
              <tbody id="inv-tbody"></tbody>
            </table>
          </div>
          <nav><ul class="pagination pagination-sm mt-2" id="inv-pager"></ul></nav>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Drawer (sin modal) -->
<aside id="inv-drawer" class="inv-drawer">
  <div class="inv-drawer-head">
    <div class="d-flex align-items-center gap-2">
      <i class="fas fa-edit"></i>
      <span id="drawer-title">Nuevo</span>
    </div>
    <button id="drawer-close" class="btn btn-sm btn-outline-light">
      <i class="fas fa-times"></i>
    </button>
  </div>
  <div class="inv-drawer-body">
    <form id="inv-form" autocomplete="off">
      <input type="hidden" name="id" id="f-id">
      <input type="hidden" name="tabla" id="f-tabla">
      <div id="form-fields"></div>
      <div class="d-grid gap-2 mt-3">
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i> Guardar
        </button>
        <button type="button" id="btn-cancel" class="btn btn-outline-secondary">Cancelar</button>
      </div>
      <div id="form-err" class="alert alert-danger d-none mt-3"></div>
      <div id="form-ok"  class="alert alert-success d-none mt-3"></div>
    </form>
  </div>
</aside>
<div id="inv-mask" class="inv-mask"></div>

<script>
  window.INV_CFG = {
    base: "<?= BASE_URL ?>",
    api:  "<?= BASE_URL ?>/modules/inventario_mtc/api.php",
    empresa_id: <?= (int)$empresaId ?>,
  };
</script>
<script src="<?= BASE_URL ?>/modules/inventario_mtc/inventario.js?v=11"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
