(function () {
  const root = document.querySelector('#egApp');
  if (!root) return;

  const API = String(root.dataset.api || '');
  const qs = (s, c = document) => c.querySelector(s);
  const qsa = (s, c = document) => Array.from(c.querySelectorAll(s));

  const ORDER = ['EFECTIVO', 'YAPE', 'PLIN', 'TRANSFERENCIA'];
  const LABELS = {
    EFECTIVO: 'Efectivo',
    YAPE: 'Yape',
    PLIN: 'Plin',
    TRANSFERENCIA: 'Transferencia'
  };

  const state = {
    mode: 'NORMAL',
    cajas: {},
    asignaciones: {},
    payload: []
  };

  const esc = (s) => (s || '').toString().replace(/[&<>"']/g, function (m) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
  });

  const num = (v) => {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 0;
  };

  const round2 = (v) => Math.round((num(v) + Number.EPSILON) * 100) / 100;
  const money = (v) => 'S/ ' + round2(v).toFixed(2);

  const fmtDate = (x) => {
    if (!x) return '-';
    const d = new Date(String(x) + 'T00:00:00');
    if (isNaN(d)) return String(x);
    return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0') + '/' + d.getFullYear();
  };

  const canonKey = (raw) => {
    const n = String(raw || '').toUpperCase().trim();
    if (ORDER.indexOf(n) >= 0) return n;
    if (n.indexOf('EFECTIVO') >= 0 || n.indexOf('CASH') >= 0) return 'EFECTIVO';
    if (n.indexOf('YAPE') >= 0) return 'YAPE';
    if (n.indexOf('PLIN') >= 0) return 'PLIN';
    if (n.indexOf('TRANSFER') >= 0) return 'TRANSFERENCIA';
    return '';
  };

  const getMontoObjetivo = () => round2(num((qs('#egMonto') || {}).value));
  const isMulticaja = () => (qs('#egTipoEgresoModo') || {}).value === 'MULTICAJA';

  async function req(url, opt) {
    const r = await fetch(url, Object.assign({ credentials: 'same-origin' }, opt || {}));
    const txt = await r.text();
    let j = null;

    try {
      j = JSON.parse(txt);
    } catch (e) {
      j = null;
    }

    if (!r.ok || !j || j.ok !== true) {
      let msg = (j && j.error) ? j.error : '';
      if (!msg) {
        const plain = (txt || '').trim();
        msg = plain !== '' ? plain : ('Error HTTP ' + r.status);
      }
      const er = new Error(msg);
      er.payload = j;
      throw er;
    }
    return j;
  }

  function get(action, params) {
    const q = new URLSearchParams(Object.assign({ accion: action }, params || {}));
    return req(API + '?' + q.toString());
  }

  function showBox(type, html) {
    const box = qs('#egMulticajaBox');
    if (!box) return;
    if (!html) {
      box.classList.add('d-none');
      box.innerHTML = '';
      return;
    }

    let cls = 'alert-info';
    if (type === 'success') cls = 'alert-success';
    else if (type === 'warning') cls = 'alert-warning';
    else if (type === 'danger') cls = 'alert-danger';

    box.className = 'alert py-2 px-3 mb-3 ' + cls;
    box.innerHTML = html;
    box.classList.remove('d-none');
  }

  function setMode(mode) {
    const newMode = mode === 'MULTICAJA' ? 'MULTICAJA' : 'NORMAL';
    state.mode = newMode;

    const sel = qs('#egTipoEgresoModo');
    const hid = qs('#egTipoEgreso');
    const btn = qs('#egBtnDistribuir');
    const resumenNormal = qs('#egFuentesResumen');
    const resumenMc = qs('#egMulticajaResumenWrap');

    if (sel) sel.value = newMode;
    if (hid) hid.value = newMode;

    if (btn) {
      if (newMode === 'MULTICAJA') {
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-outline-warning');
        btn.innerHTML = '<i class="fas fa-boxes mr-1"></i>Seleccionar cajas';
      } else {
        btn.classList.remove('btn-outline-warning');
        btn.classList.add('btn-outline-primary');
        btn.innerHTML = '<i class="fas fa-random mr-1"></i>Distribuir';
      }
    }

    if (resumenNormal) resumenNormal.classList.toggle('d-none', newMode === 'MULTICAJA');
    if (resumenMc) resumenMc.classList.toggle('d-none', newMode !== 'MULTICAJA');

    if (newMode === 'MULTICAJA') {
      showBox('info', 'Modo <strong>Multicaja</strong> activo. Ahora el egreso se registrará usando las cajas fuente seleccionadas en este formulario.');
      renderResumen();
    } else {
      showBox('', '');
      syncHiddenPayload([]);
      renderResumen();
    }
  }

  function resetAll() {
    state.cajas = {};
    state.asignaciones = {};
    syncHiddenPayload([]);
    renderSeleccionadas();
    renderDistribucion();
    renderResumen();
    updateTotals();
    setMode('NORMAL');
  }

  function syncHiddenPayload(payload) {
    state.payload = Array.isArray(payload) ? payload : [];
    const hid = qs('#egMulticajaPayload');
    if (hid) {
      hid.value = JSON.stringify(state.payload);
    }
  }

  function saldoDisponibleCaja(row) {
    return round2((((row || {}).saldo || {}).saldo_disponible) || 0);
  }

  function getRowsCaja(row) {
    const byKey = {};
    ((row && row.por_medio) || []).forEach(function (r) {
      const key = canonKey((r || {}).key || (r || {}).label || '');
      if (!key) return;
      byKey[key] = r;
    });

    return ORDER.map(function (key) {
      const r = byKey[key] || {};
      return {
        key: key,
        label: String(r.label || LABELS[key] || key),
        saldo_disponible: round2((r && r.saldo_disponible != null) ? r.saldo_disponible : 0)
      };
    });
  }

  function asignKey(cajaId, key) {
    return String(cajaId) + '|' + String(key);
  }

  async function buscarCajas() {
    const tbody = qs('#egMcCajaBody');
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted small">Buscando cajas...</td></tr>';
    }

    try {
      const r = await get('listar_cajas_fuente', {
        q: (qs('#egMcQ') || {}).value || '',
        fecha: (qs('#egMcFecha') || {}).value || '',
        desde: (qs('#egMcDesde') || {}).value || '',
        hasta: (qs('#egMcHasta') || {}).value || '',
        limit: 40
      });

      renderListaCajas(r.items || []);
      setModalMsg('info', 'Selecciona una o mas cajas de la misma empresa. Solo se muestran saldos reales disponibles por caja.');
    } catch (e) {
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-danger small">' + esc(e.message || 'No se pudo consultar cajas.') + '</td></tr>';
      }
      setModalMsg('danger', esc(e.message || 'No se pudo consultar cajas.'));
    }
  }

  function renderListaCajas(items) {
    const tbody = qs('#egMcCajaBody');
    if (!tbody) return;

    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted small">No se encontraron cajas con los filtros indicados.</td></tr>';
      return;
    }

    tbody.innerHTML = items.map(function (row) {
      const disabled = !row.seleccionable ? 'disabled' : '';
      const badge = String(row.estado || '').toLowerCase() === 'abierta'
        ? '<span class="badge badge-success">Abierta</span>'
        : '<span class="badge badge-secondary">Cerrada</span>';

      return '' +
        '<tr>' +
          '<td><strong>' + esc(row.codigo || '-') + '</strong></td>' +
          '<td>' + esc(fmtDate(row.fecha || '')) + '</td>' +
          '<td>' + badge + '</td>' +
          '<td>' + esc((((row || {}).caja_mensual || {}).codigo) || '-') + '</td>' +
          '<td class="text-right">' + esc(money(saldoDisponibleCaja(row))) + '</td>' +
          '<td>' +
            '<button type="button" class="btn btn-outline-primary btn-xs js-egmc-add" data-id="' + esc(row.id) + '" ' + disabled + '>' +
              '<i class="fas fa-plus mr-1"></i>Elegir' +
            '</button>' +
          '</td>' +
        '</tr>';
    }).join('');
  }

  async function agregarCaja(idCaja) {
    const id = parseInt(idCaja, 10);
    if (!(id > 0)) return;
    if (state.cajas[id]) {
      setModalMsg('warning', 'Esa caja ya fue agregada a la distribucion.');
      return;
    }

    try {
      const r = await get('detalle_caja_fuente', { id_caja_diaria: id });
      const row = r.row || null;
      if (!row || !(row.id > 0)) {
        setModalMsg('danger', 'No se pudo cargar el detalle de la caja seleccionada.');
        return;
      }
      if (!row.seleccionable) {
        setModalMsg('warning', 'La caja seleccionada no tiene saldo disponible para esta operacion.');
        return;
      }

      state.cajas[row.id] = row;
      renderSeleccionadas();
      renderDistribucion();
      updateTotals();
      setModalMsg('success', 'Caja <strong>' + esc(row.codigo || '') + '</strong> agregada. Ahora distribuye montos por medio.');
    } catch (e) {
      setModalMsg('danger', esc(e.message || 'No se pudo cargar la caja fuente.'));
    }
  }

  function quitarCaja(idCaja) {
    const id = parseInt(idCaja, 10);
    if (!(id > 0) || !state.cajas[id]) return;

    delete state.cajas[id];
    Object.keys(state.asignaciones).forEach(function (k) {
      if (k.indexOf(String(id) + '|') === 0) {
        delete state.asignaciones[k];
      }
    });

    renderSeleccionadas();
    renderDistribucion();
    renderResumen();
    updateTotals();
  }

  function renderSeleccionadas() {
    const tbody = qs('#egMcSeleccionBody');
    if (!tbody) return;

    const cajas = Object.values(state.cajas || {});
    if (cajas.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-muted small">Aun no has agregado cajas a la distribucion.</td></tr>';
      return;
    }

    tbody.innerHTML = cajas.map(function (row) {
      const badge = String(row.estado || '').toLowerCase() === 'abierta'
        ? '<span class="badge badge-success">Abierta</span>'
        : '<span class="badge badge-secondary">Cerrada</span>';

      return '' +
        '<tr>' +
          '<td><strong>' + esc(row.codigo || '-') + '</strong></td>' +
          '<td>' + esc(fmtDate(row.fecha || '')) + '</td>' +
          '<td>' + badge + '</td>' +
          '<td class="text-right">' + esc(money(saldoDisponibleCaja(row))) + '</td>' +
          '<td>' +
            '<button type="button" class="btn btn-outline-danger btn-xs js-egmc-remove" data-id="' + esc(row.id) + '">' +
              '<i class="fas fa-times mr-1"></i>Quitar' +
            '</button>' +
          '</td>' +
        '</tr>';
    }).join('');
  }

  function renderDistribucion() {
    const tbody = qs('#egMcDistribBody');
    if (!tbody) return;

    const cajas = Object.values(state.cajas || {});
    if (cajas.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-muted small">Selecciona una o mas cajas para distribuir el egreso.</td></tr>';
      return;
    }

    const html = [];
    cajas.forEach(function (row) {
      const medios = getRowsCaja(row);

      html.push(
        '<tr class="table-warning">' +
          '<td colspan="5">' +
            '<strong>' + esc(row.codigo || '-') + '</strong> &nbsp; ' +
            '<span class="text-muted">(' + esc(fmtDate(row.fecha || '')) + ')</span> &nbsp; ' +
            '<span class="badge badge-light">Disponible: ' + esc(money(saldoDisponibleCaja(row))) + '</span>' +
          '</td>' +
        '</tr>'
      );

      medios.forEach(function (m) {
        const key = asignKey(row.id, m.key);
        const val = round2(state.asignaciones[key] || 0);

        html.push(
          '<tr>' +
            '<td>' + esc(row.codigo || '-') + '</td>' +
            '<td>' + esc(fmtDate(row.fecha || '')) + '</td>' +
            '<td>' + esc(m.label || LABELS[m.key] || m.key) + '</td>' +
            '<td class="text-right">' + esc(money(m.saldo_disponible || 0)) + '</td>' +
            '<td>' +
              '<input type="number" step="0.01" min="0" max="' + esc(String(m.saldo_disponible || 0)) + '" ' +
                'class="form-control form-control-sm js-egmc-monto" ' +
                'data-id="' + esc(row.id) + '" data-key="' + esc(m.key) + '" value="' + esc(val ? String(val) : '') + '">' +
            '</td>' +
          '</tr>'
        );
      });
    });

    tbody.innerHTML = html.join('');
  }

  function setModalMsg(type, html) {
    const box = qs('#egMcModalMsg');
    if (!box) return;

    let cls = 'alert-info';
    if (type === 'success') cls = 'alert-success';
    else if (type === 'warning') cls = 'alert-warning';
    else if (type === 'danger') cls = 'alert-danger';

    box.className = 'alert py-2 mb-3 ' + cls;
    box.innerHTML = html || '';
  }

  function collectPayload() {
    const payload = [];
    Object.keys(state.cajas || {}).forEach(function (idStr) {
      const row = state.cajas[idStr];
      getRowsCaja(row).forEach(function (m) {
        const key = asignKey(row.id, m.key);
        const monto = round2(state.asignaciones[key] || 0);
        if (monto > 0) {
          payload.push({
            id_caja_diaria: row.id,
            key: m.key,
            monto: monto,
            label: m.label,
            caja_codigo: row.codigo || '',
            caja_fecha: row.fecha || ''
          });
        }
      });
    });
    return payload;
  }

  function validateCurrent() {
    const objetivo = getMontoObjetivo();
    const cajas = Object.values(state.cajas || {});
    if (!(objetivo > 0)) return { ok: false, msg: 'Ingresa primero el monto del egreso antes de usar Multicaja.' };
    if (cajas.length === 0) return { ok: false, msg: 'Agrega al menos una caja fuente.' };

    let total = 0;
    const payload = collectPayload();
    for (let i = 0; i < payload.length; i++) {
      const item = payload[i];
      const caja = state.cajas[item.id_caja_diaria];
      const medio = getRowsCaja(caja).filter(function (r) { return r.key === item.key; })[0] || null;
      const disponible = round2((medio && medio.saldo_disponible) || 0);
      if (item.monto > disponible + 0.0001) {
        return { ok: false, msg: 'El monto asignado supera el disponible de ' + (item.label || item.key) + ' en la caja ' + (item.caja_codigo || '') };
      }
      total += item.monto;
    }
    total = round2(total);
    if (payload.length === 0) return { ok: false, msg: 'Debes asignar al menos un monto en alguna caja fuente.' };
    if (Math.abs(total - objetivo) > 0.009) {
      return { ok: false, msg: 'La suma distribuida en Multicaja no coincide con el monto del egreso. Objetivo: ' + money(objetivo) + ', asignado: ' + money(total) };
    }
    return { ok: true, payload: payload, total: total, objetivo: objetivo };
  }

  function updateTotals() {
    const res = validateCurrent();
    const objetivo = getMontoObjetivo();
    const total = res.ok ? round2(res.total) : round2(collectPayload().reduce(function (acc, item) { return acc + num(item.monto); }, 0));
    const diff = round2(objetivo - total);
    if (qs('#egMcMontoObjetivo')) qs('#egMcMontoObjetivo').textContent = money(objetivo);
    if (qs('#egMcMontoAsignado')) qs('#egMcMontoAsignado').textContent = money(total);
    if (qs('#egMcMontoDiff')) qs('#egMcMontoDiff').textContent = money(diff);
  }

  function renderResumen() {
    const box = qs('#egMulticajaResumen');
    if (!box) return;
    const payload = state.payload || [];
    if (!Array.isArray(payload) || payload.length === 0) {
      box.innerHTML = '<span class="text-muted">Aun no hay cajas fuente seleccionadas.</span>';
      return;
    }
    const grouped = {};
    payload.forEach(function (item) {
      const key = String(item.id_caja_diaria);
      if (!grouped[key]) grouped[key] = { codigo: item.caja_codigo || '', fecha: item.caja_fecha || '', rows: [] };
      grouped[key].rows.push(item);
    });
    const html = Object.keys(grouped).map(function (key) {
      const g = grouped[key];
      const rows = g.rows.map(function (item) {
        return esc(item.label || item.key) + ': <strong>' + esc(money(item.monto)) + '</strong>';
      }).join(' &nbsp; | &nbsp; ');
      return '<div class="mb-1"><strong>' + esc(g.codigo || ('Caja ' + key)) + '</strong> <span class="text-muted">(' + esc(fmtDate(g.fecha || '')) + ')</span><br>' + rows + '</div>';
    }).join('');
    box.innerHTML = html;
  }

  function aplicarSeleccion() {
    const v = validateCurrent();
    if (!v.ok) {
      setModalMsg('danger', esc(v.msg));
      return;
    }
    syncHiddenPayload(v.payload);
    renderResumen();
    showBox('success', 'Distribución Multicaja aplicada al formulario. Ahora ya se utilizará al guardar el egreso.');
    if (window.jQuery) window.jQuery('#egMulticajaModal').modal('hide');
  }

  function autoCompletar() {
    const objetivo = getMontoObjetivo();
    if (!(objetivo > 0)) {
      setModalMsg('warning', 'Ingresa primero el monto del egreso.');
      return;
    }

    const cajas = Object.values(state.cajas || {});
    if (cajas.length === 0) {
      setModalMsg('warning', 'Agrega al menos una caja para auto completar.');
      return;
    }

    state.asignaciones = {};
    let faltante = objetivo;

    cajas.forEach(function (row) {
      getRowsCaja(row).forEach(function (m) {
        if (!(faltante > 0)) return;
        const disp = round2(m.saldo_disponible || 0);
        if (!(disp > 0)) return;

        const usar = round2(Math.min(disp, faltante));
        if (usar > 0) {
          state.asignaciones[asignKey(row.id, m.key)] = usar;
          faltante = round2(faltante - usar);
        }
      });
    });

    renderDistribucion();
    updateTotals();

    if (faltante > 0) {
      setModalMsg('warning', 'No alcanza el saldo combinado de las cajas seleccionadas para cubrir el monto total.');
    } else {
      setModalMsg('success', 'Auto completar aplicado. Revisa los montos antes de confirmar.');
    }
  }

  function openModalMulticaja() {
    if (!(getMontoObjetivo() > 0)) {
      showBox('warning', 'Ingresa primero el monto del egreso para usar Multicaja.');
      const monto = qs('#egMonto');
      if (monto) monto.focus();
      return;
    }
    updateTotals();
    renderSeleccionadas();
    renderDistribucion();
    buscarCajas();
    if (window.jQuery) window.jQuery('#egMulticajaModal').modal('show');
  }

  document.addEventListener('click', function (ev) {
    const addBtn = ev.target.closest('.js-egmc-add');
    if (addBtn) { ev.preventDefault(); agregarCaja(addBtn.getAttribute('data-id')); return; }
    const removeBtn = ev.target.closest('.js-egmc-remove');
    if (removeBtn) { ev.preventDefault(); quitarCaja(removeBtn.getAttribute('data-id')); }
  });

  document.addEventListener('input', function (ev) {
    const el = ev.target;
    if (!el || !el.classList || !el.classList.contains('js-egmc-monto')) return;
    const idCaja = parseInt(el.getAttribute('data-id') || '0', 10);
    const key = canonKey(el.getAttribute('data-key') || '');
    const monto = round2(num(el.value || 0));
    if (!(idCaja > 0) || !key) return;
    if (monto > 0) state.asignaciones[asignKey(idCaja, key)] = monto;
    else delete state.asignaciones[asignKey(idCaja, key)];
    updateTotals();
  });

  const modeSel = qs('#egTipoEgresoModo');
  if (modeSel) modeSel.addEventListener('change', function () { setMode(modeSel.value || 'NORMAL'); });
  if (qs('#egMcBuscar')) qs('#egMcBuscar').addEventListener('click', buscarCajas);
  if (qs('#egMcLimpiar')) qs('#egMcLimpiar').addEventListener('click', function () {
    if (qs('#egMcQ')) qs('#egMcQ').value = '';
    if (qs('#egMcFecha')) qs('#egMcFecha').value = '';
    if (qs('#egMcDesde')) qs('#egMcDesde').value = '';
    if (qs('#egMcHasta')) qs('#egMcHasta').value = '';
    buscarCajas();
  });
  if (qs('#egMcAplicar')) qs('#egMcAplicar').addEventListener('click', aplicarSeleccion);
  if (qs('#egMcAuto')) qs('#egMcAuto').addEventListener('click', autoCompletar);
  if (qs('#egBtnLimpiar')) qs('#egBtnLimpiar').addEventListener('click', function () { window.setTimeout(resetAll, 0); });
  if (qs('#egMonto')) qs('#egMonto').addEventListener('input', function () { updateTotals(); if (isMulticaja()) renderResumen(); });
  if (qs('#egBtnDistribuir')) {
    qs('#egBtnDistribuir').addEventListener('click', function (ev) {
      if (!isMulticaja()) return;
      ev.preventDefault();
      ev.stopImmediatePropagation();
      openModalMulticaja();
    }, true);
  }

  window.egMulticaja = {
    isModeMulticaja: isMulticaja,
    getPayload: function () { return Array.isArray(state.payload) ? state.payload.slice() : []; },
    validateCurrent: validateCurrent,
    resetAll: resetAll,
    setMode: setMode,
    renderResumen: renderResumen
  };

  setMode((qs('#egTipoEgreso') || {}).value || 'NORMAL');
})();
