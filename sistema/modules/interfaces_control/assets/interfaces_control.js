// /modules/interfaces_control/assets/interfaces_control.js
(function () {
  'use strict';

  var cfg = window.IC_CFG || {};
  var api = cfg.api || '';
  var users = [];
  var interfaces = [];

  var $user = document.getElementById('icUser');
  var $list = document.getElementById('icInterfaces');
  var $msg = document.getElementById('icMsg');
  var $save = document.getElementById('icSave');
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

  function getSelectedSlugs() {
    var out = [];
    var checks = document.querySelectorAll('.ic-slug:checked');
    checks.forEach(function (c) { out.push(c.value); });
    return out;
  }

  function loadAssignments() {
    var uid = $user && $user.value ? $user.value : '';
    if (!uid) {
      renderInterfaces({});
      return;
    }
    clearMsg();
    jfetch({ action: 'get_assignments', user_id: uid }, 'GET').then(function (res) {
      if (!res || !res.ok) throw new Error((res && res.msg) || 'Error');
      var selected = {};
      (res.slugs || []).forEach(function (s) { selected[s] = true; });
      renderInterfaces(selected);
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo cargar asignaciones.');
    });
  }

  function saveAssignments() {
    var uid = $user && $user.value ? $user.value : '';
    if (!uid) {
      showMsg('warning', 'Primero selecciona un usuario.');
      return;
    }
    clearMsg();
    $save.disabled = true;
    jfetch({
      action: 'save_assignments',
      user_id: uid,
      slugs: getSelectedSlugs()
    }, 'POST').then(function (res) {
      if (!res || !res.ok) throw new Error((res && res.msg) || 'Error');
      showMsg('success', 'Asignaciones guardadas correctamente.');
      loadAssignments();
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo guardar.');
    }).finally(function () {
      $save.disabled = false;
    });
  }

  function boot() {
    Promise.all([
      jfetch({ action: 'list_control_users' }, 'GET'),
      jfetch({ action: 'list_interfaces' }, 'GET')
    ]).then(function (res) {
      var rUsers = res[0], rIfaces = res[1];
      if (!rUsers || !rUsers.ok) throw new Error((rUsers && rUsers.msg) || 'Error listando usuarios.');
      if (!rIfaces || !rIfaces.ok) throw new Error((rIfaces && rIfaces.msg) || 'Error listando interfaces.');
      users = rUsers.data || [];
      interfaces = rIfaces.data || [];
      renderUsers();
      renderInterfaces({});
    }).catch(function (e) {
      showMsg('danger', e.message || 'No se pudo inicializar.');
    });

    if ($user) $user.addEventListener('change', loadAssignments);
    if ($save) $save.addEventListener('click', saveAssignments);
    if ($reload) $reload.addEventListener('click', function () { location.reload(); });
  }

  boot();
})();

