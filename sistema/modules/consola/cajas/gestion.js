// modules/consola/cajas/gestion.js
export function init(slot, apiUrl) {
  if (slot.__cajasBound) { slot.__cajasRefresh?.(); return; }
  slot.__cajasBound = true;

  /* helpers */
  const $ = (sel) => slot.querySelector(sel);
  const esc = (s)=> (s??'').toString().replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const showOK = (msg)=> showAlert(msg,'ok');
  const showERR= (msg)=> showAlert(msg,'err');
  const fmt2 = (n)=> String(n).padStart(2,'0');
  const ymd = (d)=> `${d.getFullYear()}-${fmt2(d.getMonth()+1)}-${fmt2(d.getDate())}`;
  const ym  = (d)=> `${d.getFullYear()}-${fmt2(d.getMonth()+1)}`;
  function toDMY(s){
    if(!s) return '';
    const t = new Date(s.replace(' ','T'));
    return `${fmt2(t.getDate())}/${fmt2(t.getMonth()+1)}/${t.getFullYear()}`;
  }
  function toHM(s){
    if(!s) return '';
    const t = new Date(s.replace(' ','T'));
    return `${fmt2(t.getHours())}:${fmt2(t.getMinutes())}`;
  }
  function badge(estado){ return `<span class="badge ${estado==='abierta'?'green':'red'}">${esc(estado)}</span>`; }

  // === MEJORA: UI dinámica para "1.b Apertura rápida (mensual)" ===
  function updateQuickOpenUI(){
    // Ajusta texto y comportamiento del botón 1.b según la selección y estado actual
    const btn = $('#btn-m-open');
    const mOpen = $('#m-open-month');
    if (!btn || !mOpen) return;

    const ymStr = (mOpen.value || '').trim(); // "YYYY-MM"
    const cur = FX.mensual_actual ? `${FX.mensual_actual.anio}-${fmt2(FX.mensual_actual.mes)}` : null;

    if (!FX.empresa_id || !ymStr) {
      btn.textContent = 'Abrir mensual';
      btn.dataset.mode = 'abrir';
      btn.disabled = !FX.empresa_id;
      return;
    }

    if (cur && ymStr === cur) {
      // El mes elegido ya está ABIERTO
      btn.textContent = 'Cerrar mensual (mes actual)';
      btn.dataset.mode = 'cerrar';
      btn.disabled = false;
    } else {
      // El mes elegido no está abierto; puede no existir o estar cerrado
      btn.textContent = 'Abrir mensual';
      btn.dataset.mode = 'abrir';
      btn.disabled = false;
    }
  }

  async function j(url, opts = {}) {
    const r = await fetch(url, { credentials: 'same-origin', ...opts });
    const txt = await r.text();
    let d;
    try {
      d = JSON.parse(txt);
    } catch {
      const h1 = txt.match(/<h1[^>]*>(.*?)<\/h1>/i);
      const title = txt.match(/<title[^>]*>(.*?)<\/title>/i);
      const msg = (h1?.[1] || title?.[1] || '').replace(/<[^>]+>/g, '').trim();
      throw new Error(msg || 'Respuesta inválida (backend no devolvió JSON)');
    }
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  function showAlert(msg, type){
    const host = $('#alerts');
    host.innerHTML = `<div class="alert ${type==='ok'?'ok':'err'}">${esc(msg)}</div>`;
    setTimeout(()=>{ host.innerHTML=''; }, 3500);
  }

  const FX = { empresa_id:0, mensual_actual: null, diaria_actual: null };
  const LM = { estado:'', mes_ini:'', mes_fin:'', page:1, per_page:5, total:0, rows:[] };
  const LD = { estado:'', fecha_ini:'', fecha_fin:'', page:1, per_page:5, total:0, rows:[] };

  /* combos */
  async function loadEmpresas(){
    const sel = $('#fx-emp'); sel.innerHTML='<option value="0">Cargando…</option>';
    try{
      const r = await j(`${apiUrl}?action=empresas_combo`);
      const opts = []
        .concat(['<option value="0">Todos (todas las empresas)</option>'])
        .concat(['<option value="" disabled>──────────</option>'])
        .concat((r.data||[]).map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`));
      sel.innerHTML = opts.join('');
      // por defecto: Todos
      sel.value = "0";
      FX.empresa_id = 0;
    }catch(_){ sel.innerHTML='<option value="0">Error</option>'; }
  }

  /* KPIs / resumen */
  async function loadResumen(){
    // Guarda también periodo actual para la lógica de 1.b
    if (!FX.empresa_id) {
      $('#k-mens').textContent='—';
      $('#k-dia').textContent='—';
      $('#k-mens-act').textContent='—';
      $('#k-dia-act').textContent='—';
      FX.mensual_actual = null;
      FX.diaria_actual = null;
      updateQuickOpenUI();
      return;
    }
    try{
      const r = await j(`${apiUrl}?action=resumen_empresa&empresa_id=${FX.empresa_id}`);
      $('#k-mens').textContent = r.tot_mensuales_anio ?? '0';
      $('#k-dia').textContent  = r.tot_diarias_mes ?? '0';
      $('#k-mens-act').textContent = r.mensual_actual ? `${r.mensual_actual.codigo}` : '—';
      $('#k-dia-act').textContent  = r.diaria_actual ? `${r.diaria_actual.codigo}` : '—';
      FX.mensual_actual = r.mensual_actual || null; // {id, anio, mes, codigo} | null
      FX.diaria_actual  = r.diaria_actual || null;  // {id, fecha, codigo} | null
      updateQuickOpenUI();
    }catch(err){
      showERR(err.message||'No se pudo cargar el resumen');
      FX.mensual_actual = null;
      FX.diaria_actual  = null;
      updateQuickOpenUI();
    }
  }

  /* listados */
  async function loadMensuales(){
    const qs = new URLSearchParams({
      action:'list_mensuales',
      empresa_id:String(FX.empresa_id), // 0 => Todas
      estado: LM.estado||'',
      mes_ini: LM.mes_ini||'',
      mes_fin: LM.mes_fin||'',
      page: String(LM.page),
      per_page: String(LM.per_page)
    });
    try{
      const r = await j(`${apiUrl}?${qs.toString()}`);
      LM.rows=r.data||[]; LM.total=r.total||0;
      pintarMensuales();
    }catch(err){
      $('#lm-tbody').innerHTML = `<tr><td colspan="6" class="text-danger">${esc(err.message||'Error')}</td></tr>`;
      $('#lm-pager').innerHTML='';
    }
  }

  function pintarMensuales(){
    const tb = $('#lm-tbody'); const ul = $('#lm-pager');
    const start = (LM.page-1)*LM.per_page;
    const rows = (LM.rows||[]);
    tb.innerHTML = rows.map((r,i)=>`
      <tr data-id="${r.id}" data-emp="${r.id_empresa}">
        <td>${start+i+1}</td>
        <td>${esc(r.empresa)}</td>
        <td>${r.anio}-${fmt2(r.mes)}</td>
        <td>${esc(r.codigo)}</td>
        <td>${badge(r.estado)}</td>
        <td class="text-end">
          <div class="actions">
            ${r.estado==='abierta'
              ? `<button class="btn yellow m-close">Cerrar</button>`
              : `<button class="btn blue m-open">Abrir</button>`}
            <button class="btn gray m-rep">reporte</button>
            <button class="btn red m-del">Eliminar</button>
          </div>
          <div class="submeta">
            <div><b>Apertura oficial:</b> ${toDMY(r.abierto_en)} ${toHM(r.abierto_en)}</div>
            <div><b>Cierre oficial:</b> ${r.cerrado_en ? (toDMY(r.cerrado_en)+' '+toHM(r.cerrado_en)) : '—'}</div>
          </div>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="6" class="text-muted">Sin resultados</td></tr>`;

    // pager
    const pages = Math.max(1, Math.ceil(LM.total/LM.per_page));
    const cur = Math.min(Math.max(1,LM.page),pages); LM.page=cur;
    const out=[];
    const add=(p,l,cls)=> out.push(`<span class="page ${cls||''}" data-page="${p}">${l}</span>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p, p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = out.join('');
  }

  async function loadDiarias(){
    const qs = new URLSearchParams({
      action:'list_diarias',
      empresa_id:String(FX.empresa_id), // 0 => Todas
      estado: LD.estado||'',
      fecha_ini: LD.fecha_ini||'',
      fecha_fin: LD.fecha_fin||'',
      page: String(LD.page),
      per_page: String(LD.per_page)
    });
    try{
      const r = await j(`${apiUrl}?${qs.toString()}`);
      LD.rows=r.data||[]; LD.total=r.total||0;
      pintarDiarias();
    }catch(err){
      $('#ld-tbody').innerHTML = `<tr><td colspan="6" class="text-danger">${esc(err.message||'Error')}</td></tr>`;
      $('#ld-pager').innerHTML='';
    }
  }

  function pintarDiarias(){
    const tb = $('#ld-tbody'); const ul = $('#ld-pager');
    const start = (LD.page-1)*LD.per_page;
    const rows = (LD.rows||[]);
    tb.innerHTML = rows.map((r,i)=>`
      <tr data-id="${r.id}" data-emp="${r.id_empresa}">
        <td>${start+i+1}</td>
        <td>${esc(r.empresa)}</td>
        <td>${r.fecha}</td>
        <td>${esc(r.codigo)}</td>
        <td>${badge(r.estado)}</td>
        <td class="text-end">
          <div class="actions">
            ${r.estado==='abierta'
              ? `<button class="btn yellow d-close">Cerrar</button>`
              : `<button class="btn blue d-open">Abrir</button>`}
            <button class="btn gray d-rep">reporte</button>
            <button class="btn red d-del">Eliminar</button>
          </div>
          <div class="submeta">
            <div><b>Apertura oficial:</b> ${toDMY(r.abierto_en)} ${toHM(r.abierto_en)}</div>
            <div><b>Cierre oficial:</b> ${r.cerrado_en ? (toDMY(r.cerrado_en)+' '+toHM(r.cerrado_en)) : '—'}</div>
          </div>
        </td>
      </tr>
    `).join('') || `<tr><td colspan="6" class="text-muted">Sin resultados</td></tr>`;

    // pager
    const pages = Math.max(1, Math.ceil(LD.total/LD.per_page));
    const cur = Math.min(Math.max(1,LD.page),pages); LD.page=cur;
    const out=[]; const add=(p,l,cls)=> out.push(`<span class="page ${cls||''}" data-page="${p}">${l}</span>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p, p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = out.join('');
  }

  async function recargarTodo(){ await Promise.all([loadResumen(), loadMensuales(), loadDiarias()]); }

  /* acciones servidor */
  async function abrirMensual(anio, mes, empresaId){
    const emp = parseInt(empresaId ?? FX.empresa_id, 10) || 0;
    if (!emp) { showERR('Selecciona una empresa válida.'); return; }
    const fd = new FormData();
    fd.append('action','abrir_mensual');
    fd.append('empresa_id', String(emp));
    fd.append('anio', String(anio));
    fd.append('mes', String(mes));
    try{
      const r = await j(apiUrl,{method:'POST',body:fd});
      if (r.noop) showOK('La mensual ya estaba abierta.');
      else       showOK('Mensual abierta.');
      await recargarTodo();
    }catch(err){
      showERR(err.message||'No se pudo abrir mensual');
    }
  }

  async function cerrarMensual(empresaId){
    const emp = parseInt(empresaId ?? FX.empresa_id, 10) || 0;
    if (!emp) { showERR('Selecciona una empresa válida.'); return; }
    const fd = new FormData();
    fd.append('action','cerrar_mensual');
    fd.append('empresa_id', String(emp));
    try{
      await j(apiUrl,{method:'POST',body:fd});
      showOK('Mensual cerrada.');
      await recargarTodo();
    }catch(err){
      showERR(err.message||'No se pudo cerrar mensual');
    }
  }

  async function abrirDiaria(fecha, empresaId){
    const emp = parseInt(empresaId ?? FX.empresa_id, 10) || 0;
    if (!emp) { showERR('Selecciona una empresa válida.'); return; }
    const fd = new FormData();
    fd.append('action','abrir_diaria');
    fd.append('empresa_id', String(emp));
    fd.append('fecha', fecha);
    try{
      const r = await j(apiUrl,{method:'POST',body:fd});
      if (r.noop) showOK('La diaria ya estaba abierta.');
      else       showOK('Diaria abierta.');
      await recargarTodo();
    }catch(err){
      showERR(err.message||'No se pudo abrir diaria');
    }
  }

  async function cerrarDiaria(empresaId){
    const emp = parseInt(empresaId ?? FX.empresa_id, 10) || 0;
    if (!emp) { showERR('Selecciona una empresa válida.'); return; }
    const fd = new FormData();
    fd.append('action','cerrar_diaria');
    fd.append('empresa_id', String(emp));
    try{
      await j(apiUrl,{method:'POST',body:fd});
      showOK('Diaria cerrada.');
      await recargarTodo();
    }catch(err){
      showERR(err.message||'No se pudo cerrar diaria');
    }
  }

  // === NUEVOS: crear (no reabrir) con manejo de NO-OP ===
  async function crearMensual(ymStr){
    if (!FX.empresa_id) { showERR('Selecciona una empresa distinta de "Todos".'); return; }
    if (!/^\d{4}-\d{2}$/.test(ymStr)) { showERR('Mes inválido.'); return; }
    const [an, me] = ymStr.split('-').map(n=>parseInt(n,10));
    const fd = new FormData();
    fd.append('action','crear_mensual');
    fd.append('empresa_id',String(FX.empresa_id));
    fd.append('anio',String(an));
    fd.append('mes',String(me));
    try{
      const r = await j(apiUrl,{method:'POST',body:fd});
      if (r.noop) showOK('La mensual ya estaba abierta.');
      else       showOK('Mensual creada o reabierta.');
      await recargarTodo();
    }catch(err){
      showERR(err.message||'No se pudo crear mensual');
    }
  }

  async function crearDiaria(fecha){
    if (!FX.empresa_id) { showERR('Selecciona una empresa distinta de "Todos".'); return; }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) { showERR('Fecha inválida.'); return; }
    const fd = new FormData();
    fd.append('action','crear_diaria');
    fd.append('empresa_id',String(FX.empresa_id));
    fd.append('fecha',fecha);
    try{
      const r = await j(apiUrl,{method:'POST',body:fd});
      if (r.noop) showOK('La diaria ya estaba abierta.');
      else       showOK('Diaria creada o reabierta.');
      await recargarTodo();
    }catch(err){
      showERR(err.message||'No se pudo crear diaria');
    }
  }

  /* eventos */
  slot.addEventListener('change', async (e)=>{
    const id = e.target?.id;
    if (id==='fx-emp'){
      FX.empresa_id = parseInt(e.target.value,10)||0;
      LM.page=1; LD.page=1;
      await recargarTodo();
      updateQuickOpenUI(); // MEJORA: actualizar UI rápido
    }
    if (id==='lm-estado'){ LM.estado=e.target.value||''; LM.page=1; await loadMensuales(); }
    if (id==='ld-estado'){ LD.estado=e.target.value||''; LD.page=1; await loadDiarias(); }
    if (id==='lm-ini'){ LM.mes_ini = e.target.value||''; LM.page=1; await loadMensuales(); }
    if (id==='lm-fin'){ LM.mes_fin = e.target.value||''; LM.page=1; await loadMensuales(); }
    if (id==='ld-ini'){ LD.fecha_ini = e.target.value||''; LD.page=1; await loadDiarias(); }
    if (id==='ld-fin'){ LD.fecha_fin = e.target.value||''; LD.page=1; await loadDiarias(); }

    // MEJORA: si cambia el month picker, refrescar estado del botón 1.b
    if (id==='m-open-month'){ updateQuickOpenUI(); }
  });

  slot.addEventListener('click', async (e)=>{
    // Apertura rápida (botones arriba)
    if (e.target?.id === 'btn-m-open') {
      const ymStr = $('#m-open-month')?.value || '';
      const mode = e.target.dataset.mode || 'abrir';
      if (mode === 'cerrar') {
        return cerrarMensual(FX.empresa_id);
      } else {
        return crearMensual(ymStr);
      }
    }
    if (e.target?.id === 'btn-d-open') {
      const fStr = $('#d-open-date')?.value || '';
      return crearDiaria(fStr);
    }

    // pagers
    const pm = e.target?.closest('#lm-pager .page'); if(pm){
      const p = parseInt(pm.dataset.page,10);
      if (!Number.isFinite(p)) return;
      if (pm.classList.contains('disabled') || pm.classList.contains('active')) return;
      LM.page=p; await loadMensuales(); return;
    }
    const pd = e.target?.closest('#ld-pager .page'); if(pd){
      const p = parseInt(pd.dataset.page,10);
      if (!Number.isFinite(p)) return;
      if (pd.classList.contains('disabled') || pd.classList.contains('active')) return;
      LD.page=p; await loadDiarias(); return;
    }

    // acciones fila mensual
    const trM = e.target?.closest('#lm-tbody tr[data-id]');
    if (trM){
      const id = parseInt(trM.dataset.id,10);
      const cells = trM.children;
      const ymStr = cells[2].textContent.trim(); // YYYY-MM
      const anio = parseInt(ymStr.slice(0,4),10); const mes = parseInt(ymStr.slice(5,7),10);

      const empRowM = parseInt(trM.dataset.emp,10) || 0;
      if (e.target.closest('.m-open')) return abrirMensual(anio, mes, empRowM);
      if (e.target.closest('.m-close')) return cerrarMensual(empRowM);

      if (e.target.closest('.m-rep')) {
        const url = `${apiUrl.replace('/api.php','')}/reporte.php?tipo=mensual&id=${id}`;
        window.open(url,'_blank','noopener'); return;
      }
      if (e.target.closest('.m-del')){
        if (!confirm('¿Eliminar esta caja mensual? (sus diarias serán eliminadas)')) return;
        const fd=new FormData(); fd.append('action','eliminar_mensual'); fd.append('id',String(id));
        try{ await j(apiUrl,{method:'POST',body:fd}); showOK('Mensual eliminada.'); await recargarTodo(); }
        catch(err){ showERR(err.message||'No se pudo eliminar mensual'); }
        return;
      }
    }

    // acciones fila diaria
    const trD = e.target?.closest('#ld-tbody tr[data-id]');
    if (trD){
      const id = parseInt(trD.dataset.id,10);
      const fecha = trD.children[2].textContent.trim();

      const empRowD = parseInt(trD.dataset.emp,10) || 0;
      if (e.target.closest('.d-open')) return abrirDiaria(fecha, empRowD);
      if (e.target.closest('.d-close')) return cerrarDiaria(empRowD);

      if (e.target.closest('.d-rep')) {
        const url = `${apiUrl.replace('/api.php','')}/reporte.php?tipo=diaria&id=${id}`;
        window.open(url,'_blank','noopener'); return;
      }
      if (e.target.closest('.d-del')){
        if (!confirm('¿Eliminar esta caja diaria?')) return;
        const fd=new FormData(); fd.append('action','eliminar_diaria'); fd.append('id',String(id));
        try{ await j(apiUrl,{method:'POST',body:fd}); showOK('Diaria eliminada.'); await recargarTodo(); }
        catch(err){ showERR(err.message||'No se pudo eliminar diaria'); }
        return;
      }
    }
  });

  /* primera carga */
  slot.__cajasRefresh = async function(){
    await loadEmpresas(); // deja seleccionado "Todos (0)"

    // defaults de rango: mes/año actuales
    let hoy = '', mesActual = '';
    try{
      const r = await j(`${apiUrl}?action=ahora`);
      hoy = r.hoy; mesActual = r.mes_actual;
    }catch(_){
      const t = new Date(); hoy = ymd(t); mesActual = ym(t);
    }
    // Mensuales: del primer mes del año actual al mes actual
    const t = new Date(hoy+'T00:00:00');
    const y = t.getFullYear();
    LM.mes_ini = `${y}-01`; LM.mes_fin = mesActual;
    $('#lm-ini').value = LM.mes_ini; $('#lm-fin').value = LM.mes_fin;
    LM.estado = ''; $('#lm-estado').value='';

    // Diarias: del primer día del mes actual a hoy
    const firstMonthDay = `${mesActual}-01`;
    LD.fecha_ini = firstMonthDay; LD.fecha_fin = hoy;
    $('#ld-ini').value = LD.fecha_ini; $('#ld-fin').value = LD.fecha_fin;
    LD.estado=''; $('#ld-estado').value='';

    // Prefill apertura rápida
    const mOpen = $('#m-open-month'); if (mOpen) mOpen.value = mesActual;
    const dOpen = $('#d-open-date');  if (dOpen) dOpen.value = hoy;

    // Con "Todos" no hay resumen por empresa
    $('#k-mens').textContent='—'; $('#k-dia').textContent='—';
    $('#k-mens-act').textContent='—'; $('#k-dia-act').textContent='—';

    // Cargar listados para "Todos"
    await Promise.all([loadMensuales(), loadDiarias()]);
    // Ajustar estado del botón 1.b con lo que quedó seleccionado
    updateQuickOpenUI();
  };

  slot.__cajasRefresh();
}
