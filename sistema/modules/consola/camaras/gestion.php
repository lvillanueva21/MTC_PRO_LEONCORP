<!-- modules/consola/camaras/gestion.php -->
<style>
  .cam-wrap { display:flex; flex-direction:column; gap:16px; }
  .cam-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .cam-title { font-weight:600; margin-bottom:8px; }
  .help { font-size:12px; color:#6b7280; }
  .form-control, .form-select { min-height:38px; }

  .table td, .table th { vertical-align: middle; }
  .actions-inline{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-start; }
  .actions-inline .btn{ flex:0 0 auto; white-space:nowrap; }

  .hint{ background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:6px; padding:.5rem .75rem; }

  /* Paginación simple */
  .pagination .page-link{ cursor:pointer; }
</style>

<div class="cam-wrap">
  <!-- 1) TOP: DVR de la empresa (crear/editar 1:1) -->
  <div class="cam-card">
    <div class="cam-title">1. DVR de la empresa</div>

    <div class="row g-2 align-items-start">
      <div class="col-12 col-lg-4">
        <label class="form-label mb-1">Empresa</label>
        <select id="dv-empresa" class="form-select">
          <option value="0">Selecciona una empresa ...</option>
        </select>
        <div class="help mt-1">Cada empresa puede tener <strong>un solo DVR</strong>. Si la empresa aún no lo tiene, podrás crearlo aquí.</div>
      </div>

      <div class="col-12 col-lg-8">
        <form id="dvr-form" class="row g-2 align-items-start" autocomplete="off">
          <input type="hidden" id="dvr-id" value="0">
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Usuario principal *</label>
            <input id="dvr-u" class="form-control" required>
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Clave principal *</label>
            <input id="dvr-p" class="form-control" required>
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Total cámaras</label>
            <input id="dvr-cams" type="number" min="0" class="form-control" value="0">
          </div>

          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Usuario SUTRAN</label>
            <input id="dvr-su" class="form-control">
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Clave SUTRAN</label>
            <input id="dvr-sp" class="form-control">
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Link remoto</label>
            <input id="dvr-rl" class="form-control" placeholder="http(s)://…">
          </div>

          <div class="col-12 col-md-8 d-flex flex-column">
            <label class="form-label mb-1">Link local</label>
            <input id="dvr-ll" class="form-control" placeholder="http://192.168.x.x">
          </div>

          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1 invisible">Acción</label>
            <button class="btn btn-primary w-100" type="button" id="dvr-save">Guardar DVR</button>
          </div>
        </form>

        <div id="dvr-alert" class="alert alert-danger d-none mt-2"></div>
        <div id="dvr-ok" class="alert alert-success d-none mt-2"></div>
      </div>
    </div>
  </div>

  <!-- 2) ZONA INFERIOR: izquierda (discos) / derecha (historial del DVR de la empresa) -->
  <div class="row g-3">
    <!-- IZQUIERDA: Inventario de discos -->
    <div class="col-12 col-lg-6">
      <div class="cam-card">
        <div class="cam-title">2. Inventario de discos</div>

        <form id="disk-form" class="row g-2 align-items-start" autocomplete="off">
          <input type="hidden" id="disk-id" value="0">
          <div class="col-12 col-md-3 d-flex flex-column">
            <label class="form-label mb-1">Capacidad total (GB) *</label>
            <input id="disk-total" type="number" min="1" class="form-control" required>
          </div>
          <div class="col-12 col-md-3 d-flex flex-column">
            <label class="form-label mb-1">Libre (GB)</label>
            <input id="disk-free" type="number" min="0" class="form-control" placeholder="Opcional">
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Último cambio</label>
            <input id="disk-dt" type="datetime-local" class="form-control" placeholder="Opcional">
          </div>
          <div class="col-12 col-md-2 d-flex flex-column">
            <label class="form-label mb-1 invisible">Acción</label>
            <button class="btn btn-secondary w-100" type="button" id="disk-save">Guardar</button>
          </div>
        </form>

        <div id="disk-alert" class="alert alert-danger d-none mt-2"></div>
        <div id="disk-ok" class="alert alert-success d-none mt-2"></div>

        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle" id="disk-table">
            <thead>
              <tr>
                <th style="width:60px;">ID</th>
                <th>Total (GB)</th>
                <th>Libre (GB)</th>
                <th>Último cambio</th>
                <th style="width:180px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="disk-tbody"></tbody>
          </table>
        </div>
        <nav><ul class="pagination pagination-sm mt-2" id="disk-pager"></ul></nav>
      </div>
    </div>

    <!-- DERECHA: Historial del DVR de la empresa -->
    <div class="col-12 col-lg-6">
      <div class="cam-card">
        <div class="cam-title">3. Historial de discos del DVR de la empresa</div>

        <div id="hist-hint" class="hint mb-2">Selecciona una <strong>empresa</strong> y guarda su DVR para habilitar el historial.</div>

        <div id="hist-panel" class="d-none">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Disco actual</label>
              <div id="hist-current" class="form-control bg-light"></div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mb-1">Asignar disco</label>
              <div class="d-flex gap-2">
                <select id="hist-disk" class="form-select">
                  <option value="0">Selecciona del inventario…</option>
                </select>
                <button class="btn btn-success" id="hist-assign" type="button">Asignar</button>
                <button class="btn btn-warning" id="hist-retire" type="button">Retirar actual</button>
              </div>
            </div>
          </div>

          <div id="hist-alert" class="alert alert-danger d-none mt-2"></div>
          <div id="hist-ok" class="alert alert-success d-none mt-2"></div>

          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle" id="hist-table">
              <thead>
                <tr>
                  <th style="width:80px;">Hist ID</th>
                  <th style="width:70px;">Disco</th>
                  <th>Instalación</th>
                  <th>Retiro</th>
                  <th>Total / Libre (GB)</th>
                </tr>
              </thead>
              <tbody id="hist-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
