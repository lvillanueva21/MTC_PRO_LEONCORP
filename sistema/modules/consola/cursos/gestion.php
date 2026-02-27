<!-- modules/consola/cursos/gestion.php -->
<style>
  .cur-wrap{display:flex;flex-direction:column;gap:16px}
  .cur-card{border:1px solid #e5e7eb;border-radius:8px;padding:12px}
  .cur-title{font-weight:600;margin-bottom:8px}
  .help{font-size:12px;color:#6b7280}

  /* Chips */
  .chiparea.form-control{min-height:38px;padding:6px 8px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;overflow-x:hidden;overflow-y:auto}
  .chip{display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:999px;background:#eef2ff;font-size:12px}
  .chip .x{border:0;background:transparent;cursor:pointer;line-height:1;font-weight:700}
  .chip-input{flex:1;min-width:120px;outline:none;border:0;background:transparent}
  .chip.soft{background:#e5e7eb}

  /* Estado */
  .status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:middle;margin-left:8px}
  .status-on{background:#16a34a}
  .status-off{background:#dc2626}

  /* Acciones */
  .actions-cell{white-space:nowrap}
  .actions-inline{display:flex;gap:8px;justify-content:flex-start;align-items:center;flex-wrap:nowrap}
  .actions-inline .btn{display:inline-flex;width:auto!important;flex:0 0 auto;white-space:nowrap;padding-inline:.75rem}

  @media (max-width: 992px){
    .actions-cell{white-space:normal}
    .actions-inline{flex-wrap:wrap;justify-content:center}
  }

  /* Nombre a izq + dot a der */
  .name-cell{display:flex;align-items:center;justify-content:space-between;gap:8px;min-width:0}
  .name-cell .name-text{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

  /* Panel izquierdo (curso actual, temas) */
  .e-srv-marquee{overflow:hidden;white-space:nowrap}
  .e-srv-text{display:inline-block;padding-right:2rem}
  .e-srv-text.animate{animation:srvmarquee 12s linear infinite}
  @keyframes srvmarquee{0%{transform:translateX(0)}100%{transform:translateX(-100%)}}
  @media (prefers-reduced-motion:reduce){.e-srv-text.animate{animation:none}}

  /* Altura inputs */
  #cur-filtros .form-control,#cur-filtros .form-select{min-height:38px}
</style>

<div class="cur-wrap">
  <!-- 1) Crear/editar curso -->
  <div class="cur-card">
    <div class="cur-title">1. Crear curso</div>

    <form id="cur-create" class="row g-2" autocomplete="off" enctype="multipart/form-data">
      <div class="col-12 col-lg-3 d-flex flex-column">
        <label class="form-label mb-1">Nombre</label>
        <input type="text" class="form-control" id="c-nombre" name="nombre" required maxlength="150">
      </div>
      <div class="col-12 col-lg-3 d-flex flex-column">
        <label class="form-label mb-1">Descripción</label>
        <textarea class="form-control" id="c-desc" name="descripcion" rows="1" placeholder="Ingresa descripción ..."></textarea>
      </div>
      <div class="col-12 col-lg-3 d-flex flex-column">
        <label class="form-label mb-1">Etiquetas (separa con coma)</label>
        <div id="c-tags" class="chiparea form-control">
          <input id="c-tags-input" class="chip-input" type="text" placeholder="moto, auto, taller">
        </div>
        <input type="hidden" id="c-etiquetas" name="etiquetas">
        <div class="help mt-1">Escribe y separa con coma (,) o Enter. Quita con ✕.</div>
      </div>
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1">Imagen</label>
        <input class="form-control" type="file" id="c-imagen" name="imagen" accept="image/*">
        <div class="help mt-1">PNG/JPG/WebP (≤5MB) — se reemplaza si subes otra.</div>
      </div>
      <div class="col-12 col-lg-1 d-flex flex-column">
        <label class="form-label mb-1 invisible">Acción</label>
        <button class="btn btn-primary w-100" type="button" id="c-crear">Crear</button>
      </div>
    </form>

    <div id="c-alert" class="alert alert-danger d-none mt-2"></div>
    <div id="c-ok" class="alert alert-success alert-dismissible d-none mt-2" role="alert">
      <button type="button" class="close c-ok-close" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
      <span class="msg"></span>
    </div>
  </div>

  <div class="row g-3">
    <!-- 2) Añadir contenido a curso -->
    <div class="col-12 col-lg-6">
      <div class="cur-card h-100">
        <div class="cur-title">2. Añadir contenido a curso</div>

        <div id="t-hint" class="alert alert-info py-2 mb-2">
          Presiona el botón <strong>Contenido</strong> de un curso para gestionarlo aquí.
        </div>

        <div id="t-panel" class="d-none">
          <div class="row g-2">
            <div class="col-12 col-lg-6 d-flex flex-column">
              <label class="form-label mb-1">Curso actual</label>
              <div class="form-control e-srv-marquee bg-light">
                <span id="t-curso-actual" class="e-srv-text"></span>
              </div>
            </div>
            <div class="col-12 col-lg-6 d-flex flex-column">
              <label class="form-label mb-1">Temas</label>
              <select id="t-select" class="form-select">
                <option value="0" selected>— Nuevo tema —</option>
              </select>
              <div id="t-empty" class="text-danger small mt-1 d-none">Aún no hay temas guardados</div>
            </div>
          </div>

          <form id="t-form" class="row g-2 mt-2" autocomplete="off" enctype="multipart/form-data">
            <input type="hidden" id="t-id" name="id" value="0">
            <div class="col-12">
              <label class="form-label mb-1">Título</label>
              <input type="text" class="form-control" id="t-titulo" name="titulo" maxlength="200" required>
            </div>
            <div class="col-12">
              <label class="form-label mb-1">Clase</label>
              <textarea class="form-control" id="t-clase" name="clase" rows="6" placeholder="Texto largo (se permiten párrafos, pegado desde editor)…"></textarea>
            </div>
            <div class="col-12 col-lg-8">
              <label class="form-label mb-1">Video (URL YouTube)</label>
              <input type="url" class="form-control" id="t-video" name="video_url" maxlength="300" placeholder="https://www.youtube.com/watch?v=...">
            </div>
            <div class="col-12 col-lg-4">
              <label class="form-label mb-1">Miniatura</label>
              <input class="form-control" type="file" id="t-miniatura" name="miniatura" accept="image/*">
              <div class="help mt-1">PNG/JPG/WebP (≤5MB) — se reemplaza si subes otra.</div>
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
              <button class="btn btn-primary" type="button" id="t-guardar">Crear tema</button>
              <button class="btn btn-danger d-none" type="button" id="t-eliminar">Eliminar tema</button>
            </div>
          </form>

          <div id="t-alert" class="alert alert-danger d-none mt-2"></div>
        </div>
      </div>
    </div>

    <!-- 3) Listar cursos -->
    <div class="col-12 col-lg-6">
      <div class="cur-card h-100">
        <div class="cur-title">3. Listar cursos</div>

        <form id="cur-filtros" class="row g-2 align-items-start">
          <div class="col-12 col-md-6 d-flex flex-column">
            <label class="form-label mb-1">Texto</label>
            <input type="text" id="f-q" class="form-control" placeholder="Buscar por nombre o descripción …">
          </div>
          <div class="col-12 col-md-6 d-flex flex-column">
            <label class="form-label mb-1">Estado</label>
            <select id="f-estado" class="form-select">
              <option value="">Todos</option>
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </form>

        <div id="l-alert" class="alert alert-danger d-none mt-2"></div>
        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle" id="cur-lista">
            <thead>
              <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="l-tbody"></tbody>
          </table>
        </div>
        <nav><ul class="pagination pagination-sm mt-2" id="l-pager"></ul></nav>
      </div>
    </div>
  </div>
</div>
