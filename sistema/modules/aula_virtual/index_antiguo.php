<?php
// modules/aula_virtual/index.php

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Permisos por ID de rol (ajusta a gusto): Cliente(7), Desarrollo(1), Administración(4), Gerente(6)
acl_require_ids([7,1,4,6]);

// -------------------------
// Datos de ejemplo (sin BD)
// -------------------------
$course = [
  'title'          => 'Cómo Hablar con Cualquiera sin Hacerlo Incómodo',
  'rating'         => 4.5,
  'ratings_count'  => 14115,
  'students'       => 321195,
  'total_duration' => '1.2 h',
  'updated_ago'    => 'hace 3 días',
  'languages'      => 'ES, EN',
  'captions'       => 'Sí',
  'level'          => 'Todos los niveles',
  'lectures'       => 25
];

// Playlist de ejemplo (pega tus links de YouTube en 'url')
// Formato recomendado: https://www.youtube.com/embed/VIDEO_ID
$sections = [
  [
    'title' => 'Course Intro',
    'items' => [
      // antes: watch?v=iUtnZpzkbG8&list=PL...
      ['id'=>'v1','title'=>'Bienvenida','duration'=>'6 min','completed'=>true,
       'url'=>'https://www.youtube-nocookie.com/embed/iUtnZpzkbG8?rel=0&modestbranding=1&list=PLGoWuvyH709vpTCVrjaJtaaFfite9U6u8'],
      // antes: watch?v=mLwlGsRhNIU&list=PL...&index=2
      ['id'=>'v2','title'=>'Ajustes de expectativas','duration'=>'8 min','completed'=>false,
       'url'=>'https://www.youtube-nocookie.com/embed/mLwlGsRhNIU?rel=0&modestbranding=1&list=PLGoWuvyH709vpTCVrjaJtaaFfite9U6u8&index=2'],
    ]
  ],
  [
    'title' => 'Habilidades Base',
    'items' => [
      ['id'=>'v3','title'=>'Respiración y postura','duration'=>'12 min','completed'=>false,
       'url'=>'https://www.youtube.com/embed/9WC-8F8-1NQ?si=85tav86L1E3WMk2q'], // TODO
      // antes: watch?v=muczNvx9fgg&list=PL...&index=3
      ['id'=>'v4','title'=>'Escucha activa','duration'=>'9 min','completed'=>false,
       'url'=>'https://www.youtube-nocookie.com/embed/muczNvx9fgg?rel=0&modestbranding=1&list=PLGoWuvyH709vpTCVrjaJtaaFfite9U6u8&index=3'],
      // antes: watch?v=cEHP_LeBeyQ&list=PL...&index=5
      ['id'=>'v5','title'=>'Preguntas que abren','duration'=>'11 min','completed'=>false,
       'url'=>'https://www.youtube-nocookie.com/embed/cEHP_LeBeyQ?rel=0&modestbranding=1&list=PLGoWuvyH709vpTCVrjaJtaaFfite9U6u8&index=5'],
    ]
  ],
  [
    'title' => 'Práctica guiada',
    'items' => [
      ['id'=>'v6','title'=>'Role-play 1: Inicio','duration'=>'10 min','completed'=>false,
       'url'=>'https://www.youtube.com/embed/vlDOjTaaEdA?si=OMAkTD4DW1P8slCn'], // TODO
      ['id'=>'v7','title'=>'Role-play 2: Mantener la charla','duration'=>'12 min','completed'=>false,
       'url'=>'https://www.youtube.com/embed/D7tit_JZKvk?si=ty8dnQ_VgW69Yu48'], // TODO
    ]
  ],
  [
    'title' => 'Cierre',
    'items' => [
      // antes: watch?v=NKZ5Nvoj9s4
      ['id'=>'v8','title'=>'Plan de 7 días','duration'=>'6 min','completed'=>false,
       'url'=>'https://www.youtube-nocookie.com/embed/NKZ5Nvoj9s4?rel=0&modestbranding=1'],
      // antes: watch?v=kn2ItyqyxFU
      ['id'=>'v9','title'=>'Siguientes pasos','duration'=>'4 min','completed'=>false,
       'url'=>'https://www.youtube-nocookie.com/embed/kn2ItyqyxFU?rel=0&modestbranding=1'],
    ]
  ],
];

// Video inicial
$firstUrl = $sections[0]['items'][0]['url'] ?? '';
include __DIR__ . '/../../includes/header.php';
?>

<style>
  /* Estética ligera para emular el UI de referencia */
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
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-7">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/inicio.php">Inicio</a></li>
              <li class="breadcrumb-item">Cursos</li>
              <li class="breadcrumb-item active" aria-current="page">Comunicación</li>
            </ol>
          </nav>
          <h1 class="m-0"><?= htmlspecialchars($course['title']) ?></h1>
          <div class="d-flex gap-3 flex-wrap mt-2">
            <span class="text-warning"><i class="fas fa-star"></i> <?= number_format($course['rating'],1) ?></span>
            <span class="text-muted"><?= number_format($course['ratings_count']) ?> calificaciones</span>
            <span class="text-muted"><i class="fas fa-user-graduate me-1"></i><?= number_format($course['students']) ?> alumnos</span>
            <span class="text-muted"><i class="far fa-clock me-1"></i><?= $course['total_duration'] ?></span>
            <span class="text-muted"><i class="far fa-calendar-check me-1"></i><?= $course['updated_ago'] ?></span>
            <span class="text-muted"><i class="fas fa-language me-1"></i><?= $course['languages'] ?></span>
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
        <!-- Columna: Video y tabs -->
        <div class="col-lg-8">
          <div class="card av-card shadow-sm">
            <div class="card-body">
              <div class="ratio-16x9 mb-3">
                <iframe id="avPlayer" src="<?= htmlspecialchars($firstUrl) ?>" allowfullscreen></iframe>
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
                        <li><strong>Nivel:</strong> <?= htmlspecialchars($course['level']) ?></li>
                        <li><strong>Clases:</strong> <?= (int)$course['lectures'] ?></li>
                        <li><strong>Duración total:</strong> <?= htmlspecialchars($course['total_duration']) ?></li>
                      </ul>
                    </div>
                    <div class="col-md-6">
                      <ul class="list-unstyled mb-0">
                        <li><strong>Subtítulos:</strong> <?= htmlspecialchars($course['captions']) ?></li>
                        <li><strong>Idiomas:</strong> <?= htmlspecialchars($course['languages']) ?></li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade" id="tab_notes" role="tabpanel">
                  <p class="text-muted mb-2">Escribe notas personales (no se guardan; solo demostración):</p>
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

        <!-- Columna: Contenido del curso (playlist) -->
        <div class="col-lg-4">
          <div class="card av-card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="m-0">Contenido del Curso</h5>
                <span class="badge bg-light text-dark" id="avCountBadge"></span>
              </div>

              <div class="av-list" id="avPlaylist">
                <?php
                $total = 0; $done = 0;
                foreach ($sections as $sec):
                  $secCount = count($sec['items']); $total += $secCount;
                ?>
                  <div class="av-section"><?= htmlspecialchars($sec['title']) ?></div>
                  <?php foreach ($sec['items'] as $it):
                    $isDone = !empty($it['completed']);
                    if ($isDone) $done++;
                  ?>
                    <div class="item d-flex justify-content-between align-items-center mb-1"
                         data-url="<?= htmlspecialchars($it['url']) ?>"
                         data-id="<?= htmlspecialchars($it['id']) ?>">
                      <div>
                        <div class="title"><?= htmlspecialchars($it['title']) ?></div>
                        <div class="meta"><?= htmlspecialchars($it['duration']) ?></div>
                      </div>
                      <div class="ms-2">
                        <?php if ($isDone): ?>
                          <i class="fas fa-check-circle text-success"></i>
                        <?php else: ?>
                          <i class="far fa-play-circle text-muted"></i>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </div>

              <hr>
              <div class="small text-muted">Tip: haz clic en cualquier lección para reproducirla sin salir de esta página.</div>
            </div>
          </div>
        </div>

      </div><!-- /row -->
    </div><!-- /container-fluid -->
  </section>
</div>

<script>
// --- JS simple para manejar playlist ---
(function(){
  const $player = document.getElementById('avPlayer');
  const $playlist = document.getElementById('avPlaylist');
  const $progressText = document.getElementById('avProgressText');
  const $countBadge = document.getElementById('avCountBadge');

  // Contadores iniciales (calculados en PHP, reflejados en atributos data si prefieres)
  const total = <?= (int)$total ?>;
  let done = <?= (int)$done ?>;

  function updateProgress(){
    const pct = total ? Math.round((done/total)*100) : 0;
    $progressText.textContent = pct + '% completado';
    $countBadge.textContent = done + '/' + total + ' completadas';
  }

  function selectItem(el){
    // Quitar activo
    Array.from($playlist.querySelectorAll('.item.active')).forEach(i=>i.classList.remove('active'));
    // Activar
    el.classList.add('active');
    // Cambiar video
    const url = el.getAttribute('data-url') || '';
    if (url) { $player.src = url; }
    // Marcar como "hecho" visualmente si no lo estaba
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

  // Activar primero visible
  const firstItem = $playlist.querySelector('.item');
  if (firstItem) firstItem.classList.add('active');

  updateProgress();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
