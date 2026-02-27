<!-- modules/consola/publicidades/gestion.php -->
<style>
  .pb-wrap { display:flex; flex-direction:column; gap:16px; }
  .pb-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .pb-title{ font-weight:600; margin-bottom:8px; }
  .help { font-size:12px; color:#6b7280; }
  .form-control,.form-select{ min-height:38px; }

  .status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-left:8px;vertical-align:middle;}
  .status-on{background:#16a34a;} .status-off{background:#dc2626;}
  .name-cell{ display:flex; align-items:center; justify-content:space-between; gap:8px; min-width:0; }
  .name-cell .name-text{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

  /* chips (separador por espacio/enter) */
  .chiparea.form-control{ min-height:38px; padding:6px 8px; display:flex; flex-wrap:wrap; gap:6px; align-items:center; position:relative; }
  .chip{display:inline-flex; align-items:center; gap:6px; padding:3px 8px; border-radius:999px; background:#eef2ff; font-size:12px;}
  .chip .x{border:0; background:transparent; cursor:pointer; line-height:1; font-weight:700;}
  .chip-input{flex:1; min-width:120px; outline:none; border:0; background:transparent;}
  .chip-suggest{ position:absolute; left:8px; right:8px; top:100%; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.06); max-height:200px; overflow:auto; display:none; }
  .chip-suggest.show{ display:block; }
  .chip-suggest button{ display:block; width:100%; text-align:left; border:0; background:#fff; padding:6px 10px; }
  .chip-suggest button:hover{ background:#f3f4f6; }

  .pb-detail td{ background:#f9fafb; }
  .chips-read{ display:flex; gap:6px; flex-wrap:wrap; }
  .chip.soft{ background:#e5e7eb; }

  .actions-inline{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-start; }
  .grid-2{ display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
  @media (max-width: 992px){ .grid-2{ grid-template-columns: 1fr; } }
</style>

<div class="pb-wrap">
  <!-- 1) Crear / Editar Publicidad -->
  <div class="pb-card">
    <div class="pb-title">1. Crear / Editar publicidad</div>
    <form id="p-form" class="row g-2 align-items-start" autocomplete="off" enctype="multipart/form-data">
      <div class="col-12 col-lg-4 d-flex flex-column">
        <label class="form-label mb-1">Título (obligatorio)</label>
        <input id="p-titulo" name="titulo" type="text" maxlength="300" class="form-control" required>
      </div>
      <div class="col-12 col-lg-4 d-flex flex-column">
        <label class="form-label mb-1">Imagen (opcional)</label>
        <input id="p-img" name="imagen" type="file" accept="image/*" class="form-control">
        <div class="help mt-1">PNG/JPG/WebP (≤5MB). Se guarda en <code>almacen/img_publicidades/</code></div>
      </div>
      <div class="col-12 col-lg-4 d-flex flex-column">
        <label class="form-label mb-1">Activo</label>
        <select name="activo" class="form-select">
          <option value="1" selected>Activo</option>
          <option value="0">Inactivo</option>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label mb-1">Descripción</label>
        <textarea id="p-desc" name="descripcion" rows="3" class="form-control" placeholder="Texto descriptivo…"></textarea>
      </div>

      <div class="col-12 col-lg-6 d-flex flex-column">
        <label class="form-label mb-1">Etiquetas (usa espacio o Enter para confirmar)</label>
        <div id="p-tags" class="chiparea form-control">
          <input id="p-tags-input" class="chip-input" type="text" placeholder="promoción curso outlet …">
          <div id="p-suggest" class="chip-suggest"></div>
        </div>
        <input type="hidden" id="p-etiquetas" name="etiquetas">
        <div class="help mt-1">Empieza a escribir: aparecerán sugerencias basadas en etiquetas existentes. Confirma con <b>espacio</b> o <b>Enter</b>. Quita con ✕.</div>
      </div>

      <div class="col-12 col-lg-3">
        <label class="form-label mb-1 invisible">—</label>
        <button id="p-guardar" type="button" class="btn btn-primary w-100">Crear</button>
      </div>
    </form>
    <div id="p-err" class="alert alert-danger d-none mt-2"></div>
    <div id="p-ok"  class="alert alert-success d-none mt-2"><span class="msg"></span></div>
  </div>

  <!-- 2 y 3) Audiencias / Listado -->
  <div class="grid-2">
    <!-- Audiencias por publicidad -->
    <div class="pb-card">
      <div class="pb-title">2. Audiencias de la publicidad</div>
      <div id="a-hint" class="alert alert-info">Selecciona <b>Audiencias</b> en la tabla de la derecha.</div>
      <div id="a-panel" class="d-none">
        <div class="row g-2">
          <div class="col-12">
            <label class="form-label mb-1">Publicidad actual</label>
            <div class="form-control bg-light" id="a-pub-actual"></div>
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

          <div class="table-responsive mt-3">
            <table class="table table-sm align-middle">
              <thead><tr><th style="width:60px;">#</th><th>Tipo</th><th>Detalle</th><th class="text-end" style="width:120px;">Acción</th></tr></thead>
              <tbody id="a-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Listado de publicidades -->
    <div class="pb-card">
      <div class="pb-title">3. Listar publicidades</div>
      <form class="row g-2 align-items-start">
        <div class="col-12 col-lg-5">
          <label class="form-label mb-1">Texto</label>
          <input id="f-q" type="text" class="form-control" placeholder="buscar título/descr…">
        </div>
        <div class="col-6 col-lg-3">
          <label class="form-label mb-1">Estado</label>
          <select id="f-estado" class="form-select">
            <option value="">Todos</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </form>
      <div id="l-err" class="alert alert-danger d-none mt-2"></div>

      <div class="table-responsive mt-2">
        <table class="table table-sm align-middle">
          <thead><tr><th style="width:60px;">#</th><th>Título</th><th style="width:420px;">Acciones</th></tr></thead>
          <tbody id="l-tbody"></tbody>
        </table>
      </div>
      <nav><ul id="l-pager" class="pagination pagination-sm mt-2"></ul></nav>
    </div>
  </div>

  <!-- 4) Grupos de publicidades -->
  <div class="pb-card">
    <div class="pb-title">4. Grupos de publicidades (layouts 1/2/4/N)</div>
    <div class="row g-2">
      <!-- Crear / Editar grupo -->
      <div class="col-12 col-lg-4">
        <form id="g-form" class="row g-2 align-items-start" autocomplete="off">
          <div class="col-12">
            <label class="form-label mb-1">Nombre del grupo</label>
            <input id="g-nombre" name="nombre" type="text" maxlength="150" class="form-control" required>
          </div>
          <div class="col-6">
            <label class="form-label mb-1">Slots (layout)</label>
            <input id="g-slots" name="layout_slots" type="number" min="1" step="1" class="form-control" value="1">
          </div>
          <div class="col-6">
            <label class="form-label mb-1">Activo</label>
            <select id="g-activo" class="form-select">
              <option value="1" selected>Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
          <div class="col-12">
            <button id="g-guardar" type="button" class="btn btn-primary w-100">Crear grupo</button>
          </div>
        </form>
        <div id="g-err" class="alert alert-danger d-none mt-2"></div>
        <div id="g-ok"  class="alert alert-success d-none mt-2"><span class="msg"></span></div>
      </div>

      <!-- Lista de grupos -->
      <div class="col-12 col-lg-4">
        <div class="d-flex align-items-end gap-2">
          <div class="flex-grow-1">
            <label class="form-label mb-1">Buscar grupo</label>
            <input id="g-q" type="text" class="form-control" placeholder="texto…">
          </div>
          <div style="width:160px;">
            <label class="form-label mb-1">Estado</label>
            <select id="g-estado" class="form-select">
              <option value="">Todos</option>
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
        <div id="gl-err" class="alert alert-danger d-none mt-2"></div>
        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead><tr><th>#</th><th>Grupo</th><th style="width:180px;">Acciones</th></tr></thead>
            <tbody id="g-tbody"></tbody>
          </table>
        </div>
        <nav><ul id="g-pager" class="pagination pagination-sm mt-2"></ul></nav>
      </div>

      <!-- Items del grupo + audiencias -->
      <div class="col-12 col-lg-4">
        <div class="pb-title">Items del grupo seleccionado</div>
        <div id="gi-hint" class="alert alert-info">Selecciona un grupo en la tabla central.</div>
        <div id="gi-panel" class="d-none">
          <div class="input-group mb-2">
            <input id="gi-q" type="text" class="form-control" placeholder="Buscar publicidad para añadir…">
            <button id="gi-buscar" class="btn btn-secondary" type="button">Buscar</button>
          </div>
          <div id="gi-suggest" class="border rounded p-2 mb-2" style="max-height:180px; overflow:auto;"></div>

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead><tr><th>#</th><th>Publicidad</th><th style="width:160px;">Orden</th><th class="text-end" style="width:120px;">Acción</th></tr></thead>
              <tbody id="gi-tbody"></tbody>
            </table>
          </div>

          <div class="pb-title mt-3">Audiencias del grupo</div>
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label mb-1">Tipo de audiencia</label>
              <select id="ga-tipo" class="form-select">
                <option value="TODOS">TODOS</option>
                <option value="USUARIO">USUARIO</option>
                <option value="ROL">ROL</option>
                <option value="EMPRESA">EMPRESA</option>
                <option value="EMPRESA_ROL">EMPRESA + ROL</option>
              </select>
            </div>
            <div class="col-12 d-flex gap-2">
              <select id="ga-empresa" class="form-select"></select>
              <select id="ga-rol" class="form-select"></select>
            </div>
            <div class="col-12">
              <div class="input-group">
                <input id="ga-uq" type="text" class="form-control" placeholder="buscar usuario…">
                <button id="ga-buscar" class="btn btn-secondary" type="button">Buscar</button>
              </div>
              <div id="ga-ures" class="border rounded p-2 mt-2" style="max-height:140px; overflow:auto;"></div>
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center">
              <div class="help">Preview: <span id="ga-prev">—</span></div>
              <button id="ga-add" class="btn btn-success" type="button">Añadir regla</button>
            </div>
            <div class="table-responsive mt-2">
              <table class="table table-sm align-middle">
                <thead><tr><th>#</th><th>Tipo</th><th>Detalle</th><th class="text-end" style="width:120px;">Acción</th></tr></thead>
                <tbody id="ga-tbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
