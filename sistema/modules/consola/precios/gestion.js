// modules/consola/precios/gestion.js
// Módulo UI: Gestión de precios
// Usa API endpoints en modules/consola/precios/api.php
export function init(slot, apiUrl) {
  // Evitar doble enlace si reabren el modal
  if (slot.__preciosBound) {
    slot.__preciosRefresh?.();
    return;
  }
  slot.__preciosBound = true;

  // ---------- Utils ----------
  const $  = (sel) => slot.querySelector(sel);
  const esc = (s) => (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el, msg='') => { if (!el) return; el.textContent = msg; el.classList.remove('d-none'); };
  const hide = (el) => { if (!el) return; el.classList.add('d-none'); };
  const debounce = (fn, ms=300) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

  async function j(url, opts={}) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta invalida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  const baseApiUrl = (() => {
    try { return new URL(apiUrl, window.location.href); }
    catch (_) { return null; }
  })();

  function appBaseUrl() {
    if (!baseApiUrl) return '';
    const idx = baseApiUrl.pathname.indexOf('/modules/');
    const basePath = idx >= 0 ? baseApiUrl.pathname.slice(0, idx) : '';
    return `${baseApiUrl.origin}${basePath}`;
  }

  function toPublicImageUrl(path, cacheBuster = '') {
    const p = (path || '').trim();
    if (!p) return '';
    if (/^https?:\/\//i.test(p)) {
      if (!cacheBuster) return p;
      const sepAbs = p.indexOf('?') >= 0 ? '&' : '?';
      return `${p}${sepAbs}_v=${encodeURIComponent(cacheBuster)}`;
    }
    const clean = p.replace(/^\/+/, '');
    const base = appBaseUrl();
    const sep = clean.indexOf('?') >= 0 ? '&' : '?';
    const v = cacheBuster ? `${sep}_v=${encodeURIComponent(cacheBuster)}` : '';
    if (!base) return `/${clean}${v}`;
    return `${base}/${clean}${v}`;
  }

  // ---------- Estado ----------
  const TOP = { empresa_id: 0, servicio_id: 0, servicio_nombre: '' };

  const S = { // lista de servicios (panel centro-izquierdo)
    q: '', estado: '', rows: [],
    page: 1, per_page: 5
  };

  // Panel precios (derecha) usa TOP.empresa_id / TOP.servicio_id

  // ---------- Carga empresas (TOP) ----------
  async function cargarEmpresasTop() {
  const sel = $('#px-empresa');
  if (!sel) return;
  sel.innerHTML = `<option value="0">Cargando...</option>`;
  try {
    const r = await j(`${apiUrl}?action=empresas`);
    sel.innerHTML = `<option value="0">Selecciona una empresa ...</option>` +
      (r.data || []).map(e => `<option value="${e.id}">${esc(e.nombre)}</option>`).join('');
    sel.value = String(TOP.empresa_id || 0);
  } catch (err) {
    sel.innerHTML = `<option value="0">Error al cargar</option>`;
  }
}

  // ---------- Lista de servicios (centro-izquierda) ----------
  function pintarServicios() {
  const tb = $('#px-stbody'); if (!tb) return;
  const ul = $('#px-spager'); if (!ul) return;

  const total = S.rows.length;
  const pages = Math.max(1, Math.ceil(total / S.per_page));
  S.page = Math.min(Math.max(1, S.page), pages);

  const start = (S.page - 1) * S.per_page;
  const rows = S.rows.slice(start, start + S.per_page);
  const thumbHtml = (r) => {
    const path = (r && r.imagen_path) ? String(r.imagen_path).trim() : '';
    if (!path) return `<span class="px-srv-thumb px-srv-thumb-empty" title="Sin imagen">SIN</span>`;
    const v = (r && r.actualizado) ? String(r.actualizado) : String(r.id || Date.now());
    const src = toPublicImageUrl(path, v);
    return `<img class="px-srv-thumb" src="${esc(src)}" alt="Imagen de ${esc(r.nombre || 'servicio')}" loading="lazy">`;
  };

  tb.innerHTML = rows.map((r, i) => `
    <tr>
      <td>${start + i + 1}</td>
      <td>
        <div class="px-srv-name">
          ${thumbHtml(r)}
          <div class="px-srv-main">
            <div class="px-srv-text">${esc(r.nombre)}</div>
            <div class="mt-1">
              <span class="badge ${r.activo ? 'badge-success bg-success' : 'badge-secondary bg-secondary'}">
                ${r.activo ? 'Activo' : 'Inactivo'}
              </span>
            </div>
          </div>
        </div>
      </td>
      <td class="text-end">
        <button class="btn btn-sm btn-warning s-ver" data-id="${r.id}" data-nombre="${esc(r.nombre)}">
          Ver precios
        </button>
      </td>
    </tr>
  `).join('') || `<tr><td colspan="3" class="text-muted">Sin resultados</td></tr>`;

  const items = [];
  const add = (p, label, cls='') =>
    items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`);
  add(S.page-1, '«', S.page<=1?'disabled':'');
  let s = Math.max(1, S.page-2), e = Math.min(pages, s+4); s = Math.max(1, e-4);
  for (let p=s; p<=e; p++) add(p, p, p===S.page?'active':'');
  add(S.page+1, '»', S.page>=pages?'disabled':'');
  ul.innerHTML = items.join('');
}

async function cargarServicios() {
  const tb = $('#px-stbody'); if (tb) tb.innerHTML = `<tr><td colspan="3">Cargando…</td></tr>`;

  // Si no hay empresa seleccionada, muestra aviso y limpia la tabla
  const info = $('#px-salert');
  if (!TOP.empresa_id) {
    if (info) { info.classList.remove('d-none'); info.textContent = 'Selecciona una empresa, podrás ver todos los servicios disponibles.'; }
    S.rows = []; pintarServicios(); return;
  }
  if (info) info.classList.add('d-none');

  try {
    const qs = new URLSearchParams({
      action: 'servicios_empresa',
      empresa_id: TOP.empresa_id, // <-- NECESARIO por api.php
      q: S.q,
      estado: S.estado
    });
    const r = await j(`${apiUrl}?${qs.toString()}`);
    S.rows = r.data || [];
    S.page = 1;
    pintarServicios();
  } catch (err) {
    if (tb) tb.innerHTML = `<tr><td colspan="3" class="text-danger">${esc(err.message||'Error')}</td></tr>`;
  }
}
  // ---------- Panel derecho: PRECIOS ----------
  async function preciosListar() {
    const tBody = $('#p-tbody'); if (!tBody) return;
    const alert = $('#p-alert'); hide(alert);

    if (!TOP.empresa_id || !TOP.servicio_id) {
      tBody.innerHTML = '<tr><td colspan="3" class="text-muted">Selecciona un servicio</td></tr>';
      return;
    }

    const est = ($('#p-est-pre')?.value || '');
    try {
      const url = `${apiUrl}?action=precios_list&empresa_id=${TOP.empresa_id}&servicio_id=${TOP.servicio_id}&estado=${encodeURIComponent(est)}`;
      const r = await j(url);
      const rows = r.data || [];

      const html = rows.map((row, idx) => {
        const rolLabel = row.es_principal ? 'Principal' : `opción ${row.rol}`;
        const rolClass = row.es_principal ? 'badge-primary bg-primary' : 'badge-secondary bg-secondary';
        const estLabel = row.activo ? 'Activo' : 'Inactivo';
        const estClass = row.activo ? 'badge-success bg-success' : 'badge-danger bg-danger';
        const notaTxt  = (row.nota && row.nota.trim()!=='') ? esc(row.nota)
                       : '<span class="p-nota-empty">Sin asignar</span>';

        return `
          <tr data-id="${row.id}">
            <td>${idx+1}</td>
            <td class="price-cell">
              <div><strong>${Number(row.precio).toFixed(2)} soles</strong> | ${notaTxt}</div>
              <div class="mt-1 d-flex justify-content-between align-items-center w-100">
  <span class="badge ${rolClass}">${rolLabel}</span>
  <span class="badge ${estClass}">${estLabel}</span>
</div>
            </td>
            <td>
              <div class="actions-inline">
                <button class="btn btn-sm btn-primary p-edit" data-id="${row.id}">Editar</button>
                <button class="btn btn-sm ${row.activo?'btn-warning':'btn-success'} p-toggle" data-id="${row.id}">
                  ${row.activo ? 'Desactivar' : 'Activar'}
                </button>
                <button class="btn btn-sm btn-info p-principal" data-id="${row.id}">Principal</button>
              </div>
            </td>
          </tr>`;
      }).join('');

      tBody.innerHTML = html || '<tr><td colspan="3" class="text-muted">Sin precios</td></tr>';
    } catch (err) {
      show(alert, err.message || 'Error al listar precios');
    }
  }

  // ---------- Eventos TOP ----------
slot.addEventListener('change', (e)=>{
  if (e.target?.id === 'px-empresa') {
    TOP.empresa_id = parseInt(e.target.value, 10) || 0;
    TOP.servicio_id = 0; TOP.servicio_nombre = '';
    const selEl = $('#px-sel-track'); if (selEl) selEl.textContent = 'Aún no has seleccionado un servicio';
    cargarServicios();
    preciosListar();
  }
});

  // ---------- Filtros servicios ----------
slot.addEventListener('input', debounce((e)=>{
  if (e.target?.id === 'px-sq') { S.q = e.target.value || ''; S.page = 1; cargarServicios(); }
}, 300));
slot.addEventListener('change', (e)=>{
  if (e.target?.id === 'px-sestado') { S.estado = e.target.value || ''; S.page = 1; cargarServicios(); }
});

  // Pager servicios
  slot.addEventListener('click', (e)=>{
    const a = e.target?.closest('#px-spager a[data-page]');
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10);
    if (p>0) { S.page = p; pintarServicios(); }
  });

  // Ver precios de un servicio concreto
  slot.addEventListener('click', (e)=>{
    const btn = e.target?.closest('.s-ver'); if (!btn) return;
    const sid = parseInt(btn.dataset.id,10); if (!sid) return;
    TOP.servicio_id = sid;
    TOP.servicio_nombre = btn.dataset.nombre || '';
    const selEl = $('#px-sel-track'); if (selEl) selEl.textContent = TOP.servicio_nombre || `ID ${sid}`;
    preciosListar();
  });

  // ---------- Filtro estado precios ----------
  slot.addEventListener('change', (e)=>{
    if (e.target?.id === 'p-est-pre') preciosListar();
  });

  // Editar (precio + nota)
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.p-edit'); if (!btn) return;
    const tr  = btn.closest('tr'); const id = parseInt(tr?.dataset.id||'0',10); if (!id) return;

    const cell = tr.querySelector('.price-cell');
    if (!tr.classList.contains('editing')) {
      tr.classList.add('editing'); btn.textContent = 'Guardar';

      const precioTxt = cell.querySelector('strong')?.textContent || '0.00 soles';
      const monto = (precioTxt.replace('soles','').trim().replace(',','.').split(' ')[0]) || '0';
      const notaNode = cell.querySelector('.p-nota-empty') ? '' : (cell.childNodes[2]?.textContent || '');

      cell.__old = cell.innerHTML;
      cell.innerHTML = `
        <div class="d-flex flex-wrap align-items-center gap-2">
          <input type="number" step="0.01" min="0" class="form-control form-control-sm" style="max-width:120px" value="${esc(monto)}">
          <input type="text" class="form-control form-control-sm nota-input" placeholder="Nota (opcional)" value="${esc((notaNode||'').trim())}">
        </div>
        <div class="mt-1 text-muted small">Ingresa el precio en soles (2 decimales) y la nota.</div>
      `;
      return;
    }

    // Guardar
    const num  = cell.querySelector('input[type="number"]')?.value ?? '';
    const nota = cell.querySelector('.nota-input')?.value ?? '';
    const fd = new FormData();
    fd.append('action','precio_update');
    fd.append('id', String(id));
    fd.append('precio', String(num));
    fd.append('nota', nota);

    btn.disabled = true;
    try {
      const r = await fetch(apiUrl, { method:'POST', body:fd, credentials:'same-origin' });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.msg || `HTTP ${r.status}`);
      tr.classList.remove('editing'); btn.textContent = 'Editar';
      await preciosListar();
    } catch (err) {
      const alert = $('#p-alert'); show(alert, err.message || 'No se pudo guardar');
    } finally {
      btn.disabled = false;
    }
  });

  // Activar / Desactivar (si desactivas principal, B pasa a principal automáticamente)
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.p-toggle'); if (!btn) return;
    const id  = parseInt(btn.dataset.id||'0',10); if (!id) return;

    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.append('action','precio_toggle');
      fd.append('id', String(id));
      const r = await fetch(apiUrl, { method:'POST', body:fd, credentials:'same-origin' });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.msg || `HTTP ${r.status}`);
      await preciosListar();
    } catch (err) {
      const alert = $('#p-alert'); show(alert, err.message || 'No se pudo actualizar estado');
    } finally { btn.disabled = false; }
  });

  // Marcar como Principal
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.p-principal'); if (!btn) return;
    const id  = parseInt(btn.dataset.id||'0',10); if (!id) return;

    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.append('action','precio_set_principal');
      fd.append('id', String(id));
      const r = await fetch(apiUrl, { method:'POST', body:fd, credentials:'same-origin' });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.msg || `HTTP ${r.status}`);
      await preciosListar();
    } catch (err) {
      const alert = $('#p-alert'); show(alert, err.message || 'No se pudo cambiar el principal');
    } finally { btn.disabled = false; }
  });

  // ---------- Refresh público (al reabrir modal) ----------
  slot.__preciosRefresh = async function(){
  const empSel = $('#px-empresa'); if (empSel) empSel.value = '0';
  const srvSel = $('#px-sel-track'); if (srvSel) srvSel.textContent = 'Aún no has seleccionado un servicio';

  TOP.empresa_id = 0; TOP.servicio_id = 0; TOP.servicio_nombre = '';

  const q = $('#px-sq'); const es = $('#px-sestado');
  if (q) q.value=''; if (es) es.value='';
  S.q=''; S.estado=''; S.page=1; S.rows=[]; pintarServicios();

  const pe = $('#p-est-pre'); if (pe) pe.value='';
  preciosListar();

  await cargarEmpresasTop();
};

  // ---------- Primera carga ----------
  slot.__preciosRefresh();
}
