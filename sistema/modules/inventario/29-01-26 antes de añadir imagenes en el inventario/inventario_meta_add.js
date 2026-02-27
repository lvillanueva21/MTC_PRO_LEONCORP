/* UI Meta Add: crear Ubicaciones y Categorías dentro del modal (Bootstrap 4 / AdminLTE 3) */
(function () {
  function $(id) { return document.getElementById(id); }

  function showTmpMsg(el, msg) {
    if (!el) return;
    el.textContent = msg || '';
    el.classList.remove('d-none');
    clearTimeout(el.__t);
    el.__t = setTimeout(function () {
      el.classList.add('d-none');
      el.textContent = '';
    }, 2500);
  }

  function setBusy(btn, on) {
    if (!btn) return;
    btn.disabled = !!on;
    btn.classList.toggle('disabled', !!on);
  }

  function normalizeName(s) {
    s = (s == null) ? '' : String(s);
    s = s.replace(/\s+/g, ' ').trim();
    return s;
  }

  function sortByNombre(a, b) {
    return String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' });
  }

  function apiPost(action, payload) {
    var app = window.INV_APP;
    if (!app || !app.j || !app.API_URL) return Promise.reject(new Error('INV_APP no disponible'));
    var fd = new FormData();
    fd.append('action', action);
    payload = payload || {};
    for (var k in payload) {
      if (Object.prototype.hasOwnProperty.call(payload, k)) fd.append(k, String(payload[k]));
    }
    return app.j(app.API_URL, { method: 'POST', body: fd });
  }

  function collapseHide(id) {
    if (!window.jQuery) return;
    var $box = window.jQuery('#' + id);
    if ($box.length) $box.collapse('hide');
  }

  function filterCats(term) {
    var root = $('bienCats');
    if (!root) return;
    var t = normalizeName(term).toLowerCase();

    var labels = root.querySelectorAll('label.invx-cat');
    for (var i = 0; i < labels.length; i++) {
      var lab = labels[i];
      var txt = (lab.textContent || '').toLowerCase();
      if (!t || txt.indexOf(t) >= 0) lab.style.display = '';
      else lab.style.display = 'none';
    }
  }

  function resetCatFilter() {
    var inp = $('invxCatFilter');
    if (inp) inp.value = '';
    filterCats('');
  }

  // Init cuando ya cargó inventario.js
  function init() {
    if (!window.INV_APP) return;

    var btnUbicCreate = $('btnUbicCreate');
    var btnUbicCancel = $('btnUbicCancel');
    var inpUbic = $('invxUbicNewName');
    var msgUbic = $('invxUbicMsg');

    var btnCatCreate = $('btnCatCreate');
    var inpCat = $('invxCatNewName');
    var msgCat = $('invxCatMsg');
    var inpCatFilter = $('invxCatFilter');

    // Reset filtros al abrir modal (mejor UX)
    if (window.jQuery && window.jQuery('#mdlBien').length) {
      window.jQuery('#mdlBien').on('shown.bs.modal', function () {
        resetCatFilter();
        if (inpUbic) inpUbic.value = '';
        if (inpCat) inpCat.value = '';
      });
    }

    if (inpCatFilter) {
      inpCatFilter.addEventListener('input', function () {
        filterCats(inpCatFilter.value || '');
      });
    }

    if (btnUbicCancel) {
      btnUbicCancel.addEventListener('click', function () {
        if (inpUbic) inpUbic.value = '';
        collapseHide('invxUbicNewBox');
      });
    }

    if (btnUbicCreate) {
      btnUbicCreate.addEventListener('click', function () {
        var nombre = normalizeName(inpUbic ? inpUbic.value : '');
        if (!nombre) return;

        setBusy(btnUbicCreate, true);

        apiPost('ubic_add', { nombre: nombre })
          .then(function (d) {
            var id = (d && d.id) ? parseInt(d.id, 10) : 0;
            if (!id) throw new Error('No se recibió ID de ubicación');

            var meta = window.INV_APP.getMeta();
            meta.ubicaciones = meta.ubicaciones || [];

            // Evitar duplicado en el array (por si exists=1)
            var existsInArr = false;
            for (var i = 0; i < meta.ubicaciones.length; i++) {
              if (parseInt(meta.ubicaciones[i].id, 10) === id) { existsInArr = true; break; }
            }
            if (!existsInArr) meta.ubicaciones.push({ id: id, nombre: nombre });
            meta.ubicaciones.sort(sortByNombre);

            window.INV_APP.setMeta(meta);
            window.INV_APP.repaintUbicaciones(String(id), null);

            showTmpMsg(msgUbic, (d && d.exists) ? 'Ya existía. Seleccionada.' : 'Ubicación creada y seleccionada.');
            if (inpUbic) inpUbic.value = '';
            collapseHide('invxUbicNewBox');
          })
          .catch(function (e) {
            // Reusar el alert del modal si existe
            var box = document.getElementById('mBienErr');
            if (box) {
              box.textContent = (e && e.message) ? e.message : 'No se pudo crear la ubicación';
              box.classList.remove('d-none');
            }
          })
          .finally(function () {
            setBusy(btnUbicCreate, false);
          });
      });
    }

    if (btnCatCreate) {
      btnCatCreate.addEventListener('click', function () {
        var nombre = normalizeName(inpCat ? inpCat.value : '');
        if (!nombre) return;

        setBusy(btnCatCreate, true);

        apiPost('cat_add', { nombre: nombre })
          .then(function (d) {
            var id = (d && d.id) ? parseInt(d.id, 10) : 0;
            if (!id) throw new Error('No se recibió ID de categoría');

            var meta = window.INV_APP.getMeta();
            meta.categorias = meta.categorias || [];

            var existsInArr = false;
            for (var i = 0; i < meta.categorias.length; i++) {
              if (parseInt(meta.categorias[i].id, 10) === id) { existsInArr = true; break; }
            }
            if (!existsInArr) meta.categorias.push({ id: id, nombre: nombre });
            meta.categorias.sort(sortByNombre);

            // conservar selección y marcar la nueva
            var cur = window.INV_APP.getCatsSelectedFromUI() || [];
            var map = {};
            for (var j = 0; j < cur.length; j++) map[cur[j]] = true;
            map[id] = true;

            var merged = [];
            for (var k in map) {
              if (Object.prototype.hasOwnProperty.call(map, k)) merged.push(parseInt(k, 10));
            }

            window.INV_APP.setMeta(meta);
            window.INV_APP.repaintCategorias(merged);

            resetCatFilter();
            showTmpMsg(msgCat, (d && d.exists) ? 'Ya existía. Marcada.' : 'Categoría creada y marcada.');
            if (inpCat) inpCat.value = '';
            collapseHide('invxCatNewBox');
          })
          .catch(function (e) {
            var box = document.getElementById('mBienErr');
            if (box) {
              box.textContent = (e && e.message) ? e.message : 'No se pudo crear la categoría';
              box.classList.remove('d-none');
            }
          })
          .finally(function () {
            setBusy(btnCatCreate, false);
          });
      });
    }
  }

  // Esperar a DOM listo y a que inventario.js haya corrido
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(init, 0);
    });
  } else {
    setTimeout(init, 0);
  }
})();
