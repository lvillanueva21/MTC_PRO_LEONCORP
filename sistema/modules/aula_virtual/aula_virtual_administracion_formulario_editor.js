// Ver 08-03-26
(function () {
  var root = document.getElementById('avAdminFormEditorRoot');
  if (!root) return;

  var cfg = window.avAdminFormEditorConfig || {};
  var apiUrl = cfg.apiUrl || '';
  if (!apiUrl) return;

  var state = {
    formId: Number(cfg.formId || 0),
    mode: String(cfg.modo || 'FAST').toUpperCase(),
    initialGroupId: Number(cfg.grupoId || 0),
    initialTab: String(cfg.tab || 'config'),
    catalog: {
      grupos: [],
      cursos: [],
      temas: [],
      tipos_doc: [],
      categorias: []
    },
    detail: null,
    questions: [],
    attempts: [],
    editingQuestion: null,
    share: null
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
    var el = $('#avaNotice');
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
    var el = $('#avaNotice');
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

  function showModal(id) {
    if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(id).modal('show');
      return;
    }
    var el = document.querySelector(id);
    if (!el) return;
    el.style.display = 'block';
    el.classList.add('show');
    el.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
  }

  function hideModal(id) {
    if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(id).modal('hide');
      return;
    }
    var el = document.querySelector(id);
    if (!el) return;
    el.style.display = 'none';
    el.classList.remove('show');
    el.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }

  function activateTab(tabName) {
    var map = {
      config: '#avfeTabConfig',
      preguntas: '#avfeTabPreguntas',
      attempts: '#avfeTabAttempts',
      share: '#avfeTabShare'
    };
    var target = map[tabName] || '#avfeTabConfig';
    var link = $('#avfeTabs a[href="' + target + '"]');
    if (!link) return;

    if (window.jQuery && typeof window.jQuery.fn.tab === 'function') {
      window.jQuery(link).tab('show');
      return;
    }

    var links = root.querySelectorAll('#avfeTabs .nav-link');
    for (var i = 0; i < links.length; i++) links[i].classList.remove('active');
    link.classList.add('active');

    var panes = root.querySelectorAll('.tab-content .tab-pane');
    for (var j = 0; j < panes.length; j++) panes[j].classList.remove('show', 'active');
    var pane = $(target);
    if (pane) pane.classList.add('show', 'active');
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

  function renderModeBlocks() {
    var mode = String(state.mode || 'FAST').toUpperCase();
    var aulaFields = $('#avfeAulaFields');
    var fastFields = $('#avfeFastFields');
    if (aulaFields) aulaFields.classList.toggle('d-none', mode !== 'AULA');
    if (fastFields) fastFields.classList.toggle('d-none', mode !== 'FAST');
    var shareLink = $('#avfeTabs a[href="#avfeTabShare"]');
    if (shareLink && mode !== 'FAST') {
      shareLink.classList.add('disabled');
      shareLink.setAttribute('aria-disabled', 'true');
    } else if (shareLink) {
      shareLink.classList.remove('disabled');
      shareLink.removeAttribute('aria-disabled');
    }
  }

  function renderCatalogBase() {
    var gruposSel = $('#avfeGrupo');
    var cursosSel = $('#avfeCurso');
    var tiposWrap = $('#avfeTiposDocList');

    if (gruposSel) {
      var opts = ['<option value="0">Selecciona grupo</option>'];
      state.catalog.grupos.forEach(function (g) {
        var code = g.codigo ? ('[' + g.codigo + '] ') : '';
        opts.push('<option value="' + g.id + '" data-curso="' + Number(g.curso_id || 0) + '">' + esc(code + (g.nombre || 'Grupo')) + '</option>');
      });
      gruposSel.innerHTML = opts.join('');
    }

    if (cursosSel) {
      var copts = ['<option value="0">Selecciona curso</option>'];
      state.catalog.cursos.forEach(function (c) {
        copts.push('<option value="' + c.id + '">' + esc(c.nombre || ('Curso ' + c.id)) + '</option>');
      });
      cursosSel.innerHTML = copts.join('');
    }

    if (tiposWrap) {
      if (!state.catalog.tipos_doc.length) {
        tiposWrap.innerHTML = '<div class="text-muted small">No hay tipos de documento.</div>';
      } else {
        tiposWrap.innerHTML = state.catalog.tipos_doc.map(function (d) {
          var code = String(d.codigo || '').toUpperCase();
          var isDni = code === 'DNI';
          return (
            '<div class="form-check">' +
              '<input class="form-check-input avfe-doc-opt" type="checkbox" id="avfeDoc' + d.id + '" value="' + d.id + '"' + (isDni ? ' data-dni="1" checked disabled' : '') + '>' +
              '<label class="form-check-label" for="avfeDoc' + d.id + '">' + esc(d.codigo || ('DOC ' + d.id)) + (isDni ? ' (obligatorio)' : '') + '</label>' +
            '</div>'
          );
        }).join('');
      }
    }
  }

  async function loadTemasByCurso(cursoId) {
    var temaSel = $('#avfeTema');
    if (!temaSel) return;
    if (Number(cursoId || 0) <= 0) {
      temaSel.innerHTML = '<option value="0">Sin tema</option>';
      return;
    }
    var qs = new URLSearchParams({
      action: 'temas_by_curso',
      curso_id: String(cursoId)
    });
    var data = await request(apiUrl + '?' + qs.toString());
    var temas = data.data || [];
    var opts = ['<option value="0">Sin tema</option>'];
    temas.forEach(function (t) {
      opts.push('<option value="' + t.id + '">' + esc(t.titulo || ('Tema ' + t.id)) + '</option>');
    });
    temaSel.innerHTML = opts.join('');
  }

  function getDocCheckboxValues() {
    var list = root.querySelectorAll('.avfe-doc-opt');
    var out = [];
    for (var i = 0; i < list.length; i++) {
      if (list[i].checked) out.push(Number(list[i].value || 0));
    }
    out = out.filter(function (n) { return n > 0; });
    var dni = state.catalog.tipos_doc.find(function (d) {
      return String(d.codigo || '').toUpperCase() === 'DNI';
    });
    var dniId = dni ? Number(dni.id || 0) : 0;
    if (dniId > 0 && out.indexOf(dniId) === -1) {
      out.unshift(dniId);
    }
    if (!out.length && state.catalog.tipos_doc.length) {
      out.push(Number(state.catalog.tipos_doc[0].id || 1));
    }
    return out;
  }

  function setDocCheckboxValues(ids) {
    var list = root.querySelectorAll('.avfe-doc-opt');
    var set = {};
    (ids || []).forEach(function (id) {
      var n = Number(id || 0);
      if (n > 0) set[n] = true;
    });
    var dni = state.catalog.tipos_doc.find(function (d) {
      return String(d.codigo || '').toUpperCase() === 'DNI';
    });
    var dniId = dni ? Number(dni.id || 0) : 0;
    if (dniId > 0) set[dniId] = true;
    for (var i = 0; i < list.length; i++) {
      var val = Number(list[i].value || 0);
      list[i].checked = !!set[val];
      if (list[i].dataset && list[i].dataset.dni === '1') {
        list[i].checked = true;
      }
    }
  }

  function readConfigForm() {
    var modoSel = $('#avfeModo');
    var modo = modoSel ? String(modoSel.value || 'FAST').toUpperCase() : 'FAST';
    var payload = {
      modo: modo,
      tipo: 'EXAMEN',
      titulo: ($('#avfeTitulo') && $('#avfeTitulo').value ? $('#avfeTitulo').value.trim() : ''),
      descripcion: ($('#avfeDescripcion') && $('#avfeDescripcion').value ? $('#avfeDescripcion').value.trim() : ''),
      intentos_max: Number($('#avfeIntentosMax') ? $('#avfeIntentosMax').value : 1) || 1,
      tiempo_activo: Number($('#avfeTiempoActivo') ? $('#avfeTiempoActivo').value : 0) ? 1 : 0,
      duracion_min: Number($('#avfeDuracionMin') ? $('#avfeDuracionMin').value : 0) || 0,
      nota_min: Number($('#avfeNotaMin') ? $('#avfeNotaMin').value : 11) || 11,
      mostrar_resultado: Number($('#avfeMostrarResultado') ? $('#avfeMostrarResultado').value : 1) ? 1 : 0,
      requisito_cumplimiento: $('#avfeRequisito') ? String($('#avfeRequisito').value || 'ENVIAR') : 'ENVIAR'
    };

    if (modo === 'AULA') {
      payload.grupo_id = Number($('#avfeGrupo') ? $('#avfeGrupo').value : 0) || 0;
      payload.curso_id = Number($('#avfeCurso') ? $('#avfeCurso').value : 0) || 0;
      payload.tema_id = Number($('#avfeTema') ? $('#avfeTema').value : 0) || 0;
      if (payload.tema_id <= 0) payload.tema_id = '';
    } else {
      payload.campos_fast = JSON.stringify({
        pedir_nombres: $('#avfeFastNombres') && $('#avfeFastNombres').checked ? 1 : 0,
        pedir_apellidos: $('#avfeFastApellidos') && $('#avfeFastApellidos').checked ? 1 : 0,
        pedir_celular: $('#avfeFastCelular') && $('#avfeFastCelular').checked ? 1 : 0,
        pedir_categorias: $('#avfeFastCategorias') && $('#avfeFastCategorias').checked ? 1 : 0,
        tipos_doc_permitidos: getDocCheckboxValues()
      });
    }

    return payload;
  }

  function fillConfigForm(form) {
    form = form || {};
    state.mode = String(form.modo || state.mode || 'FAST').toUpperCase();

    if ($('#avfeTitulo')) $('#avfeTitulo').value = form.titulo || '';
    if ($('#avfeDescripcion')) $('#avfeDescripcion').value = form.descripcion || '';
    if ($('#avfeModo')) $('#avfeModo').value = state.mode;
    if ($('#avfeModo')) $('#avfeModo').disabled = state.formId > 0;
    if ($('#avfeIntentosMax')) $('#avfeIntentosMax').value = Number(form.intentos_max || 1);
    if ($('#avfeTiempoActivo')) $('#avfeTiempoActivo').value = Number(form.tiempo_activo || 0) ? '1' : '0';
    if ($('#avfeDuracionMin')) $('#avfeDuracionMin').value = form.duracion_min != null ? Number(form.duracion_min) : '';
    if ($('#avfeNotaMin')) $('#avfeNotaMin').value = form.nota_min != null ? Number(form.nota_min) : 11;
    if ($('#avfeMostrarResultado')) $('#avfeMostrarResultado').value = Number(form.mostrar_resultado || 1) ? '1' : '0';
    if ($('#avfeRequisito')) $('#avfeRequisito').value = String(form.requisito_cumplimiento || 'ENVIAR');

    if ($('#avfeGrupo')) $('#avfeGrupo').value = String(Number(form.grupo_id || state.initialGroupId || 0));
    var courseValue = Number(form.curso_id || 0);
    if (state.mode === 'AULA' && courseValue <= 0) {
      var gid = Number(form.grupo_id || state.initialGroupId || 0);
      if (gid > 0) {
        var gx = state.catalog.grupos.find(function (x) { return Number(x.id) === gid; }) || null;
        if (gx) courseValue = Number(gx.curso_id || 0);
      }
    }
    if ($('#avfeCurso')) $('#avfeCurso').value = String(courseValue || 0);
    loadTemasByCurso(Number(courseValue || 0)).then(function () {
      if ($('#avfeTema')) $('#avfeTema').value = String(Number(form.tema_id || 0));
    });

    var cf = form.campos_fast || {};
    if ($('#avfeFastNombres')) $('#avfeFastNombres').checked = Number(cf.pedir_nombres || 0) === 1;
    if ($('#avfeFastApellidos')) $('#avfeFastApellidos').checked = Number(cf.pedir_apellidos || 0) === 1;
    if ($('#avfeFastCelular')) $('#avfeFastCelular').checked = Number(cf.pedir_celular || 0) === 1;
    if ($('#avfeFastCategorias')) $('#avfeFastCategorias').checked = Number(cf.pedir_categorias || 0) === 1;
    setDocCheckboxValues(cf.tipos_doc_permitidos || []);

    renderModeBlocks();
  }

  function renderSummary() {
    var form = state.detail ? state.detail.form : null;
    if ($('#avfeFormIdLabel')) $('#avfeFormIdLabel').textContent = form ? String(form.id) : 'Nuevo';
    if ($('#avfeEstadoLabel')) $('#avfeEstadoLabel').textContent = form ? String(form.estado || 'BORRADOR') : 'BORRADOR';
    if ($('#avfePreguntasCount')) $('#avfePreguntasCount').textContent = String(state.questions.length);
    if ($('#avfeIntentosCount')) $('#avfeIntentosCount').textContent = String(state.attempts.length);
    if ($('#avfeHeaderTitle')) $('#avfeHeaderTitle').textContent = form ? (form.titulo || ('Formulario #' + form.id)) : 'Nuevo examen';
    if ($('#avfeHeaderMeta')) $('#avfeHeaderMeta').textContent = form ? ('Modo ' + (form.modo || state.mode)) : ('Modo ' + state.mode);

    var sum = 0;
    state.questions.forEach(function (q) {
      sum += Number(q.puntos || 0);
    });
    var totalEl = $('#avfePuntosTotal');
    if (totalEl) {
      totalEl.textContent = 'Total puntos: ' + sum.toFixed(2).replace(/\.00$/, '') + ' / 20';
      totalEl.classList.remove('ok', 'bad');
      totalEl.classList.add(Math.abs(sum - 20) < 0.01 ? 'ok' : 'bad');
    }

    var estado = form ? String(form.estado || 'BORRADOR').toUpperCase() : 'BORRADOR';
    if ($('#avfePublishBtn')) $('#avfePublishBtn').classList.toggle('d-none', !(state.formId > 0 && estado === 'BORRADOR'));
    if ($('#avfeCloseBtn')) $('#avfeCloseBtn').classList.toggle('d-none', !(state.formId > 0 && estado === 'PUBLICADO'));
    if ($('#avfeDraftBtn')) $('#avfeDraftBtn').classList.toggle('d-none', !(state.formId > 0 && estado === 'CERRADO'));
    if ($('#avfeDeleteBtn')) $('#avfeDeleteBtn').classList.toggle('d-none', !(state.formId > 0));
  }

  function renderQuestions() {
    var list = $('#avfeQuestionsList');
    var empty = $('#avfeQuestionsEmpty');
    if (!list || !empty) return;

    if (!state.questions.length) {
      list.innerHTML = '';
      empty.classList.remove('d-none');
      return;
    }
    empty.classList.add('d-none');
    list.innerHTML = state.questions.map(function (q) {
      var opts = (q.opciones || []).map(function (o) {
        var ok = Number(o.es_correcta || 0) === 1 ? ' <span class="badge badge-success">OK</span>' : '';
        return '<li>' + esc(o.texto || '') + ok + '</li>';
      }).join('');
      return (
        '<div class="avfe-question-item">' +
          '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">' +
            '<div>' +
              '<div class="avfe-question-title">' + esc(q.enunciado || '') + '</div>' +
              '<div class="text-muted small">Tipo: ' + esc(q.tipo || '') + ' | Puntos: ' + esc(q.puntos || 0) + ' | Orden: ' + esc(q.orden || 0) + '</div>' +
            '</div>' +
            '<div class="avfe-actions">' +
              '<button type="button" class="btn btn-sm btn-outline-primary" data-action="edit-question" data-id="' + q.id + '">Editar</button>' +
              '<button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-question" data-id="' + q.id + '">Eliminar</button>' +
            '</div>' +
          '</div>' +
          '<ul class="mb-0 mt-2">' + opts + '</ul>' +
        '</div>'
      );
    }).join('');
  }

  function renderAttempts() {
    var tbody = $('#avfeAttemptsTbody');
    if (!tbody) return;

    if (!state.attempts.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="ava-empty">Sin intentos registrados.</td></tr>';
      return;
    }

    tbody.innerHTML = state.attempts.map(function (a) {
      var participante = '-';
      if (String(a.modo || '').toUpperCase() === 'FAST') {
        var full = ((a.nombres || '') + ' ' + (a.apellidos || '')).trim();
        var doc = ((a.tipo_doc_codigo || 'DOC') + ' ' + (a.nro_doc || '')).trim();
        participante = full || doc || 'FAST';
      } else {
        participante = 'Usuario ID ' + Number(a.usuario_id || 0);
      }
      var nota = (a.nota_final != null) ? Number(a.nota_final).toFixed(2).replace(/\.00$/, '') : '-';
      return (
        '<tr>' +
          '<td>' +
            '<div class="fw-semibold">' + esc(participante) + '</div>' +
            '<div class="small text-muted">Intento #' + Number(a.intento_nro || 0) + ' | Token: ' + esc(String(a.token || '').slice(0, 12)) + '...</div>' +
          '</td>' +
          '<td class="text-center">' + esc(a.status || '-') + '</td>' +
          '<td class="text-center">' + esc(nota) + '</td>' +
          '<td>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-attempt" data-id="' + Number(a.id || 0) + '">Eliminar</button>' +
          '</td>' +
        '</tr>'
      );
    }).join('');
  }

  function renderShare() {
    var box = $('#avfeShareBox');
    var empty = $('#avfeShareEmpty');
    var form = state.detail ? state.detail.form : null;
    var isFast = form && String(form.modo || '').toUpperCase() === 'FAST';

    if (!box || !empty) return;
    if (!form || !isFast || !state.share) {
      box.classList.add('d-none');
      empty.classList.remove('d-none');
      return;
    }

    box.classList.remove('d-none');
    empty.classList.add('d-none');
    if ($('#avfeShareLink')) $('#avfeShareLink').value = state.share.link || '';
    if ($('#avfeShareCode')) $('#avfeShareCode').value = state.share.code || '';
  }

  async function loadAttempts() {
    if (state.formId <= 0) {
      state.attempts = [];
      renderAttempts();
      return;
    }
    var qs = new URLSearchParams({
      action: 'attempts_list',
      form_id: String(state.formId),
      page: '1',
      per_page: '100'
    });
    var data = await request(apiUrl + '?' + qs.toString());
    state.attempts = data.data || [];
    renderAttempts();
    renderSummary();
  }

  async function loadDetail() {
    if (state.formId <= 0) {
      state.detail = null;
      state.questions = [];
      state.share = null;
      renderQuestions();
      renderShare();
      renderSummary();
      fillConfigForm({
        modo: state.mode,
        grupo_id: state.initialGroupId,
        intentos_max: 1,
        tiempo_activo: 0,
        mostrar_resultado: 1,
        nota_min: 11
      });
      return;
    }
    var qs = new URLSearchParams({
      action: 'form_detail',
      form_id: String(state.formId)
    });
    var data = await request(apiUrl + '?' + qs.toString());
    state.detail = data.data || null;
    state.questions = (state.detail && state.detail.questions) ? state.detail.questions : [];
    state.share = (state.detail && state.detail.share) ? state.detail.share : null;
    if (state.detail && state.detail.form) {
      fillConfigForm(state.detail.form);
    }
    renderQuestions();
    renderShare();
    renderSummary();
    await loadAttempts();
  }

  async function loadCatalog() {
    var cursoId = 0;
    if (state.detail && state.detail.form) cursoId = Number(state.detail.form.curso_id || 0);
    else if ($('#avfeCurso')) cursoId = Number($('#avfeCurso').value || 0);

    var qs = new URLSearchParams({ action: 'catalog_data' });
    if (cursoId > 0) qs.set('curso_id', String(cursoId));
    var data = await request(apiUrl + '?' + qs.toString());
    state.catalog = data.data || state.catalog;
    renderCatalogBase();
  }

  function formStatePost(action, payload) {
    var fd = new FormData();
    fd.append('action', action);
    Object.keys(payload || {}).forEach(function (k) {
      if (payload[k] === null || payload[k] === undefined) return;
      fd.append(k, String(payload[k]));
    });
    return request(apiUrl, { method: 'POST', body: fd });
  }

  async function saveConfig() {
    clearNotify();
    var btn = $('#avfeSaveBtn');
    var p = readConfigForm();
    if (!p.titulo) {
      notify('warning', 'El titulo es obligatorio.');
      return;
    }
    if (p.modo === 'AULA' && Number(p.grupo_id || 0) <= 0) {
      notify('warning', 'Para AULA debes seleccionar un grupo.');
      return;
    }
    if (Number(p.tiempo_activo || 0) === 1 && Number(p.duracion_min || 0) <= 0) {
      notify('warning', 'Define la duracion en minutos cuando el tiempo esta activo.');
      return;
    }

    setButtonLoading(btn, true, state.formId > 0 ? 'Guardando...' : 'Creando...');
    try {
      var data;
      if (state.formId > 0) {
        p.form_id = state.formId;
        data = await formStatePost('form_update', p);
      } else {
        data = await formStatePost('form_create', p);
      }
      notify('success', data.msg || 'Configuracion guardada.');
      var f = data.data || null;
      if (f && f.id && state.formId <= 0) {
        state.formId = Number(f.id);
        var url = new URL(location.href);
        url.searchParams.set('id', String(state.formId));
        history.replaceState(null, '', url.toString());
      }
      await loadCatalog();
      await loadDetail();
    } catch (err) {
      notify('error', err.message || 'No se pudo guardar la configuracion.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  async function setEstado(estado) {
    if (state.formId <= 0) return;
    try {
      var data = await formStatePost('form_set_estado', {
        form_id: state.formId,
        estado: estado
      });
      notify('success', data.msg || 'Estado actualizado.');
      await loadDetail();
    } catch (err) {
      notify('error', err.message || 'No se pudo cambiar el estado.');
    }
  }

  async function deleteForm() {
    if (state.formId <= 0) return;
    if (!window.confirm('Vas a eliminar/cerrar este formulario. Deseas continuar?')) return;
    try {
      var data = await formStatePost('form_delete', {
        form_id: state.formId
      });
      notify('success', data.msg || 'Formulario procesado.');
      if (cfg.backUrl) location.href = cfg.backUrl;
    } catch (err) {
      notify('error', err.message || 'No se pudo eliminar el formulario.');
    }
  }

  function resetQuestionModal() {
    state.editingQuestion = null;
    if ($('#avfeQuestionId')) $('#avfeQuestionId').value = '0';
    if ($('#avfeQuestionEnunciado')) $('#avfeQuestionEnunciado').value = '';
    if ($('#avfeQuestionTipo')) $('#avfeQuestionTipo').value = 'OM_UNICA';
    if ($('#avfeQuestionPuntos')) $('#avfeQuestionPuntos').value = '1';
    if ($('#avfeQuestionOrden')) $('#avfeQuestionOrden').value = String(state.questions.length + 1);
    if ($('#avfeQuestionModalTitle')) $('#avfeQuestionModalTitle').textContent = 'Agregar pregunta';
    var wrap = $('#avfeOptionsWrap');
    if (wrap) wrap.innerHTML = '';
    addOptionRow('', 1);
    addOptionRow('', 0);
  }

  function addOptionRow(texto, correct) {
    var wrap = $('#avfeOptionsWrap');
    if (!wrap) return;
    var row = document.createElement('div');
    row.className = 'avfe-option-row';
    row.innerHTML =
      '<input type="text" class="form-control avfe-opt-text" maxlength="255" placeholder="Texto de opcion">' +
      '<label class="m-0 small"><input type="checkbox" class="avfe-opt-ok"> Correcta</label>' +
      '<button type="button" class="btn btn-sm btn-outline-danger avfe-opt-del">Quitar</button>';
    var input = row.querySelector('.avfe-opt-text');
    var chk = row.querySelector('.avfe-opt-ok');
    if (input) input.value = texto || '';
    if (chk) chk.checked = Number(correct || 0) === 1;
    wrap.appendChild(row);
  }

  function readQuestionPayload() {
    var tipo = $('#avfeQuestionTipo') ? String($('#avfeQuestionTipo').value || 'OM_UNICA') : 'OM_UNICA';
    var enunciado = $('#avfeQuestionEnunciado') ? $('#avfeQuestionEnunciado').value.trim() : '';
    var puntos = Number($('#avfeQuestionPuntos') ? $('#avfeQuestionPuntos').value : 0);
    var orden = Number($('#avfeQuestionOrden') ? $('#avfeQuestionOrden').value : 1) || 1;

    var rows = document.querySelectorAll('#avfeOptionsWrap .avfe-option-row');
    var opciones = [];
    for (var i = 0; i < rows.length; i++) {
      var txt = rows[i].querySelector('.avfe-opt-text');
      var ok = rows[i].querySelector('.avfe-opt-ok');
      var t = txt ? txt.value.trim() : '';
      if (!t) continue;
      opciones.push({
        texto: t,
        es_correcta: ok && ok.checked ? 1 : 0
      });
    }

    return {
      tipo: tipo,
      enunciado: enunciado,
      puntos: puntos,
      orden: orden,
      opciones: opciones
    };
  }

  async function saveQuestion() {
    if (state.formId <= 0) {
      notify('warning', 'Primero guarda la configuracion del formulario.');
      return;
    }
    var btn = $('#avfeQuestionSaveBtn');
    var p = readQuestionPayload();
    if (!p.enunciado) {
      notify('warning', 'El enunciado es obligatorio.');
      return;
    }
    if (p.puntos <= 0) {
      notify('warning', 'Los puntos deben ser mayores a 0.');
      return;
    }
    if (!p.opciones.length || p.opciones.length < 2) {
      notify('warning', 'Debes registrar al menos 2 opciones.');
      return;
    }

    setButtonLoading(btn, true, 'Guardando...');
    try {
      var payload = {
        tipo: p.tipo,
        enunciado: p.enunciado,
        puntos: p.puntos,
        orden: p.orden,
        opciones: JSON.stringify(p.opciones)
      };
      var data;
      if (state.editingQuestion && Number(state.editingQuestion.id || 0) > 0) {
        payload.question_id = Number(state.editingQuestion.id);
        data = await formStatePost('question_update', payload);
      } else {
        payload.form_id = state.formId;
        data = await formStatePost('question_add', payload);
      }
      notify('success', data.msg || 'Pregunta guardada.');
      hideModal('#avfeQuestionModal');
      await loadDetail();
      activateTab('preguntas');
    } catch (err) {
      notify('error', err.message || 'No se pudo guardar la pregunta.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  function openEditQuestion(questionId) {
    var q = state.questions.find(function (x) {
      return Number(x.id) === Number(questionId);
    }) || null;
    if (!q) return;
    state.editingQuestion = q;
    if ($('#avfeQuestionId')) $('#avfeQuestionId').value = String(q.id || 0);
    if ($('#avfeQuestionModalTitle')) $('#avfeQuestionModalTitle').textContent = 'Editar pregunta';
    if ($('#avfeQuestionTipo')) $('#avfeQuestionTipo').value = String(q.tipo || 'OM_UNICA');
    if ($('#avfeQuestionPuntos')) $('#avfeQuestionPuntos').value = String(q.puntos || 1);
    if ($('#avfeQuestionOrden')) $('#avfeQuestionOrden').value = String(q.orden || 1);
    if ($('#avfeQuestionEnunciado')) $('#avfeQuestionEnunciado').value = q.enunciado || '';
    var wrap = $('#avfeOptionsWrap');
    if (wrap) wrap.innerHTML = '';
    (q.opciones || []).forEach(function (o) {
      addOptionRow(o.texto || '', Number(o.es_correcta || 0));
    });
    if ((q.opciones || []).length < 2) {
      addOptionRow('', 0);
      addOptionRow('', 0);
    }
    showModal('#avfeQuestionModal');
  }

  async function deleteQuestion(questionId) {
    if (!window.confirm('Vas a eliminar esta pregunta. Deseas continuar?')) return;
    try {
      var data = await formStatePost('question_delete', {
        question_id: Number(questionId || 0)
      });
      notify('success', data.msg || 'Pregunta eliminada.');
      await loadDetail();
      activateTab('preguntas');
    } catch (err) {
      notify('error', err.message || 'No se pudo eliminar la pregunta.');
    }
  }

  async function deleteAttempt(attemptId) {
    if (state.formId <= 0 || attemptId <= 0) return;
    if (!window.confirm('Vas a eliminar fisicamente este intento. Deseas continuar?')) return;
    try {
      var data = await formStatePost('attempt_delete', {
        form_id: state.formId,
        attempt_id: attemptId
      });
      notify('success', data.msg || 'Intento eliminado.');
      await loadAttempts();
    } catch (err) {
      notify('error', err.message || 'No se pudo eliminar el intento.');
    }
  }

  async function autoSplitPoints() {
    if (state.formId <= 0) {
      notify('warning', 'Primero guarda la configuracion del formulario.');
      return;
    }
    if (!state.questions.length) {
      notify('warning', 'No hay preguntas para repartir.');
      return;
    }
    if (!window.confirm('Se repartiran 20 puntos entre todas las preguntas. Deseas continuar?')) return;

    var btn = $('#avfeAutoSplitBtn');
    setButtonLoading(btn, true, 'Repartiendo...');
    try {
      var total = 20;
      var n = state.questions.length;
      var base = Math.floor((total / n) * 100) / 100;
      var assigned = 0;

      for (var i = 0; i < n; i++) {
        var q = state.questions[i];
        var val = (i === n - 1) ? Number((total - assigned).toFixed(2)) : Number(base.toFixed(2));
        assigned = Number((assigned + val).toFixed(2));

        await formStatePost('question_update', {
          question_id: Number(q.id || 0),
          tipo: String(q.tipo || 'OM_UNICA'),
          enunciado: String(q.enunciado || ''),
          puntos: val,
          orden: Number(q.orden || (i + 1)),
          opciones: JSON.stringify((q.opciones || []).map(function (o) {
            return {
              texto: String(o.texto || ''),
              es_correcta: Number(o.es_correcta || 0) === 1 ? 1 : 0
            };
          }))
        });
      }

      notify('success', 'Puntos repartidos automaticamente a 20.');
      await loadDetail();
      activateTab('preguntas');
    } catch (err) {
      notify('error', err.message || 'No se pudo repartir puntos automaticamente.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  document.addEventListener('click', function (e) {
    var backBtn = e.target.closest('#avfeBackBtn');
    if (backBtn) {
      if (cfg.backUrl) location.href = cfg.backUrl;
      return;
    }

    var delBtn = e.target.closest('#avfeDeleteBtn');
    if (delBtn) {
      deleteForm();
      return;
    }

    var pubBtn = e.target.closest('#avfePublishBtn');
    if (pubBtn) {
      setEstado('PUBLICADO');
      return;
    }
    var closeBtn = e.target.closest('#avfeCloseBtn');
    if (closeBtn) {
      setEstado('CERRADO');
      return;
    }
    var draftBtn = e.target.closest('#avfeDraftBtn');
    if (draftBtn) {
      setEstado('BORRADOR');
      return;
    }

    var qNew = e.target.closest('#avfeQuestionNewBtn');
    if (qNew) {
      resetQuestionModal();
      showModal('#avfeQuestionModal');
      return;
    }

    var splitBtn = e.target.closest('#avfeAutoSplitBtn');
    if (splitBtn) {
      autoSplitPoints();
      return;
    }

    var addOpt = e.target.closest('#avfeOptionAddBtn');
    if (addOpt) {
      addOptionRow('', 0);
      return;
    }

    var delOpt = e.target.closest('.avfe-opt-del');
    if (delOpt) {
      var rows = document.querySelectorAll('#avfeOptionsWrap .avfe-option-row');
      if (rows.length <= 2) {
        notify('warning', 'Debes mantener al menos 2 opciones.');
        return;
      }
      var row = delOpt.closest('.avfe-option-row');
      if (row) row.remove();
      return;
    }

    var actBtn = e.target.closest('button[data-action][data-id]');
    if (!actBtn) return;
    var action = actBtn.dataset.action;
    var id = Number(actBtn.dataset.id || 0);
    if (id <= 0) return;

    if (action === 'edit-question') {
      openEditQuestion(id);
      return;
    }
    if (action === 'delete-question') {
      deleteQuestion(id);
      return;
    }
    if (action === 'delete-attempt') {
      deleteAttempt(id);
      return;
    }
  });

  document.addEventListener('change', function (e) {
    if (!e.target) return;

    if (e.target.id === 'avfeModo') {
      state.mode = String(e.target.value || 'FAST').toUpperCase();
      renderModeBlocks();
      return;
    }

    if (e.target.id === 'avfeGrupo') {
      var gid = Number(e.target.value || 0);
      if (gid > 0) {
        var g = state.catalog.grupos.find(function (x) {
          return Number(x.id) === gid;
        }) || null;
        if (g && $('#avfeCurso')) {
          $('#avfeCurso').value = String(Number(g.curso_id || 0));
          loadTemasByCurso(Number(g.curso_id || 0));
        }
      }
      return;
    }

    if (e.target.id === 'avfeCurso') {
      var cid = Number(e.target.value || 0);
      loadTemasByCurso(cid);
      return;
    }

    if (e.target.id === 'avfeQuestionTipo') {
      if (String(e.target.value || '') === 'OM_UNICA') {
        var checks0 = document.querySelectorAll('#avfeOptionsWrap .avfe-opt-ok');
        var seen = false;
        for (var c0 = 0; c0 < checks0.length; c0++) {
          if (checks0[c0].checked && !seen) {
            seen = true;
            continue;
          }
          if (seen) checks0[c0].checked = false;
        }
      }
      return;
    }

    if (e.target.classList.contains('avfe-opt-ok')) {
      var tipo = $('#avfeQuestionTipo') ? String($('#avfeQuestionTipo').value || 'OM_UNICA') : 'OM_UNICA';
      if (tipo === 'OM_UNICA' && e.target.checked) {
        var checks = document.querySelectorAll('#avfeOptionsWrap .avfe-opt-ok');
        for (var i = 0; i < checks.length; i++) {
          if (checks[i] !== e.target) checks[i].checked = false;
        }
      }
      return;
    }
  });

  var configForm = $('#avfeConfigForm');
  if (configForm) {
    configForm.addEventListener('submit', function (e) {
      e.preventDefault();
      saveConfig();
    });
  }

  var questionForm = $('#avfeQuestionForm');
  if (questionForm) {
    questionForm.addEventListener('submit', function (e) {
      e.preventDefault();
      saveQuestion();
    });
  }

  var reloadAttempts = $('#avfeReloadAttemptsBtn');
  if (reloadAttempts) {
    reloadAttempts.addEventListener('click', function () {
      loadAttempts().catch(function (err) {
        notify('error', err.message || 'No se pudieron recargar intentos.');
      });
    });
  }

  Promise.resolve()
    .then(function () { return loadCatalog(); })
    .then(function () { return loadDetail(); })
    .then(function () { activateTab(state.initialTab); })
    .catch(function (err) {
      notify('error', err.message || 'No se pudo cargar el editor.');
    });
})();
