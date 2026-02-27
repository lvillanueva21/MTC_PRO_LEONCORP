<?php
// modules/aula_virtual/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Permisos por rol: Cliente(7), Desarrollo(1), Administración(4), Gerente(6)
acl_require_ids([7,1,4,6]);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function asset($rel){ return $rel ? (BASE_URL.'/'.ltrim($rel,'/')) : ''; }

/** Convierte una URL variada de YouTube a formato embebido (nocookie) */
function yt_embed($url){
  $url = trim((string)$url);
  if ($url === '') return '';
  // youtu.be/ID
  if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)) {
    $id = $m[1];
  }
  // youtube.com/watch?v=ID
  elseif (preg_match('~v=([A-Za-z0-9_-]{6,})~', $url, $m)) {
    $id = $m[1];
  }
  // /embed/ID
  elseif (preg_match('~/embed/([A-Za-z0-9_-]{6,})~', $url, $m)) {
    $id = $m[1];
  } else {
    return $url; // no tocar si no reconocemos
  }
  return "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1";
}

// -------------------------
// 1) Cargar cursos
// -------------------------
$courses = $db->query("
  SELECT id, nombre, descripcion, imagen_path, activo, creado, actualizado
  FROM cr_cursos
  ORDER BY id DESC
")->fetch_all(MYSQLI_ASSOC);

$selectedId = (int)($_GET['curso'] ?? 0);
if (!$selectedId && $courses) $selectedId = (int)$courses[0]['id'];

// Detalle del curso seleccionado
$courseSel = null;
foreach ($courses as $c) { if ((int)$c['id'] === $selectedId) { $courseSel = $c; break; } }

// -------------------------
// 2) Temas del curso
// -------------------------
$temas = [];
if ($courseSel) {
  $st = $db->prepare("
    SELECT id, titulo, clase, video_url, miniatura_path, creado, actualizado
    FROM cr_temas
    WHERE curso_id=?
    ORDER BY id ASC
  ");
  $st->bind_param('i', $selectedId);
  $st->execute();
  $temas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

// --- Datos “de marketing” (placeholders genéricos) ---
$courseMeta = [
  'title'          => $courseSel ? $courseSel['nombre'] : 'Selecciona un curso',
  'rating'         => 4.7,
  'ratings_count'  => 1200,
  'students'       => 82450,
  'total_duration' => '—',
  'updated_ago'    => 'reciente',
  'languages'      => 'ES',
  'captions'       => 'Sí',
  'level'          => 'Todos los niveles',
  'lectures'       => count($temas),
];

// Video inicial
$firstUrl = '';
if ($temas) {
  $firstUrl = yt_embed($temas[0]['video_url'] ?? '');
}

include __DIR__ . '/../../includes/header.php';
?>
<style>
  /* Estética ligera, basada en tu referencia */
  .av-card { border-radius: 14px; }
  .av-badge { font-weight: 700; }
  .av-list .item { cursor:pointer; border-radius: 10px; padding: .55rem .65rem; }
  .av-list .item.active { background:#eef2ff; }
  .av-list .item .title { font-weight:600; }
  .av-list .item .meta { font-size:.85rem; color:#6b7280; }
  .av-section { border-bottom: 1px dashed #e5e7eb; padding-bottom:.3rem; margin-bottom:.3rem; color:#6b7280; text-transform:uppercase; font-size:.8rem; font-weight:700; }
  .av-controls .btn { border-radius: 10px; }
  .av-tabs .nav-link { border:none; color:#64748b; }
  .av-tabs .nav-link.active { color:#111827; border-bottom:2px solid #6366f1; border-radius:0; }
  .ratio-16x9 { position:relative; width:100%; padding-top:56.25%; }
  .ratio-16x9 iframe { position:absolute; inset:0; width:100%; height:100%; border:0; border-radius:12px; }

  .av-course-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 8px; }
  .av-mini { width: 40px; height: 28px; object-fit: cover; border-radius: 4px; }
  .av-course-item { cursor:pointer; border-radius:10px; padding:.5rem; }
  .av-course-item.active { background:#eef2ff; }
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/inicio.php">Inicio</a></li>
              <li class="breadcrumb-item">Aula virtual</li>
              <li class="breadcrumb-item active" aria-current="page"><?= h($courseMeta['title']) ?></li>
            </ol>
          </nav>
          <h1 class="m-0"><?= h($courseMeta['title']) ?></h1>
          <div class="d-flex gap-3 flex-wrap mt-2">
            <span class="text-warning"><i class="fas fa-star"></i> <?= number_format((float)$courseMeta['rating'],1) ?></span>
            <span class="text-muted"><?= number_format((int)$courseMeta['ratings_count']) ?> calificaciones</span>
            <span class="text-muted"><i class="fas fa-user-graduate me-1"></i><?= number_format((int)$courseMeta['students']) ?> alumnos</span>
            <span class="text-muted"><i class="far fa-clock me-1"></i><?= h($courseMeta['total_duration']) ?></span>
            <span class="text-muted"><i class="far fa-calendar-check me-1"></i><?= h($courseMeta['updated_ago']) ?></span>
            <span class="text-muted"><i class="fas fa-language me-1"></i><?= h($courseMeta['languages']) ?></span>
          </div>
        </div>
        <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
          <span class="badge bg-primary av-badge me-1">Mi progreso</span>
          <span id="avProgressText" class="text-muted">0% completado</span>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row g-3">
        <!-- Columna: Reproductor -->
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
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab_overview" role="tab">Overview</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_notes" role="tab">Notas</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_ann" role="tab">Anuncios</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_reviews" role="tab">Reseñas</a></li>
              </ul>

              <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="tab_overview" role="tabpanel">
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
                    <hr>
                    <p class="mb-0"><?= nl2br(h($courseSel['descripcion'])) ?></p>
                  <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab_notes" role="tabpanel">
                  <p class="text-muted mb-2">Escribe notas personales (no se guardan; demo):</p>
                  <textarea class="form-control" rows="4" placeholder="Tus notas…"></textarea>
                </div>

                <div class="tab-pane fade" id="tab_ann" role="tabpanel">
                  <p class="text-muted">No hay anuncios por ahora.</p>
                </div>

                <div class="tab-pane fade" id="tab_reviews" role="tabpanel">
                  <p class="text-muted">Las reseñas estarán disponibles próximamente.</p>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Columna: Cursos + Playlist -->
        <div class="col-lg-4">
          <!-- Selector de curso -->
          <div class="card av-card shadow-sm mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="m-0">Cursos</h5>
                <div class="text-muted small"><?= count($courses) ?> disponibles</div>
              </div>

              <div class="mb-2">
                <select id="avCourseSelect" class="form-select">
                  <?php foreach ($courses as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id']===$selectedId?'selected':'') ?>>
                      <?= h($c['nombre']) ?> <?= ((int)$c['activo']? '':'(inactivo)') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Lista compacta de cursos con miniatura -->
              <div class="list-group">
                <?php if (!$courses): ?>
                  <div class="text-muted small">Aún no hay cursos.</div>
                <?php else: ?>
                  <?php foreach ($courses as $c):
                    $thumb = $c['imagen_path'] ? asset($c['imagen_path']) : (BASE_URL.'/modules/consola/assets/no-image.png');
                    $isActive = ((int)$c['id'] === $selectedId);
                  ?>
                    <div class="av-course-item list-group-item d-flex align-items-center <?= $isActive?'active':'' ?>"
                         data-id="<?= (int)$c['id'] ?>">
                      <img src="<?= h($thumb) ?>" class="av-course-thumb me-2" alt="thumb">
                      <div class="flex-fill">
                        <div class="fw-semibold"><?= h($c['nombre']) ?></div>
                        <div class="small text-muted"><?= (int)$c['activo'] ? 'Activo' : 'Inactivo' ?></div>
                      </div>
                      <i class="fas fa-chevron-right text-muted"></i>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Playlist de temas -->
          <div class="card av-card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="m-0">Contenido del Curso</h5>
                <span class="badge bg-light text-dark" id="avCountBadge"></span>
              </div>

              <div class="av-list" id="avPlaylist">
                <?php
                $total = count($temas); $done = 0; // progreso demo
                if ($temas):
                ?>
                  <div class="av-section">Temas</div>
                  <?php foreach ($temas as $i=>$t):
                    $url = yt_embed($t['video_url'] ?? '');
                    $thumb = $t['miniatura_path'] ? asset($t['miniatura_path']) : (BASE_URL.'/modules/consola/assets/no-image.png');
                  ?>
                    <div class="item d-flex justify-content-between align-items-center mb-1"
                         data-url="<?= h($url) ?>"
                         data-id="<?= (int)$t['id'] ?>">
                      <div class="d-flex align-items-center">
                        <img src="<?= h($thumb) ?>" class="av-mini me-2" alt="mini">
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
    </div><!-- /container-fluid -->
  </section>
</div>

<script>
(function(){
  const $player = document.getElementById('avPlayer');
  const $playlist = document.getElementById('avPlaylist');
  const $progressText = document.getElementById('avProgressText');
  const $countBadge = document.getElementById('avCountBadge');
  const $courseSelect = document.getElementById('avCourseSelect');

  // Progreso (demo)
  const total = <?= (int)$total ?>;
  let done = 0;

  function updateProgress(){
    const pct = total ? Math.round((done/total)*100) : 0;
    $progressText.textContent = pct + '% completado';
    $countBadge.textContent = done + '/' + total + ' completadas';
  }

  function selectItem(el){
    // Desactivar previos
    Array.from($playlist.querySelectorAll('.item.active')).forEach(i=>i.classList.remove('active'));
    el.classList.add('active');
    const url = el.getAttribute('data-url') || '';
    if (url) { $player.src = url; }
    // Marcar como realizado (solo visual/demo)
    const icon = el.querySelector('i');
    if (icon && icon.classList.contains('far')) {
      icon.classList.remove('far','fa-play-circle','text-muted');
      icon.classList.add('fas','fa-check-circle','text-success');
      done = Math.min(done+1, total);
      updateProgress();
    }
  }

  // Click en playlist
  $playlist.addEventListener('click', function(e){
    const item = e.target.closest('.item');
    if (item) selectItem(item);
  });

  // Cambiar curso (select)
  $courseSelect?.addEventListener('change', function(){
    const id = this.value || '';
    if (id) {
      const url = new URL(location.href);
      url.searchParams.set('curso', id);
      location.href = url.toString();
    }
  });

  // Click en tarjeta de curso (lista)
  document.querySelectorAll('.av-course-item').forEach(el=>{
    el.addEventListener('click', function(){
      const id = this.getAttribute('data-id') || '';
      if (id) {
        const url = new URL(location.href);
        url.searchParams.set('curso', id);
        location.href = url.toString();
      }
    });
  });

  // Activar el primer tema
  const firstItem = $playlist.querySelector('.item');
  if (firstItem) firstItem.classList.add('active');

  updateProgress();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
