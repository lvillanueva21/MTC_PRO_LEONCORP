// modules/consola/comunicados/gestion.js
export function init(slot, apiUrl){
  if (slot.__comBound){ slot.__comRefresh?.(); return; }
  slot.__comBound = true;

  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').toString().replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el,msg='')=>{ if(!el) return; const span=el.querySelector?.('.msg'); if(span) span.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); };
  const hide = el => { if(!el) return; el.classList.add('d-none'); };
  const debounce = (fn,ms=300)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url,opts={}){ const r=await fetch(url,{credentials:'same-origin',...opts}); const d=await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'})); if(!r.ok||!d.ok) throw new Error(d.msg||`HTTP ${r.status}`); return d; }

  /* -------- Estado -------- */
  const TOP = { editId:0 };
  const L = { q:'', estado:'', vigencia:'', page:1, per_page:5, rows:[], openId:0 };
  const A = { cid:0, tipo:'TODOS', empresa:0, rol:0, usuarioId:0 };

  /* -------- Catálogos -------- */
  async function cargarEmpresas(){ try{ const r=await j(`${apiUrl}?action=empresas`); const sel=$('#a-empresa'); if(sel){ sel.innerHTML='<option value="0">Todas</option>'+ (r.data||[]).map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join(''); } }catch{} }
  async function cargarRoles(){ try{ const r=await j(`${apiUrl}?action=roles`); const sel=$('#a-rol'); if(sel){ sel.innerHTML='<option value="0">—</option>'+ (r.data||[]).map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join(''); } }catch{} }

  /* -------- Crear/Editar -------- */
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('#c-guardar'); if (!btn) return;
    hide($('#c-err')); hide($('#c-ok'));

    const fd = new FormData($('#c-form'));
    const titulo = ($('#c-titulo')?.value||'').trim();
    if (!titulo){ show($('#c-err'),'El título es obligatorio'); return; }

    if (TOP.editId>0){ fd.append('action','update'); fd.append('id', String(TOP.editId)); }
    else { fd.append('action','create'); }

    btn.disabled=true; const old=btn.textContent; btn.textContent = TOP.editId>0 ? 'Guardando…' : 'Creando…';
    try{
      const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'});
      const jx=await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
      if (!r.ok || !jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`);
      show($('#c-ok'), TOP.editId>0?'Comunicado actualizado':'Comunicado creado');
      $('#c-form')?.reset(); TOP.editId=0; $('#c-guardar').textContent='Crear';
      await cargarLista();
    }catch(err){ show($('#c-err'), err.message||'Error al guardar'); }
    finally{ btn.disabled=false; btn.textContent=old; }
  });

  // Modo edición desde lista
  function cargarEnEditor(row){
    TOP.editId = row.id;
    $('#c-titulo').value = row.titulo || '';
    $('#c-cuerpo').value = '';
    $('#c-fi').value = row.fecha_inicio ? row.fecha_inicio.replace(' ','T').slice(0,16) : '';
    $('#c-ff').value = row.fecha_fin ? row.fecha_fin.replace(' ','T').slice(0,16) : '';
    $('#c-fl').value = row.fecha_limite ? row.fecha_limite.replace(' ','T').slice(0,16) : '';
    $('#c-img').value = '';
    $('#c-guardar').textContent='Guardar cambios';
    $('#c-titulo')?.focus();
  }

  /* -------- Listado -------- */
  function pintarTabla(rows){
    const tb = $('#l-tbody');
    const isOpen = id => L.openId===id;
    const main = r => `
      <tr class="c-row" data-id="${r.id}">
        <td>${r.id}</td>
        <td class="name-cell">
          <span class="name-text">${esc(r.titulo)}</span>
          <span class="status-dot ${r.activo?'status-on':'status-off'}"></span>
        </td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-primary l-edit" data-id="${r.id}">Editar</button>
          <button class="btn btn-sm btn-secondary l-aud" data-id="${r.id}" data-titulo="${esc(r.titulo)}">Audiencias</button>
          <button class="btn btn-sm ${r.activo?'btn-warning':'btn-success'} l-toggle" data-id="${r.id}">
            ${r.activo?'Desactivar':'Activar'}
          </button>
          <button class="btn btn-sm btn-danger l-del" data-id="${r.id}">Eliminar</button>
        </td>
      </tr>`;
    const detail = r => `
      <tr class="c-detail ${isOpen(r.id)?'':'d-none'}">
        <td></td>
        <td colspan="2">
          <div><strong>Vigencia:</strong> ${esc(r.fecha_inicio||'—')} → ${esc(r.fecha_fin||'—')}</div>
          <div><strong>Límite contador:</strong> ${esc(r.fecha_limite||'—')}</div>
        </td>
      </tr>`;
    tb.innerHTML = rows.map(r=>main(r)+detail(r)).join('');
  }
  function pintarPager(total){
    const ul=$('#l-pager'); const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages); const cur=L.page; const items=[];
    const add=(p,lab,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${lab}</a></li>`);
    add(cur-1,'«',cur<=1?'disabled':''); let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':''); add(cur+1,'»',cur>=pages?'disabled':''); ul.innerHTML=items.join('');
  }
  async function cargarLista(){
    hide($('#l-err'));
    const qs = new URLSearchParams({
      action:'list', q:L.q, estado:L.estado, vigencia:L.vigencia, page:L.page, per_page:L.per_page
    });
    try{
      const r = await j(`${apiUrl}?${qs.toString()}`);
      L.rows=r.data||[]; if (!L.rows.some(x=>x.id===L.openId)) L.openId=0;
      pintarTabla(L.rows); pintarPager(r.total||0);
    }catch(err){ show($('#l-err'), err.message||'Error al listar'); }
  }
  // filtros
  slot.addEventListener('input', debounce(e=>{
    if (e.target?.id==='f-q'){ L.q=e.target.value; L.page=1; cargarLista(); }
  },300));
  slot.addEventListener('change', e=>{
    if (e.target?.id==='f-estado'){ L.estado=e.target.value; L.page=1; cargarLista(); }
    if (e.target?.id==='f-vig'){ L.vigencia=e.target.value; L.page=1; cargarLista(); }
  });
  // pager
  slot.addEventListener('click', e=>{
    const a = e.target?.closest('#l-pager a[data-page]'); if(!a) return; e.preventDefault();
    const li = a.parentElement; if (li.classList.contains('disabled')||li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10); if (p>0){ L.page=p; cargarLista(); }
  });
  // toggle detalle
  slot.addEventListener('click', e=>{
    const tr = e.target?.closest('tr.c-row'); if(!tr || e.target.closest('button')) return;
    const id = parseInt(tr.dataset.id,10); L.openId = (L.openId===id)?0:id; if (Array.isArray(L.rows)) pintarTabla(L.rows);
  });
  // editar
  slot.addEventListener('click', e=>{
    const btn=e.target?.closest('.l-edit'); if(!btn) return;
    const id=parseInt(btn.dataset.id,10); const row=L.rows.find(r=>r.id===id); if(!row) return; cargarEnEditor(row);
  });
  // activar/desactivar
  slot.addEventListener('click', async e=>{
    const btn=e.target?.closest('.l-toggle'); if(!btn) return; const id=parseInt(btn.dataset.id,10); if(!id) return;
    const row=L.rows.find(r=>r.id===id); const nuevo=row?.activo?0:1; if (!confirm(`${nuevo?'Activar':'Desactivar'} comunicado?`)) return;
    btn.disabled=true; try{
      const fd=new FormData(); fd.append('action','set_activo'); fd.append('id',String(id)); fd.append('activo',String(nuevo));
      const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
      if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await cargarLista();
    }catch(err){ show($('#l-err'), err.message||'No se pudo actualizar estado'); } finally{ btn.disabled=false; }
  });
  // eliminar físico
  slot.addEventListener('click', async e=>{
    const btn=e.target?.closest('.l-del'); if(!btn) return; const id=parseInt(btn.dataset.id,10); if(!id) return;
    if(!confirm('¿Eliminar comunicado permanentemente? Esta acción no se puede deshacer.')) return;
    btn.disabled=true; try{
      const fd=new FormData(); fd.append('action','delete'); fd.append('id',String(id));
      const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
      if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await cargarLista();
    }catch(err){ show($('#l-err'), err.message||'No se pudo eliminar'); } finally{ btn.disabled=false; }
  });

  /* -------- Audiencias (panel izq) -------- */
  function aud_setCid(id, titulo){
    A.cid = id; $('#a-hint').classList.add('d-none'); $('#a-panel').classList.remove('d-none');
    $('#a-com-actual').textContent = titulo || `ID ${id}`;
    cargarTargets(); cargarPreview();
  }
  // abrir audiencias desde lista
  slot.addEventListener('click', e=>{
    const btn=e.target?.closest('.l-aud'); if(!btn) return;
    const id=parseInt(btn.dataset.id,10); const titulo=btn.dataset.titulo||''; aud_setCid(id,titulo);
  });

  // cargar reglas
  async function cargarTargets(){
    const tb=$('#a-tbody'); if(tb) tb.innerHTML='<tr><td colspan="4">Cargando…</td></tr>';
    if (!A.cid) return;
    try{
      const r=await j(`${apiUrl}?action=targets_list&comunicado_id=${A.cid}`);
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
  // eliminar regla
  slot.addEventListener('click', async e=>{
    const btn=e.target?.closest('.a-del'); if(!btn) return; const id=parseInt(btn.dataset.id,10); if(!id) return;
    if(!confirm('Quitar esta regla?')) return;
    btn.disabled=true; try{
      const fd=new FormData(); fd.append('action','target_del'); fd.append('id',String(id));
      const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
      if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`); await cargarTargets(); await cargarPreview();
    }catch(err){ alert(err.message||'No se pudo quitar'); } finally{ btn.disabled=false; }
  });

  // cambio tipo controla visibilidad de selects
  function toggleInputs(){
    const t = $('#a-tipo')?.value || 'TODOS';
    $('#wrap-emp').classList.toggle('d-none', !(t==='EMPRESA' || t==='EMPRESA_ROL'));
    $('#wrap-rol').classList.toggle('d-none', !(t==='ROL' || t==='EMPRESA_ROL'));
    $('#wrap-user').classList.toggle('d-none', t!=='USUARIO');
  }
  slot.addEventListener('change', e=>{ if(e.target?.id==='a-tipo') toggleInputs(); });

  // buscar usuarios
  slot.addEventListener('click', async e=>{
    const btn=e.target?.closest('#a-buscar'); if(!btn) return;
    const q = ($('#a-uq')?.value||'').trim(); const res = $('#a-ures'); res.innerHTML='Buscando…';
    try{
      const r = await j(`${apiUrl}?action=users_search&q=${encodeURIComponent(q)}&limit=20`);
      res.innerHTML = (r.data||[]).map(u=>`
        <label class="form-check d-block"><input type="radio" name="sel_user" value="${u.id}">
          ${esc(u.nombres)} ${esc(u.apellidos)} — ${esc(u.usuario)} (${esc(u.empresa)})</label>
      `).join('') || '<div class="text-muted">Sin resultados</div>';
    }catch(err){ res.innerHTML = `<div class="text-danger">${esc(err.message||'Error')}</div>`; }
  });

  // añadir regla
  slot.addEventListener('click', async e=>{
    const btn=e.target?.closest('#a-add'); if(!btn) return;
    if (!A.cid) return;
    const tipo = $('#a-tipo')?.value || 'TODOS';
    const emp  = parseInt($('#a-empresa')?.value||'0',10);
    const rol  = parseInt($('#a-rol')?.value||'0',10);
    let uid = 0; const pick = slot.querySelector('input[name="sel_user"]:checked'); if (pick) uid = parseInt(pick.value,10)||0;

    const fd=new FormData(); fd.append('action','target_add'); fd.append('comunicado_id',String(A.cid)); fd.append('tipo',tipo);
    if (tipo==='USUARIO') fd.append('usuario_id', String(uid||0));
    if (tipo==='ROL') fd.append('rol_id', String(rol||0));
    if (tipo==='EMPRESA') fd.append('empresa_id', String(emp||0));
    if (tipo==='EMPRESA_ROL'){ fd.append('empresa_id', String(emp||0)); fd.append('rol_id', String(rol||0)); }

    btn.disabled=true; try{
      const r=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin'}); const jx=await r.json();
      if(!r.ok||!jx.ok) throw new Error(jx.msg||`HTTP ${r.status}`);
      await cargarTargets(); await cargarPreview();
    }catch(err){ alert(err.message||'No se pudo añadir'); } finally{ btn.disabled=false; }
  });

  // preview destinatarios
  async function cargarPreview(){
    const lbl=$('#a-prev'); if(!A.cid) { lbl.textContent='—'; return; }
    try{
      const r = await j(`${apiUrl}?action=preview&comunicado_id=${A.cid}`);
      lbl.textContent = `Destinatarios estimados: ${r.total || 0}`;
    }catch{ lbl.textContent='—'; }
  }

  /* -------- Refresh público -------- */
  slot.__comRefresh = async function(){
    // editor
    $('#c-form')?.reset(); TOP.editId=0; $('#c-guardar').textContent='Crear'; hide($('#c-err')); hide($('#c-ok'));
    // filtros
    const fq=$('#f-q'); const fe=$('#f-estado'); const fv=$('#f-vig');
    if(fq) fq.value=''; if(fe) fe.value=''; if(fv) fv.value='';
    L.q=''; L.estado=''; L.vigencia=''; L.page=1; L.openId=0;
    // audiencias
    $('#a-panel').classList.add('d-none'); $('#a-hint').classList.remove('d-none'); $('#a-ures').innerHTML=''; $('#a-prev').textContent='—';
    await cargarEmpresas(); await cargarRoles(); await cargarLista(); toggleInputs();
  };

  // Primera carga
  slot.__comRefresh();
}
