<!-- modules/consola/comunicados/gestion.php -->
<style>
  .c-wrap { display:flex; flex-direction:column; gap:16px; }
  .c-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .c-title{ font-weight:600; margin-bottom:8px; }
  .help { font-size:12px; color:#6b7280; }
  .form-control,.form-select{ min-height:38px; }
  .status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-left:8px;vertical-align:middle;}
  .status-on{background:#16a34a;} .status-off{background:#dc2626;}
  .name-cell{ display:flex; align-items:center; justify-content:space-between; gap:8px; min-width:0; }
  .name-cell .name-text{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .actions-inline{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-start; }
</style>

<div class="c-wrap">
  <!-- 1) Crear/Editar -->
  <div class="c-card">
    <div class="c-title">1. Crear / Editar comunicado</div>
    <form id="c-form" class="row g-2 align-items-start" autocomplete="off" enctype="multipart/form-data">
      <div class="col-12 col-lg-4 d-flex flex-column">
        <label class="form-label mb-1">Título (obligatorio)</label>
        <input id="c-titulo" name="titulo" type="text" maxlength="300" class="form-control" required>
      </div>
      <div class="col-12 col-lg-4 d-flex flex-column">
        <label class="form-label mb-1">Imagen (opcional)</label>
        <input id="c-img" name="imagen" type="file" accept="image/*" class="form-control">
        <div class="help mt-1">PNG/JPG/WebP (≤5MB)</div>
      </div>
      <div class="col-12 col-lg-4 d-flex flex-column">
        <label class="form-label mb-1">Activo</label>
        <select name="activo" class="form-select">
          <option value="1" selected>Activo</option>
          <option value="0">Inactivo</option>
        </select>
      </div>

      <div class="col-12 d-flex flex-column">
        <label class="form-label mb-1">Cuerpo (opcional)</label>
        <textarea id="c-cuerpo" name="cuerpo" rows="3" class="form-control" placeholder="Texto del comunicado…"></textarea>
      </div>

      <div class="col-12 col-md-4 d-flex flex-column">
        <label class="form-label mb-1">Fecha inicio (opcional)</label>
        <input id="c-fi" name="fecha_inicio" type="datetime-local" class="form-control">
      </div>
      <div class="col-12 col-md-4 d-flex flex-column">
        <label class="form-label mb-1">Fecha fin (opcional)</label>
        <input id="c-ff" name="fecha_fin" type="datetime-local" class="form-control">
      </div>
      <div class="col-12 col-md-4 d-flex flex-column">
        <label class="form-label mb-1">Fecha límite (contador)</label>
        <input id="c-fl" name="fecha_limite" type="datetime-local" class="form-control">
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label mb-1 invisible">Acción</label>
        <button id="c-guardar" type="button" class="btn btn-primary w-100">Crear</button>
      </div>
    </form>
    <div id="c-err" class="alert alert-danger d-none mt-2"></div>
    <div id="c-ok" class="alert alert-success d-none mt-2"><span class="msg"></span></div>
  </div>

  <!-- 2 y 3) Panel izquierdo: Audiencias / Panel derecho: Listar -->
  <div class="row g-3">
    <!-- 2) Audiencias -->
    <div class="col-12 col-lg-6">
      <div class="c-card h-100">
        <div class="c-title">2. Audiencias del comunicado</div>
        <div id="a-hint" class="alert alert-info">Selecciona <b>Audiencias</b> en la tabla de la derecha.</div>

        <div id="a-panel" class="d-none">
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label mb-1">Comunicado actual</label>
              <div class="form-control bg-light" id="a-com-actual"></div>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label mb-1">Tipo de audiencia</label>
              <select id="a-tipo" class="form-select">
                <option value="TODOS">TODOS</option>
                <option value="USUARIO">USUARIO</option>
                <option value="ROL">ROL</option>
                <option value="EMPRESA">EMPRESA</option>
                <option value="EMPRESA_ROL">EMPRESA + ROL</option>
              </select>
            </div>

            <div id="wrap-emp" class="col-12 col-md-4 d-none">
              <label class="form-label mb-1">Empresa</label>
              <select id="a-empresa" class="form-select"></select>
            </div>
            <div id="wrap-rol" class="col-12 col-md-4 d-none">
              <label class="form-label mb-1">Rol</label>
              <select id="a-rol" class="form-select"></select>
            </div>

            <div id="wrap-user" class="col-12 d-none">
              <label class="form-label mb-1">Buscar usuario</label>
              <div class="input-group">
                <input id="a-uq" type="text" class="form-control" placeholder="nombre/usuario…">
                <button id="a-buscar" type="button" class="btn btn-secondary">Buscar</button>
              </div>
              <div id="a-ures" class="border rounded p-2 mt-2" style="max-height:180px; overflow:auto;"></div>
            </div>

            <div class="col-12 d-flex justify-content-between align-items-center">
              <div class="help">Vista previa: <span id="a-prev">—</span></div>
              <button id="a-add" type="button" class="btn btn-success">Añadir regla</button>
            </div>
          </div>

          <div class="table-responsive mt-3">
            <table class="table table-sm align-middle">
              <thead><tr><th style="width:60px;">#</th><th>Tipo</th><th>Detalle</th><th class="text-end" style="width:120px;">Acción</th></tr></thead>
              <tbody id="a-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 3) Listar -->
    <div class="col-12 col-lg-6">
      <div class="c-card h-100">
        <div class="c-title">3. Listar comunicados</div>

        <form class="row g-2 align-items-start">
          <div class="col-12 col-lg-5">
            <label class="form-label mb-1">Texto</label>
            <input id="f-q" type="text" class="form-control" placeholder="buscar título/cuerpo…">
          </div>
          <div class="col-6 col-lg-3">
            <label class="form-label mb-1">Estado</label>
            <select id="f-estado" class="form-select">
              <option value="">Todos</option>
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
          <div class="col-6 col-lg-4">
            <label class="form-label mb-1">Vigencia</label>
            <select id="f-vig" class="form-select">
              <option value="">Todas</option>
              <option value="vigente">Vigente</option>
              <option value="programado">Programado</option>
              <option value="expirado">Expirado</option>
            </select>
          </div>
        </form>

        <div id="l-err" class="alert alert-danger d-none mt-2"></div>

        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead><tr><th style="width:60px;">#</th><th>Título</th><th style="width:380px;">Acciones</th></tr></thead>
            <tbody id="l-tbody"></tbody>
          </table>
        </div>
        <nav><ul id="l-pager" class="pagination pagination-sm mt-2"></ul></nav>
      </div>
    </div>
  </div>
</div>
