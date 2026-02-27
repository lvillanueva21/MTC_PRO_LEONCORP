// modules/consola/publicidades/gestion.js
export function init(slot, apiUrl){
  // Si ya fue enlazado, solo refrescamos todo
  if (slot.__pbBound) { slot.__pbRefresh?.(); return; }
  slot.__pbBound = true;

  /* ----------------- Helpers ----------------- */
  const $  = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').toString().replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el,msg='')=>{ if(!el) return; const span=el.querySelector?.('.msg'); if(span) span.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); };
  const hide = el => { if(!el) return; el.classList.add('d-none'); };
  const debounce = (fn,ms=300)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url,opts={}){ const r=await fetch(url,{credentials:'same-origin',...opts}); const d=await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'})); if(!r.ok||!d.ok) throw new Error(d.msg||`HTTP ${r.status}`); return d; }

  /* ----------------- Estado ----------------- */
  const TOP = { editId:0 };                         // publicidad en edición
  const L   = { q:'', estado:'', page:1, per_page:5, rows:[], openId:0 }; // listado publicidades
  const A   = { pid:0 };                            // audiencias por publicidad

  // Grupos
  const G   = { q:'', estado:'', page:1, per_page:5, rows:[], editId:0, selId:0, selNombre:'', selSlots:1, selActivo:1 };
  const GI  = { q:'', rows:[] };                    // items del grupo seleccionado
  const GA  = { };                                  // audiencias del grupo seleccionado (usa G.selId)

  /* ----------------- Catálogos ----------------- */
  async function cargarEmpresas(selectIds=['#a-empresa','#ga-empresa']){
    try{
      const r = await j(`${apiUrl}?action=empresas`);
      for(const sid of selectIds){
        const sel = $(sid); if(!sel) continue;
        sel.innerHTML = '<option value="0">—</option>' + (r.data||[]).map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      }
    }catch{}
  }
  async function cargarRoles(selectIds=['#a-rol','#ga-rol']){
    try{
      const r = await j(`${apiUrl}?action=roles`);
      for(const sid of selectIds){
        const sel = $(sid); if(!sel) continue;
        sel.innerHTML = '<option value="0">—</option>' + (r.data||[]).map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      }
    }catch{}
  }

  /* ----------------- Chips de etiquetas (espacio/Enter) + sugerencias ----------------- */
  const boxEl    = () => $('#p-tags');
  const inputEl  = () => $('#p-tags-input');
  const hiddenEl = () => $('#p-etiquetas');
  const suggEl   = () => $('#p-suggest');

  const readTags = () => {
    const box = boxEl(); if (!box) return [];
    if (box.dataset.tags){ try{ const a=JSON.parse(box.dataset.tags); return Array.isArray(a)?a:[]; }catch{ return []; } }
    const arr=[]; box.querySelectorAll('.chip .txt')?.forEach(n=>arr.push(n.textContent||'')); return arr;
  };
  const writeTags = (arr) => {
    const box = boxEl(); if (!box) return;
    box.dataset.tags = JSON.stringify(arr);
    // render
    box.querySelectorAll('.chip').forEach(c=>c.remove());
    const input = inputEl(); if (!input) return;
    arr.forEach((t,i)=>{
      const chip = document.createElement('span');
      chip.className='chip';
      chip.innerHTML = `<span class="txt">${esc(t)}</span><button class="x" type="button" data-i="${i}" aria-label="Quitar">×</button>`;
      box.insertBefore(chip,input);
    });
    const hid = hiddenEl(); if (hid) hid.value = arr.join(',');
  };
  const norm = t => (t||'').replace(/\s+/g,' ').trim().slice(0,50);
  const addTag = (text) => {
    const t = norm(text); if (!t) return;
    const tags = readTags();
    if (!tags.includes(t)){ tags.push(t); writeTags(tags); }
    if (inputEl()) inputEl().value = '';
    hideSuggest();
  };
  const removeTagAt = (idx) => {
    const tags = readTags(); if (idx>=0 && idx<tags.length){ tags.splice(idx,1); writeTags(tags); }
  };

  function hideSuggest(){ const el=suggEl(); if(el){ el.classList.remove('show'); el.innerHTML=''; } }
  function showSuggest(list){
    const el = suggEl(); if(!el) return;
    if (!list || !list.length){ hideSuggest(); return; }
    el.innerHTML = list.map(t=>`<button type="button" class="chip-sel" data-tag="${esc(t)}">${esc(t)}</button>`).join('');
    el.classList.add('show');
  }

  // focus fácil y evitar submit con Enter fuera del input
  slot.addEventListener('mousedown', (e)=>{
    const area = e.target?.closest('#p-tags'); if (!area) return;
    if (!e.target.closest('#p-tags-input')){ e.preventDefault(); inputEl()?.focus(); }
  });
  slot.addEventListener('keydown', (e)=>{
    if (e.target && e.target.closest('#p-form') && e.key==='Enter'){
      if (!e.target.closest('#p-tags-input')) e.preventDefault();
    }
  });

  // tipo chips: separador por espacio o Enter
  slot.addEventListener('keydown', (e)=>{
    if (!e.target?.closest || !e.target.closest('#p-tags-input')) return;
    const input=inputEl(); if(!input) return;
    if (e.key==='Enter' || e.key===' '){
      const v = input.value || '';
      if (norm(v)!==''){ e.preventDefault(); addTag(v); }
      else if (e.key===' '){ e.preventDefault(); } // evita espacios múltiples
    } else if (e.key==='Backspace' && input.value===''){
      const tags = readTags(); if (tags.length){ tags.pop(); writeTags(tags); }
    }
  });

  // sugerencias al escribir (prefijo)
  slot.addEventListener('input', debounce(async (e)=>{
    if (!e.target?.closest || !e.target.closest('#p-tags-input')) return;
    const v = (inputEl()?.value || '').trim();
    if (/\s/.test(v)){ // si pegó espacios, intenta cortar
      const parts = v.split(/\s+/);
      for (let i=0;i<parts.length-1;i++) addTag(parts[i]);
      inputEl().value = norm(parts.at(-1));
    }
    const q = (inputEl()?.value || '').trim();
    if (q.length<1){ hideSuggest(); return; }
    try{
      const r = await j(`${apiUrl}?action=tags_suggest&q=${encodeURIComponent(q)}&limit=10`);
      // filtra los ya añadidos
      const have = new Set(readTags().map(x=>x.toLowerCase()));
      const list = (r.data||[]).filter(t=>!have.has(t.toLowerCase()));
      showSuggest(list);
    }catch{ hideSuggest(); }
  }, 180));

  // click sugerencias
  slot.addEventListener('click', (e)=>{
    const btn=e.target?.closest('.chip-suggest .chip-sel'); if(!btn) return;
    const tag = btn.dataset.tag || ''; addTag(tag);
  });

  // quitar chip
  slot.addEventListener('click', (e)=>{
    const btn=e.target?.closest('.chip .x'); if(!btn) return;
    const i = parseInt(btn.dataset.i,10); if(Number.isNaN(i)) return;
    removeTagAt(i); inputEl()?.focus();
  });

  // ocultar sugerencias si clic fuera
  document.addEventListener('click', (e)=>{
    if (!slot.contains(e.target)){ hideSuggest(); }
  });

  /* ----------------- Crear/Editar Publicidad ----------------- */
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('#p-guardar'); if(!btn) return;
    hide($('#p-err')); hide($('#p-ok'));

    const titulo = ($('#p-titulo')?.value||'').trim();
    if (!titulo){ show($('#p-err'),'El título es obligatorio'); return; }
    // asegura CSV actualizado
    writeTags(readTags());

    const fd = new FormData($('#p-form'));
    if (TOP.editId>0){ fd.append('action','update'); fd.append('id',String(TOP.editId)); }
    else { fd.append('action','create'); }

    btn.disabled=true; const old=btn.textContent; btn.textContent = TOP.editId>0 ? 'Guardando…' : 'Creando…';
    try{
      const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'});
      const jx=await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
      if (!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`);
      const msg = TOP.editId>0 ? 'Publicidad actualizada' : 'Publicidad creada';
      show($('#p-ok'), `${msg} (ID ${jx.id ?? TOP.editId}).`);
      // reset editor
      $('#p-form')?.reset(); TOP.editId=0; $('#p-guardar').textContent='Crear';
      writeTags([]); hideSuggest();
      await cargarLista();
    }catch(err){ show($('#p-err'), err.message||'Error al guardar'); }
    finally{ btn.disabled=false; btn.textContent=old; }
  });

  function cargarEnEditor(row){
    TOP.editId = row.id;
    $('#p-titulo').value = row.titulo || '';
    $('#p-desc').value   = row.descripcion || '';
    $('#p-img').value    = '';
    writeTags(Array.isArray(row.tags)?row.tags:[]);
    $('#p-guardar').textContent='Guardar cambios';
    $('#p-titulo')?.focus();
  }

  /* ----------------- Listado Publicidades ----------------- */
  function pintarTabla(rows){
    const tb = $('#l-tbody');
    const isOpen = id => L.openId===id;
    const main = r => `
      <tr class="pb-row" data-id="${r.id}">
        <td>${r.id}</td>
        <td class="name-cell">
          <span class="name-text">${esc(r.titulo)}</span>
          <span class="status-dot ${r.activo?'status-on':'status-off'}"></span>
        </td>
        <td class="text-nowrap">
          <div class="actions-inline">
            <button class="btn btn-sm btn-primary l-edit" data-id="${r.id}">Editar</button>
            <button class="btn btn-sm btn-secondary l-aud" data-id="${r.id}" data-titulo="${esc(r.titulo)}">Audiencias</button>
            <button class="btn btn-sm ${r.activo?'btn-warning':'btn-success'} l-toggle" data-id="${r.id}">
              ${r.activo?'Desactivar':'Activar'}
            </button>
            <button class="btn btn-sm btn-danger l-del" data-id="${r.id}">Eliminar</button>
          </div>
        </td>
      </tr>`;
    const detail = r => `
      <tr class="pb-detail ${isOpen(r.id)?'':'d-none'}">
        <td></td>
        <td colspan="2">
          <div><strong>Descripción:</strong> ${esc(r.descripcion||'—')}</div>
          <div class="mt-2 chips-read">
            ${(r.tags||[]).map(t=>`<span class="chip soft">${esc(t)}</span>`).join('') || '<span class="text-muted">Sin etiquetas</span>'}
          </div>
        </td>
      </tr>`;
    tb.innerHTML = rows.map(r=>main(r)+detail(r)).join('');
  }
  function pintarPager(total){
    const ul=$('#l-pager'); const pages=Math.max(1,Math.ceil(total/L.per_page));
    L.page=Math.min(L.page,pages); const cur=L.page; const items=[];
    const add=(p,lab,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${lab}</a></li>`);
    add(cur-1,'«',cur<=1?'disabled':''); let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':''); add(cur+1,'»',cur>=pages?'disabled':''); ul.innerHTML=items.join('');
  }
  async function cargarLista(){
    hide($('#l-err'));
    const qs = new URLSearchParams({ action:'list', q:L.q, estado:L.estado, page:L.page, per_page:L.per_page });
    try{
      const r=await j(`${apiUrl}?${qs.toString()}`);
      L.rows=r.data||[]; if(!L.rows.some(x=>x.id===L.openId)) L.openId=0;
      pintarTabla(L.rows); pintarPager(r.total||0);
    }catch(err){ show($('#l-err'), err.message||'Error al listar'); }
  }
  // filtros + pager + toggles de fila
  slot.addEventListener('input', debounce(e=>{
    if (e.target?.id==='f-q'){ L.q=e.target.value; L.page=1; cargarLista(); }
  },300));
  slot.addEventListener('change', e=>{
    if (e.target?.id==='f-estado'){ L.estado=e.target.value; L.page=1; cargarLista(); }
  });
  slot.addEventListener('click', e=>{
    const a=e.target?.closest('#l-pager a[data-page]'); if(a){ e.preventDefault(); const li=a.parentElement;
      if(!li.classList.contains('disabled')&&!li.classList.contains('active')){ const p=parseInt(a.dataset.page,10); if(p>0){ L.page=p; cargarLista(); } }
      return;
    }
    const tr = e.target?.closest('tr.pb-row'); if(tr && !e.target.closest('button')){
      const id=parseInt(tr.dataset.id,10); L.openId=(L.openId===id)?0:id; if(Array.isArray(L.rows)) pintarTabla(L.rows);
    }
  });

  // acciones de fila: editar / audiencias / toggle / eliminar
  slot.addEventListener('click', e=>{
    // editar
    const be=e.target?.closest('.l-edit'); if(be){
      const id=parseInt(be.dataset.id,10); const row=L.rows.find(r=>r.id===id); if(row) cargarEnEditor(row);
      return;
    }
    // audiencias
    const ba=e.target?.closest('.l-aud'); if(ba){
      const id=parseInt(ba.dataset.id,10); const titulo=ba.dataset.titulo||''; aud_setPid(id,titulo); return;
    }
  });
  slot.addEventListener('click', async e=>{
    // toggle activo
    const bt=e.target?.closest('.l-toggle'); if(bt){
      const id=parseInt(bt.dataset.id,10); const row=L.rows.find(r=>r.id===id); if(!row) return;
      const nuevo=row.activo?0:1; if(!confirm(`${nuevo?'Activar':'Desactivar'} publicidad?`)) return;
      bt.disabled=true; try{
        const fd=new FormData(); fd.append('action','set_activo'); fd.append('id',String(id)); fd.append('activo',String(nuevo));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await cargarLista();
      }catch(err){ show($('#l-err'), err.message||'No se pudo actualizar estado'); } finally{ bt.disabled=false; }
      return;
    }
    // eliminar
    const bd=e.target?.closest('.l-del'); if(bd){
      const id=parseInt(bd.dataset.id,10); if(!id) return;
      if(!confirm('¿Eliminar publicidad permanentemente? Esta acción no se puede deshacer.')) return;
      bd.disabled=true; try{
        const fd=new FormData(); fd.append('action','delete'); fd.append('id',String(id));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await cargarLista();
      }catch(err){ show($('#l-err'), err.message||'No se pudo eliminar'); } finally{ bd.disabled=false; }
    }
  });

  /* ----------------- Audiencias por Publicidad ----------------- */
  function toggleInputsAud(){
    const t = $('#a-tipo')?.value || 'TODOS';
    $('#wrap-emp').classList.toggle('d-none', !(t==='EMPRESA' || t==='EMPRESA_ROL'));
    $('#wrap-rol').classList.toggle('d-none', !(t==='ROL' || t==='EMPRESA_ROL'));
    $('#wrap-user').classList.toggle('d-none', t!=='USUARIO');
  }
  slot.addEventListener('change', e=>{ if(e.target?.id==='a-tipo') toggleInputsAud(); });

  async function cargarTargets(){
    const tb=$('#a-tbody'); if(tb) tb.innerHTML='<tr><td colspan="4">Cargando…</td></tr>';
    if (!A.pid) return;
    try{
      const r=await j(`${apiUrl}?action=targets_list&publicidad_id=${A.pid}`);
      const rows=r.data||[];
      tb.innerHTML = rows.map(x=>`
        <tr>
          <td>${x.id}</td>
          <td>${esc(x.tipo)}</td>
          <td>${
            x.tipo==='USUARIO'      ? `${esc(x.u_usuario)} — ${esc(x.u_nombre||'')}` :
            x.tipo==='ROL'          ? `${esc(x.rol_nombre||'')}` :
            x.tipo==='EMPRESA'      ? `${esc(x.emp_nombre||'')}` :
            x.tipo==='EMPRESA_ROL'  ? `${esc(x.emp_nombre||'')} / ${esc(x.rol_nombre||'')}` :
            '—'
          }</td>
          <td class="text-end">
            <button class="btn btn-sm btn-danger a-del" data-id="${x.id}">Quitar</button>
          </td>
        </tr>
      `).join('') || '<tr><td colspan="4" class="text-muted">Sin reglas</td></tr>';
    }catch(err){ $('#a-tbody').innerHTML = `<tr><td colspan="4" class="text-danger">${esc(err.message||'Error')}</td></tr>`; }
  }
  async function cargarPreview(){
    const lbl=$('#a-prev'); if(!A.pid){ lbl.textContent='—'; return; }
    try{ const r=await j(`${apiUrl}?action=preview&publicidad_id=${A.pid}`); lbl.textContent=`Destinatarios estimados: ${r.total||0}`; }
    catch{ lbl.textContent='—'; }
  }
  // buscar usuarios audiencias publicidad
  slot.addEventListener('click', async (e)=>{
    const btn=e.target?.closest('#a-buscar'); if(!btn) return;
    const q = ($('#a-uq')?.value||'').trim(); const res = $('#a-ures'); res.innerHTML='Buscando…';
    try{
      const r = await j(`${apiUrl}?action=users_search&q=${encodeURIComponent(q)}&limit=20`);
      res.innerHTML = (r.data||[]).map(u=>`
        <label class="form-check d-block"><input type="radio" name="sel_user_pub" value="${u.id}">
          ${esc(u.nombres)} ${esc(u.apellidos)} — ${esc(u.usuario)} (${esc(u.empresa)})</label>
      `).join('') || '<div class="text-muted">Sin resultados</div>';
    }catch(err){ res.innerHTML = `<div class="text-danger">${esc(err.message||'Error')}</div>`; }
  });
  // añadir/quitar reglas publicidad
  slot.addEventListener('click', async (e)=>{
    const add=e.target?.closest('#a-add'); if(add){
      if(!A.pid) return;
      const tipo = $('#a-tipo')?.value || 'TODOS';
      const emp  = parseInt($('#a-empresa')?.value||'0',10);
      const rol  = parseInt($('#a-rol')?.value||'0',10);
      let uid=0; const pick=slot.querySelector('input[name="sel_user_pub"]:checked'); if(pick) uid=parseInt(pick.value,10)||0;

      const fd=new FormData(); fd.append('action','target_add'); fd.append('publicidad_id',String(A.pid)); fd.append('tipo',tipo);
      if (tipo==='USUARIO') fd.append('usuario_id', String(uid||0));
      if (tipo==='ROL')     fd.append('rol_id', String(rol||0));
      if (tipo==='EMPRESA') fd.append('empresa_id', String(emp||0));
      if (tipo==='EMPRESA_ROL'){ fd.append('empresa_id', String(emp||0)); fd.append('rol_id', String(rol||0)); }

      add.disabled=true; try{
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await cargarTargets(); await cargarPreview();
      }catch(err){ alert(err.message||'No se pudo añadir'); } finally{ add.disabled=false; }
      return;
    }
    const del=e.target?.closest('.a-del'); if(del){
      const id=parseInt(del.dataset.id,10); if(!id) return;
      if(!confirm('Quitar esta regla?')) return;
      del.disabled=true; try{
        const fd=new FormData(); fd.append('action','target_del'); fd.append('id',String(id));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await cargarTargets(); await cargarPreview();
      }catch(err){ alert(err.message||'No se pudo quitar'); } finally{ del.disabled=false; }
    }
  });

  function aud_setPid(id, titulo){
    A.pid = id; $('#a-hint').classList.add('d-none'); $('#a-panel').classList.remove('d-none');
    $('#a-pub-actual').textContent = titulo || `ID ${id}`;
    cargarTargets(); cargarPreview();
  }

  /* ----------------- Grupos: CRUD / Lista ----------------- */
  async function g_cargarLista(){
    hide($('#gl-err'));
    const qs=new URLSearchParams({action:'groups_list', q:G.q, estado:G.estado, page:G.page, per_page:G.per_page});
    try{
      const r=await j(`${apiUrl}?${qs.toString()}`);
      G.rows=r.data||[]; if (G.page>Math.ceil((r.total||1)/G.per_page)) G.page=1;
      g_pintarTabla(G.rows); g_pintarPager(r.total||0);
    }catch(err){ show($('#gl-err'), err.message||'Error al listar grupos'); }
  }
  function g_pintarTabla(rows){
    const tb=$('#g-tbody');
    tb.innerHTML = (rows||[]).map(r=>`
      <tr class="g-row" data-id="${r.id}">
        <td>${r.id}</td>
        <td class="name-cell">
          <span class="name-text">${esc(r.nombre)} · slots:${r.layout_slots}</span>
          <span class="status-dot ${r.activo?'status-on':'status-off'}"></span>
        </td>
        <td class="text-nowrap">
          <div class="actions-inline">
            <button class="btn btn-sm btn-primary g-edit" data-id="${r.id}">Editar</button>
            <button class="btn btn-sm btn-secondary g-sel" data-id="${r.id}" data-nombre="${esc(r.nombre)}" data-slots="${r.layout_slots}" data-activo="${r.activo?1:0}">Seleccionar</button>
            <button class="btn btn-sm ${r.activo?'btn-warning':'btn-success'} g-toggle" data-id="${r.id}">
              ${r.activo?'Desactivar':'Activar'}
            </button>
            <button class="btn btn-sm btn-danger g-del" data-id="${r.id}">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');
  }
  function g_pintarPager(total){
    const ul=$('#g-pager'); const pages=Math.max(1,Math.ceil(total/G.per_page));
    G.page=Math.min(G.page,pages); const cur=G.page; const items=[];
    const add=(p,lab,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${lab}</a></li>`);
    add(cur-1,'«',cur<=1?'disabled':''); let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':''); add(cur+1,'»',cur>=pages?'disabled':''); ul.innerHTML=items.join('');
  }
  // filtros/pager grupos
  slot.addEventListener('input', debounce(e=>{
    if (e.target?.id==='g-q'){ G.q=e.target.value; G.page=1; g_cargarLista(); }
  },300));
  slot.addEventListener('change', e=>{
    if (e.target?.id==='g-estado'){ G.estado=e.target.value; G.page=1; g_cargarLista(); }
  });
  slot.addEventListener('click', e=>{
    const a=e.target?.closest('#g-pager a[data-page]'); if(a){ e.preventDefault(); const li=a.parentElement;
      if(!li.classList.contains('disabled')&&!li.classList.contains('active')){ const p=parseInt(a.dataset.page,10); if(p>0){ G.page=p; g_cargarLista(); } }
    }
  });

  // crear/actualizar grupo
  slot.addEventListener('click', async (e)=>{
    const btn=e.target?.closest('#g-guardar'); if(!btn) return;
    hide($('#g-err')); hide($('#g-ok'));
    const nombre = ($('#g-nombre')?.value||'').trim();
    const slots  = parseInt($('#g-slots')?.value||'1',10)||1;
    const activo = parseInt($('#g-activo')?.value||'1',10)||1;
    if (!nombre){ show($('#g-err'),'Nombre requerido'); return; }

    const fd=new FormData(); if (G.editId>0){
      fd.append('action','group_update'); fd.append('id',String(G.editId));
      fd.append('nombre',nombre); fd.append('layout_slots',String(slots));
    }else{
      fd.append('action','group_create'); fd.append('nombre',nombre); fd.append('layout_slots',String(slots)); fd.append('activo',String(activo));
    }

    btn.disabled=true; const old=btn.textContent; btn.textContent = G.editId>0 ? 'Guardando…' : 'Creando…';
    try{
      const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
      if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`);
      show($('#g-ok'), G.editId>0?'Grupo actualizado':'Grupo creado');
      // reset form si fue create
      if (!G.editId){ $('#g-form')?.reset(); $('#g-activo').value='1'; }
      G.editId=0; $('#g-guardar').textContent='Crear grupo';
      await g_cargarLista();
    }catch(err){ show($('#g-err'), err.message||'Error al guardar grupo'); }
    finally{ btn.disabled=false; btn.textContent=old; }
  });

  // acciones fila grupos
  slot.addEventListener('click', async (e)=>{
    // editar
    const ge=e.target?.closest('.g-edit'); if(ge){
      const id=parseInt(ge.dataset.id,10); const row=(G.rows||[]).find(r=>r.id===id); if(!row) return;
      G.editId=id; $('#g-nombre').value=row.nombre||''; $('#g-slots').value=String(row.layout_slots||1); $('#g-activo').value=String(row.activo?1:0);
      $('#g-guardar').textContent='Guardar cambios'; $('#g-nombre')?.focus(); return;
    }
    // seleccionar (abrir panel de items y audiencias)
    const gs=e.target?.closest('.g-sel'); if(gs){
      G.selId=parseInt(gs.dataset.id,10)||0; G.selNombre=gs.dataset.nombre||''; G.selSlots=parseInt(gs.dataset.slots||'1',10)||1; G.selActivo=parseInt(gs.dataset.activo||'1',10)||1;
      await gi_openPanel(); return;
    }
  });
  slot.addEventListener('click', async (e)=>{
    // toggle activo
    const gt=e.target?.closest('.g-toggle'); if(gt){
      const id=parseInt(gt.dataset.id,10)||0; if(!id) return;
      const row=(G.rows||[]).find(r=>r.id===id); if(!row) return;
      const nuevo=row.activo?0:1; if(!confirm(`${nuevo?'Activar':'Desactivar'} grupo?`)) return;
      gt.disabled=true; try{
        const fd=new FormData(); fd.append('action','group_set_activo'); fd.append('id',String(id)); fd.append('activo',String(nuevo));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await g_cargarLista();
      }catch(err){ show($('#gl-err'), err.message||'No se pudo actualizar estado'); } finally{ gt.disabled=false; }
      return;
    }
    // eliminar grupo
    const gd=e.target?.closest('.g-del'); if(gd){
      const id=parseInt(gd.dataset.id,10)||0; if(!id) return;
      if(!confirm('¿Eliminar grupo permanentemente?')) return;
      gd.disabled=true; try{
        const fd=new FormData(); fd.append('action','group_delete'); fd.append('id',String(id));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`);
        // si eliminaste el grupo que estaba abierto, cierra panel
        if (G.selId===id){ gi_closePanel(); }
        await g_cargarLista();
      }catch(err){ show($('#gl-err'), err.message||'No se pudo eliminar'); } finally{ gd.disabled=false; }
    }
  });

  /* ----------------- Panel Items del Grupo ----------------- */
  function gi_setHeader(){
    const hint=$('#gi-hint'); const panel=$('#gi-panel');
    if (!G.selId){ panel?.classList.add('d-none'); hint?.classList.remove('d-none'); return; }
    hint?.classList.add('d-none'); panel?.classList.remove('d-none');
  }
  async function gi_openPanel(){
    gi_setHeader();
    await gi_listItems();
    await cargarEmpresas(['#ga-empresa']); await cargarRoles(['#ga-rol']);
    await ga_listTargets(); await ga_preview();
  }
  function gi_closePanel(){
    G.selId=0; G.selNombre=''; G.selSlots=1; G.selActivo=1;
    gi_setHeader();
    $('#gi-tbody')?.replaceChildren(); $('#gi-suggest') && ($('#gi-suggest').innerHTML='');
    $('#ga-tbody')?.replaceChildren(); $('#ga-prev') && ($('#ga-prev').textContent='—');
  }

  async function gi_listItems(){
    if(!G.selId) return;
    const tb=$('#gi-tbody'); tb.innerHTML='<tr><td colspan="4">Cargando…</td></tr>';
    try{
      const r=await j(`${apiUrl}?action=group_items_list&grupo_id=${G.selId}`);
      const rows=r.data||[];
      tb.innerHTML = rows.map((x,i)=>`
        <tr>
          <td>${i+1}</td>
          <td>${esc(x.titulo)} ${x.activo?'<span class="badge-soft ms-1">activo</span>':''}</td>
          <td>
            <div class="btn-group btn-group-sm" role="group">
              <button class="btn btn-outline-secondary gi-move" data-id="${x.id}" data-dir="up">↑</button>
              <button class="btn btn-outline-secondary gi-move" data-id="${x.id}" data-dir="down">↓</button>
            </div>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-danger gi-del" data-id="${x.id}">Quitar</button>
          </td>
        </tr>
      `).join('') || '<tr><td colspan="4" class="text-muted">Sin items</td></tr>';
    }catch(err){ $('#gi-tbody').innerHTML=`<tr><td colspan="4" class="text-danger">${esc(err.message||'Error')}</td></tr>`; }
  }

  // buscar publicidades activas para añadir
  slot.addEventListener('click', async (e)=>{
    const btn=e.target?.closest('#gi-buscar'); if(!btn) return;
    const q = ($('#gi-q')?.value||'').trim(); const box=$('#gi-suggest'); box.innerHTML='Buscando…';
    try{
      const r=await j(`${apiUrl}?action=ads_search&q=${encodeURIComponent(q)}&grupo_id=${G.selId||0}&limit=20`);
      box.innerHTML = (r.data||[]).map(a=>`<button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2 gi-add" data-id="${a.id}">${esc(a.titulo)}</button>`).join('') || '<div class="text-muted">Sin resultados</div>';
    }catch(err){ box.innerHTML = `<div class="text-danger">${esc(err.message||'Error')}</div>`; }
  });

  // añadir item / mover / quitar
  slot.addEventListener('click', async (e)=>{
    const add=e.target?.closest('.gi-add'); if(add){
      if(!G.selId) return;
      const pid=parseInt(add.dataset.id,10)||0; if(!pid) return;
      add.disabled=true; try{
        const fd=new FormData(); fd.append('action','group_item_add'); fd.append('grupo_id',String(G.selId)); fd.append('publicidad_id',String(pid));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await gi_listItems();
      }catch(err){ alert(err.message||'No se pudo añadir'); } finally{ add.disabled=false; }
      return;
    }
    const mv=e.target?.closest('.gi-move'); if(mv){
      const id=parseInt(mv.dataset.id,10)||0; const dir=mv.dataset.dir||'';
      if(!id||!dir) return;
      mv.disabled=true; try{
        const fd=new FormData(); fd.append('action','group_item_move'); fd.append('id',String(id)); fd.append('dir',dir);
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); if(jx.moved) await gi_listItems();
      }catch(err){ alert(err.message||'No se pudo mover'); } finally{ mv.disabled=false; }
      return;
    }
    const del=e.target?.closest('.gi-del'); if(del){
      const id=parseInt(del.dataset.id,10)||0; if(!id) return;
      if(!confirm('Quitar publicidad del grupo?')) return;
      del.disabled=true; try{
        const fd=new FormData(); fd.append('action','group_item_del'); fd.append('id',String(id));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await gi_listItems();
      }catch(err){ alert(err.message||'No se pudo quitar'); } finally{ del.disabled=false; }
    }
  });

  /* ----------------- Audiencias del Grupo ----------------- */
  function ga_toggleInputs(){
    const t = $('#ga-tipo')?.value || 'TODOS';
    // Empresa/Rol (ya están juntos en el layout)
    const needEmp = (t==='EMPRESA' || t==='EMPRESA_ROL');
    const needRol = (t==='ROL' || t==='EMPRESA_ROL');
    $('#ga-empresa')?.closest('.d-flex')?.classList.toggle('d-none', !(needEmp||needRol));
    // Usuario search
    const userWrap = $('#ga-ures')?.closest('.col-12');
    if (userWrap) userWrap.classList.toggle('d-none', t!=='USUARIO');
  }
  slot.addEventListener('change', e=>{ if(e.target?.id==='ga-tipo') ga_toggleInputs(); });

  // Buscar usuario para audiencia de grupo
  slot.addEventListener('click', async (e)=>{
    const btn=e.target?.closest('#ga-buscar'); if(!btn) return;
    const q=($('#ga-uq')?.value||'').trim(); const res=$('#ga-ures'); res.innerHTML='Buscando…';
    try{
      const r = await j(`${apiUrl}?action=users_search&q=${encodeURIComponent(q)}&limit=20`);
      res.innerHTML = (r.data||[]).map(u=>`
        <label class="form-check d-block"><input type="radio" name="sel_user_grp" value="${u.id}">
          ${esc(u.nombres)} ${esc(u.apellidos)} — ${esc(u.usuario)} (${esc(u.empresa)})</label>
      `).join('') || '<div class="text-muted">Sin resultados</div>';
    }catch(err){ res.innerHTML=`<div class="text-danger">${esc(err.message||'Error')}</div>`; }
  });

  async function ga_listTargets(){
    const tb=$('#ga-tbody'); if(tb) tb.innerHTML='<tr><td colspan="4">Cargando…</td></tr>';
    if(!G.selId) return;
    try{
      const r=await j(`${apiUrl}?action=group_targets_list&grupo_id=${G.selId}`);
      const rows=r.data||[];
      tb.innerHTML = rows.map(x=>`
        <tr>
          <td>${x.id}</td>
          <td>${esc(x.tipo)}</td>
          <td>${
            x.tipo==='USUARIO'      ? `${esc(x.u_usuario)} — ${esc(x.u_nombre||'')}` :
            x.tipo==='ROL'          ? `${esc(x.rol_nombre||'')}` :
            x.tipo==='EMPRESA'      ? `${esc(x.emp_nombre||'')}` :
            x.tipo==='EMPRESA_ROL'  ? `${esc(x.emp_nombre||'')} / ${esc(x.rol_nombre||'')}` :
            '—'
          }</td>
          <td class="text-end"><button class="btn btn-sm btn-danger ga-del" data-id="${x.id}">Quitar</button></td>
        </tr>
      `).join('') || '<tr><td colspan="4" class="text-muted">Sin reglas</td></tr>';
    }catch(err){ $('#ga-tbody').innerHTML=`<tr><td colspan="4" class="text-danger">${esc(err.message||'Error')}</td></tr>`; }
  }
  async function ga_preview(){
    const lbl=$('#ga-prev'); if(!G.selId){ lbl.textContent='—'; return; }
    try{ const r=await j(`${apiUrl}?action=group_preview&grupo_id=${G.selId}`); lbl.textContent=`Destinatarios estimados: ${r.total||0}`; }
    catch{ lbl.textContent='—'; }
  }
  // añadir/quitar reglas de grupo
  slot.addEventListener('click', async (e)=>{
    const add=e.target?.closest('#ga-add'); if(add){
      if(!G.selId) return;
      const tipo = $('#ga-tipo')?.value || 'TODOS';
      const emp  = parseInt($('#ga-empresa')?.value||'0',10);
      const rol  = parseInt($('#ga-rol')?.value||'0',10);
      let uid=0; const pick=slot.querySelector('input[name="sel_user_grp"]:checked'); if(pick) uid=parseInt(pick.value,10)||0;

      const fd=new FormData(); fd.append('action','group_target_add'); fd.append('grupo_id',String(G.selId)); fd.append('tipo',tipo);
      if (tipo==='USUARIO') fd.append('usuario_id', String(uid||0));
      if (tipo==='ROL')     fd.append('rol_id', String(rol||0));
      if (tipo==='EMPRESA') fd.append('empresa_id', String(emp||0));
      if (tipo==='EMPRESA_ROL'){ fd.append('empresa_id', String(emp||0)); fd.append('rol_id', String(rol||0)); }

      add.disabled=true; try{
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`);
        await ga_listTargets(); await ga_preview();
      }catch(err){ alert(err.message||'No se pudo añadir'); } finally{ add.disabled=false; }
      return;
    }
    const del=e.target?.closest('.ga-del'); if(del){
      const id=parseInt(del.dataset.id,10)||0; if(!id) return;
      if(!confirm('Quitar esta regla del grupo?')) return;
      del.disabled=true; try{
        const fd=new FormData(); fd.append('action','group_target_del'); fd.append('id',String(id));
        const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
        if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`);
        await ga_listTargets(); await ga_preview();
      }catch(err){ alert(err.message||'No se pudo quitar'); } finally{ del.disabled=false; }
    }
  });

  /* ----------------- Refresh público ----------------- */
  slot.__pbRefresh = async function(){
    // Editor publicidad
    $('#p-form')?.reset(); TOP.editId=0; $('#p-guardar').textContent='Crear'; hide($('#p-err')); hide($('#p-ok'));
    writeTags([]); hideSuggest();

    // Listado publicidades (filtros + estado)
    const fq=$('#f-q'); const fe=$('#f-estado'); if(fq) fq.value=''; if(fe) fe.value='';
    L.q=''; L.estado=''; L.page=1; L.openId=0;

    // Audiencias publicidad
    $('#a-panel')?.classList.add('d-none'); $('#a-hint')?.classList.remove('d-none'); $('#a-ures') && ($('#a-ures').innerHTML=''); $('#a-prev') && ($('#a-prev').textContent='—');
    A.pid=0;

    // Grupos: filtros y selección
    $('#g-form')?.reset(); $('#g-activo').value='1'; G.editId=0; $('#g-guardar').textContent='Crear grupo';
    $('#g-q') && ($('#g-q').value=''); $('#g-estado') && ($('#g-estado').value='');
    G.q=''; G.estado=''; G.page=1;

    gi_closePanel();

    await cargarEmpresas(['#a-empresa','#ga-empresa']);
    await cargarRoles(['#a-rol','#ga-rol']);
    toggleInputsAud(); ga_toggleInputs();

    await cargarLista();
    await g_cargarLista();
  };

  // Primera carga
  slot.__pbRefresh();
}
