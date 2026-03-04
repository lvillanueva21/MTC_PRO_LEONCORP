// modules/consola/comprobantes/gestion.js
export function init(slot, apiUrl) {
  if (slot.__comprobantesBound) {
    slot.__comprobantesRefresh?.();
    return;
  }
  slot.__comprobantesBound = true;

  const $ = (sel) => slot.querySelector(sel);
  const esc = (s) => (s ?? '').toString().replace(/[&<>"']/g, (m) => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
  const hide = (el) => {
    if (!el) return;
    el.classList.add('d-none');
    el.classList.remove('show');
  };
  const show = (el, msg = '') => {
    if (!el) return;
    const holder = el.querySelector?.('.msg');
    if (holder) holder.textContent = msg;
    else el.textContent = msg;
    el.classList.remove('d-none');
    el.classList.add('show');
  };
  const debounce = (fn, ms = 300) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };
  const chip = (kind, text) => `<span class="cmp-chip ${kind}">${esc(text)}</span>`;

  async function j(url, opts = {}) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(() => ({ ok:false, msg:'Respuesta invalida del servidor.' }));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  const FORM = {
    editId: 0,
    usedCount: 0,
    canChangeSeries: true
  };
  const COMPANY = {
    q: '',
    status: 'all',
    page: 1,
    per_page: 6,
    total: 0,
    rows: []
  };
  const SERIES = {
    empresa: 0,
    activo: 'all',
    q: '',
    page: 1,
    per_page: 8,
    total: 0,
    rows: []
  };

  function pagerHtml(total, perPage, current) {
    const pages = Math.max(1, Math.ceil((total || 0) / perPage));
    const cur = Math.min(Math.max(1, current), pages);
    const items = [];
    const add = (p, label, cls = '') => {
      items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`);
    };
    add(cur - 1, '«', cur <= 1 ? 'disabled' : '');
    let start = Math.max(1, cur - 2);
    let end = Math.min(pages, start + 4);
    start = Math.max(1, end - 4);
    for (let p = start; p <= end; p += 1) add(p, p, p === cur ? 'active' : '');
    add(cur + 1, '»', cur >= pages ? 'disabled' : '');
    return { html: items.join(''), cur };
  }

  function setFormDefaults() {
    FORM.editId = 0;
    FORM.usedCount = 0;
    FORM.canChangeSeries = true;

    $('#cmp-form-title').textContent = '1. Crear ticket POS por empresa';
    $('#cmp-id').value = '0';
    $('#cmp-empresa').value = '';
    $('#cmp-empresa').disabled = false;
    $('#cmp-serie').value = '';
    $('#cmp-serie').disabled = false;
    $('#cmp-next').value = '1';
    $('#cmp-activo').value = '1';
    $('#cmp-last-ticket').textContent = 'Aun no registra ventas';
    $('#cmp-usage-note').textContent = 'Si esta serie ya tiene ventas emitidas, no se permitira cambiar su codigo ni moverla a otra empresa.';
    $('#cmp-save').textContent = 'Crear ticket';
    $('#cmp-cancel').classList.add('d-none');
  }

  function fillForm(row) {
    FORM.editId = Number(row.id || 0);
    FORM.usedCount = Number(row.used_count || 0);
    FORM.canChangeSeries = !!row.can_change_series;

    $('#cmp-form-title').textContent = `1. Editar ticket POS #${row.id}`;
    $('#cmp-id').value = String(row.id || 0);
    $('#cmp-empresa').value = String(row.id_empresa || '');
    $('#cmp-empresa').disabled = true;
    $('#cmp-serie').value = row.serie || '';
    $('#cmp-serie').disabled = !FORM.canChangeSeries;
    $('#cmp-next').value = String(row.siguiente_numero || 1);
    $('#cmp-activo').value = String(Number(row.activo || 0));
    $('#cmp-last-ticket').textContent = row.last_ticket || 'Aun no registra ventas';

    if (FORM.usedCount > 0) {
      $('#cmp-usage-note').textContent = `Esta serie ya fue usada en ${FORM.usedCount} venta(s). No se puede cambiar el codigo de serie y el siguiente numero debe ser mayor al ultimo emitido.`;
    } else {
      $('#cmp-usage-note').textContent = 'Esta serie aun no registra ventas. Puedes ajustar su codigo y correlativo con cuidado.';
    }

    $('#cmp-save').textContent = 'Guardar cambios';
    $('#cmp-cancel').classList.remove('d-none');
    $('#cmp-next').focus();
  }

  async function loadEmpresasCombo() {
    const r = await j(`${apiUrl}?action=empresas`);
    const opts = (r.data || []).map((e) => `<option value="${e.id}">${esc(e.nombre)} · ${esc(e.ruc)}</option>`).join('');
    $('#cmp-empresa').innerHTML = `<option value="">Selecciona una empresa...</option>${opts}`;
    $('#cmp-list-empresa').innerHTML = `<option value="0">Todas</option>${opts}`;
  }

  async function loadSummary() {
    const r = await j(`${apiUrl}?action=summary`);
    const s = r.data || {};
    $('#cmp-stat-total-empresas').textContent = String(s.total_empresas || 0);
    $('#cmp-stat-con-ticket').textContent = String(s.empresas_con_activa || 0);
    $('#cmp-stat-sin-ticket').textContent = String(s.empresas_sin_series || 0);
    $('#cmp-stat-series-activas').textContent = String(s.series_activas || 0);
  }

  function renderCompanyRows() {
    const tbody = $('#cmp-company-tbody');
    const rows = COMPANY.rows || [];
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="6" class="cmp-empty">No hay empresas para los filtros seleccionados.</td></tr>`;
    } else {
      tbody.innerHTML = rows.map((r) => {
        const stateText = Number(r.total_series || 0) === 0
          ? chip('warn', 'Sin tickets')
          : (Number(r.active_series || 0) > 0 ? chip('ok', 'Operativa') : chip('soft', 'Solo inactivas'));
        const activeText = Number(r.active_series || 0) > 0
          ? `<div>${chip('ok', r.active_series_label || 'Activa')}</div><div class="cmp-table-meta mt-1">${Number(r.active_series || 0)} activa(s) · ${Number(r.total_series || 0)} total</div>`
          : `<div class="cmp-table-meta">No tiene serie activa</div>`;
        const primaryAction = Number(r.active_series_id || 0) > 0
          ? `<button class="btn btn-sm btn-primary cmp-company-edit" data-id="${r.active_series_id}">Editar activa</button>`
          : `<button class="btn btn-sm btn-success cmp-company-create" data-empresa="${r.id}">Crear ticket</button>`;
        const secondaryAction = Number(r.latest_series_id || 0) > 0
          ? `<button class="btn btn-sm btn-outline-secondary cmp-series-edit" data-id="${r.latest_series_id}">Ver ultima</button>`
          : '';

        return `
          <tr>
            <td class="text-muted">${r.id}</td>
            <td>
              <div class="font-weight-bold">${esc(r.nombre || '')}</div>
              <div class="cmp-table-meta">${esc(r.razon_social || '')}</div>
            </td>
            <td>${esc(r.ruc || '')}</td>
            <td>${activeText}</td>
            <td>${stateText}</td>
            <td><div class="cmp-row-actions">${primaryAction}${secondaryAction}</div></td>
          </tr>
        `;
      }).join('');
    }

    const pager = pagerHtml(COMPANY.total, COMPANY.per_page, COMPANY.page);
    COMPANY.page = pager.cur;
    $('#cmp-company-pager').innerHTML = pager.html;
  }

  async function loadCompanyStatus() {
    hide($('#cmp-company-alert'));
    const qs = new URLSearchParams({
      action: 'company_status_list',
      q: COMPANY.q,
      status: COMPANY.status,
      page: String(COMPANY.page),
      per_page: String(COMPANY.per_page)
    });

    try {
      const r = await j(`${apiUrl}?${qs.toString()}`);
      COMPANY.rows = r.data || [];
      COMPANY.total = Number(r.total || 0);
      renderCompanyRows();
    } catch (err) {
      show($('#cmp-company-alert'), err.message || 'No se pudo listar el estado de empresas.');
    }
  }

  function renderSeriesRows() {
    const tbody = $('#cmp-series-tbody');
    const rows = SERIES.rows || [];
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="cmp-empty">No hay tickets registrados para esos filtros.</td></tr>`;
    } else {
      tbody.innerHTML = rows.map((r) => {
        const state = Number(r.activo || 0) === 1 ? chip('ok', 'Activa') : chip('neutral', 'Inactiva');
        const lastTicket = r.last_ticket
          ? `<div>${esc(r.last_ticket)}</div><div class="cmp-table-meta">${Number(r.used_count || 0)} venta(s)</div>`
          : `<div class="cmp-table-meta">Sin uso todavia</div>`;
        const toggleText = Number(r.activo || 0) === 1 ? 'Desactivar' : 'Activar';
        const toggleClass = Number(r.activo || 0) === 1 ? 'btn-outline-danger' : 'btn-outline-success';

        return `
          <tr>
            <td class="text-muted">${r.id}</td>
            <td>
              <div class="font-weight-bold">${esc(r.empresa || '')}</div>
              <div class="cmp-table-meta">${esc(r.ruc || '')}</div>
            </td>
            <td>
              <div>${esc(r.serie || '')}</div>
              <div class="cmp-table-meta">${esc(r.tipo_comprobante || 'TICKET')}</div>
            </td>
            <td>${Number(r.siguiente_numero || 0)}</td>
            <td>${lastTicket}</td>
            <td>${state}</td>
            <td>
              <div class="cmp-row-actions">
                <button class="btn btn-sm btn-primary cmp-series-edit" data-id="${r.id}">Editar</button>
                <button class="btn btn-sm ${toggleClass} cmp-series-toggle" data-id="${r.id}" data-active="${Number(r.activo || 0)}">${toggleText}</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    const pager = pagerHtml(SERIES.total, SERIES.per_page, SERIES.page);
    SERIES.page = pager.cur;
    $('#cmp-series-pager').innerHTML = pager.html;
  }

  async function loadSeriesList() {
    hide($('#cmp-series-alert'));
    const qs = new URLSearchParams({
      action: 'list',
      empresa: String(SERIES.empresa || 0),
      activo: SERIES.activo,
      q: SERIES.q,
      page: String(SERIES.page),
      per_page: String(SERIES.per_page)
    });

    try {
      const r = await j(`${apiUrl}?${qs.toString()}`);
      SERIES.rows = r.data || [];
      SERIES.total = Number(r.total || 0);
      renderSeriesRows();
    } catch (err) {
      show($('#cmp-series-alert'), err.message || 'No se pudo listar los tickets.');
    }
  }

  async function openSeries(id) {
    const r = await j(`${apiUrl}?action=get&id=${id}`);
    fillForm(r.data || {});
    hide($('#cmp-err'));
    hide($('#cmp-ok'));
  }

  function validateForm() {
    const empresa = Number($('#cmp-empresa').value || 0);
    const serie = ($('#cmp-serie').value || '').trim().toUpperCase();
    const next = Number($('#cmp-next').value || 0);
    const activo = Number($('#cmp-activo').value || 0);

    if (!empresa) throw new Error('Selecciona una empresa.');
    if (!/^[A-Z0-9][A-Z0-9-]{0,9}$/.test(serie)) {
      throw new Error('La serie solo admite letras, numeros y guion, con maximo 10 caracteres.');
    }
    if (!Number.isInteger(next) || next < 1) {
      throw new Error('El siguiente numero debe ser un entero mayor o igual a 1.');
    }
    if (![0, 1].includes(activo)) {
      throw new Error('Estado invalido.');
    }

    return { empresa, serie, next, activo };
  }

  async function saveForm() {
    hide($('#cmp-err'));
    hide($('#cmp-ok'));

    const data = validateForm();
    const fd = new FormData();
    fd.append('action', FORM.editId > 0 ? 'update' : 'create');
    fd.append('id', String(FORM.editId || 0));
    fd.append('id_empresa', String(data.empresa));
    fd.append('serie', data.serie);
    fd.append('siguiente_numero', String(data.next));
    fd.append('activo', String(data.activo));

    const btn = $('#cmp-save');
    const oldText = btn.textContent;
    btn.disabled = true;
    btn.textContent = FORM.editId > 0 ? 'Guardando...' : 'Creando...';

    try {
      const r = await j(apiUrl, { method:'POST', body: fd });
      show($('#cmp-ok'), r.msg || (FORM.editId > 0 ? 'Ticket actualizado.' : 'Ticket creado.'));
      setFormDefaults();
      await Promise.all([loadSummary(), loadCompanyStatus(), loadSeriesList()]);
    } catch (err) {
      show($('#cmp-err'), err.message || 'No se pudo guardar el ticket.');
    } finally {
      btn.disabled = false;
      btn.textContent = oldText;
    }
  }

  async function toggleSerie(id, active) {
    hide($('#cmp-err'));
    hide($('#cmp-ok'));
    const nextActive = active === 1 ? 0 : 1;
    const actionText = nextActive === 1 ? 'activar' : 'desactivar';
    if (!confirm(`¿Seguro que deseas ${actionText} este ticket?`)) return;

    const fd = new FormData();
    fd.append('action', 'toggle_active');
    fd.append('id', String(id));
    fd.append('activo', String(nextActive));

    try {
      const r = await j(apiUrl, { method:'POST', body: fd });
      show($('#cmp-ok'), r.msg || 'Estado actualizado.');
      await Promise.all([loadSummary(), loadCompanyStatus(), loadSeriesList()]);

      if (FORM.editId === Number(id)) {
        await openSeries(id);
      }
    } catch (err) {
      show($('#cmp-err'), err.message || 'No se pudo cambiar el estado del ticket.');
    }
  }

  slot.addEventListener('click', async (e) => {
    const okClose = e.target.closest('.cmp-ok-close');
    if (okClose) {
      hide($('#cmp-ok'));
      return;
    }

    const saveBtn = e.target.closest('#cmp-save');
    if (saveBtn) {
      try {
        await saveForm();
      } catch (err) {
        show($('#cmp-err'), err.message || 'No se pudo guardar el ticket.');
      }
      return;
    }

    const cancelBtn = e.target.closest('#cmp-cancel');
    if (cancelBtn) {
      setFormDefaults();
      hide($('#cmp-err'));
      hide($('#cmp-ok'));
      return;
    }

    const createBtn = e.target.closest('.cmp-company-create');
    if (createBtn) {
      setFormDefaults();
      $('#cmp-empresa').value = String(createBtn.dataset.empresa || '');
      $('#cmp-serie').focus();
      return;
    }

    const editCompanyBtn = e.target.closest('.cmp-company-edit');
    if (editCompanyBtn) {
      await openSeries(Number(editCompanyBtn.dataset.id || 0));
      return;
    }

    const editSerieBtn = e.target.closest('.cmp-series-edit');
    if (editSerieBtn) {
      await openSeries(Number(editSerieBtn.dataset.id || 0));
      return;
    }

    const toggleBtn = e.target.closest('.cmp-series-toggle');
    if (toggleBtn) {
      await toggleSerie(Number(toggleBtn.dataset.id || 0), Number(toggleBtn.dataset.active || 0));
      return;
    }
  });

  slot.addEventListener('input', debounce((e) => {
    if (e.target?.id === 'cmp-company-q') {
      COMPANY.q = e.target.value || '';
      COMPANY.page = 1;
      loadCompanyStatus();
      return;
    }

    if (e.target?.id === 'cmp-list-q') {
      SERIES.q = e.target.value || '';
      SERIES.page = 1;
      loadSeriesList();
      return;
    }

    if (e.target?.id === 'cmp-serie') {
      e.target.value = (e.target.value || '').toUpperCase().replace(/\s+/g, '');
    }
  }, 300));

  slot.addEventListener('change', (e) => {
    if (e.target?.id === 'cmp-company-status') {
      COMPANY.status = e.target.value || 'all';
      COMPANY.page = 1;
      loadCompanyStatus();
      return;
    }

    if (e.target?.id === 'cmp-list-empresa') {
      SERIES.empresa = Number(e.target.value || 0);
      SERIES.page = 1;
      loadSeriesList();
      return;
    }

    if (e.target?.id === 'cmp-list-activo') {
      SERIES.activo = e.target.value || 'all';
      SERIES.page = 1;
      loadSeriesList();
    }
  });

  slot.addEventListener('click', (e) => {
    const companyPageLink = e.target.closest('#cmp-company-pager a[data-page]');
    if (companyPageLink) {
      e.preventDefault();
      const li = companyPageLink.parentElement;
      if (li.classList.contains('disabled') || li.classList.contains('active')) return;
      COMPANY.page = Number(companyPageLink.dataset.page || 1);
      loadCompanyStatus();
      return;
    }

    const seriesPageLink = e.target.closest('#cmp-series-pager a[data-page]');
    if (seriesPageLink) {
      e.preventDefault();
      const li = seriesPageLink.parentElement;
      if (li.classList.contains('disabled') || li.classList.contains('active')) return;
      SERIES.page = Number(seriesPageLink.dataset.page || 1);
      loadSeriesList();
    }
  });

  slot.__comprobantesRefresh = async function () {
    hide($('#cmp-ok'));
    hide($('#cmp-err'));
    hide($('#cmp-company-alert'));
    hide($('#cmp-series-alert'));

    setFormDefaults();

    COMPANY.q = '';
    COMPANY.status = 'all';
    COMPANY.page = 1;
    $('#cmp-company-q').value = '';
    $('#cmp-company-status').value = 'all';

    SERIES.empresa = 0;
    SERIES.activo = 'all';
    SERIES.q = '';
    SERIES.page = 1;
    $('#cmp-list-q').value = '';

    await loadEmpresasCombo();
    $('#cmp-list-empresa').value = '0';
    $('#cmp-list-activo').value = 'all';

    await Promise.all([loadSummary(), loadCompanyStatus(), loadSeriesList()]);
  };

  slot.__comprobantesRefresh();
}
