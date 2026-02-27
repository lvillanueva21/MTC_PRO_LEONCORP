// modules/consola/empresas/gestion.js
export function init(slot, apiUrl) {
  if (slot.__modEmpresasBound) { slot.__modEmpresasRefresh?.(); return; }
  slot.__modEmpresasBound = true;

  // ---------- Helpers ----------
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el, msg='') => { if(!el) return; const span=el.querySelector?.('.msg'); if(span) span.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); el.classList.add('show'); };
  const hide = el => { if(!el) return; el.classList.add('d-none'); el.classList.remove('show'); };
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url, opts={}) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }
    // Normalizador de URLs relativas -> absolutas respecto al proyecto (ÚNICO BLOQUE VÁLIDO)
  const PROJECT_ROOT = (location.pathname.split('/modules/')[0] || '');
  function assetUrl(relPath) {
    if (!relPath) return '';
    const clean = String(relPath).replace(/^\/+/, '');
    const base = PROJECT_ROOT ? PROJECT_ROOT : '';
    return `${base}/${clean}`;
  }

  // Preview de logo
  const defaultLogo = slot.getAttribute('data-default-logo') || '../../../dist/img/user2-160x160.jpg';
  function emp_setLogoPreview(url){ const p = $('#emp-logo-prev'); if (p) p.style.backgroundImage = `url('${url}')`; }
  function emp_setLogoCaption(txt){ const c = $('#emp-logo-cap'); if (c) c.textContent = txt || 'Sin logo por el momento'; }
  function emp_setLogoSize(bytes){
    const s = $('#emp-logo-size'); if (!s) return;
    if (!bytes){ s.textContent=''; s.classList.add('d-none'); return; }
    const kb = (bytes/1024).toFixed(1);
    s.textContent = `Peso de archivo cargado: ${kb} KB`;
    s.classList.remove('d-none');
  }
  function emp_resetLogo(){ emp_setLogoPreview(defaultLogo); emp_setLogoCaption('Sin logo por el momento'); emp_setLogoSize(0); }
  function fileNameFromPath(p){ try{ return (p||'').split('/').pop().split('\\').pop(); }catch{ return ''; } }

  // ---------- Estado ----------
  const R = { q:'', page:1, per_page:8, rows:[], editId:0, lastCreatedId:0 };
  const E = { q:'', page:1, per_page:8, rows:[], editId:0 };
  const C = { tipos:[], depas:[], repleg:[] }; // combos

  // ---------- Carga de combos ----------
  async function cargarCombos(prefillRepId=0){
    const res = await j(`${apiUrl}?action=combos`);
    C.tipos = res.tipos || []; C.depas = res.depas || []; C.repleg = res.repleg || [];

    const selTipo = $('#emp-tipo'), selDepa = $('#emp-depa'), selRep = $('#emp-repleg');
    if (selTipo) selTipo.innerHTML = `<option value="">Selecciona…</option>` + C.tipos.map(t=>`<option value="${t.id}">${esc(t.nombre)}</option>`).join('');
    if (selDepa) selDepa.innerHTML = `<option value="">Selecciona…</option>` + C.depas.map(d=>`<option value="${d.id}">${esc(d.nombre)}</option>`).join('');
    if (selRep)  selRep.innerHTML  = `<option value="">Selecciona…</option>` + C.repleg.map(r=>`<option value="${r.id}">${esc(r.nom)}</option>`).join('');
    if (prefillRepId && selRep) selRep.value = String(prefillRepId);
  }

  // ---------- Representantes: pintar y paginar ----------
  function rep_pintarTabla(rows){
    const tb = $('#rep-tbody'); if (!tb) return;
    tb.innerHTML = rows.map(r => `
      <tr data-id="${r.id}" data-nombres="${esc(r.nombres)}" data-apellidos="${esc(r.apellidos)}" data-documento="${esc(r.documento)}">
        <td class="text-muted">${r.id}</td>
        <td>${esc(r.nombres)} ${esc(r.apellidos)}</td>
        <td>${esc(r.documento)}</td>
        <td>
          <div class="actions-inline">
            <button class="btn btn-sm btn-primary rep-edit">Editar</button>
            <button class="btn btn-sm btn-danger rep-del">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');
  }
  function rep_pintarPager(total){
    const ul = $('#rep-pager'); const pages = Math.max(1, Math.ceil(total / R.per_page));
    R.page = Math.min(R.page, pages); const cur = R.page; const items = [];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«', cur<=1?'disabled':'' );
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }
  async function rep_cargarLista(){
    hide($('#rep-alert'));
    const qs = new URLSearchParams({action:'rep_list', q:R.q, page:R.page, per_page:R.per_page});
    try{
      const res = await j(`${apiUrl}?${qs}`);
      R.rows = res.data || [];
      rep_pintarTabla(R.rows); rep_pintarPager(res.total || 0);
    }catch(err){
      const a=$('#rep-alert'); a.textContent = err.message || 'Error al listar'; a.classList.remove('d-none');
    }
  }

  // ---------- Empresas: pintar y paginar ----------
    function emp_pintarTabla(rows){
    const tb = $('#emp-tbody'); if (!tb) return;
    tb.innerHTML = rows.map(e => {
      const logo = assetUrl(e.logo_path || '');
      return `
        <tr data-id="${e.id}"
            data-nombre="${esc(e.nombre)}"
            data-razon_social="${esc(e.razon_social)}"
            data-ruc="${esc(e.ruc)}"
            data-direccion="${esc(e.direccion)}"
            data-id_tipo="${e.id_tipo}"
            data-id_depa="${e.id_depa}"
            data-id_repleg="${e.id_repleg}"
            data-logo="${esc(logo)}">
          <td class="text-muted">${e.id}</td>
          <td><div class="fw-semibold">${esc(e.nombre)}</div><div class="small text-muted">${esc(e.razon_social)}</div></td>
          <td>${esc(e.ruc)}</td>
          <td><div>${esc(e.tipo)}</div><div class="small text-muted">${esc(e.depa)}</div></td>
          <td>${esc(e.repleg)}</td>
          <td>
            <div class="actions-inline">
              <button class="btn btn-sm btn-primary emp-edit">Editar</button>
              <button class="btn btn-sm btn-danger emp-del">Eliminar</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }
  function emp_pintarPager(total){
    const ul = $('#emp-pager'); const pages = Math.max(1, Math.ceil(total / E.per_page));
    E.page = Math.min(E.page, pages); const cur = E.page; const items=[];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }
  async function emp_cargarLista(){
    hide($('#emp-alert'));
    const qs = new URLSearchParams({action:'emp_list', q:E.q, page:E.page, per_page:E.per_page});
    try{
      const res = await j(`${apiUrl}?${qs}`);
      E.rows = res.data || [];
      emp_pintarTabla(E.rows); emp_pintarPager(res.total || 0);
    }catch(err){
      const a=$('#emp-alert'); a.textContent = err.message || 'Error al listar'; a.classList.remove('d-none');
    }
  }

  // ---------- Modo crear/editar (Representantes) ----------
  function rep_setCreate(){
    const f = $('#rep-form'); if (!f) return;
    f.reset(); f.elements['id'].value = '';
    $('#rep-form-title').textContent = '1. Nuevo representante legal';
    $('#rep-guardar').textContent = 'Crear';
    $('#rep-cancelar').classList.add('d-none');
  }
  function rep_setEdit(tr){
    const f = $('#rep-form'); if (!f || !tr) return;
    f.elements['id'].value = tr.dataset.id;
    f.elements['nombres'].value = tr.dataset.nombres || '';
    f.elements['apellidos'].value = tr.dataset.apellidos || '';
    f.elements['documento'].value = tr.dataset.documento || '';
    if (f.elements['clave']) f.elements['clave'].value = '';
    $('#rep-form-title').textContent = `Editar representante #${tr.dataset.id}`;
    $('#rep-guardar').textContent = 'Guardar cambios';
    $('#rep-cancelar').classList.remove('d-none');
    f.querySelector('#rep-nombres')?.focus();
  }

  // ---------- Modo crear/editar (Empresas) ----------
    function emp_setCreate(prefillRepId=0){
    const f = $('#emp-form'); if (!f) return;
    f.reset(); f.elements['id'].value = '';
    $('#emp-form-title').textContent = '3. Nueva empresa';
    $('#emp-guardar').textContent = 'Crear';
    $('#emp-cancelar').classList.add('d-none');
    if (prefillRepId) $('#emp-repleg') && ($('#emp-repleg').value = String(prefillRepId));
    const file = $('#emp-logo'); if (file) file.value = '';
    emp_resetLogo();
  }

  function emp_setEdit(tr){
    const f = $('#emp-form'); if (!f || !tr) return;
    f.elements['id'].value           = tr.dataset.id;
    f.elements['nombre'].value       = tr.dataset.nombre || '';
    f.elements['razon_social'].value = tr.dataset.razon_social || '';
    f.elements['ruc'].value          = tr.dataset.ruc || '';
    f.elements['direccion'].value    = tr.dataset.direccion || '';
    f.elements['id_tipo'].value      = tr.dataset.id_tipo || '';
    f.elements['id_depa'].value      = tr.dataset.id_depa || '';
    f.elements['id_repleg'].value    = tr.dataset.id_repleg || '';
    $('#emp-form-title').textContent = `Editar empresa #${tr.dataset.id}`;
    $('#emp-guardar').textContent = 'Guardar cambios';
    $('#emp-cancelar').classList.remove('d-none');
    f.querySelector('#emp-nombre')?.focus();

    const file = $('#emp-logo'); if (file) file.value = '';
    const ruta = tr.dataset.logo || '';
    if (ruta){
      emp_setLogoPreview(ruta);
      emp_setLogoCaption(fileNameFromPath(ruta));
      emp_setLogoSize(0);
    }else{
      emp_resetLogo();
    }
  }
  // Logo: preview y validaciones
  slot.addEventListener('change', (e)=>{
    if (e.target?.id !== 'emp-logo') return;
    const f = e.target.files?.[0]; hide($('#emp-alert'));
    if (!f){ emp_resetLogo(); return; }
    const okType = ['image/jpeg','image/png','image/webp'].includes(f.type);
    if (!okType){ e.target.value=''; emp_resetLogo(); show($('#emp-alert'),'Formato no permitido (PNG/JPG/WebP).'); return; }
    if (f.size > 5*1024*1024){ e.target.value=''; emp_resetLogo(); show($('#emp-alert'),'Máximo 5MB.'); return; }
    const url = URL.createObjectURL(f);
    emp_setLogoPreview(url);
    emp_setLogoCaption(f.name || 'archivo');
    emp_setLogoSize(f.size);
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  });
  // ---------- Acciones: crear/actualizar/eliminar ----------
  slot.addEventListener('click', async (e)=>{
    // REP: guardar
    if (e.target.closest('#rep-guardar')) {
      const f = $('#rep-form'); const id = parseInt(f.elements['id'].value||'0',10);
      const data = new FormData(f);
      data.append('action', id>0 ? 'rep_update' : 'rep_create');
      try{
        const r = await j(apiUrl, {method:'POST', body:data});
        show($('#emp-ok'), id>0 ? 'Representante actualizado.' : 'Representante creado.');
        R.lastCreatedId = id>0 ? id : (r.id||0);
        await rep_cargarLista();
        // refrescar combo de reps y prefijar si es creación
        await cargarCombos(R.lastCreatedId);
        emp_setCreate(R.lastCreatedId);
        rep_setCreate();
      }catch(err){ show($('#rep-alert'), err.message || 'Error guardando representante'); }
      return;
    }
    // REP: cancelar
    if (e.target.closest('#rep-cancelar')) { rep_setCreate(); return; }
    // REP: editar
    const repEdit = e.target.closest('.rep-edit');
    if (repEdit) { rep_setEdit(repEdit.closest('tr')); return; }
    // REP: eliminar
    const repDel = e.target.closest('.rep-del');
    if (repDel) {
      const tr = repDel.closest('tr'); if (!tr) return;
      if (!confirm('¿Eliminar este representante legal?')) return;
      const fd = new FormData(); fd.append('action','rep_delete'); fd.append('id', tr.dataset.id);
      try{ await j(apiUrl,{method:'POST', body:fd}); show($('#emp-ok'),'Representante eliminado.'); await rep_cargarLista(); await cargarCombos(); }
      catch(err){ show($('#rep-alert'), err.message || 'No se pudo eliminar'); }
      return;
    }

    // EMP: guardar
    if (e.target.closest('#emp-guardar')) {
      const f = $('#emp-form'); const id = parseInt(f.elements['id'].value||'0',10);
      const data = new FormData(f);
      data.append('action', id>0 ? 'emp_update' : 'emp_create');
      try{
        await j(apiUrl, {method:'POST', body:data});
        show($('#emp-ok'), id>0 ? 'Empresa actualizada.' : 'Empresa creada.');
        await emp_cargarLista();
        emp_setCreate();
      }catch(err){ show($('#emp-alert'), err.message || 'Error guardando empresa'); }
      return;
    }
    // EMP: cancelar
    if (e.target.closest('#emp-cancelar')) { emp_setCreate(); return; }
    // EMP: editar
    const empEdit = e.target.closest('.emp-edit');
    if (empEdit) { emp_setEdit(empEdit.closest('tr')); return; }
    // EMP: eliminar
    const empDel = e.target.closest('.emp-del');
    if (empDel) {
      const tr = empDel.closest('tr'); if (!tr) return;
      if (!confirm('¿Eliminar esta empresa?')) return;
      const fd = new FormData(); fd.append('action','emp_delete'); fd.append('id', tr.dataset.id);
      try{ await j(apiUrl,{method:'POST', body:fd}); show($('#emp-ok'),'Empresa eliminada.'); await emp_cargarLista(); }
      catch(err){ show($('#emp-alert'), err.message || 'No se pudo eliminar'); }
      return;
    }

    // Cerrar alerta OK
    if (e.target.closest('.emp-ok-close')) hide($('#emp-ok'));
  });

  // Paginadores
  slot.addEventListener('click', (e)=>{
    const a1 = e.target.closest('#rep-pager a[data-page]'); if (a1){ e.preventDefault(); const p=parseInt(a1.dataset.page,10); if(!isNaN(p)){ R.page=p; rep_cargarLista(); } return; }
    const a2 = e.target.closest('#emp-pager a[data-page]'); if (a2){ e.preventDefault(); const p=parseInt(a2.dataset.page,10); if(!isNaN(p)){ E.page=p; emp_cargarLista(); } return; }
  });

  // Filtros (debounced)
  slot.addEventListener('input', debounce((e)=>{
    if (e.target.id==='rep-q'){ R.q=e.target.value||''; R.page=1; rep_cargarLista(); }
    if (e.target.id==='emp-q'){ E.q=e.target.value||''; E.page=1; emp_cargarLista(); }
  }, 300));

  // ---------- REFRESH (al reabrir modal) ----------
  slot.__modEmpresasRefresh = async function(){
    hide($('#emp-err')); hide($('#emp-ok')); hide($('#rep-alert')); hide($('#emp-alert'));
    R.q=''; R.page=1; R.editId=0; R.lastCreatedId=0;
    E.q=''; E.page=1; E.editId=0;
    $('#rep-q') && ($('#rep-q').value=''); $('#emp-q') && ($('#emp-q').value='');
    rep_setCreate(); emp_setCreate();
    await cargarCombos();
    await Promise.all([rep_cargarLista(), emp_cargarLista()]);
  };

  // ---------- Primera carga ----------
  slot.__modEmpresasRefresh();
}
