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
            <div class="help mt-1">PNG/JPG/WebP — Máx 5MB. Se guardará en <code>almacen/AAAA/MM/DD/fondo_certificado</code>.</div>
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

     <!-- ====== DIV IZQUIERDO: Listado de plantillas ====== -->
  <div class="pc-card">
    <div class="pc-title">2. Plantillas creadas</div>

    <form id="pl-filtros" class="row g-2">
      <div class="col-12 col-md-8 d-flex flex-column">
        <label class="form-label mb-1">Buscar</label>
        <input id="pl-q" class="form-control" placeholder="Nombre, empresa o resolución…">
      </div>
      <div class="col-12 col-md-4 d-flex align-items-end">
        <div class="help">Se muestran 5 por página</div>
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
            <th style="width:120px;">Acción</th>
          </tr>
        </thead>
        <tbody id="pl-tbody"></tbody>
      </table>
    </div>
    <nav><ul class="pagination pagination-sm mt-2" id="pl-pager"></ul></nav>

    <div id="pl-empty" class="text-muted d-none">No hay resultados.</div>
  </div>

  <!-- ====== DIV DERECHO (placeholder) ====== -->
  <div class="pc-card">
    <div class="pc-title">3. (Pendiente)</div>
    <div class="text-muted">Este panel se implementará más adelante.</div>
  </div>
</div>
