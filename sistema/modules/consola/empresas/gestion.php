<!-- modules/consola/empresas/gestion.php -->
<style>
  .emp-wrap{display:flex;flex-direction:column;gap:16px}
  .emp-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media (max-width: 1200px){.emp-grid{grid-template-columns:1fr}}
  .emp-card{border:1px solid #e5e7eb;border-radius:10px;padding:12px}
  .emp-title{font-weight:600;margin-bottom:8px}
  .help{font-size:12px;color:#6b7280}
  .table td,.table th{vertical-align:middle}
  .stack .btn{display:block;width:100%;margin-bottom:6px}
  .small-label{font-size:.85rem;margin-bottom:.25rem}
  .actions-inline{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-start}
  .actions-inline .btn{display:inline-flex;flex:0 0 auto}
    /* Preview circular para logo de empresa */
  .emp-photo-box{display:flex;flex-direction:column;align-items:center;text-align:center}
  .emp-logo-prev{
    width:96px;height:96px;border-radius:50%;
    background-size:cover;background-position:center;
    border:2px solid #e5e7eb;flex:0 0 auto;margin:0 auto;
  }
  .emp-file{max-width:260px;margin-top:.5rem}
  .emp-photo-caption{font-size:12px;color:#6b7280}
  #rep-filtros .form-control,#emp-filtros .form-control,
  #rep-filtros .form-select,#emp-filtros .form-select{min-height:38px}
</style>

<div class="emp-wrap" id="emp-root" data-default-logo="../../../dist/img/user2-160x160.jpg">
  <!-- Alertas globales reutilizables -->
  <div id="emp-ok" class="alert alert-success alert-dismissible d-none" role="alert">
    <button type="button" class="close emp-ok-close" aria-label="Cerrar">
      <span aria-hidden="true">&times;</span>
    </button>
    <span class="msg"></span>
  </div>
  <div id="emp-err" class="alert alert-danger d-none"></div>

  <div class="emp-grid">
  <!-- ========== Columna Izquierda: Rep. Legal + Nueva empresa ========== -->
  <div>
    <!-- 1. Nuevo representante legal -->
    <div class="emp-card mb-3">
      <div class="emp-title" id="rep-form-title">1. Nuevo representante legal</div>
      <form id="rep-form" class="row g-2" autocomplete="off">
        <input type="hidden" name="id" value="">
        <div class="col-12 col-lg-6">
          <label class="small-label">Nombres</label>
          <input class="form-control" name="nombres" id="rep-nombres" required maxlength="100">
        </div>
        <div class="col-12 col-lg-6">
          <label class="small-label">Apellidos</label>
          <input class="form-control" name="apellidos" id="rep-apellidos" required maxlength="100">
        </div>
        <div class="col-12 col-lg-6">
          <label class="small-label">DNI (8 dígitos)</label>
          <input class="form-control" name="documento" id="rep-documento" pattern="\d{8}" maxlength="8" required>
        </div>
        <div class="col-12 col-lg-6">
          <label class="small-label">Contraseña (texto plano)</label>
          <input class="form-control" name="clave" id="rep-clave" minlength="1" placeholder="(solo al crear o cambiar)">
          <div class="help mt-1">⚠️ Se guarda como <em>clave_mana</em> (texto plano). Considera migrar a hash más adelante.</div>
        </div>
        <div class="col-12 d-flex gap-2 mt-2">
          <button class="btn btn-primary" type="button" id="rep-guardar">Crear</button>
          <button class="btn btn-secondary d-none" type="button" id="rep-cancelar">Cancelar edición</button>
        </div>
      </form>
    </div>

    <!-- 3. Nueva empresa -->
    <div class="emp-card mb-3">
      <div class="emp-title" id="emp-form-title">3. Nueva empresa</div>

      <form id="emp-form" class="row g-3 align-items-start" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="id" value="">

        <!-- Columna izquierda: Logo -->
        <div class="col-12 col-lg-5 d-flex flex-column">
          <label class="small-label mb-1">Logo</label>
          <div class="emp-photo-box">
            <div id="emp-logo-prev" class="emp-logo-prev"
                 style="background-image:url('../../../dist/img/user2-160x160.jpg');"></div>

            <input class="form-control emp-file" type="file"
                   name="logo" id="emp-logo"
                   accept="image/jpeg,image/png,image/webp">

            <div class="emp-photo-caption mt-1">
              <span id="emp-logo-cap">Sin logo por el momento</span>
            </div>
            <div class="alert alert-success p-2 py-1 mt-2 d-none" id="emp-logo-size"></div>

            <div class="help mt-1">
              PNG/JPG/WEBP — Máx 5MB. Se guardará en
              <code>almacen/AAAA/MM/DD/img_logos_empresas</code>.
            </div>
          </div>
        </div>

        <!-- Columna derecha: Campos -->
        <div class="col-12 col-lg-7">
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="small-label">Nombre comercial</label>
              <input class="form-control" name="nombre" id="emp-nombre" required maxlength="150">
            </div>
            <div class="col-12 col-md-6">
              <label class="small-label">Razón social</label>
              <input class="form-control" name="razon_social" id="emp-razon" required maxlength="300">
            </div>

            <div class="col-12 col-md-6">
              <label class="small-label">RUC (11 dígitos)</label>
              <input class="form-control" name="ruc" id="emp-ruc" pattern="\d{11}" maxlength="11" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="small-label">Dirección</label>
              <input class="form-control" name="direccion" id="emp-direccion" required maxlength="300">
            </div>

            <div class="col-12 col-md-6">
              <label class="small-label">Departamento</label>
              <select class="form-control" name="id_depa" id="emp-depa" required>
                <option value="">Cargando…</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="small-label">Tipo</label>
              <select class="form-control" name="id_tipo" id="emp-tipo" required>
                <option value="">Cargando…</option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="small-label">Representante legal</label>
              <select class="form-control" name="id_repleg" id="emp-repleg" required>
                <option value="">Cargando…</option>
              </select>
            </div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary" type="button" id="emp-guardar">Crear</button>
            <button class="btn btn-secondary d-none" type="button" id="emp-cancelar">Cancelar edición</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- ========== Columna Derecha: Listados (Rep. y Empresas) ========== -->
  <div>
    <!-- 2. Listado de representantes -->
    <div class="emp-card mb-3">
      <div class="emp-title">2. Listado de representantes</div>
      <form id="rep-filtros" class="row g-2">
        <div class="col-12 col-md-6">
          <label class="small-label">Buscar</label>
          <input id="rep-q" class="form-control" placeholder="Nombre/DNI…">
        </div>
      </form>

      <div id="rep-alert" class="alert alert-danger d-none mt-2"></div>
      <div class="table-responsive mt-2">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="width:70px">ID</th>
              <th>Nombre</th>
              <th>DNI</th>
              <th style="width:200px">Acciones</th>
            </tr>
          </thead>
          <tbody id="rep-tbody"></tbody>
        </table>
      </div>
      <nav><ul class="pagination pagination-sm mt-2" id="rep-pager"></ul></nav>
    </div>

    <!-- 4. Listado de empresas -->
    <div class="emp-card">
      <div class="emp-title">4. Listado de empresas</div>
      <form id="emp-filtros" class="row g-2">
        <div class="col-12 col-md-6">
          <label class="small-label">Buscar</label>
          <input id="emp-q" class="form-control" placeholder="Empresa/RUC/Tipo/Depa/Representante…">
        </div>
      </form>

      <div id="emp-alert" class="alert alert-danger d-none mt-2"></div>
      <div class="table-responsive mt-2">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="width:70px">ID</th>
              <th>Empresa</th>
              <th>RUC</th>
              <th>Tipo / Depa</th>
              <th>Rep. Legal</th>
              <th style="width:220px">Acciones</th>
            </tr>
          </thead>
          <tbody id="emp-tbody"></tbody>
        </table>
      </div>
      <nav><ul class="pagination pagination-sm mt-2" id="emp-pager"></ul></nav>
    </div>
  </div>
</div>
</div>
