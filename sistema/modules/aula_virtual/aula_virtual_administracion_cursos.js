// Ver 08-03-26
(function () {
  const root = document.getElementById('avAdminCursosRoot');
  if (!root) return;

  const config = window.avAdminCursosConfig || {};
  const apiUrl = config.apiUrl || '';
  if (!apiUrl) return;

  const perPage = Number(config.perPage || 10);
  const PROJECT_ROOT = (location.pathname.split('/modules/')[0] || '');
  const defaultCourseImage = root.getAttribute('data-default-course-image') || (PROJECT_ROOT ? (PROJECT_ROOT + '/modules/consola/assets/no-image.png') : '/modules/consola/assets/no-image.png');

  const state = {
    filters: {
      q: '',
      estado: '',
      page: 1,
      per_page: perPage,
    },
    courses: [],
    total: 0,
    selectedCourse: null,
    topics: [],
    selectedTopicId: 0,
    groups: [],
    deleteTarget: null,
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

  function nl2br(text) {
    return esc(text).replace(/\n/g, '<br>');
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

  function assetUrl(path, fallback) {
    const raw = String(path || '').trim();
    if (!raw) return fallback || '';
    if (/^(https?:)?\/\//i.test(raw) || raw.startsWith('data:')) return raw;
    if (raw.startsWith('/')) return raw;
    return (PROJECT_ROOT ? PROJECT_ROOT : '') + '/' + raw.replace(/^\/+/, '');
  }

  function toDatetimeLocal(raw) {
    const src = String(raw || '').trim();
    if (!src) return '';
    return src.replace(' ', 'T').slice(0, 16);
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

  function getCourseById(id) {
    return state.courses.find(function (c) { return Number(c.id) === Number(id); }) || null;
  }

  function getGroupById(id) {
    return state.groups.find(function (g) { return Number(g.id) === Number(id); }) || null;
  }

  function renderPager() {
    const ul = $('#avcPager');
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

  function updateSelectedLabels() {
    const topicLabel = $('#avcTopicCourseLabel');
    const groupLabel = $('#avcGroupCourseLabel');
    const modalLabel = $('#avcGroupModalCourseLabel');
    const newGroupBtn = $('#avcGroupNewBtn');

    if (!state.selectedCourse) {
      if (topicLabel) topicLabel.textContent = 'Ninguno seleccionado';
      if (groupLabel) groupLabel.textContent = 'Ninguno seleccionado';
      if (modalLabel) modalLabel.textContent = '-';
      if (newGroupBtn) newGroupBtn.disabled = true;
      return;
    }

    const base = (state.selectedCourse.nombre || 'Curso') + ' (ID ' + state.selectedCourse.id + ')';
    if (topicLabel) topicLabel.textContent = base;
    if (groupLabel) groupLabel.textContent = base;
    if (modalLabel) modalLabel.textContent = state.selectedCourse.nombre || '-';
    if (newGroupBtn) newGroupBtn.disabled = false;
  }

  function renderCourseTable() {
    const tbody = $('#avcCourseTbody');
    const count = $('#avcCourseCount');
    if (count) count.textContent = String(state.total || 0);
    if (!tbody) return;

    if (!state.courses.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="ava-empty">No se encontraron cursos con esos filtros.</td></tr>';
      return;
    }

    const selectedId = Number(state.selectedCourse?.id || 0);

    tbody.innerHTML = state.courses.map(function (c) {
      const thumb = assetUrl(c.imagen_path || '', defaultCourseImage);
      const isActive = Number(c.activo || 0) === 1;
      const isSelected = Number(c.id) === selectedId;
      const status = isActive
        ? '<span class="badge badge-success">ACTIVO</span>'
        : '<span class="badge badge-secondary">INACTIVO</span>';

      return (
        '<tr class="' + (isSelected ? 'avc-course-selected' : '') + '">' +
          '<td>' +
            '<div class="avc-course-row">' +
              '<img class="avc-course-avatar" src="' + esc(thumb) + '" alt="Curso ' + esc(c.nombre || '') + '">' +
              '<div class="avc-course-meta">' +
                '<div class="name">' + esc(c.nombre || 'Sin nombre') + '</div>' +
                '<div class="desc">' + esc(c.descripcion || 'Sin descripcion') + '</div>' +
                '<div class="mt-1">' + status + '</div>' +
              '</div>' +
            '</div>' +
          '</td>' +
          '<td class="text-center">' + Number(c.temas_count || 0) + '</td>' +
          '<td class="text-center">' + Number(c.grupos_count || 0) + '</td>' +
          '<td class="text-center">' + Number(c.matriculados_count || 0) + '</td>' +
          '<td>' +
            '<div class="ava-actions">' +
              '<button type="button" class="btn btn-sm btn-outline-primary" data-action="select-course" data-id="' + c.id + '">Ver temas</button>' +
              '<button type="button" class="btn btn-sm btn-primary" data-action="focus-groups" data-id="' + c.id + '">Gestionar grupos</button>' +
            '</div>' +
          '</td>' +
        '</tr>'
      );
    }).join('');
  }

  function renderTopicsList() {
    const list = $('#avcTopicsList');
    const empty = $('#avcTopicsEmpty');
    const detail = $('#avcTopicDetail');

    if (!list || !empty || !detail) return;

    if (!state.selectedCourse) {
      list.innerHTML = '';
      empty.classList.remove('d-none');
      empty.textContent = 'Selecciona un curso para ver sus temas.';
      detail.classList.add('d-none');
      return;
    }

    if (!state.topics.length) {
      list.innerHTML = '';
      empty.classList.remove('d-none');
      empty.textContent = 'Este curso no tiene temas registrados.';
      detail.classList.add('d-none');
      return;
    }

    empty.classList.add('d-none');
    list.innerHTML = state.topics.map(function (t) {
      const selected = Number(t.id) === Number(state.selectedTopicId || 0);
      return (
        '<button type="button" class="avc-topic-item ' + (selected ? 'active' : '') + '" data-action="select-topic" data-id="' + t.id + '">' +
          '<div class="title">' + esc(t.titulo || 'Tema') + '</div>' +
          '<div class="resume">' + esc(t.clase_resumen || 'Sin contenido') + '</div>' +
        '</button>'
      );
    }).join('');

    renderTopicDetail();
  }

  function renderTopicDetail() {
    const detail = $('#avcTopicDetail');
    const title = $('#avcTopicDetailTitle');
    const thumbWrap = $('#avcTopicDetailThumbWrap');
    const thumb = $('#avcTopicDetailThumb');
    const video = $('#avcTopicDetailVideo');
    const klass = $('#avcTopicDetailClass');
    if (!detail || !title || !thumbWrap || !thumb || !video || !klass) return;

    if (!state.topics.length) {
      detail.classList.add('d-none');
      return;
    }

    let selected = state.topics.find(function (t) { return Number(t.id) === Number(state.selectedTopicId || 0); }) || null;
    if (!selected) {
      selected = state.topics[0];
      state.selectedTopicId = Number(selected.id);
      const btn = $('#avcTopicsList .avc-topic-item[data-id="' + selected.id + '"]');
      if (btn) btn.classList.add('active');
    }

    detail.classList.remove('d-none');
    title.textContent = selected.titulo || 'Tema';

    const mini = assetUrl(selected.miniatura_path || '', '');
    if (mini) {
      thumb.src = mini;
      thumbWrap.classList.remove('d-none');
    } else {
      thumb.src = '';
      thumbWrap.classList.add('d-none');
    }

    const v = String(selected.video_url || '').trim();
    if (v) {
      video.href = v;
      video.classList.remove('d-none');
    } else {
      video.href = '#';
      video.classList.add('d-none');
    }

    const clase = String(selected.clase || '').trim();
    klass.innerHTML = clase ? nl2br(clase) : 'Sin contenido disponible.';
  }

  function renderGroups() {
    const tbody = $('#avcGroupTbody');
    if (!tbody) return;

    if (!state.selectedCourse) {
      tbody.innerHTML = '<tr><td colspan="3" class="ava-empty">Selecciona un curso para gestionar grupos.</td></tr>';
      return;
    }

    if (!state.groups.length) {
      tbody.innerHTML = '<tr><td colspan="3" class="ava-empty">Este curso no tiene grupos para tu empresa.</td></tr>';
      return;
    }

    tbody.innerHTML = state.groups.map(function (g) {
      const active = Number(g.activo || 0) === 1;
      const toggleLabel = active ? 'Desactivar' : 'Activar';
      const toggleValue = active ? 0 : 1;
      const badge = active
        ? '<span class="badge badge-success">ACTIVO</span>'
        : '<span class="badge badge-secondary">INACTIVO</span>';
      const range = g.rango_text || 'Indefinido';
      return (
        '<tr>' +
          '<td>' +
            '<div class="avc-group-main">' +
              '<div class="title">' + esc((g.codigo || 'SIN-CODIGO') + ' - ' + (g.nombre || 'Grupo')) + '</div>' +
              '<div class="meta">' +
                '<div><strong>Rango:</strong> ' + esc(range) + '</div>' +
                '<div>' + badge + '</div>' +
              '</div>' +
            '</div>' +
          '</td>' +
          '<td class="text-center">' + Number(g.matriculados_activos_count || 0) + '</td>' +
          '<td>' +
            '<div class="ava-actions">' +
              '<button type="button" class="btn btn-sm btn-primary" data-action="edit-group" data-id="' + g.id + '">Editar</button>' +
              '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="toggle-group" data-id="' + g.id + '" data-activo="' + toggleValue + '">' + toggleLabel + '</button>' +
              '<button type="button" class="btn btn-sm btn-danger" data-action="open-delete-group" data-id="' + g.id + '">Eliminar</button>' +
            '</div>' +
          '</td>' +
        '</tr>'
      );
    }).join('');
  }

  async function loadCourses() {
    const qs = new URLSearchParams({
      action: 'cursos_ro_list',
      q: state.filters.q || '',
      estado: state.filters.estado || '',
      page: String(state.filters.page || 1),
      per_page: String(state.filters.per_page || perPage),
    });

    const data = await request(apiUrl + '?' + qs.toString());
    state.courses = data.data || [];
    state.total = Number(data.total || 0);
    state.filters.page = Number(data.page || state.filters.page || 1);

    if (state.selectedCourse) {
      const updated = getCourseById(state.selectedCourse.id);
      if (updated) state.selectedCourse = updated;
    }

    renderCourseTable();
    renderPager();
    updateSelectedLabels();
  }

  async function loadTopics() {
    if (!state.selectedCourse) {
      state.topics = [];
      state.selectedTopicId = 0;
      renderTopicsList();
      return;
    }

    const qs = new URLSearchParams({ action: 'temas_ro_list', curso_id: String(state.selectedCourse.id) });
    const data = await request(apiUrl + '?' + qs.toString());
    state.topics = data.data || [];

    if (!state.topics.some(function (t) { return Number(t.id) === Number(state.selectedTopicId || 0); })) {
      state.selectedTopicId = state.topics.length ? Number(state.topics[0].id) : 0;
    }
    renderTopicsList();
  }

  async function loadGroups() {
    if (!state.selectedCourse) {
      state.groups = [];
      renderGroups();
      return;
    }

    const qs = new URLSearchParams({ action: 'grupos_list', curso_id: String(state.selectedCourse.id) });
    const data = await request(apiUrl + '?' + qs.toString());
    state.groups = data.data || [];
    renderGroups();
  }

  async function selectCourse(courseId, scrollGroups) {
    const course = getCourseById(courseId);
    if (!course) {
      notify('warning', 'No se encontro el curso seleccionado.');
      return;
    }
    state.selectedCourse = course;
    state.selectedTopicId = 0;

    renderCourseTable();
    updateSelectedLabels();

    try {
      await loadTopics();
      await loadGroups();
      if (scrollGroups) {
        const card = $('#avcGroupTbody');
        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    } catch (err) {
      notify('error', err.message || 'No se pudo cargar el detalle del curso.');
    }
  }

  function resetGroupForm(mode, group) {
    const form = $('#avcGroupForm');
    if (form) form.reset();

    const isEdit = mode === 'edit';
    const gid = $('#avcGroupId');
    const cid = $('#avcGroupCursoId');
    const title = $('#avcGroupModalTitle');
    const saveBtn = $('#avcGroupSaveBtn');

    if (gid) gid.value = isEdit ? String(group.id || 0) : '0';
    if (cid) cid.value = String(state.selectedCourse?.id || 0);

    if (title) title.textContent = isEdit ? 'Editar grupo' : 'Crear grupo';
    if (saveBtn) saveBtn.textContent = isEdit ? 'Guardar cambios' : 'Guardar grupo';

    const name = $('#avcGroupName');
    const desc = $('#avcGroupDesc');
    const start = $('#avcGroupStart');
    const end = $('#avcGroupEnd');
    const active = $('#avcGroupActive');

    if (name) name.value = isEdit ? (group.nombre || '') : '';
    if (desc) desc.value = isEdit ? (group.descripcion || '') : '';
    if (start) start.value = isEdit ? toDatetimeLocal(group.inicio_at || '') : '';
    if (end) end.value = isEdit ? toDatetimeLocal(group.fin_at || '') : '';
    if (active) {
      const current = isEdit ? Number(group.activo || 0) : 1;
      active.checked = current === 1;
      active.dataset.original = String(current);
    }
  }

  function openCreateGroupModal() {
    if (!state.selectedCourse) {
      notify('warning', 'Selecciona un curso antes de crear grupo.');
      return;
    }
    resetGroupForm('create', null);
    updateSelectedLabels();
    showModal('#avcGroupModal');
  }

  function openEditGroupModal(groupId) {
    const group = getGroupById(groupId);
    if (!group) {
      notify('warning', 'No se encontro el grupo seleccionado.');
      return;
    }
    resetGroupForm('edit', group);
    updateSelectedLabels();
    showModal('#avcGroupModal');
  }

  async function saveGroup() {
    clearNotify();

    if (!state.selectedCourse) {
      notify('warning', 'Selecciona un curso para guardar grupo.');
      return;
    }

    const groupId = Number($('#avcGroupId')?.value || 0);
    const courseId = Number($('#avcGroupCursoId')?.value || 0);
    const name = ($('#avcGroupName')?.value || '').trim();
    const desc = ($('#avcGroupDesc')?.value || '').trim();
    const start = ($('#avcGroupStart')?.value || '').trim();
    const end = ($('#avcGroupEnd')?.value || '').trim();
    const activeEl = $('#avcGroupActive');
    const active = activeEl?.checked ? 1 : 0;
    const originalActive = Number(activeEl?.dataset.original || active);
    const btn = $('#avcGroupSaveBtn');

    if (courseId <= 0) {
      notify('warning', 'No se encontro el curso seleccionado.');
      return;
    }
    if (!name) {
      notify('warning', 'El nombre del grupo es obligatorio.');
      return;
    }
    if ((start && !end) || (!start && end)) {
      notify('warning', 'Si defines inicio o fin, debes completar ambos.');
      return;
    }
    if (start && end) {
      const s = new Date(start);
      const e = new Date(end);
      if (Number.isNaN(s.getTime()) || Number.isNaN(e.getTime())) {
        notify('warning', 'Formato de fecha/hora invalido.');
        return;
      }
      if (e.getTime() < s.getTime()) {
        notify('warning', 'La fecha/hora fin no puede ser menor a inicio.');
        return;
      }
    }

    setButtonLoading(btn, true, groupId > 0 ? 'Guardando...' : 'Creando...');
    try {
      const fd = new FormData();
      if (groupId > 0) {
        fd.append('action', 'grupo_update');
        fd.append('grupo_id', String(groupId));
      } else {
        fd.append('action', 'grupo_create');
        fd.append('curso_id', String(courseId));
        fd.append('activo', String(active));
      }
      fd.append('nombre', name);
      fd.append('descripcion', desc);
      if (start) fd.append('inicio_at', start);
      if (end) fd.append('fin_at', end);

      const res = await request(apiUrl, { method: 'POST', body: fd });

      if (groupId > 0 && active !== originalActive) {
        const fd2 = new FormData();
        fd2.append('action', 'grupo_set_activo');
        fd2.append('grupo_id', String(groupId));
        fd2.append('activo', String(active));
        await request(apiUrl, { method: 'POST', body: fd2 });
      }

      hideModal('#avcGroupModal');
      notify('success', res.msg || (groupId > 0 ? 'Grupo actualizado correctamente.' : 'Grupo creado correctamente.'));
      await loadGroups();
      await loadCourses();
      renderCourseTable();
    } catch (err) {
      notify('error', err.message || 'No se pudo guardar el grupo.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  async function setGroupActivo(groupId, activo) {
    const group = getGroupById(groupId);
    if (!group) return;

    clearNotify();
    const fd = new FormData();
    fd.append('action', 'grupo_set_activo');
    fd.append('grupo_id', String(groupId));
    fd.append('activo', String(activo));

    try {
      const res = await request(apiUrl, { method: 'POST', body: fd });
      notify('success', res.msg || 'Estado del grupo actualizado.');
      await loadGroups();
      await loadCourses();
      renderCourseTable();
    } catch (err) {
      notify('error', err.message || 'No se pudo cambiar el estado del grupo.');
    }
  }

  function openDeleteGroupModal(groupId) {
    const group = getGroupById(groupId);
    if (!group) {
      notify('warning', 'No se encontro el grupo seleccionado.');
      return;
    }

    state.deleteTarget = group;
    const code = group.codigo || ('#' + group.id);
    const name = group.nombre || 'Grupo';
    const total = Number(group.matriculas_total_count || group.matriculados_activos_count || 0);

    const text = $('#avcGroupDeleteText');
    if (text) {
      text.textContent = 'Vas a eliminar el grupo ' + code + ' - ' + name + '. Esto eliminara tambien ' + total + ' matriculas asociadas y quitara el acceso al curso a esos usuarios. Esta accion no se puede deshacer.';
    }

    showModal('#avcGroupDeleteModal');
  }

  async function confirmDeleteGroup() {
    if (!state.deleteTarget) return;

    const btn = $('#avcGroupDeleteConfirmBtn');
    setButtonLoading(btn, true, 'Eliminando...');

    try {
      const fd = new FormData();
      fd.append('action', 'grupo_delete');
      fd.append('grupo_id', String(state.deleteTarget.id));

      const res = await request(apiUrl, { method: 'POST', body: fd });
      hideModal('#avcGroupDeleteModal');
      state.deleteTarget = null;
      notify('success', res.msg || 'Grupo eliminado correctamente.');
      await loadGroups();
      await loadCourses();
      renderCourseTable();
    } catch (err) {
      notify('error', err.message || 'No se pudo eliminar el grupo.');
    } finally {
      setButtonLoading(btn, false);
    }
  }

  async function refreshAll() {
    clearNotify();
    await loadCourses();
    if (state.selectedCourse) {
      await loadTopics();
      await loadGroups();
    } else {
      renderTopicsList();
      renderGroups();
    }
  }

  root.addEventListener('click', function (e) {
    const refreshBtn = e.target.closest('#avcRefreshBtn');
    if (refreshBtn) {
      refreshAll().catch(function (err) { notify('error', err.message || 'No se pudo recargar la informacion.'); });
      return;
    }

    const pageLink = e.target.closest('#avcPager a[data-page]');
    if (pageLink) {
      e.preventDefault();
      const li = pageLink.parentElement;
      if (li && (li.classList.contains('disabled') || li.classList.contains('active'))) return;
      const page = Number(pageLink.dataset.page || 1);
      if (page > 0) {
        state.filters.page = page;
        loadCourses().catch(function (err) { notify('error', err.message || 'No se pudo cargar la pagina.'); });
      }
      return;
    }

    const courseBtn = e.target.closest('button[data-action][data-id]');
    if (courseBtn) {
      const action = courseBtn.dataset.action;
      const id = Number(courseBtn.dataset.id || 0);
      if (!id) return;

      if (action === 'select-course') {
        selectCourse(id, false);
        return;
      }
      if (action === 'focus-groups') {
        selectCourse(id, true);
        return;
      }
      if (action === 'select-topic') {
        state.selectedTopicId = id;
        renderTopicsList();
        return;
      }
      if (action === 'edit-group') {
        openEditGroupModal(id);
        return;
      }
      if (action === 'toggle-group') {
        const next = Number(courseBtn.dataset.activo || 0);
        setGroupActivo(id, next);
        return;
      }
      if (action === 'open-delete-group') {
        openDeleteGroupModal(id);
        return;
      }
    }

    const createGroupBtn = e.target.closest('#avcGroupNewBtn');
    if (createGroupBtn) {
      openCreateGroupModal();
      return;
    }

    const confirmDeleteBtn = e.target.closest('#avcGroupDeleteConfirmBtn');
    if (confirmDeleteBtn) {
      confirmDeleteGroup();
      return;
    }
  });

  const onQInput = debounce(function (e) {
    state.filters.q = (e.target.value || '').trim();
    state.filters.page = 1;
    loadCourses().catch(function (err) { notify('error', err.message || 'No se pudo filtrar cursos.'); });
  }, 320);

  root.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'avcFilterQ') onQInput(e);
  });

  root.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'avcFilterEstado') {
      state.filters.estado = String(e.target.value || '');
      state.filters.page = 1;
      loadCourses().catch(function (err) { notify('error', err.message || 'No se pudo filtrar por estado.'); });
      return;
    }
  });

  const groupForm = $('#avcGroupForm');
  if (groupForm) {
    groupForm.addEventListener('submit', function (e) {
      e.preventDefault();
      saveGroup();
    });
  }

  updateSelectedLabels();
  renderTopicsList();
  renderGroups();
  refreshAll().catch(function (err) {
    notify('error', err.message || 'No se pudo iniciar el modulo de cursos.');
  });
})();
