<?php
// dashboard/clientes/publicidades.php
// Carrusel 2x (2 columnas por slide, 1 en móviles) con modal (zoom + descargar).

require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

$mysqli = db();
$mysqli->set_charset('utf8mb4');

$me        = currentUser();
$uid       = (int)($me['id'] ?? 0);
$empresaId = (int)($me['empresa']['id'] ?? ($me['id_empresa'] ?? 0));

function imgUrl(?string $p) {
  if (!$p) return null;
  if (preg_match('~^https?://~i', $p)) return $p;
  return (defined('BASE_URL') ? BASE_URL : '') . '/' . ltrim($p, '/');
}

// 1) Buscar un grupo aplicable al usuario
$grupo = null;
$sqlGrupo = "
  SELECT g.id, g.nombre, g.layout_slots
  FROM pb_grupos g
  WHERE g.activo = 1
    AND EXISTS (
      SELECT 1
      FROM pb_grupo_target t
      WHERE t.grupo_id = g.id
        AND (
             t.tipo = 'TODOS'
          OR (t.tipo = 'USUARIO'     AND t.usuario_id  = ?)
          OR (t.tipo = 'EMPRESA'     AND t.empresa_id  = ?)
          OR (t.tipo = 'ROL'         AND EXISTS (SELECT 1 FROM mtp_usuario_roles ur WHERE ur.id_usuario = ? AND ur.id_rol = t.rol_id))
          OR (t.tipo = 'EMPRESA_ROL' AND t.empresa_id  = ? AND EXISTS (SELECT 1 FROM mtp_usuario_roles ur WHERE ur.id_usuario = ? AND ur.id_rol = t.rol_id))
        )
    )
  ORDER BY g.id DESC
  LIMIT 1
";
if ($st = $mysqli->prepare($sqlGrupo)) {
  $st->bind_param('iiiii', $uid, $empresaId, $uid, $empresaId, $uid);
  $st->execute();
  $grupo = $st->get_result()->fetch_assoc();
  $st->close();
}

// 2) Cargar imágenes de publicidades activas dentro del grupo
$ads = [];
if ($grupo) {
  $st = $mysqli->prepare("
    SELECT gi.id, gi.orden, p.id AS pub_id, p.titulo, p.imagen_path
    FROM pb_grupo_item gi
    JOIN pb_publicidades p ON p.id = gi.publicidad_id
    WHERE gi.grupo_id = ? AND p.activo = 1 AND p.imagen_path IS NOT NULL
    ORDER BY gi.orden ASC, gi.id ASC
  ");
  $st->bind_param('i', $grupo['id']);
  $st->execute();
  $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  $st->close();

  foreach ($rows as $r) {
    $src = imgUrl($r['imagen_path'] ?? null);
    if ($src) {
      $ads[] = [
        'id'    => (int)$r['pub_id'],
        'title' => $r['titulo'] ?? '',
        'src'   => $src,
      ];
    }
  }
}
?>
<style>
/* ===== Bloque principal ===== */
.pb-carr {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}
.pb-carr .pb-title { font-weight: 700; margin: 0 0 .25rem 0; }
.pb-carr .pb-sub   { color: #6b7280; margin-bottom: .5rem; }

/* ===== Mensaje vacío ===== */
.pb-empty {
  border-radius: 10px;
  padding: .9rem 1rem;
  background: #f8fafc;
  border: 1px dashed #e5e7eb;
  color: #334155;
}

/* ===== Carrusel ===== */
.pb-viewport {
  position: relative;
  overflow: hidden;
}
.pb-track {
  display: flex;
  width: 100%;
  transition: transform .5s ease;
}
.pb-slide {
  flex: 0 0 100%;
  padding: 2px; /* separador mínimo para que no se peguen los bordes */
}
.pb-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
@media (max-width: 768px) {
  .pb-grid-2 { grid-template-columns: 1fr; }
}

.pb-item {
  border-radius: 12px;
  overflow: hidden;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  cursor: zoom-in;
}
.pb-item img {
  display: block;
  width: 100%;
  height: auto;
}

/* Controles */
.pb-ctrl {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 10px;
}
.pb-btn {
  border: 1px solid #e5e7eb; background:#fff; color:#111827;
  padding: .35rem .65rem; border-radius: 8px; cursor: pointer; font-size: 13px;
}
.pb-btn:hover { background:#f3f4f6; }
.pb-dots { display:flex; gap:6px; }
.pb-dot { width:8px; height:8px; background:#cbd5e1; border-radius:50%; cursor:pointer; }
.pb-dot.active { background:#4f46e5; }

/* ===== Modal nativo (sin dep.) ===== */
.pb-modal {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, .65);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}
.pb-modal.show { display: flex; }
.pb-modal-dialog {
  background: #fff;
  border-radius: 12px;
  max-width: min(1080px, 92vw);
  width: 100%;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 30px rgba(0,0,0,.25);
}
.pb-modal-header {
  display: flex; align-items: center; gap: 8px;
  padding: .6rem .8rem;
  border-bottom: 1px solid #e5e7eb;
}
.pb-modal-header .title { font-weight: 700; font-size: 14px; }
.pb-modal-header .spacer { flex: 1 1 auto; }
.pb-btn.primary { border-color:#2563eb; color:#1d4ed8; }
.pb-close { border:0; background:transparent; font-size:20px; line-height:1; cursor:pointer; color:#6b7280; }
.pb-close:hover { color:#111827; }

.pb-modal-body { position: relative; padding: 0; overflow: hidden; display: flex; flex: 1 1 auto; }
.pb-imgwrap {
  position: relative; overflow: auto; width: 100%; height: 100%;
  background: #0b1220; display: flex; align-items: center; justify-content: center;
}
.pb-modal-img {
  max-width: 100%; max-height: 90vh; height: auto; width: auto;
  transition: transform .2s ease, cursor .2s ease;
  cursor: zoom-in;
}
.pb-modal-img.zoomed { transform: scale(1.6); cursor: zoom-out; }
</style>

<div class="pb-carr">
  <div class="pb-title">Bienvenido a MTC PRO</div>
  <div class="pb-sub">Inicio</div>

  <?php if (!$grupo || !$ads): ?>
    <div class="pb-empty">No tienes publicidades asignadas por ahora. Cuando tu empresa o rol reciba una campaña, la verás aquí ✨</div>
  <?php else:
    // Chunk en pares (2 por slide)
    $slides = [];
    for ($i=0; $i<count($ads); $i+=2) { $slides[] = array_slice($ads, $i, 2); }
  ?>
    <div class="pb-viewport" id="pbViewport">
      <div class="pb-track" id="pbTrack" style="transform:translateX(0%);">
        <?php foreach ($slides as $slide): ?>
          <div class="pb-slide">
            <div class="pb-grid-2">
              <?php foreach ($slide as $ad): ?>
                <figure class="pb-item">
                  <img
                    src="<?= htmlspecialchars($ad['src']) ?>"
                    alt="<?= htmlspecialchars($ad['title'] ?: 'Publicidad') ?>"
                    loading="lazy"
                    data-title="<?= htmlspecialchars($ad['title'] ?: 'Publicidad') ?>"
                    onclick="window.__pbOpenModal && window.__pbOpenModal(this)"
                    onerror="this.closest('.pb-item')?.remove();"
                  >
                </figure>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (count($slides) > 1): ?>
      <div class="pb-ctrl">
        <button class="pb-btn" type="button" id="pbPrev">« Anterior</button>
        <div class="pb-dots" id="pbDots">
          <?php for ($i=0; $i<count($slides); $i++): ?>
            <span class="pb-dot<?= $i===0 ? ' active':'' ?>" data-i="<?= $i ?>"></span>
          <?php endfor; ?>
        </div>
        <button class="pb-btn" type="button" id="pbNext">Siguiente »</button>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal -->
<div class="pb-modal" id="pbModal" aria-hidden="true">
  <div class="pb-modal-dialog" role="dialog" aria-modal="true">
    <div class="pb-modal-header">
      <div class="title" id="pbModalTitle">Publicidad</div>
      <div class="spacer"></div>
      <a id="pbDownload" class="pb-btn primary" href="#" download>Descargar</a>
      <button class="pb-close" type="button" aria-label="Cerrar" id="pbClose">&times;</button>
    </div>
    <div class="pb-modal-body">
      <div class="pb-imgwrap" id="pbImgWrap">
        <img id="pbModalImg" class="pb-modal-img" src="" alt="Publicidad">
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  // ===== Modal (zoom + descarga) =====
  const modal      = document.getElementById('pbModal');
  const modalImg   = document.getElementById('pbModalImg');
  const modalTitle = document.getElementById('pbModalTitle');
  const btnClose   = document.getElementById('pbClose');
  const btnDownload= document.getElementById('pbDownload');
  const imgWrap    = document.getElementById('pbImgWrap');

  window.__pbOpenModal = function(imgEl){
    if (!imgEl) return;
    const src   = imgEl.getAttribute('src');
    const title = imgEl.getAttribute('data-title') || 'Publicidad';

    modalImg.classList.remove('zoomed');
    modalImg.src = src;
    modalImg.alt = title;
    modalTitle.textContent = title || 'Publicidad';
    btnDownload.href = src;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden','false');
  };

  function closeModal(){
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden','true');
    modalImg.classList.remove('zoomed');
    modalImg.removeAttribute('src');
  }
  btnClose?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (e)=>{ if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape' && modal.classList.contains('show')) closeModal(); });
  modalImg?.addEventListener('click', ()=>{
    modalImg.classList.toggle('zoomed');
    if (modalImg.classList.contains('zoomed')) { imgWrap.style.cursor = 'grab'; }
    else { imgWrap.style.cursor = 'default'; imgWrap.scrollTop = 0; imgWrap.scrollLeft = 0; }
  });
  let isPanning = false, startX=0, startY=0, scrollL=0, scrollT=0;
  imgWrap.addEventListener('mousedown', (e)=>{
    if (!modalImg.classList.contains('zoomed')) return;
    isPanning = true; imgWrap.style.cursor='grabbing';
    startX=e.clientX; startY=e.clientY; scrollL=imgWrap.scrollLeft; scrollT=imgWrap.scrollTop; e.preventDefault();
  });
  imgWrap.addEventListener('mousemove', (e)=>{
    if (!isPanning) return;
    imgWrap.scrollLeft = scrollL - (e.clientX - startX);
    imgWrap.scrollTop  = scrollT  - (e.clientY - startY);
  });
  ['mouseup','mouseleave'].forEach(ev=>{
    imgWrap.addEventListener(ev, ()=>{
      isPanning = false;
      imgWrap.style.cursor = modalImg.classList.contains('zoomed') ? 'grab' : 'default';
    });
  });

  // ===== Carrusel 2x =====
  const track   = document.getElementById('pbTrack');
  const dotsBox = document.getElementById('pbDots');
  const prevBtn = document.getElementById('pbPrev');
  const nextBtn = document.getElementById('pbNext');

  if (!track) return;

  const slidesCount = track.children.length;
  let i = 0;
  let timer = null;

  function render(idx){
    i = (idx + slidesCount) % slidesCount;
    track.style.transform = `translateX(-${i * 100}%)`;
    if (dotsBox) {
      [...dotsBox.children].forEach((d,k)=>d.classList.toggle('active', k===i));
    }
  }

  function startAuto(){
    stopAuto();
    if (slidesCount > 1) {
      timer = setInterval(()=> render(i+1), 5000);
    }
  }
  function stopAuto(){ if (timer){ clearInterval(timer); timer=null; } }

  prevBtn?.addEventListener('click', ()=>{ render(i-1); startAuto(); });
  nextBtn?.addEventListener('click', ()=>{ render(i+1); startAuto(); });
  dotsBox?.addEventListener('click', (e)=>{
    const d = e.target.closest('.pb-dot'); if (!d) return;
    const k = parseInt(d.dataset.i, 10); if (!Number.isNaN(k)) { render(k); startAuto(); }
  });

  // Pausa al pasar el mouse sobre el carrusel
  const viewport = document.getElementById('pbViewport');
  viewport?.addEventListener('mouseenter', stopAuto);
  viewport?.addEventListener('mouseleave', startAuto);

  render(0);
  startAuto();
})();
</script>
