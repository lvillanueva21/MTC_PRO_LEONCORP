<?php
// modules/aula_virtual/index.php (v2 - cursos asignados, sin <select>)
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

acl_require_ids([7,1,4,6]);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

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

/* === Cursos (SOLO los asignados y activos) === */
$uid = current_user_id();

$courses = [];
if ($uid > 0) {
  $st = $db->prepare(
    "SELECT c.id, c.nombre, c.descripcion, c.imagen_path, c.activo, c.creado, c.actualizado
     FROM cr_usuario_curso uc
     JOIN cr_cursos c ON c.id = uc.curso_id
     WHERE uc.usuario_id = ?
       AND uc.activo = 1
       AND c.activo = 1
     ORDER BY c.id DESC"
  );
  $st->bind_param('i', $uid);
  $st->execute();
  $courses = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

$selectedId = (int)($_GET['curso'] ?? 0);

/* Si no enviaron curso o enviaron uno no asignado, seleccionar el primero asignado (si existe) */
$courseSel = null;
if ($courses) {
  if (!$selectedId) $selectedId = (int)$courses[0]['id'];
  foreach ($courses as $c) if ((int)$c['id'] === $selectedId) { $courseSel = $c; break; }
  if (!$courseSel) { $selectedId = (int)$courses[0]['id']; $courseSel = $courses[0]; }
}

/* Temas del curso seleccionado */
$temas = [];
if ($courseSel) {
  $st = $db->prepare("SELECT id,titulo,clase,video_url,miniatura_path,creado,actualizado FROM cr_temas WHERE curso_id=? ORDER BY id ASC");
  $st->bind_param('i',$selectedId);
  $st->execute();
  $temas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
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
  'total_duration' => '—',
  'updated_ago'    => 'reciente',
  'languages'      => 'ES',
  'captions'       => 'Sí',
  'level'          => 'Todos los niveles',
  'lectures'       => $totalTemas,
];

$firstUrl = $temas ? yt_embed($temas[0]['video_url'] ?? '') : '';

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
  'themeStorageKey' => 'av_theme_pref',
];
$avConfigJson = json_encode(
  $avConfig,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($avConfigJson === false) {
  $avConfigJson = '{"totalTemas":0,"temaMap":{},"themeStorageKey":"av_theme_pref"}';
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/aula_virtual/aula_virtual.css?v=<?= h($cssVersion) ?>">

<div class="content-wrapper" id="avRoot" data-theme="light">
  <div class="content-header av-hero">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/inicio.php">Inicio</a></li>
              <li class="breadcrumb-item">Aula virtual</li>
              <li class="breadcrumb-item active" aria-current="page"><?= h($courseMeta['title']) ?></li>
            </ol>
          </nav>
          <h1 class="m-0 mt-1"><?= h($courseMeta['title']) ?></h1>
          <div class="d-flex gap-3 flex-wrap mt-2">
            <span><i class="fas fa-star me-1"></i><?= number_format((float)$courseMeta['rating'],1) ?></span>
            <span><?= number_format((int)$courseMeta['ratings_count']) ?> calificaciones</span>
            <span><i class="fas fa-user-graduate me-1"></i><?= number_format((int)$courseMeta['students']) ?> alumnos</span>
          </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
          <span class="badge bg-light text-dark av-badge me-1">Mi progreso</span>
          <span id="avProgressText" class="text-white-50">0% completado</span>
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
              <div class="ratio-16x9 mb-3">
                <iframe id="avPlayer" src="<?= h($firstUrl) ?>" allowfullscreen></iframe>
              </div>

              <div class="d-flex flex-wrap gap-2 av-controls mb-3">
                <button class="btn btn-outline-secondary btn-sm"><i class="far fa-bookmark me-1"></i>Guardar nota</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-download me-1"></i>Descargar</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="far fa-share-square me-1"></i>Compartir</button>
              </div>

              <ul class="nav nav-tabs av-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" data-toggle="tab" href="#tab_tema" role="tab">Tema</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-toggle="tab" href="#tab_curso" role="tab">Curso</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-toggle="tab" href="#tab_exam" role="tab">Examen</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-toggle="tab" href="#tab_calif" role="tab">Calificación</a></li>
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
                        <li><strong>Duración total:</strong> <?= h($courseMeta['total_duration']) ?></li>
                      </ul>
                    </div>
                    <div class="col-md-6">
                      <ul class="list-unstyled mb-0">
                        <li><strong>Subtítulos:</strong> <?= h($courseMeta['captions']) ?></li>
                        <li><strong>Idiomas:</strong> <?= h($courseMeta['languages']) ?></li>
                      </ul>
                    </div>
                  </div>
                  <?php if (!empty($courseSel['descripcion'])): ?>
                    <hr><p class="mb-0"><?= nl2br(h($courseSel['descripcion'])) ?></p>
                  <?php endif; ?>
                </div>
                <div class="tab-pane fade" id="tab_exam" role="tabpanel">
                  <p class="text-muted mb-0">Sin contenido disponible</p>
                </div>
                <div class="tab-pane fade" id="tab_calif" role="tabpanel">
                  <p class="text-muted mb-0">Sin contenido disponible</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cursos + Playlist -->
        <div class="col-lg-4">
          <!-- Cursos -->
          <div class="card av-card shadow-sm mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="m-0">Cursos asignados</h5>
                <span class="badge bg-light text-dark"><?= count($courses) ?> asignados</span>
              </div>

              <div class="list-group">
                <?php if (!$courses): ?>
                  <div class="text-muted small">Aún no tienes cursos asignados.</div>
                <?php else: ?>
                  <?php foreach ($courses as $c):
                    $thumb = $c['imagen_path'] ? asset($c['imagen_path']) : (BASE_URL.'/modules/consola/assets/no-image.png');
                    $isActive = ((int)$c['id'] === $selectedId);
                  ?>
                    <div class="av-course-item <?= $isActive?'active':'' ?>" data-id="<?= (int)$c['id'] ?>">
                      <img src="<?= h($thumb) ?>" class="av-course-thumb" alt="Curso">
                      <div class="flex-fill">
                        <div class="fw-semibold"><?= h($c['nombre']) ?></div>
                        <div class="small text-muted">Activo</div>
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

              <div class="av-list" id="avPlaylist">
                <?php if ($temas): ?>
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
                  <div class="text-danger">Aún no hay temas guardados.</div>
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
