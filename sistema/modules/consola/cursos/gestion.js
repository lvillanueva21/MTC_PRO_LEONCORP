// modules/consola/cursos/gestion.js
export function init(slot, apiUrl) {
  if (slot.__modCursosBound) { slot.__modCursosRefresh?.(); return; }
  slot.__modCursosBound = true;

  // ---------- Helpers ----------
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el,msg='')=>{ if(!el) return; const sp=el.querySelector?.('.msg'); if(sp) sp.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); el.classList.add('show'); };
  const hide = el => { if(!el) return; el.classList.add('d-none'); el.classList.remove('show'); };
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url,opts={}){ const r=await fetch(url,{credentials:'same-origin',...opts}); const d=await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'})); if(!r.ok||!d.ok) throw new Error(d.msg||`HTTP ${r.status}`); return d; }

  // ---------- Chips ----------
  const boxEl    = () => $('#c-tags');
  const inputEl  = () => $('#c-tags-input');
  const hiddenEl = () => $('#c-etiquetas');
  const formEl   = () => $('#cur-create');

  const readTags = () => {
    const box = boxEl();
    if (box?.dataset.tags) { try { const a=JSON.parse(box.dataset.tags); return Array.isArray(a)?a:[]; } catch { return []; } }
    const arr=[]; box?.querySelectorAll('.chip .txt')?.forEach(n=>arr.push(n.textContent||'')); return arr;
  };
  const renderChips = arr => {
    const box = boxEl(); const input=inputEl(); if(!box||!input) return;
    box.querySelectorAll('.chip').forEach(c=>c.remove());
    arr.forEach((t,i)=>{
      const chip=document.createElement('span');
      chip.className='chip';
      chip.innerHTML=`<span class="txt">${esc(t)}</span><button type="button" class="x" data-i="${i}" aria-label="Quitar">×</button>`;
      box.insertBefore(chip,input);
    });
  };
  const writeTags = arr => { const box=boxEl(); if(box) box.dataset.tags=JSON.stringify(arr); renderChips(arr); const hidden=hiddenEl(); if(hidden) hidden.value=arr.join(','); };
  const norm = t => (t||'').replace(/\s+/g,' ').trim().slice(0,50);
  const addTag = txt => { const t=norm(txt); if(!t) return; const tags=readTags(); if(!tags.includes(t)){ tags.push(t); writeTags(tags); } };
  const removeTag = i => { const tags=readTags(); if(i>=0&&i<tags.length){ tags.splice(i,1); writeTags(tags); } };

  // Focus chips
  slot.addEventListener('mousedown', e => { const area=e.target?.closest('#c-tags'); if(!area) return; if(!e.target.closest('#c-tags-input')){ e.preventDefault(); inputEl()?.focus(); } });
  slot.addEventListener('keydown', e => { if(e.target&&e.target.closest('#cur-create')&&e.key==='Enter'){ if(!e.target.closest('#c-tags-input')) e.preventDefault(); } });
  slot.addEventListener('keydown', e => {
    if(!e.target?.closest || !e.target.closest('#c-tags-input')) return;
    const input=inputEl(); if(!input) return;
    if(e.key===','||e.key==='Enter'){ e.preventDefault(); addTag(input.value); input.value=''; }
    else if(e.key==='Backspace' && input.value===''){ const tags=readTags(); if(tags.length){ tags.pop(); writeTags(tags); } }
  });
  slot.addEventListener('input', e => {
    if(!e.target?.closest || !e.target.closest('#c-tags-input')) return;
    const input=inputEl(); if(!input) return;
    const v=input.value||'';
    if(v.includes(',')){ const parts=v.split(/[,，、،]+/); for(let i=0;i<parts.length-1;i++) addTag(parts[i]); input.value=norm(parts.at(-1)); }
    writeTags(readTags());
  });
  slot.addEventListener('click', e => { const btn=e.target?.closest('.chip .x'); if(!btn) return; const i=parseInt(btn.dataset.i,10); if(Number.isNaN(i)) return; removeTag(i); inputEl()?.focus(); });

  // ---------- Listado ----------
  const L = { q:'', estado:'', page:1, per_page:5, openId:0, rows:[], editId:0 };

  function pintarTabla(rows){
    const tb = $('#l-tbody');
    const isOpen = id => L.openId === id;

    const rowMain = r => `
      <tr class="cur-row" data-id="${r.id}">
        <td>${r.id}</td>
        <td class="name-cell">
          <span class="name-text">${esc(r.nombre)}</span>
          <span class="status-dot ${r.activo?'status-on':'status-off'}"></span>
        </td>
        <td class="actions-cell">
          <div class="actions-inline">
            <button class="btn btn-sm btn-primary l-edit" data-id="${r.id}">Editar</button>
            <button class="btn btn-sm btn-warning l-desact" data-id="${r.id}">${r.activo?'Desactivar':'Activar'}</button>
            <button class="btn btn-sm btn-success l-temas" data-id="${r.id}" data-nombre="${esc(r.nombre)}">Contenido</button>
          </div>
        </td>
      </tr>`;

    const rowDetail = r => `
      <tr class="cur-detail ${isOpen(r.id)?'':'d-none'}" data-for="${r.id}">
        <td></td>
        <td colspan="2">
          <div><strong>Descripción:</strong> ${esc(r.descripcion || '—')}</div>
          <div class="mt-2">${(r.tags||[]).map(t=>`<span class="chip soft">${esc(t)}</span>`).join('') || '<span class="text-muted">Sin etiquetas</span>'}</div>
        </td>
      </tr>`;

    tb.innerHTML = rows.map(r => rowMain(r) + rowDetail(r)).join('');
  }

  function pintarPager(total){
    const ul = $('#l-pager');
    const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages);
    const cur = L.page, items=[];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«',cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»',cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }

  async function cargarLista(){
    hide($('#l-alert'));
    const qs = new URLSearchParams({action:'list', q:L.q, estado:L.estado, page:L.page, per_page:L.per_page});
    try{
      const res = await j(`${apiUrl}?${qs.toString()}`);
      L.rows = res.data || [];
      if (!L.rows.some(r=>r.id===L.openId)) L.openId=0;
      pintarTabla(L.rows); pintarPager(res.total);
    }catch(err){
      const a=$('#l-alert'); a.textContent = err.message || 'Error al listar'; a.classList.remove('d-none');
    }
  }

  // Filtros
  slot.addEventListener('change', e => {
    if (e.target?.id==='f-estado'){ L.estado=e.target.value; L.page=1; cargarLista(); }
  });
  slot.addEventListener('input', debounce(e => {
    if (e.target?.id==='f-q'){ L.q=e.target.value; L.page=1; cargarLista(); }
  },300));

  // Paginación
  slot.addEventListener('click', e => {
    const a = e.target?.closest('#l-pager a[data-page]'); if(!a) return; e.preventDefault();
    const li = a.parentElement; if(li.classList.contains('disabled')||li.classList.contains('active')) return;
    const p=parseInt(a.dataset.page,10); if(p>0){ L.page=p; cargarLista(); }
  });

  // Toggle detalle (click fila)
  slot.addEventListener('click', e => {
    const tr = e.target?.closest('tr.cur-row'); if(!tr || !tr.closest('#cur-lista')) return;
    if (e.target.closest('.actions-inline') || e.target.closest('button')) return;
    const id = parseInt(tr.dataset.id,10); L.openId = (L.openId===id)?0:id; if(Array.isArray(L.rows)) pintarTabla(L.rows);
  });

  // Editar curso -> pasa datos arriba
  slot.addEventListener('click', e => {
    const btn = e.target?.closest('.l-edit'); if(!btn) return;
    const id = parseInt(btn.dataset.id,10); if(!id) return;
    const row=(L.rows||[]).find(r=>r.id===id); if(!row) return;

    $('#c-nombre').value = row.nombre || '';
    $('#c-desc').value   = row.descripcion || '';
    const file = $('#c-imagen'); if(file) file.value='';
    writeTags(Array.isArray(row.tags)?row.tags:[]);
    L.editId = id;
    const b = $('#c-crear'); if (b) b.textContent='Guardar cambios';
    $('#c-nombre')?.focus();
    L.openId = id; if(Array.isArray(L.rows)) pintarTabla(L.rows);
  });

  // Activar/Desactivar
  slot.addEventListener('click', async e => {
    const btn = e.target?.closest('.l-desact'); if(!btn) return;
    const id = parseInt(btn.dataset.id,10); if(!id) return;
    const row=(L.rows||[]).find(r=>r.id===id); if(!row) return;
    const nuevo = row.activo ? 0 : 1;
    if(!confirm(`${nuevo?'Activar':'Desactivar'} el curso "${row.nombre}"?`)) return;
    btn.disabled=true;
    try{
      const fd=new FormData(); fd.append('action','set_activo'); fd.append('id',String(id)); fd.append('activo',String(nuevo));
      const r=await j(apiUrl,{method:'POST',body:fd});
      const okEl=$('#c-ok'); show(okEl, `Curso "${row.nombre}" ${nuevo?'activado':'desactivado'} con éxito.`);
      await cargarLista();
    }catch(err){
      const a=$('#l-alert'); a.textContent=err.message||'Error al actualizar estado'; a.classList.remove('d-none');
    }finally{ btn.disabled=false; }
  });

  // Botón Contenido -> cargar panel izquierdo
  const T = { cursoId:0, cursoNombre:'', temas:[], temaId:0 };
  slot.addEventListener('click', async e => {
    const btn = e.target?.closest('.l-temas'); if(!btn) return;
    T.cursoId = parseInt(btn.dataset.id,10)||0;
    T.cursoNombre = btn.dataset.nombre || '';
    await t_cargarLista();
  });

  // Crear/Actualizar curso
  slot.addEventListener('click', async e => {
    const btn = e.target?.closest('#c-crear'); if(!btn) return;
    const okEl=$('#c-ok'), errEl=$('#c-alert'); hide(okEl); hide(errEl);
    const nombre=($('#c-nombre')?.value||'').trim(); if(!nombre){ show(errEl,'El nombre es obligatorio'); return; }
    writeTags(readTags());
    const fd=new FormData(formEl());
    if (L.editId>0){ fd.append('action','update'); fd.append('id',String(L.editId)); }
    else { fd.append('action','create'); }
    btn.disabled=true; const old=btn.textContent; btn.textContent=(L.editId>0?'Guardando…':'Creando…');
    try{
      const r=await j(apiUrl,{method:'POST',body:fd});
      const msg = L.editId>0 ? `Curso "${nombre}" actualizado con éxito. Id: ${L.editId}.` : `Curso "${nombre}" creado con éxito. Id: ${r.id}.`;
      show(okEl,msg);
      formEl().reset?.(); writeTags([]); $('#c-tags-input')&&( $('#c-tags-input').value='');
      L.page=1; L.openId=0; L.editId=0; $('#c-crear').textContent='Crear';
      await cargarLista();
    }catch(err){ show(errEl, err?.message || 'Error al guardar'); }
    finally{ btn.disabled=false; btn.textContent=old; }
  });
  slot.addEventListener('click', e=>{ const x=e.target?.closest('.c-ok-close'); if(!x) return; hide($('#c-ok')); });

  // ---------- Panel Temas ----------
  function t_setHint(){ $('#t-panel')?.classList.add('d-none'); $('#t-hint')?.classList.remove('d-none'); }
  function t_setPanel(){ $('#t-hint')?.classList.add('d-none'); $('#t-panel')?.classList.remove('d-none'); }
  function t_fillCursoHeader(){
    const label = $('#t-curso-actual'); if (!label) return;
    label.textContent = T.cursoNombre || (`ID ${T.cursoId}`);
    const box = label.closest('.e-srv-marquee');
    if (box) requestAnimationFrame(()=>{ const need = label.scrollWidth > box.clientWidth + 2; label.classList.toggle('animate', need); });
  }
  function t_setCreate(){
    $('#t-id').value='0';
    $('#t-titulo').value=''; $('#t-clase').value=''; $('#t-video').value='';
    const f=$('#t-miniatura'); if(f) f.value='';
    $('#t-guardar').textContent='Crear tema';
    $('#t-eliminar').classList.add('d-none');
    $('#t-select').value='0';
  }
  function t_fillSelect(){
    const sel=$('#t-select'); if(!sel) return;
    if (!Array.isArray(T.temas) || T.temas.length===0){
      sel.innerHTML = `<option value="0">— Nuevo tema —</option>`;
      $('#t-empty').classList.remove('d-none');
    } else {
      $('#t-empty').classList.add('d-none');
      sel.innerHTML = `<option value="0">— Nuevo tema —</option>` + T.temas.map(x=>`<option value="${x.id}">${esc(x.titulo)}</option>`).join('');
    }
  }
  async function t_cargarLista(){
    if (!T.cursoId){ t_setHint(); return; }
    t_setPanel(); t_fillCursoHeader();
    try{
      const res = await j(`${apiUrl}?action=temas_list&curso_id=${T.cursoId}`);
      T.temas = res.data || []; T.temaId=0;
      t_fillSelect(); t_setCreate();
    }catch(err){
      const a=$('#t-alert'); a.textContent = err.message || 'Error al cargar temas'; a.classList.remove('d-none');
    }
  }

  // Cambiar selección de tema
  slot.addEventListener('change', e=>{
    if (e.target?.id !== 't-select') return;
    const id = parseInt(e.target.value,10)||0;
    if (!id){ T.temaId=0; t_setCreate(); return; }
    const row = (T.temas||[]).find(x=>x.id===id); if(!row) return;
    T.temaId = id;
    $('#t-id').value=String(id);
    $('#t-titulo').value=row.titulo||'';
    $('#t-clase').value=row.clase||'';
    $('#t-video').value=row.video_url||'';
    const f=$('#t-miniatura'); if(f) f.value='';
    $('#t-guardar').textContent='Actualizar tema';
    $('#t-eliminar').classList.remove('d-none');
  });

  // Guardar tema (create/update)
  slot.addEventListener('click', async e=>{
    const btn = e.target?.closest('#t-guardar'); if(!btn) return;
    if (!T.cursoId){ alert('Selecciona un curso desde la lista.'); return; }
    const fd = new FormData($('#t-form'));
    fd.append('curso_id', String(T.cursoId));
    fd.append('action', T.temaId>0 ? 'tema_update' : 'tema_create');
    btn.disabled=true; const old=btn.textContent; btn.textContent=T.temaId>0?'Actualizando…':'Creando…';
    try{
      await j(apiUrl,{method:'POST', body:fd});
      show($('#c-ok'), T.temaId>0?'Tema actualizado.':'Tema creado.');
      await t_cargarLista();
    }catch(err){ show($('#t-alert'), err.message || 'Error al guardar tema'); }
    finally{ btn.disabled=false; btn.textContent=old; }
  });

  // Eliminar tema
  slot.addEventListener('click', async e=>{
    const btn=e.target?.closest('#t-eliminar'); if(!btn) return;
    if (!T.temaId){ alert('Selecciona un tema.'); return; }
    if (!confirm('¿Eliminar este tema?')) return;
    btn.disabled=true;
    try{
      const fd=new FormData(); fd.append('action','tema_delete'); fd.append('id',String(T.temaId));
      await j(apiUrl,{method:'POST', body:fd});
      show($('#c-ok'),'Tema eliminado.'); await t_cargarLista();
    }catch(err){ show($('#t-alert'), err.message || 'No se pudo eliminar el tema'); }
    finally{ btn.disabled=false; }
  });

  // ---------- REFRESH ----------
  slot.__modCursosRefresh = async function(){
    hide($('#c-alert')); hide($('#c-ok')); hide($('#t-alert'));
    // reset filtros y chips
    $('#f-q') && ($('#f-q').value=''); $('#f-estado') && ($('#f-estado').value='');
    writeTags([]); $('#c-tags-input') && ($('#c-tags-input').value='');
    // reset estado
    L.q=''; L.estado=''; L.page=1; L.openId=0; L.editId=0;
    const b=$('#c-crear'); if(b) b.textContent='Crear';
    // reset temas
    T.cursoId=0; T.cursoNombre=''; T.temas=[]; T.temaId=0; t_setHint();
    await cargarLista();
  };

  // Primera carga
  slot.__modCursosRefresh();
}
