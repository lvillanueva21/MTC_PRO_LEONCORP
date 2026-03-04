<!-- modules/consola/comprobantes/gestion.php -->
<style>
  .cmp-wrap{display:flex;flex-direction:column;gap:16px}
  .cmp-grid{display:grid;grid-template-columns:minmax(320px,430px) 1fr;gap:16px}
  @media (max-width: 1200px){.cmp-grid{grid-template-columns:1fr}}
  .cmp-card{border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fff}
  .cmp-title{font-weight:600;margin-bottom:8px}
  .cmp-help{font-size:12px;color:#6b7280}
  .cmp-form .form-control,.cmp-form .form-select,
  .cmp-filtros .form-control,.cmp-filtros .form-select{min-height:38px}
  .cmp-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
  @media (max-width: 991.98px){.cmp-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media (max-width: 575.98px){.cmp-summary{grid-template-columns:1fr}}
  .cmp-stat{border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:linear-gradient(180deg,#f8fafc,#eef2ff)}
  .cmp-stat .k{font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em}
  .cmp-stat .v{font-size:28px;font-weight:700;line-height:1.1}
  .cmp-note{border:1px dashed #f59e0b;border-radius:10px;padding:12px;background:#fffbeb}
  .cmp-note .headline{font-weight:700;color:#92400e}
  .cmp-badges{display:flex;flex-wrap:wrap;gap:8px}
  .cmp-badges .badge{font-size:12px;padding:.45rem .65rem}
  .cmp-subtext{font-size:12px;color:#6b7280}
  .cmp-chip{display:inline-flex;align-items:center;border-radius:999px;padding:.2rem .6rem;font-size:12px;font-weight:600}
  .cmp-chip.ok{background:#dcfce7;color:#166534}
  .cmp-chip.warn{background:#fee2e2;color:#991b1b}
  .cmp-chip.soft{background:#e0f2fe;color:#075985}
  .cmp-chip.neutral{background:#f3f4f6;color:#374151}
  .cmp-locked{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:8px 10px}
  .cmp-locked strong{color:#0f172a}
  .table td,.table th{vertical-align:middle}
  .cmp-row-actions{display:flex;gap:8px;flex-wrap:wrap}
  .cmp-row-actions .btn{white-space:nowrap}
  .cmp-table-meta{font-size:12px;color:#6b7280}
  .cmp-empty{font-size:13px;color:#6b7280;padding:8px 0}
</style>

<div class="cmp-wrap">
  <div id="cmp-ok" class="alert alert-success alert-dismissible d-none" role="alert">
    <button type="button" class="close cmp-ok-close" aria-label="Cerrar">
      <span aria-hidden="true">&times;</span>
    </button>
    <span class="msg"></span>
  </div>
  <div id="cmp-err" class="alert alert-danger d-none"></div>

  <div class="cmp-summary" id="cmp-summary">
    <div class="cmp-stat">
      <div class="k">Empresas</div>
      <div class="v" id="cmp-stat-total-empresas">0</div>
      <div class="cmp-subtext">Registradas en el sistema</div>
    </div>
    <div class="cmp-stat">
      <div class="k">Con ticket activo</div>
      <div class="v" id="cmp-stat-con-ticket">0</div>
      <div class="cmp-subtext">Ya pueden vender en caja</div>
    </div>
    <div class="cmp-stat">
      <div class="k">Sin ticket</div>
      <div class="v" id="cmp-stat-sin-ticket">0</div>
      <div class="cmp-subtext">No podrán completar ventas</div>
    </div>
    <div class="cmp-stat">
      <div class="k">Series activas</div>
      <div class="v" id="cmp-stat-series-activas">0</div>
      <div class="cmp-subtext">POS listas para emitir</div>
    </div>
  </div>

  <div class="cmp-grid">
    <div>
      <div class="cmp-card mb-3">
        <div class="cmp-title" id="cmp-form-title">1. Crear ticket POS por empresa</div>

        <form id="cmp-form" class="cmp-form row g-3" autocomplete="off">
          <input type="hidden" id="cmp-id" value="0">

          <div class="col-12">
            <label class="form-label mb-1">Empresa</label>
            <select id="cmp-empresa" class="form-select" required>
              <option value="">Cargando...</option>
            </select>
            <div class="cmp-help mt-1">Cada empresa debe tener al menos una serie activa de tipo ticket para poder vender desde caja.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label mb-1">Tipo de comprobante</label>
            <input type="text" class="form-control" value="TICKET" readonly>
            <div class="cmp-help mt-1">Por ahora el módulo de caja trabaja solo con tickets POS.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label mb-1">Serie</label>
            <input type="text" id="cmp-serie" class="form-control text-uppercase" maxlength="10" placeholder="Ejemplo: T001" required>
            <div class="cmp-help mt-1">Solo letras, números y guión. Máximo 10 caracteres.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label mb-1">Siguiente número</label>
            <input type="number" id="cmp-next" class="form-control" min="1" step="1" value="1" required>
            <div class="cmp-help mt-1">Debe ser mayor que el último número ya emitido en esa serie.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label mb-1">Estado inicial</label>
            <select id="cmp-activo" class="form-select">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
            <div class="cmp-help mt-1">Se recomienda dejar solo una serie activa por empresa.</div>
          </div>

          <div class="col-12">
            <div class="cmp-locked">
              <div><strong>Último ticket usado:</strong> <span id="cmp-last-ticket">Aún no registra ventas</span></div>
              <div class="cmp-subtext" id="cmp-usage-note">Si esta serie ya tiene ventas emitidas, no se permitirá cambiar su código ni moverla a otra empresa.</div>
            </div>
          </div>

          <div class="col-12 d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" id="cmp-save">Crear ticket</button>
            <button type="button" class="btn btn-secondary d-none" id="cmp-cancel">Cancelar edición</button>
          </div>
        </form>
      </div>

      <div class="cmp-card">
        <div class="cmp-title">Guía rápida y validaciones</div>
        <div class="cmp-note mb-3">
          <div class="headline">Sin una serie activa, la empresa no podrá completar ventas en caja.</div>
          <div class="cmp-subtext mt-1">
            El módulo `caja` intenta tomar la primera serie activa de la empresa. Si no encuentra ninguna, la venta se detiene y no se genera comprobante.
          </div>
        </div>

        <div class="mb-2 fw-semibold">Ejemplos válidos de series</div>
        <div class="cmp-badges mb-3">
          <span class="badge badge-light border">T001</span>
          <span class="badge badge-light border">T002</span>
          <span class="badge badge-light border">GC01</span>
          <span class="badge badge-light border">LEON-1</span>
        </div>

        <div class="mb-2 fw-semibold">Reglas aplicadas por este módulo</div>
        <ul class="mb-0 pl-3">
          <li>Solo se crea tipo de comprobante `TICKET`.</li>
          <li>No se permiten espacios ni símbolos extraños en la serie.</li>
          <li>Si una empresa ya tiene una serie activa, no podrás activar otra sin desactivar la anterior.</li>
          <li>Si una serie ya emitió ventas, no podrás cambiar su código ni bajarle el correlativo.</li>
          <li>Para dejar de usar una serie, se recomienda desactivarla en lugar de eliminarla.</li>
        </ul>
      </div>
    </div>

    <div>
      <div class="cmp-card mb-3">
        <div class="cmp-title">2. Empresas y estado de ticket</div>

        <form id="cmp-company-filters" class="cmp-filtros row g-2">
          <div class="col-12 col-md-7">
            <label class="form-label mb-1">Buscar empresa</label>
            <input type="text" id="cmp-company-q" class="form-control" placeholder="Nombre comercial, razón social o RUC...">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label mb-1">Estado</label>
            <select id="cmp-company-status" class="form-select">
              <option value="all">Todas</option>
              <option value="with_active">Con ticket activo</option>
              <option value="only_inactive">Solo tickets inactivos</option>
              <option value="no_series">Sin tickets</option>
            </select>
          </div>
        </form>

        <div id="cmp-company-alert" class="alert alert-danger d-none mt-2"></div>

        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:70px;">ID</th>
                <th>Empresa</th>
                <th style="width:140px;">RUC</th>
                <th style="width:180px;">Ticket activo</th>
                <th style="width:140px;">Estado</th>
                <th style="width:220px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="cmp-company-tbody"></tbody>
          </table>
        </div>

        <nav><ul class="pagination pagination-sm mt-2" id="cmp-company-pager"></ul></nav>
      </div>

      <div class="cmp-card">
        <div class="cmp-title">3. Tickets registrados</div>

        <form id="cmp-series-filters" class="cmp-filtros row g-2">
          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Empresa</label>
            <select id="cmp-list-empresa" class="form-select">
              <option value="0">Todas</option>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Estado</label>
            <select id="cmp-list-activo" class="form-select">
              <option value="all">Todas</option>
              <option value="1">Activas</option>
              <option value="0">Inactivas</option>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Buscar serie</label>
            <input type="text" id="cmp-list-q" class="form-control" placeholder="Serie o empresa...">
          </div>
        </form>

        <div id="cmp-series-alert" class="alert alert-danger d-none mt-2"></div>

        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:70px;">ID</th>
                <th>Empresa</th>
                <th style="width:120px;">Serie</th>
                <th style="width:130px;">Siguiente</th>
                <th style="width:150px;">Último usado</th>
                <th style="width:120px;">Estado</th>
                <th style="width:220px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="cmp-series-tbody"></tbody>
          </table>
        </div>

        <nav><ul class="pagination pagination-sm mt-2" id="cmp-series-pager"></ul></nav>
      </div>
    </div>
  </div>
</div>
