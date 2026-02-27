// modules/consola/servicios/gestion.js
export function init(slot, apiUrl) {
  // Si ya está enlazado, solo refrescamos (relee DOM nuevo, empresas y lista)
  if (slot.__modServiciosBound) {
    slot.__modServiciosRefresh?.();
    return;
  }
  slot.__modServiciosBound = true;

  // ---------- Helpers ----------
  const esc = s => (s ?? '').replace(/[&<>\"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
  const $  = sel => slot.querySelector(sel);
  const show = (el, msg='') => { if (!el) return; const span = el.querySelector?.('.msg'); if (span) span.textContent = msg; else el.textContent = msg; el.classList.remove('d-none'); el.classList.add('show'); };
  const hide = el => { if (!el) return; el.classList.add('d-none'); el.classList.remove('show'); };
  const debounce = (fn,ms) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url, opts={}) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  // ---------- Getters dinámicos (no guardamos nodos viejos) ----------
  const boxEl    = () => $('#s-tags');
  const inputEl  = () => $('#s-tags-input');
  const hiddenEl = () => $('#s-etiquetas');
  const formEl   = () => $('#srv-create');

  // ---------- Chips ----------
  const readTags = () => {
    const box = boxEl();
    if (box?.dataset.tags) { try { const a = JSON.parse(box.dataset.tags); return Array.isArray(a)?a:[]; } catch { return []; } }
    const arr=[]; box?.querySelectorAll('.chip .txt')?.forEach(n=>arr.push(n.textContent||'')); return arr;
  };
  const renderChips = (arr) => {
    const box = boxEl(); const input = inputEl(); if (!box || !input) return;
    box.querySelectorAll('.chip').forEach(c => c.remove());
    arr.forEach((t,i) => {
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.innerHTML = `<span class="txt">${esc(t)}</span><button type="button" class="x" data-i="${i}" aria-label="Quitar">×</button>`;
      box.insertBefore(chip, input);
    });
  };
  const writeTags = (arr) => {
    const box = boxEl(); if (box) box.dataset.tags = JSON.stringify(arr);
    renderChips(arr);
    const hidden = hiddenEl(); if (hidden) hidden.value = arr.join(',');
  };
  const norm   = t => (t||'').replace(/\s+/g,' ').trim().slice(0,50);
  const addTag = (text) => { const t = norm(text); if (!t) return; const tags = readTags(); if (!tags.includes(t)) { tags.push(t); writeTags(tags); } };
  const removeTag = (idx) => { const tags = readTags(); if (idx>=0 && idx<tags.length) { tags.splice(idx,1); writeTags(tags); } };

  // foco fácil en área de chips
  slot.addEventListener('mousedown', (e) => {
    const area = e.target?.closest('#s-tags'); if (!area) return;
    if (!e.target.closest('#s-tags-input')) { e.preventDefault(); inputEl()?.focus(); }
  });

  // evitar enter nativo (menos en chips)
  slot.addEventListener('keydown', (e) => {
    if (e.target && e.target.closest('#srv-create') && e.key === 'Enter') {
      if (!e.target.closest('#s-tags-input')) e.preventDefault();
    }
  });

  // coma/enter/backspace chips
  slot.addEventListener('keydown', (e) => {
    if (!e.target?.closest || !e.target.closest('#s-tags-input')) return;
    const input = inputEl(); if (!input) return;
    if (e.key === ',' || e.key === 'Enter') { e.preventDefault(); addTag(input.value); input.value=''; }
    else if (e.key === 'Backspace' && input.value==='') { const tags=readTags(); if (tags.length){ tags.pop(); writeTags(tags);} }
  });

  // pegar con comas
  slot.addEventListener('input', (e) => {
    if (!e.target?.closest || !e.target.closest('#s-tags-input')) return;
    const input = inputEl(); if (!input) return;
    const v = input.value || '';
    if (v.includes(',')) {
      const parts = v.split(/[,，、،]+/);
      for (let i=0; i<parts.length-1; i++) addTag(parts[i]);
      input.value = norm(parts.at(-1));
    }
    writeTags(readTags());
  });

  // quitar chip
  slot.addEventListener('click', (e) => {
    const btn = e.target?.closest('.chip .x'); if (!btn) return;
    const i = parseInt(btn.dataset.i,10); if (Number.isNaN(i)) return;
    removeTag(i); inputEl()?.focus();
  });

  // crear servicio
  slot.addEventListener('click', async (e) => {
    const btn = e.target?.closest('#s-crear'); if (!btn) return;

    const form  = formEl();
    const okEl  = $('#s-ok');
    const errEl = $('#s-alert');

    hide(errEl); hide(okEl);

    const nombre = ($('#s-nombre')?.value || '').trim();
    if (!nombre) { show(errEl, 'El nombre es obligatorio'); return; }

    writeTags(readTags()); // asegura CSV
    
    const fd = new FormData(form);
if (L.editId > 0) {
  fd.append('action','update');
  fd.append('id', String(L.editId));
} else {
  fd.append('action','create');
}

    btn.disabled=true; const old=btn.textContent; btn.textContent='Creando…';
    try{
      const r = await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'});
      const j = await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
      if (!r.ok || !j.ok) throw new Error(j.msg || `HTTP ${r.status}`);
      const esUpdate = L.editId > 0;
const sid = (j && j.id) ? j.id : L.editId;   // por si el backend no devuelve id en algún caso
show(okEl, `Servicio "${nombre}" ${esUpdate ? 'actualizado' : 'creado'} con éxito. Id: ${sid}.`);
      // reset UI y estado
form.reset?.();
writeTags([]); const inp = inputEl(); if (inp) inp.value='';
L.page = 1;            // si quieres mantener página, quita esta línea
L.openId = 0;

// Si estábamos editando, salir de modo edición
if (L.editId > 0) {
  L.editId = 0;
  const btnSave = slot.querySelector('#s-crear');
  if (btnSave) btnSave.textContent = 'Crear';
}

// refrescar la lista
await cargarLista();
      // refresca lista
      L.page = 1; await cargarLista();
    }catch(err){ show(errEl, err?.message || 'Error al crear el servicio'); }
    finally{ btn.disabled=false; btn.textContent=old; }
  });

  // cerrar ok
  slot.addEventListener('click', (e)=>{ const x=e.target?.closest('.s-ok-close'); if(!x) return; hide($('#s-ok')); });

  // ---------- Listado (DIV 3) ----------
  const L = { empresa:0, q:'', estado:'', page:1, per_page:5, openId:0, rows:[], editId:0 };

  async function cargarEmpresas(){
    try{
      const res = await j(`${apiUrl}?action=empresas`);
      const sel = $('#f-empresa');
      if (sel) {
        sel.innerHTML = `<option value="0">Todas las empresas</option>` +
          res.data.map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('');
      }
    }catch(_){}
  }

  function pintarTabla(rows){
  const tb = $('#l-tbody');
  const isOpen = id => L.openId === id;

  const rowMain = (r) => `
    <tr class="srv-row" data-id="${r.id}">
      <td>${r.id}</td>
<td class="name-cell">
  <span class="name-text">${esc(r.nombre)}</span>
  <span class="status-dot ${r.activo?'status-on':'status-off'}"></span>
</td>
      <td class="actions-cell">
<div class="actions-inline">
  <button class="btn btn-sm btn-primary l-edit" data-id="${r.id}">Editar</button>
  <button class="btn btn-sm btn-warning l-desact" data-id="${r.id}">
    ${r.activo ? 'Desactivar' : 'Activar'}
  </button>
  <button class="btn btn-sm btn-success l-empresas" data-id="${r.id}" data-nombre="${esc(r.nombre)}">
    Empresas
  </button>
</div>
      </td>
    </tr>`;

  const rowDetail = (r) => `
    <tr class="srv-detail ${isOpen(r.id)?'':'d-none'}" data-for="${r.id}">
      <td></td>
      <td colspan="2">
        <div><strong>Descripción:</strong> ${esc(r.descripcion || '—')}</div>
        <div class="mt-2 chips">
          ${(r.tags||[]).map(t=>`<span class="chip soft">${esc(t)}</span>`).join('') || '<span class="text-muted">Sin etiquetas</span>'}
        </div>
      </td>
    </tr>`;

  tb.innerHTML = rows.map(r => rowMain(r) + rowDetail(r)).join('');
}

  function pintarPager(total){
    const ul = $('#l-pager');
    const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages);
    const cur = L.page;
    const items = [];
    const add = (p, label, cls='') => items.push(
      `<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`
    );
    add(cur-1,'«', cur<=1?'disabled':'');
    let start = Math.max(1, cur-2), end = Math.min(pages, start+4); start = Math.max(1, end-4);
    for (let p=start; p<=end; p++) add(p, p, p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }

  async function cargarLista(){
    hide($('#l-alert'));
    const qs = new URLSearchParams({
      action:'list',
      empresa: L.empresa || 0,
      q: L.q,
      estado: L.estado,
      page: L.page,
      per_page: L.per_page
    });
    try{
      const res = await j(`${apiUrl}?${qs.toString()}`);
      L.rows = res.data || [];
      if (!L.rows.some(r => r.id === L.openId)) L.openId = 0;
      pintarTabla(L.rows); pintarPager(res.total);
    }catch(err){
      const a=$('#l-alert'); a.textContent = err.message || 'Error al listar'; a.classList.remove('d-none');
    }
  }

  // filtros
  slot.addEventListener('change', (e)=>{
    if (e.target?.id === 'f-empresa') { L.empresa = parseInt(e.target.value,10)||0; L.page=1; cargarLista(); }
    if (e.target?.id === 'f-estado')  { L.estado  = e.target.value; L.page=1; cargarLista(); }
  });
  slot.addEventListener('input', debounce((e)=>{
    if (e.target?.id === 'f-q') { L.q = e.target.value; L.page=1; cargarLista(); }
  }, 300));

  // pager
  slot.addEventListener('click', (e)=>{
    const a = e.target?.closest('#l-pager a[data-page]'); if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10); if (p>0) { L.page=p; cargarLista(); }
  });
  
  // Click en Editar: cargar datos al DIV SUPERIOR
slot.addEventListener('click', (e)=>{
  const btn = e.target?.closest('.l-edit'); if (!btn) return;
  const id = parseInt(btn.dataset.id, 10); if (!id) return;

  // Buscar el registro en la página actual
  const row = (L.rows || []).find(r => r.id === id);
  if (!row) return;

  // Poblar campos
  const nombre = slot.querySelector('#s-nombre');
  const desc   = slot.querySelector('#s-desc');
  const file   = slot.querySelector('#s-imagen');
  if (nombre) nombre.value = row.nombre || '';
  if (desc)   desc.value   = row.descripcion || '';
  if (file)   file.value   = '';            // limpiar input file
  writeTags(Array.isArray(row.tags) ? row.tags : []);

  // Marcar modo edición
  L.editId = id;
  const btnSave = slot.querySelector('#s-crear');
  if (btnSave) btnSave.textContent = 'Guardar cambios';

  // Llevar foco al nombre
  nombre?.focus();
  // Opcional: abrir el detalle de esa fila
  L.openId = id; if (Array.isArray(L.rows)) pintarTabla(L.rows);
});

  // toggle detalle (click en fila, no en botones)
  slot.addEventListener('click', (e)=>{
    const tr = e.target?.closest('tr.srv-row');
    if (!tr || !tr.closest('#srv-lista')) return;
    if (e.target.closest('.actions-inline') || e.target.closest('button')) return;
    const id = parseInt(tr.dataset.id,10);
    L.openId = (L.openId === id) ? 0 : id;
    if (Array.isArray(L.rows)) pintarTabla(L.rows);
  });
  
// Activar / Desactivar (toggle)
slot.addEventListener('click', async (e) => {
  const btn = e.target?.closest('.l-desact');
  if (!btn) return;

  const id = parseInt(btn.dataset.id, 10);
  if (!id) return;

  const row = (L.rows || []).find(r => r.id === id);
  if (!row) return;

  const nuevo = row.activo ? 0 : 1;                 // 0 = desactivar, 1 = activar
  const accionTxt = nuevo ? 'Activar' : 'Desactivar';
  if (!confirm(`¿${accionTxt} el servicio "${row.nombre}"?`)) return;

  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('action', 'set_activo');
    fd.append('id', String(id));
    fd.append('activo', String(nuevo));

    const res = await fetch(apiUrl, { method:'POST', body: fd, credentials:'same-origin' });
    const j = await res.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
    if (!res.ok || !j.ok) throw new Error(j.msg || `HTTP ${res.status}`);

    const okEl = slot.querySelector('#s-ok');
    if (okEl) {
      const msg = `Servicio "${row.nombre}" ${nuevo ? 'activado' : 'desactivado'} con éxito.`;
      const span = okEl.querySelector('.msg');
      if (span) span.textContent = msg; else okEl.textContent = msg;
      okEl.classList.remove('d-none'); okEl.classList.add('show');
    }

    await cargarLista();
  } catch (err) {
    const a = slot.querySelector('#l-alert');
    if (a) { a.textContent = err.message || 'Error al actualizar estado'; a.classList.remove('d-none'); }
  } finally {
    btn.disabled = false;
  }
});

// Abrir panel izquierdo desde el botón "Empresas"
slot.addEventListener('click', async (e)=>{
  const btn = e.target?.closest('.l-empresas'); if (!btn) return;
  const id  = parseInt(btn.dataset.id, 10); if (!id) return;
  E.servicioId = id;
  E.servicioNombre = btn.dataset.nombre || '';
  E.page = 1; E.estado = ''; E.empresa_id = 0;
  await e_cargarSelectEmpresas();
  await e_cargarLista();
});

// Filtros izquierdo
slot.addEventListener('change', (e)=>{
  if (e.target?.id === 'e-empresa') { E.empresa_id = parseInt(e.target.value,10) || 0; E.page=1; e_cargarLista(); }
  if (e.target?.id === 'e-estado')  { E.estado     = e.target.value; E.page=1; e_cargarLista(); }
});

// Paginación izquierdo
slot.addEventListener('click', (e)=>{
  const a = e.target?.closest('#e-pager a[data-page]'); if (!a) return;
  e.preventDefault();
  const li = a.parentElement;
  if (li.classList.contains('disabled') || li.classList.contains('active')) return;
  const p = parseInt(a.dataset.page,10); if (p>0) { E.page=p; e_cargarLista(); }
});

// Asignar / Quitar empresa <-> servicio
slot.addEventListener('click', async (e)=>{
  const btn = e.target?.closest('.e-toggle'); if (!btn) return;
  if (!E.servicioId) return;

  const empresa_id = parseInt(btn.dataset.empresa, 10);
  const asignado   = parseInt(btn.dataset.asignado, 10) === 1;
  const assign     = asignado ? 0 : 1; // 1 asignar, 0 quitar
  const nombreSrv  = E.servicioNombre;

  if (!empresa_id) return;

  if (!confirm(`${assign? 'Asignar':'Quitar'} "${nombreSrv}" ${assign?'a':'de'} esta empresa?`)) return;

  btn.disabled = true;
  try{
    const fd = new FormData();
    fd.append('action','set_emp_srv');
    fd.append('empresa_id', String(empresa_id));
    fd.append('servicio_id', String(E.servicioId));
    fd.append('assign', String(assign));
    const r = await fetch(apiUrl, { method:'POST', body: fd, credentials:'same-origin' });
    const j = await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
    if (!r.ok || !j.ok) throw new Error(j.msg || `HTTP ${r.status}`);

    // Feedback en el alert verde superior (reutilizado)
    const okEl = slot.querySelector('#s-ok');
    if (okEl) {
      const span = okEl.querySelector('.msg');
      const msg = `Servicio "${nombreSrv}" ${assign? 'asignado a':'quitado de'} la empresa.`;
      if (span) span.textContent = msg; else okEl.textContent = msg;
      okEl.classList.remove('d-none'); okEl.classList.add('show');
    }

    await e_cargarLista();
  }catch(err){
    const a = slot.querySelector('#e-alert');
    if (a) { a.textContent = err.message || 'Error al actualizar asignación'; a.classList.remove('d-none'); }
  }finally{
    btn.disabled = false;
  }
});

// ---------- Panel izquierdo: Empresas del servicio ----------
const E = { servicioId:0, servicioNombre:'', empresa_id:0, estado:'', page:1, per_page:5 };

async function e_cargarSelectEmpresas(){
  try{
    const r = await j(`${apiUrl}?action=empresas`);
    const sel = slot.querySelector('#e-empresa');
    if (sel) {
      sel.innerHTML = `<option value="0">Todas</option>` +
        r.data.map(x => `<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      if (E.empresa_id) sel.value = String(E.empresa_id);
    }
  }catch(_){}
}

function e_pintarTabla(rows, total){
  const tb = slot.querySelector('#e-tbody');
  const start = (E.page - 1) * E.per_page;
  tb.innerHTML = rows.map((r,i)=>`
    <tr>
      <td>${start + i + 1}</td>
<td>
  ${esc(r.nombre)}<br>
  <span class="badge rounded-pill ${r.asignado ? 'bg-success' : 'bg-secondary'}">
    ${r.asignado ? 'Asignado' : 'No asignado'}
  </span>
</td>
      <td class="text-end">
        <button class="btn btn-sm ${r.asignado?'btn-danger':'btn-success'} e-toggle"
                data-empresa="${r.id}" data-asignado="${r.asignado?1:0}">
          ${r.asignado ? 'Quitar' : 'Asignar'}
        </button>
      </td>
    </tr>
  `).join('');

  const ul = slot.querySelector('#e-pager');
  const pages = Math.max(1, Math.ceil(total / E.per_page));
  E.page = Math.min(E.page, pages);
  const cur = E.page;
  const li = [];
  const add = (p, label, cls='')=> li.push(
    `<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`
  );
  add(cur-1, '«', cur<=1?'disabled':'');
  let s = Math.max(1, cur-2), e = Math.min(pages, s+4); s = Math.max(1, e-4);
  for (let p=s; p<=e; p++) add(p, p, p===cur?'active':'');
  add(cur+1, '»', cur>=pages?'disabled':'');
  ul.innerHTML = li.join('');
}

async function e_cargarLista(){
  const hint  = slot.querySelector('#emp-hint');
  const panel = slot.querySelector('#emp-panel');
  if (!E.servicioId) {
    panel?.classList.add('d-none'); hint?.classList.remove('d-none');
    return;
  }
  hint?.classList.add('d-none'); panel?.classList.remove('d-none');

const label = slot.querySelector('#e-srv-actual');
if (label) {
  label.textContent = E.servicioNombre || `ID ${E.servicioId}`;

  // Activa animación solo si el contenido desborda el contenedor
  const box = label.closest('.e-srv-marquee');
  if (box) {
    // Espera un frame para que el layout esté listo
    requestAnimationFrame(() => {
      const need = label.scrollWidth > box.clientWidth + 2;
      label.classList.toggle('animate', need);
    });
  }
}

  const qs = new URLSearchParams({
    action:'empresas_srv',
    servicio_id: E.servicioId,
    empresa_id: E.empresa_id || 0,
    estado: E.estado,
    page: E.page,
    per_page: E.per_page
  });

  const alert = slot.querySelector('#e-alert');
  alert?.classList.add('d-none');

  try{
    const r = await j(`${apiUrl}?${qs.toString()}`);
    e_pintarTabla(r.data || [], r.total || 0);
  }catch(err){
    if (alert){ alert.textContent = err.message || 'Error al listar empresas'; alert.classList.remove('d-none'); }
  }
}

  // ---------- REFRESH para reabrir el modal ----------
  slot.__modServiciosRefresh = async function(){
    // limpiar mensajes
    hide($('#s-alert')); hide($('#s-ok'));
    // reset filtros UI
    const fe = $('#f-empresa'); const fq = $('#f-q'); const fs = $('#f-estado');
    if (fe) fe.value = '0'; if (fq) fq.value = ''; if (fs) fs.value = '';
    // reset chips
    writeTags([]); const inp = inputEl(); if (inp) inp.value = '';
    // reset estado de lista
    L.empresa=0; L.q=''; L.estado=''; L.page=1; L.openId=0;
    await cargarEmpresas();
    await cargarLista();
    L.editId = 0;
const btnSave = slot.querySelector('#s-crear'); if (btnSave) btnSave.textContent = 'Crear';
// reset panel izquierdo
E.servicioId = 0; E.servicioNombre = ''; E.empresa_id = 0; E.estado = ''; E.page = 1;
const hint  = slot.querySelector('#emp-hint');
const panel = slot.querySelector('#emp-panel');
if (panel) panel.classList.add('d-none');
if (hint)  hint.classList.remove('d-none');
slot.querySelector('#e-tbody')?.replaceChildren();
slot.querySelector('#e-pager')?.replaceChildren();
slot.querySelector('#e-srv-actual') && (slot.querySelector('#e-srv-actual').textContent = '');
  };

  // Primera carga
  slot.__modServiciosRefresh();
}
