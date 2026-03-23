// /modules/interfaces_control/assets/interfaces_control.js
(function () {
  'use strict';

  var cfg = window.IC_CFG || {};
  var api = cfg.api || '';
  var users = [];
  var interfaces = [];
  var classicModules = [];

  var $user = document.getElementById('icUser');
  var $list = document.getElementById('icInterfaces');
  var $classicList = document.getElementById('icClassicModules');
  var $msg = document.getElementById('icMsg');
  var $save = document.getElementById('icSave');
  var $saveClassic = document.getElementById('icSaveClassic');
  var $reload = document.getElementById('icReload');

  function showMsg(type, text) {
    if (!$msg) return;
    $msg.className = 'alert alert-' + type;
    $msg.textContent = text;
    $msg.classList.remove('d-none');
  }

  function clearMsg() {
    if (!$msg) return;
    $msg.classList.add('d-none');
  }

  function jfetch(params, method) {
    method = method || 'GET';
    if (method === 'GET') {
      var qs = new URLSearchParams(params).toString();
      return fetch(api + '?' + qs, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }
    var form = new FormData();
    Object.keys(params).forEach(function (k) {
      var v = params[k];
      if (Array.isArray(v)) {
        v.forEach(function (x) { form.append(k + '[]', x); });
      } else {
        form.append(k, v);
      }
    });
    return fetch(api, { method: 'POST', body: form, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }

  function renderUsers() {
    if (!$user) return;
    $user.innerHTML = '';

    var opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'Selecciona usuario Control...';
    $user.appendChild(opt0);

    users.forEach(function (u) {
      var opt = document.createElement('option');
      opt.value = String(u.id);
      var full = (u.nombres || '') + ' ' + (u.apellidos || '');
      var label = (full.trim() || u.usuario) + ' (' + u.usuario + ')';
      if (u.empresa) label += ' - ' + u.empresa;
      opt.textContent = label;
      $user.appendChild(opt);
    });
  }

  function renderInterfaces(selected) {
    if (!$list) return;
    selected = selected || {};
    $list.innerHTML = '';
    if (!interfaces.length) {
      $list.innerHTML = '<div class="text-muted small">No hay interfaces detectadas.</div>';
      return;
    }
    interfaces.forEach(function (it) {
      var row = document.createElement('div');
      row.className = 'ic-iface-item';
      var checked = selected[it.slug] ? 'checked' : '';
      row.innerHTML =
        '<div class="form-check">' +
        '<input class="form-check-input ic-slug" type="checkbox" value="' + it.slug + '" id="ic_' + it.slug + '" ' + checked + '>' +
        '<label class="form-check-label" for="ic_' + it.slug + '">' +
        '<i class="' + (it.icon || 'far fa-circle') + ' mr-1"></i>' + it.label +
        ' <span class="text-muted small">(' + it.slug + ')</span>' +
        '</label>' +
        '</div>';
      $list.appendChild(row);
    });
  }

  function renderClassicModules(selected) {
    if (!$classicList) return;
    selected = selected || {};
    $classicList.innerHTML = '';
    if (!classicModules.length) {
      $classicList.innerHTML = '<div class="text-muted small">No hay modulos elegibles en el catalogo.</div>';
      return;
    }
    classicModules.forEach(function (it) {
      var slug = it.slug || '';
      var label = it.label || slug;
      var path = it.path || '';
      var row = document.createElement('div');
      row.className = 'ic-iface-item';
      var checked = selected[slug] ? 'checked' : '';
      row.innerHTML =
        '<div class="form-check">' +
        '<input class="form-check-input ic-classic-slug" type="checkbox" value="' + slug + '" id="icc_' + slug + '" ' + checked + '>' +
        '<label class="form-check-label" for="icc_' + slug + '">' +
        '<i class="fas fa-cube mr-1"></i>' + label +
        ' <span class="text-muted small">(' + slug + ')</span>' +
        (path ? ' <span class="text-muted small">- ' + path + '</span>' : '') +
        '</label>' +
        '</div>';
      $classicList.appendChild(row);
    });
  }

  function getSelectedSlugs() {
    var out = [];
    var checks = document.querySelectorAll('.ic-slug:checked');
    checks.forEach(function (c) { out.push(c.value); });
    return out;
  }

  function getSelectedClassicSlugs() {
    var out = [];
    var checks = document.querySelectorAll('.ic-classic-slug:checked');
    checks.forEach(function (c) { out.push(c.value); });
    return out;
  }

  function selectedUserId() {
    return $user && $user.value ? $user.value : '';
  }

  function loadInterfaceAssignments() {
    var uid = selectedUserId();
    if (!uid) {
      renderInterfaces({});
      return;
    }
    jfetch({ action: 'get_assignments', user_id: uid }, 'GET').then(function (res) {
      if (!res || !res.ok) throw new Error((res && res.msg) || 'Error');
      var selected = {};
      (res.slugs || []).forEach(function (s) { selected[s] = true; });
      renderInterfaces(selected);
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo cargar asignaciones de interfaces.');
    });
  }

  function loadClassicAssignments() {
    var uid = selectedUserId();
    if (!uid) {
      renderClassicModules({});
      return;
    }
    jfetch({ action: 'get_control_special_assignments', user_id: uid }, 'GET').then(function (res) {
      if (!res || !res.ok) throw new Error((res && res.msg) || 'Error');
      var selected = {};
      (res.slugs || []).forEach(function (s) { selected[s] = true; });
      renderClassicModules(selected);
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo cargar permisos especiales.');
    });
  }

  function loadUserAssignments() {
    clearMsg();
    loadInterfaceAssignments();
    loadClassicAssignments();
  }

  function saveAssignments() {
    var uid = selectedUserId();
    if (!uid) {
      showMsg('warning', 'Primero selecciona un usuario.');
      return;
    }
    clearMsg();
    if ($save) $save.disabled = true;
    jfetch({
      action: 'save_assignments',
      user_id: uid,
      slugs: getSelectedSlugs()
    }, 'POST').then(function (res) {
      if (!res || !res.ok) throw new Error((res && res.msg) || 'Error');
      showMsg('success', 'Asignaciones de interfaces guardadas correctamente.');
      loadInterfaceAssignments();
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo guardar asignaciones de interfaces.');
    }).finally(function () {
      if ($save) $save.disabled = false;
    });
  }

  function saveClassicAssignments() {
    var uid = selectedUserId();
    if (!uid) {
      showMsg('warning', 'Primero selecciona un usuario.');
      return;
    }
    clearMsg();
    if ($saveClassic) $saveClassic.disabled = true;
    jfetch({
      action: 'save_control_special_assignments',
      user_id: uid,
      slugs: getSelectedClassicSlugs()
    }, 'POST').then(function (res) {
      if (!res || !res.ok) throw new Error((res && res.msg) || 'Error');
      showMsg('success', 'Permisos especiales por modulo guardados correctamente.');
      loadClassicAssignments();
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo guardar permisos especiales.');
    }).finally(function () {
      if ($saveClassic) $saveClassic.disabled = false;
    });
  }

  function boot() {
    Promise.all([
      jfetch({ action: 'list_control_users' }, 'GET'),
      jfetch({ action: 'list_interfaces' }, 'GET'),
      jfetch({ action: 'list_control_special_catalog' }, 'GET')
    ]).then(function (res) {
      var rUsers = res[0], rIfaces = res[1], rClassic = res[2];
      if (!rUsers || !rUsers.ok) throw new Error((rUsers && rUsers.msg) || 'Error listando usuarios.');
      if (!rIfaces || !rIfaces.ok) throw new Error((rIfaces && rIfaces.msg) || 'Error listando interfaces.');
      if (!rClassic || !rClassic.ok) throw new Error((rClassic && rClassic.msg) || 'Error listando modulos elegibles.');

      users = rUsers.data || [];
      interfaces = rIfaces.data || [];
      classicModules = rClassic.data || [];

      renderUsers();
      renderInterfaces({});
      renderClassicModules({});
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo inicializar.');
    });

    if ($user) $user.addEventListener('change', loadUserAssignments);
    if ($save) $save.addEventListener('click', saveAssignments);
    if ($saveClassic) $saveClassic.addEventListener('click', saveClassicAssignments);
    if ($reload) $reload.addEventListener('click', function () { location.reload(); });
  }

  boot();
})();
