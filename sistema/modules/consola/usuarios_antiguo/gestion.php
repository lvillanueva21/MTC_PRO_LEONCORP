<!-- modules/consola/usuarios/gestion.php -->
<style>
  .usr-wrap { display:flex; flex-direction:column; gap:16px; }
  .usr-card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .usr-title { font-weight:600; margin-bottom:8px; }
  .help { font-size:12px; color:#6b7280; }

  /* Altura de inputs igual al estilo usado antes */
  .usr-card .form-control, .usr-card .form-select { min-height:38px; }

  /* Tabla compacta */
  .table td, .table th { vertical-align: middle; }

  /* Botones en fila (y se apilan en pantallas pequeñas) */
  .actions-inline{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-start; }
  .actions-inline .btn{ flex:0 0 auto; white-space:nowrap; width:auto!important; }

  /* Filtros alineados como en “Crear servicio” */
  #u-filtros .form-label{ margin-bottom:.25rem; }
</style>

<div class="usr-wrap">

  <!-- 1) DIV SUPERIOR: Crear / Editar usuario -->
  <div class="usr-card">
    <div class="usr-title">1. Crear / editar usuario</div>

    <form id="u-form" class="row g-2 align-items-start" autocomplete="off">
      <!-- Usuario -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1">Usuario (DNI/CE)</label>
        <input type="text" class="form-control" id="u-usuario" name="usuario" maxlength="11" placeholder="DNI/CE (solo dígitos)" required>
      </div>

      <!-- Contraseña -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1">Contraseña</label>
        <input type="password" class="form-control" id="u-clave" name="clave" minlength="6" placeholder="••••••">
        <div class="help mt-1">En edición: deja la contraseña vacía para no cambiarla.</div>
      </div>

      <!-- Nombres -->
      <div class="col-12 col-lg-3 d-flex flex-column">
        <label class="form-label mb-1">Nombres</label>
        <input type="text" class="form-control" id="u-nombres" name="nombres" required>
      </div>

      <!-- Apellidos -->
      <div class="col-12 col-lg-3 d-flex flex-column">
        <label class="form-label mb-1">Apellidos</label>
        <input type="text" class="form-control" id="u-apellidos" name="apellidos" required>
      </div>

      <!-- Empresa -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1">Empresa</label>
        <select id="u-empresa" name="id_empresa" class="form-select" required>
          <option value="">Cargando…</option>
        </select>
      </div>

      <!-- Rol único (opcional) -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1">Rol</label>
        <select id="u-rol" name="id_rol" class="form-select">
          <option value="0">— Ninguno —</option>
        </select>
      </div>

      <!-- Botón -->
      <div class="col-12 col-lg-2 d-flex flex-column">
        <label class="form-label mb-1 invisible">Acción</label>
        <button class="btn btn-primary w-100" type="button" id="u-guardar">Crear</button>
      </div>
    </form>

    <div id="u-alert" class="alert alert-danger d-none mt-2"></div>

    <!-- Éxito con ✕ -->
    <div id="u-ok" class="alert alert-success alert-dismissible d-none mt-2" role="alert">
      <button type="button" class="close u-ok-close" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
      <span class="msg"></span>
    </div>
  </div>

  <!-- 2) Zona inferior: izquierda/derecha — izquierda en blanco por ahora -->
  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="usr-card">
        <div class="usr-title">2. Panel izquierdo (pendiente)</div>
        <div class="text-muted">Próximamente…</div>
      </div>
    </div>

    <!-- 3) Derecha: Listar usuarios -->
    <div class="col-12 col-lg-6">
      <div class="usr-card">
        <div class="usr-title">3. Listar usuarios</div>

        <!-- Filtros -->
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
