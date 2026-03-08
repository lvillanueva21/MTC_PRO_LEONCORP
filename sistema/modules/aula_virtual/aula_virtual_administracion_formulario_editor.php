<?php
// Ver 08-03-26
// modules/aula_virtual/aula_virtual_administracion_formulario_editor.php
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

$heroTitle = "Aula Virtual - {$empresaNombre} - Formularios";
$formId = (int)($_GET['id'] ?? 0);
$modo = strtoupper(trim((string)($_GET['modo'] ?? 'FAST')));
if (!in_array($modo, ['FAST', 'AULA'], true)) $modo = 'FAST';
$grupoId = (int)($_GET['grupo_id'] ?? 0);
$tab = trim((string)($_GET['tab'] ?? 'config'));
if (!in_array($tab, ['config', 'preguntas', 'attempts', 'share'], true)) $tab = 'config';

$baseCssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual.css') ?: '1');
$adminCssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion.css') ?: '1');
$formsCssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion_formularios.css') ?: '1');
$editorJsVersion = (string)(@filemtime(__DIR__ . '/aula_virtual_administracion_formulario_editor.js') ?: '1');

$config = [
  'apiUrl' => BASE_URL . '/modules/aula_virtual/api_formularios_admin.php',
  'backUrl' => BASE_URL . '/modules/aula_virtual/aula_virtual_administracion_formularios.php',
  'formId' => $formId,
  'modo' => $modo,
  'grupoId' => $grupoId,
  'tab' => $tab,
];
$configJson = json_encode(
  $config,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($configJson === false) {
  $configJson = '{"apiUrl":"","backUrl":"","formId":0,"modo":"FAST","grupoId":0,"tab":"config"}';
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
          <p class="ava-subtitle m-0 mt-2">Editor de examenes FAST/AULA para administracion.</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
          <a href="<?= h(BASE_URL . '/modules/aula_virtual/aula_virtual_administracion_formularios.php') ?>" class="btn btn-light btn-sm">Volver a Formularios</a>
        </div>
      </div>
    </div>
  </div>

  <section class="content py-3 ava-admin" id="avAdminFormEditorRoot">
    <div class="container-fluid">
      <div id="avaNotice" class="alert d-none" role="alert"></div>

      <div class="card av-card mb-3">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <strong id="avfeHeaderTitle">Nuevo examen</strong>
              <span class="text-muted ml-2" id="avfeHeaderMeta">Modo <?= h($modo) ?></span>
            </div>
            <div class="avfe-actions">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="avfeBackBtn">Regresar</button>
              <button type="button" class="btn btn-outline-danger btn-sm d-none" id="avfeDeleteBtn">Eliminar formulario</button>
            </div>
          </div>
        </div>
      </div>

      <ul class="nav nav-tabs av-tabs mb-3" role="tablist" id="avfeTabs">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#avfeTabConfig" role="tab">Configuracion</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#avfeTabPreguntas" role="tab">Preguntas</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#avfeTabAttempts" role="tab">Resultados</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#avfeTabShare" role="tab">Compartir</a></li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="avfeTabConfig" role="tabpanel">
          <div class="row g-3">
            <div class="col-12 col-xl-8">
              <div class="card av-card avfe-card">
                <div class="card-header">
                  <h5 class="m-0">Configuracion del formulario</h5>
                </div>
                <div class="card-body">
                  <form id="avfeConfigForm" novalidate>
                    <div class="row">
                      <div class="col-12 col-md-8 mb-2">
                        <label class="form-label mb-1" for="avfeTitulo">Titulo *</label>
                        <input type="text" class="form-control" id="avfeTitulo" maxlength="180" required>
                      </div>
                      <div class="col-12 col-md-4 mb-2">
                        <label class="form-label mb-1" for="avfeModo">Modo</label>
                        <select class="form-control" id="avfeModo">
                          <option value="FAST">FAST</option>
                          <option value="AULA">AULA</option>
                        </select>
                      </div>
                    </div>

                    <div class="mb-2">
                      <label class="form-label mb-1" for="avfeDescripcion">Descripcion</label>
                      <textarea class="form-control" id="avfeDescripcion" rows="2" maxlength="1000"></textarea>
                    </div>

                    <div id="avfeAulaFields" class="d-none">
                      <div class="row">
                        <div class="col-12 col-md-4 mb-2">
                          <label class="form-label mb-1" for="avfeGrupo">Grupo *</label>
                          <select class="form-control" id="avfeGrupo">
                            <option value="0">Selecciona grupo</option>
                          </select>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                          <label class="form-label mb-1" for="avfeCurso">Curso</label>
                          <select class="form-control" id="avfeCurso">
                            <option value="0">Selecciona curso</option>
                          </select>
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                          <label class="form-label mb-1" for="avfeTema">Tema (opcional)</label>
                          <select class="form-control" id="avfeTema">
                            <option value="0">Sin tema</option>
                          </select>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-12 col-md-6 mb-2">
                          <label class="form-label mb-1" for="avfeRequisito">Requisito cumplimiento</label>
                          <select class="form-control" id="avfeRequisito">
                            <option value="ENVIAR">Cumple con enviar</option>
                            <option value="APROBAR">Cumple con aprobar</option>
                          </select>
                        </div>
                      </div>
                    </div>

                    <div id="avfeFastFields" class="d-none">
                      <div class="row">
                        <div class="col-12 col-md-6 mb-2">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="avfeFastNombres">
                            <label class="form-check-label" for="avfeFastNombres">Pedir nombres</label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="avfeFastApellidos">
                            <label class="form-check-label" for="avfeFastApellidos">Pedir apellidos</label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="avfeFastCelular">
                            <label class="form-check-label" for="avfeFastCelular">Pedir celular</label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="avfeFastCategorias">
                            <label class="form-check-label" for="avfeFastCategorias">Pedir categorias de conductor</label>
                          </div>
                        </div>
                        <div class="col-12 col-md-6 mb-2">
                          <label class="form-label mb-1">Tipos de documento permitidos (DNI siempre obligatorio)</label>
                          <div id="avfeTiposDocList" class="border rounded p-2" style="max-height:180px;overflow:auto;"></div>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-12 col-md-3 mb-2">
                        <label class="form-label mb-1" for="avfeIntentosMax">Intentos max</label>
                        <input type="number" class="form-control" id="avfeIntentosMax" min="1" max="50" value="1">
                      </div>
                      <div class="col-12 col-md-3 mb-2">
                        <label class="form-label mb-1" for="avfeNotaMin">Nota minima</label>
                        <input type="number" class="form-control" id="avfeNotaMin" min="0" max="20" step="0.5" value="11">
                      </div>
                      <div class="col-12 col-md-3 mb-2">
                        <label class="form-label mb-1" for="avfeTiempoActivo">Tiempo activo</label>
                        <select class="form-control" id="avfeTiempoActivo">
                          <option value="0">No</option>
                          <option value="1">Si</option>
                        </select>
                      </div>
                      <div class="col-12 col-md-3 mb-2">
                        <label class="form-label mb-1" for="avfeDuracionMin">Duracion (min)</label>
                        <input type="number" class="form-control" id="avfeDuracionMin" min="1" max="720" placeholder="Solo si tiempo activo">
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-12 col-md-4 mb-2">
                        <label class="form-label mb-1" for="avfeMostrarResultado">Mostrar resultado al final</label>
                        <select class="form-control" id="avfeMostrarResultado">
                          <option value="1">Si</option>
                          <option value="0">No</option>
                        </select>
                      </div>
                    </div>

                    <div class="avfe-actions mt-2">
                      <button type="submit" class="btn btn-primary" id="avfeSaveBtn">Guardar configuracion</button>
                      <button type="button" class="btn btn-outline-success d-none" id="avfePublishBtn">Publicar</button>
                      <button type="button" class="btn btn-outline-warning d-none" id="avfeCloseBtn">Cerrar</button>
                      <button type="button" class="btn btn-outline-secondary d-none" id="avfeDraftBtn">Enviar a borrador</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="col-12 col-xl-4">
              <div class="card av-card avfe-card">
                <div class="card-header"><h5 class="m-0">Resumen</h5></div>
                <div class="card-body">
                  <div class="small text-muted mb-2">ID formulario: <strong id="avfeFormIdLabel">Nuevo</strong></div>
                  <div class="small text-muted mb-2">Estado: <strong id="avfeEstadoLabel">BORRADOR</strong></div>
                  <div class="small text-muted mb-2">Tipo: <strong>EXAMEN (v1)</strong></div>
                  <div class="small text-muted mb-2">Preguntas: <strong id="avfePreguntasCount">0</strong></div>
                  <div class="small text-muted mb-2">Intentos registrados: <strong id="avfeIntentosCount">0</strong></div>
                  <div class="avfe-total bad mt-3" id="avfePuntosTotal">Total puntos: 0 / 20</div>
                  <div class="ava-help mt-1">Para publicar, el total de puntos debe ser exactamente 20.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="avfeTabPreguntas" role="tabpanel">
          <div class="card av-card avfe-card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="m-0">Preguntas del examen</h5>
              <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="avfeAutoSplitBtn">Auto repartir 20</button>
                <button type="button" class="btn btn-sm btn-primary" id="avfeQuestionNewBtn">+ Agregar pregunta</button>
              </div>
            </div>
            <div class="card-body">
              <div id="avfeQuestionsList"></div>
              <div id="avfeQuestionsEmpty" class="ava-empty">No hay preguntas registradas.</div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="avfeTabAttempts" role="tabpanel">
          <div class="card av-card avfe-card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="m-0">Resultados / Intentos</h5>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="avfeReloadAttemptsBtn">Recargar</button>
            </div>
            <div class="card-body">
              <div class="ava-table-wrap">
                <table class="table table-sm ava-table mb-0">
                  <thead>
                    <tr>
                      <th>Participante</th>
                      <th class="text-center" style="width:130px;">Estado</th>
                      <th class="text-center" style="width:130px;">Nota</th>
                      <th style="width:130px;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="avfeAttemptsTbody">
                    <tr><td colspan="4" class="ava-empty">Sin intentos registrados.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="avfeTabShare" role="tabpanel">
          <div class="card av-card avfe-card">
            <div class="card-header"><h5 class="m-0">Compartir (solo FAST)</h5></div>
            <div class="card-body">
              <div id="avfeShareBox" class="d-none">
                <div class="mb-2">
                  <label class="form-label mb-1">Link publico</label>
                  <input type="text" class="form-control avf-link-readonly" id="avfeShareLink" readonly>
                </div>
                <div class="mb-2">
                  <label class="form-label mb-1">Codigo</label>
                  <input type="text" class="form-control avf-link-readonly" id="avfeShareCode" readonly>
                </div>
                <div class="ava-help">Para QR y WhatsApp usa la pantalla principal de Formularios, panel FAST.</div>
              </div>
              <div id="avfeShareEmpty" class="ava-empty">Disponible solo para formularios FAST guardados.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="avfeQuestionModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="avfeQuestionForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="avfeQuestionModalTitle">Agregar pregunta</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="avfeQuestionId" value="0">
          <div class="row">
            <div class="col-12 col-md-4 mb-2">
              <label class="form-label mb-1" for="avfeQuestionTipo">Tipo</label>
              <select class="form-control" id="avfeQuestionTipo">
                <option value="OM_UNICA">Opcion multiple (una correcta)</option>
                <option value="OM_MULTIPLE">Opcion multiple (varias, todo-o-nada)</option>
              </select>
            </div>
            <div class="col-12 col-md-4 mb-2">
              <label class="form-label mb-1" for="avfeQuestionPuntos">Puntos</label>
              <input type="number" class="form-control" id="avfeQuestionPuntos" min="0.1" step="0.1" value="1">
            </div>
            <div class="col-12 col-md-4 mb-2">
              <label class="form-label mb-1" for="avfeQuestionOrden">Orden</label>
              <input type="number" class="form-control" id="avfeQuestionOrden" min="1" value="1">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label mb-1" for="avfeQuestionEnunciado">Enunciado *</label>
            <textarea class="form-control" id="avfeQuestionEnunciado" rows="3" required></textarea>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label m-0">Opciones</label>
            <button type="button" class="btn btn-sm btn-outline-primary" id="avfeOptionAddBtn">+ Opcion</button>
          </div>
          <div id="avfeOptionsWrap"></div>
          <div class="ava-help">En OM_UNICA marca solo una opcion correcta. En OM_MULTIPLE se evaluara todo-o-nada.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="avfeQuestionSaveBtn">Guardar pregunta</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  window.avAdminFormEditorConfig = <?= $configJson ?>;
</script>
<script src="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_administracion_formulario_editor.js?v=<?= h($editorJsVersion) ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
