// modules/consola/certificados/gestion.js
export function init(slot, apiUrl) {
  if (slot.__modCertificadosBound) { slot.__modCertificadosRefresh?.(); return; }
  slot.__modCertificadosBound = true;

  // ---------- Helpers ----------
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').toString()
    .replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const show = (el, msg='') => { if(!el) return; const sp=el.querySelector?.('.msg'); if (sp) sp.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); el.classList.add('show'); };
  const hide = el => { if(!el) return; el.classList.add('d-none'); el.classList.remove('show'); };
  const debounce = (fn,ms=300)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url, opts={}) {
    const r = await fetch(url, { credentials: 'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  // ---------- Cargar empresas ----------
  async function cargarEmpresas() {
    const sel = $('#pc-empresa');
    try {
      const r = await j(`${apiUrl}?action=empresas`);
      sel.innerHTML = `<option value="">— Selecciona —</option>` +
        (r.data || []).map(e => `<option value="${e.id}">${esc(e.nombre)}</option>`).join('');
    } catch (err) {
      sel.innerHTML = `<option value="">(Error al cargar)</option>`;
      show($('#pc-alert'), err.message || 'No se pudieron cargar las empresas');
      console.error('cargarEmpresas:', err);
    }
  }

  // URLs relativas -> absolutas (para previews de imágenes guardadas)
  const PROJECT_ROOT = (location.pathname.split('/modules/')[0] || '');
  function assetUrl(relPath) {
    if (!relPath) return '';
    const clean = String(relPath).replace(/^\/+/, '');
    const base  = PROJECT_ROOT ? PROJECT_ROOT : '';
    return `${base}/${clean}`;
  }
  function fileNameFromPath(p){ try{ return (p||'').split('/').pop().split('\\').pop(); }catch{ return ''; } }

  // Estado de edición
  const PC = { editId: 0 };
  function setEditMode(id){
    PC.editId = id;
    const btn = $('#pc-guardar'); if (btn) btn.textContent = 'Guardar cambios';
    $('#pc-cancelar')?.classList.remove('d-none');
  }
  function clearEditMode(){
    PC.editId = 0;
    const btn = $('#pc-guardar'); if (btn) btn.textContent = 'Guardar plantilla';
    $('#pc-cancelar')?.classList.add('d-none');
  }


  // ---------- Preview archivos (sin recortes y mostrando tamaño) ----------
  function setPreview(boxSel, url, name, sizeBytes) {
    const prev = $(`${boxSel}-prev`);
    const cap  = $(`${boxSel}-cap`);
    const siz  = $(`${boxSel}-size`);
    if (prev) prev.style.backgroundImage = url ? `url('${url}')` : '';
    if (cap)  cap.textContent = name || 'Sin imagen actualmente.';
    if (siz)  {
      if (sizeBytes && sizeBytes > 0) {
        siz.textContent = `Peso de archivo cargado: ${(sizeBytes/1024).toFixed(1)} KB`;
        siz.classList.remove('d-none');
      } else {
        siz.textContent = '';
        siz.classList.add('d-none');
      }
    }
  }
  function resetPreview(boxSel, msg) { setPreview(boxSel, '', msg || 'Sin imagen actualmente.', 0); }

  const okTypes = ['image/jpeg','image/png','image/webp'];
  function validarArchivo(f, maxMB=5) {
    if (!f) return {ok:true};
    if (!okTypes.includes(f.type)) return {ok:false,msg:'Formato no permitido (PNG/JPG/WebP)'};
    if (f.size > maxMB*1024*1024)  return {ok:false,msg:`Máximo ${maxMB}MB`};
    return {ok:true};
  }

  slot.addEventListener('change', (e)=>{
    const id = e.target?.id;
    if (!id) return;
    const f = e.target.files?.[0];
    const alertEl = $('#pc-alert'); hide(alertEl);

    if (id === 'pc-fondo' || id === 'pc-logo' || id === 'pc-firma') {
      const r = validarArchivo(f, 5);
      const box = id === 'pc-fondo' ? '#pc-fondo' : (id === 'pc-logo' ? '#pc-logo' : '#pc-firma');
      if (!f) { resetPreview(box); return; }
      if (!r.ok) {
        e.target.value = '';
        resetPreview(box);
        show(alertEl, r.msg);
        return;
      }
      const url = URL.createObjectURL(f);
      setPreview(box, url, f.name || 'archivo', f.size || 0);
      setTimeout(()=>URL.revokeObjectURL(url), 5000);
    }
  });

    // ---------- Guardar (crear / actualizar plantilla) ----------
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('#pc-guardar'); if (!btn) return;
    const okEl=$('#pc-ok'), errEl=$('#pc-alert'); hide(okEl); hide(errEl);

    // Validaciones
    const nombre = ($('#pc-nombre')?.value||'').trim();
    const paginas = parseInt($('#pc-paginas')?.value||'1',10) || 1;
    const id_empresa = parseInt($('#pc-empresa')?.value||'0',10) || 0;
    if (!nombre) { show(errEl, 'El nombre es obligatorio'); return; }
    if (!id_empresa) { show(errEl, 'Selecciona una empresa'); return; }

    const fd = new FormData($('#pc-form'));
    if (PC.editId > 0) {
      fd.append('action', 'update');
      fd.append('id', String(PC.editId));
    } else {
      fd.append('action', 'create');
    }

    btn.disabled=true; const old=btn.textContent; btn.textContent='Guardando…';
    try{
      const r = await j(apiUrl, { method:'POST', body: fd });
      if (PC.editId > 0) {
        show(okEl, `Plantilla #${PC.editId} actualizada.`);
      } else {
        show(okEl, `Plantilla creada. Id: ${r.id}.`);
      }

      // Reset a modo crear
      $('#pc-form')?.reset();
      $('#pc-paginas') && ($('#pc-paginas').value = '1');
      resetPreview('#pc-fondo', 'Sin imagen actualmente.');
      resetPreview('#pc-logo',  'Sin imagen actualmente.');
      resetPreview('#pc-firma', 'Sin imagen actualmente.');
      clearEditMode();

      await pl_cargarLista();
    }catch(err){
      show(errEl, err.message || 'No se pudo guardar');
    }finally{
      btn.disabled=false; btn.textContent=old;
    }
  });
  
    // ---------- Listado de plantillas (panel izquierdo) ----------
  const PL = { q:'', page:1, per_page:5, total:0, rows:[] };

  function pl_pintarTabla(rows){
    const tb = $('#pl-tbody'); const empty = $('#pl-empty');
    if (!tb) return;
    if (!rows || !rows.length) {
      tb.innerHTML = '';
      empty?.classList.remove('d-none');
      return;
    }
    empty?.classList.add('d-none');
    tb.innerHTML = rows.map(r => `
      <tr>
        <td class="text-muted">${r.id}</td>
        <td>${esc(r.nombre)}</td>
        <td>${esc(r.empresa)}</td>
        <td class="text-center">${r.paginas}</td>
        <td>${esc((r.creado || '').slice(0,10))}</td>
        <td>
          <button class="btn btn-sm btn-primary pl-edit" data-id="${r.id}">Editar</button>
        </td>
      </tr>
    `).join('');
  }

  function pl_pintarPager(total){
    const ul = $('#pl-pager'); if (!ul) return;
    const pages = Math.max(1, Math.ceil(total / PL.per_page));
    PL.page = Math.min(PL.page, pages);
    const cur = PL.page, items=[];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s=Math.max(1, cur-2), e=Math.min(pages, s+4); s=Math.max(1, e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }

  async function pl_cargarLista(){
    hide($('#pl-alert'));
    const qs = new URLSearchParams({ action:'list', q:PL.q, page:PL.page, per_page:PL.per_page });
    try{
      const r = await j(`${apiUrl}?${qs.toString()}`);
      PL.rows = r.data || []; PL.total = r.total || 0;
      pl_pintarTabla(PL.rows); pl_pintarPager(PL.total);
    }catch(err){
      const a = $('#pl-alert'); a.textContent = err.message || 'Error al listar plantillas'; a.classList.remove('d-none');
    }
  }

  // Filtro + pager
  slot.addEventListener('input', debounce(e=>{
    if (e.target?.id === 'pl-q') { PL.q = e.target.value || ''; PL.page = 1; pl_cargarLista(); }
  }, 300));

  slot.addEventListener('click', e=>{
    const a = e.target?.closest('#pl-pager a[data-page]'); if (a){ e.preventDefault();
      const li=a.parentElement; if (!li.classList.contains('disabled') && !li.classList.contains('active')){
        const p = parseInt(a.dataset.page,10) || 1; PL.page = p; pl_cargarLista();
      }
      return;
    }

    // Editar -> cargar datos en el Div 1
    const ed = e.target?.closest('.pl-edit');
    if (ed) {
      const id = parseInt(ed.dataset.id,10)||0; if (!id) return;
      (async ()=>{
        try{
          const r = await j(`${apiUrl}?action=get&id=${id}`);
          const d = r.data || {};
          // llenar inputs
          $('#pc-nombre')       && ($('#pc-nombre').value       = d.nombre || '');
          $('#pc-paginas')      && ($('#pc-paginas').value      = String(d.paginas || 1));
          $('#pc-empresa')      && ($('#pc-empresa').value      = String(d.id_empresa || ''));
          $('#pc-representante')&& ($('#pc-representante').value= d.representante || '');
          $('#pc-ciudad')       && ($('#pc-ciudad').value       = d.ciudad || '');
          $('#pc-resolucion')   && ($('#pc-resolucion').value   = d.resolucion || '');
          // limpiar inputs file
          $('#pc-fondo') && ($('#pc-fondo').value = '');
          $('#pc-logo')  && ($('#pc-logo').value  = '');
          $('#pc-firma') && ($('#pc-firma').value = '');
          // previews desde paths guardados
          setPreview('#pc-fondo', assetUrl(d.fondo_path||''), fileNameFromPath(d.fondo_path||''), 0);
          setPreview('#pc-logo',  assetUrl(d.logo_path||''),  fileNameFromPath(d.logo_path||''),  0);
          setPreview('#pc-firma', assetUrl(d.firma_path||''), fileNameFromPath(d.firma_path||''), 0);

          setEditMode(id);
          $('#pc-nombre')?.focus();
        }catch(err){
          const a = $('#pc-alert'); show(a, err.message || 'No se pudo cargar la plantilla');
        }
      })();
    }
  });
  
    // ---------- Cancelar edición ----------
  slot.addEventListener('click', (e)=>{
    const btn = e.target?.closest('#pc-cancelar'); if (!btn) return;
    $('#pc-form')?.reset();
    $('#pc-paginas') && ($('#pc-paginas').value = '1');
    resetPreview('#pc-fondo', 'Sin imagen actualmente.');
    resetPreview('#pc-logo',  'Sin imagen actualmente.');
    resetPreview('#pc-firma', 'Sin imagen actualmente.');
    clearEditMode();
    $('#pc-nombre')?.focus();
  });
  
    // ---------- REFRESH ----------
  slot.__modCertificadosRefresh = async function() {
    hide($('#pc-alert')); hide($('#pc-ok')); hide($('#pl-alert'));
    $('#pc-form')?.reset();
    $('#pc-paginas') && ($('#pc-paginas').value = '1');
    resetPreview('#pc-fondo', 'Sin imagen actualmente.');
    resetPreview('#pc-logo',  'Sin imagen actualmente.');
    resetPreview('#pc-firma', 'Sin imagen actualmente.');
    clearEditMode();

    // Listado izquierdo
    PL.q = ''; PL.page = 1;
    $('#pl-q') && ($('#pl-q').value = '');

    await cargarEmpresas();
    await pl_cargarLista();
  };

  // Primera carga
  slot.__modCertificadosRefresh();

}
