<!-- modules/consola/inventarios/gestion.php -->
<style>
  .iv-wrap { display:flex; flex-direction:column; gap:16px; }
  .iv-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .iv-title { font-weight:600; margin-bottom:8px; }
  .mini { font-size:.85rem; color:#6b7280; }

  .iv-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
  @media (max-width: 1200px){ .iv-grid { grid-template-columns:1fr; } }

  .iv-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
  .iv-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

  .iv-tabs { display:flex; gap:6px; flex-wrap:wrap; }
  .iv-tab { border:1px solid #d1d5db; padding:6px 12px; border-radius:999px; cursor:pointer; user-select:none; }
  .iv-tab.active { background:#111827; color:#fff; border-color:#111827; }

  .iv-form-grid { display:grid; grid-template-columns: repeat(12, 1fr); gap:8px; }
  .iv-col-12 { grid-column: span 12; }
  .iv-col-8  { grid-column: span 8; }
  .iv-col-6  { grid-column: span 6; }
  .iv-col-4  { grid-column: span 4; }
  .iv-col-3  { grid-column: span 3; }
  .iv-col-2  { grid-column: span 2; }

  .iv-form label { font-size:.85rem; margin-bottom:.25rem; }
  .iv-form input { min-height:36px; }

  .table td,.table th { vertical-align: middle; }
  .actions-cell{ white-space: nowrap; }
</style>

<div class="iv-wrap">
  <!-- 1) Empresa + PDF -->
  <div class="iv-card">
    <div class="iv-toolbar">
      <div class="iv-actions">
        <div>
          <label class="mini mb-1 d-block">Empresa</label>
          <select id="iv-empresa" class="form-control" style="min-width:260px">
            <option value="">Cargando empresas…</option>
          </select>
        </div>
        <div class="mini">
          Última actualización: <strong id="iv-ultima">—</strong>
        </div>
      </div>
      <div class="iv-actions">
        <button type="button" id="iv-btn-pdf" class="btn btn-outline-primary btn-sm" disabled>
          <i class="fas fa-file-download"></i> Descargar PDF
        </button>
      </div>
    </div>
  </div>

  <!-- 2) Tabs de categorías -->
  <div class="iv-card">
    <div class="iv-title">Categorías</div>
    <div class="iv-tabs" id="iv-tabs">
      <div class="iv-tab" data-tipo="pc">Computadoras</div>
      <div class="iv-tab" data-tipo="cam">Cámaras</div>
      <div class="iv-tab" data-tipo="dvr">DVR</div>
      <div class="iv-tab" data-tipo="hue">Huelleros</div>
      <div class="iv-tab" data-tipo="sw">Switches</div>
      <div class="iv-tab" data-tipo="red">Datos de la red</div>
      <div class="iv-tab" data-tipo="tx">Acceso de transmisión</div>
    </div>
  </div>

  <!-- 3) Formulario + Listado -->
  <div class="iv-grid">
    <!-- Form -->
    <div class="iv-card">
      <div class="iv-title" id="iv-form-title">Crear registro</div>
      <form id="iv-form" class="iv-form" autocomplete="off"></form>
      <div class="iv-actions mt-2">
        <button type="button" id="iv-save" class="btn btn-primary">Crear</button>
        <button type="button" id="iv-cancel" class="btn btn-secondary">Cancelar</button>
      </div>
      <div id="iv-ok" class="alert alert-success d-none mt-2"></div>
      <div id="iv-err" class="alert alert-danger d-none mt-2"></div>
    </div>

    <!-- List -->
    <div class="iv-card">
      <div class="iv-title">Listado</div>

      <div class="row g-2 mb-2">
        <div class="col-12 col-md-6">
          <label class="mini mb-1">Texto</label>
          <input type="text" id="iv-q" class="form-control" placeholder="Buscar…">
        </div>
        <div class="col-6 col-md-3">
          <label class="mini mb-1">Estado</label>
          <select id="iv-estado" class="form-control">
            <option value="">Todos</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="mini mb-1">Por página</label>
          <select id="iv-perpage" class="form-control">
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>

      <div id="iv-list-err" class="alert alert-danger d-none"></div>

      <div class="table-responsive">
        <table class="table table-sm align-middle" id="iv-table">
          <thead>
            <tr id="iv-thead"></tr>
          </thead>
          <tbody id="iv-tbody"></tbody>
        </table>
      </div>
      <nav><ul class="pagination pagination-sm mt-2" id="iv-pager"></ul></nav>
    </div>
  </div>
</div>
