// modules/consola/certificados/gestion.js
export function init(slot, apiUrl) {
  if (slot.__pcBound) { slot.__pcRefresh?.(); return; }
  slot.__pcBound = true;

  // ---------- Helpers ----------
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  const show = (el,msg='')=>{ if(!el) return; const sp=el.querySelector?.('.msg'); if(sp) sp.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); };
  const hide = el => { if(!el) return; el.classList.add('d-none'); };
  async function j(url,opts={}){ const r=await fetch(url,{credentials:'same-origin',...opts}); const d=await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'})); if(!r.ok||!d.ok) throw new Error(d.msg||`HTTP ${r.status}`); return d; }
  // Normalizador de URLs relativas -> absolutas respecto al proyecto (igual que otras triadas)
  const PROJECT_ROOT = (location.pathname.split('/modules/')[0] || '');
  function assetUrl(relPath){ if(!relPath) return ''; const clean=String(relPath).replace(/^\/+/, ''); const base = PROJECT_ROOT ? PROJECT_ROOT : ''; return `${base}/${clean}`; }
  
  // === Imágenes por defecto para los previews ===
// Coloca estos archivos en: modules/consola/certificados/img/
const DEFAULTS = {
  fondo: 'dist/img/default.png',
  logo:  'dist/img/default.png',
  firma: 'dist/img/default.png'
};

  // ---------- Estado ----------
  const F = { editId:0 };                                // formulario superior
  const L = { q:'', empresa_id:'', page:1, per_page:5, rows:[] }; // listado izq.

    // ---------- Previews ----------
    // ---------- Previews ----------
  function setPrev(idBox, path){
    const el = $(idBox); if(!el) return;

    // Elegimos el placeholder según el contenedor
    let dflt = '';
    if (idBox === '#pc-fondo-prev') dflt = DEFAULTS.fondo;
    else if (idBox === '#pc-logo-prev') dflt = DEFAULTS.logo;
    else if (idBox === '#pc-firma-prev') dflt = DEFAULTS.firma;

    // Si no hay path, usamos la imagen default
    let src = path && String(path).trim();
    if (!src) src = dflt;

    // blob:/data:/http(s): -> usar tal cual; caso contrario, normalizar con assetUrl
    let url = '';
    if (/^(blob:|data:|https?:\/\/)/i.test(src)) url = src;
    else url = assetUrl(src);

    el.style.backgroundImage = url ? `url("${url}")` : 'none';
  }
  function setCap(idSpan, txt){ const el=$(idSpan); if(el) el.textContent = txt || 'Sin imagen actualmente.'; }
  function setSize(idAlert, bytes){
    const el=$(idAlert); if(!el) return;
    if(!bytes){ el.classList.add('d-none'); el.textContent=''; return; }
    const kb=(bytes/1024).toFixed(1);
    el.textContent=`Peso de archivo cargado: ${kb} KB`;
    el.classList.remove('d-none');
  }
  function resetImgs(){
    setPrev('#pc-fondo-prev',''); setCap('#pc-fondo-cap','Sin imagen actualmente.'); setSize('#pc-fondo-size',0);
    setPrev('#pc-logo-prev','');  setCap('#pc-logo-cap','Sin imagen actualmente.');  setSize('#pc-logo-size',0);
    setPrev('#pc-firma-prev',''); setCap('#pc-firma-cap','Sin imagen actualmente.'); setSize('#pc-firma-size',0);
    const f1=$('#pc-fondo'); if(f1) f1.value='';
    const f2=$('#pc-logo');  if(f2) f2.value='';
    const f3=$('#pc-firma'); if(f3) f3.value='';
  }

  // ---------- Combos (empresas) ----------
  async function cargarEmpresas(){
    try{
      const r = await j(`${apiUrl}?action=empresas`);
      const arr = Array.isArray(r.data) ? r.data : [];
      const opts = arr.map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      const selForm = $('#pc-empresa'); if(selForm){ selForm.innerHTML = `<option value="">Seleccione…</option>${opts}`; }
      const selList = $('#pl-empresa'); if(selList){ selList.innerHTML = `<option value="">Todas</option>${opts}`; }
    }catch(e){
      // Dejar selects con su opción por defecto si hay error
    }
  }

  // ---------- Listado ----------
  function pintarTabla(rows){
    const tb = $('#pl-tbody'); if(!tb) return;
    if(!Array.isArray(rows) || !rows.length){
      tb.innerHTML = '';
      $('#pl-empty')?.classList.remove('d-none');
      return;
    }
    $('#pl-empty')?.classList.add('d-none');
    tb.innerHTML = rows.map(r=>`
      <tr data-id="${r.id}"
          data-nombre="${esc(r.nombre)}"
          data-paginas="${r.paginas}"
          data-id_empresa="${r.id_empresa}"
          data-representante="${esc(r.representante||'')}"
          data-ciudad="${esc(r.ciudad||'')}"
          data-resolucion="${esc(r.resolucion||'')}"
          data-fondo="${esc(r.fondo_path||'')}"
          data-logo="${esc(r.logo_path||'')}"
          data-firma="${esc(r.firma_path||'')}">
        <td>${r.id}</td>
        <td>${esc(r.nombre)}</td>
        <td>${esc(r.empresa)}</td>
        <td>${r.paginas}</td>
        <td>${esc(r.creado||'')}</td>
        <td><button class="btn btn-sm btn-primary pl-edit">Editar</button></td>
      </tr>
    `).join('');
  }
  function pintarPager(total){
    const ul = $('#pl-pager'); if(!ul) return;
    const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages); const cur = L.page;
    const items=[];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML=items.join('');
  }
  async function pl_cargarLista(){
    hide($('#pl-alert'));
    const qs=new URLSearchParams({action:'list', q:L.q, page:L.page, per_page:L.per_page});
    if (L.empresa_id) qs.append('empresa_id', L.empresa_id);
    try{
      const r=await j(`${apiUrl}?${qs.toString()}`);
      L.rows = r.data || [];
      pintarTabla(L.rows); pintarPager(r.total || 0);
    }catch(err){
      const a=$('#pl-alert'); a.textContent = err.message || 'Error al listar'; a.classList.remove('d-none');
    }
  }

  // ---------- Formulario superior ----------
  function form_setCreate(){
    F.editId=0;
    $('#pc-nombre').value='';
    $('#pc-paginas').value='1';
    $('#pc-representante').value='';
    $('#pc-empresa').value='';
    $('#pc-ciudad').value='';
    $('#pc-resolucion').value='';
    resetImgs();
    $('#pc-cancelar')?.classList.add('d-none');
    $('#pc-guardar')?.classList.remove('disabled');
  }
  function form_setEdit(tr){
    if(!tr) return;
    F.editId = parseInt(tr.dataset.id,10) || 0;
    $('#pc-nombre').value = tr.dataset.nombre || '';
    $('#pc-paginas').value = tr.dataset.paginas || '1';
    $('#pc-representante').value = tr.dataset.representante || '';
    $('#pc-empresa').value = tr.dataset.id_empresa || '';
    $('#pc-ciudad').value = tr.dataset.ciudad || '';
    $('#pc-resolucion').value = tr.dataset.resolucion || '';
    setPrev('#pc-fondo-prev', tr.dataset.fondo || '');
    setPrev('#pc-logo-prev',  tr.dataset.logo  || '');
    setPrev('#pc-firma-prev', tr.dataset.firma || '');
    setCap('#pc-fondo-cap', tr.dataset.fondo ? tr.dataset.fondo.split('/').pop() : 'Sin imagen actualmente.');
    setCap('#pc-logo-cap',  tr.dataset.logo  ? tr.dataset.logo.split('/').pop()   : 'Sin imagen actualmente.');
    setCap('#pc-firma-cap', tr.dataset.firma ? tr.dataset.firma.split('/').pop()  : 'Sin imagen actualmente.');
    setSize('#pc-fondo-size',0); setSize('#pc-logo-size',0); setSize('#pc-firma-size',0);
    $('#pc-cancelar')?.classList.remove('d-none');
  }

  // Previews on change
  slot.addEventListener('change', (e)=>{
    const t=e.target;
    if(!t || t.type!=='file') return;
    const f=t.files?.[0];
    const id=t.id;
    if(!f){
      if(id==='pc-fondo'){ setPrev('#pc-fondo-prev',''); setCap('#pc-fondo-cap','Sin imagen actualmente.'); setSize('#pc-fondo-size',0); }
      if(id==='pc-logo'){  setPrev('#pc-logo-prev','');  setCap('#pc-logo-cap','Sin imagen actualmente.');  setSize('#pc-logo-size',0); }
      if(id==='pc-firma'){ setPrev('#pc-firma-prev',''); setCap('#pc-firma-cap','Sin imagen actualmente.'); setSize('#pc-firma-size',0); }
      return;
    }
    const url=URL.createObjectURL(f);
    if(id==='pc-fondo'){ setPrev('#pc-fondo-prev',url); setCap('#pc-fondo-cap', f.name); setSize('#pc-fondo-size', f.size); }
    if(id==='pc-logo'){  setPrev('#pc-logo-prev', url); setCap('#pc-logo-cap',  f.name); setSize('#pc-logo-size',  f.size); }
    if(id==='pc-firma'){ setPrev('#pc-firma-prev',url); setCap('#pc-firma-cap', f.name); setSize('#pc-firma-size', f.size); }
    setTimeout(()=>URL.revokeObjectURL(url), 4000);
  });

  // Guardar (create/update)
  slot.addEventListener('click', async e=>{
    if(!e.target?.closest('#pc-guardar')) return;
    const btn=e.target; btn.disabled=true;
    const okEl=$('#pc-ok'), errEl=$('#pc-alert'); hide(okEl); hide(errEl);
    try{
      const fd=new FormData($('#pc-form'));
      fd.delete('fondo'); fd.delete('logo'); fd.delete('firma'); // nos aseguramos que existan una sola vez
      if($('#pc-fondo')?.files?.[0]) fd.append('fondo',$('#pc-fondo').files[0]);
      if($('#pc-logo')?.files?.[0])  fd.append('logo',$('#pc-logo').files[0]);
      if($('#pc-firma')?.files?.[0]) fd.append('firma',$('#pc-firma').files[0]);

      fd.append('action', F.editId>0 ? 'update' : 'create');
      if(F.editId>0) fd.append('id', String(F.editId));

      const r=await j(apiUrl, {method:'POST', body:fd});
      show(okEl, F.editId>0 ? 'Plantilla actualizada.' : 'Plantilla creada.');
      form_setCreate();
      L.page=1; await pl_cargarLista();
    }catch(err){ show($('#pc-alert'), err.message || 'Error al guardar'); }
    finally{ btn.disabled=false; }
  });

  // Cancelar edición
  slot.addEventListener('click', e=>{
    if(!e.target?.closest('#pc-cancelar')) return;
    form_setCreate();
  });

  // Editar desde la tabla
  slot.addEventListener('click', e=>{
    const btn=e.target?.closest('.pl-edit'); if(!btn) return;
    const tr=btn.closest('tr'); form_setEdit(tr);
    slot.scrollIntoView({behavior:'smooth', block:'start'});
  });

  // Paginación
  slot.addEventListener('click', e=>{
    const a=e.target?.closest('#pl-pager a[data-page]'); if(!a) return; e.preventDefault();
    const li=a.parentElement; if(li.classList.contains('disabled')||li.classList.contains('active')) return;
    const p=parseInt(a.dataset.page,10); if(p>0){ L.page=p; pl_cargarLista(); }
  });

  // Filtros (buscar / empresa)
  slot.addEventListener('input', debounce(e=>{
    if(e.target?.id==='pl-q'){ L.q=e.target.value||''; L.page=1; pl_cargarLista(); }
  },300));
  slot.addEventListener('change', e=>{
    if(e.target?.id==='pl-empresa'){ L.empresa_id = e.target.value || ''; L.page=1; pl_cargarLista(); }
  });

  // ---------- Refresh ----------
  slot.__pcRefresh = async function(){
    hide($('#pc-alert')); hide($('#pc-ok')); hide($('#pl-alert'));
    await cargarEmpresas();
    form_setCreate();
    L.q=''; L.empresa_id=''; L.page=1; await pl_cargarLista();
  };

  // Primera carga
  slot.__pcRefresh();
}
