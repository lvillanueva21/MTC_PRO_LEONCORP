// Ver 08-03-26
(function () {
  var root = document.getElementById('avExamRoot');
  if (!root) return;

  var cfg = window.avExamResolverConfig || {};
  var apiUrl = cfg.apiUrl || '';
  var mode = String(cfg.mode || '').toUpperCase();
  if (!apiUrl || (mode !== 'FAST' && mode !== 'AULA')) return;

  var state = {
    form: null,
    questions: [],
    tiposDoc: [],
    categorias: [],
    token: '',
    status: '',
    responses: {},
    remainingSeconds: null,
    timerInterval: null,
    autosaveInterval: null,
    saveDebounce: null,
    dirty: false,
    saving: false,
    starting: false,
    submitting: false
  };

  function $(sel) {
    return root.querySelector(sel) || document.querySelector(sel);
  }

  function esc(text) {
    return String(text == null ? '' : text).replace(/[&<>"']/g, function (m) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      })[m];
    });
  }

  function notify(type, msg) {
    var el = $('#avExamNotice');
    if (!el) return;
    el.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
    var cls = 'alert-info';
    if (type === 'success') cls = 'alert-success';
    if (type === 'error') cls = 'alert-danger';
    if (type === 'warning') cls = 'alert-warning';
    el.classList.add('alert', cls);
    el.textContent = msg;
  }

  function clearNotify() {
    var el = $('#avExamNotice');
    if (!el) return;
    el.classList.add('d-none');
    el.textContent = '';
  }

  function setButtonLoading(btn, loading, txt) {
    if (!btn) return;
    if (loading) {
      btn.dataset.oldText = btn.textContent || '';
      btn.disabled = true;
      btn.textContent = txt || 'Procesando...';
      return;
    }
    btn.disabled = false;
    if (btn.dataset.oldText) btn.textContent = btn.dataset.oldText;
  }

  async function request(url, opts) {
    var res = await fetch(url, Object.assign({ credentials: 'same-origin' }, opts || {}));
    var data = await res.json().catch(function () {
      return { ok: false, msg: 'Respuesta invalida del servidor.' };
    });
    if (!res.ok || !data.ok) {
      throw new Error(data.msg || ('HTTP ' + res.status));
    }
    return data;
  }

  function currentStorageKey() {
    if (cfg.storageKey) return String(cfg.storageKey);
    if (mode === 'FAST') return 'av_exam_fast_token_' + String(cfg.code || '');
    return 'av_exam_aula_token_' + String(cfg.formId || '');
  }

  function readStoredToken() {
    var key = currentStorageKey();
    try {
      return localStorage.getItem(key) || '';
    } catch (e) {
      return '';
    }
  }

  function saveStoredToken(token) {
    var key = currentStorageKey();
    try {
      if (!token) localStorage.removeItem(key);
      else localStorage.setItem(key, token);
    } catch (e) {}
  }

  function getTokenFromUrl() {
    var u = new URL(location.href);
    return u.searchParams.get('t') || '';
  }

  function setUrlToken(token) {
    var u = new URL(location.href);
    if (token) u.searchParams.set('t', token);
    else u.searchParams.delete('t');
    history.replaceState(null, '', u.toString());
  }

  function setToken(token) {
    state.token = String(token || '');
    saveStoredToken(state.token);
    setUrlToken(state.token);
  }

  function setView(name) {
    var landing = $('#avExamLanding');
    var run = $('#avExamRun');
    var final = $('#avExamFinal');
    if (landing) landing.classList.add('avex-hidden');
    if (run) run.classList.add('avex-hidden');
    if (final) final.classList.add('avex-hidden');
    if (name === 'landing' && landing) landing.classList.remove('avex-hidden');
    if (name === 'run' && run) run.classList.remove('avex-hidden');
    if (name === 'final' && final) final.classList.remove('avex-hidden');
  }

  function fmtRemaining(secs) {
    if (secs == null) return 'Sin limite';
    var s = Math.max(0, Number(secs || 0));
    var h = Math.floor(s / 3600);
    var m = Math.floor((s % 3600) / 60);
    var r = s % 60;
    var hm = h > 0 ? (String(h).padStart(2, '0') + ':') : '';
    return hm + String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
  }

  function stopIntervals() {
    if (state.timerInterval) {
      clearInterval(state.timerInterval);
      state.timerInterval = null;
    }
    if (state.autosaveInterval) {
      clearInterval(state.autosaveInterval);
      state.autosaveInterval = null;
    }
    if (state.saveDebounce) {
      clearTimeout(state.saveDebounce);
      state.saveDebounce = null;
    }
  }

  function startIntervals() {
    stopIntervals();

    state.autosaveInterval = setInterval(function () {
      if (state.status !== 'EN_PROGRESO') return;
      saveAttempt(true);
    }, 60000);

    var timerEl = $('#avExamTimer');
    if (state.remainingSeconds == null) {
      if (timerEl) timerEl.textContent = 'Tiempo: Sin limite';
      return;
    }
    if (timerEl) timerEl.textContent = 'Tiempo: ' + fmtRemaining(state.remainingSeconds);

    state.timerInterval = setInterval(function () {
      if (state.status !== 'EN_PROGRESO') {
        stopIntervals();
        return;
      }
      state.remainingSeconds = Number(state.remainingSeconds || 0) - 1;
      if (state.remainingSeconds < 0) state.remainingSeconds = 0;
      if (timerEl) timerEl.textContent = 'Tiempo: ' + fmtRemaining(state.remainingSeconds);
      if (state.remainingSeconds <= 0) {
        submitAttempt(true);
      }
    }, 1000);
  }

  function touchSavedLabel() {
    var label = $('#avExamLastSaved');
    if (!label) return;
    var d = new Date();
    var hh = String(d.getHours()).padStart(2, '0');
    var mm = String(d.getMinutes()).padStart(2, '0');
    var ss = String(d.getSeconds()).padStart(2, '0');
    label.textContent = 'Guardado: ' + hh + ':' + mm + ':' + ss;
  }

  function normalizeResponseMap(raw) {
    var out = {};
    if (!raw || typeof raw !== 'object') return out;
    Object.keys(raw).forEach(function (k) {
      var qid = Number(k || 0);
      if (qid <= 0) return;
      var arr = Array.isArray(raw[k]) ? raw[k] : [raw[k]];
      var clean = [];
      arr.forEach(function (v) {
        var id = Number(v || 0);
        if (id > 0 && clean.indexOf(id) === -1) clean.push(id);
      });
      clean.sort(function (a, b) { return a - b; });
      out[qid] = clean;
    });
    return out;
  }

  function readResponsesFromDom() {
    var map = {};
    state.questions.forEach(function (q) {
      var qid = Number(q.id || 0);
      if (qid <= 0) return;
      var opts = root.querySelectorAll('[data-qid="' + qid + '"][data-oid]');
      var selected = [];
      for (var i = 0; i < opts.length; i++) {
        if (opts[i].checked) {
          var oid = Number(opts[i].dataset.oid || 0);
          if (oid > 0 && selected.indexOf(oid) === -1) selected.push(oid);
        }
      }
      selected.sort(function (a, b) { return a - b; });
      if (selected.length) map[qid] = selected;
    });
    state.responses = map;
    return map;
  }

  function applyResponsesToDom() {
    var map = normalizeResponseMap(state.responses);
    state.responses = map;
    Object.keys(map).forEach(function (k) {
      var qid = Number(k || 0);
      var ids = map[qid] || [];
      ids.forEach(function (oid) {
        var input = root.querySelector('[data-qid="' + qid + '"][data-oid="' + oid + '"]');
        if (input) input.checked = true;
      });
    });
  }

  function renderQuestions() {
    var wrap = $('#avExamQuestions');
    if (!wrap) return;
    if (!state.questions.length) {
      wrap.innerHTML = '<div class="alert alert-warning mb-0">Este examen no tiene preguntas disponibles.</div>';
      return;
    }

    wrap.innerHTML = state.questions.map(function (q, idx) {
      var qid = Number(q.id || 0);
      var type = String(q.tipo || 'OM_UNICA').toUpperCase();
      var inputType = (type === 'OM_UNICA') ? 'radio' : 'checkbox';
      var name = 'q_' + qid + (inputType === 'checkbox' ? '[]' : '');
      var options = (q.opciones || []).map(function (o) {
        var oid = Number(o.id || 0);
        return (
          '<label class="avex-option">' +
            '<input type="' + inputType + '" name="' + esc(name) + '" value="' + oid + '" data-qid="' + qid + '" data-oid="' + oid + '">' +
            ' ' + esc(o.texto || '') +
          '</label>'
        );
      }).join('');

      return (
        '<div class="avex-question">' +
          '<div class="avex-question-title">' + (idx + 1) + '. ' + esc(q.enunciado || '') + '</div>' +
          '<div class="small text-muted mb-2">Puntos: ' + Number(q.puntos || 0).toFixed(2).replace(/\.00$/, '') + '</div>' +
          '<div>' + options + '</div>' +
        '</div>'
      );
    }).join('');

    applyResponsesToDom();
  }

  function renderFastFields() {
    var box = $('#avExamFastFields');
    if (!box) return;
    if (mode !== 'FAST') {
      box.classList.add('avex-hidden');
      return;
    }
    box.classList.remove('avex-hidden');

    var campos = state.form && state.form.campos_fast ? state.form.campos_fast : {};
    var docSel = $('#avExamTipoDoc');
    if (docSel) {
      var allowed = (campos.tipos_doc_permitidos || []).map(function (x) { return Number(x || 0); });
      var docs = state.tiposDoc.filter(function (d) {
        return allowed.indexOf(Number(d.id || 0)) !== -1;
      });
      if (!docs.length) docs = state.tiposDoc.slice();
      var opts = docs.map(function (d) {
        return '<option value="' + d.id + '">' + esc(d.codigo || ('DOC ' + d.id)) + '</option>';
      });
      docSel.innerHTML = opts.join('');
    }

    function toggle(id, on) {
      var el = $(id);
      if (!el) return;
      el.classList.toggle('avex-hidden', !on);
    }

    toggle('#avExamNombresWrap', Number(campos.pedir_nombres || 0) === 1);
    toggle('#avExamApellidosWrap', Number(campos.pedir_apellidos || 0) === 1);
    toggle('#avExamCelularWrap', Number(campos.pedir_celular || 0) === 1);

    var catWrap = $('#avExamCategoriasWrap');
    var catList = $('#avExamCategoriasList');
    if (catWrap) catWrap.classList.toggle('avex-hidden', Number(campos.pedir_categorias || 0) !== 1);
    if (catList && Number(campos.pedir_categorias || 0) === 1) {
      catList.innerHTML = (state.categorias || []).map(function (c) {
        return (
          '<div class="form-check">' +
            '<input class="form-check-input avex-cat-opt" type="checkbox" id="avExamCat' + c.id + '" value="' + c.id + '">' +
            '<label class="form-check-label" for="avExamCat' + c.id + '">' + esc((c.tipo_categoria || '') + ' - ' + (c.codigo || '')) + '</label>' +
          '</div>'
        );
      }).join('');
    }
  }

  function renderHeaderInfo() {
    if ($('#avExamTitle')) $('#avExamTitle').textContent = state.form ? (state.form.titulo || 'Examen') : 'Examen';
    if ($('#avExamDesc')) $('#avExamDesc').textContent = state.form ? (state.form.descripcion || '') : '';

    var rules = [];
    if (state.form) {
      rules.push('Intentos maximos: ' + Number(state.form.intentos_max || 1));
      if (Number(state.form.tiempo_activo || 0) === 1 && Number(state.form.duracion_min || 0) > 0) {
        rules.push('Duracion: ' + Number(state.form.duracion_min) + ' minutos');
      } else {
        rules.push('Duracion: sin limite');
      }
      rules.push('Nota minima: ' + Number(state.form.nota_min || 11));
    }
    if ($('#avExamRules')) $('#avExamRules').textContent = rules.join(' | ');
  }

  function toAttemptPayloadObject(raw) {
    if (!raw || typeof raw !== 'object') return null;
    return {
      token: raw.token || state.token || '',
      status: raw.status || '',
      respuestas: raw.respuestas || {},
      remaining_seconds: (raw.remaining_seconds === null || raw.remaining_seconds === undefined) ? null : Number(raw.remaining_seconds),
      nota_final: raw.nota_final,
      puntaje_obtenido: raw.puntaje_obtenido,
      aprobado: raw.aprobado,
      submitted_at: raw.submitted_at || '',
      mostrar_resultado: (raw.mostrar_resultado === null || raw.mostrar_resultado === undefined)
        ? Number(state.form ? state.form.mostrar_resultado : 1)
        : Number(raw.mostrar_resultado),
      attempt_id: raw.attempt_id || null
    };
  }

  function renderFinal(data, autoMsg) {
    stopIntervals();
    state.status = 'ENVIADO';
    state.dirty = false;

    var payload = toAttemptPayloadObject(data) || {};
    var msgEl = $('#avExamFinalMsg');
    var scoreEl = $('#avExamFinalScore');
    var pdfBtn = $('#avExamPdfBtn');

    var baseMsg = autoMsg || 'Tu examen fue enviado correctamente.';
    if (msgEl) msgEl.textContent = baseMsg;

    var showResult = Number(payload.mostrar_resultado || 0) === 1;
    if (scoreEl) {
      if (showResult) {
        var nota = (payload.nota_final != null) ? Number(payload.nota_final).toFixed(2).replace(/\.00$/, '') : '-';
        var puntaje = (payload.puntaje_obtenido != null) ? Number(payload.puntaje_obtenido).toFixed(2).replace(/\.00$/, '') : '-';
        var aprob = Number(payload.aprobado || 0) === 1 ? 'APROBADO' : 'NO APROBADO';
        scoreEl.textContent = 'Nota: ' + nota + ' | Puntaje: ' + puntaje + ' | ' + aprob;
      } else {
        scoreEl.textContent = 'Resultado oculto por configuracion del formulario.';
      }
    }

    if (pdfBtn) {
      if (mode === 'FAST' && cfg.pdfUrl && state.token) {
        pdfBtn.classList.remove('avex-hidden');
        pdfBtn.href = cfg.pdfUrl + '?t=' + encodeURIComponent(state.token);
      } else {
        pdfBtn.classList.add('avex-hidden');
      }
    }

    setView('final');
  }

  function applyAttemptState(raw) {
    var p = toAttemptPayloadObject(raw);
    if (!p) return;
    if (p.token) setToken(p.token);

    if (String(p.status || '').toUpperCase() === 'ENVIADO') {
      renderFinal(p, 'Examen finalizado.');
      return;
    }

    state.status = String(p.status || 'EN_PROGRESO').toUpperCase();
    state.responses = normalizeResponseMap(p.respuestas || {});
    state.remainingSeconds = p.remaining_seconds;
    if (state.status !== 'EN_PROGRESO') {
      renderFinal(p, 'Examen finalizado.');
      return;
    }

    renderQuestions();
    if ($('#avExamStatusText')) $('#avExamStatusText').textContent = 'Intento en progreso';
    setView('run');
    startIntervals();
  }

  function buildPostData(action, extra) {
    var fd = new FormData();
    fd.append('action', action);
    Object.keys(extra || {}).forEach(function (k) {
      if (extra[k] === null || extra[k] === undefined) return;
      fd.append(k, String(extra[k]));
    });
    return fd;
  }

  function readFastParticipant() {
    var tipoDocId = Number($('#avExamTipoDoc') ? $('#avExamTipoDoc').value : 0) || 0;
    var nroDoc = ($('#avExamNroDoc') && $('#avExamNroDoc').value) ? $('#avExamNroDoc').value.trim() : '';
    var nombres = ($('#avExamNombres') && $('#avExamNombres').value) ? $('#avExamNombres').value.trim() : '';
    var apellidos = ($('#avExamApellidos') && $('#avExamApellidos').value) ? $('#avExamApellidos').value.trim() : '';
    var celular = ($('#avExamCelular') && $('#avExamCelular').value) ? $('#avExamCelular').value.trim() : '';

    if (tipoDocId <= 0) throw new Error('Selecciona el tipo de documento.');
    if (!nroDoc) throw new Error('Ingresa el numero de documento.');

    var categorias = [];
    var checks = root.querySelectorAll('.avex-cat-opt');
    for (var i = 0; i < checks.length; i++) {
      if (checks[i].checked) categorias.push(Number(checks[i].value || 0));
    }
    categorias = categorias.filter(function (x) { return x > 0; });

    return {
      tipo_doc_id: tipoDocId,
      nro_doc: nroDoc,
      nombres: nombres,
      apellidos: apellidos,
      celular: celular,
      categorias: JSON.stringify(categorias)
    };
  }

  async function startAttempt() {
    if (state.starting) return;
    clearNotify();
    var btn = $('#avExamStartBtn');
    state.starting = true;
    setButtonLoading(btn, true, 'Iniciando...');

    try {
      var action = mode === 'FAST' ? 'attempt_start' : 'aula_attempt_start';
      var payload = {};
      if (mode === 'FAST') {
        payload.code = String(cfg.code || '');
        Object.assign(payload, readFastParticipant());
      } else {
        payload.form_id = Number(cfg.formId || 0);
      }

      var data = await request(apiUrl, { method: 'POST', body: buildPostData(action, payload) });
      var d = data.data || {};
      applyAttemptState(d);
      notify('success', data.msg || 'Intento iniciado.');
    } catch (err) {
      notify('error', err.message || 'No se pudo iniciar el intento.');
    } finally {
      setButtonLoading(btn, false);
      state.starting = false;
    }
  }

  async function saveAttempt(force) {
    if (!state.token || state.status !== 'EN_PROGRESO') return;
    if (!force && !state.dirty) return;
    if (state.saving || state.submitting) return;

    state.saving = true;
    readResponsesFromDom();

    try {
      var action = mode === 'FAST' ? 'attempt_save' : 'aula_attempt_save';
      var data = await request(apiUrl, {
        method: 'POST',
        body: buildPostData(action, {
          token: state.token,
          respuestas: JSON.stringify(state.responses || {})
        })
      });
      var d = data.data || {};
      if (String(d.status || '').toUpperCase() === 'ENVIADO') {
        renderFinal(d, d.msg || 'El tiempo expiro y el examen se envio automaticamente.');
        return;
      }
      applyAttemptState(d);
      state.dirty = false;
      touchSavedLabel();
    } catch (err) {
      notify('warning', err.message || 'No se pudo guardar temporalmente.');
    } finally {
      state.saving = false;
    }
  }

  function queueSave() {
    state.dirty = true;
    if (state.saveDebounce) clearTimeout(state.saveDebounce);
    state.saveDebounce = setTimeout(function () {
      saveAttempt(false);
    }, 500);
  }

  async function submitAttempt(auto) {
    if (!state.token || state.submitting) return;
    if (state.status === 'ENVIADO') return;
    state.submitting = true;
    var btn = $('#avExamSubmitBtn');
    setButtonLoading(btn, true, auto ? 'Enviando por tiempo...' : 'Enviando...');
    readResponsesFromDom();
    try {
      var action = mode === 'FAST' ? 'attempt_submit' : 'aula_attempt_submit';
      var data = await request(apiUrl, {
        method: 'POST',
        body: buildPostData(action, {
          token: state.token,
          respuestas: JSON.stringify(state.responses || {})
        })
      });
      renderFinal(data.data || {}, auto ? 'Tiempo finalizado. El examen fue enviado automaticamente.' : 'Examen enviado correctamente.');
      notify('success', data.msg || 'Examen enviado correctamente.');
    } catch (err) {
      notify('error', err.message || 'No se pudo enviar el examen.');
    } finally {
      state.submitting = false;
      setButtonLoading(btn, false);
    }
  }

  async function loadInfo() {
    var qs = new URLSearchParams();
    if (mode === 'FAST') {
      qs.set('action', 'form_public_info');
      qs.set('code', String(cfg.code || ''));
    } else {
      qs.set('action', 'aula_form_info');
      qs.set('form_id', String(Number(cfg.formId || 0)));
    }

    var data = await request(apiUrl + '?' + qs.toString());
    var d = data.data || {};
    state.form = d.form || null;
    state.questions = d.questions || [];
    state.tiposDoc = d.tipos_doc || [];
    state.categorias = d.categorias || [];

    renderHeaderInfo();
    renderFastFields();

    if (mode === 'AULA' && Number(d.blocked || 0) === 1) {
      notify('warning', d.blocked_msg || 'El examen esta bloqueado por rango horario del grupo.');
      if ($('#avExamStartBtn')) $('#avExamStartBtn').disabled = true;
    }
  }

  async function checkTokenStatus(token) {
    if (!token) return false;
    try {
      var qs = new URLSearchParams();
      qs.set('action', mode === 'FAST' ? 'attempt_status' : 'aula_attempt_status');
      qs.set('token', token);
      var data = await request(apiUrl + '?' + qs.toString());
      var d = data.data || {};
      applyAttemptState(d);
      return true;
    } catch (err) {
      setToken('');
      return false;
    }
  }

  root.addEventListener('change', function (e) {
    if (!e.target) return;
    if (!e.target.matches('[data-qid][data-oid]')) return;
    queueSave();
  });

  var startBtn = $('#avExamStartBtn');
  if (startBtn) {
    startBtn.addEventListener('click', function () {
      startAttempt();
    });
  }

  var saveBtn = $('#avExamSaveBtn');
  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      saveAttempt(true);
    });
  }

  var submitBtn = $('#avExamSubmitBtn');
  if (submitBtn) {
    submitBtn.addEventListener('click', function () {
      if (!window.confirm('Vas a enviar el examen. Esta accion no se puede deshacer.')) return;
      submitAttempt(false);
    });
  }

  var backBtn = $('#avExamBackBtn');
  if (backBtn) {
    backBtn.addEventListener('click', function () {
      stopIntervals();
      setToken('');
      state.responses = {};
      state.status = '';
      setView('landing');
      clearNotify();
      renderQuestions();
    });
  }

  Promise.resolve()
    .then(function () { return loadInfo(); })
    .then(function () {
      var token = getTokenFromUrl() || readStoredToken();
      if (!token) {
        setView('landing');
        return;
      }
      return checkTokenStatus(token).then(function (ok) {
        if (!ok) setView('landing');
      });
    })
    .catch(function (err) {
      notify('error', err.message || 'No se pudo cargar el formulario.');
      setView('landing');
    });
})();
