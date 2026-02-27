// modules/consola/camaras/gestion.js
// UI de gestión de DVR 1:1 por empresa + inventario de discos + historial
export function init(slot, apiUrl) {
  if (slot.__camBound) { slot.__camRefresh?.(); return; }
  slot.__camBound = true;

  // --------- Helpers ----------
  const $  = (sel) => slot.querySelector(sel);
  const esc = (s)=> (s??'').toString().replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el,msg='')=>{ if(!el) return; const sp=el.querySelector?.('.msg'); if(sp) sp.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); };
  const hide = (el)=>{ if(!el) return; el.classList.add('d-none'); };
  const debounce = (fn,ms=300)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

  async function j(url, opts={}) {
    const r = await fetch(url, {credentials:'same-origin', ...opts});
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if(!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  // --------- Estado ----------
  const TOP = { empresa_id:0, dvr_id:0 };
  const DISK = { page:1, per_page:10, total:0, rows:[] };

  // --------- Empresas (TOP) ----------
  async function cargarEmpresas(){
    const sel = $('#dv-empresa');
    if (!sel) return;
    sel.innerHTML = `<option value="0">Cargando…</option>`;
    try{
      const r = await j(`${apiUrl}?action=empresas`);
      sel.innerHTML = `<option value="0">Selecciona una empresa ...</option>` +
        (r.data||[]).map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('');
      if (TOP.empresa_id) sel.value = String(TOP.empresa_id);
    }catch(_){
      sel.innerHTML = `<option value="0">Error al cargar</option>`;
    }
  }

  // --------- DVR: get + pintar + guardar ----------
  async function dvrGet(){
    const emp = TOP.empresa_id || 0;
    // Limpia UI
    $('#dvr-id').value = '0';
    $('#dvr-u').value = ''; $('#dvr-p').value=''; $('#dvr-cams').value='0';
    $('#dvr-su').value=''; $('#dvr-sp').value=''; $('#dvr-rl').value=''; $('#dvr-ll').value='';
    hide($('#dvr-ok')); hide($('#dvr-alert'));

    if (!emp) {
      TOP.dvr_id = 0;
      // también oculta panel de historial
      $('#hist-panel')?.classList.add('d-none');
      $('#hist-hint')?.classList.remove('d-none');
      return;
    }
    try{
      const r = await j(`${apiUrl}?action=dvr_get&empresa_id=${emp}`);
      const d = r.data || null;
      if (d) {
        TOP.dvr_id = d.id;
        $('#dvr-id').value = String(d.id);
        $('#dvr-u').value  = d.principal_usuario || '';
        $('#dvr-p').value  = d.principal_clave || '';
        $('#dvr-cams').value = String(d.total_camaras ?? 0);
        $('#dvr-su').value = d.sutran_usuario || '';
        $('#dvr-sp').value = d.sutran_clave || '';
        $('#dvr-rl').value = d.link_remoto || '';
        $('#dvr-ll').value = d.link_local || '';
        // abre historial
        await histInit();
      } else {
        TOP.dvr_id = 0;
        $('#hist-panel')?.classList.add('d-none');
        $('#hist-hint')?.classList.remove('d-none');
      }
    }catch(err){
      show($('#dvr-alert'), err.message || 'No se pudo cargar DVR');
    }
  }

  async function dvrSave(){
    hide($('#dvr-alert')); hide($('#dvr-ok'));
    const emp = TOP.empresa_id || 0;
    const id  = parseInt($('#dvr-id').value||'0',10) || 0;
    const fd = new FormData();
    fd.append('action','dvr_save');
    fd.append('empresa_id', String(emp));
    fd.append('id', String(id));
    fd.append('principal_usuario', $('#dvr-u').value || '');
    fd.append('principal_clave',  $('#dvr-p').value || '');
    fd.append('sutran_usuario',   $('#dvr-su').value || '');
    fd.append('sutran_clave',     $('#dvr-sp').value || '');
    fd.append('link_remoto',      $('#dvr-rl').value || '');
    fd.append('link_local',       $('#dvr-ll').value || '');
    fd.append('total_camaras',    $('#dvr-cams').value || '0');

    try{
      const r = await j(apiUrl, {method:'POST', body:fd});
      TOP.dvr_id = r.id || 0;
      $('#dvr-id').value = String(TOP.dvr_id);
      show($('#dvr-ok'), 'DVR guardado correctamente.');
      await histInit();
    }catch(err){
      show($('#dvr-alert'), err.message || 'No se pudo guardar');
    }
  }

  // --------- Discos: listar + pager + CRUD ----------
  function diskPaint(){
    const tb = $('#disk-tbody'); const ul = $('#disk-pager');
    const rows = DISK.rows || [];
    tb.innerHTML = rows.map(r=>`
      <tr data-id="${r.id}">
        <td class="text-muted">#${r.id}</td>
        <td>${r.disco_total}</td>
        <td>${(r.disco_restante===null||r.disco_restante==='')?'—':r.disco_restante}</td>
        <td>${esc(r.ultimo_cambio || '—')}</td>
        <td>
          <div class="actions-inline">
            <button class="btn btn-sm btn-primary disk-edit">Editar</button>
            <button class="btn btn-sm btn-danger  disk-del">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="5" class="text-muted">Sin discos</td></tr>`;

    // pager
    const pages = Math.max(1, Math.ceil((DISK.total||0)/DISK.per_page));
    DISK.page = Math.min(DISK.page, pages);
    const items = [];
    const add = (p,l,cls='')=> items.push(`<li class="page-item ${cls}"><a class="page-link" data-page="${p}">${l}</a></li>`);
    add(DISK.page-1,'«', DISK.page<=1?'disabled':'');
    let s = Math.max(1, DISK.page-2), e = Math.min(pages, s+4); s = Math.max(1, e-4);
    for(let p=s;p<=e;p++) add(p,p, p===DISK.page?'active':'');
    add(DISK.page+1,'»', DISK.page>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }

  async function diskList(){
    hide($('#disk-alert')); hide($('#disk-ok'));
    const qs = new URLSearchParams({ action:'discos_list', page:String(DISK.page), per_page:String(DISK.per_page) });
    try{
      const r = await j(`${apiUrl}?${qs.toString()}`);
      DISK.rows = r.data || []; DISK.total = r.total || 0;
      diskPaint();
      // refrescar combo de asignación
      const sel = $('#hist-disk');
      if (sel) {
        sel.innerHTML = `<option value="0">Selecciona del inventario…</option>` +
          (DISK.rows||[]).map(d=>`<option value="${d.id}">#${d.id} · ${d.disco_total}GB${(d.disco_restante!=null)?' · libre '+d.disco_restante+'GB':''}</option>`).join('');
      }
    }catch(err){
      show($('#disk-alert'), err.message || 'No se pudieron listar discos');
    }
  }

  async function diskSave(){
    hide($('#disk-alert')); hide($('#disk-ok'));
    const id = parseInt($('#disk-id').value||'0',10)||0;
    const fd = new FormData();
    fd.append('action', id? 'disco_update' : 'disco_create');
    fd.append('id', String(id));
    fd.append('disco_total', $('#disk-total').value || '0');
    fd.append('disco_restante', ($('#disk-free').value ?? ''));
    fd.append('ultimo_cambio', ($('#disk-dt').value ?? ''));

    try{
      await j(apiUrl, {method:'POST', body:fd});
      show($('#disk-ok'), id? 'Disco actualizado.' : 'Disco creado.');
      // reset form
      $('#disk-id').value='0'; $('#disk-total').value=''; $('#disk-free').value=''; $('#disk-dt').value='';
      DISK.page = 1; await diskList();
      await histInit(); // por si el combo de asignación lo necesita
    }catch(err){
      show($('#disk-alert'), err.message || 'No se pudo guardar el disco');
    }
  }

  async function diskDelete(id){
    if (!confirm('¿Eliminar el disco #'+id+'?')) return;
    hide($('#disk-alert')); hide($('#disk-ok'));
    const fd = new FormData();
    fd.append('action','disco_delete'); fd.append('id', String(id));
    try{
      await j(apiUrl, {method:'POST', body:fd});
      show($('#disk-ok'), 'Disco eliminado.');
      await diskList();
      await histInit();
    }catch(err){
      show($('#disk-alert'), err.message || 'No se pudo eliminar');
    }
  }

  // --------- Historial: init + listar + asignar/retirar ----------
  async function histInit(){
    const hint  = $('#hist-hint');
    const panel = $('#hist-panel');
    if (!TOP.empresa_id) { panel.classList.add('d-none'); hint.classList.remove('d-none'); return; }

    // requiere DVR creado
    if (!TOP.dvr_id) { panel.classList.add('d-none'); hint.classList.remove('d-none'); return; }

    hint.classList.add('d-none'); panel.classList.remove('d-none');
    await histRefresh();
  }

  async function histRefresh(){
    hide($('#hist-alert')); hide($('#hist-ok'));
    // current
    try{
      const r = await j(`${apiUrl}?action=dvr_get&empresa_id=${TOP.empresa_id}`);
      const d = r.data || null;
      const cur = d?.id_disco_actual ? `#${d.id_disco_actual}` : '— sin disco —';
      $('#hist-current').textContent = cur;
    }catch(_){ $('#hist-current').textContent = '—'; }

    // list
    try{
      const r2 = await j(`${apiUrl}?action=dvr_hist&empresa_id=${TOP.empresa_id}`);
      const rows = r2.data || [];
      $('#hist-tbody').innerHTML = rows.map(h=>`
        <tr>
          <td class="text-muted">#${h.id}</td>
          <td>#${h.id_disco}</td>
          <td>${esc(h.fecha_instalacion || '')}</td>
          <td>${esc(h.fecha_retiro || '—')}</td>
          <td>${h.disco_total} / ${(h.disco_restante==null)?'—':h.disco_restante}</td>
        </tr>
      `).join('') || `<tr><td colspan="5" class="text-muted">Sin historial</td></tr>`;
    }catch(err){
      show($('#hist-alert'), err.message || 'No se pudo cargar historial');
    }
  }

  async function histAssign(){
    const diskId = parseInt($('#hist-disk').value||'0',10)||0;
    if (!diskId) return;
    if (!confirm('¿Asignar el disco #'+diskId+' al DVR de esta empresa?')) return;

    hide($('#hist-alert')); hide($('#hist-ok'));
    const fd = new FormData();
    fd.append('action','dvr_assign_disco');
    fd.append('empresa_id', String(TOP.empresa_id));
    fd.append('disco_id', String(diskId));
    try{
      await j(apiUrl, {method:'POST', body:fd});
      show($('#hist-ok'), 'Disco asignado.');
      await histRefresh();
    }catch(err){
      show($('#hist-alert'), err.message || 'No se pudo asignar');
    }
  }

  async function histRetire(){
    if (!confirm('¿Retirar el disco actual del DVR?')) return;
    hide($('#hist-alert')); hide($('#hist-ok'));
    const fd = new FormData();
    fd.append('action','dvr_retire_disco');
    fd.append('empresa_id', String(TOP.empresa_id));
    try{
      await j(apiUrl, {method:'POST', body:fd});
      show($('#hist-ok'), 'Disco retirado.');
      await histRefresh();
    }catch(err){
      show($('#hist-alert'), err.message || 'No se pudo retirar');
    }
  }

  // --------- Eventos ----------
  slot.addEventListener('change', async (e)=>{
    if (e.target?.id === 'dv-empresa') {
      TOP.empresa_id = parseInt(e.target.value,10) || 0;
      await dvrGet();
    }
  });

  slot.addEventListener('click', async (e)=>{
    if (e.target?.id === 'dvr-save') { e.preventDefault(); await dvrSave(); }
    if (e.target?.classList.contains('disk-edit')) {
      const tr = e.target.closest('tr[data-id]'); if (!tr) return;
      $('#disk-id').value = tr.dataset.id || '0';
      $('#disk-total').value = tr.children[1].textContent.trim();
      const freeTxt = tr.children[2].textContent.trim();
      $('#disk-free').value = (freeTxt==='—'?'':freeTxt);
      const dtTxt = tr.children[3].textContent.trim();
      $('#disk-dt').value = (dtTxt==='—'?'': dtTxt.replace(' ','T'));
    }
    if (e.target?.classList.contains('disk-del')) {
      const tr = e.target.closest('tr[data-id]'); if (!tr) return;
      await diskDelete(parseInt(tr.dataset.id,10));
    }
    if (e.target?.id === 'disk-save') { e.preventDefault(); await diskSave(); }

    if (e.target?.id === 'hist-assign') { e.preventDefault(); await histAssign(); }
    if (e.target?.id === 'hist-retire') { e.preventDefault(); await histRetire(); }
  });

  // Pager discos
  slot.addEventListener('click', (e)=>{
    const a = e.target?.closest('#disk-pager a[data-page]'); if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10); if (p>0){ DISK.page=p; diskList(); }
  });

  // --------- Refresh (al reabrir modal) ----------
  slot.__camRefresh = async function(){
    hide($('#dvr-alert')); hide($('#dvr-ok'));
    hide($('#disk-alert')); hide($('#disk-ok'));
    hide($('#hist-alert')); hide($('#hist-ok'));

    TOP.empresa_id = 0; TOP.dvr_id = 0;
    $('#dv-empresa').value='0';
    // limpiar DVR form
    $('#dvr-id').value='0'; $('#dvr-u').value=''; $('#dvr-p').value='';
    $('#dvr-su').value=''; $('#dvr-sp').value=''; $('#dvr-rl').value=''; $('#dvr-ll').value='';
    $('#dvr-cams').value='0';
    // historial
    $('#hist-panel')?.classList.add('d-none'); $('#hist-hint')?.classList.remove('d-none');

    await cargarEmpresas();
    DISK.page=1; await diskList();
  };

  // Primera carga
  slot.__camRefresh();
}
