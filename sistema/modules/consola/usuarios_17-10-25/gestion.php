<!-- modules/consola/usuarios/gestion.php -->
<style>
  .usr-wrap { display:flex; flex-direction:column; gap:16px; }
  .usr-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .usr-title { font-weight:600; margin-bottom:8px; }
  .help { font-size:12px; color:#6b7280; }
  .usr-card .form-control, .usr-card .form-select { min-height:38px; }
  .table td, .table th { vertical-align: middle; }
  .actions-inline{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-start; }
  .actions-inline .btn{ flex:0 0 auto; white-space:nowrap; width:auto!important; }
  #u-filtros .form-label{ margin-bottom:.25rem; }

  /* Preview circular fijo */
  #u-foto-prev{
    width:96px;height:96px;border-radius:50%;
    background-size:cover;background-position:center;
    border:2px solid #e5e7eb;flex:0 0 auto;
  }

  /* Layout de la sección 1 (divisor) */
  .u-right-col{ position:relative; }
  @media (min-width: 992px){
    .u-right-col{ border-left:1px dashed #d1d5db; padding-left:16px; }
  }
  @media (max-width: 991.98px){
    .u-right-col{ border-top:1px dashed #d1d5db; margin-top:12px; padding-top:12px; }
  }

  /* Caja de foto centrada y contenido en columna */
  .u-photo-box{
    display:flex; flex-direction:column; align-items:center; text-align:center;
  }
  .u-file{ max-width:260px; margin-top:.5rem; }
  .u-photo-caption{ font-size:12px; color:#6b7280; }
  .u-photo-size{ font-size:12px; color:#065f46; } /* verde suave */

  /* Espaciado entre filas de campos de la derecha */
  .u-form-grid .row + .row{ margin-top:.5rem; }

  /* Más espacio antes de la fila de acciones (botón) */
  .u-form-actions{ margin-top:.75rem; }
  @media (min-width: 992px){
    .u-form-actions{ margin-top:1.25rem; }
  }

  /* Botón ojo dentro de input-group */
  .u-eye-btn i{ pointer-events:none; }
</style>

<div class="usr-wrap"
     data-default-avatar="/dist/img/user2-160x160.jpg"
     data-cursos-api="/modules/consola/cursos/api.php">
  <div class="usr-card">
    <div class="usr-title">1. Crear / editar usuario</div>

    <form id="u-form" class="row g-3 align-items-start" autocomplete="off" enctype="multipart/form-data">

      <!-- Columna izquierda: foto (centrada) -->
      <div class="col-12 col-lg-4 d-flex flex-column">
        <label class="form-label mb-1">Foto de perfil (opcional)</label>
        <div class="u-photo-box">
          <div id="u-foto-prev" style="background-image:url('/dist/img/user2-160x160.jpg');"></div>
          <!-- Input de archivo debajo de la foto -->
          <input type="file" class="form-control u-file" id="u-foto" name="foto"
                accept="image/jpeg,image/png,image/webp">

          <!-- Nombre de archivo y peso centrados -->
          <div class="u-photo-caption mt-1">
            <span id="u-foto-cap">Sin foto por el momento</span>
          </div>
          <div class="u-photo-size mt-1" id="u-foto-size"></div>

          <!-- Límite de tamaño debajo del input -->
          <div class="help mt-1">JPG/PNG/WEBP — Máx 4MB.</div>
        </div>
      </div>

      <!-- Columna derecha: campos -->
      <div class="col-12 col-lg-8 u-right-col">
        <div class="u-form-grid">
          <div class="row">
            <div class="col-12 col-lg-4 d-flex flex-column">
              <label class="form-label mb-1">Usuario (DNI/CE)</label>
              <input type="text" class="form-control" id="u-usuario" name="usuario" maxlength="11" placeholder="DNI/CE (solo dígitos)" required>
            </div>

            <div class="col-12 col-lg-4 d-flex flex-column">
              <label class="form-label mb-1">Contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control" id="u-clave" name="clave" minlength="6" placeholder="••••••" autocomplete="current-password">
                <button type="button" class="btn btn-outline-secondary u-eye-btn" id="u-clave-toggle" aria-label="Mostrar u ocultar contraseña">
                  <i class="far fa-eye"></i>
                </button>
              </div>
              <div class="help mt-1">En edición: deja la contraseña vacía para no cambiarla.</div>
            </div>

            <div class="col-12 col-lg-4 d-flex flex-column">
              <label class="form-label mb-1">Rol</label>
              <select id="u-rol" name="id_rol" class="form-select">
                <option value="0">— Ninguno —</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-12 col-lg-4 d-flex flex-column">
              <label class="form-label mb-1">Nombres</label>
              <input type="text" class="form-control" id="u-nombres" name="nombres" required>
            </div>
            <div class="col-12 col-lg-4 d-flex flex-column">
              <label class="form-label mb-1">Apellidos</label>
              <input type="text" class="form-control" id="u-apellidos" name="apellidos" required>
            </div>
            <div class="col-12 col-lg-4 d-flex flex-column">
              <label class="form-label mb-1">Empresa</label>
              <select id="u-empresa" name="id_empresa" class="form-select" required>
                <option value="">Cargando…</option>
              </select>
            </div>
          </div>

          <div class="row u-form-actions">
            <div class="col-12 d-flex justify-content-center">
              <button class="btn btn-primary px-4" type="button" id="u-guardar">Crear</button>
            </div>
          </div>
        </div>
      </div>
    </form>

    <div id="u-alert" class="alert alert-danger d-none mt-2"></div>

    <div id="u-ok" class="alert alert-success alert-dismissible d-none mt-2" role="alert">
      <button type="button" class="close u-ok-close" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      <span class="msg"></span>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <!-- 2) Usuarios y sus cursos -->
      <div class="usr-card">
        <div class="usr-title">2. Usuarios y sus cursos</div>

        <form id="uc-filtros" class="row g-2 align-items-start">
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Empresa</label>
            <select id="uc-f-empresa" class="form-select">
              <option value="0">Todas</option>
            </select>
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Texto</label>
            <input type="text" id="uc-f-q" class="form-control" placeholder="Buscar por usuario o nombre…">
          </div>
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Curso</label>
            <select id="uc-f-curso" class="form-select">
              <option value="0">Todos</option>
            </select>
          </div>
        </form>

        <div id="uc-alert" class="alert alert-danger d-none mt-2"></div>

        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:60px;">ID</th>
                <th>Nombre</th>
                <th>Empresa</th>
                <th style="width:100px;">Cursos</th>
              </tr>
            </thead>
            <tbody id="uc-tbody"></tbody>
          </table>
        </div>
        <nav><ul class="pagination pagination-sm mt-2" id="uc-pager"></ul></nav>

        <div id="uc-empty" class="text-muted mt-2 d-none">No hay usuarios con cursos según los filtros.</div>
      </div>

      <!-- 2.b) Panel de cursos del usuario seleccionado desde "3. Listar usuarios" -->
      <div class="usr-card">
        <div class="usr-title">
          Panel de cursos <span id="uc-user-label" class="text-muted"></span>
        </div>

        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <div class="border rounded p-2 h-100">
              <div class="fw-semibold mb-2">Cursos disponibles</div>
              <div id="uc-disp-list" class="list-group list-group-flush"></div>
              <nav><ul class="pagination pagination-sm mt-2" id="uc-disp-pager"></ul></nav>
              <div id="uc-disp-empty" class="text-muted small d-none">No hay cursos activos disponibles.</div>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="border rounded p-2 h-100">
              <div class="fw-semibold mb-2">Cursos del <span id="uc-user-mini"></span></div>
              <div id="uc-asig-list" class="list-group list-group-flush"></div>
              <div id="uc-asig-empty" class="text-muted small d-none">Este usuario todavía no tiene cursos.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3) Listar usuarios -->
    <div class="col-12 col-lg-6">
      <div class="usr-card">
        <div class="usr-title">3. Listar usuarios</div>

        <form id="u-filtros" class="row g-2 align-items-start">
          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Empresa</label>
            <select id="f-empresa" class="form-select">
              <option value="0">Todas</option>
            </select>
          </div>

          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Texto</label>
            <input type="text" id="f-q" class="form-control" placeholder="buscar por usuario o nombre…">
          </div>

          <div class="col-12 col-md-4 d-flex flex-column">
            <label class="form-label mb-1">Rol</label>
            <select id="f-rol" class="form-select">
              <option value="0">Todos</option>
            </select>
          </div>
        </form>

        <div id="l-alert" class="alert alert-danger d-none mt-2"></div>

        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle" id="u-lista">
            <thead>
              <tr>
                <th style="width:60px;">ID</th>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Empresa</th>
                <th>Rol</th>
                <th style="width:220px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="u-tbody"></tbody>
          </table>
        </div>

        <nav>
          <ul class="pagination pagination-sm mt-2" id="u-pager"></ul>
        </nav>
      </div>
    </div>
  </div>
</div>
