<?php
// Ver 07-03-26
// modules/aula_virtual/aula_virtual_administracion_formularios.php
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
$formsCssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion_formularios.css') ?: '1');
$formsJsVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion_formularios.js') ?: '1');

$cfg = [
  'apiUrl' => BASE_URL . '/modules/aula_virtual/api_formularios_admin.php',
  'editorUrl' => BASE_URL . '/modules/aula_virtual/aula_virtual_administracion_formulario_editor.php',
  'qrUrl' => BASE_URL . '/modules/aula_virtual/qr_fast.php',
  'groupsUrl' => BASE_URL . '/modules/aula_virtual/aula_virtual_administracion_cursos.php',
  'perPage' => 10,
];
$cfgJson = json_encode(
  $cfg,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($cfgJson === false) {
  $cfgJson = '{"apiUrl":"","editorUrl":"","qrUrl":"","groupsUrl":"","perPage":10}';
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual.css?v=<?= h($baseCssVersion) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion.css?v=<?= h($adminCssVersion) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion_formularios.css?v=<?= h($formsCssVersion) ?>">

<div class="content-wrapper" id="avRoot" data-theme="light">
  <div class="content-header av-hero">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <h1 class="m-0 mt-1"><?= h($heroTitle) ?></h1>
          <p class="ava-subtitle m-0 mt-2">Crea y administra formularios de examen FAST y AULA por empresa/grupo.</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
          <span class="badge bg-light text-dark av-badge">Empresa activa: <?= h($empresaNombre) ?></span>
        </div>
      </div>
    </div>
  </div>

  <section class="content py-3 ava-admin" id="avAdminFormsRoot">
    <div class="container-fluid">
      <div id="avaNotice" class="alert d-none" role="alert"></div>

      <div class="row g-3">
        <div class="col-12 col-xl-6">
          <div class="card av-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h5 class="m-0">FAST (publico)</h5>
              <button type="button" class="btn btn-sm btn-primary" id="avfFastNewBtn">+ Nuevo FAST (Examen)</button>
            </div>
            <div class="card-body">
              <div class="ava-toolbar mb-3">
                <div class="field">
                  <label class="form-label mb-1">Buscar</label>
                  <input type="text" id="avfFastQ" class="form-control" placeholder="Titulo o descripcion...">
                </div>
                <div class="field">
                  <label class="form-label mb-1">Estado</label>
                  <select id="avfFastEstado" class="form-control ava-filter-control">
                    <option value="">Todos</option>
                    <option value="BORRADOR">Borrador</option>
                    <option value="PUBLICADO">Publicado</option>
                    <option value="CERRADO">Cerrado</option>
                  </select>
                </div>
                <div class="field field--refresh">
                  <label class="form-label mb-1">&nbsp;</label>
                  <button type="button" class="btn ava-filter-control ava-refresh-btn w-100" id="avfFastRefresh">Recargar</button>
                </div>
              </div>

              <div class="ava-table-wrap">
                <table class="table table-sm ava-table mb-0">
                  <thead>
                    <tr>
                      <th>Formulario</th>
                      <th class="text-center" style="width:90px;">Puntos</th>
                      <th class="text-center" style="width:120px;">Intentos</th>
                      <th style="width:210px;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="avfFastTbody">
                    <tr><td colspan="4" class="ava-empty">Cargando formularios FAST...</td></tr>
                  </tbody>
                </table>
              </div>

              <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0" id="avfFastPager"></ul>
              </nav>
            </div>
          </div>

          <div class="card av-card mt-3">
            <div class="card-header">
              <h5 class="m-0">Compartir FAST</h5>
            </div>
            <div class="card-body">
              <div class="ava-selected mb-3">
                <div class="fw-semibold">Formulario seleccionado</div>
                <div class="small" id="avfShareSelected">Ninguno seleccionado</div>
              </div>

              <div class="mb-2">
                <label class="form-label mb-1">Link publico</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="avfShareLink" readonly>
                  <button type="button" class="btn btn-outline-secondary" id="avfShareCopyBtn">Copiar</button>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="avfShareQrBtn">Ver QR</button>
              </div>

              <div class="row">
                <div class="col-12 col-md-7 mb-2">
                  <label class="form-label mb-1">WhatsApp (+51)</label>
                  <input type="text" class="form-control" id="avfWaPhone" maxlength="9" placeholder="9 digitos">
                </div>
                <div class="col-12 col-md-5 mb-2 d-flex align-items-end">
                  <button type="button" class="btn btn-success w-100" id="avfWaBtn">Abrir WhatsApp</button>
                </div>
              </div>

              <div class="ava-help">WhatsApp se abre de forma manual en una nueva pestaña con el link precargado.</div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6">
          <div class="card av-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h5 class="m-0">AULA (privado por grupo)</h5>
              <button type="button" class="btn btn-sm btn-primary" id="avfAulaNewBtn">+ Nuevo AULA (Examen)</button>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label mb-1">Grupo</label>
                <select id="avfAulaGrupo" class="form-control ava-filter-control">
                  <option value="0">Selecciona un grupo</option>
                </select>
                <div class="ava-help mt-1 d-none" id="avfNoGroupsHelp">
                  No hay grupos en tu empresa. Crea uno primero para habilitar exámenes AULA.
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2 d-none" id="avfGoGroupsBtn">Ir a Cursos &gt; Grupos</button>
              </div>

              <div class="ava-toolbar mb-3">
                <div class="field">
                  <label class="form-label mb-1">Buscar</label>
                  <input type="text" id="avfAulaQ" class="form-control" placeholder="Titulo o descripcion...">
                </div>
                <div class="field">
                  <label class="form-label mb-1">Curso</label>
                  <select id="avfAulaCurso" class="form-control ava-filter-control">
                    <option value="0">Todos</option>
                  </select>
                </div>
                <div class="field">
                  <label class="form-label mb-1">Tema</label>
                  <select id="avfAulaTema" class="form-control ava-filter-control">
                    <option value="0">Todos</option>
                  </select>
                </div>
                <div class="field">
                  <label class="form-label mb-1">Estado</label>
                  <select id="avfAulaEstado" class="form-control ava-filter-control">
                    <option value="">Todos</option>
                    <option value="BORRADOR">Borrador</option>
                    <option value="PUBLICADO">Publicado</option>
                    <option value="CERRADO">Cerrado</option>
                  </select>
                </div>
                <div class="field field--refresh">
                  <label class="form-label mb-1">&nbsp;</label>
                  <button type="button" class="btn ava-filter-control ava-refresh-btn w-100" id="avfAulaRefresh">Recargar</button>
                </div>
              </div>

              <div class="ava-table-wrap">
                <table class="table table-sm ava-table mb-0">
                  <thead>
                    <tr>
                      <th>Formulario</th>
                      <th class="text-center" style="width:90px;">Puntos</th>
                      <th class="text-center" style="width:110px;">Intentos</th>
                      <th style="width:220px;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="avfAulaTbody">
                    <tr><td colspan="4" class="ava-empty">Selecciona un grupo para listar exámenes AULA.</td></tr>
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

<div class="modal fade" id="avfQrModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">QR de formulario FAST</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-2 fw-semibold" id="avfQrCode">-</div>
        <img src="" alt="QR formulario FAST" id="avfQrImg" class="avf-qr-image">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.avAdminFormsConfig = <?= $cfgJson ?>;
</script>
<script src="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion_formularios.js?v=<?= h($formsJsVersion) ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
