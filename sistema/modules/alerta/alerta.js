(() => {
  const CFG = window.AL_CFG || {};
  const API = CFG.api;

  const $  = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => [...root.querySelectorAll(sel)];
  const esc = s => (s ?? '').toString().replace(/[&<>\"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));

  // DOM
  const cards = $('#al-cards');
  const qInput = $('#al-q');
  const qClear = $('#al-clear');
  const sEstado = $('#al-estado');
  const sTipo   = $('#al-tipo');
  const tbody = $('#al-tbody');
  const pager = $('#al-pager');
  const alertBox = $('#al-alert');
  const btnNew = $('#btn-new');

  const drawer = $('#al-drawer');
  const mask = $('#al-mask');
  const dClose = $('#drawer-close');
  const dCancel = $('#btn-cancel');
  const dTitle = $('#drawer-title');
  const f = $('#al-form');
  const fId = $('#f-id');
  const fTitulo = $('#f-titulo');
  const fCategoria = $('#f-categoria');
  const fDescripcion = $('#f-descripcion');
  const fTipo = $('#f-tipo');
  const wrapIntervalo = $('#wrap-intervalo');
  const fIntervalo = $('#f-intervalo');
  const fFecha = $('#f-fecha');
  const fAnt = $('#f-anticipacion');
  const fActivo = $('#f-activo');
  const fErr = $('#form-err');
  const fOk  = $('#form-ok');

  const state = { q:'', estado:'', tipo:'', page:1, per:10, rows:[], total:0 };

  function showDrawer(open=true){
    drawer.classList.toggle('open', open);
    mask.classList.toggle('show', open);
    if (!open) { fErr.classList.add('d-none'); fOk.classList.add('d-none'); }
  }
  dClose.addEventListener('click', ()=>showDrawer(false));
  dCancel.addEventListener('click', ()=>showDrawer(false));
  mask.addEventListener('click', ()=>showDrawer(false));

  function sinceSecondsToStr(sec) {
    if (sec == null) return '—';
    let s = Math.max(0, Math.floor(sec));
    const d = Math.floor(s / 86400); s -= d*86400;
    const h = Math.floor(s / 3600); s -= h*3600;
    const m = Math.floor(s / 60); s -= m*60;
    const pad = n => String(n).padStart(2,'0');
    if (d>0) return `${d}d ${pad(h)}:${pad(m)}:${pad(s)}`;
    return `${pad(h)}:${pad(m)}:${pad(s)}`;
  }

  let countdownTimer = null;
  function startCountdowns(){
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(()=>{
      $$('.al-countdown[data-seconds]').forEach(el=>{
        let sec = parseInt(el.dataset.seconds,10);
        const nextTs = parseInt(el.dataset.nextTs||'0',10);
        const nowTs = Math.floor(Date.now()/1000);
        sec = Math.max(0, nextTs - nowTs);
        el.dataset.seconds = String(sec);
        el.textContent = sinceSecondsToStr(sec);

        // colores por estado
        const warnFrom = parseInt(el.dataset.warnFrom||'0',10);
        const isOverdue = nowTs > nextTs && nextTs>0;
        el.classList.remove('badge-soft-green','badge-soft-amber','badge-soft-red');
        if (isOverdue) {
          el.classList.add('badge-soft-red');
          el.title = 'Vencida';
        } else if (nowTs >= warnFrom && nextTs>0) {
          el.classList.add('badge-soft-amber');
          el.title = 'Dentro de ventana de alerta';
        } else {
          el.classList.add('badge-soft-green');
          el.title = 'A tiempo';
        }
      });
    }, 1000);
  }

  async function j(url, opts) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  async function loadCards(){
    // tarjetas simples: totales y por estado (activos/inactivos)
    // Reutilizamos list sin filtros para contar rápido (servidor paginado => traer todos y contar sería costoso).
    // Haremos 2 llamadas con estado=1 y estado=0 (solo totales).
    const [a,b] = await Promise.all([
      j(`${API}?action=list&estado=1&page=1&per=1`),
      j(`${API}?action=list&estado=0&page=1&per=1`)
    ]);
    const activos = a.total||0, inactivos = b.total||0;
    const tot = activos + inactivos;

    cards.innerHTML = `
      <div class="col-6 col-sm-4 col-lg-3">
        <div class="al-card"><div class="h">Total</div><div class="n">${tot}</div><div class="mut">Alertas</div></div>
      </div>
      <div class="col-6 col-sm-4 col-lg-3">
        <div class="al-card"><div class="h">Activas</div><div class="n">${activos}</div><div class="mut">En seguimiento</div></div>
      </div>
      <div class="col-6 col-sm-4 col-lg-3">
        <div class="al-card"><div class="h">Inactivas</div><div class="n">${inactivos}</div><div class="mut">Pausadas</div></div>
      </div>
    `;
  }

  async function loadList(){
    try{
      alertBox.classList.add('d-none');
      const qs = new URLSearchParams({
        action:'list',
        q: state.q, estado: state.estado, tipo: state.tipo,
        page: state.page, per: state.per
      });
      const d = await j(`${API}?${qs.toString()}`);
      state.rows = d.data || [];
      state.total = d.total || 0;
      paintRows();
      paintPager();
      await loadCards();
      startCountdowns();
    }catch(err){
      alertBox.textContent = err.message || 'Error';
      alertBox.classList.remove('d-none');
    }
  }

  function paintRows(){
    if (!state.rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted">Sin registros.</td></tr>`;
      return;
    }
    tbody.innerHTML = state.rows.map(r => {
      const tipoMap = {ONCE:'Una sola vez', MONTHLY:'Mensual', YEARLY:'Anual', INTERVAL:`Cada ${r.intervalo_dias||'?'} días`};
      const next = r._next_iso ? esc(r._next_iso) : '—';
      const sec = r._in_seconds ?? 0;
      const warn = r._warn_from_ts ?? 0;
      const nextTs = r._next_ts ?? 0;
      const badgeTipo = (()=>{
        if (r.tipo==='ONCE') return 'badge-soft-blue';
        if (r.tipo==='MONTHLY') return 'badge-soft-gray';
        if (r.tipo==='YEARLY') return 'badge-soft-gray';
        return 'badge-soft-gray';
      })();
      const activoBadge = +r.activo ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>';

      return `<tr>
        <td>
          <div class="fw-bold">${esc(r.titulo||'—')}</div>
          <div class="text-muted small">${esc(r.descripcion||'')}</div>
        </td>
        <td>${esc(r.categoria||'—')}</td>
        <td><span class="badge badge-pill ${badgeTipo}">${esc(tipoMap[r.tipo]||r.tipo)}</span></td>
        <td>
          <div>${next}</div>
          <div class="al-countdown badge badge-pill mt-1" data-seconds="${sec}" data-next-ts="${nextTs}" data-warn-from="${warn}">${sinceSecondsToStr(sec)}</div>
        </td>
        <td>${parseInt(r.anticipacion_dias||0)} día(s)</td>
        <td>${activoBadge}</td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-primary" data-act="edit" data-id="${r.id}"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm ${+r.activo?'btn-warning':'btn-success'}" data-act="toggle" data-id="${r.id}" data-new="${+r.activo?0:1}">
            ${+r.activo?'Inactivar':'Activar'}
          </button>
        </td>
      </tr>`;
    }).join('');
  }

  function paintPager(){
    const pages = Math.max(1, Math.ceil(state.total / state.per));
    const cur   = Math.min(state.page, pages);
    const items = [];
    const add = (p, label, cls='') => items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`);
    add(cur-1,'«',cur<=1?'disabled':'');
    let s = Math.max(1, cur-2), e = Math.min(pages, s+4); s = Math.max(1, e-4);
    for (let p=s; p<=e; p++) add(p, p, p===cur?'active':'');
    add(cur+1,'»',cur>=pages?'disabled':'');
    pager.innerHTML = items.join('');
  }

  pager.addEventListener('click', (e)=>{
    const a = e.target.closest('a[data-page]'); if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10); if (p>0){ state.page=p; loadList(); }
  });

  // Toolbar
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); } };
  qInput.addEventListener('input', debounce(()=>{ state.q=qInput.value.trim(); state.page=1; loadList(); }, 300));
  qClear.addEventListener('click', ()=>{ qInput.value=''; state.q=''; state.page=1; loadList(); });
  sEstado.addEventListener('change', ()=>{ state.estado=sEstado.value; state.page=1; loadList(); });
  sTipo.addEventListener('change', ()=>{ state.tipo=sTipo.value; state.page=1; loadList(); });

  // Nuevo / Editar
  btnNew.addEventListener('click', ()=> openForm(0));
  tbody.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-act]'); if (!btn) return;
    const act = btn.dataset.act; const id = parseInt(btn.dataset.id,10)||0;
    if (act==='edit') openForm(id);
    else if (act==='toggle'){
      const nuevo = parseInt(btn.dataset.new,10)||0;
      if (!confirm(`¿${nuevo?'Activar':'Inactivar'} esta alerta?`)) return;
      const fd = new FormData(); fd.append('action','toggle'); fd.append('id',id); fd.append('activo', String(nuevo));
      try { await j(API, {method:'POST', body:fd}); loadList(); } catch(err){ alert(err.message||'Error'); }
    }
  });

  function syncIntervaloVisibility(){
    wrapIntervalo.style.display = (fTipo.value === 'INTERVAL') ? '' : 'none';
    if (fTipo.value !== 'INTERVAL') fIntervalo.value = '';
  }
  fTipo.addEventListener('change', syncIntervaloVisibility);

  async function openForm(id){
    f.reset?.();
    fErr.classList.add('d-none'); fOk.classList.add('d-none');
    fId.value = id ? String(id) : '';
    dTitle.textContent = id ? 'Editar alerta' : 'Nueva alerta';
    if (id>0){
      try{
        const r = await j(`${API}?action=get&id=${id}`);
        const x = r.data || {};
        fTitulo.value = x.titulo||'';
        fCategoria.value = x.categoria||'';
        fDescripcion.value = x.descripcion||'';
        fTipo.value = x.tipo||'ONCE';
        fIntervalo.value = x.intervalo_dias||'';
        fFecha.value = x.fecha_base ? x.fecha_base.replace(' ','T').slice(0,16) : '';
        fAnt.value = x.anticipacion_dias||0;
        fActivo.checked = +x.activo ? true : false;
      }catch(err){ alert(err.message||'No se pudo cargar'); return; }
    } else {
      // defaults
      const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      fFecha.value = now.toISOString().slice(0,16);
      fTipo.value = 'ONCE';
      fIntervalo.value = '';
      fAnt.value = 0;
      fActivo.checked = true;
    }
    syncIntervaloVisibility();
    showDrawer(true);
    setTimeout(()=> fTitulo.focus(), 50);
  }

  f.addEventListener('submit', async (e)=>{
    e.preventDefault();
    fErr.classList.add('d-none'); fOk.classList.add('d-none');

    const fd = new FormData(f);
    fd.append('action','save');
    if (!$('#f-activo').checked) fd.set('activo',''); // por claridad

    try{
      await j(API, {method:'POST', body:fd});
      fOk.textContent = 'Guardado correctamente.'; fOk.classList.remove('d-none');
      await loadList();
      setTimeout(()=> showDrawer(false), 400);
    }catch(err){
      fErr.textContent = err.message || 'Error al guardar';
      fErr.classList.remove('d-none');
    }
  });

  // Boot
  (async function boot(){ await loadCards(); await loadList(); })();
})();
