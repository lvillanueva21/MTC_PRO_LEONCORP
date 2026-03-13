<?php
// /modules/api_hub/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/api_hub/api_hub.css?v=1">
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">ApiHub</h1>
    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="alert alert-info">
        Área de api hub para usuarios desarrollo, en construcción.
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label small mb-1">Periodo</label>
              <input type="month" id="ahPeriodo" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
              <button type="button" class="btn btn-primary btn-sm" id="ahReload">
                <i class="fas fa-sync-alt mr-1"></i>Actualizar
              </button>
            </div>
          </div>
          <div id="ahMsg" class="small text-muted mt-2">Consultas API por empresa (éxitos y fallos).</div>
        </div>
      </div>

      <div class="row g-2 mb-3" id="ahCards">
        <div class="col-6 col-lg-3"><div class="ah-stat-card"><div class="ah-stat-label">DNI OK</div><div id="ahDniOk" class="ah-stat-num">0</div></div></div>
        <div class="col-6 col-lg-3"><div class="ah-stat-card"><div class="ah-stat-label">DNI FAIL</div><div id="ahDniFail" class="ah-stat-num">0</div></div></div>
        <div class="col-6 col-lg-3"><div class="ah-stat-card"><div class="ah-stat-label">RUC OK</div><div id="ahRucOk" class="ah-stat-num">0</div></div></div>
        <div class="col-6 col-lg-3"><div class="ah-stat-card"><div class="ah-stat-label">RUC FAIL</div><div id="ahRucFail" class="ah-stat-num">0</div></div></div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0" id="ahTable">
              <thead class="table-light">
                <tr>
                  <th>Empresa</th>
                  <th class="text-end">DNI OK</th>
                  <th class="text-end">DNI FAIL</th>
                  <th class="text-end">RUC OK</th>
                  <th class="text-end">RUC FAIL</th>
                  <th class="text-end">Total</th>
                  <th>Última consulta</th>
                  <th>Último estado</th>
                </tr>
              </thead>
              <tbody id="ahBody">
                <tr><td colspan="8" class="text-muted small">Cargando...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
  window.API_HUB_CFG = {
    api: <?= json_encode(BASE_URL . '/modules/api_hub/api.php') ?>
  };
</script>
<script src="<?= BASE_URL ?>/modules/api_hub/api_hub.js?v=1"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
