/* SPA ligera para inventario por empresa (sin recargar) */
(() => {
  const CFG = window.INV_CFG || {};
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => [...root.querySelectorAll(sel)];
  const esc = s => (s ?? '').toString().replace(/[&<>\"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));

  const API = CFG.api;
  const state = {
    cat: 'computadoras',
    meta: null,                 // defs de thead/form por cat
    q: '', estado: '', page: 1, per: 10,
    rows: [], total: 0,
    editingId: 0,
  };

  // ---------- UI init ----------
  const invCards = $('#inv-cards');
  const invTabs  = $('#inv-tabs');
  const tHead    = $('#inv-thead');
  const tBody    = $('#inv-tbody');
  const pager    = $('#inv-pager');
  const qInput   = $('#inv-q');
  const qClear   = $('#inv-clear');
  const sEstado  = $('#inv-estado');
  const btnNew   = $('#btn-new');

  const drawer   = $('#inv-drawer');
  const mask     = $('#inv-mask');
  const dClose   = $('#drawer-close');
  const dCancel  = $('#btn-cancel');
  const dTitle   = $('#drawer-title');
  const f        = $('#inv-form');
  const fId      = $('#f-id');
  const fTabla   = $('#f-tabla');
  const fFields  = $('#form-fields');
  const fErr     = $('#form-err');
  const fOk      = $('#form-ok');

  function showDrawer(open=true){
    drawer.classList.toggle('open', open);
    mask.classList.toggle('show', open);
    if (!open) { fErr.classList.add('d-none'); fOk.classList.add('d-none'); }
  }

  dClose.addEventListener('click', () => showDrawer(false));
  dCancel.addEventListener('click', () => showDrawer(false));
  mask.addEventListener('click', () => showDrawer(false));

  // ---------- Fetch helpers ----------
  async function j(url, opts) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  // ---------- Load meta + stats ----------
  async function loadMeta(){
    const d = await j(`${API}?action=meta`);
    state.meta = d.data || {};
  }
  async function loadStats(){
    const d = await j(`${API}?action=stats`);
    const s = d.data || {};
    const label = {
      computadoras:'Computadoras',
      camaras:'Cámaras',
      dvrs:'DVR',
      huelleros:'Huelleros',
      switches:'Switches',
      red:'Red',
      transmision:'Transmisión',
    };
    invCards.innerHTML = Object.keys(label).map(k => {
      const it = s[k] || {activos:0,total:0};
      return `
      <div class="col-6 col-sm-4 col-lg-3 col-xxl-2">
        <div class="inv-card">
          <div class="h">${esc(label[k])}</div>
          <div class="d-flex align-items-baseline justify-content-between">
            <div class="n">${it.total}</div>
            <div class="mut">Activos: ${it.activos}</div>
          </div>
        </div>
      </div>`;
    }).join('');
  }

  // ---------- Tabs ----------
  invTabs.addEventListener('click', (e)=>{
    const a = e.target.closest('a[data-cat]'); if (!a) return;
    e.preventDefault();
    $$('.nav-link', invTabs).forEach(x=>x.classList.remove('active'));
    a.classList.add('active');
    state.cat = a.dataset.cat;
    state.page = 1; qInput.value=''; sEstado.value='';
    state.q=''; state.estado='';
    renderHead();
    loadList();
  });

  // ---------- Toolbar ----------
  qInput.addEventListener('input', () => { state.q = qInput.value.trim(); state.page=1; debounceList(); });
  qClear.addEventListener('click', () => { qInput.value=''; state.q=''; state.page=1; loadList(); });
  sEstado.addEventListener('change', () => { state.estado = sEstado.value; state.page=1; loadList(); });

  btnNew.addEventListener('click', () => {
    openForm(state.cat, 0, null);
  });

  // ---------- Head (thead) ----------
  function renderHead() {
    const m = (state.meta || {})[state.cat];
    const cols = (m && m.thead) ? m.thead : [];
    tHead.innerHTML = `<tr>${cols.map(c=>`<th>${esc(c)}</th>`).join('')}</tr>`;
    tBody.innerHTML = '';
    pager.innerHTML = '';
  }

  // ---------- List ----------
  async function loadList(){
    try{
      $('#inv-alert').classList.add('d-none');
      const qs = new URLSearchParams({
        action:'list', tabla: state.cat,
        page: state.page, per: state.per,
        q: state.q, estado: state.estado
      });
      const d = await j(`${API}?${qs.toString()}`);
      state.rows = d.data || [];
      state.total = d.total || 0;
      paintRows();
      paintPager();
      await loadStats(); // refresca tarjetas
    }catch(err){
      const a=$('#inv-alert'); a.textContent = err.message || 'Error';
      a.classList.remove('d-none');
    }
  }
  const debounceList = (()=>{ let t; return ()=>{ clearTimeout(t); t=setTimeout(loadList,300); }; })();

  function paintRows(){
    const cat = state.cat;
    const rows = state.rows;
    if (!rows.length) { tBody.innerHTML = `<tr><td colspan="8" class="text-muted">Sin registros.</td></tr>`; return; }

    const r = rows.map(x => {
      switch (cat) {
        case 'computadoras':
          return `<tr>
            <td>${esc(x.ambiente||'')}</td>
            <td><div class="fw-semibold">${esc(x.nombre_equipo||'')}</div></td>
            <td>${esc([x.marca,x.modelo].filter(Boolean).join(' / '))}</td>
            <td>${esc(x.sistema_operativo||'')}</td>
            <td>${esc(x.ip||'')}</td>
            <td>${+x.activo?'<span class="badge bg-success">Sí</span>':'<span class="badge bg-secondary">No</span>'}</td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-primary" data-act="edit" data-id="${x.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm ${+x.activo?'btn-warning':'btn-success'}" data-act="toggle" data-id="${x.id}" data-new="${+x.activo?0:1}">
                ${+x.activo?'Inactivar':'Activar'}
              </button>
            </td>
          </tr>`;
        case 'camaras':
          return `<tr>
            <td>${esc(x.etiqueta||'')}</td>
            <td>${esc(x.ambiente||'')}</td>
            <td>${esc([x.marca,x.modelo].filter(Boolean).join(' / '))}</td>
            <td>${esc(x.serie||'')}</td>
            <td>${+x.activo?'<span class="badge bg-success">Sí</span>':'<span class="badge bg-secondary">No</span>'}</td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-primary" data-act="edit" data-id="${x.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm ${+x.activo?'btn-warning':'btn-success'}" data-act="toggle" data-id="${x.id}" data-new="${+x.activo?0:1}">
                ${+x.activo?'Inactivar':'Activar'}
              </button>
            </td>
          </tr>`;
        case 'dvrs':
        case 'switches':
          return `<tr>
            <td>${esc([x.marca,x.modelo].filter(Boolean).join(' / '))}</td>
            <td>${esc(x.serie||'')}</td>
            <td>${+x.activo?'<span class="badge bg-success">Sí</span>':'<span class="badge bg-secondary">No</span>'}</td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-primary" data-act="edit" data-id="${x.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm ${+x.activo?'btn-warning':'btn-success'}" data-act="toggle" data-id="${x.id}" data-new="${+x.activo?0:1}">
                ${+x.activo?'Inactivar':'Activar'}
              </button>
            </td>
          </tr>`;
        case 'huelleros':
          return `<tr>
            <td>${esc(x.etiqueta||'')}</td>
            <td>${esc([x.marca,x.modelo].filter(Boolean).join(' / '))}</td>
            <td>${esc(x.serie||'')}</td>
            <td>${+x.activo?'<span class="badge bg-success">Sí</span>':'<span class="badge bg-secondary">No</span>'}</td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-primary" data-act="edit" data-id="${x.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm ${+x.activo?'btn-warning':'btn-success'}" data-act="toggle" data-id="${x.id}" data-new="${+x.activo?0:1}">
                ${+x.activo?'Inactivar':'Activar'}
              </button>
            </td>
          </tr>`;
        case 'red':
          return `<tr>
            <td>${esc(x.ip_publica||'')}</td>
            <td>${esc(x.transmision_online||'')}</td>
            <td>${esc([x.bajada_txt, x.subida_txt].filter(Boolean).join(' / '))}</td>
            <td>${+x.activo?'<span class="badge bg-success">Sí</span>':'<span class="badge bg-secondary">No</span>'}</td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-primary" data-act="edit" data-id="${x.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm ${+x.activo?'btn-warning':'btn-success'}" data-act="toggle" data-id="${x.id}" data-new="${+x.activo?0:1}">
                ${+x.activo?'Inactivar':'Activar'}
              </button>
            </td>
          </tr>`;
        case 'transmision':
          return `<tr>
            <td>${esc(x.acceso_url||'')}</td>
            <td>${esc(x.usuario||'')}</td>
            <td>${esc(x.clave||'')}</td>
            <td>${+x.activo?'<span class="badge bg-success">Sí</span>':'<span class="badge bg-secondary">No</span>'}</td>
            <td class="text-nowrap">
              <button class="btn btn-sm btn-primary" data-act="edit" data-id="${x.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm ${+x.activo?'btn-warning':'btn-success'}" data-act="toggle" data-id="${x.id}" data-new="${+x.activo?0:1}">
                ${+x.activo?'Inactivar':'Activar'}
              </button>
            </td>
          </tr>`;
      }
    }).join('');
    tBody.innerHTML = r;
  }

  function paintPager(){
    const pages = Math.max(1, Math.ceil(state.total / state.per));
    const cur   = Math.min(state.page, pages);
    const items = [];
    const add = (p, label, cls='') => items.push(
      `<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`
    );
    add(cur-1, '«', cur<=1?'disabled':'');
    let s = Math.max(1, cur-2), e = Math.min(pages, s+4); s = Math.max(1, e-4);
    for (let p=s; p<=e; p++) add(p, p, p===cur?'active':'');
    add(cur+1, '»', cur>=pages?'disabled':'');
    pager.innerHTML = items.join('');
  }

  pager.addEventListener('click', (e)=>{
    const a = e.target.closest('a[data-page]'); if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10); if (p>0) { state.page=p; loadList(); }
  });

  // ---------- actions (edit/toggle) ----------
  tBody.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-act]'); if (!btn) return;
    const act = btn.dataset.act; const id = parseInt(btn.dataset.id,10) || 0;
    if (act==='edit') {
      openForm(state.cat, id, null);
    } else if (act==='toggle') {
      const nuevo = parseInt(btn.dataset.new,10) || 0;
      if (!confirm(`¿${nuevo? 'Activar':'Inactivar'} este registro?`)) return;
      const fd = new FormData();
      fd.append('action','toggle'); fd.append('tabla',state.cat); fd.append('id', id); fd.append('activo', nuevo);
      try { await j(API, {method:'POST', body:fd}); await loadList(); }
      catch(err){ alert(err.message||'Error'); }
    }
  });

  // ---------- form builder ----------
  function buildFields(cat, row=null) {
    const def = (state.meta?.[cat]?.form) || [];
    fFields.innerHTML = def.map(d => {
      const val = row ? (row[d.name] ?? '') : (d.type==='switch' ? 1 : '');
      const common = `name="${esc(d.name)}" id="f-${esc(d.name)}"`;
      const label  = `<label class="form-label">${esc(d.label)}${d.req?' *':''}</label>`;
      const req    = d.req ? ' required' : '';
      const max    = d.max ? ` maxlength="${d.max}"` : '';
      if (d.type === 'textarea') {
        return `<div class="mb-2">${label}<textarea class="form-control"${req}${max} ${common} rows="2">${esc(val)}</textarea></div>`;
      } else if (d.type === 'switch') {
        const chk = row ? (+val ? 'checked' : '') : 'checked';
        return `<div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" ${common} ${chk}>
          <label class="form-check-label" for="f-${esc(d.name)}">${esc(d.label)}</label>
        </div>`;
      }
      return `<div class="mb-2">${label}<input class="form-control" type="text"${req}${max} ${common} value="${esc(val)}"></div>`;
    }).join('');
  }

  async function openForm(cat, id=0, preset=null){
    f.reset?.();
    fErr.classList.add('d-none'); fOk.classList.add('d-none');
    fId.value = id ? String(id) : '';
    fTabla.value = cat;
    dTitle.textContent = id ? 'Editar' : 'Nuevo';
    if (id>0) {
      try{
        const r = await j(`${API}?action=get&tabla=${encodeURIComponent(cat)}&id=${id}`);
        buildFields(cat, r.data || {});
      }catch(err){
        alert(err.message || 'No se pudo cargar');
        return;
      }
    } else {
      buildFields(cat, preset);
    }
    showDrawer(true);
    setTimeout(()=>$('#inv-drawer input, #inv-drawer textarea')?.focus(), 50);
  }

  // ---------- submit ----------
  f.addEventListener('submit', async (e)=>{
    e.preventDefault();
    fErr.classList.add('d-none'); fOk.classList.add('d-none');
    const fd = new FormData(f);
    fd.append('action','save');
    // normaliza switch activo (si existe)
    if ($('#f-activo')) { fd.set('activo', $('#f-activo').checked ? '1' : '0'); }

    try{
      const r = await j(API, {method:'POST', body: fd});
      fOk.textContent = 'Guardado correctamente.'; fOk.classList.remove('d-none');
      await loadList();
      setTimeout(()=> showDrawer(false), 400);
    }catch(err){
      fErr.textContent = err.message || 'Error al guardar'; fErr.classList.remove('d-none');
    }
  });

  // ---------- boot ----------
  (async function boot(){
    await loadMeta();
    renderHead();
    await loadStats();
    await loadList();
  })();
})();
