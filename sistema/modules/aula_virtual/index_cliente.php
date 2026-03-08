<?php
// Ver 07-03-26
// modules/aula_virtual/index_cliente.php (interfaz Cliente)
if (!defined('AULA_VIRTUAL_ROLE_ROUTED') || !defined('AULA_VIRTUAL_VIEW_ROLE_ID') || AULA_VIRTUAL_VIEW_ROLE_ID !== 7) {
  http_response_code(403);
  exit('Acceso denegado.');
}

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

acl_require_ids([7,1,4,6]);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');
date_default_timezone_set('America/Lima');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function asset($rel){ return $rel ? (BASE_URL.'/'.ltrim($rel,'/')) : ''; }

/** Normaliza enlaces Youtube a embed nocookie */
function yt_embed($url){
  $url = trim((string)$url);
  if ($url === '') return '';
  if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~',$url,$m))      $id=$m[1];
  elseif (preg_match('~v=([A-Za-z0-9_-]{6,})~',$url,$m))          $id=$m[1];
  elseif (preg_match('~/embed/([A-Za-z0-9_-]{6,})~',$url,$m))     $id=$m[1];
  else return $url;
  return "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1";
}

/** Obtiene el ID del usuario autenticado con fallbacks seguros */
function current_user_id(): int {
  if (function_exists('auth_user')) {
    $u = auth_user();
    if (is_array($u) && isset($u['id'])) return (int)$u['id'];
    if (is_object($u) && isset($u->id))  return (int)$u->id;
    if (is_array($u) && isset($u['id_usuario'])) return (int)$u['id_usuario'];
    if (is_object($u) && isset($u->id_usuario))  return (int)$u->id_usuario;
  }
  if (function_exists('auth_uid')) {
    $id = (int)auth_uid();
    if ($id > 0) return $id;
  }
  return (int)($_SESSION['user']['id']
           ?? $_SESSION['auth']['id']
           ?? $_SESSION['uid']
           ?? $_SESSION['id_usuario']
           ?? 0);
}

$u = function_exists('currentUser') ? currentUser() : [];
$empresaNombre = trim((string)($u['empresa']['nombre'] ?? ''));
if ($empresaNombre === '') $empresaNombre = 'Empresa';

$alumnoNombre = trim((string)(
  ($u['nombres'] ?? '')
  . ' '
  . ($u['apellidos'] ?? '')
));
if ($alumnoNombre === '') $alumnoNombre = trim((string)($u['usuario'] ?? ''));
if ($alumnoNombre === '') $alumnoNombre = 'Alumno';

$heroTitle = "Aula Virtual - {$empresaNombre} - Alumno: {$alumnoNombre}";

/* === Cursos por matriculas activas (usuario -> grupo -> curso) === */
$uid = current_user_id();
$tzLima = new DateTimeZone('America/Lima');
$nowLima = new DateTime('now', $tzLima);

$courses = [];
if ($uid > 0) {
  $st = $db->prepare(
    "SELECT
        c.id,
        c.nombre,
        c.descripcion,
        c.imagen_path,
        c.activo,
        c.creado,
        c.actualizado,
        mg.grupo_id,
        mg.matriculado_at,
        g.nombre AS grupo_nombre,
        g.codigo AS grupo_codigo,
        g.inicio_at AS grupo_inicio_at,
        g.fin_at AS grupo_fin_at
     FROM cr_matriculas_grupo mg
     JOIN mtp_usuarios ux ON ux.id = mg.usuario_id
     JOIN cr_grupos g ON g.id = mg.grupo_id AND g.empresa_id = ux.id_empresa
     JOIN cr_cursos c ON c.id = mg.curso_id
     WHERE mg.usuario_id = ?
       AND mg.estado = 1
       AND g.activo = 1
       AND c.activo = 1
     ORDER BY c.id DESC"
  );
  $st->bind_param('i', $uid);
  $st->execute();
  $courses = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

foreach ($courses as &$c) {
  $inicio = trim((string)($c['grupo_inicio_at'] ?? ''));
  $fin = trim((string)($c['grupo_fin_at'] ?? ''));
  $blocked = false;
  $inicioDt = null;
  $finDt = null;

  if ($inicio !== '') {
    try {
      $inicioDt = new DateTime($inicio, $tzLima);
    } catch (Throwable $ignore) {
      $inicioDt = null;
    }
  }
  if ($fin !== '') {
    try {
      $finDt = new DateTime($fin, $tzLima);
    } catch (Throwable $ignore) {
      $finDt = null;
    }
  }

  if ($inicioDt && $finDt) {
    $blocked = ($nowLima < $inicioDt || $nowLima > $finDt);
  }
  $c['blocked'] = $blocked ? 1 : 0;
  $c['rango_texto'] = ($inicioDt && $finDt)
    ? ($inicioDt->format('d/m/Y H:i') . ' - ' . $finDt->format('d/m/Y H:i'))
    : 'Indefinido';
}
unset($c);

$selectedId = (int)($_GET['curso'] ?? 0);

/* Si no enviaron curso o enviaron uno no asignado, seleccionar el primero asignado (si existe) */
$courseSel = null;
if ($courses) {
  if (!$selectedId) $selectedId = (int)$courses[0]['id'];
  foreach ($courses as $c) if ((int)$c['id'] === $selectedId) { $courseSel = $c; break; }
  if (!$courseSel) { $selectedId = (int)$courses[0]['id']; $courseSel = $courses[0]; }
}

$courseBlocked = (bool)((int)($courseSel['blocked'] ?? 0) === 1);
$blockedMessage = '';
if ($courseSel && $courseBlocked) {
  $inicioTxt = '';
  $finTxt = '';
  if (!empty($courseSel['grupo_inicio_at'])) {
    $inicioTxt = (new DateTime($courseSel['grupo_inicio_at'], $tzLima))->format('d/m/Y H:i');
  }
  if (!empty($courseSel['grupo_fin_at'])) {
    $finTxt = (new DateTime($courseSel['grupo_fin_at'], $tzLima))->format('d/m/Y H:i');
  }
  if ($inicioTxt !== '' && $finTxt !== '') {
    $blockedMessage = "Acceso bloqueado. Este curso solo está disponible desde {$inicioTxt} hasta {$finTxt}. Vuelve a intentarlo dentro de ese rango.";
  } else {
    $blockedMessage = 'Acceso bloqueado temporalmente para este grupo. Contacta a tu administrador.';
  }
}

/* Temas del curso seleccionado */
$temas = [];
if ($courseSel && !$courseBlocked) {
  $st = $db->prepare("SELECT id,titulo,clase,video_url,miniatura_path,creado,actualizado FROM cr_temas WHERE curso_id=? ORDER BY id ASC");
  $st->bind_param('i',$selectedId);
  $st->execute();
  $temas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* Examenes AULA del curso/grupo seleccionado */
$examenesAula = [];
if ($courseSel && $uid > 0) {
  $grupoSelId = (int)($courseSel['grupo_id'] ?? 0);
  if ($grupoSelId > 0) {
    $st = $db->prepare(
      "SELECT
          f.id,
          f.titulo,
          f.descripcion,
          f.intentos_max,
          f.tiempo_activo,
          f.duracion_min,
          f.nota_min,
          f.mostrar_resultado,
          (
            SELECT COUNT(*) FROM cr_formulario_intentos i
            WHERE i.formulario_id = f.id
              AND i.usuario_id = mg.usuario_id
          ) AS intentos_usados,
          (
            SELECT COUNT(*) FROM cr_formulario_intentos i
            WHERE i.formulario_id = f.id
              AND i.usuario_id = mg.usuario_id
              AND i.status = 'EN_PROGRESO'
          ) AS intentos_en_progreso
       FROM cr_formularios f
       JOIN cr_matriculas_grupo mg ON mg.grupo_id = f.grupo_id
         AND mg.curso_id = f.curso_id
         AND mg.usuario_id = ?
         AND mg.estado = 1
       JOIN mtp_usuarios ux ON ux.id = mg.usuario_id
       JOIN cr_grupos g ON g.id = f.grupo_id
         AND g.empresa_id = ux.id_empresa
       WHERE f.modo = 'AULA'
         AND f.tipo = 'EXAMEN'
         AND f.estado = 'PUBLICADO'
         AND f.curso_id = ?
         AND f.grupo_id = ?
         AND g.activo = 1
       ORDER BY f.id DESC"
    );
    $st->bind_param('iii', $uid, $selectedId, $grupoSelId);
    $st->execute();
    $examenesAula = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  }
}

/* Conteo inicial para progreso */
$totalTemas = (int)count($temas);
$doneInicial = 0;

/* Metadatos simples */
$courseMeta = [
  'title'          => $courseSel ? $courseSel['nombre'] : 'Selecciona un curso',
  'rating'         => 4.8,
  'ratings_count'  => 2400,
  'students'       => 90500,
  'total_duration' => '-',
  'updated_ago'    => 'reciente',
  'languages'      => 'ES',
  'captions'       => 'Si',
  'level'          => 'Todos los niveles',
  'lectures'       => $totalTemas,
];

$firstUrl = (!$courseBlocked && $temas) ? yt_embed($temas[0]['video_url'] ?? '') : '';

$temaMap = [];
foreach ($temas as $t) {
  $tid = (string)((int)($t['id'] ?? 0));
  if ($tid === '0') continue;
  $temaMap[$tid] = [
    'titulo' => (string)($t['titulo'] ?? ''),
    'clase'  => (string)($t['clase'] ?? ''),
  ];
}

$cssVersion = (string)(@filemtime(__DIR__ . '/aula_virtual.css') ?: '1');
$jsVersion  = (string)(@filemtime(__DIR__ . '/aula_virtual.js') ?: '1');

$avConfig = [
  'totalTemas'      => $totalTemas,
  'temaMap'         => $temaMap,
  'blocked'         => $courseBlocked ? 1 : 0,
  'blockedMessage'  => $blockedMessage,
  'themeStorageKey' => 'av_theme_pref',
];
$avConfigJson = json_encode(
  $avConfig,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($avConfigJson === false) {
  $avConfigJson = '{"totalTemas":0,"temaMap":{},"blocked":0,"blockedMessage":"","themeStorageKey":"av_theme_pref"}';
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual.css?v=<?= h($cssVersion) ?>">

<div class="content-wrapper" id="avRoot" data-theme="light">
  <div class="content-header av-hero">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <h1 class="m-0 mt-1"><?= h($heroTitle) ?></h1>
          <div class="d-flex gap-3 flex-wrap mt-2">
            <span><i class="fas fa-star me-1"></i><?= number_format((float)$courseMeta['rating'],1) ?></span>
          </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
          <div class="d-flex justify-content-lg-end align-items-center gap-2 flex-wrap">
            <button type="button" class="btn av-btn-tareas">Tareas</button>
            <span class="badge bg-light text-dark av-badge me-1">Mi progreso</span>
            <span id="avProgressText" class="text-white-50">0% completado</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <section class="content py-3">
    <div class="container-fluid">
      <div class="row g-3">
        <!-- Player / Tabs -->
        <div class="col-lg-8">
          <div class="card av-card shadow-sm">
            <div class="card-body">
              <div id="avBlockedNotice" class="alert alert-warning mb-3<?= $courseBlocked ? '' : ' d-none' ?>">
                <?= h($blockedMessage !== '' ? $blockedMessage : 'Acceso bloqueado temporalmente.') ?>
              </div>

              <div id="avPlayerWrap" class="ratio-16x9 mb-3<?= $courseBlocked ? ' d-none' : '' ?>">
                <iframe id="avPlayer" src="<?= h($firstUrl) ?>" allowfullscreen></iframe>
              </div>

              <div id="avContentShell" class="<?= $courseBlocked ? 'd-none' : '' ?>">
                <div class="d-flex flex-wrap gap-2 av-controls mb-3">
                  <button class="btn btn-outline-secondary btn-sm"><i class="far fa-bookmark me-1"></i>Guardar nota</button>
                  <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-download me-1"></i>Descargar</button>
                  <button class="btn btn-outline-secondary btn-sm"><i class="far fa-share-square me-1"></i>Compartir</button>
                </div>

                <ul class="nav nav-tabs av-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" data-toggle="tab" href="#tab_tema" role="tab">Tema</a></li>
                  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-toggle="tab" href="#tab_curso" role="tab">Curso</a></li>
                  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-toggle="tab" href="#tab_exam" role="tab">Examen</a></li>
                  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-toggle="tab" href="#tab_calif" role="tab">Calificacion</a></li>
                </ul>

                <div class="tab-content pt-3">
                  <div class="tab-pane fade show active" id="tab_tema" role="tabpanel">
                    <?php if ($temas): ?>
                      <h6 id="avTemaTitulo" class="mb-2"><?= h($temas[0]['titulo'] ?? 'Tema') ?></h6>
                      <div id="avTemaDesc" class="text-muted mb-0">
                        <?= !empty($temas[0]['clase']) ? nl2br(h($temas[0]['clase'])) : 'Sin contenido disponible' ?>
                      </div>
                    <?php else: ?>
                      <p class="text-muted mb-0">Sin contenido disponible</p>
                    <?php endif; ?>
                  </div>
                  <div class="tab-pane fade" id="tab_curso" role="tabpanel">
                    <div class="row">
                      <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                          <li><strong>Nivel:</strong> <?= h($courseMeta['level']) ?></li>
                          <li><strong>Clases:</strong> <?= (int)$courseMeta['lectures'] ?></li>
                          <li><strong>Duracion total:</strong> <?= h($courseMeta['total_duration']) ?></li>
                        </ul>
                      </div>
                      <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                          <li><strong>Subtitulos:</strong> <?= h($courseMeta['captions']) ?></li>
                          <li><strong>Idiomas:</strong> <?= h($courseMeta['languages']) ?></li>
                        </ul>
                      </div>
                    </div>
                    <?php if (!empty($courseSel['descripcion'])): ?>
                      <hr><p class="mb-0"><?= nl2br(h($courseSel['descripcion'])) ?></p>
                    <?php endif; ?>
                  </div>
                  <div class="tab-pane fade" id="tab_exam" role="tabpanel">
                    <?php if ($courseBlocked): ?>
                      <div class="alert alert-warning mb-0">Los examenes estan bloqueados mientras el curso este fuera del rango horario del grupo.</div>
                    <?php elseif (!$courseSel): ?>
                      <p class="text-muted mb-0">Selecciona un curso para ver examenes.</p>
                    <?php elseif (!$examenesAula): ?>
                      <p class="text-muted mb-0">No hay examenes disponibles para este curso y grupo.</p>
                    <?php else: ?>
                      <div class="av-exam-list">
                        <?php foreach ($examenesAula as $ex):
                          $intentosMax = (int)($ex['intentos_max'] ?? 1);
                          $intentosUsados = (int)($ex['intentos_usados'] ?? 0);
                          $enProgreso = (int)($ex['intentos_en_progreso'] ?? 0);
                          $restantes = max(0, $intentosMax - $intentosUsados);
                          $puedeAbrir = ($enProgreso > 0 || $restantes > 0);
                          $btnLabel = $enProgreso > 0 ? 'Continuar' : 'Comenzar';
                          $duracionTxt = ((int)($ex['tiempo_activo'] ?? 0) === 1 && (int)($ex['duracion_min'] ?? 0) > 0)
                            ? ((int)$ex['duracion_min'] . ' min')
                            : 'Sin limite';
                        ?>
                          <div class="av-exam-item">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                              <div>
                                <div class="fw-semibold"><?= h((string)($ex['titulo'] ?? 'Examen')) ?></div>
                                <div class="small text-muted"><?= !empty($ex['descripcion']) ? h((string)$ex['descripcion']) : 'Sin descripcion' ?></div>
                                <div class="small text-muted mt-1">
                                  Intentos: <?= $intentosUsados ?>/<?= $intentosMax ?> - Restantes: <?= $restantes ?> - Duracion: <?= h($duracionTxt) ?> - Nota minima: <?= h((string)$ex['nota_min']) ?>
                                </div>
                              </div>
                              <?php if ($puedeAbrir): ?>
                                <a class="btn btn-sm btn-primary" href="<?= h(BASE_URL . '/modules/aula_virtual/aula_examen.php?pub=' . (int)$ex['id']) ?>"><?= h($btnLabel) ?></a>
                              <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Sin intentos</button>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="tab-pane fade" id="tab_calif" role="tabpanel">
                    <p class="text-muted mb-0">Sin contenido disponible</p>
                  </div>
                </div>
              </div>

              <?php if ($courseBlocked): ?>
                <div class="small text-muted">Tu acceso se habilitara automaticamente en el rango permitido del grupo.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Cursos + Playlist -->
        <div class="col-lg-4">
          <!-- Cursos -->
          <div class="card av-card shadow-sm mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="m-0">Cursos matriculados</h5>
                <span class="badge bg-light text-dark"><?= count($courses) ?> matriculas</span>
              </div>

              <div class="list-group">
                <?php if (!$courses): ?>
                  <div class="text-muted small">Aun no tienes cursos matriculados.</div>
                <?php else: ?>
                  <?php foreach ($courses as $c):
                    $thumb = $c['imagen_path'] ? asset($c['imagen_path']) : (BASE_URL.'/modules/consola/assets/no-image.png');
                    $isActive = ((int)$c['id'] === $selectedId);
                    $isBlocked = ((int)($c['blocked'] ?? 0) === 1);
                    $grupoNombre = trim((string)($c['grupo_nombre'] ?? ''));
                    $grupoCodigo = trim((string)($c['grupo_codigo'] ?? ''));
                  ?>
                    <div class="av-course-item <?= $isActive?'active':'' ?> <?= $isBlocked ? 'blocked' : '' ?>" data-id="<?= (int)$c['id'] ?>">
                      <img src="<?= h($thumb) ?>" class="av-course-thumb" alt="Curso">
                      <div class="flex-fill">
                        <div class="fw-semibold"><?= h($c['nombre']) ?></div>
                        <div class="small text-muted">
                          Grupo: <?= h($grupoNombre !== '' ? $grupoNombre : ('#' . (int)($c['grupo_id'] ?? 0))) ?>
                          <?= $grupoCodigo !== '' ? (' (' . h($grupoCodigo) . ')') : '' ?>
                        </div>
                        <div class="small <?= $isBlocked ? 'text-danger' : 'text-muted' ?>">
                          <?= h((string)($c['rango_texto'] ?? 'Indefinido')) ?> · <?= $isBlocked ? 'Bloqueado' : 'Disponible' ?>
                        </div>
                      </div>
                      <i class="fas fa-chevron-right"></i>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Playlist -->
          <div class="card av-card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="m-0">Contenido del Curso</h5>
                <span class="badge bg-light text-dark" id="avCountBadge"><?= $doneInicial ?>/<?= $totalTemas ?> completadas</span>
              </div>

              <div class="av-list<?= $courseBlocked ? ' d-none' : '' ?>" id="avPlaylist">
                <?php if ($courseBlocked): ?>
                  <div class="text-danger">Contenido bloqueado para este grupo fuera del rango permitido.</div>
                <?php elseif ($temas): ?>
                  <div class="av-section">Temas</div>
                  <?php foreach ($temas as $i=>$t):
                    $url = yt_embed($t['video_url'] ?? '');
                    $thumb = $t['miniatura_path'] ? asset($t['miniatura_path']) : (BASE_URL.'/modules/consola/assets/no-image.png');
                  ?>
                  <div class="item d-flex justify-content-between align-items-center mb-1"
                       data-url="<?= h($url) ?>"
                       data-id="<?= (int)$t['id'] ?>">
                    <div class="d-flex align-items-center">
                      <img src="<?= h($thumb) ?>" class="av-mini me-2" alt="Miniatura">
                      <div>
                        <div class="title"><?= h($t['titulo']) ?></div>
                        <div class="meta">Tema <?= ($i+1) ?></div>
                      </div>
                    </div>
                    <div class="ms-2"><i class="far fa-play-circle text-muted"></i></div>
                  </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-danger">Aun no hay temas guardados.</div>
                <?php endif; ?>
              </div>

              <hr>
              <div class="small text-muted">Tip: haz clic en cualquier tema para reproducirlo.</div>
            </div>
          </div>
        </div>
      </div><!-- /row -->
    </div>
  </section>

  <button type="button" id="avThemeToggle" class="av-theme-fab" aria-label="Cambiar tema del Aula Virtual">
    <i class="fas fa-adjust" aria-hidden="true"></i>
    <span id="avThemeToggleText">Tema: Claro</span>
  </button>
</div>

<script>
  window.avAulaConfig = <?= $avConfigJson ?>;
</script>
<script src="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual.js?v=<?= h($jsVersion) ?>"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
