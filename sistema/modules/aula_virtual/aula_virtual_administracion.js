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
    assigned: [],
    selectedClient: null,
    editId: 0,
    formBasePhoto: '',
  };

  function $(sel) {
    return root.querySelector(sel);
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
    const assignedBox = $('#avaAssignedList');
    const availableEmpty = $('#avaAvailableEmpty');
    const assignedEmpty = $('#avaAssignedEmpty');

    if (!availableBox || !assignedBox || !availableEmpty || !assignedEmpty) return;

    if (!state.selectedClient) {
      availableBox.innerHTML = '';
      assignedBox.innerHTML = '';
      availableEmpty.classList.remove('d-none');
      assignedEmpty.classList.remove('d-none');
      availableEmpty.textContent = 'Selecciona un cliente para ver cursos disponibles.';
      assignedEmpty.textContent = 'Selecciona un cliente para ver cursos asignados.';
      return;
    }

    const assignedIds = new Set((state.assigned || []).map(function (c) { return Number(c.id); }));
    const available = (state.courses || []).filter(function (c) { return !assignedIds.has(Number(c.id)); });

    if (!available.length) {
      availableBox.innerHTML = '';
      availableEmpty.classList.remove('d-none');
      availableEmpty.textContent = 'No hay cursos disponibles para asignar.';
    } else {
      availableEmpty.classList.add('d-none');
      availableBox.innerHTML = available.map(function (c) {
        return (
          '<div class="ava-course-item">' +
            '<div class="title">' + esc(c.nombre) + '</div>' +
            '<button type="button" class="btn btn-sm btn-success" data-action="add-course" data-course-id="' + c.id + '">Agregar</button>' +
          '</div>'
        );
      }).join('');
    }

    if (!state.assigned.length) {
      assignedBox.innerHTML = '';
      assignedEmpty.classList.remove('d-none');
      assignedEmpty.textContent = 'Este cliente aun no tiene cursos asignados.';
    } else {
      assignedEmpty.classList.add('d-none');
      assignedBox.innerHTML = state.assigned.map(function (c) {
        return (
          '<div class="ava-course-item">' +
            '<div class="title">' + esc(c.nombre) + '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-course" data-course-id="' + c.id + '">Quitar</button>' +
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
              '<button type="button" class="btn btn-sm btn-outline-primary" data-action="select-client" data-id="' + c.id + '">Cursos</button>' +
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

  async function loadAssignedCourses() {
    if (!state.selectedClient) {
      state.assigned = [];
      renderCourseLists();
      return;
    }
    const data = await request(apiUrl + '?action=cliente_cursos_list&usuario_id=' + encodeURIComponent(String(state.selectedClient.id)));
    state.assigned = data.data || [];
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
        state.assigned = [];
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
      await loadAssignedCourses();
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
        await loadAssignedCourses();
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
        state.assigned = [];
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

  async function addOrRemoveCourse(action, courseId) {
    if (!state.selectedClient) {
      notify('warning', 'Primero selecciona un cliente.');
      return;
    }

    const fd = new FormData();
    fd.append('action', action);
    fd.append('usuario_id', String(state.selectedClient.id));
    fd.append('curso_id', String(courseId));

    try {
      const data = await request(apiUrl, { method: 'POST', body: fd });
      notify('success', data.msg || 'Operacion completada.');
      await loadAssignedCourses();
      await loadClients();
    } catch (err) {
      notify('error', err.message || 'No se pudo actualizar la asignacion.');
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
        loadAssignedCourses().catch(function (err) { notify('error', err.message || 'No se pudieron cargar los cursos del cliente.'); });
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

    const addBtn = e.target.closest('button[data-action="add-course"][data-course-id]');
    if (addBtn) {
      const courseId = Number(addBtn.dataset.courseId || 0);
      if (courseId > 0) addOrRemoveCourse('cliente_curso_add', courseId);
      return;
    }

    const rmBtn = e.target.closest('button[data-action="remove-course"][data-course-id]');
    if (rmBtn) {
      const courseId = Number(rmBtn.dataset.courseId || 0);
      if (courseId > 0) addOrRemoveCourse('cliente_curso_remove', courseId);
    }
  });

  const onQInput = debounce(function (e) {
    state.filters.q = (e.target.value || '').trim();
    state.filters.page = 1;
    loadClients().catch(function (err) { notify('error', err.message || 'No se pudo filtrar la lista.'); });
  }, 320);

  root.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'avaFilterQ') onQInput(e);
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

  resetForm();
  refreshAll().catch(function (err) {
    notify('error', err.message || 'No se pudo iniciar el modulo de administracion.');
  });
})();
