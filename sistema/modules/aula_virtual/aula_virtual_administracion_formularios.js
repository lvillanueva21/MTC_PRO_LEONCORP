// Ver 08-03-26
(function () {
  var root = document.getElementById('avAdminFormsRoot');
  if (!root) return;

  var cfg = window.avAdminFormsConfig || {};
  var apiUrl = cfg.apiUrl || '';
  var editorUrl = cfg.editorUrl || '';
  var groupsUrl = cfg.groupsUrl || '';
  var perPage = Number(cfg.perPage || 10);
  if (!apiUrl) return;

  var state = {
    catalog: {
      grupos: [],
      cursos: [],
      temas: []
    },
    fast: {
      q: '',
      estado: '',
      page: 1,
      per_page: perPage,
      total: 0,
      rows: []
    },
    aula: {
      grupo_id: 0,
      q: '',
      curso_id: 0,
      tema_id: 0,
      estado: '',
      rows: []
    },
    selectedFast: null,
    selectedFastShare: null
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

  function debounce(fn, delay) {
    var t = null;
    return function () {
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(null, args);
      }, delay);
    };
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

  function setButtonLoading(btn, loading, text) {
    if (!btn) return;
    if (loading) {
      btn.dataset.oldText = btn.textContent || '';
      btn.disabled = true;
      btn.textContent = text || 'Procesando...';
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

  function estadoBadge(estado) {
    var e = String(estado || '').toUpperCase();
    if (e === 'PUBLICADO') return '<span class="badge badge-success avf-state-badge">PUBLICADO</span>';
    if (e === 'CERRADO') return '<span class="badge badge-secondary avf-state-badge">CERRADO</span>';
    return '<span class="badge badge-warning avf-state-badge">BORRADOR</span>';
  }

  function nextEstado(current) {
    var c = String(current || '').toUpperCase();
    if (c === 'BORRADOR') return 'PUBLICADO';
    if (c === 'PUBLICADO') return 'CERRADO';
    return 'PUBLICADO';
  }

  function nextEstadoLabel(current) {
    var n = nextEstado(current);
    if (n === 'PUBLICADO') return 'Publicar';
    if (n === 'CERRADO') return 'Cerrar';
    return 'Borrador';
  }

  function openEditor(query) {
    if (!editorUrl) {
      notify('error', 'No se pudo ubicar la URL del editor.');
      return;
    }
    var qs = new URLSearchParams(query || {});
    location.href = editorUrl + (qs.toString() ? ('?' + qs.toString()) : '');
  }

  function buildFastPager() {
    var pager = $('#avfFastPager');
    if (!pager) return;

    var pages = Math.max(1, Math.ceil((state.fast.total || 0) / state.fast.per_page));
    if (state.fast.page > pages) state.fast.page = pages;
    var cur = state.fast.page;
    var html = [];

    function add(page, label, cls) {
      html.push(
        '<li class="page-item ' + (cls || '') + '">' +
          '<a class="page-link" href="#" data-fast-page="' + page + '">' + label + '</a>' +
        '</li>'
      );
    }

    add(cur - 1, '&laquo;', cur <= 1 ? 'disabled' : '');
    var start = Math.max(1, cur - 2);
    var end = Math.min(pages, start + 4);
    start = Math.max(1, end - 4);
    for (var p = start; p <= end; p++) {
      add(p, String(p), p === cur ? 'active' : '');
    }
    add(cur + 1, '&raquo;', cur >= pages ? 'disabled' : '');
    pager.innerHTML = html.join('');
  }

  function renderFastRows() {
    var tbody = $('#avfFastTbody');
    if (!tbody) return;

    if (!state.fast.rows.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="ava-empty">No se encontraron formularios FAST.</td></tr>';
      return;
    }

    var selectedId = Number(state.selectedFast ? state.selectedFast.id : 0);
    tbody.innerHTML = state.fast.rows.map(function (r) {
      var id = Number(r.id || 0);
      var active = id === selectedId ? 'table-primary' : '';
      var puntos = Number(r.puntos_total || 0);
      var intentos = Number(r.intentos_enviados || 0) + '/' + Number(r.intentos_total || 0);
      return (
        '<tr class="' + active + '">' +
          '<td>' +
            '<div class="avf-row-title">' + esc(r.titulo || 'Formulario') + '</div>' +
            '<div class="avf-row-meta">' + esc(r.descripcion || 'Sin descripcion') + '</div>' +
            '<div class="mt-1">' + estadoBadge(r.estado) + '</div>' +
          '</td>' +
          '<td class="text-center">' + puntos.toFixed(2).replace(/\.00$/, '') + '</td>' +
          '<td class="text-center">' + esc(intentos) + '</td>' +
          '<td>' +
            '<div class="avf-actions">' +
              '<button type="button" class="btn btn-sm btn-outline-primary" data-action="fast-select" data-id="' + id + '">Compartir</button>' +
              '<button type="button" class="btn btn-sm btn-primary" data-action="fast-edit" data-id="' + id + '">Editar</button>' +
              '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="fast-results" data-id="' + id + '">Resultados</button>' +
              '<button type="button" class="btn btn-sm btn-outline-success" data-action="fast-state" data-id="' + id + '" data-estado="' + esc(String(r.estado || '')) + '">' + esc(nextEstadoLabel(r.estado)) + '</button>' +
            '</div>' +
          '</td>' +
        '</tr>'
      );
    }).join('');
  }

  function renderAulaRows() {
    var tbody = $('#avfAulaTbody');
    if (!tbody) return;

    if (state.aula.grupo_id <= 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="ava-empty">Selecciona un grupo para listar examenes AULA.</td></tr>';
      return;
    }
    if (!state.aula.rows.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="ava-empty">No se encontraron examenes AULA para este grupo.</td></tr>';
      return;
    }

    tbody.innerHTML = state.aula.rows.map(function (r) {
      var puntos = Number(r.puntos_total || 0);
      var intentos = Number(r.intentos_enviados || 0) + '/' + Number(r.intentos_total || 0);
      return (
        '<tr>' +
          '<td>' +
            '<div class="avf-row-title">' + esc(r.titulo || 'Formulario') + '</div>' +
            '<div class="avf-row-meta">' + esc((r.curso_nombre || '-') + ' / ' + (r.tema_titulo || 'Sin tema')) + '</div>' +
            '<div class="mt-1">' + estadoBadge(r.estado) + '</div>' +
          '</td>' +
          '<td class="text-center">' + puntos.toFixed(2).replace(/\.00$/, '') + '</td>' +
          '<td class="text-center">' + esc(intentos) + '</td>' +
          '<td>' +
            '<div class="avf-actions">' +
              '<button type="button" class="btn btn-sm btn-primary" data-action="aula-edit" data-id="' + r.id + '">Editar</button>' +
              '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="aula-results" data-id="' + r.id + '">Resultados</button>' +
              '<button type="button" class="btn btn-sm btn-outline-success" data-action="aula-state" data-id="' + r.id + '" data-estado="' + esc(String(r.estado || '')) + '">' + esc(nextEstadoLabel(r.estado)) + '</button>' +
            '</div>' +
          '</td>' +
        '</tr>'
      );
    }).join('');
  }

  function renderGroupsAndFilters() {
    var groupSel = $('#avfAulaGrupo');
    var noGroupsHelp = $('#avfNoGroupsHelp');
    var goGroupsBtn = $('#avfGoGroupsBtn');
    var aulaNewBtn = $('#avfAulaNewBtn');
    var aulaCurso = $('#avfAulaCurso');
    var aulaTema = $('#avfAulaTema');

    if (groupSel) {
      var groupOpts = ['<option value="0">Selecciona un grupo</option>'];
      state.catalog.grupos.forEach(function (g) {
        var code = g.codigo ? ('[' + g.codigo + '] ') : '';
        groupOpts.push('<option value="' + g.id + '">' + esc(code + (g.nombre || 'Grupo')) + '</option>');
      });
      groupSel.innerHTML = groupOpts.join('');
      groupSel.value = String(state.aula.grupo_id || 0);
    }

    var hasGroups = state.catalog.grupos.length > 0;
    if (noGroupsHelp) noGroupsHelp.classList.toggle('d-none', hasGroups);
    if (goGroupsBtn) goGroupsBtn.classList.toggle('d-none', hasGroups);
    if (aulaNewBtn) aulaNewBtn.disabled = !hasGroups;

    if (aulaCurso) {
      var cursoOpts = ['<option value="0">Todos</option>'];
      state.catalog.cursos.forEach(function (c) {
        cursoOpts.push('<option value="' + c.id + '">' + esc(c.nombre || ('Curso ' + c.id)) + '</option>');
      });
      aulaCurso.innerHTML = cursoOpts.join('');
      aulaCurso.value = String(state.aula.curso_id || 0);
    }

    if (aulaTema) {
      var temaOpts = ['<option value="0">Todos</option>'];
      state.catalog.temas.forEach(function (t) {
        temaOpts.push('<option value="' + t.id + '">' + esc(t.titulo || ('Tema ' + t.id)) + '</option>');
      });
      aulaTema.innerHTML = temaOpts.join('');
      aulaTema.value = String(state.aula.tema_id || 0);
    }
  }

  function renderSharePanel() {
    var selected = state.selectedFast;
    var selectedLabel = $('#avfShareSelected');
    var linkInput = $('#avfShareLink');
    var qrBtn = $('#avfShareQrBtn');
    var copyBtn = $('#avfShareCopyBtn');
    var waBtn = $('#avfWaBtn');

    if (!selected) {
      if (selectedLabel) selectedLabel.textContent = 'Ninguno seleccionado';
      if (linkInput) linkInput.value = '';
      if (qrBtn) qrBtn.disabled = true;
      if (copyBtn) copyBtn.disabled = true;
      if (waBtn) waBtn.disabled = true;
      return;
    }

    if (selectedLabel) {
      selectedLabel.textContent = (selected.titulo || 'Formulario') + ' (ID ' + selected.id + ')';
    }
    if (linkInput) linkInput.value = state.selectedFastShare ? (state.selectedFastShare.link || '') : '';
    if (qrBtn) qrBtn.disabled = !state.selectedFastShare;
    if (copyBtn) copyBtn.disabled = !state.selectedFastShare;
    if (waBtn) waBtn.disabled = !state.selectedFastShare;
  }

  async function loadCatalog(cursoIdForTemas) {
    var qs = new URLSearchParams({ action: 'catalog_data' });
    if (cursoIdForTemas && Number(cursoIdForTemas) > 0) {
      qs.set('curso_id', String(cursoIdForTemas));
    }
    var data = await request(apiUrl + '?' + qs.toString());
    var d = data.data || {};
    state.catalog.grupos = d.grupos || [];
    state.catalog.cursos = d.cursos || [];
    state.catalog.temas = d.temas || [];
    renderGroupsAndFilters();
  }

  async function loadTemasByCurso(cursoId) {
    var aulaTema = $('#avfAulaTema');
    if (Number(cursoId || 0) <= 0) {
      state.catalog.temas = [];
      if (aulaTema) {
        aulaTema.innerHTML = '<option value="0">Todos</option>';
        aulaTema.value = '0';
      }
      state.aula.tema_id = 0;
      return;
    }
    var qs = new URLSearchParams({
      action: 'temas_by_curso',
      curso_id: String(cursoId)
    });
    var data = await request(apiUrl + '?' + qs.toString());
    state.catalog.temas = data.data || [];
    if (aulaTema) {
      var opts = ['<option value="0">Todos</option>'];
      state.catalog.temas.forEach(function (t) {
        opts.push('<option value="' + t.id + '">' + esc(t.titulo || ('Tema ' + t.id)) + '</option>');
      });
      aulaTema.innerHTML = opts.join('');
      aulaTema.value = String(state.aula.tema_id || 0);
    }
  }

  async function loadFast() {
    var qs = new URLSearchParams({
      action: 'forms_fast_list',
      q: state.fast.q || '',
      estado: state.fast.estado || '',
      page: String(state.fast.page || 1),
      per_page: String(state.fast.per_page || perPage)
    });
    var data = await request(apiUrl + '?' + qs.toString());
    state.fast.rows = data.data || [];
    state.fast.total = Number(data.total || 0);
    state.fast.page = Number(data.page || state.fast.page || 1);

    if (state.selectedFast) {
      var found = state.fast.rows.find(function (r) {
        return Number(r.id || 0) === Number(state.selectedFast.id || 0);
      });
      if (found) state.selectedFast = found;
    }

    renderFastRows();
    buildFastPager();
    renderSharePanel();
  }

  async function loadAula() {
    if (state.aula.grupo_id <= 0) {
      state.aula.rows = [];
      renderAulaRows();
      return;
    }
    var qs = new URLSearchParams({
      action: 'forms_aula_list',
      grupo_id: String(state.aula.grupo_id),
      q: state.aula.q || '',
      curso_id: String(state.aula.curso_id || 0),
      tema_id: String(state.aula.tema_id || 0),
      estado: state.aula.estado || ''
    });
    var data = await request(apiUrl + '?' + qs.toString());
    state.aula.rows = data.data || [];
    renderAulaRows();
  }

  async function selectFast(formId) {
    var row = state.fast.rows.find(function (r) { return Number(r.id) === Number(formId); }) || null;
    if (!row) {
      notify('warning', 'No se encontro el formulario FAST seleccionado.');
      return;
    }
    state.selectedFast = row;
    renderFastRows();
    renderSharePanel();

    try {
      var qs = new URLSearchParams({
        action: 'form_share_info',
        form_id: String(formId)
      });
      var data = await request(apiUrl + '?' + qs.toString());
      state.selectedFastShare = data.data || null;
      renderSharePanel();
    } catch (err) {
      state.selectedFastShare = null;
      renderSharePanel();
      notify('error', err.message || 'No se pudo cargar informacion de compartir.');
    }
  }

  async function setFormEstado(formId, estado) {
    var fd = new FormData();
    fd.append('action', 'form_set_estado');
    fd.append('form_id', String(formId));
    fd.append('estado', String(estado || ''));
    var data = await request(apiUrl, { method: 'POST', body: fd });
    notify('success', data.msg || 'Estado actualizado.');
    await loadFast();
    await loadAula();
  }

  async function refreshAll() {
    clearNotify();
    await loadCatalog(state.aula.curso_id);
    await loadFast();
    await loadAula();
  }

  root.addEventListener('click', function (e) {
    var newFast = e.target.closest('#avfFastNewBtn');
    if (newFast) {
      openEditor({ modo: 'FAST' });
      return;
    }

    var newAula = e.target.closest('#avfAulaNewBtn');
    if (newAula) {
      if (state.aula.grupo_id <= 0) {
        notify('warning', 'Selecciona un grupo para crear un formulario AULA.');
        return;
      }
      openEditor({ modo: 'AULA', grupo_id: String(state.aula.grupo_id) });
      return;
    }

    var refreshFast = e.target.closest('#avfFastRefresh');
    if (refreshFast) {
      loadFast().catch(function (err) {
        notify('error', err.message || 'No se pudo recargar FAST.');
      });
      return;
    }

    var refreshAula = e.target.closest('#avfAulaRefresh');
    if (refreshAula) {
      loadAula().catch(function (err) {
        notify('error', err.message || 'No se pudo recargar AULA.');
      });
      return;
    }

    var goGroups = e.target.closest('#avfGoGroupsBtn');
    if (goGroups) {
      if (groupsUrl) location.href = groupsUrl;
      return;
    }

    var qrBtn = e.target.closest('#avfShareQrBtn');
    if (qrBtn) {
      if (!state.selectedFastShare || !state.selectedFastShare.qr_url) {
        notify('warning', 'Selecciona un formulario FAST para ver su QR.');
        return;
      }
      var img = $('#avfQrImg');
      var code = $('#avfQrCode');
      if (img) img.src = state.selectedFastShare.qr_url + '&_=' + Date.now();
      if (code) code.textContent = state.selectedFastShare.code || '-';
      showModal('#avfQrModal');
      return;
    }

    var copyBtn = e.target.closest('#avfShareCopyBtn');
    if (copyBtn) {
      var link = state.selectedFastShare ? String(state.selectedFastShare.link || '') : '';
      if (!link) {
        notify('warning', 'Selecciona un formulario FAST para copiar el link.');
        return;
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(function () {
          notify('success', 'Link copiado al portapapeles.');
        }).catch(function () {
          notify('warning', 'No se pudo copiar automaticamente. Copialo manualmente.');
        });
      } else {
        var input = $('#avfShareLink');
        if (input) {
          input.focus();
          input.select();
        }
        notify('warning', 'Tu navegador no soporta copiado automatico. Copialo manualmente.');
      }
      return;
    }

    var waBtn = e.target.closest('#avfWaBtn');
    if (waBtn) {
      if (!state.selectedFastShare || !state.selectedFastShare.link) {
        notify('warning', 'Selecciona un formulario FAST para compartir por WhatsApp.');
        return;
      }
      var phone = ($('#avfWaPhone') && $('#avfWaPhone').value) ? $('#avfWaPhone').value.trim() : '';
      if (!/^9\d{8}$/.test(phone)) {
        notify('warning', 'Ingresa un celular valido de Peru (9 digitos iniciando en 9).');
        return;
      }
      var title = state.selectedFast ? (state.selectedFast.titulo || 'Examen') : 'Examen';
      var text = 'Hola, te compartimos el formulario "' + title + '". Ingresa aqui: ' + state.selectedFastShare.link;
      var waUrl = 'https://wa.me/51' + phone + '?text=' + encodeURIComponent(text);
      window.open(waUrl, '_blank');
      return;
    }

    var pagerLink = e.target.closest('#avfFastPager a[data-fast-page]');
    if (pagerLink) {
      e.preventDefault();
      var li = pagerLink.parentElement;
      if (li && (li.classList.contains('active') || li.classList.contains('disabled'))) return;
      var p = Number(pagerLink.dataset.fastPage || 1);
      if (p > 0) {
        state.fast.page = p;
        loadFast().catch(function (err) {
          notify('error', err.message || 'No se pudo cambiar pagina.');
        });
      }
      return;
    }

    var actionBtn = e.target.closest('button[data-action][data-id]');
    if (!actionBtn) return;

    var action = actionBtn.dataset.action;
    var id = Number(actionBtn.dataset.id || 0);
    if (id <= 0) return;

    if (action === 'fast-select') {
      selectFast(id);
      return;
    }

    if (action === 'fast-edit' || action === 'aula-edit') {
      openEditor({ id: String(id) });
      return;
    }

    if (action === 'fast-results' || action === 'aula-results') {
      openEditor({ id: String(id), tab: 'attempts' });
      return;
    }

    if (action === 'fast-state' || action === 'aula-state') {
      var current = actionBtn.dataset.estado || '';
      var next = nextEstado(current);
      var label = nextEstadoLabel(current);
      if (!window.confirm('Vas a cambiar el estado del formulario a "' + label + '". Deseas continuar?')) {
        return;
      }
      setButtonLoading(actionBtn, true, '...');
      setFormEstado(id, next).catch(function (err) {
        notify('error', err.message || 'No se pudo cambiar el estado.');
      }).finally(function () {
        setButtonLoading(actionBtn, false);
      });
      return;
    }
  });

  var onFastQ = debounce(function (ev) {
    state.fast.q = (ev.target.value || '').trim();
    state.fast.page = 1;
    loadFast().catch(function (err) { notify('error', err.message || 'No se pudo filtrar FAST.'); });
  }, 300);

  var onAulaQ = debounce(function (ev) {
    state.aula.q = (ev.target.value || '').trim();
    loadAula().catch(function (err) { notify('error', err.message || 'No se pudo filtrar AULA.'); });
  }, 300);

  root.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'avfFastQ') onFastQ(e);
    if (e.target && e.target.id === 'avfAulaQ') onAulaQ(e);
  });

  root.addEventListener('change', function (e) {
    if (!e.target) return;
    if (e.target.id === 'avfFastEstado') {
      state.fast.estado = String(e.target.value || '');
      state.fast.page = 1;
      loadFast().catch(function (err) { notify('error', err.message || 'No se pudo filtrar FAST por estado.'); });
      return;
    }
    if (e.target.id === 'avfAulaGrupo') {
      state.aula.grupo_id = Number(e.target.value || 0);
      loadAula().catch(function (err) { notify('error', err.message || 'No se pudo listar AULA.'); });
      return;
    }
    if (e.target.id === 'avfAulaEstado') {
      state.aula.estado = String(e.target.value || '');
      loadAula().catch(function (err) { notify('error', err.message || 'No se pudo filtrar AULA por estado.'); });
      return;
    }
    if (e.target.id === 'avfAulaCurso') {
      state.aula.curso_id = Number(e.target.value || 0);
      state.aula.tema_id = 0;
      loadTemasByCurso(state.aula.curso_id).then(function () {
        return loadAula();
      }).catch(function (err) {
        notify('error', err.message || 'No se pudo filtrar AULA por curso.');
      });
      return;
    }
    if (e.target.id === 'avfAulaTema') {
      state.aula.tema_id = Number(e.target.value || 0);
      loadAula().catch(function (err) { notify('error', err.message || 'No se pudo filtrar AULA por tema.'); });
      return;
    }
  });

  renderSharePanel();
  renderAulaRows();
  refreshAll().catch(function (err) {
    notify('error', err.message || 'No se pudo inicializar Formularios.');
  });
})();
