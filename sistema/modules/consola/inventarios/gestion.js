// modules/consola/inventarios/gestion.js
export function init(slot, apiUrl) {
  // Evita doble enlace si reabres el modal
  if (slot.__ivBound) { slot.__ivRefresh?.(); return; }
  slot.__ivBound = true;

  // ---------- Helpers ----------
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').toString().replace(/[&<>\"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
  const show = (el, msg) => { if (!el) return; if (msg!=null) el.textContent=msg; el.classList.remove('d-none'); };
  const hide = el => { if (!el) return; el.classList.add('d-none'); };
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url, opts={}) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  // ---------- Especificaciones UI ----------
  const SPECS = {
    pc: {
      label_sg: 'Computadora', label_pl: 'Computadoras',
      columns: ['ID','Nombre','Marca / Modelo','IP','Sistema operativo','Acciones'],
      listRender: r => ([
        r.id,
        esc(r.nombre_equipo || ''),
        esc([r.marca||'', r.modelo||''].filter(Boolean).join(' / ')),
        esc(r.ip || ''),
        esc(r.sistema_operativo || '')
      ]),
      fields: [
        {name:'ambiente', label:'Ambiente', col:'iv-col-6'},
        {name:'nombre_equipo', label:'Nombre del equipo', col:'iv-col-6'},
        {name:'marca', label:'Marca', col:'iv-col-4'},
        {name:'modelo', label:'Modelo', col:'iv-col-4'},
        {name:'serie', label:'Serie', col:'iv-col-4'},
        {name:'procesador', label:'Procesador', col:'iv-col-6'},
        {name:'disco_gb', label:'Disco', col:'iv-col-3', ph:'p.ej. 240 GB'},
        {name:'ram_gb', label:'RAM', col:'iv-col-3', ph:'p.ej. 8 GB'},
        {name:'sistema_operativo', label:'Sistema operativo', col:'iv-col-6'},
        {name:'mac', label:'MAC', col:'iv-col-3'},
        {name:'ip', label:'IP', col:'iv-col-3'},
        {name:'notas', label:'Notas', col:'iv-col-12'}
      ]
    },
    cam: {
      label_sg: 'Cámara', label_pl: 'Cámaras',
      columns: ['ID','Etiqueta','Ambiente','Marca / Modelo','Acciones'],
      listRender: r => ([
        r.id,
        esc(r.etiqueta || ''),
        esc(r.ambiente || ''),
        esc([r.marca||'', r.modelo||''].filter(Boolean).join(' / '))
      ]),
      fields: [
        {name:'etiqueta', label:'Etiqueta', col:'iv-col-4'},
        {name:'ambiente', label:'Ambiente', col:'iv-col-8'},
        {name:'marca', label:'Marca', col:'iv-col-3'},
        {name:'modelo', label:'Modelo', col:'iv-col-3'},
        {name:'serie', label:'Serie', col:'iv-col-3'},
        {name:'notas', label:'Notas', col:'iv-col-12'}
      ]
    },
    dvr: {
      label_sg: 'DVR', label_pl: 'DVR',
      columns: ['ID','Marca / Modelo','Serie','Acciones'],
      listRender: r => ([
        r.id,
        esc([r.marca||'', r.modelo||''].filter(Boolean).join(' / ')),
        esc(r.serie || '')
      ]),
      fields: [
        {name:'marca', label:'Marca', col:'iv-col-4'},
        {name:'modelo', label:'Modelo', col:'iv-col-4'},
        {name:'serie', label:'Serie', col:'iv-col-4'},
        {name:'notas', label:'Notas', col:'iv-col-12'}
      ]
    },
    hue: {
      label_sg: 'Huellero', label_pl: 'Huelleros',
      columns: ['ID','Etiqueta','Marca / Modelo','Serie','Acciones'],
      listRender: r => ([
        r.id,
        esc(r.etiqueta || ''),
        esc([r.marca||'', r.modelo||''].filter(Boolean).join(' / ')),
        esc(r.serie || '')
      ]),
      fields: [
        {name:'etiqueta', label:'Etiqueta', col:'iv-col-4', ph:'p.ej. IDENTIFICADOR 1'},
        {name:'marca', label:'Marca', col:'iv-col-3'},
        {name:'modelo', label:'Modelo', col:'iv-col-3'},
        {name:'serie', label:'Serie', col:'iv-col-2'},
        {name:'notas', label:'Notas', col:'iv-col-12'}
      ]
    },
    sw: {
      label_sg: 'Switch', label_pl: 'Switches',
      columns: ['ID','Marca / Modelo','Serie','Acciones'],
      listRender: r => ([
        r.id,
        esc([r.marca||'', r.modelo||''].filter(Boolean).join(' / ')),
        esc(r.serie || '')
      ]),
      fields: [
        {name:'marca', label:'Marca', col:'iv-col-4'},
        {name:'modelo', label:'Modelo', col:'iv-col-4'},
        {name:'serie', label:'Serie', col:'iv-col-4'},
        {name:'notas', label:'Notas', col:'iv-col-12'}
      ]
    },
    red: {
      label_sg: 'Datos de la red', label_pl: 'Datos de la red',
      columns: ['ID','IP pública','Transmisión en línea','Bajada / Subida','Acciones'],
      listRender: r => ([
        r.id,
        esc(r.ip_publica || ''),
        esc(r.transmision_online || ''),
        esc([r.bajada_txt||'', r.subida_txt||''].filter(Boolean).join(' / '))
      ]),
      fields: [
        {name:'ip_publica', label:'IP pública', col:'iv-col-3'},
        {name:'transmision_online', label:'Transmisión en línea', col:'iv-col-4', ph:'host:puerto'},
        {name:'bajada_txt', label:'Bajada', col:'iv-col-2', ph:'p.ej. 4 Mbps'},
        {name:'subida_txt', label:'Subida', col:'iv-col-2', ph:'p.ej. 4 Mbps'},
        {name:'notas', label:'Notas', col:'iv-col-12'}
      ]
    },
    tx: {
      label_sg: 'Acceso de transmisión', label_pl: 'Datos de acceso a la transmisión',
      columns: ['ID','URL','Usuario','Acciones'],
      listRender: r => ([
        r.id,
        esc(r.acceso_url || ''),
        esc(r.usuario || '')
      ]),
      fields: [
        {name:'acceso_url', label:'URL de acceso', col:'iv-col-6', ph:'http://host:puerto'},
        {name:'usuario', label:'Usuario', col:'iv-col-3'},
        {name:'clave', label:'Clave', col:'iv-col-3'},
        {name:'notas', label:'Notas', col:'iv-col-12'}
      ]
    }
  };

  // ---------- Estado ----------
  const ST = {
    empresaId: 0,
    tipo: 'pc',
    editId: 0,
    q: '',
    estado: '',
    page: 1,
    perPage: 10,
    rows: []
  };

  // ---------- Refs ----------
  const selEmp = () => $('#iv-empresa');
  const ultimo = () => $('#iv-ultima');
  const btnPdf = () => $('#iv-btn-pdf');
  const tabsEl = () => $('#iv-tabs');

  const form = () => $('#iv-form');
  const formTitle = () => $('#iv-form-title');
  const btnSave = () => $('#iv-save');
  const btnCancel = () => $('#iv-cancel');
  const okEl = () => $('#iv-ok');
  const errEl = () => $('#iv-err');

  const qEl = () => $('#iv-q');
  const estEl = () => $('#iv-estado');
  const perEl = () => $('#iv-perpage');

  const thead = () => $('#iv-thead');
  const tbody = () => $('#iv-tbody');
  const pager = () => $('#iv-pager');
  const listErr = () => $('#iv-list-err');

  // ---------- Render Form ----------
  function renderForm() {
    const S = SPECS[ST.tipo];
    const vals = ST.editId ? (ST.rows.find(r => r.id === ST.editId) || {}) : {};
    formTitle().textContent = ST.editId ? `Editar ${S.label_sg}` : `Crear ${S.label_sg}`;
    btnSave().textContent   = ST.editId ? 'Guardar cambios' : `Crear ${S.label_sg}`;

    const parts = ['<div class="iv-form-grid">'];
    for (const f of S.fields) {
      parts.push(`
        <div class="${f.col}">
          <label>${esc(f.label)}</label>
          <input class="form-control" name="${esc(f.name)}" placeholder="${esc(f.ph||'')}" value="${esc(vals[f.name] ?? '')}">
        </div>
      `);
    }
    parts.push('</div>');
    form().innerHTML = parts.join('');
  }

  // ---------- Render Tabla ----------
  function renderTable(rows, total) {
    const S = SPECS[ST.tipo];
    // Head
    thead().innerHTML = S.columns.map(c => `<th>${esc(c)}</th>`).join('');

    // Body
    if (!rows.length) {
      tbody().innerHTML = `<tr><td colspan="${S.columns.length}" class="text-center text-muted">Sin registros.</td></tr>`;
    } else {
      tbody().innerHTML = rows.map(r => {
        const baseCells = S.listRender(r).map(td => `<td>${td}</td>`).join('');
        const acc = `
          <td class="actions-cell">
            <button class="btn btn-sm btn-primary iv-edit" data-id="${r.id}">Editar</button>
            <button class="btn btn-sm btn-danger iv-del" data-id="${r.id}">Eliminar</button>
            <button class="btn btn-sm ${r.activo ? 'btn-warning' : 'btn-success'} iv-act" data-id="${r.id}" data-state="${r.activo?1:0}">
              ${r.activo ? 'Desactivar' : 'Activar'}
            </button>
          </td>`;
        return `<tr data-id="${r.id}">${baseCells}${acc}</tr>`;
      }).join('');
    }

    // Pager
    const pages = Math.max(1, Math.ceil(total / ST.perPage));
    ST.page = Math.min(ST.page, pages);
    const cur = ST.page;

    const parts = [];
    const add = (p, label, cls='') => parts.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`);
    add(cur-1, '«', cur<=1?'disabled':'');
    let start = Math.max(1, cur-2), end = Math.min(pages, start+4); start = Math.max(1, end-4);
    for (let p=start; p<=end; p++) add(p, p, p===cur?'active':'');
    add(cur+1, '»', cur>=pages?'disabled':'');
    pager().innerHTML = parts.join('');
  }

  // ---------- Data ----------
  async function loadEmpresas() {
    const r = await j(`${apiUrl}?action=empresas`);
    const opts = [`<option value="">Selecciona una empresa…</option>`]
      .concat((r.data||[]).map(e => `<option value="${e.id}">${esc(e.nombre)}</option>`));
    selEmp().innerHTML = opts.join('');
    if (ST.empresaId) selEmp().value = String(ST.empresaId);
  }
  async function loadUltima() {
    if (!ST.empresaId) { ultimo().textContent = '—'; return; }
    try {
      const r = await j(`${apiUrl}?action=ultima&empresa_id=${ST.empresaId}`);
      ultimo().textContent = r.ultima || '—';
    } catch { ultimo().textContent = '—'; }
  }
  async function loadList() {
    hide(listErr());
    if (!ST.empresaId) { thead().innerHTML=''; tbody().innerHTML=''; pager().innerHTML=''; return; }
    const qs = new URLSearchParams({
      action:'list', tipo:ST.tipo, empresa_id:ST.empresaId,
      q:ST.q, estado:ST.estado, page:ST.page, per_page:ST.perPage
    });
    try {
      const r = await j(`${apiUrl}?${qs.toString()}`);
      ST.rows = r.data || [];
      renderTable(ST.rows, r.total || 0);
    } catch (err) {
      show(listErr(), err.message || 'Error al listar');
    }
  }

  // ---------- Reset ----------
  function resetForm() { ST.editId=0; hide(errEl()); hide(okEl()); renderForm(); }
  function resetFilters() {
    ST.q=''; ST.estado=''; ST.page=1; ST.perPage=10;
    qEl().value=''; estEl().value=''; perEl().value='10';
  }

  // ---------- Eventos ----------
  // Empresa
  slot.addEventListener('change', async (e) => {
    if (e.target === selEmp()) {
      ST.empresaId = parseInt(selEmp().value,10) || 0;
      btnPdf().disabled = !ST.empresaId;
      await loadUltima();
      resetForm();
      resetFilters();
      await loadList();
    }
  });

  // ==== PDF (SIN NAVEGAR): pide el HTML a la MISMA API y abre diálogo de imprimir en un iframe oculto ====
  async function fetchReportHtml(empresaId) {
    const url = `${apiUrl}?action=report&empresa_id=${encodeURIComponent(empresaId)}`;
    const resp = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'text/html' }
    });
    if (!resp.ok) {
      const txt = await resp.text().catch(()=> '');
      throw new Error(txt || `HTTP ${resp.status}`);
    }
    const ct = resp.headers.get('content-type') || '';
    if (!ct.includes('text/html')) {
      throw new Error('Respuesta no válida del reporte.');
    }
    return resp.text();
  }
  function printHtmlInIframe(html) {
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.setAttribute('sandbox', 'allow-modals allow-same-origin allow-scripts');
    document.body.appendChild(iframe);
    iframe.srcdoc = html;
    iframe.onload = () => {
      try { iframe.contentWindow?.focus(); iframe.contentWindow?.print(); }
      finally { setTimeout(()=>iframe.remove(), 1500); }
    };
  }
  slot.addEventListener('click', async (e) => {
    if (e.target === btnPdf()) {
      if (!ST.empresaId) return;
      const old = btnPdf().innerHTML;
      btnPdf().disabled = true;
      btnPdf().innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando…';
      try {
        const html = await fetchReportHtml(ST.empresaId);
        printHtmlInIframe(await html);
      } catch (err) {
        alert(err?.message || 'No se pudo generar el reporte.');
      } finally {
        btnPdf().disabled = false;
        btnPdf().innerHTML = old;
      }
    }
  });

  // Tabs
  slot.addEventListener('click', async (e) => {
    const tab = e.target?.closest('.iv-tab'); if (!tab || !tabsEl().contains(tab)) return;
    tabsEl().querySelectorAll('.iv-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    ST.tipo = tab.dataset.tipo;
    ST.page = 1;
    resetForm();
    await loadList();
  });

  // Filtros
  slot.addEventListener('input', debounce(async (e)=>{
    if (e.target === qEl()) { ST.q = qEl().value; ST.page=1; await loadList(); }
  }, 300));
  slot.addEventListener('change', async (e)=>{
    if (e.target === estEl()) { ST.estado = estEl().value; ST.page=1; await loadList(); }
    if (e.target === perEl()) { ST.perPage = parseInt(perEl().value,10)||10; ST.page=1; await loadList(); }
  });

  // Paginación
  slot.addEventListener('click', async (e)=>{
    const a = e.target?.closest('#iv-pager a[data-page]'); if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10);
    if (p>0) { ST.page = p; await loadList(); }
  });

  // Guardar (crear / update)
  slot.addEventListener('click', async (e)=>{
    if (e.target !== btnSave()) return;
    hide(errEl()); hide(okEl());
    if (!ST.empresaId && !ST.editId) { show(errEl(), 'Selecciona una empresa.'); return; }

    const S = SPECS[ST.tipo];
    const fd = new FormData();
    for (const inp of form().querySelectorAll('input[name]')) {
      fd.append(inp.name, inp.value.trim());
    }
    fd.append('tipo', ST.tipo);

    if (ST.editId) {
      fd.append('action','update');
      fd.append('id', String(ST.editId));
    } else {
      fd.append('action','create');
      fd.append('empresa_id', String(ST.empresaId));
    }

    btnSave().disabled = true;
    const old = btnSave().textContent;
    btnSave().textContent = ST.editId ? 'Guardando…' : `Creando ${S.label_sg}…`;

    try {
      const r = await fetch(apiUrl, { method:'POST', body:fd, credentials:'same-origin' });
      const jres = await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
      if (!r.ok || !jres.ok) throw new Error(jres.msg || `HTTP ${r.status}`);

      show(okEl(), ST.editId ? 'Registro actualizado.' : `${S.label_sg} creado.`);
      ST.page = 1;
      resetForm();
      await loadUltima();
      await loadList();
    } catch (err) {
      show(errEl(), err.message || 'Error al guardar.');
    } finally {
      btnSave().disabled = false;
      btnSave().textContent = old;
    }
  });

  // Cancelar
  slot.addEventListener('click', (e)=>{ if (e.target === btnCancel()) resetForm(); });

  // Editar
  slot.addEventListener('click', (e)=>{
    const btn = e.target?.closest('.iv-edit'); if (!btn) return;
    const id = parseInt(btn.dataset.id,10); if (!id) return;
    ST.editId = id; renderForm();
    const row = ST.rows.find(r => r.id === id) || {};
    for (const inp of form().querySelectorAll('input[name]')) { inp.value = row[inp.name] ?? ''; }
    btnSave().textContent = 'Guardar cambios';
  });

  // Eliminar
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.iv-del'); if (!btn) return;
    const id = parseInt(btn.dataset.id,10); if (!id) return;
    if (!confirm('¿Eliminar este registro?')) return;

    const fd = new FormData();
    fd.append('action','delete');
    fd.append('tipo', ST.tipo);
    fd.append('id', String(id));

    try {
      const r = await fetch(apiUrl, { method:'POST', body:fd, credentials:'same-origin' });
      const jres = await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
      if (!r.ok || !jres.ok) throw new Error(jres.msg || `HTTP ${r.status}`);

      show(okEl(), 'Registro eliminado.');
      ST.page = 1;
      await loadUltima();
      await loadList();
    } catch (err) {
      show(errEl(), err.message || 'Error al eliminar.');
    }
  });

  // Activar / Desactivar
  slot.addEventListener('click', async (e)=>{
    const btn = e.target?.closest('.iv-act'); if (!btn) return;
    const id = parseInt(btn.dataset.id,10); if (!id) return;
    const cur = parseInt(btn.dataset.state,10) === 1;
    const nuevo = cur ? 0 : 1;
    if (!confirm(`${nuevo ? 'Activar' : 'Desactivar'} este registro?`)) return;

    const fd = new FormData();
    fd.append('action','set_activo');
    fd.append('tipo', ST.tipo);
    fd.append('id', String(id));
    fd.append('activo', String(nuevo));

    btn.disabled = true;
    try {
      const r = await fetch(apiUrl, { method:'POST', body:fd, credentials:'same-origin' });
      const jres = await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
      if (!r.ok || !jres.ok) throw new Error(jres.msg || `HTTP ${r.status}`);

      show(okEl(), `Registro ${nuevo ? 'activado' : 'desactivado'}.`);
      await loadUltima();
      await loadList();
    } catch (err) {
      show(errEl(), err.message || 'Error al actualizar estado.');
    } finally {
      btn.disabled = false;
    }
  });

  // ---------- Refresh al reabrir modal ----------
  slot.__ivRefresh = async function() {
    // Estado base
    ST.empresaId = 0; ST.tipo='pc'; ST.editId=0; ST.q=''; ST.estado=''; ST.page=1; ST.perPage=10; ST.rows=[];
    // UI
    tabsEl().querySelectorAll('.iv-tab').forEach(t => t.classList.remove('active'));
    tabsEl().querySelector('.iv-tab[data-tipo="pc"]')?.classList.add('active');
    hide(okEl()); hide(errEl()); hide(listErr());
    qEl().value=''; estEl().value=''; perEl().value='10';
    btnPdf().disabled = true; ultimo().textContent='—';
    renderForm();
    await loadEmpresas();
    thead().innerHTML=''; tbody().innerHTML=''; pager().innerHTML='';
  };

  // Primera carga
  slot.__ivRefresh();
}
