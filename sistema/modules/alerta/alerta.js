(function () {
  var CFG = window.AL_CFG || {};
  var API = CFG.api || '';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
  }

  function pad2(n) { return String(n).padStart(2, '0'); }

  function debounce(fn, ms) {
    var t = null;
    return function () {
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(null, args); }, ms);
    };
  }

  function normTxt(value) {
    var raw = String(value == null ? '' : value).trim();
    if (!raw) return '';
    var low = raw.toLowerCase();
    try {
      return low.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim();
    } catch (e) {
      return low.replace(/\s+/g, ' ').trim();
    }
  }

  function fmtDateTime(value) {
    if (!value) return '-';
    var d = new Date(String(value).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(value);
    return pad2(d.getDate()) + '/' + pad2(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
  }

  function toInputDateTime(value) {
    if (!value) return '';
    return String(value).replace(' ', 'T').slice(0, 16);
  }

  function secondsToClock(sec) {
    if (sec == null) return '-';
    var s = Math.max(0, Math.floor(Number(sec) || 0));
    var d = Math.floor(s / 86400); s -= d * 86400;
    var h = Math.floor(s / 3600); s -= h * 3600;
    var m = Math.floor(s / 60); s -= m * 60;
    if (d > 0) return d + 'd ' + pad2(h) + ':' + pad2(m) + ':' + pad2(s);
    return pad2(h) + ':' + pad2(m) + ':' + pad2(s);
  }

  function knownErrorMessage(msg, fallback) {
    var raw = String(msg || '').trim();
    if (!raw) return fallback || 'No se pudo completar la operación.';
    var key = normTxt(raw);

    if (key.indexOf('empresa no asignada') !== -1) return 'Tu sesión no tiene empresa asignada. Vuelve a iniciar sesión.';
    if (key.indexOf('titulo') !== -1 && key.indexOf('requerido') !== -1) return 'El título es obligatorio para guardar la alerta.';
    if (key.indexOf('fecha base') !== -1 && key.indexOf('requerida') !== -1) return 'Debes indicar una fecha y hora base.';
    if (key.indexOf('fecha base') !== -1 && key.indexOf('invalida') !== -1) return 'La fecha base no es válida. Revisa el formato.';
    if (key.indexOf('tipo invalido') !== -1) return 'El tipo de recordatorio seleccionado no es válido.';
    if (key.indexOf('intervalo') !== -1 && key.indexOf('invalido') !== -1) return 'El intervalo debe ser mayor a 0 días.';
    if (key.indexOf('no encontrado') !== -1) return 'No se encontró la alerta. Puede haber sido eliminada o modificada.';
    if (key.indexOf('error de servidor') !== -1) return 'Ocurrió un error interno. Intenta nuevamente en unos segundos.';

    return raw;
  }

  async function api(url, opts) {
    var response = await fetch(url, Object.assign({ credentials: 'same-origin' }, opts || {}));
    var data = await response.json().catch(function () { return { ok: false, msg: 'Respuesta inválida del servidor.' }; });
    if (!response.ok || !data.ok) {
      throw new Error(knownErrorMessage(data.msg, 'No se pudo procesar la solicitud.'));
    }
    return data;
  }

  var dom = {
    cards: $('#al-cards'),
    qInput: $('#al-q'),
    qClear: $('#al-clear'),
    sEstado: $('#al-estado'),
    sTipo: $('#al-tipo'),
    tbody: $('#al-tbody'),
    pager: $('#al-pager'),
    alertBox: $('#al-alert'),
    btnNew: $('#btn-new'),

    modal: $('#al-modal'),
    dClose: $('#drawer-close'),
    dCancel: $('#btn-cancel'),
    dTitle: $('#drawer-title'),
    btnSave: $('#btn-save'),

    form: $('#al-form'),
    fId: $('#f-id'),
    fTitulo: $('#f-titulo'),
    fCategoria: $('#f-categoria'),
    fCategoriaInput: $('#f-categoria-input'),
    fCategoriaChip: $('#f-categoria-chip'),
    fCategoriaSuggest: $('#f-categoria-suggest'),
    fDescripcion: $('#f-descripcion'),
    fTipo: $('#f-tipo'),
    wrapIntervalo: $('#wrap-intervalo'),
    fIntervalo: $('#f-intervalo'),
    fFecha: $('#f-fecha'),
    fAnt: $('#f-anticipacion'),
    fActivo: $('#f-activo'),
    fErr: $('#form-err'),
    fOk: $('#form-ok'),

    cntTitulo: $('#cnt-titulo'),
    cntCategoria: $('#cnt-categoria'),
    cntDescripcion: $('#cnt-descripcion'),

    toast: $('#al-toast'),
    toastIcon: $('#al-toast-icon'),
    toastText: $('#al-toast-text')
  };

  if (!API || !dom.form || !dom.tbody) return;

  var state = {
    q: '',
    estado: '',
    tipo: '',
    page: 1,
    per: 10,
    rows: [],
    total: 0,
    categories: [],
    countdownTimer: null,
    catDebounce: null,
    toastTimer: null
  };

  var hasJQModal = !!(window.jQuery && dom.modal && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function');

  var typeLabels = {
    ONCE: 'Una sola vez',
    MONTHLY: 'Mensual',
    YEARLY: 'Anual',
    INTERVAL: 'Cada N días'
  };

  function showToast(message, level) {
    if (!dom.toast || !dom.toastText || !dom.toastIcon) return;

    dom.toast.classList.remove('d-none', 'success', 'error', 'info');
    dom.toast.classList.add(level || 'info');
    dom.toastText.textContent = message || 'Operación completada.';

    var icon = 'fas fa-info-circle mr-2';
    if (level === 'success') icon = 'fas fa-check-circle mr-2';
    if (level === 'error') icon = 'fas fa-exclamation-circle mr-2';
    dom.toastIcon.className = icon;

    clearTimeout(state.toastTimer);
    state.toastTimer = setTimeout(function () {
      dom.toast.classList.add('d-none');
    }, 2800);
  }

  function showMainError(msg) {
    if (!dom.alertBox) return;
    dom.alertBox.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>' + esc(msg || 'No se pudo completar la operación.');
    dom.alertBox.classList.remove('d-none');
  }

  function clearMainError() {
    if (!dom.alertBox) return;
    dom.alertBox.classList.add('d-none');
  }

  function showFormError(msg) {
    dom.fErr.textContent = msg || 'Revisa los campos e intenta nuevamente.';
    dom.fErr.classList.remove('d-none');
    dom.fOk.classList.add('d-none');
  }

  function showFormSuccess(msg) {
    dom.fOk.textContent = msg || 'Guardado correctamente.';
    dom.fOk.classList.remove('d-none');
    dom.fErr.classList.add('d-none');
  }

  function openEditorModal(onShown) {
    if (!dom.modal) return;
    if (hasJQModal) {
      var jqModal = window.jQuery(dom.modal);
      if (typeof onShown === 'function') {
        jqModal.off('shown.bs.modal.alFocus').one('shown.bs.modal.alFocus', onShown);
      }
      jqModal.modal('show');
      return;
    }
    dom.modal.classList.add('show');
    dom.modal.style.display = 'block';
    dom.modal.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    if (typeof onShown === 'function') {
      setTimeout(onShown, 120);
    }
  }

  function closeEditorModal() {
    if (!dom.modal) return;
    dom.fErr.classList.add('d-none');
    dom.fOk.classList.add('d-none');
    closeCategorySuggest();
    if (hasJQModal) {
      window.jQuery(dom.modal).modal('hide');
      return;
    }
    dom.modal.classList.remove('show');
    dom.modal.style.display = 'none';
    dom.modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }

  function setSaving(isSaving) {
    if (!dom.btnSave) return;
    dom.btnSave.disabled = !!isSaving;
    dom.btnSave.innerHTML = isSaving
      ? '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...'
      : '<i class="fas fa-save mr-1"></i> Guardar alerta';
  }

  function updateCounter(input, counter, max, currentLength) {
    if (!counter) return;
    var len = Number(currentLength);
    if (isNaN(len)) {
      len = String(input && input.value != null ? input.value : '').length;
    }
    var limit = Number(max || (input ? input.maxLength : 0) || 0);
    counter.textContent = len + '/' + limit;
    counter.classList.remove('warn', 'error');
    if (limit > 0) {
      var ratio = len / limit;
      if (ratio >= 1) counter.classList.add('error');
      else if (ratio >= 0.85) counter.classList.add('warn');
    }
  }

  function refreshAllCounters() {
    updateCounter(dom.fTitulo, dom.cntTitulo, 160);
    updateCounter(dom.fDescripcion, dom.cntDescripcion, 255);
    var categoryLen = Math.max(
      String(dom.fCategoria.value || '').length,
      String(dom.fCategoriaInput.value || '').trim().length
    );
    updateCounter(dom.fCategoriaInput, dom.cntCategoria, 80, categoryLen);
  }

  async function loadSummaryCards() {
    var reqs = [
      api(API + '?action=list&estado=1&page=1&per=1'),
      api(API + '?action=list&estado=0&page=1&per=1'),
      api(API + '?action=week&limit=50')
    ];
    var results = await Promise.all(reqs);
    var activeRes = results[0] || {};
    var inactiveRes = results[1] || {};
    var weekRes = results[2] || {};

    var active = Number(activeRes.total || 0);
    var inactive = Number(inactiveRes.total || 0);
    var total = active + inactive;
    var weekRows = Array.isArray(weekRes.data) ? weekRes.data : [];
    var weekTotal = weekRows.length;
    var inWindow = weekRows.filter(function (r) { return !!r._in_window; }).length;

    dom.cards.innerHTML = ''
      + '<div class="col-6 col-lg-3 mb-2">'
      + '  <div class="al-card"><div class="h">Total</div><div class="n">' + total + '</div><div class="mut">Alertas registradas</div></div>'
      + '</div>'
      + '<div class="col-6 col-lg-3 mb-2">'
      + '  <div class="al-card"><div class="h">Activas</div><div class="n">' + active + '</div><div class="mut">Listas para recordar</div></div>'
      + '</div>'
      + '<div class="col-6 col-lg-3 mb-2">'
      + '  <div class="al-card"><div class="h">Esta semana</div><div class="n">' + weekTotal + '</div><div class="mut">Próximas por atender</div></div>'
      + '</div>'
      + '<div class="col-6 col-lg-3 mb-2">'
      + '  <div class="al-card"><div class="h">En anticipación</div><div class="n">' + inWindow + '</div><div class="mut">Dentro de ventana</div></div>'
      + '</div>';
  }

  function renderRows() {
    if (!state.rows.length) {
      dom.tbody.innerHTML = ''
        + '<tr>'
        + '  <td colspan="7" class="text-center text-muted py-4">'
        + '    <i class="far fa-folder-open mr-1"></i>No hay alertas para mostrar con los filtros actuales.'
        + '  </td>'
        + '</tr>';
      return;
    }

    dom.tbody.innerHTML = state.rows.map(function (row) {
      var tipoLabel = row.tipo === 'INTERVAL'
        ? 'Cada ' + (row.intervalo_dias || '?') + ' días'
        : (typeLabels[row.tipo] || row.tipo || '-');

      var next = row._next_iso ? fmtDateTime(row._next_iso) : '-';
      var sec = row._in_seconds == null ? 0 : Number(row._in_seconds);
      var warn = row._warn_from_ts == null ? 0 : Number(row._warn_from_ts);
      var nextTs = row._next_ts == null ? 0 : Number(row._next_ts);

      var badgeTipo = row.tipo === 'ONCE' ? 'badge-soft-blue' : 'badge-soft-gray';
      var activeBadge = Number(row.activo)
        ? '<span class="badge bg-success">Sí</span>'
        : '<span class="badge bg-secondary">No</span>';

      return ''
        + '<tr>'
        + '  <td>'
        + '    <div class="font-weight-bold">' + esc(row.titulo || '-') + '</div>'
        + '    <div class="text-muted small">' + esc(row.descripcion || '') + '</div>'
        + '  </td>'
        + '  <td>' + esc(row.categoria || '-') + '</td>'
        + '  <td><span class="badge badge-pill ' + badgeTipo + '">' + esc(tipoLabel) + '</span></td>'
        + '  <td>'
        + '    <div>' + esc(next) + '</div>'
        + '    <div class="al-countdown badge badge-pill mt-1" data-seconds="' + sec + '" data-next-ts="' + nextTs + '" data-warn-from="' + warn + '">' + esc(secondsToClock(sec)) + '</div>'
        + '  </td>'
        + '  <td>' + parseInt(row.anticipacion_dias || 0, 10) + ' día(s)</td>'
        + '  <td>' + activeBadge + '</td>'
        + '  <td class="text-right text-nowrap">'
        + '    <button class="btn btn-sm btn-primary" data-act="edit" data-id="' + row.id + '" title="Editar alerta"><i class="fas fa-edit"></i></button> '
        + '    <button class="btn btn-sm ' + (Number(row.activo) ? 'btn-warning' : 'btn-success') + '" data-act="toggle" data-id="' + row.id + '" data-new="' + (Number(row.activo) ? 0 : 1) + '">'
        +        (Number(row.activo) ? 'Inactivar' : 'Activar')
        + '    </button>'
        + '  </td>'
        + '</tr>';
    }).join('');
  }

  function renderPager() {
    var totalPages = Math.max(1, Math.ceil(state.total / state.per));
    var current = Math.min(state.page, totalPages);
    var items = [];

    function add(page, label, cls) {
      items.push('<li class="page-item ' + (cls || '') + '"><a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>');
    }

    add(current - 1, '&laquo;', current <= 1 ? 'disabled' : '');
    var s = Math.max(1, current - 2);
    var e = Math.min(totalPages, s + 4);
    s = Math.max(1, e - 4);
    for (var p = s; p <= e; p++) add(p, p, p === current ? 'active' : '');
    add(current + 1, '&raquo;', current >= totalPages ? 'disabled' : '');

    dom.pager.innerHTML = items.join('');
  }

  function updateCountdowns() {
    var nowTs = Math.floor(Date.now() / 1000);
    $$('.al-countdown[data-next-ts]').forEach(function (el) {
      var nextTs = parseInt(el.dataset.nextTs || '0', 10);
      if (!nextTs) return;

      var warnFrom = parseInt(el.dataset.warnFrom || '0', 10);
      var sec = Math.max(0, nextTs - nowTs);
      el.dataset.seconds = String(sec);
      el.textContent = secondsToClock(sec);

      el.classList.remove('badge-soft-green', 'badge-soft-amber', 'badge-soft-red');
      if (nowTs > nextTs) el.classList.add('badge-soft-red');
      else if (warnFrom > 0 && nowTs >= warnFrom) el.classList.add('badge-soft-amber');
      else el.classList.add('badge-soft-green');
    });
  }

  function startCountdowns() {
    if (state.countdownTimer) clearInterval(state.countdownTimer);
    state.countdownTimer = setInterval(updateCountdowns, 1000);
    updateCountdowns();
  }

  async function loadList() {
    clearMainError();
    var qs = new URLSearchParams({
      action: 'list',
      q: state.q,
      estado: state.estado,
      tipo: state.tipo,
      page: String(state.page),
      per: String(state.per)
    });

    var resp = await api(API + '?' + qs.toString());
    state.rows = Array.isArray(resp.data) ? resp.data : [];
    state.total = Number(resp.total || 0);
    renderRows();
    renderPager();
    startCountdowns();
  }

  function closeCategorySuggest() {
    if (!dom.fCategoriaSuggest) return;
    dom.fCategoriaSuggest.classList.add('d-none');
    dom.fCategoriaSuggest.innerHTML = '';
  }

  function renderCategoryChip() {
    var value = String(dom.fCategoria.value || '').trim();
    if (!value) {
      dom.fCategoriaChip.innerHTML = '';
      refreshAllCounters();
      return;
    }

    dom.fCategoriaChip.innerHTML = ''
      + '<span class="al-tag-chip">'
      + '  <span class="al-tag-chip-label">' + esc(value) + '</span>'
      + '  <button type="button" class="al-tag-chip-remove" id="f-categoria-clear" aria-label="Quitar">&times;</button>'
      + '</span>';

    var clearBtn = $('#f-categoria-clear');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        dom.fCategoria.value = '';
        dom.fCategoriaInput.value = '';
        renderCategoryChip();
        dom.fCategoriaInput.focus();
      });
    }
    refreshAllCounters();
  }

  function setCategory(value) {
    dom.fCategoria.value = String(value || '').replace(/\s+/g, ' ').trim().slice(0, 80);
    dom.fCategoriaInput.value = '';
    renderCategoryChip();
    closeCategorySuggest();
  }

  function canonicalCategory(raw) {
    var txt = String(raw || '').replace(/\s+/g, ' ').trim();
    if (!txt) return '';

    var n = normTxt(txt);
    var best = null;
    state.categories.forEach(function (item) {
      var label = String(item.label || '').trim();
      if (!label) return;
      var nk = normTxt(label);
      if (!nk) return;
      if (nk === n) {
        best = label;
        return;
      }
      if (!best && (nk.indexOf(n) !== -1 || n.indexOf(nk) !== -1)) {
        best = label;
      }
    });
    return String(best || txt).slice(0, 80);
  }

  function getLocalCategorySuggestions(q, max) {
    var n = normTxt(q);
    var limit = max || 8;
    if (!n) return state.categories.slice(0, limit);

    return state.categories
      .map(function (item) {
        var label = String(item.label || '');
        var nk = normTxt(label);
        var score = 99;
        if (nk === n) score = 0;
        else if (nk.indexOf(n) === 0) score = 1;
        else if (nk.indexOf(n) !== -1) score = 2;
        return { label: label, count: Number(item.count || 0), score: score };
      })
      .filter(function (x) { return x.score < 99; })
      .sort(function (a, b) {
        if (a.score !== b.score) return a.score - b.score;
        if (a.count !== b.count) return b.count - a.count;
        return a.label.localeCompare(b.label);
      })
      .slice(0, limit);
  }

  function renderCategorySuggest(items) {
    var list = (items || []).slice(0, 8);
    if (!list.length) {
      closeCategorySuggest();
      return;
    }

    dom.fCategoriaSuggest.innerHTML = list.map(function (item) {
      return ''
        + '<button type="button" class="al-tag-suggest-item" data-label="' + esc(item.label || '') + '">'
        + '  <span>' + esc(item.label || '') + '</span>'
        + '  <small>' + esc(String(item.count || 0)) + '</small>'
        + '</button>';
    }).join('');

    dom.fCategoriaSuggest.classList.remove('d-none');
    $$('.al-tag-suggest-item', dom.fCategoriaSuggest).forEach(function (btn) {
      btn.addEventListener('click', function () {
        setCategory(btn.dataset.label || '');
        dom.fCategoriaInput.focus();
      });
    });
  }

  async function fetchCategories(q) {
    var qs = new URLSearchParams({ action: 'categories', q: q || '', limit: '25' });
    var resp = await api(API + '?' + qs.toString());
    var data = Array.isArray(resp.data) ? resp.data : [];

    var merged = {};
    state.categories.forEach(function (x) {
      merged[normTxt(x.label)] = { label: x.label, count: Number(x.count || 0) };
    });
    data.forEach(function (x) {
      var key = normTxt(x.label);
      if (!key) return;
      merged[key] = { label: x.label, count: Number(x.count || 0) };
    });

    state.categories = Object.keys(merged).map(function (k) { return merged[k]; });
    return data;
  }

  function commitCategoryFromInput() {
    var typed = String(dom.fCategoriaInput.value || '').trim();
    if (!typed) return;
    setCategory(canonicalCategory(typed));
  }

  function syncIntervalVisibility() {
    var isInterval = dom.fTipo.value === 'INTERVAL';
    dom.wrapIntervalo.style.display = isInterval ? '' : 'none';
    if (!isInterval) dom.fIntervalo.value = '';
  }

  function resetForm() {
    if (dom.form.reset) dom.form.reset();
    dom.fId.value = '';
    dom.fCategoria.value = '';
    dom.fCategoriaInput.value = '';
    dom.fErr.classList.add('d-none');
    dom.fOk.classList.add('d-none');

    var now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    dom.fFecha.value = now.toISOString().slice(0, 16);
    dom.fTipo.value = 'ONCE';
    dom.fAnt.value = 0;
    dom.fActivo.checked = true;
    dom.dTitle.textContent = 'Nueva alerta';
    syncIntervalVisibility();
    renderCategoryChip();
    refreshAllCounters();
  }

  async function openForm(id) {
    resetForm();
    await fetchCategories('');

    if (Number(id) > 0) {
      var resp = await api(API + '?action=get&id=' + Number(id));
      var row = resp.data || {};
      dom.fId.value = String(row.id || id);
      dom.dTitle.textContent = 'Editar alerta';
      dom.fTitulo.value = row.titulo || '';
      dom.fDescripcion.value = row.descripcion || '';
      dom.fTipo.value = row.tipo || 'ONCE';
      dom.fIntervalo.value = row.intervalo_dias || '';
      dom.fFecha.value = toInputDateTime(row.fecha_base || '');
      dom.fAnt.value = row.anticipacion_dias || 0;
      dom.fActivo.checked = Number(row.activo) === 1;
      setCategory(row.categoria || '');
      syncIntervalVisibility();
    }

    refreshAllCounters();
    openEditorModal(function () {
      dom.fTitulo.focus();
    });
  }

  function validateFormBeforeSave() {
    var title = String(dom.fTitulo.value || '').trim();
    if (!title) return 'El título es obligatorio.';
    if (!dom.fFecha.value) return 'Debes indicar fecha y hora base.';
    if (dom.fTipo.value === 'INTERVAL') {
      var interval = Number(dom.fIntervalo.value || 0);
      if (!interval || interval <= 0) return 'El intervalo debe ser mayor a 0 días.';
    }
    return '';
  }

  async function saveForm(e) {
    e.preventDefault();
    dom.fErr.classList.add('d-none');
    dom.fOk.classList.add('d-none');

    if (!dom.fCategoria.value && String(dom.fCategoriaInput.value || '').trim()) {
      commitCategoryFromInput();
    }

    var validationError = validateFormBeforeSave();
    if (validationError) {
      showFormError(validationError);
      showToast(validationError, 'error');
      return;
    }

    setSaving(true);
    try {
      var fd = new FormData(dom.form);
      fd.append('action', 'save');
      if (!dom.fActivo.checked) fd.delete('activo');

      await api(API, { method: 'POST', body: fd });
      showFormSuccess('Alerta guardada correctamente.');
      showToast('Alerta guardada correctamente.', 'success');

      await Promise.all([loadSummaryCards(), loadList(), fetchCategories('')]);
      setTimeout(function () { closeEditorModal(); }, 420);
    } catch (err) {
      var message = knownErrorMessage(err.message, 'No se pudo guardar la alerta.');
      showFormError(message);
      showToast(message, 'error');
    } finally {
      setSaving(false);
    }
  }

  function bindEvents() {
    dom.btnNew.addEventListener('click', function () {
      openForm(0).catch(function (err) {
        var msg = knownErrorMessage(err.message, 'No se pudo abrir el formulario.');
        showMainError(msg);
        showToast(msg, 'error');
      });
    });

    dom.dClose.addEventListener('click', function (e) {
      e.preventDefault();
      closeEditorModal();
    });
    dom.dCancel.addEventListener('click', function () {
      closeEditorModal();
    });
    if (dom.modal) {
      dom.modal.addEventListener('hidden.bs.modal', function () {
        dom.fErr.classList.add('d-none');
        dom.fOk.classList.add('d-none');
        closeCategorySuggest();
      });
    }

    dom.form.addEventListener('submit', saveForm);
    dom.fTipo.addEventListener('change', syncIntervalVisibility);

    dom.fTitulo.addEventListener('input', function () { updateCounter(dom.fTitulo, dom.cntTitulo, 160); });
    dom.fDescripcion.addEventListener('input', function () { updateCounter(dom.fDescripcion, dom.cntDescripcion, 255); });
    dom.fCategoriaInput.addEventListener('input', function () { refreshAllCounters(); });

    dom.fCategoriaInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === 'Tab' || e.key === ',' || e.key === ' ') {
        if (String(dom.fCategoriaInput.value || '').trim()) {
          e.preventDefault();
          commitCategoryFromInput();
        }
      } else if (e.key === 'Backspace' && !String(dom.fCategoriaInput.value || '').trim() && String(dom.fCategoria.value || '').trim()) {
        dom.fCategoria.value = '';
        renderCategoryChip();
      } else if (e.key === 'Escape') {
        closeCategorySuggest();
      }
    });

    dom.fCategoriaInput.addEventListener('input', function () {
      var q = String(dom.fCategoriaInput.value || '').trim();
      clearTimeout(state.catDebounce);
      state.catDebounce = setTimeout(async function () {
        try {
          var remote = await fetchCategories(q);
          if (String(dom.fCategoriaInput.value || '').trim() !== q) return;
          var local = getLocalCategorySuggestions(q, 8);
          renderCategorySuggest(remote.length ? remote : local);
        } catch (err) {
          var local = getLocalCategorySuggestions(q, 8);
          renderCategorySuggest(local);
        }
      }, 180);
    });

    dom.fCategoriaInput.addEventListener('focus', function () {
      var q = String(dom.fCategoriaInput.value || '').trim();
      var local = getLocalCategorySuggestions(q, 8);
      if (local.length) renderCategorySuggest(local);
    });

    dom.fCategoriaInput.addEventListener('blur', function () {
      setTimeout(closeCategorySuggest, 120);
    });

    dom.qInput.addEventListener('input', debounce(function () {
      state.q = String(dom.qInput.value || '').trim();
      state.page = 1;
      loadList().catch(function (err) {
        var msg = knownErrorMessage(err.message, 'No se pudo cargar la lista.');
        showMainError(msg);
      });
    }, 260));

    dom.qClear.addEventListener('click', function () {
      dom.qInput.value = '';
      state.q = '';
      state.page = 1;
      loadList().catch(function (err) {
        var msg = knownErrorMessage(err.message, 'No se pudo cargar la lista.');
        showMainError(msg);
      });
    });

    dom.sEstado.addEventListener('change', function () {
      state.estado = dom.sEstado.value;
      state.page = 1;
      loadList().catch(function (err) {
        var msg = knownErrorMessage(err.message, 'No se pudo cargar la lista.');
        showMainError(msg);
      });
    });

    dom.sTipo.addEventListener('change', function () {
      state.tipo = dom.sTipo.value;
      state.page = 1;
      loadList().catch(function (err) {
        var msg = knownErrorMessage(err.message, 'No se pudo cargar la lista.');
        showMainError(msg);
      });
    });

    dom.pager.addEventListener('click', function (e) {
      var a = e.target.closest('a[data-page]');
      if (!a) return;
      e.preventDefault();
      var li = a.parentElement;
      if (li.classList.contains('disabled') || li.classList.contains('active')) return;
      var p = parseInt(a.dataset.page || '1', 10);
      if (p > 0) {
        state.page = p;
        loadList().catch(function (err) {
          var msg = knownErrorMessage(err.message, 'No se pudo cargar la lista.');
          showMainError(msg);
        });
      }
    });

    dom.tbody.addEventListener('click', function (e) {
      var btn = e.target.closest('button[data-act]');
      if (!btn) return;

      var act = btn.dataset.act;
      var id = parseInt(btn.dataset.id || '0', 10);
      if (id <= 0) return;

      if (act === 'edit') {
        openForm(id).catch(function (err) {
          var msg = knownErrorMessage(err.message, 'No se pudo abrir la alerta.');
          showMainError(msg);
          showToast(msg, 'error');
        });
        return;
      }

      if (act === 'toggle') {
        var newState = parseInt(btn.dataset.new || '0', 10);
        var confirmText = newState
          ? '¿Activar esta alerta para que vuelva a mostrar recordatorios?'
          : '¿Inactivar esta alerta? Podrás volver a activarla cuando quieras.';
        if (!window.confirm(confirmText)) return;

        var fd = new FormData();
        fd.append('action', 'toggle');
        fd.append('id', String(id));
        fd.append('activo', String(newState));

        api(API, { method: 'POST', body: fd }).then(function () {
          return Promise.all([loadSummaryCards(), loadList()]);
        }).then(function () {
          showToast(newState ? 'Alerta activada.' : 'Alerta inactivada.', 'success');
        }).catch(function (err) {
          var msg = knownErrorMessage(err.message, 'No se pudo actualizar el estado de la alerta.');
          showMainError(msg);
          showToast(msg, 'error');
        });
      }
    });
  }

  async function boot() {
    bindEvents();
    resetForm();
    try {
      await Promise.all([loadSummaryCards(), loadList(), fetchCategories('')]);
      startCountdowns();
      showToast('Módulo de alertas listo para trabajar.', 'info');
    } catch (err) {
      var msg = knownErrorMessage(err.message, 'No se pudo iniciar el módulo de alertas.');
      showMainError(msg);
      showToast(msg, 'error');
    }
  }

  boot();
})();
