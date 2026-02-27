// modules/consola/usuarios/gestion.js
export function init(slot, apiUrl) {
  if (slot.__usuariosBound) {
    slot.__usuariosRefresh?.();
    return;
  }
  slot.__usuariosBound = true;

  // Utils
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el, msg='') => {
    if (!el) return;
    const span=el.querySelector?.('.msg');
    if (span) span.textContent = msg;
    else el.textContent = msg;
    el.classList.remove('d-none');
    el.classList.add('show');
  };
  const hide = el => {
    if (!el) return;
    el.classList.add('d-none');
    el.classList.remove('show');
  };
  const debounce = (fn,ms=300)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};};

  async function j(url, opts={}) {
    const r = await fetch(url, {credentials:'same-origin', ...opts});
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if(!r.ok||!d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  // === Rutas simples (sin BASE_URL ni heurísticas) ===
  const cursosApi = slot.getAttribute('data-cursos-api') || '/modules/consola/cursos/api.php';

  // Estado
  const U = { editId: 0 };
  const L = { empresa:0, q:'', rol:0, page:1, per_page:5, rows:[], total:0 };

  // --- Carga combos ---
  async function cargarEmpresas() {
    try {
      const r = await j(`${apiUrl}?action=empresas`);
      const top = $('#u-empresa');
      const f = $('#f-empresa');
      if (top) top.innerHTML = `<option value="">— Selecciona —</option>${(r.data||[]).map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('')}`;
      if (f) f.innerHTML = `<option value="0">Todas</option>${(r.data||[]).map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('')}`;
    } catch { /* noop */ }
  }

  async function cargarRoles() {
    try {
      const r = await j(`${apiUrl}?action=roles`);
      const top = $('#u-rol');
      const f = $('#f-rol');
      const opts = (r.data||[]).map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      if (top) top.innerHTML = `<option value="0">— Ninguno —</option>${opts}`;
      if (f) f.innerHTML = `<option value="0">Todos</option>${opts}`;
    } catch { /* noop */ }
  }

  // --- Listado ---
  function pintarTabla(rows) {
    const tb = $('#u-tbody'); if (!tb) return;
    tb.innerHTML = (rows||[]).map(r => `
      <tr data-id="${r.id}"
          data-usuario="${esc(r.usuario)}"
          data-nombres="${esc(r.nombres)}"
          data-apellidos="${esc(r.apellidos)}"
          data-id_empresa="${r.id_empresa}"
          data-id_rol="${r.rol_id || 0}"
          data-foto="${esc(r.foto || '')}">
        <td class="text-muted">${r.id}</td>
        <td>${esc(r.usuario)}</td>
        <td>${esc((r.nombres||'') + ' ' + (r.apellidos||''))}</td>
        <td>${esc(r.empresa||'')}</td>
        <td>${esc(r.rol||'—')}</td>
        <td>
          <div class="actions-inline">
            <button class="btn btn-sm btn-secondary u-cursos" data-id="${r.id}" data-nombre="${esc((r.nombres||'') + ' ' + (r.apellidos||''))}">Cursos</button>
            <button class="btn btn-sm btn-primary u-edit" data-id="${r.id}">Editar</button>
            <button class="btn btn-sm btn-danger u-del" data-id="${r.id}">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="6" class="text-muted">Sin resultados</td></tr>`;
  }

  function pintarPager(total) {
    const ul = $('#u-pager');
    if (!ul) return;
    const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages);
    const cur = L.page;
    const items = [];
    const add = (p, label, cls='') => items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s = Math.max(1, cur-2), e = Math.min(pages, s+4);
    s = Math.max(1, e-4);
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
      L.rows = r.data || [];
      L.total = r.total || 0;
      pintarTabla(L.rows);
      pintarPager(L.total);
    } catch (err) {
      const a=$('#l-alert');
      a.textContent = err.message || 'Error al listar';
      a.classList.remove('d-none');
    }
  }

  // --- Preview de foto (sin BASE_URL ni transformaciones) ---
  const defaultPreview = slot.getAttribute('data-default-avatar') || '/dist/img/user2-160x160.jpg';

  function setPreview(url){
    const p = $('#u-foto-prev'); if (!p) return;
    p.style.backgroundImage = `url('${url}')`;
  }
  function resetPreview(){ setPreview(defaultPreview); setCaption('Sin foto por el momento'); setSize(0); }
  function setCaption(txt){ const c = $('#u-foto-cap'); if (c) c.textContent = txt || 'Sin foto por el momento'; }
  function setSize(bytes){
    const s = $('#u-foto-size'); if (!s) return;
    if (!bytes){ s.textContent = ''; return; }
    const kb = (bytes/1024).toFixed(0);
    s.textContent = `Peso de archivo cargado: ${kb} KB`;
  }
  function fileNameFromPath(p){ try{ return (p||'').split('/').pop().split('\\').pop(); }catch{ return ''; } }

  slot.addEventListener('change', (e)=>{
    if (e.target?.id !== 'u-foto') return;
    const f = e.target.files?.[0]; const errEl=$('#u-alert'); hide(errEl);
    if (!f) { resetPreview(); return; }
    const okType = ['image/jpeg','image/png','image/webp'].includes(f.type);
    if (!okType) { e.target.value=''; resetPreview(); show(errEl,'Solo JPG, PNG o WEBP.'); return; }
    if (f.size > 4*1024*1024) { e.target.value=''; resetPreview(); show(errEl,'Máximo 4MB.'); return; }
    const url = URL.createObjectURL(f);
    setPreview(url); setCaption(f.name || 'archivo'); setSize(f.size);
    setTimeout(()=>URL.revokeObjectURL(url), 5000);
  });

  // --- Toggle ojo contraseña ---
  slot.addEventListener('click', (e)=>{
    const btn = e.target?.closest('#u-clave-toggle'); if (!btn) return;
    const inp = $('#u-clave'); if (!inp) return;
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    const ic = btn.querySelector('i'); if (ic){ ic.classList.toggle('fa-eye'); ic.classList.toggle('fa-eye-slash'); }
  });

  // --- Crear / Actualizar ---
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('#u-guardar'); if (!btn) return;

    const okEl=$('#u-ok'), errEl=$('#u-alert'); hide(errEl); hide(okEl);

    const usuario = ($('#u-usuario')?.value||'').trim();
    const clave = ($('#u-clave')?.value||'').trim();
    const nombres = ($('#u-nombres')?.value||'').trim();
    const apellidos=($('#u-apellidos')?.value||'').trim();
    const id_emp = ($('#u-empresa')?.value||'').trim();
    const id_rol = ($('#u-rol')?.value||'0').trim();
    const fotoInp = $('#u-foto');

    if (!/^\d{8,11}$/.test(usuario)) { show(errEl,'Usuario debe ser DNI/CE (8–11 dígitos).'); return; }
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
      if (clave !== '') fd.append('clave', clave);
    } else {
      fd.append('action','create');
      fd.append('clave', clave);
    }

    if (fotoInp && fotoInp.files && fotoInp.files[0]) fd.append('foto', fotoInp.files[0]);

    btn.disabled = true;
    const old = btn.textContent;
    btn.textContent = U.editId>0 ? 'Guardando…' : 'Creando…';

    try {
      const r = await j(apiUrl, { method:'POST', body: fd });
      show(okEl, `Usuario ${U.editId>0 ? 'actualizado' : 'creado'} con éxito. Id: ${r.id || U.editId}.`);
      U.editId = 0;
      $('#u-form')?.reset();
      if ($('#u-rol')) $('#u-rol').value='0';
      if (fotoInp) fotoInp.value='';
      resetPreview();

      // reset explícito del tipo de input y del icono del ojo
      const pwd = $('#u-clave'); if (pwd) pwd.type = 'password';
      const eye = $('#u-clave-toggle i'); if (eye) { eye.classList.add('fa-eye'); eye.classList.remove('fa-eye-slash'); }

      const saveBtn = $('#u-guardar'); if (saveBtn) saveBtn.textContent = 'Crear';
      await cargarLista();
    } catch (err) {
      show(errEl, err.message || 'No se pudo guardar');
    } finally {
      btn.disabled=false; btn.textContent=old;
    }
  });

  // cerrar ok
  slot.addEventListener('click', (e)=>{
    const x=e.target?.closest('.u-ok-close');
    if(!x) return;
    hide($('#u-ok'));
  });

  // Editar
  slot.addEventListener('click', (e)=>{
    const btn = e.target?.closest('.u-edit'); if (!btn) return;
    const tr = btn.closest('tr[data-id]'); if (!tr) return;

    U.editId = parseInt(tr.dataset.id,10) || 0;
    $('#u-usuario').value = tr.dataset.usuario || '';
    $('#u-nombres').value = tr.dataset.nombres || '';
    $('#u-apellidos').value = tr.dataset.apellidos || '';
    $('#u-empresa').value = tr.dataset.id_empresa || '';
    $('#u-rol').value = tr.dataset.id_rol || '0';

    // limpiar y resetear password (tipo + icono)
    const pwd = $('#u-clave'); if (pwd){ pwd.value=''; pwd.type='password'; }
    const eye = $('#u-clave-toggle i'); if (eye){ eye.classList.add('fa-eye'); eye.classList.remove('fa-eye-slash'); }

    // limpiar input file
    const f = $('#u-foto'); if (f) f.value = '';

    // foto actual (si la hay) proveniente de data-foto
    const ruta = tr.dataset.foto || '';
    if (ruta) {
      setPreview(ruta);
      setCaption(fileNameFromPath(ruta));
      setSize(0);
    } else {
      resetPreview();
    }

    const b = $('#u-guardar'); if (b) b.textContent = 'Guardar cambios';
    hide($('#u-ok')); hide($('#u-alert'));
    $('#u-usuario').focus();
  });

  // Eliminar
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.u-del');
    if (!btn) return;
    const id = parseInt(btn.dataset.id,10)||0;
    if (!id) return;
    if (!confirm(`¿Eliminar usuario #${id}?`)) return;

    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.append('action','delete');
      fd.append('id', String(id));
      await j(apiUrl, { method:'POST', body: fd });
      const okEl = $('#u-ok');
      show(okEl, `Usuario #${id} eliminado.`);
      await cargarLista();
    } catch (err) {
      const a=$('#l-alert');
      a.textContent = err.message || 'No se pudo eliminar';
      a.classList.remove('d-none');
    } finally {
      btn.disabled=false;
    }
  });

  // ======== Cursos (panel izquierdo) ========
  const UCSTATE = {
    // Panel de cursos para un usuario
    userId: 0, userName: '',
    disp: [], asig: [],
    pageDisp: 1, perDisp: 6, totalDisp: 0,
    // Listado "Usuarios y sus cursos"
    L: { empresa:0, curso_id:0, q:'', page:1, per_page:5, total:0, rows:[] }
  };

  // --- Render helpers (panel) ---
  function uc_setUserLabel(){
    $('#uc-user-label').textContent = UCSTATE.userId ? `· ${UCSTATE.userName}` : '';
    $('#uc-user-mini').textContent  = UCSTATE.userName || '';
  }
  function uc_renderDisponibles(){
    const box = $('#uc-disp-list');
    const empty = $('#uc-disp-empty');
    if (!box) return;
    if (!(UCSTATE.disp||[]).length){
      box.innerHTML=''; empty.classList.remove('d-none'); return;
    }
    empty.classList.add('d-none');
    box.innerHTML = UCSTATE.disp.map(c=>`
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <span class="text-truncate">${esc(c.nombre)}</span>
        <button class="btn btn-sm btn-success uc-add" data-id="${c.id}">Agregar</button>
      </div>
    `).join('');
  }
  function uc_renderAsignados(){
    const box = $('#uc-asig-list');
    const empty = $('#uc-asig-empty');
    if (!box) return;
    if (!(UCSTATE.asig||[]).length){
      box.innerHTML=''; empty.classList.remove('d-none'); return;
    }
    empty.classList.add('d-none');
    box.innerHTML = UCSTATE.asig.map(c=>`
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <span class="text-truncate">${esc(c.nombre)}</span>
        <button class="btn btn-sm btn-outline-danger uc-remove" data-id="${c.id}">Quitar</button>
      </div>
    `).join('');
  }

  // --- Cargas (panel) ---
  async function uc_cargarAsignados(){
    if (!UCSTATE.userId) { $('#uc-asig-list').innerHTML=''; $('#uc-asig-empty').classList.remove('d-none'); return; }
    const qs = new URLSearchParams({action:'usuario_cursos_list', usuario_id: UCSTATE.userId});
    const r = await j(`${cursosApi}?${qs.toString()}`);
    // Este endpoint devuelve {data:[...]} con cursos asignados
    UCSTATE.asig = r.data || [];
    uc_renderAsignados();
  }
  async function uc_cargarDisponibles(){
    // Reutilizamos action=list (estado=1) de cursos con paginación simple
    const qs = new URLSearchParams({action:'list', estado:'1', page:UCSTATE.pageDisp, per_page:UCSTATE.perDisp});
    const r = await j(`${cursosApi}?${qs.toString()}`);
    UCSTATE.disp = r.data || []; UCSTATE.totalDisp = r.total || 0;
    uc_renderDisponibles();
    // pager
    const pages = Math.max(1, Math.ceil((UCSTATE.totalDisp||0)/UCSTATE.perDisp));
    const cur = Math.min(UCSTATE.pageDisp, pages);
    const ul = $('#uc-disp-pager'); if (!ul) return;
    const items=[];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«',cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»',cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }

  // Click en “Cursos” (tabla derecha)
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.u-cursos'); if (!btn) return;
    const tr = btn.closest('tr[data-id]'); if(!tr) return;
    UCSTATE.userId = parseInt(tr.dataset.id,10)||0;
    UCSTATE.userName = `${tr.dataset.nombres||''} ${tr.dataset.apellidos||''}`.trim();
    uc_setUserLabel();
    try{
      await uc_cargarAsignados();
      await uc_cargarDisponibles();
    }catch(err){
      const a=$('#uc-alert'); a.textContent = err.message || 'Error al cargar cursos del usuario'; a.classList.remove('d-none');
    }
  });

  // Paginación de disponibles
  slot.addEventListener('click', (e)=>{
    const a=e.target?.closest('#uc-disp-pager a[data-page]'); if(!a) return; e.preventDefault();
    const li=a.parentElement; if(li.classList.contains('disabled')||li.classList.contains('active')) return;
    const p=parseInt(a.dataset.page,10)||1; UCSTATE.pageDisp=p; uc_cargarDisponibles();
  });

  // Agregar/Quitar curso
  slot.addEventListener('click', async (e)=>{
    const addBtn = e.target?.closest('.uc-add');
    const rmBtn  = e.target?.closest('.uc-remove');
    if (!addBtn && !rmBtn) return;
    if (!UCSTATE.userId){ alert('Primero selecciona un usuario (botón “Cursos”).'); return; }
    const curso_id = parseInt((addBtn||rmBtn).dataset.id,10)||0;
    const fd = new FormData();
    fd.append('usuario_id', String(UCSTATE.userId));
    fd.append('curso_id', String(curso_id));
    try{
      if (addBtn){ fd.append('action','usuario_curso_add'); await j(cursosApi,{method:'POST',body:fd}); }
      else       { fd.append('action','usuario_curso_remove'); await j(cursosApi,{method:'POST',body:fd}); }
      await uc_cargarAsignados();
      await uc_cargarDisponibles();
    }catch(err){ const a=$('#uc-alert'); a.textContent=err.message||'No se pudo actualizar'; a.classList.remove('d-none'); }
  });

  // ======== Listado: Usuarios y sus cursos ========
  async function uc_cargarCursosCombo(){
    try{
      const r = await j(`${cursosApi}?action=list&estado=1&page=1&per_page=200`);
      const sel = $('#uc-f-curso'); if(!sel) return;
      sel.innerHTML = `<option value="0">Todos</option>` + (r.data||[]).map(c=>`<option value="${c.id}">${esc(c.nombre)}</option>`).join('');
    }catch{/* noop */}
  }
  async function uc_cargarEmpresasCombo(){
    try{
      const r = await j(`${apiUrl}?action=empresas`);
      const sel = $('#uc-f-empresa'); if(!sel) return;
      sel.innerHTML = `<option value="0">Todas</option>` + (r.data||[]).map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('');
    }catch{/* noop */}
  }
  function uc_pintarTabla(rows){
    const tb = $('#uc-tbody'); const empty=$('#uc-empty');
    if (!tb) return;
    if (!(rows||[]).length){ tb.innerHTML=''; empty.classList.remove('d-none'); return; }
    empty.classList.add('d-none');
    tb.innerHTML = rows.map(r=>`
      <tr>
        <td class="text-muted">${r.id}</td>
        <td>${esc((r.nombres||'') + ' ' + (r.apellidos||''))}</td>
        <td>${esc(r.empresa||'')}</td>
        <td class="text-center">${r.cursos_count||0}</td>
      </tr>
    `).join('');
  }
  function uc_pintarPager(total){
    const ul = $('#uc-pager'); if(!ul) return;
    const pages = Math.max(1, Math.ceil(total / UCSTATE.L.per_page));
    UCSTATE.L.page = Math.min(UCSTATE.L.page, pages);
    const cur = UCSTATE.L.page; const items=[];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«',cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»',cur>=pages?'disabled':'');
    ul.innerHTML=items.join('');
  }
  async function uc_cargarLista(){
    hide($('#uc-alert'));
    const qs = new URLSearchParams({
      action:'usuarios_con_cursos_list',
      empresa: UCSTATE.L.empresa || 0,
      curso_id: UCSTATE.L.curso_id || 0,
      q: UCSTATE.L.q || '',
      page: UCSTATE.L.page,
      per_page: UCSTATE.L.per_page
    });
    try{
      const r = await j(`${cursosApi}?${qs.toString()}`);
      UCSTATE.L.rows=r.data||[]; UCSTATE.L.total=r.total||0;
      uc_pintarTabla(UCSTATE.L.rows); uc_pintarPager(UCSTATE.L.total);
    }catch(err){ const a=$('#uc-alert'); a.textContent=err.message||'Error al listar'; a.classList.remove('d-none'); }
  }

  // Filtros listado usuarios-cursos
  slot.addEventListener('change', (e)=>{
    if (e.target?.id==='uc-f-empresa'){ UCSTATE.L.empresa=parseInt(e.target.value,10)||0; UCSTATE.L.page=1; uc_cargarLista(); }
    if (e.target?.id==='uc-f-curso'){ UCSTATE.L.curso_id=parseInt(e.target.value,10)||0; UCSTATE.L.page=1; uc_cargarLista(); }
  });
  slot.addEventListener('input', debounce((e)=>{
    if (e.target?.id==='uc-f-q'){ UCSTATE.L.q=e.target.value||''; UCSTATE.L.page=1; uc_cargarLista(); }
  },300));
  // Pager
  slot.addEventListener('click', (e)=>{
    const a = e.target?.closest('#uc-pager a[data-page]'); if(!a) return; e.preventDefault();
    const li=a.parentElement; if(li.classList.contains('disabled')||li.classList.contains('active')) return;
    const p=parseInt(a.dataset.page,10); if(p>0){ UCSTATE.L.page=p; uc_cargarLista(); }
  });

  // Filtros (listado derecha)
  slot.addEventListener('change', (e)=>{
    if (e.target?.id === 'f-empresa') {
      L.empresa = parseInt(e.target.value,10)||0;
      L.page=1;
      cargarLista();
    }
    if (e.target?.id === 'f-rol') {
      L.rol = parseInt(e.target.value,10)||0;
      L.page=1;
      cargarLista();
    }
  });

  slot.addEventListener('input', debounce((e)=>{
    if (e.target?.id === 'f-q') {
      L.q = e.target.value || '';
      L.page=1;
      cargarLista();
    }
  },300));

  // Paginación (listado derecha)
  slot.addEventListener('click', (e)=>{
    const a = e.target?.closest('#u-pager a[data-page]');
    if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10);
    if (p>0) {
      L.page=p;
      cargarLista();
    }
  });

  // Primera carga / Refresh
  slot.__usuariosRefresh = async function(){
    hide($('#u-alert')); hide($('#u-ok'));
    // Form top
    $('#u-form')?.reset();
    $('#u-rol') && ($('#u-rol').value='0');
    const saveBtn = $('#u-guardar'); if (saveBtn) saveBtn.textContent='Crear';
    U.editId = 0; resetPreview();
    const pwd = $('#u-clave'); if (pwd) pwd.type='password';
    const eye = $('#u-clave-toggle i'); if (eye){ eye.classList.add('fa-eye'); eye.classList.remove('fa-eye-slash'); }

    // Listado derecha
    const fe=$('#f-empresa'), fq=$('#f-q'), fr=$('#f-rol');
    if (fe) fe.value='0'; if (fq) fq.value=''; if (fr) fr.value='0';
    L.empresa=0; L.q=''; L.rol=0; L.page=1;

    // Panel cursos (estado inicial)
    const ucList = $('#uc-asig-list'); if (ucList) ucList.innerHTML='';
    const ucEmpty= $('#uc-asig-empty'); if (ucEmpty) ucEmpty.classList.remove('d-none');
    UCSTATE.userId=0; UCSTATE.userName=''; uc_setUserLabel();
    UCSTATE.pageDisp=1; UCSTATE.perDisp=6;

    await cargarEmpresas();
    await cargarRoles();
    await cargarLista();

    // Combos del panel izquierdo
    await uc_cargarEmpresasCombo();
    await uc_cargarCursosCombo();
    // Listado “Usuarios y sus cursos”
    UCSTATE.L.empresa=0; UCSTATE.L.curso_id=0; UCSTATE.L.q=''; UCSTATE.L.page=1;
    $('#uc-f-empresa') && ($('#uc-f-empresa').value='0');
    $('#uc-f-curso') && ($('#uc-f-curso').value='0');
    $('#uc-f-q') && ($('#uc-f-q').value='');
    await uc_cargarLista();
    await uc_cargarDisponibles();
  };

  slot.__usuariosRefresh();
}
