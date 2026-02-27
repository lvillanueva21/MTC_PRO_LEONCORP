<!-- modules/consola/certificados/gestion.php -->
<style>
  .pc-wrap{display:flex;flex-direction:column;gap:16px}
  .pc-card{border:1px solid #e5e7eb;border-radius:10px;padding:12px}
  .pc-title{font-weight:700;margin-bottom:8px}
  .help{font-size:12px;color:#6b7280}
  /* Grilla 3× para las tarjetas de imágenes (responsive 2 y 1) */
  .pc-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
  }
  @media (max-width: 992px){
    .pc-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
  @media (max-width: 576px){
    .pc-grid{ grid-template-columns:1fr; }
  }
    .pc-photo-box{display:flex;flex-direction:column;align-items:center;text-align:center}
  .pc-prev{
    width:100%;height:180px;border-radius:6px;
    background:#f9fafb;
    background-size:contain;       /* ← No recorta */
    background-repeat:no-repeat;   /* ← No repite */
    background-position:center;    /* ← Centrado */
    border:1px solid #e5e7eb
  }
    .pc-file{width:100%;max-width:100%;margin-top:.5rem}
  .pc-caption{font-size:12px;color:#6b7280}
  .pc-size{font-size:12px}
  .stack .btn{display:block;width:100%}
  .pc-form .form-control,.pc-form .form-select{min-height:38px}
    /* === Grid 3× para los campos básicos del formulario === */
  .pc-form-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:12px 16px;
  }
  @media (max-width: 992px){ /* md/lg */
    .pc-form-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
  @media (max-width: 576px){ /* xs */
    .pc-form-grid{ grid-template-columns:1fr; }
  }
    .pc-field .form-label{
    display:block;
    margin-bottom:.25rem;
  }
  /* Uniformidad visual entre select e inputs */
  .pc-form .form-control,
  .pc-form .form-select{
    border:1px solid #e5e7eb;
    display:block;
    width:100%;
  }
    /* ===== Grilla 2 columnas para los dos paneles inferiores ===== */
  .pc-bottom-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
  }
  @media (max-width: 992px){
    .pc-bottom-grid{ grid-template-columns:1fr; }
  }
  /* Filtros del listado: dos columnas de igual ancho (responsive) */
.pl-filters-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:12px 16px;
}
@media (max-width: 576px){
  .pl-filters-grid{ grid-template-columns:1fr; }
}
/* Mismo look & feel para inputs/selects del listado */
#pl-filtros .form-control,
#pl-filtros .form-select{
  min-height:38px;
  border:1px solid #e5e7eb;
}
  /* ===== Vista previa (Div 4) ===== */
  .pv-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;margin-bottom:8px}
  .pv-group{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
  .pv-label{font-weight:600;font-size:14px;margin-right:4px}
  .pv-btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid #e5e7eb;background:#fff;border-radius:6px;padding:4px 8px;line-height:1;cursor:pointer}
  .pv-btn:disabled{opacity:.5;cursor:not-allowed}
  .pv-canvas{position:relative;width:100%;aspect-ratio:297/210;background:#f9fafb center/contain no-repeat;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
  .pv-img{position:absolute;max-width:100%;max-height:100%}
  .pv-loader{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.6);font-weight:600}
  .pv-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px}
  .pv-meta .left{font-size:12px;color:#6b7280}
    /* ===== Panel 3: Elementos de certificado (responsive) ===== */

  /* Ajuste de columnas en escritorio */
  #pe-form .pe-col-ver,
  #pe-form td:first-child {
    width: 40px;
    min-width: 40px;
    text-align: center;
    white-space: nowrap;
  }

  #pe-form .pe-col-el,
  #pe-form td:nth-child(2) {
    width: 30%;
    white-space: nowrap;
  }

  #pe-form .pe-col-desc,
  #pe-form td:nth-child(3) {
    width: 70%;
    white-space: normal;
    word-break: break-word;
  }

  /* Versión móvil: la descripción baja a una segunda línea */
  @media (max-width: 768px) {
    /* Ocultamos la cabecera de descripción */
    #pe-form thead .pe-col-desc {
      display: none;
    }

    /* La celda de descripción pasa a ocupar toda la fila, debajo del nombre */
    #pe-form tbody tr td:nth-child(3) {
      display: block;
      width: 100%;
      padding-top: 0.15rem;
      padding-bottom: 0.35rem;
      padding-left: calc(40px + 0.75rem); /* se alinea debajo de "Elemento" */
      font-size: 0.78rem;
      color: #6b7280; /* igual que .text-muted */
    }

    /* Suavizar el salto visual entre filas */
    #pe-form tbody tr {
      border-bottom: 1px solid #e5e7eb;
    }

    #pe-form tbody tr:last-child {
      border-bottom: none;
    }
  }
</style>

<div class="pc-wrap">
  <!-- ====== DIV SUPERIOR: Creador de plantillas ====== -->
  <div class="pc-card">
    <div class="pc-title">1. Creador de plantillas de certificados</div>

        <form id="pc-form" class="pc-form" autocomplete="off" enctype="multipart/form-data">
            <!-- Campos básicos en grilla 3× -->
      <div class="pc-form-grid">
        <div class="pc-field">
          <label class="form-label mb-1">Nombre: <span class="text-danger">*</span></label>
          <input id="pc-nombre" name="nombre" class="form-control" maxlength="150" required>
        </div>

        <div class="pc-field">
          <label class="form-label mb-1">Páginas: <span class="text-danger">*</span></label>
          <input id="pc-paginas" name="paginas" type="number" min="1" max="255" class="form-control" value="1" required>
        </div>

        <div class="pc-field">
          <label class="form-label mb-1">Representante:</label>
          <input id="pc-representante" name="representante" class="form-control" maxlength="200" placeholder="Nombres y apellidos (opcional)">
        </div>

        <div class="pc-field">
          <label class="form-label mb-1">Empresa: <span class="text-danger">*</span></label>
          <select id="pc-empresa" name="id_empresa" class="form-select" required>
            <option value="">Cargando…</option>
          </select>
        </div>

        <div class="pc-field">
          <label class="form-label mb-1">Ciudad:</label>
          <input id="pc-ciudad" name="ciudad" class="form-control" maxlength="100" placeholder="(opcional)">
        </div>

        <div class="pc-field">
          <label class="form-label mb-1">Resolución:</label>
          <input id="pc-resolucion" name="resolucion" class="form-control" maxlength="200" placeholder="(opcional)">
        </div>
      </div>


      <!-- Bloque de imágenes -->
            <div class="pc-grid">
        <!-- Fondo (obligatorio) -->
        <div class="pc-card">
          <div class="fw-semibold mb-1">Fondo de certificado: <span class="text-danger">*</span></div>
          <div class="pc-photo-box">
            <div id="pc-fondo-prev" class="pc-prev"></div>
            <input id="pc-fondo" name="fondo" type="file" class="form-control pc-file"
                   accept="image/jpeg,image/png,image/webp" required>
            <div class="pc-caption mt-1"><span id="pc-fondo-cap">Sin imagen actualmente.</span></div>
            <div class="alert alert-success p-2 py-1 mt-2 d-none" id="pc-fondo-size"></div>
            <div class="help mt-1">Se guardará en <code>almacen/AAAA/MM/DD/fondo_certificado</code>.</div>
          </div>
        </div>

        <!-- Logo (opcional) -->
        <div class="pc-card">
          <div class="fw-semibold mb-1">Logo de empresa:</div>
          <div class="pc-photo-box">
            <div id="pc-logo-prev" class="pc-prev"></div>
            <input id="pc-logo" name="logo" type="file" class="form-control pc-file"
                   accept="image/jpeg,image/png,image/webp">
            <div class="pc-caption mt-1"><span id="pc-logo-cap">Sin imagen actualmente.</span></div>
            <div class="alert alert-success p-2 py-1 mt-2 d-none" id="pc-logo-size"></div>
            <div class="help mt-1">Se guardará en <code>almacen/AAAA/MM/DD/logo_certificado</code>.</div>
          </div>
        </div>

        <!-- Firma (opcional) -->
        <div class="pc-card">
          <div class="fw-semibold mb-1">Firma de representante:</div>
          <div class="pc-photo-box">
            <div id="pc-firma-prev" class="pc-prev"></div>
            <input id="pc-firma" name="firma" type="file" class="form-control pc-file"
                   accept="image/jpeg,image/png,image/webp">
            <div class="pc-caption mt-1"><span id="pc-firma-cap">Sin imagen actualmente.</span></div>
            <div class="alert alert-success p-2 py-1 mt-2 d-none" id="pc-firma-size"></div>
            <div class="help mt-1">Se guardará en <code>almacen/AAAA/MM/DD/firma_representante</code>.</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1 invisible">Acción</label>
        <div class="d-flex gap-2">
          <button class="btn btn-primary flex-fill" type="button" id="pc-guardar">Guardar plantilla</button>
          <button class="btn btn-secondary flex-fill d-none" type="button" id="pc-cancelar">Cancelar edición</button>
        </div>
      </div>
    </form>

    <div id="pc-alert" class="alert alert-danger d-none mt-2"></div>
    <div id="pc-ok" class="alert alert-success alert-dismissible d-none mt-2" role="alert">
      <button type="button" class="close" aria-label="Cerrar" onclick="this.closest('.alert').classList.add('d-none')">
        <span aria-hidden="true">&times;</span>
      </button>
      <span class="msg"></span>
    </div>
  </div>
  <!-- ====== DOS PANELES INFERIORES EN GRILLA 2× ====== -->
  <div class="pc-bottom-grid">
    <!-- IZQUIERDA: Listado de plantillas -->
    <div class="pc-card">
      <div class="pc-title">2. Plantillas creadas</div>
<form id="pl-filtros" class="pl-filters-grid">
  <div class="d-flex flex-column">
    <label class="form-label mb-1">Buscar</label>
    <input id="pl-q" class="form-control" placeholder="Nombre, empresa o resolución…">
  </div>
  <div class="d-flex flex-column">
    <label class="form-label mb-1">Empresa</label>
    <select id="pl-empresa" class="form-select">
      <option value="">Todas</option>
    </select>
  </div>
</form>
      <div id="pl-alert" class="alert alert-danger d-none mt-2"></div>

      <div class="table-responsive mt-2">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="width:60px;">ID</th>
              <th>Nombre</th>
              <th>Empresa</th>
              <th style="width:80px;">Pág.</th>
              <th style="width:120px;">Creado</th>
              <th style="width:160px;">Acción</th>
            </tr>
          </thead>
          <tbody id="pl-tbody"></tbody>
        </table>
      </div>
      <nav><ul class="pagination pagination-sm mt-2" id="pl-pager"></ul></nav>

      <div id="pl-empty" class="text-muted d-none">No hay resultados.</div>
    </div>

        <!-- DERECHA: Elementos de certificado -->
    <div class="pc-card">
      <div class="pc-title">3. Elementos de certificado</div>

      <div id="pe-empty" class="text-muted">
        Selecciona una plantilla en la lista y haz clic en el botón de elementos (botón verde) para configurar qué datos se mostrarán en el certificado.
      </div>

           <div id="pe-wrap" class="d-none">
        <div class="mb-2 small text-muted">
          Plantilla: <strong id="pe-plantilla">—</strong> · Empresa: <span id="pe-empresa">—</span>
        </div>

        <div id="pe-alert" class="alert alert-danger d-none"></div>
        <div id="pe-ok" class="alert alert-success d-none"><span class="msg"></span></div>

        <form id="pe-form">
          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle mb-2">
              <thead>
                <tr>
                  <th class="pe-col-ver">Ver</th>
                  <th class="pe-col-el">Elemento</th>
                  <th class="pe-col-desc text-muted small">Descripción</th>
                </tr>
              </thead>
              <tbody id="pe-tbody">
                <!-- Se rellena por JS -->
              </tbody>
            </table>
          </div>

          <button type="button" id="pe-guardar" class="btn btn-success btn-sm">
            Guardar elementos
          </button>
        </form>
      </div>

    </div>
    <!-- ====== DIV INFERIOR: Vista previa ====== -->
  <div class="pc-card">
    <div class="pc-title">4. Vista previa</div>

    <div id="pv-empty" class="text-muted">Selecciona un certificado para ver su vista previa en plantillas creadas.</div>

    <div id="pv-wrap" class="d-none">
      <div class="pv-toolbar">
        <div class="pv-group">
          <span class="pv-label">Logo:</span>
          <button type="button" class="pv-btn pv-move" title="Arriba (logo)" data-target="logo" data-dy="-1">↑</button>
          <button type="button" class="pv-btn pv-move" title="Abajo (logo)" data-target="logo" data-dy="1">↓</button>
          <button type="button" class="pv-btn pv-move" title="Izquierda (logo)" data-target="logo" data-dx="-1">←</button>
          <button type="button" class="pv-btn pv-move" title="Derecha (logo)" data-target="logo" data-dx="1">→</button>
          <button type="button" class="pv-btn pv-move" title="Más grande (logo)" data-target="logo" data-dw="2">＋</button>
          <button type="button" class="pv-btn pv-move" title="Más pequeño (logo)" data-target="logo" data-dw="-2">－</button>
        </div>

        <div class="pv-group">
          <span class="pv-label">Firma:</span>
          <button type="button" class="pv-btn pv-move" title="Arriba (firma)" data-target="firma" data-dy="-1">↑</button>
          <button type="button" class="pv-btn pv-move" title="Abajo (firma)" data-target="firma" data-dy="1">↓</button>
          <button type="button" class="pv-btn pv-move" title="Izquierda (firma)" data-target="firma" data-dx="-1">←</button>
          <button type="button" class="pv-btn pv-move" title="Derecha (firma)" data-target="firma" data-dx="1">→</button>
          <button type="button" class="pv-btn pv-move" title="Más grande (firma)" data-target="firma" data-dw="2">＋</button>
          <button type="button" class="pv-btn pv-move" title="Más pequeño (firma)" data-target="firma" data-dw="-2">－</button>
        </div>
      </div>

      <div id="pv-canvas" class="pv-canvas">
        <div id="pv-loader" class="pv-loader d-none">Cargando…</div>
        <img id="pv-logo"  alt="logo"  class="pv-img" style="left:50%;top:15%;width:30%;transform:translate(-50%,-50%);">
        <img id="pv-firma" alt="firma" class="pv-img" style="left:80%;top:80%;width:25%;transform:translate(-50%,-50%);">
      </div>
      <div class="pv-meta">
        <div class="left"><b id="pv-title">—</b> — <span id="pv-empresa">—</span></div>
        <div class="right d-flex gap-2">
          <button type="button" id="pv-save-layout" class="btn btn-sm btn-success">
            Guardar posiciones
          </button>
          <button type="button" id="pv-print" class="btn btn-sm btn-outline-secondary">
            Imprimir PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
