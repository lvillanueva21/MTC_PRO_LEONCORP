<?php
// dashboard/clientes/publicidades.php
// Página simple de publicidades para clientes: grilla 2 cols (1 en móviles) + modal con zoom/descarga.

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

// 1) Encontrar un grupo aplicable al usuario (el más reciente)
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

// 2) Cargar publicidades activas del grupo (solo con imagen)
$ads = [];
if ($grupo) {
  $st = $mysqli->prepare("
    SELECT gi.id, gi.orden, p.id AS pub_id, p.titulo, p.imagen_path
    FROM pb_grupo_item gi
    JOIN pb_publicidades p ON p.id = gi.publicidad_id
    WHERE gi.grupo_id = ? AND p.activo = 1
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
/* ==== Estilos scopeados (no tocan AdminLTE) ==== */
.pb-simple {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}
.pb-simple .pb-title { font-weight: 700; margin: 0 0 .25rem 0; }
.pb-simple .pb-sub   { color: #6b7280; margin-bottom: .75rem; }

.pb-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}
@media (max-width: 992px) {
  .pb-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
  .pb-grid { grid-template-columns: 1fr; }
}
.pb-item {
  border-radius: 14px;
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

/* Mensaje amigable */
.pb-empty {
  border-radius: 10px;
  padding: .9rem 1rem;
  margin: .25rem 0 0 0;
  background: #f8fafc;
  border: 1px dashed #e5e7eb;
  color: #334155;
}

/* ==== Modal propio (sin dependencias) ==== */
.pb-modal {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, .65);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1050; /* por encima de la UI */
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
.pb-modal-header .title {
  font-weight: 700; font-size: 14px;
}
.pb-modal-header .spacer { flex: 1 1 auto; }
.pb-btn {
  border: 1px solid #e5e7eb; background:#fff; color:#111827;
  padding: .35rem .65rem; border-radius: 8px; cursor: pointer; font-size: 13px;
}
.pb-btn:hover { background:#f3f4f6; }
.pb-btn.primary { border-color:#2563eb; color:#1d4ed8; }

.pb-modal-body {
  position: relative;
  padding: 0;
  overflow: hidden; /* el wrap interior manejará el scroll */
  display: flex;
  flex: 1 1 auto;
}
.pb-imgwrap {
  position: relative;
  overflow: auto;
  width: 100%;
  height: 100%;
  background: #0b1220; /* leve contraste detrás de la imagen */
  display: flex; align-items: center; justify-content: center;
}
.pb-modal-img {
  max-width: 100%;
  max-height: 90vh;
  height: auto; width: auto;
  transition: transform .2s ease, cursor .2s ease;
  cursor: zoom-in;
}
.pb-modal-img.zoomed {
  transform: scale(1.6);
  cursor: zoom-out;
}

/* Botón cerrar (X) */
.pb-close {
  border: 0; background: transparent; font-size: 20px; line-height: 1; cursor: pointer;
  color: #6b7280;
}
.pb-close:hover { color: #111827; }
</style>

<div class="pb-simple">
  <div class="pb-title">Bienvenido a MTC PRO</div>
  <?php if (!$grupo || !$ads): ?>
    <div class="pb-empty">
      No tienes publicidades asignadas por ahora. Cuando tu empresa o rol reciba una campaña, la verás aquí ✨
    </div>
  <?php else: ?>
    <div class="pb-grid" id="pbGrid">
      <?php foreach ($ads as $ad): ?>
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
  const modal      = document.getElementById('pbModal');
  const modalImg   = document.getElementById('pbModalImg');
  const modalTitle = document.getElementById('pbModalTitle');
  const btnClose   = document.getElementById('pbClose');
  const btnDownload= document.getElementById('pbDownload');
  const imgWrap    = document.getElementById('pbImgWrap');

  // Abre modal desde una <img> de la grilla
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
    // Limpieza opcional
    modalImg.classList.remove('zoomed');
    modalImg.removeAttribute('src');
  }

  // Cerrar con botón X
  btnClose?.addEventListener('click', closeModal);

  // Cerrar al clickear fuera del diálogo (en el fondo oscuro)
  modal?.addEventListener('click', (e)=>{
    if (e.target === modal) closeModal();
  });

  // ESC para cerrar
  document.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
  });

  // Zoom: clic sobre la imagen
  modalImg?.addEventListener('click', ()=>{
    modalImg.classList.toggle('zoomed');
    // al hacer zoom, permitir scroll en el contenedor
    if (modalImg.classList.contains('zoomed')) {
      imgWrap.style.cursor = 'grab';
    } else {
      imgWrap.style.cursor = 'default';
      imgWrap.scrollTop = 0; imgWrap.scrollLeft = 0;
    }
  });

  // (Opcional) arrastre mientras está con zoom para "paneo"
  let isPanning = false, startX=0, startY=0, scrollL=0, scrollT=0;
  imgWrap.addEventListener('mousedown', (e)=>{
    if (!modalImg.classList.contains('zoomed')) return;
    isPanning = true;
    imgWrap.style.cursor = 'grabbing';
    startX = e.clientX; startY = e.clientY;
    scrollL = imgWrap.scrollLeft; scrollT = imgWrap.scrollTop;
    e.preventDefault();
  });
  imgWrap.addEventListener('mousemove', (e)=>{
    if (!isPanning) return;
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    imgWrap.scrollLeft = scrollL - dx;
    imgWrap.scrollTop  = scrollT - dy;
  });
  ['mouseup','mouseleave'].forEach(ev=>{
    imgWrap.addEventListener(ev, ()=>{
      isPanning = false;
      if (modalImg.classList.contains('zoomed')) imgWrap.style.cursor = 'grab';
      else imgWrap.style.cursor = 'default';
    });
  });
})();
</script>
