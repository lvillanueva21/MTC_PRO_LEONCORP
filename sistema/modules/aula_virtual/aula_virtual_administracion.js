// Ver 07-03-26
(function () {
  const root = document.getElementById('avAdminRoot');
  if (!root) return;

  const config = window.avAdminConfig || {};
  const apiUrl = config.apiUrl || '';
  if (!apiUrl) return;

  const perPage = Number(config.perPage || 10);
  const PROJECT_ROOT = (location.pathname.split('/modules/')[0] || '');
  const defaultAvatar = root.getAttribute('data-default-avatar') || (PROJECT_ROOT ? (PROJECT_ROOT + '/dist/img/user2-160x160.jpg') : '/dist/img/user2-160x160.jpg');

  const state = {
    filters: {
      q: '',
      curso_id: 0,
      page: 1,
      per_page: perPage,
    },
    clients: [],
    total: 0,
    courses: [],
    matriculas: [],
    selectedClient: null,
    editId: 0,
    formBasePhoto: '',
    enrollCourse: null,
    expelTarget: null,
    availableCourseQuery: '',
  };

  function $(sel) {
    return root.querySelector(sel) || document.querySelector(sel);
  }

  function esc(text) {
    return String(text ?? '').replace(/[&<>"']/g, function (m) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      }[m];
    });
  }

  function normalizeText(text) {
    const base = String(text ?? '').toLowerCase();
    if (typeof base.normalize === 'function') {
      return base.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }
    return base.trim();
  }

  function debounce(fn, delay) {
    let t = null;
    return function () {
      const args = arguments;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(null, args);
      }, delay);
    };
  }

  function fileNameFromPath(path) {
    const p = String(path || '');
    if (!p) return '';
    return p.split('/').pop().split('\\').pop();
  }

  function fotoUrl(path) {
    const raw = String(path || '').trim();
    if (!raw) return '';
    if (/^(https?:)?\/\//i.test(raw) || raw.startsWith('data:')) return raw;
    if (raw.startsWith('/')) return raw;
    return (PROJECT_ROOT ? PROJECT_ROOT : '') + '/' + raw.replace(/^\/+/, '');
  }

  function showModal(id) {
    if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(id).modal('show');
      return;
    }
    const el = document.querySelector(id);
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
    const el = document.querySelector(id);
    if (!el) return;
    el.style.display = 'none';
    el.classList.remove('show');
    el.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }

  function formatDateTimeHuman(raw) {
    const src = String(raw || '').trim();
    if (!src) return '';
    const norm = src.replace(' ', 'T');
    const d = new Date(norm);
    if (Number.isNaN(d.getTime())) return src;
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const mi = String(d.getMinutes()).padStart(2, '0');
    return dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;
  }

  function scheduleLabel(inicioAt, finAt) {
    if (inicioAt && finAt) {
      return formatDateTimeHuman(inicioAt) + ' - ' + formatDateTimeHuman(finAt);
    }
    return 'Indefinido';
  }

  function setPhotoPreview(url) {
    const prev = $('#avaFotoPrev');
    if (!prev) return;
    const safe = String(url || '').replace(/"/g, '\\"');
    prev.style.backgroundImage = 'url("' + safe + '")';
  }

  function setPhotoCaption(text) {
    const cap = $('#avaFotoCap');
    if (cap) cap.textContent = text || 'Sin foto por el momento';
  }

  function setPhotoSize(bytes) {
    const size = $('#avaFotoSize');
    if (!size) return;
    if (!bytes) {
      size.textContent = '';
      return;
    }
    size.textContent = 'Peso de archivo cargado: ' + Math.round(Number(bytes || 0) / 1024) + ' KB';
  }

  function refreshPhotoPreviewFromBase() {
    if (state.formBasePhoto) {
      setPhotoPreview(state.formBasePhoto);
      setPhotoCaption(fileNameFromPath(state.formBasePhoto));
      setPhotoSize(0);
      return;
    }
    setPhotoPreview(defaultAvatar);
    setPhotoCaption('Sin foto por el momento');
    setPhotoSize(0);
  }

  function clearPhotoInput() {
    const input = $('#avaFoto');
    if (input) input.value = '';
  }

  function handlePhotoInputChange(input) {
    const file = input && input.files ? input.files[0] : null;
    if (!file) {
      refreshPhotoPreviewFromBase();
      return;
    }

    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      input.value = '';
      refreshPhotoPreviewFromBase();
      notify('warning', 'La foto debe ser JPG, PNG o WEBP.');
      return;
    }
    if (file.size > (4 * 1024 * 1024)) {
      input.value = '';
      refreshPhotoPreviewFromBase();
      notify('warning', 'La foto no puede superar 4MB.');
      return;
    }

    const objectUrl = URL.createObjectURL(file);
    setPhotoPreview(objectUrl);
    setPhotoCaption(file.name || 'archivo');
    setPhotoSize(file.size || 0);
    setTimeout(function () {
      URL.revokeObjectURL(objectUrl);
    }, 5000);
  }

  async function request(url, opts) {
    const res = await fetch(url, Object.assign({ credentials: 'same-origin' }, opts || {}));
    const data = await res.json().catch(function () {
      return { ok: false, msg: 'Respuesta invalida del servidor.' };
    });
    if (!res.ok || !data.ok) {
      throw new Error(data.msg || ('HTTP ' + res.status));
    }
    return data;
  }

  function notify(type, msg) {
    const el = $('#avaNotice');
    if (!el) return;
    el.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');

    let klass = 'alert-info';
    if (type === 'success') klass = 'alert-success';
    if (type === 'error') klass = 'alert-danger';
    if (type === 'warning') klass = 'alert-warning';

    el.classList.add('alert', klass);
    el.textContent = msg;
  }

  function clearNotify() {
    const el = $('#avaNotice');
    if (!el) return;
    el.classList.add('d-none');
    el.textContent = '';
  }

  function setButtonLoading(btn, loading, loadingText) {
    if (!btn) return;
    if (loading) {
      btn.dataset.oldText = btn.textContent || '';
      btn.textContent = loadingText || 'Procesando...';
      btn.disabled = true;
      return;
    }
    btn.disabled = false;
    if (btn.dataset.oldText) btn.textContent = btn.dataset.oldText;
  }

  function renderFilterCourses() {
    const sel = $('#avaFilterCourse');
    if (!sel) return;

    const current = String(state.filters.curso_id || 0);
    const options = ['<option value="0">Todos los cursos</option>'];
    state.courses.forEach(function (c) {
      options.push('<option value="' + c.id + '">' + esc(c.nombre) + '</option>');
    });
    sel.innerHTML = options.join('');
    sel.value = current;
    const opt = sel.options[sel.selectedIndex];
    sel.title = opt ? opt.text : '';
  }

  function renderPager() {
    const ul = $('#avaPager');
    if (!ul) return;

    const pages = Math.max(1, Math.ceil((state.total || 0) / state.filters.per_page));
    state.filters.page = Math.min(state.filters.page, pages);

    const cur = state.filters.page;
    const items = [];

    function add(page, label, cls) {
      items.push(
        '<li class="page-item ' + (cls || '') + '">' +
          '<a class="page-link" href="#" data-page="' + page + '">' + label + '</a>' +
        '</li>'
      );
    }

    add(cur - 1, '&laquo;', cur <= 1 ? 'disabled' : '');
    let start = Math.max(1, cur - 2);
    let end = Math.min(pages, start + 4);
    start = Math.max(1, end - 4);
    for (let p = start; p <= end; p++) {
      add(p, String(p), p === cur ? 'active' : '');
    }
    add(cur + 1, '&raquo;', cur >= pages ? 'disabled' : '');

    ul.innerHTML = items.join('');
  }

  function getClientById(id) {
    return state.clients.find(function (c) { return Number(c.id) === Number(id); }) || null;
  }

  function getCourseById(id) {
    return state.courses.find(function (c) { return Number(c.id) === Number(id); }) || null;
  }

  function activeMatriculaMap() {
    const map = new Map();
    (state.matriculas || []).forEach(function (m) {
      if (Number(m.estado || 0) === 1) {
        map.set(Number(m.curso_id), m);
      }
    });
    return map;
  }

  function renderSelectedClient() {
    const label = $('#avaSelectedClientLabel');
    const mini = $('#avaSelectedClientMini');

    if (!state.selectedClient) {
      if (label) label.textContent = 'Ninguno seleccionado';
      if (mini) mini.textContent = 'Ninguno seleccionado';
      return;
    }

    const fullName = ((state.selectedClient.nombres || '') + ' ' + (state.selectedClient.apellidos || '')).trim();
    const doc = state.selectedClient.usuario || '';
    const text = fullName + (doc ? ' (' + doc + ')' : '');

    if (label) label.textContent = text;
    if (mini) mini.textContent = text;
  }

  function renderCourseLists() {
    const availableBox = $('#avaAvailableList');
    const matriculasBox = $('#avaMatriculasList');
    const availableEmpty = $('#avaAvailableEmpty');
    const matriculasEmpty = $('#avaMatriculasEmpty');
    const availableSearch = $('#avaAvailableCourseSearch');

    if (!availableBox || !matriculasBox || !availableEmpty || !matriculasEmpty) return;
    if (availableSearch) availableSearch.disabled = !state.selectedClient;

    if (!state.selectedClient) {
      availableBox.innerHTML = '';
      matriculasBox.innerHTML = '';
      availableEmpty.classList.remove('d-none');
      matriculasEmpty.classList.remove('d-none');
      availableEmpty.textContent = 'Selecciona un cliente para ver cursos disponibles.';
      matriculasEmpty.textContent = 'Selecciona un cliente para ver sus matriculas.';
      return;
    }

    const activeMap = activeMatriculaMap();
    const query = normalizeText(state.availableCourseQuery || '');
    const filteredCourses = (state.courses || []).filter(function (c) {
      if (!query) return true;
      return normalizeText(c.nombre || '').indexOf(query) !== -1;
    });
    const availableRows = [];
    filteredCourses.forEach(function (c) {
      const m = activeMap.get(Number(c.id));
      if (m) {
        const gname = m.grupo_nombre || ('Grupo #' + (m.grupo_id || 0));
        availableRows.push(
          '<div class="ava-course-item ava-course-item--taken">' +
            '<div class="title">' + esc(c.nombre) + '</div>' +
            '<div class="ava-course-actions">' +
              '<span class="badge badge-success">Ya matriculado en ' + esc(gname) + '</span>' +
            '</div>' +
          '</div>'
        );
      } else {
        availableRows.push(
          '<div class="ava-course-item">' +
            '<div class="title">' + esc(c.nombre) + '</div>' +
            '<div class="ava-course-actions">' +
              '<button type="button" class="btn btn-sm btn-primary" data-action="open-enroll" data-course-id="' + c.id + '">Matricular</button>' +
            '</div>' +
          '</div>'
        );
      }
    });

    if (!availableRows.length) {
      availableBox.innerHTML = '';
      availableEmpty.classList.remove('d-none');
      availableEmpty.textContent = query
        ? 'No hay cursos que coincidan con la busqueda.'
        : 'No hay cursos activos disponibles.';
    } else {
      availableEmpty.classList.add('d-none');
      availableBox.innerHTML = availableRows.join('');
    }

    if (!state.matriculas.length) {
      matriculasBox.innerHTML = '';
      matriculasEmpty.classList.remove('d-none');
      matriculasEmpty.textContent = 'Este cliente aun no tiene matriculas.';
    } else {
      matriculasEmpty.classList.add('d-none');
      matriculasBox.innerHTML = state.matriculas.map(function (m) {
        const active = Number(m.estado || 0) === 1;
        const badge = active ? '<span class="badge badge-success">ACTIVO</span>' : '<span class="badge badge-secondary">EXPULSADO</span>';
        const gname = m.grupo_nombre || ('Grupo #' + (m.grupo_id || 0));
        const code = m.grupo_codigo ? ' (' + m.grupo_codigo + ')' : '';
        const range = m.rango_text || scheduleLabel(m.grupo_inicio_at, m.grupo_fin_at);
        const expelBtn = active
          ? '<button type="button" class="btn btn-sm btn-outline-danger" data-action="open-expel" data-curso-id="' + m.curso_id + '">Expulsar</button>'
          : '';
        return (
          '<div class="ava-course-item ava-course-item--matricula">' +
            '<div class="title">' + esc(m.curso_nombre || '') + '</div>' +
            '<div class="ava-course-meta">' +
              '<div><strong>Grupo:</strong> ' + esc(gname) + esc(code) + '</div>' +
              '<div><strong>Rango:</strong> ' + esc(range) + '</div>' +
            '</div>' +
            '<div class="ava-course-actions">' +
              badge +
              expelBtn +
            '</div>' +
          '</div>'
        );
      }).join('');
    }
  }

  function renderClientTable() {
    const tbody = $('#avaClientTbody');
    const count = $('#avaClientCount');
    if (!tbody) return;

    if (count) count.textContent = String(state.total || 0);

    if (!state.clients.length) {
      tbody.innerHTML = '<tr><td colspan="3" class="ava-empty">No se encontraron clientes con esos filtros.</td></tr>';
      return;
    }

    tbody.innerHTML = state.clients.map(function (c) {
      const fullName = ((c.nombres || '') + ' ' + (c.apellidos || '')).trim();
      const avatar = fotoUrl(c.foto || '') || defaultAvatar;
      return (
        '<tr>' +
          '<td>' +
            '<div class="ava-client-row">' +
              '<img class="ava-client-avatar" src="' + esc(avatar) + '" alt="Foto de ' + esc(fullName || 'cliente') + '">' +
              '<div class="ava-client">' +
                '<div class="name">' + esc(fullName || 'Sin nombre') + '</div>' +
                '<div class="doc">Documento: ' + esc(c.usuario || '-') + '</div>' +
              '</div>' +
            '</div>' +
          '</td>' +
          '<td class="text-center">' + Number(c.cursos_count || 0) + '</td>' +
          '<td>' +
            '<div class="ava-actions">' +
              '<button type="button" class="btn btn-sm btn-outline-primary" data-action="select-client" data-id="' + c.id + '">Matriculas</button>' +
              '<button type="button" class="btn btn-sm btn-primary" data-action="edit-client" data-id="' + c.id + '">Editar</button>' +
              '<button type="button" class="btn btn-sm btn-danger" data-action="delete-client" data-id="' + c.id + '">Eliminar</button>' +
            '</div>' +
          '</td>' +
        '</tr>'
      );
    }).join('');
  }

  function resetForm() {
    state.editId = 0;
    state.formBasePhoto = '';

    const form = $('#avaForm');
    if (form) form.reset();
    const hidden = $('#avaClientId');
    if (hidden) hidden.value = '0';

    clearPhotoInput();
    refreshPhotoPreviewFromBase();

    const title = $('#avaFormTitle');
    if (title) title.textContent = 'Crear cliente';
    const help = $('#avaFormHelp');
    if (help) help.textContent = 'Completa los datos para crear un cliente en tu empresa. El rol siempre sera Cliente.';
    const btn = $('#avaSaveBtn');
    if (btn) btn.textContent = 'Crear cliente';
  }

  function setEditForm(client) {
    state.editId = Number(client.id);
    const fullName = ((client.nombres || '') + ' ' + (client.apellidos || '')).trim();

    const hidden = $('#avaClientId');
    if (hidden) hidden.value = String(client.id);
    const usuario = $('#avaUsuario');
    const nombres = $('#avaNombres');
    const apellidos = $('#avaApellidos');
    const clave = $('#avaClave');
    if (usuario) usuario.value = client.usuario || '';
    if (nombres) nombres.value = client.nombres || '';
    if (apellidos) apellidos.value = client.apellidos || '';
    if (clave) clave.value = '';

    state.formBasePhoto = fotoUrl(client.foto || '');
    clearPhotoInput();
    refreshPhotoPreviewFromBase();

    const title = $('#avaFormTitle');
    if (title) title.textContent = 'Editar cliente';
    const help = $('#avaFormHelp');
    if (help) help.textContent = 'Editando a ' + (fullName || 'cliente') + '. Si no quieres cambiar la clave, dejala vacia. Puedes cargar una nueva foto si deseas actualizarla.';
    const btn = $('#avaSaveBtn');
    if (btn) btn.textContent = 'Guardar cambios';
  }

  async function loadCourses() {
    const data = await request(apiUrl + '?action=cursos_list_activos');
    state.courses = data.data || [];
    renderFilterCourses();
    renderCourseLists();
  }

  async function loadMatriculas() {
    if (!state.selectedClient) {
      state.matriculas = [];
      renderCourseLists();
      return;
    }
    const data = await request(apiUrl + '?action=cliente_matriculas_list&usuario_id=' + encodeURIComponent(String(state.selectedClient.id)));
    state.matriculas = data.data || [];
    renderCourseLists();
  }

  async function loadClients() {
    const qs = new URLSearchParams({
      action: 'clientes_list',
      q: state.filters.q || '',
      curso_id: String(state.filters.curso_id || 0),
      page: String(state.filters.page || 1),
      per_page: String(state.filters.per_page || perPage),
    });

    const data = await request(apiUrl + '?' + qs.toString());
    state.clients = data.data || [];
    state.total = Number(data.total || 0);
    state.filters.page = Number(data.page || state.filters.page || 1);

    if (state.selectedClient) {
      const updated = getClientById(state.selectedClient.id);
      if (!updated) {
        state.selectedClient = null;
        state.matriculas = [];
      } else {
        state.selectedClient = updated;
      }
    }

    renderClientTable();
    renderPager();
    renderSelectedClient();
    renderCourseLists();
  }

  async function refreshAll() {
    clearNotify();
    await loadCourses();
    await loadClients();
    if (state.selectedClient) {
      await loadMatriculas();
    }
  }

  async function saveClient() {
    clearNotify();
    const btn = $('#avaSaveBtn');

    const id = state.editId;
    const usuario = ($('#avaUsuario')?.value || '').trim();
    const nombres = ($('#avaNombres')?.value || '').trim();
    const apellidos = ($('#avaApellidos')?.value || '').trim();
    const clave = ($('#avaClave')?.value || '').trim();
    const fotoInput = $('#avaFoto');
    const fotoFile = fotoInput && fotoInput.files ? fotoInput.files[0] : null;

    if (!/^\d{8,11}$/.test(usuario)) {
      notify('warning', 'El documento/usuario debe tener entre 8 y 11 digitos.');
      return;
    }
    if (!nombres || !apellidos) {
      notify('warning', 'Nombres y apellidos son obligatorios.');
      return;
    }
    if (!id && clave.length < 6) {
      notify('warning', 'La clave inicial debe tener al menos 6 caracteres.');
      return;
    }
    if (id && clave !== '' && clave.length < 6) {
      notify('warning', 'Si ingresas una nueva clave, debe tener al menos 6 caracteres.');
      return;
    }

    const fd = new FormData();
    fd.append('action', id ? 'cliente_update' : 'cliente_create');
    if (id) fd.append('id', String(id));
    fd.append('usuario', usuario);
    fd.append('nombres', nombres);
    fd.append('apellidos', apellidos);
    if (clave !== '') fd.append('clave', clave);
    if (fotoFile) fd.append('foto', fotoFile);

    setButtonLoading(btn, true, id ? 'Guardando...' : 'Creando...');
    try {
      const data = await request(apiUrl, { method: 'POST', body: fd });
      notify('success', data.msg || (id ? 'Cliente actualizado correctamente.' : 'Cliente creado correctamente.'));

      const editedId = id;
      resetForm();
      state.filters.page = 1;
      await loadClients();

      if (editedId && state.selectedClient && Number(state.selectedClient.id) === Number(editedId)) {
        await loadMatriculas();
      }
    } catch (err) {
      notify('error', err.message || 'No se pudo guardar el cliente.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  async function deleteClient(id) {
    const client = getClientById(id);
    if (!client) return;
    const fullName = ((client.nombres || '') + ' ' + (client.apellidos || '')).trim();

    if (!window.confirm('Vas a eliminar a ' + (fullName || 'este cliente') + '. Esta accion no se puede deshacer.\n\nDeseas continuar?')) {
      return;
    }

    clearNotify();
    const fd = new FormData();
    fd.append('action', 'cliente_delete');
    fd.append('id', String(id));

    try {
      const data = await request(apiUrl, { method: 'POST', body: fd });
      notify('success', data.msg || 'Cliente eliminado correctamente.');

      if (state.selectedClient && Number(state.selectedClient.id) === Number(id)) {
        state.selectedClient = null;
        state.matriculas = [];
      }
      if (state.editId && Number(state.editId) === Number(id)) {
        resetForm();
      }

      await loadClients();
      renderSelectedClient();
      renderCourseLists();
    } catch (err) {
      notify('error', err.message || 'No se pudo eliminar el cliente.');
    }
  }

  function setEnrollHeader(course) {
    const clientLabel = $('#avaEnrollClient');
    const courseLabel = $('#avaEnrollCourse');
    if (clientLabel) {
      const fullName = ((state.selectedClient?.nombres || '') + ' ' + (state.selectedClient?.apellidos || '')).trim();
      const doc = state.selectedClient?.usuario || '';
      clientLabel.textContent = fullName + (doc ? ' (' + doc + ')' : '');
    }
    if (courseLabel) courseLabel.textContent = course?.nombre || '-';
  }

  function resetGroupForm(courseId, courseName) {
    const form = $('#avaGroupForm');
    if (form) form.reset();
    const cid = $('#avaGroupCursoId');
    if (cid) cid.value = String(courseId || 0);
    const label = $('#avaGroupCourseLabel');
    if (label) label.textContent = courseName || '-';
    const active = $('#avaGroupActive');
    if (active) active.checked = true;
  }

  function renderEnrollGroups(groups, preselectId) {
    const sel = $('#avaEnrollGroupSelect');
    const noGroups = $('#avaEnrollNoGroups');
    const btnEnroll = $('#avaEnrollConfirmBtn');
    if (!sel || !noGroups || !btnEnroll) return;

    if (!groups.length) {
      sel.innerHTML = '<option value="0">Sin grupos activos</option>';
      sel.value = '0';
      sel.disabled = true;
      noGroups.classList.remove('d-none');
      btnEnroll.disabled = true;
      return;
    }

    noGroups.classList.add('d-none');
    sel.disabled = false;
    btnEnroll.disabled = false;
    const opts = ['<option value="0">Selecciona un grupo</option>'];
    groups.forEach(function (g) {
      const code = g.codigo ? '[' + g.codigo + '] ' : '';
      const range = scheduleLabel(g.inicio_at, g.fin_at);
      opts.push('<option value="' + g.id + '">' + esc(code + g.nombre + ' - ' + range) + '</option>');
    });
    sel.innerHTML = opts.join('');
    if (preselectId) {
      sel.value = String(preselectId);
    }
  }

  async function openEnrollModal(courseId) {
    if (!state.selectedClient) {
      notify('warning', 'Primero selecciona un cliente.');
      return;
    }
    const course = getCourseById(courseId);
    if (!course) {
      notify('warning', 'No se encontro el curso seleccionado.');
      return;
    }
    state.enrollCourse = course;

    const cid = $('#avaEnrollCursoId');
    if (cid) cid.value = String(course.id);
    setEnrollHeader(course);
    renderEnrollGroups([], 0);

    try {
      const data = await request(apiUrl + '?action=grupos_list&curso_id=' + encodeURIComponent(String(course.id)));
      const groups = data.data || [];
      if (!groups.length) {
        resetGroupForm(course.id, course.nombre || '');
        showModal('#avaGroupCreateModal');
        return;
      }
      renderEnrollGroups(groups, 0);
      showModal('#avaEnrollModal');
    } catch (err) {
      notify('error', err.message || 'No se pudieron cargar los grupos del curso.');
    }
  }

  async function createGroup(preferEnrollAfterCreate) {
    const courseId = Number($('#avaGroupCursoId')?.value || 0);
    const name = ($('#avaGroupName')?.value || '').trim();
    const desc = ($('#avaGroupDesc')?.value || '').trim();
    const start = ($('#avaGroupStart')?.value || '').trim();
    const end = ($('#avaGroupEnd')?.value || '').trim();
    const active = $('#avaGroupActive')?.checked ? 1 : 0;
    const btn = $('#avaGroupCreateSubmit');

    if (courseId <= 0) {
      notify('warning', 'No se encontro el curso para crear grupo.');
      return null;
    }
    if (!name) {
      notify('warning', 'El nombre del grupo es obligatorio.');
      return null;
    }
    if ((start && !end) || (!start && end)) {
      notify('warning', 'Si defines inicio o fin, debes completar ambos.');
      return null;
    }
    if (start && end) {
      const s = new Date(start);
      const e = new Date(end);
      if (Number.isNaN(s.getTime()) || Number.isNaN(e.getTime())) {
        notify('warning', 'Formato de fecha/hora invalido.');
        return null;
      }
      if (e.getTime() < s.getTime()) {
        notify('warning', 'La fecha/hora fin no puede ser menor a inicio.');
        return null;
      }
    }

    const fd = new FormData();
    fd.append('action', 'grupo_create');
    fd.append('curso_id', String(courseId));
    fd.append('nombre', name);
    fd.append('descripcion', desc);
    if (start) fd.append('inicio_at', start);
    if (end) fd.append('fin_at', end);
    fd.append('activo', String(active));

    setButtonLoading(btn, true, 'Guardando...');
    try {
      const data = await request(apiUrl, { method: 'POST', body: fd });
      notify('success', data.msg || 'Grupo creado correctamente.');
      hideModal('#avaGroupCreateModal');

      if (preferEnrollAfterCreate && state.enrollCourse && Number(state.enrollCourse.id) === courseId) {
        showModal('#avaEnrollModal');
        const res = await request(apiUrl + '?action=grupos_list&curso_id=' + encodeURIComponent(String(courseId)));
        const createdId = Number(data?.data?.id || 0);
        renderEnrollGroups(res.data || [], createdId);
      }
      return data.data || null;
    } catch (err) {
      notify('error', err.message || 'No se pudo crear el grupo.');
      return null;
    } finally {
      setButtonLoading(btn, false);
    }
  }

  async function matricularSeleccionActual() {
    if (!state.selectedClient || !state.enrollCourse) {
      notify('warning', 'Selecciona cliente y curso antes de matricular.');
      return;
    }
    const groupId = Number($('#avaEnrollGroupSelect')?.value || 0);
    if (groupId <= 0) {
      notify('warning', 'Selecciona un grupo para matricular.');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'cliente_matricular');
    fd.append('usuario_id', String(state.selectedClient.id));
    fd.append('curso_id', String(state.enrollCourse.id));
    fd.append('grupo_id', String(groupId));

    const btn = $('#avaEnrollConfirmBtn');
    setButtonLoading(btn, true, 'Matriculando...');
    try {
      const data = await request(apiUrl, { method: 'POST', body: fd });
      notify('success', data.msg || 'Cliente matriculado correctamente.');
      hideModal('#avaEnrollModal');
      await loadMatriculas();
      await loadClients();
    } catch (err) {
      notify('error', err.message || 'No se pudo matricular al cliente.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  function openExpelModalFromMatricula(m) {
    if (!state.selectedClient) return;
    const fullName = ((state.selectedClient.nombres || '') + ' ' + (state.selectedClient.apellidos || '')).trim();
    const roleName = 'Cliente';
    const groupName = m.grupo_nombre || 'Grupo';
    const courseName = m.curso_nombre || 'Curso';

    state.expelTarget = {
      usuario_id: Number(state.selectedClient.id),
      curso_id: Number(m.curso_id || 0),
    };

    const main = document.getElementById('avaExpelTextMain');
    if (main) {
      main.textContent = '¿Estás seguro de expulsar al usuario ' + fullName + ' (rol ' + roleName + ') del grupo ' + groupName + ' del curso ' + courseName + '?';
    }
    showModal('#avaExpelModal');
  }

  async function confirmExpel() {
    if (!state.expelTarget) return;
    const fd = new FormData();
    fd.append('action', 'cliente_expulsar');
    fd.append('usuario_id', String(state.expelTarget.usuario_id));
    fd.append('curso_id', String(state.expelTarget.curso_id));

    const btn = document.getElementById('avaExpelConfirmBtn');
    setButtonLoading(btn, true, 'Expulsando...');
    try {
      const data = await request(apiUrl, { method: 'POST', body: fd });
      notify('success', data.msg || 'Cliente expulsado correctamente.');
      hideModal('#avaExpelModal');
      state.expelTarget = null;
      await loadMatriculas();
      await loadClients();
    } catch (err) {
      notify('error', err.message || 'No se pudo expulsar al cliente.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  root.addEventListener('click', function (e) {
    const refresh = e.target.closest('#avaRefreshBtn');
    if (refresh) {
      refreshAll().catch(function (err) { notify('error', err.message || 'No se pudo recargar la informacion.'); });
      return;
    }

    const save = e.target.closest('#avaSaveBtn');
    if (save) {
      saveClient();
      return;
    }

    const reset = e.target.closest('#avaResetBtn');
    if (reset) {
      resetForm();
      clearNotify();
      return;
    }

    const pageLink = e.target.closest('#avaPager a[data-page]');
    if (pageLink) {
      e.preventDefault();
      const li = pageLink.parentElement;
      if (li && (li.classList.contains('disabled') || li.classList.contains('active'))) return;
      const page = Number(pageLink.dataset.page || 1);
      if (page > 0) {
        state.filters.page = page;
        loadClients().catch(function (err) { notify('error', err.message || 'No se pudo cargar la pagina.'); });
      }
      return;
    }

    const clientBtn = e.target.closest('button[data-action][data-id]');
    if (clientBtn) {
      const action = clientBtn.dataset.action;
      const id = Number(clientBtn.dataset.id || 0);
      if (!id) return;

      if (action === 'select-client') {
        const client = getClientById(id);
        if (!client) return;
        state.selectedClient = client;
        renderSelectedClient();
        loadMatriculas().catch(function (err) { notify('error', err.message || 'No se pudieron cargar las matriculas del cliente.'); });
      } else if (action === 'edit-client') {
        const client = getClientById(id);
        if (!client) return;
        setEditForm(client);
        clearNotify();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else if (action === 'delete-client') {
        deleteClient(id);
      }
      return;
    }

    const enrollBtn = e.target.closest('button[data-action="open-enroll"][data-course-id]');
    if (enrollBtn) {
      const courseId = Number(enrollBtn.dataset.courseId || 0);
      if (courseId > 0) openEnrollModal(courseId);
      return;
    }

    const expelBtn = e.target.closest('button[data-action="open-expel"][data-curso-id]');
    if (expelBtn) {
      const cursoId = Number(expelBtn.dataset.cursoId || 0);
      const rec = (state.matriculas || []).find(function (m) {
        return Number(m.curso_id) === cursoId && Number(m.estado || 0) === 1;
      });
      if (rec) openExpelModalFromMatricula(rec);
      return;
    }

  });

  const onQInput = debounce(function (e) {
    state.filters.q = (e.target.value || '').trim();
    state.filters.page = 1;
    loadClients().catch(function (err) { notify('error', err.message || 'No se pudo filtrar la lista.'); });
  }, 320);

  const onAvailableCourseInput = debounce(function (e) {
    state.availableCourseQuery = (e.target.value || '').trim();
    renderCourseLists();
  }, 180);

  root.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'avaFilterQ') onQInput(e);
    if (e.target && e.target.id === 'avaAvailableCourseSearch') onAvailableCourseInput(e);
  });

  root.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'avaFoto') {
      handlePhotoInputChange(e.target);
      return;
    }

    if (e.target && e.target.id === 'avaFilterCourse') {
      const sel = e.target;
      const opt = sel.options[sel.selectedIndex];
      sel.title = opt ? opt.text : '';
      state.filters.curso_id = Number(e.target.value || 0);
      state.filters.page = 1;
      loadClients().catch(function (err) { notify('error', err.message || 'No se pudo filtrar por curso.'); });
    }
  });

  const form = $('#avaForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      saveClient();
    });
  }

  const groupForm = document.getElementById('avaGroupForm');
  if (groupForm) {
    groupForm.addEventListener('submit', function (e) {
      e.preventDefault();
      createGroup(true);
    });
  }

  const createGroupBtn = document.getElementById('avaCreateGroupBtn');
  if (createGroupBtn) {
    createGroupBtn.addEventListener('click', function () {
      if (!state.enrollCourse) {
        notify('warning', 'Selecciona un curso para crear grupo.');
        return;
      }
      resetGroupForm(state.enrollCourse.id, state.enrollCourse.nombre || '');
      hideModal('#avaEnrollModal');
      showModal('#avaGroupCreateModal');
    });
  }

  const enrollConfirmBtn = document.getElementById('avaEnrollConfirmBtn');
  if (enrollConfirmBtn) {
    enrollConfirmBtn.addEventListener('click', function () {
      matricularSeleccionActual();
    });
  }

  const expelConfirmBtn = document.getElementById('avaExpelConfirmBtn');
  if (expelConfirmBtn) {
    expelConfirmBtn.addEventListener('click', function () {
      confirmExpel();
    });
  }

  resetForm();
  refreshAll().catch(function (err) {
    notify('error', err.message || 'No se pudo iniciar el modulo de administracion.');
  });
})();
