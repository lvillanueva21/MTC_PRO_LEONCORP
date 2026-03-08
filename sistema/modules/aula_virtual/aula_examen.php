<?php
// Ver 08-03-26
// modules/aula_virtual/aula_examen.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([7,1,4,6]);

$u = currentUser();
$rolActivoId = (int)($u['rol_activo_id'] ?? 0);
if ($rolActivoId !== 7) {
  http_response_code(403);
  exit('Acceso denegado.');
}

date_default_timezone_set('America/Lima');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$userId = (int)($u['id'] ?? $_SESSION['user']['id'] ?? $_SESSION['uid'] ?? 0);
$empresaId = (int)($u['empresa']['id'] ?? 0);
$formId = (int)($_GET['pub'] ?? 0);
if ($userId <= 0 || $empresaId <= 0 || $formId <= 0) {
  http_response_code(400);
  exit('Parametros invalidos.');
}

$st = $db->prepare(
  "SELECT
     f.id,
     f.titulo,
     f.descripcion,
     f.curso_id,
     f.grupo_id,
     f.estado,
     g.nombre AS grupo_nombre,
     g.inicio_at AS grupo_inicio_at,
     g.fin_at AS grupo_fin_at,
     c.nombre AS curso_nombre
   FROM cr_formularios f
   JOIN cr_grupos g ON g.id = f.grupo_id
   JOIN cr_matriculas_grupo mg ON mg.grupo_id = f.grupo_id
     AND mg.curso_id = f.curso_id
     AND mg.usuario_id = ?
     AND mg.estado = 1
   JOIN mtp_usuarios ux ON ux.id = mg.usuario_id
   LEFT JOIN cr_cursos c ON c.id = f.curso_id
   WHERE f.id = ?
     AND f.empresa_id = ?
     AND ux.id_empresa = ?
     AND g.empresa_id = ux.id_empresa
     AND g.activo = 1
     AND f.modo = 'AULA'
     AND f.tipo = 'EXAMEN'
     AND f.estado = 'PUBLICADO'
   LIMIT 1"
);
$st->bind_param('iiii', $userId, $formId, $empresaId, $empresaId);
$st->execute();
$form = $st->get_result()->fetch_assoc();
if (!$form) {
  http_response_code(404);
  exit('No tienes acceso a este examen.');
}

$blocked = false;
$blockedMsg = '';
$inicioAt = trim((string)($form['grupo_inicio_at'] ?? ''));
$finAt = trim((string)($form['grupo_fin_at'] ?? ''));
if ($inicioAt !== '' && $finAt !== '') {
  try {
    $tz = new DateTimeZone('America/Lima');
    $now = new DateTime('now', $tz);
    $ini = new DateTime($inicioAt, $tz);
    $fin = new DateTime($finAt, $tz);
    if ($now < $ini || $now > $fin) {
      $blocked = true;
      $blockedMsg = 'Bloqueado por rango horario del grupo: ' . $ini->format('d/m/Y H:i') . ' - ' . $fin->format('d/m/Y H:i');
    }
  } catch (Throwable $ignore) {
    $blocked = true;
    $blockedMsg = 'No se pudo validar el rango horario del grupo.';
  }
}

$title = trim((string)($form['titulo'] ?? 'Examen AULA'));
$desc = trim((string)($form['descripcion'] ?? ''));
$courseName = trim((string)($form['curso_nombre'] ?? 'Curso'));
$backUrl = BASE_URL . '/modules/aula_virtual/?curso=' . (int)$form['curso_id'];

$cfg = [
  'mode' => 'AULA',
  'formId' => (int)$form['id'],
  'apiUrl' => BASE_URL . '/modules/aula_virtual/api_formularios_aula.php',
  'storageKey' => 'av_exam_aula_token_' . (int)$form['id'],
  'blocked' => $blocked ? 1 : 0,
];
$cfgJson = json_encode(
  $cfg,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($cfgJson === false) {
  $cfgJson = '{"mode":"AULA","formId":0,"apiUrl":"","storageKey":"av_exam_aula_token_0","blocked":0}';
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual.css?v=1">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual_formularios_resolver.css?v=1">

<div class="content-wrapper" id="avRoot" data-theme="light">
  <div class="content-header av-hero">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <h1 class="m-0 mt-1"><?= h($title) ?></h1>
          <p class="m-0 mt-2 text-white-50">Curso: <?= h($courseName) ?> | Grupo: <?= h((string)($form['grupo_nombre'] ?? '-')) ?></p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
          <a href="<?= h($backUrl) ?>" class="btn btn-light btn-sm">Volver al Aula</a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div id="avExamRoot">
        <div id="avExamNotice" class="alert d-none" role="alert"></div>

        <div class="card" id="avExamLanding">
          <div class="card-header">
            <div class="avex-head">
              <h3 class="card-title m-0" id="avExamTitle"><?= h($title) ?></h3>
            </div>
          </div>
          <div class="card-body">
            <p class="mb-2" id="avExamDesc"><?= h($desc) ?></p>
            <div class="avex-meta mb-3" id="avExamRules"></div>
            <?php if ($blocked): ?>
              <div class="alert alert-warning mb-3"><?= h($blockedMsg) ?></div>
            <?php endif; ?>
            <div id="avExamFastFields" class="avex-hidden"></div>
            <button type="button" class="btn btn-primary" id="avExamStartBtn"<?= $blocked ? ' disabled' : '' ?>>Comenzar</button>
          </div>
        </div>

        <div class="card avex-hidden" id="avExamRun">
          <div class="card-header">
            <div class="avex-head">
              <div class="avex-time" id="avExamTimer">Tiempo: --:--</div>
              <div class="avex-meta" id="avExamStatusText">Intento en progreso</div>
            </div>
          </div>
          <div class="card-body">
            <div id="avExamQuestions"></div>
            <div class="avex-actions">
              <button type="button" class="btn btn-outline-secondary" id="avExamSaveBtn">Guardar</button>
              <button type="button" class="btn btn-success" id="avExamSubmitBtn">Enviar examen</button>
            </div>
            <div class="avex-saved mt-2" id="avExamLastSaved">Guardado: -</div>
          </div>
        </div>

        <div class="card avex-hidden" id="avExamFinal">
          <div class="card-body">
            <h4 class="mb-2">Resultado</h4>
            <p class="mb-2" id="avExamFinalMsg"></p>
            <div class="avex-final-score mb-3" id="avExamFinalScore"></div>
            <div class="avex-actions">
              <a href="<?= h($backUrl) ?>" class="btn btn-outline-secondary">Volver al Aula</a>
              <button type="button" class="btn btn-outline-secondary" id="avExamBackBtn">Volver al inicio</button>
              <a href="#" target="_blank" class="btn btn-outline-primary avex-hidden" id="avExamPdfBtn">PDF</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  window.avExamResolverConfig = <?= $cfgJson ?>;
  window.avExamBlockedMsg = <?= json_encode($blockedMsg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>/modules/aula_virtual/examen_resolver.js?v=1"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
