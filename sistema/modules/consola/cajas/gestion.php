<!-- modules/consola/cajas/gestion.php -->
<style>
  :root{
    --fg:#111827; --muted:#6b7280; --line:#e5e7eb;
    --accent:#7c3aed; --accent-ink:#faf5ff;
    --ok:#16a34a; --warn:#ca8a04; --danger:#dc2626; --info:#2563eb;
  }
  .cx-wrap{display:flex;flex-direction:column;gap:16px;color:var(--fg)}
  .cx-header{background:var(--accent);color:#fff;border-radius:10px;padding:10px 14px;font-weight:700}
  .cx-card{border:1px solid var(--line);border-radius:10px;padding:12px;background:#fff}
  .cx-title{font-weight:700;margin-bottom:10px}
  .help{font-size:12px;color:var(--muted)}
  .kpis{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px}
  .kpi{border:1px solid var(--line);border-radius:10px;padding:10px;background:#f8fafc}
  .kpi .lbl{font-size:12px;color:var(--muted)}
  .kpi .val{font-size:18px;font-weight:700;margin-top:4px}
  .kpi .val.small{font-size:14px;font-weight:600}
  .row{display:flex;flex-wrap:wrap;gap:12px}
  .col{flex:1 1 220px}
  .form-control,.form-select{min-height:38px;width:100%}
  .table{width:100%;border-collapse:collapse}
  .table th,.table td{border-top:1px solid var(--line);padding:8px 10px;vertical-align:middle}
  .table thead th{border-top:0;background:#f3f4f6}
  .actions{display:flex;gap:6px;flex-wrap:wrap}
  .btn{border:1px solid var(--line);padding:6px 10px;border-radius:8px;background:#fff;cursor:pointer}
  .btn.blue{background:#e0e7ff;border-color:#c7d2fe}
  .btn.yellow{background:#fef9c3;border-color:#fde68a}
  .btn.red{background:#fee2e2;border-color:#fecaca}
  .btn.gray{background:#f3f4f6}
  .badge{display:inline-block;padding:.2rem .5rem;border-radius:9999px;font-size:.75rem}
  .badge.green{background:#dcfce7;color:#166534}
  .badge.red{background:#fee2e2;color:#991b1b}
  .pager{display:flex;gap:6px;justify-content:center;margin-top:8px}
  .pager .page{border:1px solid var(--line);padding:4px 8px;border-radius:6px;cursor:pointer;background:#fff}
  .pager .active{background:#eef2ff;border-color:#c7d2fe}
  .submeta{font-size:12px;color:var(--muted);display:flex;gap:16px;margin-top:6px}
  .alert{display:none;border-radius:8px;padding:8px 10px}
  .alert.ok{display:block;background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
  .alert.err{display:block;background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
   .btn[disabled]{ opacity:.6; cursor:not-allowed; }
</style>

<div class="cx-wrap">
  <!-- 1) Filtro de cajas / KPIs -->
  <div class="cx-card">
    <div class="row">
      <div class="col" style="max-width:360px">
        <label class="form-label">Empresa</label>
        <select id="fx-emp" class="form-select"><option value="0">Cargando…</option></select>
        <div class="help">Consola de <b>Gerente</b> / <b>Desarrollo</b>. Aquí puedes abrir/cerrar y <b>eliminar</b> cajas.</div>
      </div>
      <div class="col kpis">
        <div class="kpi">
          <div class="lbl">Cajas mensuales este año</div>
          <div class="val" id="k-mens">—</div>
        </div>
        <div class="kpi">
          <div class="lbl">Cajas diarias este mes</div>
          <div class="val" id="k-dia">—</div>
        </div>
        <div class="kpi">
          <div class="lbl">Actual caja mensual</div>
          <div class="val small" id="k-mens-act">—</div>
        </div>
        <div class="kpi">
          <div class="lbl">Actual caja diaria</div>
          <div class="val small" id="k-dia-act">—</div>
        </div>
      </div>
    </div>
  </div>
  <!-- 1.b) Apertura rápida -->
  <div class="cx-card">
    <div class="cx-title">1.b Apertura rápida</div>
    <div class="row">
      <div class="col" style="max-width:360px">
        <label class="form-label">Mes a abrir</label>
        <input type="month" id="m-open-month" class="form-control">
        <div class="help">Crea una <b>nueva</b> caja mensual. Si ya existe, el sistema avisará. Requiere que no haya otra mensual abierta.</div>
        <button id="btn-m-open" class="btn blue" style="margin-top:8px">Abrir mensual</button>
      </div>
      <div class="col" style="max-width:360px">
        <label class="form-label">Día a abrir</label>
        <input type="date" id="d-open-date" class="form-control">
        <div class="help">Crea una <b>nueva</b> caja diaria para la fecha elegida. Si ya existe, el sistema avisará. Requiere mensual del período abierta.</div>
        <button id="btn-d-open" class="btn blue" style="margin-top:8px">Abrir diaria</button>
      </div>
    </div>
  </div>

  <div id="alerts"></div>

  <div class="row">
    <!-- 2) Mensuales -->
    <div class="col">
      <div class="cx-card">
        <div class="cx-title">2. Cajas mensuales</div>
        <div class="row">
          <div class="col">
            <label>Mes inicial</label>
            <input type="month" id="lm-ini" class="form-control">
          </div>
          <div class="col">
            <label>Mes final</label>
            <input type="month" id="lm-fin" class="form-control">
          </div>
          <div class="col" style="max-width:220px">
            <label>Estado</label>
            <select id="lm-estado" class="form-select">
              <option value="">Todos</option>
              <option value="abierta">Abierta</option>
              <option value="cerrada">Cerrada</option>
            </select>
          </div>
        </div>

        <div class="table-responsive" style="margin-top:10px">
          <table class="table">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>Empresa</th>
                <th>Mes</th>
                <th>Código</th>
                <th>Estado</th>
                <th style="width:240px" class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody id="lm-tbody"></tbody>
          </table>
        </div>
        <div class="pager" id="lm-pager"></div>
      </div>
    </div>

    <!-- 3) Diarias -->
    <div class="col">
      <div class="cx-card">
        <div class="cx-title">3. Cajas diarias</div>
        <div class="row">
          <div class="col">
            <label>Día inicial</label>
            <input type="date" id="ld-ini" class="form-control">
          </div>
          <div class="col">
            <label>Día final</label>
            <input type="date" id="ld-fin" class="form-control">
          </div>
          <div class="col" style="max-width:220px">
            <label>Estado</label>
            <select id="ld-estado" class="form-select">
              <option value="">Todos</option>
              <option value="abierta">Abierta</option>
              <option value="cerrada">Cerrada</option>
            </select>
          </div>
        </div>

        <div class="table-responsive" style="margin-top:10px">
          <table class="table">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>Empresa</th>
                <th>Día</th>
                <th>Código</th>
                <th>Estado</th>
                <th style="width:240px" class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody id="ld-tbody"></tbody>
          </table>
        </div>
        <div class="pager" id="ld-pager"></div>
      </div>
    </div>
  </div>
</div>
