<?php
// Ver 08-03-26
// modules/aula_virtual/aula_virtual_administracion_cursos.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';

acl_require_ids([1, 4]);

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
$coursesCssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion_cursos.css') ?: '1');
$coursesJsVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion_cursos.js') ?: '1');

$config = [
  'apiUrl' => BASE_URL . '/modules/aula_virtual/api_cursos_admin.php',
  'perPage' => 10,
];
$configJson = json_encode(
  $config,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($configJson === false) {
  $configJson = '{"apiUrl":"","perPage":10}';
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual.css?v=<?= h($baseCssVersion) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion.css?v=<?= h($adminCssVersion) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion_cursos.css?v=<?= h($coursesCssVersion) ?>">

<div class="content-wrapper" id="avRoot" data-theme="light">
  <div class="content-header av-hero">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <h1 class="m-0 mt-1"><?= h($heroTitle) ?></h1>
          <p class="ava-subtitle m-0 mt-2">Consulta cursos y temas en modo lectura. Gestiona grupos de tu empresa.</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
          <span class="badge bg-light text-dark av-badge">Empresa activa: <?= h($empresaNombre) ?></span>
        </div>
      </div>
    </div>
  </div>

  <section class="content py-3 ava-admin" id="avAdminCursosRoot" data-default-course-image="<?= h(BASE_URL . '/modules/consola/assets/no-image.png') ?>">
    <div class="container-fluid">
      <div id="avaNotice" class="alert d-none" role="alert"></div>

      <div class="row g-3">
        <div class="col-12 col-xl-7">
          <div class="card av-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h5 class="m-0">Cursos (solo lectura)</h5>
              <span class="badge bg-light text-dark ava-count"><span id="avcCourseCount">0</span> cursos</span>
            </div>
            <div class="card-body">
              <div class="ava-toolbar mb-3">
                <div class="field">
                  <label class="form-label mb-1">Buscar curso</label>
                  <input type="text" id="avcFilterQ" class="form-control" placeholder="Nombre o descripcion...">
                </div>
                <div class="field">
                  <label class="form-label mb-1">Estado</label>
                  <select id="avcFilterEstado" class="form-control ava-filter-control">
                    <option value="">Todos</option>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                  </select>
                </div>
                <div class="field field--refresh">
                  <label class="form-label mb-1">&nbsp;</label>
                  <button type="button" id="avcRefreshBtn" class="btn ava-filter-control ava-refresh-btn w-100">Recargar</button>
                </div>
              </div>

              <div class="ava-table-wrap">
                <table class="table table-sm ava-table mb-0 avc-course-table">
                  <thead>
                    <tr>
                      <th>Curso</th>
                      <th class="text-center" style="width:90px;">Temas</th>
                      <th class="text-center" style="width:90px;">Grupos</th>
                      <th class="text-center" style="width:120px;">Matriculados</th>
                      <th style="width:220px;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="avcCourseTbody">
                    <tr><td colspan="5" class="ava-empty">Cargando cursos...</td></tr>
                  </tbody>
                </table>
              </div>

              <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0" id="avcPager"></ul>
              </nav>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-5">
          <div class="card av-card">
            <div class="card-header">
              <h5 class="m-0">Temas del curso (solo lectura)</h5>
            </div>
            <div class="card-body">
              <div class="ava-selected mb-3">
                <div class="fw-semibold">Curso seleccionado</div>
                <div class="small" id="avcTopicCourseLabel">Ninguno seleccionado</div>
              </div>

              <div id="avcTopicsList" class="avc-topic-list"></div>
              <div id="avcTopicsEmpty" class="ava-help">Selecciona un curso para ver sus temas.</div>

              <hr>
              <div id="avcTopicDetail" class="d-none">
                <div class="fw-semibold mb-2" id="avcTopicDetailTitle">Tema</div>
                <div class="mb-2 d-none" id="avcTopicDetailThumbWrap">
                  <img src="" alt="Miniatura del tema" id="avcTopicDetailThumb">
                </div>
                <div class="mb-2">
                  <a href="#" target="_blank" rel="noopener" id="avcTopicDetailVideo" class="d-none">Abrir video</a>
                </div>
                <div class="text-muted avc-topic-class" id="avcTopicDetailClass">Sin contenido.</div>
              </div>
            </div>
          </div>

          <div class="card av-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
              <h5 class="m-0">Grupos del curso</h5>
              <button type="button" class="btn btn-sm btn-primary" id="avcGroupNewBtn" disabled>Crear grupo</button>
            </div>
            <div class="card-body">
              <div class="ava-selected mb-3">
                <div class="fw-semibold">Curso seleccionado</div>
                <div class="small" id="avcGroupCourseLabel">Ninguno seleccionado</div>
              </div>

              <div class="ava-table-wrap">
                <table class="table table-sm ava-table mb-0 avc-group-table">
                  <thead>
                    <tr>
                      <th>Grupo</th>
                      <th class="text-center" style="width:110px;">Matriculados</th>
                      <th style="width:220px;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="avcGroupTbody">
                    <tr><td colspan="3" class="ava-empty">Selecciona un curso para gestionar grupos.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="avcGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="avcGroupForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="avcGroupModalTitle">Crear grupo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="avcGroupId" value="0">
          <input type="hidden" id="avcGroupCursoId" value="0">

          <div class="ava-help mb-3">Curso: <strong id="avcGroupModalCourseLabel">-</strong></div>

          <div class="mb-2">
            <label for="avcGroupName" class="form-label mb-1">Nombre del grupo</label>
            <input type="text" id="avcGroupName" class="form-control" maxlength="150" required>
          </div>
          <div class="mb-2">
            <label for="avcGroupDesc" class="form-label mb-1">Descripcion (opcional)</label>
            <input type="text" id="avcGroupDesc" class="form-control" maxlength="255">
          </div>
          <div class="row">
            <div class="col-12 col-md-6 mb-2">
              <label for="avcGroupStart" class="form-label mb-1">Inicio (fecha y hora)</label>
              <input type="datetime-local" id="avcGroupStart" class="form-control">
            </div>
            <div class="col-12 col-md-6 mb-2">
              <label for="avcGroupEnd" class="form-label mb-1">Fin (fecha y hora)</label>
              <input type="datetime-local" id="avcGroupEnd" class="form-control">
            </div>
          </div>
          <div class="ava-help mb-2">Si defines inicio o fin, debes completar ambos. Para grupo indefinido deja ambos vacios.</div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="avcGroupActive" checked>
            <label class="form-check-label" for="avcGroupActive">Grupo activo</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="avcGroupSaveBtn">Guardar grupo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="avcGroupDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar eliminacion de grupo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="mb-2" id="avcGroupDeleteText">Vas a eliminar un grupo.</p>
        <p class="mb-0 text-muted">Esta accion no se puede deshacer.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="avcGroupDeleteConfirmBtn">Si, eliminar</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.avAdminCursosConfig = <?= $configJson ?>;
</script>
<script src="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion_cursos.js?v=<?= h($coursesJsVersion) ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
