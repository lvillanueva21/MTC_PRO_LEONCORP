<!-- modules/consola/servicios/gestion.php -->
<style>
  .srv-wrap { display:flex; flex-direction:column; gap:16px; }
  .srv-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .srv-title { font-weight:600; margin-bottom:8px; }
  .help { font-size:12px; color:#6b7280; }

  /* Chips con apariencia de textarea */
  .chiparea.form-control{
    min-height:38px; padding:6px 8px;
    display:flex; flex-wrap:wrap; gap:6px; align-items:center;
    overflow-x:hidden; overflow-y:auto;
  }
  .chip{display:inline-flex; align-items:center; gap:6px;
        padding:3px 8px; border-radius:999px; background:#eef2ff; font-size:12px;}
  .chip .x{border:0; background:transparent; cursor:pointer; line-height:1; font-weight:700;}
  .chip-input{flex:1; min-width:120px; outline:none; border:0; background:transparent;}

  /* Alturas mínimas para alinear arriba */
  #s-desc.form-control{ min-height:38px; }
    .status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:middle;margin-left:8px;}
  .status-on{background:#16a34a;}   /* verde */
  .status-off{background:#dc2626;}  /* rojo */
  #srv-filtros .form-control,
#srv-filtros .form-select { min-height: 38px; }

  .status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:middle;margin-left:8px;}
  .status-on{background:#16a34a;}   /* verde */
  .status-off{background:#dc2626;}  /* rojo */

  /* fila de detalle */
  .srv-detail td { background:#f9fafb; }
  .srv-detail .chips { display:flex; gap:6px; flex-wrap:wrap; }
  .chip.soft { background:#e5e7eb; } /* chip de lectura (sin X) */
/* Acciones en una fila; cada botón se ajusta al texto */
.actions-cell{ white-space: nowrap; } /* evita saltos cuando hay espacio */

.actions-inline{
  display:flex;
  gap:8px;
  justify-content:flex-start;
  align-items:center;
  flex-wrap:nowrap;                 /* no envuelve en desktop */
}

.actions-inline .btn{
  display:inline-flex;              /* evita display:block heredados */
  width:auto !important;            /* ignora w-100/btn-block si existe */
  flex:0 0 auto;                    /* no crece ni se estira */
  min-width:unset;                  /* sin mínimo: ancho = contenido */
  white-space:nowrap;               /* el texto del botón en una línea */
  padding-inline: .75rem;           /* comodidad visual */
}

@media (max-width: 992px){
  .actions-cell{ white-space: normal; }  /* permite envolver */
  .actions-inline{
    flex-wrap:wrap;                 /* aquí sí pueden apilarse */
    justify-content:center;         /* centrados en tablet/móvil */
  }
  .actions-inline .btn{
    flex:0 0 auto;                  /* siguen ajustándose al texto */
  }
}
/* Nombre a la izq. y dot a la der. */
.name-cell{
  display:flex;
  align-items:center;
  justify-content:space-between;  /* empuja el dot a la derecha */
  gap:8px;
  min-width:0;                    /* permite elipsis del texto */
}
.name-main{
  display:flex;
  align-items:center;
  gap:8px;
  min-width:0;
  flex:1 1 auto;
}
.srv-thumb{
  width:28px;
  height:28px;
  border-radius:6px;
  border:1px solid #e5e7eb;
  object-fit:cover;
  flex:0 0 28px;
  background:#fff;
}
.srv-thumb-empty{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  color:#9ca3af;
  background:#f3f4f6;
  font-size:11px;
}
.name-cell .name-text{
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
/* Badge estado empresa */
.badge-soft{
  display:inline-block;
  padding:.15rem .5rem;
  border-radius:.5rem;
  background:#eef2ff;
  color:#374151;
  font-size:.75rem;
}
.filters-row .form-label{ margin-bottom:.25rem; }
/* --- Igualar altura/espaciado de filtros del panel izquierdo --- */
#emp-panel .form-control,
#emp-panel .form-select { min-height: 38px; }
#emp-panel .form-label { margin-bottom: .25rem; }

/* --- Marquee del “Servicio actual” --- */
.e-srv-marquee { overflow: hidden; white-space: nowrap; }
.e-srv-text { display:inline-block; padding-right: 2rem; }     /* espacio al final */
.e-srv-text.animate { animation: srvmarquee 12s linear infinite; }

@keyframes srvmarquee {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-100%); }
}

/* Respeto a accesibilidad: si el usuario pide menos animación, no animar */
@media (prefers-reduced-motion: reduce) {
  .e-srv-text.animate { animation: none; }
}

/* Preview de imagen en crear/editar */
.srv-preview-grid{
  display:grid;
  grid-template-columns:repeat(2, minmax(0, 1fr));
  gap:8px;
  margin-top:8px;
}
.srv-preview-card{
  border:1px dashed #d1d5db;
  border-radius:8px;
  padding:8px;
  background:#f8fafc;
}
.srv-preview-title{
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:.03em;
  color:#6b7280;
  margin-bottom:6px;
}
.srv-preview-frame{
  position:relative;
  width:100%;
  padding-top:56%;
  border-radius:6px;
  overflow:hidden;
  background:#e5e7eb;
}
.srv-preview-frame img{
  position:absolute;
  inset:0;
  width:100%;
  height:100%;
  object-fit:cover;
  display:none;
}
.srv-preview-empty{
  position:absolute;
  inset:0;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  font-size:12px;
  color:#6b7280;
  padding:6px;
}
.srv-preview-meta{
  font-size:11px;
  color:#6b7280;
  margin-top:6px;
  min-height:16px;
  word-break:break-word;
}

.srv-upload-wrap{
  margin-top:8px;
  border:1px solid #e5e7eb;
  border-radius:8px;
  padding:8px;
  background:#f8fafc;
}
.srv-upload-head{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:6px;
  font-size:12px;
}
.srv-upload-pct{
  font-weight:600;
}
.srv-upload-note{
  font-size:12px;
  color:#6b7280;
  margin-top:6px;
}

@media (max-width: 767.98px){
  .srv-preview-grid{
    grid-template-columns:1fr;
  }
}
</style>

<div class="srv-wrap">
  <!-- 1) DIV SUPERIOR: Crear servicio -->
  <div class="srv-card">
    <div class="srv-title">1. Crear servicio</div>
    <form id="srv-create" class="row g-2 align-items-start" autocomplete="off" enctype="multipart/form-data">
      <!-- Nombre -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1">Nombre</label>
        <input type="text" class="form-control" id="s-nombre" name="nombre" required maxlength="150">
      </div>
      <!-- Descripción -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1">Descripción</label>
        <textarea class="form-control" id="s-desc" name="descripcion" rows="1" placeholder="Ingresa descripción ..."></textarea>
      </div>
      <!-- Etiquetas (chips con input) -->
      <div class="col-12 col-lg-3 d-flex flex-column">
        <label class="form-label mb-1">Etiquetas (separa con coma)</label>
        <div id="s-tags" class="chiparea form-control">
          <input id="s-tags-input" class="chip-input" type="text" placeholder="moto, medico, curso">
        </div>
        <input type="hidden" id="s-etiquetas" name="etiquetas">
        <div class="help mt-1">Escribe y separa con coma (,) o Enter. Quita con ✕.</div>
      </div>
      <!-- Imagen -->
      <div class="col-12 col-lg-3 d-flex flex-column">
        <label class="form-label mb-1">Imagen</label>
        <input class="form-control" type="file" id="s-imagen" name="imagen" accept="image/*">
        <input type="hidden" id="s-imagen-actual" value="">
        <div class="srv-preview-grid">
          <div class="srv-preview-card">
            <div class="srv-preview-title">Imagen actual</div>
            <div class="srv-preview-frame">
              <img id="s-prev-current-img" alt="Imagen actual del servicio">
              <div id="s-prev-current-empty" class="srv-preview-empty">Sin imagen actual</div>
            </div>
            <div id="s-prev-current-meta" class="srv-preview-meta"></div>
          </div>
          <div class="srv-preview-card">
            <div class="srv-preview-title">Nueva imagen</div>
            <div class="srv-preview-frame">
              <img id="s-prev-new-img" alt="Vista previa de nueva imagen">
              <div id="s-prev-new-empty" class="srv-preview-empty">Aún no seleccionada</div>
            </div>
            <div id="s-prev-new-meta" class="srv-preview-meta">Selecciona un archivo para previsualizar.</div>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-2 d-none" id="s-clear-image">Quitar selección</button>
          </div>
        </div>
        <div id="s-upload-wrap" class="srv-upload-wrap d-none" aria-live="polite">
          <div class="srv-upload-head">
            <span id="s-upload-label">Subiendo imagen...</span>
            <span id="s-upload-pct" class="srv-upload-pct">0%</span>
          </div>
          <div class="progress" style="height:8px;">
            <div id="s-upload-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%;" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
          </div>
          <div id="s-upload-note" class="srv-upload-note">No cierres esta pantalla mientras se sube el archivo.</div>
        </div>
        <div class="help mt-1">PNG/JPG/WebP/GIF/BMP/AVIF (≤5MB)</div>
      </div>
      <!-- Botón -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1 invisible">Acción</label>
        <button class="btn btn-primary w-100" type="button" id="s-crear">Crear</button>
      </div>
    </form>
    <div id="s-alert" class="alert alert-danger alert-dismissible d-none mt-2" role="alert">
      <button type="button" class="close srv-alert-close" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
      <span class="msg"></span>
    </div>
    <!-- Éxito con ✕ -->
    <div id="s-ok" class="alert alert-success alert-dismissible d-none mt-2" role="alert">
      <button type="button" class="close s-ok-close srv-alert-close" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
      <span class="msg"></span>
    </div>
  </div>
<!-- Zona inferior: izquierda/derecha -->
<div class="row g-3">
  <!-- 2) IZQUIERDO: Empresas del servicio -->
  <div class="col-12 col-lg-6">
    <div class="srv-card h-100">
      <div class="srv-title">2. Empresas del servicio</div>
      <!-- Sugerencia inicial -->
      <div id="emp-hint" class="alert alert-info py-2 mb-2">
        Selecciona una empresa en la tabla <strong>Listar servicios</strong>.
      </div>
      <!-- Panel funcional -->
      <div id="emp-panel" class="d-none">
<div class="row g-2 filters-row">
  <div class="col-12 col-lg-3 d-flex flex-column">
    <label class="form-label mb-1">Servicio actual</label>
    <div class="form-control e-srv-marquee bg-light">
      <span id="e-srv-actual" class="e-srv-text"></span>
    </div>
  </div>
  <div class="col-12 col-lg-3 d-flex flex-column">
    <label class="form-label mb-1">Empresa</label>
    <select id="e-empresa" class="form-select">
      <option value="0">Todas</option>
    </select>
  </div>
  <div class="col-12 col-lg-3 d-flex flex-column">
    <label class="form-label mb-1">Buscar empresa</label>
    <input type="text" id="e-q" class="form-control" placeholder="Escribe nombre ...">
  </div>
  <div class="col-12 col-lg-3 d-flex flex-column">
    <label class="form-label mb-1">Estado</label>
    <select id="e-estado" class="form-select">
      <option value="">Todos</option>
      <option value="1">Asignado</option>
      <option value="0">No asignado</option>
    </select>
  </div>
</div>
        <div id="e-alert" class="alert alert-danger alert-dismissible d-none mt-2" role="alert">
          <button type="button" class="close srv-alert-close" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
          <span class="msg"></span>
        </div>
        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle" id="e-table">
            <thead>
              <tr>
                <th style="width:40px;">#</th>
                <th>Empresa</th>
                <th class="text-end" style="width:160px;">Acción</th>
              </tr>
            </thead>
            <tbody id="e-tbody"></tbody>
          </table>
        </div>
        <nav><ul class="pagination pagination-sm mt-2" id="e-pager"></ul></nav>
      </div>
    </div>
  </div>
  <!-- 3) DERECHO: Listar servicios -->
  <div class="col-12 col-lg-6">
    <div class="srv-card h-100">
      <div class="srv-title">3. Listar servicios</div>
      <!-- Filtros -->
      <form id="srv-filtros" class="row g-2 align-items-start">
        <div class="col-12 col-md-4 d-flex flex-column">
          <label class="form-label mb-1">Empresa</label>
          <select id="f-empresa" class="form-select">
            <option value="0">Todas las empresas</option>
          </select>
        </div>
        <div class="col-12 col-md-4 d-flex flex-column">
          <label class="form-label mb-1">Texto</label>
          <input type="text" id="f-q" class="form-control" placeholder="buscar texto ...">
        </div>
        <div class="col-12 col-md-4 d-flex flex-column">
          <label class="form-label mb-1">Estado</label>
          <select id="f-estado" class="form-select">
            <option value="">Todos</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </form>
      <div id="l-alert" class="alert alert-danger alert-dismissible d-none mt-2" role="alert">
        <button type="button" class="close srv-alert-close" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
        <span class="msg"></span>
      </div>
      <div class="table-responsive mt-2">
        <table class="table table-sm align-middle" id="srv-lista">
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
      <nav>
        <ul class="pagination pagination-sm mt-2" id="l-pager"></ul>
      </nav>
    </div>
  </div>
</div>
</div>
