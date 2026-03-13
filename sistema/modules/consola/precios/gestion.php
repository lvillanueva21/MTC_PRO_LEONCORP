<!-- modules/consola/precios/gestion.php -->
<style>
  .px-wrap { display:flex; flex-direction:column; gap:16px; }
  .px-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .px-title { font-weight:600; margin-bottom:8px; }
  .help { font-size:12px; color:#6b7280; }

  /* Marquee para servicio seleccionado */
  .marquee {
    position: relative; overflow: hidden; white-space: nowrap;
    border:1px solid #e5e7eb; border-radius:6px; background:#f8fafc; padding:.45rem .75rem;
  }
  .marquee > .track { display:inline-block; padding-left:100%; animation: slide 14s linear infinite; }
  @keyframes slide { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

  /* Altura inputs como en “Crear servicio” */
  .form-control, .form-select { min-height: 38px; }

  /* Badges suaves */
  .badge-soft{display:inline-block;padding:.15rem .5rem;border-radius:.5rem;background:#eef2ff;color:#374151;font-size:.75rem;}
  .badge-danger-soft{background:#fee2e2;color:#991b1b;}
  .badge-success-soft{background:#dcfce7;color:#166534;}
  .badge-secondary-soft{background:#e5e7eb;color:#374151;}

  /* Tabla compacta */
  .table td, .table th { vertical-align: middle; }
  .px-srv-name{
    display:flex;
    align-items:center;
    gap:8px;
    min-width:0;
  }
  .px-srv-thumb{
    width:28px;
    height:28px;
    border-radius:6px;
    border:1px solid #e5e7eb;
    object-fit:cover;
    flex:0 0 28px;
    background:#fff;
  }
  .px-srv-thumb-empty{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    color:#9ca3af;
    background:#f3f4f6;
    font-size:11px;
  }
  .px-srv-main{ min-width:0; flex:1 1 auto; }
  .px-srv-text{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }
</style>

<div class="px-wrap">

  <!-- 1) Zona superior -->
  <div class="px-card">
    <div class="row g-2 align-items-start">
      <div class="col-12 col-lg-6">
        <label class="form-label mb-1">Lista de empresas</label>
        <select id="px-empresa" class="form-select">
          <option value="">Selecciona una empresa ...</option>
        </select>
        <div class="help mt-1">Al elegir una empresa se crearán automáticamente sus 5 precios por servicio si no existen.</div>
      </div>
      <div class="col-12 col-lg-6">
        <label class="form-label mb-1">Servicio seleccionado</label>
        <div class="marquee" id="px-sel-serv">
          <span class="track" id="px-sel-track">Aún no has seleccionado un servicio</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 2) Zona inferior: izquierda y derecha -->
  <div class="row g-3">

    <!-- 2. Servicios de la empresa -->
    <div class="col-12 col-lg-6">
      <div class="px-card">
        <div class="px-title">2. Servicios de la empresa</div>

        <form id="px-sfiltros" class="row g-2 align-items-start">
          <div class="col-12 col-md-8 d-flex flex-column">
            <label class="form-label mb-1">Buscar servicio</label>
            <input type="text" id="px-sq" class="form-control" placeholder="Buscar texto ...">
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Estado</label>
            <select id="px-sestado" class="form-select">
              <option value="">Todos</option>
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </form>

        <div id="px-salert" class="alert alert-info mt-2">
          Selecciona una empresa, podrás ver todos los servicios disponibles.
        </div>

        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:40px;">#</th>
                <th>Servicio</th>
                <th class="text-end" style="width:140px;">Acción</th>
              </tr>
            </thead>
            <tbody id="px-stbody"></tbody>
          </table>
        </div>

        <nav><ul class="pagination pagination-sm mt-2" id="px-spager"></ul></nav>
      </div>
    </div>

    <!-- 3. Precios del servicio -->
<div class="col-12 col-lg-6">
  <div id="m-right" class="border rounded p-3 overflow-auto" style="max-height:60vh;">
    <h6 class="mb-2">3. Precios del servicio</h6>

    <!-- Filtro estado -->
    <div class="row g-2 mb-2">
      <div class="col-12 col-md-4">
        <label class="form-label mb-1">Estado</label>
        <select id="p-est-pre" class="form-select">
          <option value="">Todos</option>
          <option value="1">Activos</option>
          <option value="0">Inactivos</option>
        </select>
      </div>
    </div>

    <div id="p-alert" class="alert alert-danger d-none mt-2"></div>

    <div class="table-responsive">
      <table class="table table-sm align-middle" id="p-precios">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th>Precio</th>
            <th style="width:220px;">Acción</th>
          </tr>
        </thead>
        <tbody id="p-tbody">
          <!-- filas via JS -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
  .badge-soft{display:inline-block;padding:.15rem .5rem;border-radius:.5rem;background:#e5e7eb;color:#374151;font-size:.75rem}
  .p-nota-empty{color:#b91c1c;font-weight:600}
  .actions-inline{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-start}
  .actions-inline .btn{flex:0 0 auto;white-space:nowrap}
  .price-cell input[type="number"]{max-width:120px}
  .price-cell .nota-input{min-width:220px;max-width:100%}
</style>

  </div>
</div>
