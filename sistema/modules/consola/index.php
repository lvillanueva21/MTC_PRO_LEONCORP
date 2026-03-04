<?php
// modules/consola/index.php
require_once __DIR__.'/../../includes/acl.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/conexion.php';
acl_require_ids([1,6]); verificarPermiso(['Desarrollo','Gerente']);

$u=currentUser();
$nombreCompleto=trim(($u['nombres']??'').' '.($u['apellidos']??''));

$acciones=[
  ['key'=>'servicios',    'label'=>'Gestión de servicios',    'icon'=>'fas fa-cogs',             'color'=>'#1d4ed8','href'=>'servicios/gestion.php'],
  ['key'=>'precios',      'label'=>'Gestión de precios',      'icon'=>'fas fa-tags',             'color'=>'#facc15','href'=>'precios/gestion.php'],
  ['key'=>'cajas',        'label'=>'Gestión de cajas',        'icon'=>'fas fa-boxes',            'color'=>'#a855f7','href'=>'cajas/gestion.php'],
  ['key'=>'usuarios',     'label'=>'Gestión de usuarios',     'icon'=>'fas fa-users',            'color'=>'#0ea5e9','href'=>'usuarios/gestion.php'],
  ['key'=>'comprobantes', 'label'=>'Gestión de comprobantes', 'icon'=>'fas fa-receipt',          'color'=>'#ef4444','href'=>'comprobantes/gestion.php'],
  ['key'=>'cursos',       'label'=>'Gestión de cursos',       'icon'=>'fas fa-graduation-cap',   'color'=>'#8b5cf6','href'=>'cursos/gestion.php'],
  ['key'=>'empresas',     'label'=>'Gestión de empresas',     'icon'=>'fas fa-building',         'color'=>'#111827','href'=>'empresas/gestion.php'],
  ['key'=>'inventarios',  'label'=>'Gestión de inventarios',  'icon'=>'fas fa-clipboard-list',   'color'=>'#a16207','href'=>'inventarios/gestion.php'],
  ['key'=>'examenes',     'label'=>'Gestión de exámenes',     'icon'=>'fas fa-clipboard-check',  'color'=>'#16a34a','href'=>'examenes/gestion.php'],
  ['key'=>'certificados', 'label'=>'Gestión de certificados', 'icon'=>'fas fa-certificate',      'color'=>'#991b1b','href'=>'certificados/gestion.php'],
  ['key'=>'publicidades', 'label'=>'Gestión de publicidades', 'icon'=>'fas fa-bullseye',         'color'=>'#f48fb1','href'=>'publicidades/gestion.php'],
  ['key'=>'comunicados',  'label'=>'Gestión de comunicados',  'icon'=>'fas fa-bullhorn',         'color'=>'#f97316','href'=>'comunicados/gestion.php'],
];



function h($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
include __DIR__.'/../../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/consola/assets/consola.css?v=6">

<div class="content-wrapper">
  <div class="console-hero">
    <div class="container-fluid"><h1>Consola de administración</h1></div>
  </div>

  <section class="content pt-3 pb-4">
    <div class="container-fluid">
      <div class="alert alert-primary py-2 mb-3">
        Hola <?= h($nombreCompleto ?: ($u['usuario']??'Usuario')) ?>, selecciona una opción.
      </div>

      <div class="console-grid">
        <?php foreach($acciones as $a): ?>
          <div class="tile" data-toggle="modal" data-target="#miniModal"
               role="button" tabindex="0"
               aria-label="<?= h($a['label']) ?>"
               data-key="<?= h($a['key']) ?>"
               data-label="<?= h($a['label']) ?>"
               data-color="<?= h($a['color']) ?>"
               data-icon="<?= h($a['icon']) ?>"
               data-href="<?= BASE_URL ?>/modules/consola/<?= h($a['href']) ?>"
               style="--c: <?= h($a['color']) ?>;">
            <div class="tile-ico"><i class="<?= h($a['icon']) ?>"></i></div>
            <div class="tile-label"><?= h($a['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<!-- Modal -->
<div class="modal fade" id="miniModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Mi modal</h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <div id="modal-slot"><div class="text-muted small">Selecciona una opción…</div></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
    </div>
  </div></div>
</div>

<script>
// -------- Helpers para mapear gestion.php -> api.php / gestion.js ----------
function apiFromGestion(url) {
  try { return new URL(url, location.href).href.replace(/gestion\.php(\?.*)?$/,'api.php'); }
  catch { return url.replace(/gestion\.php(\?.*)?$/,'api.php'); }
}
function jsFromGestion(url) {
  try { return new URL(url, location.href).href.replace(/gestion\.php(\?.*)?$/,'gestion.js'); }
  catch { return url.replace(/gestion\.php(\?.*)?$/,'gestion.js'); }
}

// -------- Referencias del modal ----------
const slot    = document.getElementById('modal-slot');
const modalEl = document.getElementById('miniModal');

// ======= Ajusta esto: true en desarrollo, false en producción =======
const DEV_NO_CACHE = true;

// -------- Abrir tiles en el modal + carga del módulo JS ----------
document.addEventListener('click', async function(e){
  const tile = e.target.closest('.tile[data-toggle="modal"][data-target="#miniModal"]');
  if (!tile) return;

  const titulo = tile.dataset.label || 'Mi modal';
  const color  = tile.dataset.color || '#1d4ed8';
  const url    = tile.dataset.href  || '';

  const header  = document.querySelector('#miniModal .modal-header');
  const titleEl = header.querySelector('.modal-title');
  const closeEl = header.querySelector('.close');

  titleEl.textContent = titulo;
  header.style.backgroundColor = color;
  header.style.color = '#fff';
  if (closeEl) closeEl.style.color = '#fff';

  slot.innerHTML = `
    <div class="text-center py-5">
      <div class="spinner-border" role="status"></div>
      <div class="mt-2 small text-muted">Cargando ${titulo}…</div>
    </div>`;

  // ---- Cargar gestion.php SIN caché en dev ----
  const sep  = url.includes('?') ? '&' : '?';
  const bust = DEV_NO_CACHE ? `_=${Date.now()}` : '';
  const gestionURL = url + sep + 'modal=1' + (bust ? '&' + bust : '');

  try {
    const r = await fetch(gestionURL, {
      credentials: 'same-origin',
      cache: DEV_NO_CACHE ? 'no-store' : 'default'
    });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const html = await r.text();
    slot.innerHTML = html || '<div class="alert alert-warning mb-0">Vacío</div>';

    // ---- Import dinámico de gestion.js con cache-buster ----
    const apiURL = apiFromGestion(url);
    let   jsURL  = jsFromGestion(url);

    if (DEV_NO_CACHE) {
      const sepJS = jsURL.includes('?') ? '&' : '?';
      jsURL = jsURL + sepJS + '_=' + Date.now();     // siempre fresco en dev
    } else {
      const sepJS = jsURL.includes('?') ? '&' : '?';
      jsURL = jsURL + sepJS + 'v=8';                 // sube este número cuando cambies JS
    }

    try {
      const mod = await import(jsURL);
      if (typeof mod.init === 'function') mod.init(slot, apiURL);
    } catch (err) {
      console.warn('Módulo sin gestion.js o error de carga:', err);
    }

  } catch {
    slot.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el contenido.</div>';
  }
});

// (Opcional) limpiar estado visual al cerrar
modalEl && modalEl.addEventListener('hidden.bs.modal', () => {
  slot.innerHTML = '<div class="text-muted small">Selecciona una opción…</div>';
});
</script>
<?php include __DIR__.'/../../includes/footer.php'; ?>
