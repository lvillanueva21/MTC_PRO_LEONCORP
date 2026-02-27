// modules/consola/usuarios/gestion.js
export function init(slot, apiUrl) {
  if (slot.__usuariosBound) { slot.__usuariosRefresh?.(); return; }
  slot.__usuariosBound = true;

  // Utils
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el, msg='') => { if (!el) return; const span=el.querySelector?.('.msg'); if (span) span.textContent = msg; else el.textContent = msg; el.classList.remove('d-none'); el.classList.add('show'); };
  const hide = el => { if (!el) return; el.classList.add('d-none'); el.classList.remove('show'); };
  const debounce = (fn,ms=300)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};};
  async function j(url, opts={}) { const r = await fetch(url, {credentials:'same-origin', ...opts}); const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'})); if(!r.ok||!d.ok) throw new Error(d.msg || `HTTP ${r.status}`); return d; }

  // Estado
  const U = { editId: 0 };
  const L = { empresa:0, q:'', rol:0, page:1, per_page:5, rows:[], total:0 };

  // --- Carga combos (empresa/rol) ---
  async function cargarEmpresas() {
    try {
      const r = await j(`${apiUrl}?action=empresas`);
      const top = $('#u-empresa'); const f = $('#f-empresa');
      if (top) top.innerHTML = `<option value="">— Selecciona —</option>${(r.data||[]).map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('')}`;
      if (f) f.innerHTML = `<option value="0">Todas</option>${(r.data||[]).map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('')}`;
    } catch { /* noop */ }
  }
  async function cargarRoles() {
    try {
      const r = await j(`${apiUrl}?action=roles`);
      const top = $('#u-rol'); const f = $('#f-rol');
      const opts = (r.data||[]).map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      if (top) top.innerHTML = `<option value="0">— Ninguno —</option>${opts}`;
      if (f)   f.innerHTML   = `<option value="0">Todos</option>${opts}`;
    } catch { /* noop */ }
  }

  // --- Listado ---
  function pintarTabla(rows) {
    const tb = $('#u-tbody'); if (!tb) return;
    tb.innerHTML = rows.map(r => `
      <tr data-id="${r.id}"
          data-usuario="${esc(r.usuario)}"
          data-nombres="${esc(r.nombres)}"
          data-apellidos="${esc(r.apellidos)}"
          data-id_empresa="${r.id_empresa}"
          data-id_rol="${r.rol_id || 0}">
        <td class="text-muted">${r.id}</td>
        <td>${esc(r.usuario)}</td>
        <td>${esc((r.nombres||'') + ' ' + (r.apellidos||''))}</td>
        <td>${esc(r.empresa||'')}</td>
        <td>${esc(r.rol||'—')}</td>
        <td>
          <div class="actions-inline">
            <button class="btn btn-sm btn-primary u-edit" data-id="${r.id}">Editar</button>
            <button class="btn btn-sm btn-danger  u-del"  data-id="${r.id}">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="6" class="text-muted">Sin resultados</td></tr>`;
  }
  function pintarPager(total) {
    const ul = $('#u-pager'); if (!ul) return;
    const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages);
    const cur = L.page;
    const items = [];
    const add = (p, label, cls='') => items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s = Math.max(1, cur-2), e = Math.min(pages, s+4); s = Math.max(1, e-4);
    for (let p=s; p<=e; p++) add(p, p, p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }
  async function cargarLista() {
    hide($('#l-alert'));
    const qs = new URLSearchParams({
      action:'list',
      empresa: L.empresa || 0,
      q: L.q,
      rol: L.rol || 0,
      page: L.page,
      per_page: L.per_page
    });
    try {
      const r = await j(`${apiUrl}?${qs.toString()}`);
      L.rows = r.data || []; L.total = r.total || 0;
      pintarTabla(L.rows); pintarPager(L.total);
    } catch (err) {
      const a=$('#l-alert'); a.textContent = err.message || 'Error al listar'; a.classList.remove('d-none');
    }
  }

  // --- Crear / Actualizar ---
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('#u-guardar'); if (!btn) return;

    const okEl=$('#u-ok'), errEl=$('#u-alert'); hide(errEl); hide(okEl);

    const usuario = ($('#u-usuario')?.value||'').trim();
    const clave   = ($('#u-clave')?.value||'').trim();
    const nombres = ($('#u-nombres')?.value||'').trim();
    const apellidos=($('#u-apellidos')?.value||'').trim();
    const id_emp  = ($('#u-empresa')?.value||'').trim();
    const id_rol  = ($('#u-rol')?.value||'0').trim();

    if (!/^\d{8,11}$/.test(usuario)) { show(errEl,'Usuario debe ser DNI (8) o CE (hasta 11) solo dígitos.'); return; }
    if (U.editId===0 && clave.length<6) { show(errEl,'La contraseña debe tener al menos 6 caracteres.'); return; }
    if (!nombres || !apellidos) { show(errEl,'Nombres y apellidos son obligatorios.'); return; }
    if (!id_emp) { show(errEl,'Selecciona una empresa.'); return; }

    const fd = new FormData();
    fd.append('usuario', usuario);
    fd.append('nombres', nombres);
    fd.append('apellidos', apellidos);
    fd.append('id_empresa', id_emp);
    fd.append('id_rol', id_rol || '0');

    if (U.editId>0) {
      fd.append('action','update');
      fd.append('id', String(U.editId));
      if (clave !== '') fd.append('clave', clave); // solo si quieren cambiar
    } else {
      fd.append('action','create');
      fd.append('clave', clave);
    }

    btn.disabled = true; const old = btn.textContent; btn.textContent = U.editId>0 ? 'Guardando…' : 'Creando…';
    try {
      const r = await j(apiUrl, { method:'POST', body: fd });
      show(okEl, `Usuario ${U.editId>0 ? 'actualizado' : 'creado'} con éxito. Id: ${r.id || U.editId}.`);
      // reset
      U.editId = 0;
      $('#u-form')?.reset();
      const saveBtn = $('#u-guardar'); if (saveBtn) saveBtn.textContent = 'Crear';
      await cargarLista();
    } catch (err) {
      show(errEl, err.message || 'No se pudo guardar');
    } finally {
      btn.disabled=false; btn.textContent=old;
    }
  });

  // cerrar ok
  slot.addEventListener('click', (e)=>{ const x=e.target?.closest('.u-ok-close'); if(!x) return; hide($('#u-ok')); });

  // Editar
  slot.addEventListener('click', (e)=>{
    const btn = e.target?.closest('.u-edit'); if (!btn) return;
    const tr = btn.closest('tr[data-id]'); if (!tr) return;
    U.editId = parseInt(tr.dataset.id,10) || 0;
    $('#u-usuario').value   = tr.dataset.usuario || '';
    $('#u-nombres').value   = tr.dataset.nombres || '';
    $('#u-apellidos').value = tr.dataset.apellidos || '';
    $('#u-empresa').value   = tr.dataset.id_empresa || '';
    $('#u-rol').value       = tr.dataset.id_rol || '0';
    $('#u-clave').value     = '';
    const b = $('#u-guardar'); if (b) b.textContent = 'Guardar cambios';
    hide($('#u-ok')); hide($('#u-alert'));
    $('#u-usuario').focus();
  });

  // Eliminar
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.u-del'); if (!btn) return;
    const id = parseInt(btn.dataset.id,10)||0; if (!id) return;
    if (!confirm(`¿Eliminar usuario #${id}?`)) return;
    btn.disabled = true;
    try {
      const fd = new FormData(); fd.append('action','delete'); fd.append('id', String(id));
      await j(apiUrl, { method:'POST', body: fd });
      const okEl = $('#u-ok'); show(okEl, `Usuario #${id} eliminado.`);
      await cargarLista();
    } catch (err) {
      const a=$('#l-alert'); a.textContent = err.message || 'No se pudo eliminar'; a.classList.remove('d-none');
    } finally { btn.disabled=false; }
  });

  // Filtros
  slot.addEventListener('change', (e)=>{
    if (e.target?.id === 'f-empresa') { L.empresa = parseInt(e.target.value,10)||0; L.page=1; cargarLista(); }
    if (e.target?.id === 'f-rol')     { L.rol     = parseInt(e.target.value,10)||0; L.page=1; cargarLista(); }
  });
  slot.addEventListener('input', debounce((e)=>{
    if (e.target?.id === 'f-q') { L.q = e.target.value || ''; L.page=1; cargarLista(); }
  },300));

  // Paginación
  slot.addEventListener('click', (e)=>{
    const a = e.target?.closest('#u-pager a[data-page]'); if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10); if (p>0) { L.page=p; cargarLista(); }
  });

  // Refresh al reabrir modal
  slot.__usuariosRefresh = async function(){
    hide($('#u-alert')); hide($('#u-ok'));
    // reset form
    $('#u-form')?.reset();
    $('#u-rol') && ($('#u-rol').value='0');
    const saveBtn = $('#u-guardar'); if (saveBtn) saveBtn.textContent='Crear';
    U.editId = 0;
    // reset filtros
    const fe=$('#f-empresa'), fq=$('#f-q'), fr=$('#f-rol');
    if (fe) fe.value='0'; if (fq) fq.value=''; if (fr) fr.value='0';
    L.empresa=0; L.q=''; L.rol=0; L.page=1;
    await cargarEmpresas();
    await cargarRoles();
    await cargarLista();
  };

  // Primera carga
  slot.__usuariosRefresh();
}
