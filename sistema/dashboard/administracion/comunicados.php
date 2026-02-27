<?php
// dashboard/administracion/comunicados.php
// Dashboard Admin (responsivo, sin romper AdminLTE) + comunicado con modal/zoom

require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

$mysqli = db();
$mysqli->set_charset('utf8mb4');

$me        = currentUser();
$uid       = (int)($me['id'] ?? 0);
$empresaId = (int)($me['empresa']['id'] ?? ($me['id_empresa'] ?? 0));

function table_exists(mysqli $db, string $name): bool {
  $name = $db->real_escape_string($name);
  $rs = $db->query("SHOW TABLES LIKE '{$name}'");
  return $rs && $rs->num_rows > 0;
}
function imgUrl(?string $p) {
  if (!$p) return null;
  if (preg_match('~^https?://~i', $p)) return $p;
  return (defined('BASE_URL') ? BASE_URL : '').'/'.ltrim($p, '/');
}

$comunicados = [];
if (
  table_exists($mysqli, 'com_comunicados') &&
  table_exists($mysqli, 'com_comunicado_target')
) {
  $now = date('Y-m-d H:i:s');
  $sql = "
    SELECT c.id, c.titulo, c.cuerpo, c.imagen_path, c.fecha_limite, c.fecha_inicio, c.fecha_fin, c.creado
    FROM com_comunicados c
    WHERE c.activo = 1
      AND (c.fecha_inicio IS NULL OR c.fecha_inicio <= ?)
      AND (c.fecha_fin    IS NULL OR c.fecha_fin    >= ?)
      AND EXISTS (
        SELECT 1
        FROM com_comunicado_target t
        WHERE t.comunicado_id = c.id
          AND (
               t.tipo = 'TODOS'
            OR (t.tipo = 'USUARIO'     AND t.usuario_id  = ?)
            OR (t.tipo = 'EMPRESA'     AND t.empresa_id  = ?)
            OR (t.tipo = 'ROL'         AND EXISTS (SELECT 1 FROM mtp_usuario_roles ur WHERE ur.id_usuario = ? AND ur.id_rol = t.rol_id))
            OR (t.tipo = 'EMPRESA_ROL' AND t.empresa_id  = ? AND EXISTS (SELECT 1 FROM mtp_usuario_roles ur WHERE ur.id_usuario = ? AND ur.id_rol = t.rol_id))
          )
      )
    ORDER BY c.creado DESC
    LIMIT 20
  ";
  if ($st = $mysqli->prepare($sql)) {
    $st->bind_param('ssiiiii', $now, $now, $uid, $empresaId, $uid, $empresaId, $uid);
    if ($st->execute()) $comunicados = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
  }
}
?>
<style>
/* ===== Estilos scopeados: NO tocan AdminLTE ni el navbar ===== */
.dash-admin .card { border-radius: 12px; }
.dash-admin .card-title { font-weight: 700; margin-bottom: .5rem; }
.dash-admin .muted { color:#6b7280; }

/* Comunicados */
.dash-admin .cm-wrap { gap: 12px; }
.dash-admin .cm-body { max-height: 220px; overflow:auto; }
.dash-admin .cm-thumb { width:100%; height:auto; border-radius:10px; border:1px solid #e5e7eb; object-fit:cover; }
.dash-admin .cm-figure { max-width: 280px; }

/* Dots carrusel */
.dash-admin .cm-dots { display:flex; gap:6px; }
.dash-admin .cm-dot { width:8px; height:8px; border-radius:50%; background:#cbd5e1; cursor:pointer; }
.dash-admin .cm-dot.active { background:#4f46e5; }

/* Modal de comunicado */
.dash-admin .cm-modal-title { font-weight: 800; }
.dash-admin .cm-modal-body { display:grid; gap:12px; }
@media (min-width: 768px){
  .dash-admin .cm-modal-body { grid-template-columns: 1fr 1.2fr; }
}
.dash-admin .cm-modal-imgwrap {
  border:1px solid #e5e7eb; border-radius:10px; padding:6px;
  overflow:auto; /* permite desplazar cuando está con zoom */
  max-height:60vh;
}
.dash-admin .cm-modal-img { max-width:100%; height:auto; cursor:zoom-in; transition:transform .2s ease; }
.dash-admin .cm-modal-img.zoomed { cursor:zoom-out; transform: scale(1.6); transform-origin: center center; }

/* Tarjetas pequeñas: altura mínima agradable pero flexible */
.dash-admin .kpi-card { min-height: 140px; }

/* Grilla 2×2 de “Clientes …” dentro de una card */
.dash-admin .mini-grid { display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
@media (max-width: 575.98px){ .dash-admin .mini-grid { grid-template-columns: 1fr; } }
.dash-admin .mini-box {
  padding:12px; border:1px dashed #e5e7eb; border-radius:10px; text-align:center;
  min-height: 86px; display:flex; align-items:center; justify-content:center; flex-direction:column;
}
</style>

<div class="dash-admin">
  <div class="row g-3">

    <!-- Columna izquierda -->
    <div class="col-12 col-lg-8">

      <!-- Comunicados -->
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h5 class="card-title m-0">COMUNICADOS</h5>
            <small id="cm-deadline" class="text-muted"></small>
          </div>

          <?php if (!$comunicados): ?>
            <div class="alert alert-light border mb-0">
              No tienes comunicados asignados por ahora. Cuando gerencia o desarrollo te envíen uno, aparecerá aquí ✨
            </div>
          <?php else:
            $c0   = $comunicados[0];
            $img0 = imgUrl($c0['imagen_path'] ?? null);
          ?>
            <div class="cm-wrap d-flex flex-column flex-md-row">
              <div class="flex-grow-1">
                <div id="cm-title" class="fw-bold mb-1"><?= htmlspecialchars($c0['titulo'] ?? '(Sin título)') ?></div>
                <div id="cm-body" class="cm-body">
                  <?= !empty($c0['cuerpo']) ? nl2br(htmlspecialchars($c0['cuerpo'])) : '<em class="muted">(Sin cuerpo)</em>' ?>
                </div>
              </div>

              <div class="cm-figure ms-md-3 mt-3 mt-md-0">
                <?php if ($img0): ?>
                  <img id="cm-thumb" class="cm-thumb" src="<?= $img0 ?>" alt="Imagen del comunicado">
                  <div class="d-flex gap-2 mt-2">
                    <button id="cm-expand" class="btn btn-sm btn-outline-primary" type="button">Ampliar</button>
                    <a id="cm-download" class="btn btn-sm btn-outline-secondary" href="<?= $img0 ?>" download>Descargar</a>
                  </div>
                <?php else: ?>
                  <div class="mini-box" style="min-height:160px">Sin imagen</div>
                <?php endif; ?>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3">
              <div class="btn-group">
                <button id="cm-prev" class="btn btn-sm btn-light" type="button">« Anterior</button>
                <button id="cm-next" class="btn btn-sm btn-light" type="button">Siguiente »</button>
              </div>
              <div id="cm-dots" class="cm-dots"></div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Ingresos vs Egresos -->
      <div class="card mt-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <strong>Ingresos VS Egresos</strong>
            <button class="btn btn-sm btn-outline-primary">Selecciona una fecha</button>
          </div>
          <div class="text-muted"
               style="height:280px;display:flex;align-items:center;justify-content:center;border:1px dashed #e5e7eb;border-radius:8px;margin-top:.5rem;">
            (Aquí irá el gráfico principal)
          </div>
          <div class="d-flex justify-content-between mt-2">
            <small>Total ingresos: —</small><small>Total egresos: —</small>
          </div>
        </div>
      </div>

    </div>

    <!-- Columna derecha -->
    <div class="col-12 col-lg-4">

      <div class="card kpi-card">
        <div class="card-body">
          <h6 class="card-title">Ganancia</h6>
          <div class="muted">—</div>
        </div>
      </div>

      <div class="card kpi-card mt-3">
        <div class="card-body">
          <h6 class="card-title">Ventas (Ingresos)</h6>
          <div class="muted">—</div>
        </div>
      </div>

      <div class="card kpi-card mt-3">
        <div class="card-body">
          <h6 class="card-title">Gastos (Egresos)</h6>
          <div class="muted">—</div>
        </div>
      </div>

      <div class="card kpi-card mt-3">
        <div class="card-body">
          <h6 class="card-title">Efectivo actual</h6>
          <div class="muted">—</div>
        </div>
      </div>

      <!-- Bloque 2×2: Clientes -->
      <div class="card mt-3">
        <div class="card-body">
          <div class="mini-grid">
            <div class="mini-box">
              <div class="fw-semibold">Clientes nuevos</div>
              <div class="muted small">cantidad</div>
            </div>
            <div class="mini-box">
              <div class="fw-semibold">Clientes la semana</div>
              <div class="muted small">pasada</div>
            </div>
            <div class="mini-box">
              <div class="fw-semibold">Clientes nuevos</div>
              <div class="muted small">en lo que va del mes</div>
            </div>
            <div class="mini-box">
              <div class="fw-semibold">Clientes en el año</div>
              <div class="muted small">YTD</div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Fila inferior: 3 tarjetas -->
    <div class="col-12 col-lg-4 mt-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="card-title">Servicios más vendidos</h6>
          <div class="muted">torta y lista</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4 mt-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="card-title">Comparación con semana anterior</h6>
          <div class="muted">gráfico</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4 mt-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="card-title">Últimas ventas</h6>
          <div class="muted">tabla</div>
        </div>
      </div>
    </div>

  </div><!-- /.row -->
</div><!-- /.dash-admin -->

<?php if ($comunicados): ?>
<!-- Modal comunicado -->
<div class="modal fade" id="cmModal" tabindex="-1" role="dialog" aria-labelledby="cmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <div class="cm-modal-title" id="cmModalTitle">(Sin título)</div>
          <small class="text-muted" id="cmModalCount"></small>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
          <button id="cmZoomReset" type="button" class="btn btn-sm btn-outline-secondary me-2">Tamaño original</button>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
      <div class="modal-body cm-modal-body">
        <div>
          <div class="fw-bold mb-1">Contenido</div>
          <div id="cmModalBodyText" class="muted" style="white-space:pre-wrap"></div>
        </div>
        <div class="cm-modal-imgwrap">
          <img id="cmModalImg" class="cm-modal-img" src="" alt="Imagen del comunicado">
        </div>
      </div>
      <div class="modal-footer">
        <a id="cmModalDownload" class="btn btn-outline-secondary" href="#" download>Descargar</a>
        <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  // ===== Datos de comunicados para el carrusel =====
  const data = <?= json_encode(array_map(function($c){
    return [
      'id'           => (int)$c['id'],
      'titulo'       => $c['titulo'] ?? '',
      'cuerpo'       => $c['cuerpo'] ?? '',
      'imagen'       => imgUrl($c['imagen_path'] ?? null),
      'fecha_limite' => $c['fecha_limite'] ?? null,
    ];
  }, $comunicados), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  if (!Array.isArray(data) || data.length === 0) return;

  const root    = document.querySelector('.dash-admin');
  const titleEl = root.querySelector('#cm-title');
  const bodyEl  = root.querySelector('#cm-body');
  const imgEl   = root.querySelector('#cm-thumb');
  const btnEx   = root.querySelector('#cm-expand');
  const btnDl   = root.querySelector('#cm-download');
  const prev    = root.querySelector('#cm-prev');
  const next    = root.querySelector('#cm-next');
  const dotsBox = root.querySelector('#cm-dots');
  const ddlEl   = root.querySelector('#cm-deadline');

  // Modal refs (Bootstrap 4 – AdminLTE)
  const modal     = document.getElementById('cmModal');
  const mTitle    = document.getElementById('cmModalTitle');
  const mBodyText = document.getElementById('cmModalBodyText');
  const mImg      = document.getElementById('cmModalImg');
  const mDl       = document.getElementById('cmModalDownload');
  const mCount    = document.getElementById('cmModalCount');
  const mReset    = document.getElementById('cmZoomReset');

  let i=0, timer=null, countdownT=null;

  function esc(s){ return (s||'').toString().replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

  function render(k){
    i=k; const c=data[i];
    if (titleEl) titleEl.textContent = c.titulo || '(Sin título)';
    if (bodyEl)  bodyEl.innerHTML = c.cuerpo ? esc(c.cuerpo).replace(/\n/g,'<br>') : '<em class="muted">(Sin cuerpo)</em>';
    if (imgEl){
      if (c.imagen){ imgEl.src=c.imagen; imgEl.classList.remove('d-none'); btnEx?.removeAttribute('disabled'); btnDl?.setAttribute('href', c.imagen); btnDl?.classList.remove('disabled'); }
      else { imgEl.src=''; imgEl.classList.add('d-none'); btnEx?.setAttribute('disabled','disabled'); btnDl?.removeAttribute('href'); btnDl?.classList.add('disabled'); }
    }
    if (ddlEl){
      if (!c.fecha_limite){ ddlEl.textContent=''; stopCountdown(); }
      else { startCountdown(new Date(c.fecha_limite)); }
    }
    // dots
    [...(dotsBox?.children||[])].forEach((d,idx)=>d.classList.toggle('active', idx===i));
  }

  function startAuto(){ stopAuto(); timer=setInterval(()=>render((i+1)%data.length), 6000); }
  function stopAuto(){ if (timer){ clearInterval(timer); timer=null; } }

  function startCountdown(dt){
    stopCountdown();
    function tick(){
      const ms = dt - new Date();
      if (ms<=0){ ddlEl.textContent='Finalizó'; stopCountdown(); return; }
      const s=Math.floor(ms/1000), d=Math.floor(s/86400), h=Math.floor((s%86400)/3600), m=Math.floor((s%3600)/60), ss=s%60;
      ddlEl.textContent=`Faltan ${d}d ${h}h ${m}m ${ss}s`;
    }
    tick(); countdownT=setInterval(tick,1000);
  }
  function stopCountdown(){ if (countdownT){ clearInterval(countdownT); countdownT=null; } }

  // Dots
  if (dotsBox){
    dotsBox.innerHTML = data.map((_,k)=>`<span class="cm-dot${k? '':' active'}" data-i="${k}"></span>`).join('');
    dotsBox.addEventListener('click', e=>{
      const d=e.target.closest('.cm-dot'); if(!d) return;
      render(parseInt(d.dataset.i,10));
      startAuto();
    });
  }

  prev?.addEventListener('click', ()=>{ render((i-1+data.length)%data.length); startAuto(); });
  next?.addEventListener('click', ()=>{ render((i+1)%data.length); startAuto(); });

  // Abrir modal
  btnEx?.addEventListener('click', openModalFromCurrent);
  imgEl?.addEventListener('click', openModalFromCurrent);

  function openModalFromCurrent(){
    const c=data[i]; if (!c) return;
    if (!c.imagen) return;

    mTitle.textContent   = c.titulo || '(Sin título)';
    mBodyText.textContent= c.cuerpo || '(Sin cuerpo)';
    mImg.src             = c.imagen;
    mImg.classList.remove('zoomed');
    mDl.href             = c.imagen;
    mCount.textContent   = `(${i+1} de ${data.length})`;

    if (typeof $==='function' && $('#cmModal').modal) $('#cmModal').modal('show');
  }

  // Zoom en el modal
  mImg?.addEventListener('click', ()=>{ mImg.classList.toggle('zoomed'); });
  mReset?.addEventListener('click', ()=>{ mImg.classList.remove('zoomed'); });

  // Auto-rotación pausa al pasar el mouse por el card (no toca navbar)
  const cmCard = root.querySelector('.card');
  cmCard?.addEventListener('mouseenter', stopAuto);
  cmCard?.addEventListener('mouseleave', startAuto);

  render(0); startAuto();
})();
</script>