<?php
// Ver 07-03-26
// modules/aula_virtual/aula_virtual_administracion_matriculas.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';

acl_require_ids([1,4]);

$u = currentUser();
$rolActivoId = (int)($u['rol_activo_id'] ?? 0);
if ($rolActivoId !== 4) {
  http_response_code(403);
  exit('Acceso denegado.');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$empresaNombre = trim((string)($u['empresa']['nombre'] ?? ''));
if ($empresaNombre === '') $empresaNombre = 'Empresa';

$heroTitle = "Aula Virtual - {$empresaNombre} - Administracion";

$baseCssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual.css') ?: '1');
$adminCssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion.css') ?: '1');
$adminJsVersion  = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion.js') ?: '1');

$adminConfig = [
  'apiUrl'  => BASE_URL . '/modules/aula_virtual/api_administracion.php',
  'perPage' => 10,
];
$adminConfigJson = json_encode(
  $adminConfig,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($adminConfigJson === false) {
  $adminConfigJson = '{"apiUrl":"","perPage":10}';
}

include __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual.css?v=<?= h($baseCssVersion) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion.css?v=<?= h($adminCssVersion) ?>">

<div class="content-wrapper" id="avRoot" data-theme="light">
  <div class="content-header av-hero">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <h1 class="m-0 mt-1"><?= h($heroTitle) ?></h1>
          <p class="ava-subtitle m-0 mt-2">Gestiona clientes de tu empresa y administra su asignacion de cursos.</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
          <span class="badge bg-light text-dark av-badge">Empresa activa: <?= h($empresaNombre) ?></span>
        </div>
      </div>
    </div>
  </div>

  <section class="content py-3 ava-admin" id="avAdminRoot" data-default-avatar="<?= h(BASE_URL . '/dist/img/user2-160x160.jpg') ?>">
    <div class="container-fluid">
      <div id="avaNotice" class="alert d-none" role="alert"></div>

      <div class="row g-3">
        <div class="col-12 col-xl-7">
          <div class="card av-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h5 class="m-0">Clientes de la empresa</h5>
              <span class="badge bg-light text-dark ava-count"><span id="avaClientCount">0</span> clientes</span>
            </div>
            <div class="card-body">
              <div class="ava-toolbar mb-3">
                <div class="field">
                  <label class="form-label mb-1">Buscar cliente</label>
                  <input type="text" id="avaFilterQ" class="form-control" placeholder="Documento, nombres o apellidos...">
                </div>
                <div class="field">
                  <label class="form-label mb-1">Filtrar por curso</label>
                  <select id="avaFilterCourse" class="form-select ava-filter-control">
                    <option value="0">Todos los cursos</option>
                  </select>
                </div>
                <div class="field field--refresh">
                  <label class="form-label mb-1">&nbsp;</label>
                  <button type="button" id="avaRefreshBtn" class="btn ava-filter-control ava-refresh-btn w-100">Recargar</button>
                </div>
              </div>

              <div class="ava-table-wrap">
                <table class="table table-sm ava-table mb-0">
                  <thead>
                    <tr>
                      <th>Cliente</th>
                      <th class="text-center" style="width:90px;">Cursos</th>
                      <th style="width:260px;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="avaClientTbody">
                    <tr><td colspan="3" class="ava-empty">Cargando clientes...</td></tr>
                  </tbody>
                </table>
              </div>

              <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0" id="avaPager"></ul>
              </nav>
            </div>
          </div>

          <div class="card av-card mt-3">
            <div class="card-header">
              <h5 class="m-0">Asignar / quitar cursos</h5>
            </div>
            <div class="card-body">
              <div class="ava-selected mb-3">
                <div class="fw-semibold">Cliente seleccionado</div>
                <div class="small" id="avaSelectedClientLabel">Ninguno seleccionado</div>
              </div>

              <div class="ava-course-columns">
                <div class="ava-course-box">
                  <div class="ava-course-title">Cursos disponibles</div>
                  <div id="avaAvailableList" class="ava-course-list"></div>
                  <div id="avaAvailableEmpty" class="ava-help">Selecciona un cliente para ver cursos disponibles.</div>
                </div>
                <div class="ava-course-box">
                  <div class="ava-course-title">Cursos del cliente <span class="text-muted small" id="avaSelectedClientMini">Ninguno seleccionado</span></div>
                  <div id="avaAssignedList" class="ava-course-list"></div>
                  <div id="avaAssignedEmpty" class="ava-help">Selecciona un cliente para ver cursos asignados.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-5">
          <div class="card av-card">
            <div class="card-header">
              <h5 class="m-0" id="avaFormTitle">Crear cliente</h5>
            </div>
            <div class="card-body">
              <div class="ava-help mb-3" id="avaFormHelp">
                Completa los datos para crear un cliente en tu empresa. El rol siempre sera Cliente.
              </div>

              <form id="avaForm" novalidate enctype="multipart/form-data">
                <input type="hidden" id="avaClientId" value="0">

                <div class="mb-3">
                  <label class="form-label mb-1">Foto de perfil (opcional)</label>
                  <div class="ava-photo-box">
                    <div id="avaFotoPrev" class="ava-photo-prev" style="background-image:url('<?= h(BASE_URL . '/dist/img/user2-160x160.jpg') ?>');"></div>
                    <input type="file" id="avaFoto" name="foto" class="form-control ava-photo-input" accept="image/jpeg,image/png,image/webp">
                    <div class="ava-photo-caption mt-1">
                      <span id="avaFotoCap">Sin foto por el momento</span>
                    </div>
                    <div class="ava-photo-size mt-1" id="avaFotoSize"></div>
                    <div class="ava-help mt-1">JPG/PNG/WEBP &middot; Maximo 4MB.</div>
                  </div>
                </div>

                <div class="mb-2">
                  <label for="avaUsuario" class="form-label mb-1">Documento / Usuario</label>
                  <input type="text" id="avaUsuario" class="form-control" maxlength="11" placeholder="DNI o CE (8 a 11 digitos)" required>
                </div>

                <div class="mb-2">
                  <label for="avaNombres" class="form-label mb-1">Nombres</label>
                  <input type="text" id="avaNombres" class="form-control" maxlength="120" placeholder="Nombres del cliente" required>
                </div>

                <div class="mb-2">
                  <label for="avaApellidos" class="form-label mb-1">Apellidos</label>
                  <input type="text" id="avaApellidos" class="form-control" maxlength="120" placeholder="Apellidos del cliente" required>
                </div>

                <div class="mb-2">
                  <label for="avaClave" class="form-label mb-1">Clave</label>
                  <input type="password" id="avaClave" class="form-control" minlength="6" placeholder="Minimo 6 caracteres">
                  <div class="ava-help mt-1">En edicion, deja este campo vacio si no deseas cambiar la clave.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label mb-1">Empresa y rol</label>
                  <div class="form-control bg-light">
                    Empresa: <?= h($empresaNombre) ?> &middot; Rol: Cliente
                  </div>
                </div>

                <div class="ava-form-actions">
                  <button type="submit" class="btn btn-primary" id="avaSaveBtn">Crear cliente</button>
                  <button type="button" class="btn btn-outline-secondary" id="avaResetBtn">Limpiar formulario</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  window.avAdminConfig = <?= $adminConfigJson ?>;
</script>
<script src="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion.js?v=<?= h($adminJsVersion) ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


