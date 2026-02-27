/* Inventario General (SPA) - lista/cuadricula, filtros, modales, QRs */
(function () {
  var CFG = window.INV_CFG || {};
  var API = CFG.api;

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function esc(s) {
    s = (s == null) ? '' : String(s);
    return s.replace(/[&<>\"']/g, function (m) {
      return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m]);
    });
  }

  async function j(url, opts) {
    var r = await fetch(url, Object.assign({ credentials: 'same-origin' }, opts || {}));
    var txt = await r.text();
    var d = null;
    try { d = JSON.parse(txt); } catch (e) {
      var snip = (txt || '').slice(0, 400).replace(/\s+/g, ' ').trim();
      throw new Error('Respuesta inválida del servidor. Preview: ' + snip);
    }
    if (!r.ok || !d || !d.ok) throw new Error((d && d.msg) ? d.msg : ('HTTP ' + r.status));
    return d;
  }

  var state = {
    meta: null,
    q: '',
    tipo: '',
    estado: '',
    activo: '',
    catIds: [],
    view: 'list', // list|grid
    page: 1,
    per: 10,
    rows: [],
    total: 0,
    selected: {}, // id=>true
    expanded: {}  // id=>true (solo lista)
  };

  // UI refs
  var tb = $('#tb');
  var pager = $('#pager');
  var alertBox = $('#alert');

  var kpiTotal = $('#kpiTotal');
  var kpiActivos = $('#kpiActivos');
  var kpiAveriados = $('#kpiAveriados');
  var kpiConsumibles = $('#kpiConsumibles');

  var wrapList = $('#wrapList');
  var wrapGrid = $('#wrapGrid');

  // Controls
  var qInput = $('#q');
  var btnClear = $('#btnClear');
  var fTipo = $('#fTipo');
  var fEstado = $('#fEstado');
  var fActivo = $('#fActivo');

  var btnViewList = $('#btnViewList');
  var btnViewGrid = $('#btnViewGrid');

  var btnNew = $('#btnNew');
var catInput = $('#catInput');
var catOptions = $('#catOptions');
var catChips = $('#catChips');
var btnCatClear = $('#btnCatClear');

var btnResetFilters = $('#btnResetFilters');

  var btnSelAll = $('#btnSelAll');
  var btnSelNone = $('#btnSelNone');
  var chkAll = $('#chkAll');
  var selInfo = $('#selInfo');

 // ====== IMPRIMIR QRS (NUEVO) ======
var invPrintMenu = $('#invPrintMenu');
var invPrintHint = $('#invPrintHint');
var btnPrintSelected = $('#btnPrintSelected');
var btnPrintToggle = $('#btnPrintToggle');

// OJO: scope SOLO al menú de imprimir (evita conflictos con otros dropdowns)
var paperMenuItems = $all('#invPrintMenu .dropdown-item[data-paper]');
var sizeMenuItems  = $all('#invPrintMenu .dropdown-item[data-mm]');

// Estado de impresión
var printPaper = 'a4'; // a4|t80|t58
var printMm = 24;      // 18|24|32

function paperLabel(p) {
  if (p === 't80') return 'Ticket 80mm';
  if (p === 't58') return 'Ticket 58mm';
  return 'A4';
}
function mmLabel(mm) {
  if (mm === 18) return 'Pequeño (18mm)';
  if (mm === 24) return 'Mediano (24mm)';
  if (mm === 32) return 'Grande (32mm)';
  return String(mm) + 'mm';
}

function setPaperIcon(btn, on) {
  var ico = btn.querySelector('i');
  if (!ico) return;
  ico.classList.remove('fa-circle', 'fa-dot-circle');
  ico.classList.add(on ? 'fa-dot-circle' : 'fa-circle');
  btn.classList.toggle('active', on);
}

function setMmIcon(btn, on) {
  var ico = btn.querySelector('i');
  if (!ico) return;
  ico.classList.remove('fa-square', 'fa-check-square');
  ico.classList.add(on ? 'fa-check-square' : 'fa-square');
  btn.classList.toggle('active', on);
}

function paintPrintMenu() {
  paperMenuItems.forEach(function (btn) {
    var p = String(btn.getAttribute('data-paper') || 'a4').toLowerCase();
    setPaperIcon(btn, p === printPaper);
  });
  sizeMenuItems.forEach(function (btn) {
    var mm = parseInt(btn.getAttribute('data-mm') || '0', 10);
    setMmIcon(btn, mm === printMm);
  });

  if (invPrintHint) {
    invPrintHint.textContent =
      'Formato: ' + paperLabel(printPaper) +
      ' • Tamaño: ' + mmLabel(printMm) +
      ' • Tip: selecciona ítems con los checks.';
  }
}

// Mantener dropdown abierto al elegir (Bootstrap 4 cierra por defecto)
if (invPrintMenu) {
  invPrintMenu.addEventListener('click', function (e) {
    // Si es el botón imprimir, NO bloqueamos (queremos que se cierre)
    if (e.target.closest('#btnPrintSelected')) return;

    // Para cualquier otro click dentro del menú, evitamos el autocierre
    e.stopPropagation();

    var optPaper = e.target.closest('.dropdown-item[data-paper]');
    var optMm = e.target.closest('.dropdown-item[data-mm]');

    if (optPaper) {
      e.preventDefault();
      printPaper = String(optPaper.getAttribute('data-paper') || 'a4').toLowerCase();
      paintPrintMenu();
      return;
    }

    if (optMm) {
      e.preventDefault();
      var mm = parseInt(optMm.getAttribute('data-mm') || '24', 10);
      if (mm >= 16 && mm <= 45) printMm = mm;
      paintPrintMenu();
      return;
    }
  });
}
// ==================================

  // Modals
  var mdlBien = $('#mdlBien');
  var mBienTitle = $('#mBienTitle');
  var mBienErr = $('#mBienErr');
  var frmBien = $('#frmBien');
  var bienId = $('#bienId');

  var bienTipo = $('#bienTipo');
  var bienEstado = $('#bienEstado');
  var bienActivo = $('#bienActivo');
  var bienNombre = $('#bienNombre');
  var bienDesc = $('#bienDesc');
  var bienMarca = $('#bienMarca');
  var bienModelo = $('#bienModelo');
  var bienSerie = $('#bienSerie');
  var bienCant = $('#bienCant');
  var bienUnidad = $('#bienUnidad');
  var bienUbic = $('#bienUbic');
  var bienRespUser = $('#bienRespUser');
  var bienRespNom = $('#bienRespNom');
  var bienRespApe = $('#bienRespApe');
  var bienRespDni = $('#bienRespDni');
  var bienNotas = $('#bienNotas');
  var bienCats = $('#bienCats');
  var bienCodeHint = $('#bienCodeHint');

  var respUserRadio = $('#respUser');
  var respTextRadio = $('#respText');
  var respUserWrap = $('#respUserWrap');
  var respTextWrap = $('#respTextWrap');

  // ---- Imagen (refs) ----
  var bienImgFile = $('#bienImgFile');
  var bienImgKey = $('#bienImgKey');
  var bienImgTouched = $('#bienImgTouched');
  var bienImgPreview = $('#bienImgPreview');
  var btnImgRemove = $('#btnImgRemove');
  var bienImgProgWrap = $('#bienImgProgWrap');
  var bienImgProgBar = $('#bienImgProgBar');

  var mdlMove = $('#mdlMove');
  var mMoveErr = $('#mMoveErr');
  var frmMove = $('#frmMove');
  var mvId = $('#mvId');
  var mvCode = $('#mvCode');
  var mvName = $('#mvName');
  var mvUbic = $('#mvUbic');
  var mvNota = $('#mvNota');

  var mvRespUserRadio = $('#mvRespUser');
  var mvRespTextRadio = $('#mvRespText');
  var mvRespUserWrap = $('#mvRespUserWrap');
  var mvRespTextWrap = $('#mvRespTextWrap');
  var mvRespUserSel = $('#mvRespUserSel');
  var mvRespNom = $('#mvRespNom');
  var mvRespApe = $('#mvRespApe');
  var mvRespDni = $('#mvRespDni');

  var mdlQR = $('#mdlQR');
  var qrCode = $('#qrCode');
  var qrImg = $('#qrImg');
  var qrDl = $('#qrDl');
  var qrOpen = $('#qrOpen');

  var mdlHist = $('#mdlHist');
  var histCode = $('#histCode');
  var histName = $('#histName');
  var histBody = $('#histBody');

  // bootstrap modal helpers (jQuery required by AdminLTE)
  function showModal(el) { window.jQuery(el).modal('show'); }
  function hideModal(el) { window.jQuery(el).modal('hide'); }

  function showErr(msg) {
    alertBox.textContent = msg || 'Error';
    alertBox.classList.remove('d-none');
  }
  function hideErr() { alertBox.classList.add('d-none'); alertBox.textContent = ''; }

  function setSelected(id, val) {
    if (val) state.selected[id] = true;
    else delete state.selected[id];
    updateSelInfo();
  }
  function isSelected(id) { return !!state.selected[id]; }
  function selectedIds() { return Object.keys(state.selected).map(function (x) { return parseInt(x, 10); }).filter(Boolean); }
  function updateSelInfo() {
    var n = selectedIds().length;
    selInfo.textContent = n + ' seleccionados';
  }

  function fillSelect(sel, items, placeholder) {
    var html = '';
    if (placeholder != null) html += '<option value="">' + esc(placeholder) + '</option>';
    for (var i = 0; i < items.length; i++) {
      var it = items[i];
      html += '<option value="' + esc(it.value) + '">' + esc(it.label) + '</option>';
    }
    sel.innerHTML = html;
  }

  function buildCatsUI(cats, selected) {
    var sel = {};
    (selected || []).forEach(function (id) { sel[String(id)] = true; });
    return cats.map(function (c) {
      var ck = sel[String(c.id)] ? 'checked' : '';
      return (
        '<label class="invx-cat">' +
          '<input type="checkbox" class="invx-cat-chk" value="' + esc(c.id) + '" ' + ck + '>' +
          '<span class="name">' + esc(c.nombre) + '</span>' +
        '</label>'
      );
    }).join('');
  }

  function getCatsSelectedFromUI(root) {
    var out = [];
    $all('input.invx-cat-chk:checked', root).forEach(function (x) {
      var n = parseInt(x.value, 10);
      if (n > 0) out.push(n);
    });
    var m = {};
    out.forEach(function (n) { m[n] = true; });
    return Object.keys(m).map(function (x) { return parseInt(x, 10); });
  }
  
    function repaintUbicaciones(selBien, selMove) {
    var ubic = (state.meta && state.meta.ubicaciones) ? state.meta.ubicaciones : [];

    var keepBien = (selBien !== null && selBien !== undefined) ? String(selBien) : String((bienUbic && bienUbic.value) ? bienUbic.value : '');
    var keepMove = (selMove !== null && selMove !== undefined) ? String(selMove) : String((mvUbic && mvUbic.value) ? mvUbic.value : '');

    bienUbic.innerHTML = '<option value="">— Sin ubicación —</option>' + ubic.map(function (u) {
      return '<option value="' + esc(u.id) + '">' + esc(u.nombre) + '</option>';
    }).join('');

    mvUbic.innerHTML = '<option value="">— No cambiar —</option>' + ubic.map(function (u) {
      return '<option value="' + esc(u.id) + '">' + esc(u.nombre) + '</option>';
    }).join('');

    if (keepBien) bienUbic.value = keepBien;
    if (keepMove) mvUbic.value = keepMove;
  }

  function repaintUsuarios(selBienResp, selMoveResp) {
    var users = (state.meta && state.meta.usuarios) ? state.meta.usuarios : [];

    var keepBien = (selBienResp !== null && selBienResp !== undefined) ? String(selBienResp) : String((bienRespUser && bienRespUser.value) ? bienRespUser.value : '');
    var keepMove = (selMoveResp !== null && selMoveResp !== undefined) ? String(selMoveResp) : String((mvRespUserSel && mvRespUserSel.value) ? mvRespUserSel.value : '');

    bienRespUser.innerHTML = '<option value="">— Sin responsable —</option>' + users.map(function (u) {
      return '<option value="' + esc(u.id) + '">' + esc(u.nombre) + '</option>';
    }).join('');

    mvRespUserSel.innerHTML = '<option value="">— No cambiar —</option>' + users.map(function (u) {
      return '<option value="' + esc(u.id) + '">' + esc(u.nombre) + '</option>';
    }).join('');

    if (keepBien) bienRespUser.value = keepBien;
    if (keepMove) mvRespUserSel.value = keepMove;
  }

  function repaintCategorias(selectedIds) {
    var ids = selectedIds;
    if (!ids || !ids.length) ids = [];
    bienCats.innerHTML = buildCatsUI((state.meta && state.meta.categorias) ? state.meta.categorias : [], ids);
  }

  // Exponer API mínima para módulos externos (evita inflar este archivo)
  window.INV_APP = window.INV_APP || {};
  window.INV_APP.API_URL = API;
  window.INV_APP.j = j;
  window.INV_APP.esc = esc;

  window.INV_APP.getMeta = function () { return state.meta || {}; };
  window.INV_APP.setMeta = function (m) { state.meta = m || {}; };

  window.INV_APP.repaintUbicaciones = repaintUbicaciones;
  window.INV_APP.repaintCategorias = repaintCategorias;
  window.INV_APP.getCatsSelectedFromUI = function () { return getCatsSelectedFromUI(bienCats); };

  async function loadMeta() {
    var d = await j(API + '?action=meta');
    state.meta = d.data || {};

    fillSelect(fTipo, (state.meta.tipos || []).map(function (t) { return { value: t, label: 'Tipo: ' + t }; }), 'Tipo: Todos');
    fillSelect(fEstado, (state.meta.estados || []).map(function (t) { return { value: t, label: 'Estado: ' + t }; }), 'Estado: Todos');

    fillSelect(bienTipo, (state.meta.tipos || []).map(function (t) { return { value: t, label: t }; }), null);
    fillSelect(bienEstado, (state.meta.estados || []).map(function (t) { return { value: t, label: t }; }), null);
    fillSelect(bienUnidad, (state.meta.unidades || ['UND']).map(function (t) { return { value: t, label: t }; }), null);

        repaintUbicaciones(null, null);
    repaintUsuarios(null, null);
    repaintCategorias([]);
    paintCatOptions();
syncCatChips();
  }

  async function loadStats() {
    var d = await j(API + '?action=stats');
    var s = d.data || {};
    kpiTotal.textContent = s.total || 0;
    kpiActivos.textContent = s.activos || 0;
    kpiAveriados.textContent = s.averiados || 0;
    kpiConsumibles.textContent = s.consumibles || 0;
  }

  async function loadList() {
    try {
      hideErr();

      var qs = new URLSearchParams({
        action: 'list',
        page: String(state.page),
        per: String(state.per),
        q: state.q,
        tipo: state.tipo,
        estado: state.estado,
        activo: state.activo,
        cat_ids: state.catIds.join(',')
      });

      var d = await j(API + '?' + qs.toString());
      state.rows = d.data || [];
      state.total = d.total || 0;

      render();
      paintPager();
      await loadStats();

    } catch (e) {
      showErr(e.message || 'Error');
    }
  }

  var debounceTimer = null;
  function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadList, 250);
  }

  function respLabel(row) {
    if (row && row.responsable_user) return row.responsable_user;
    var t = (row && row.responsable_texto) ? String(row.responsable_texto) : '';
    t = t.trim();
    return t ? t : '—';
  }

  function badgeEstado(e) {
    var cls = 'invx-bueno';
    if (e === 'REGULAR') cls = 'invx-regular';
    if (e === 'AVERIADO') cls = 'invx-averiado';
    return '<span class="invx-pill ' + cls + '">' + esc(e || '') + '</span>';
  }

  function catsTagsHtml(catsTxt) {
    catsTxt = (catsTxt == null) ? '' : String(catsTxt);
    catsTxt = catsTxt.trim();
    if (!catsTxt) return '<span class="text-muted">—</span>';

    var parts = catsTxt.split(',');
    var out = [];
    for (var i = 0; i < parts.length; i++) {
      var p = String(parts[i] || '').trim();
      if (!p) continue;
      out.push('<span class="invx-tag">' + esc(p) + '</span>');
    }
    if (!out.length) return '<span class="text-muted">—</span>';
    return '<div class="invx-tags">' + out.join('') + '</div>';
  }

  function ubicRespCellHtml(row) {
    var ubic = (row && row.ubicacion_nombre) ? String(row.ubicacion_nombre).trim() : '';
    if (!ubic) ubic = '—';
    var resp = respLabel(row);
    return (
      '<div class="invx-ur">' +
        '<div class="u">' + esc(ubic) + '</div>' +
        '<div class="r">' + esc(resp) + '</div>' +
      '</div>'
    );
  }

  function extraRowHtml(row) {
    var desc = (row && row.descripcion) ? String(row.descripcion).trim() : '';
    if (!desc) desc = '—';

    var cant = (row && row.cantidad != null && String(row.cantidad).trim() !== '') ? String(row.cantidad).trim() : '—';
    var uni  = (row && row.unidad) ? String(row.unidad).trim() : '—';

    var cats = catsTagsHtml(row ? row.categorias_txt : '');

    return (
      '<div class="invx-extra-box">' +
        '<div class="row">' +
          '<div class="col-12 col-lg-6 mb-2">' +
            '<div class="invx-extra-k">Descripción</div>' +
            '<div class="invx-extra-v">' + esc(desc) + '</div>' +
          '</div>' +
          '<div class="col-6 col-lg-2 mb-2">' +
            '<div class="invx-extra-k">Cantidad</div>' +
            '<div class="invx-extra-v">' + esc(cant) + '</div>' +
          '</div>' +
          '<div class="col-6 col-lg-2 mb-2">' +
            '<div class="invx-extra-k">Unidad</div>' +
            '<div class="invx-extra-v">' + esc(uni) + '</div>' +
          '</div>' +
          '<div class="col-12 col-lg-2 mb-2">' +
            '<div class="invx-extra-k">Categorías</div>' +
            '<div class="invx-extra-v">' + cats + '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function toggleExtraRow(id) {
    id = parseInt(id, 10);
    if (!id) return;

    if (!state.expanded) state.expanded = {};
    var on = !state.expanded[id];
    if (on) state.expanded[id] = true;
    else delete state.expanded[id];

    var extra = tb.querySelector('tr.invx-extra-row[data-extra-for="' + id + '"]');
    var main  = tb.querySelector('tr.invx-main-row[data-id="' + id + '"]');

    if (extra) extra.classList.toggle('d-none', !on);
    if (main)  main.classList.toggle('is-open', !!on);
  }

  function thumbPlaceholderSrc() {
    if (window.INV_THUMBS && window.INV_THUMBS.placeholder) return window.INV_THUMBS.placeholder;
    if (window.INV_THUMBS_PLACEHOLDER) return window.INV_THUMBS_PLACEHOLDER;
    return '';
  }

  function thumbHtml(row, variant) {
    var key = '';
    if (row && row.img_key) key = row.img_key;
    else if (row && row.imgKey) key = row.imgKey;

    key = String(key || '').trim();

    var cls = (variant === 'card') ? 'invx-thumb--card' : 'invx-thumb--list';

    if (!key) {
      return '<div class="invx-thumb-wrap ' + cls + ' invx-thumb-empty"><i class="far fa-image"></i></div>';
    }

    var url = imgPublicUrl(key, false); // SIN cache-bust para miniaturas
    var ph = thumbPlaceholderSrc();

    return (
      '<div class="invx-thumb-wrap ' + cls + '">' +
        '<img class="invx-thumb-img invx-lazy is-loading" ' +
          (ph ? ('src="' + esc(ph) + '" ') : '') +
          'data-src="' + esc(url) + '" ' +
          'alt="' + esc((row && row.nombre) ? row.nombre : 'Imagen') + '" ' +
          'loading="lazy" decoding="async">' +
      '</div>'
    );
  }

  function mountThumbs(root) {
    if (window.INV_THUMBS && typeof window.INV_THUMBS.mount === 'function') {
      window.INV_THUMBS.mount(root || document);
    }
  }

  function render() {
    updateSelInfo();

    if (state.view === 'list') {
      wrapList.classList.remove('d-none');
      wrapGrid.classList.add('d-none');
      renderTable();
      mountThumbs(wrapList);
    } else {
      wrapList.classList.add('d-none');
      wrapGrid.classList.remove('d-none');
      renderGrid();
      mountThumbs(wrapGrid);
    }
  }

    function renderTable() {
    var rows = state.rows || [];
    if (!rows.length) {
      tb.innerHTML = '<tr><td colspan="8" class="text-muted py-3">Sin registros.</td></tr>';
      return;
    }

    tb.innerHTML = rows.map(function (x) {
      var id = parseInt(x.id, 10);
      var ck = isSelected(id) ? 'checked' : '';

      var sub = [x.marca, x.modelo].filter(Boolean).join(' ');
      if (!sub) sub = '—';

      var open = !!(state.expanded && state.expanded[id]);
      var openCls = open ? ' is-open' : '';
      var extraCls = open ? '' : ' d-none';

      return (
        '<tr class="invx-main-row' + openCls + '" data-id="' + id + '">' +
          '<td class="invx-no-toggle"><input type="checkbox" class="rowchk" data-id="' + id + '" ' + ck + '></td>' +
          '<td><span class="invx-code-sm">' + esc(x.codigo_inv || '') + '</span></td>' +
          '<td class="invx-td-thumb">' + thumbHtml(x, 'list') + '</td>' +
          '<td>' +
            '<div class="font-weight-bold">' + esc(x.nombre || '') + '</div>' +
            '<div class="text-muted small">' + esc(sub) + '</div>' +
          '</td>' +
          '<td>' + esc(x.tipo || '') + '</td>' +
          '<td>' + badgeEstado(x.estado) + '</td>' +
          '<td>' + ubicRespCellHtml(x) + '</td>' +
          '<td class="text-nowrap invx-no-toggle">' +
            '<button class="btn btn-sm btn-outline-dark mr-1" data-act="qr" title="QR"><i class="fas fa-qrcode"></i></button>' +
            '<button class="btn btn-sm btn-outline-primary mr-1" data-act="edit" title="Editar"><i class="fas fa-pen"></i></button>' +
            '<button class="btn btn-sm btn-outline-secondary mr-1" data-act="move" title="Mover"><i class="fas fa-random"></i></button>' +
            '<button class="btn btn-sm btn-outline-info mr-1" data-act="hist" title="Historial"><i class="fas fa-stream"></i></button>' +
            '<button class="btn btn-sm btn-outline-danger" data-act="del" title="Eliminar"><i class="fas fa-trash"></i></button>' +
          '</td>' +
        '</tr>' +

        '<tr class="invx-extra-row' + extraCls + '" data-extra-for="' + id + '">' +
          '<td colspan="8">' + extraRowHtml(x) + '</td>' +
        '</tr>'
      );
    }).join('');
  }

  function renderGrid() {
    var rows = state.rows || [];
    if (!rows.length) {
      wrapGrid.innerHTML = '<div class="text-muted py-3">Sin registros.</div>';
      return;
    }

    wrapGrid.innerHTML = rows.map(function (x) {
      var id = parseInt(x.id, 10);
      var ck = isSelected(id) ? 'checked' : '';
      var ubic = x.ubicacion_nombre || '—';
      var cats = x.categorias_txt || '—';
      var subtitle = [x.marca, x.modelo].filter(Boolean).join(' • ');
      if (!subtitle) subtitle = '—';

      return (
        '<div class="invx-card" data-id="' + id + '">' +
          '<div class="top">' +
            '<label class="ck"><input type="checkbox" class="rowchk" data-id="' + id + '" ' + ck + '><span></span></label>' +
            badgeEstado(x.estado) +
          '</div>' +

          '<div class="invx-card-body">' +
            '<div class="invx-card-main">' +
              '<div class="code">' + esc(x.codigo_inv || '') + '</div>' +
              '<div class="name">' + esc(x.nombre || '') + '</div>' +
              '<div class="sub text-muted small">' + esc(subtitle) + '</div>' +
              '<div class="meta">' +
                '<div><i class="fas fa-map-marker-alt mr-1"></i>' + esc(ubic) + '</div>' +
                '<div><i class="fas fa-user mr-1"></i>' + esc(respLabel(x)) + '</div>' +
              '</div>' +
              '<div class="cats text-muted small"><i class="fas fa-tags mr-1"></i>' + esc(cats) + '</div>' +
            '</div>' +

            '<div class="invx-card-side">' +
              thumbHtml(x, 'card') +
            '</div>' +
          '</div>' +

          '<div class="foot">' +
            '<div class="qty">' + esc(x.cantidad || '') + ' ' + esc(x.unidad || '') + '</div>' +
            '<div class="acts">' +
              '<button class="btn btn-sm btn-outline-dark mr-1" data-act="qr" title="QR"><i class="fas fa-qrcode"></i></button>' +
              '<button class="btn btn-sm btn-outline-primary mr-1" data-act="edit" title="Editar"><i class="fas fa-pen"></i></button>' +
              '<button class="btn btn-sm btn-outline-secondary mr-1" data-act="move" title="Mover"><i class="fas fa-random"></i></button>' +
              '<button class="btn btn-sm btn-outline-info mr-1" data-act="hist" title="Historial"><i class="fas fa-stream"></i></button>' +
              '<button class="btn btn-sm btn-outline-danger" data-act="del" title="Eliminar"><i class="fas fa-trash"></i></button>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
    }).join('');
  }

function paintPager() {
  var pages = Math.max(1, Math.ceil((state.total || 0) / state.per));
  var cur = Math.min(state.page, pages);

  function li(p, label, cls) {
    return '<li class="page-item ' + (cls || '') + '">' +
      '<a class="page-link" href="#" data-page="' + p + '">' + label + '</a>' +
    '</li>';
  }

  function dots() {
    return '<li class="page-item disabled"><span class="page-link">…</span></li>';
  }

  var out = [];

  // Prev
  out.push(li(cur - 1, '&laquo;', (cur <= 1) ? 'disabled' : ''));

  // Ventana alrededor del actual
  var win = 3; // 3 a la izquierda y 3 a la derecha
  var start = Math.max(1, cur - win);
  var end   = Math.min(pages, cur + win);

  // Primera página + dots si hace falta
  if (start > 1) {
    out.push(li(1, '1', (cur === 1) ? 'active' : ''));
    if (start > 2) out.push(dots());
  }

  // Páginas intermedias (evita duplicar 1 y last)
  for (var p = start; p <= end; p++) {
    if (p === 1 || p === pages) continue;
    out.push(li(p, String(p), (p === cur) ? 'active' : ''));
  }

  // Última página + dots si hace falta
  if (end < pages) {
    if (end < pages - 1) out.push(dots());
    out.push(li(pages, String(pages), (cur === pages) ? 'active' : ''));
  }

  // Next
  out.push(li(cur + 1, '&raquo;', (cur >= pages) ? 'disabled' : ''));

  pager.innerHTML = out.join('');
}

  // ---- Filtros ----
  qInput.addEventListener('input', function () { state.q = qInput.value.trim(); state.page = 1; debounceLoad(); });
  btnClear.addEventListener('click', function () { qInput.value = ''; state.q = ''; state.page = 1; loadList(); });

  fTipo.addEventListener('change', function () { state.tipo = fTipo.value; state.page = 1; loadList(); });
  fEstado.addEventListener('change', function () { state.estado = fEstado.value; state.page = 1; loadList(); });
  fActivo.addEventListener('change', function () { state.activo = fActivo.value; state.page = 1; loadList(); });

  btnViewList.addEventListener('click', function () { state.view = 'list'; render(); });
  btnViewGrid.addEventListener('click', function () { state.view = 'grid'; render(); });

  pager.addEventListener('click', function (e) {
    var a = e.target.closest('a[data-page]');
    if (!a) return;
    e.preventDefault();
    var liEl = a.parentElement;
    if (liEl.classList.contains('disabled') || liEl.classList.contains('active')) return;
    var p = parseInt(a.getAttribute('data-page'), 10);
    if (p > 0) { state.page = p; loadList(); }
  });

function resetAllFilters() {
  // filtros
  qInput.value = ''; state.q = '';
  fTipo.value = '';  state.tipo = '';
  fEstado.value = ''; state.estado = '';
  fActivo.value = ''; state.activo = '';
  state.catIds = [];
  if (catInput) catInput.value = '';

  // paginación y selección (opcional pero recomendado)
  state.page = 1;
  state.selected = {};
  chkAll.checked = false;
  updateSelInfo();

  syncCatChips();
  loadList();
}

if (btnResetFilters) {
  btnResetFilters.addEventListener('click', function () {
    resetAllFilters();
  });
}

  // ---- Selección ----
  chkAll.addEventListener('change', function () {
    var on = !!chkAll.checked;
    $all('.rowchk').forEach(function (c) {
      c.checked = on;
      setSelected(parseInt(c.getAttribute('data-id'), 10), on);
    });
  });

  btnSelAll.addEventListener('click', function () {
    $all('.rowchk').forEach(function (c) {
      c.checked = true;
      setSelected(parseInt(c.getAttribute('data-id'), 10), true);
    });
    chkAll.checked = true;
  });

  btnSelNone.addEventListener('click', function () {
    state.selected = {};
    $all('.rowchk').forEach(function (c) { c.checked = false; });
    chkAll.checked = false;
    updateSelInfo();
  });

  document.addEventListener('change', function (e) {
    var c = e.target.closest('.rowchk');
    if (!c) return;
    var id = parseInt(c.getAttribute('data-id'), 10);
    setSelected(id, !!c.checked);
  });
  
  function catNameById(id) {
  var cats = (state.meta && state.meta.categorias) ? state.meta.categorias : [];
  for (var i = 0; i < cats.length; i++) {
    if (parseInt(cats[i].id, 10) === id) return cats[i].nombre || ('ID ' + id);
  }
  return 'ID ' + id;
}

function paintCatOptions() {
  if (!catOptions) return;
  var cats = (state.meta && state.meta.categorias) ? state.meta.categorias : [];
  catOptions.innerHTML = cats.map(function (c) {
    // SOLO nombre (sin "id -")
    return '<option value="' + esc(c.nombre) + '"></option>';
  }).join('');
  if (catInput) catInput.disabled = (cats.length === 0);
}

function syncCatChips() {
  if (!catChips) return;

  if (!state.catIds.length) {
    catChips.innerHTML = '<span class="invx-chip">Todas</span>';
    return;
  }

  catChips.innerHTML = state.catIds.map(function (id) {
    var name = catNameById(id);
    return (
      '<span class="invx-chip invx-chip-cat" data-id="' + id + '">' +
        esc(name) +
        ' <button type="button" class="x" title="Quitar">&times;</button>' +
      '</span>'
    );
  }).join(' ');
}

function setCatIdsUnique(arr) {
  var m = {};
  (arr || []).forEach(function (n) { if (n > 0) m[n] = true; });
  state.catIds = Object.keys(m).map(function (x) { return parseInt(x, 10); });
}

function addCategoryFromInput(raw) {
  raw = (raw || '').trim();
  if (!raw) return;

  var id = 0;

  // Si escribió número, lo tomamos como ID
  if (/^\d+$/.test(raw)) {
    id = parseInt(raw, 10);
  } else {
    var ln = raw.toLowerCase();
    var cats = (state.meta && state.meta.categorias) ? state.meta.categorias : [];

    // match exacto por nombre
    for (var i = 0; i < cats.length; i++) {
      var nm = String(cats[i].nombre || '').toLowerCase();
      if (nm === ln) { id = parseInt(cats[i].id, 10); break; }
    }

    // si no hubo exacto, match parcial único
    if (!id) {
      var found = [];
      for (var j = 0; j < cats.length; j++) {
        var nm2 = String(cats[j].nombre || '').toLowerCase();
        if (nm2.indexOf(ln) >= 0) found.push(cats[j]);
      }
      if (found.length === 1) id = parseInt(found[0].id, 10);
    }
  }

  if (!id) {
    alert('Categoría no encontrada: ' + raw);
    return;
  }

  setCatIdsUnique((state.catIds || []).concat([id]));
  if (catInput) catInput.value = '';
  state.page = 1;
  syncCatChips();
  loadList();
}

// Enter para agregar categoría
if (catInput) {
  catInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      addCategoryFromInput(catInput.value);
    }
  });

  // Si el usuario elige del autocompletar (change) también la agregamos
  catInput.addEventListener('change', function () {
    addCategoryFromInput(catInput.value);
  });
}

// Click en X de chip para quitar
if (catChips) {
  catChips.addEventListener('click', function (e) {
    var x = e.target.closest('button.x');
    if (!x) return;
    var chip = x.closest('.invx-chip-cat');
    if (!chip) return;
    var id = parseInt(chip.getAttribute('data-id'), 10);
    state.catIds = (state.catIds || []).filter(function (n) { return n !== id; });
    state.page = 1;
    syncCatChips();
    loadList();
  });
}

// Botón borrar categorías
if (btnCatClear) {
  btnCatClear.addEventListener('click', function () {
    state.catIds = [];
    if (catInput) catInput.value = '';
    state.page = 1;
    syncCatChips();
    loadList();
  });
}

  // ---- Acciones por fila/card ----
  function rowById(id) {
    for (var i = 0; i < state.rows.length; i++) {
      if (parseInt(state.rows[i].id, 10) === id) return state.rows[i];
    }
    return null;
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-act]');
    if (!btn) return;

    var wrap = btn.closest('[data-id]');
    if (!wrap) return;
    var id = parseInt(wrap.getAttribute('data-id'), 10);
    if (!id) return;

    var act = btn.getAttribute('data-act');
    if (act === 'edit') openBien(id);
    if (act === 'move') openMove(id);
    if (act === 'qr') openQR(id);
    if (act === 'hist') openHist(id);
    if (act === 'del') doDelete(id);
  });
  
  // ---- Toggle fila extra (solo LISTA) ----
  document.addEventListener('click', function (e) {
    if (state.view !== 'list') return;

    // No toggle si se hizo click en checkbox/acciones/controles
    if (e.target.closest('button, a, input, label, .invx-no-toggle')) return;

    var tr = e.target.closest('tr.invx-main-row');
    if (!tr) return;

    var id = parseInt(tr.getAttribute('data-id'), 10);
    if (!id) return;

    toggleExtraRow(id);
  });

  async function doDelete(id) {
    if (!confirm('¿Eliminar este bien? (Se eliminarán también sus movimientos)')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', String(id));
    try {
      await j(API, { method: 'POST', body: fd });
      setSelected(id, false);
      await loadList();
    } catch (e) {
      alert(e.message || 'Error');
    }
  }

  // =========================
  // IMAGEN: subida directa S4
  // =========================
  var imgObjUrl = null;
  var imgUploading = false;
  var imgUploadErr = null;

  function bienSaveBtn() {
    return frmBien ? frmBien.querySelector('button[type="submit"]') : null;
  }
  function setBienBusy(on) {
    var b = bienSaveBtn();
    if (b) b.disabled = !!on;
  }

  function imgProgressShow(pct) {
    if (!bienImgProgWrap || !bienImgProgBar) return;
    if (pct == null) {
      bienImgProgWrap.classList.add('d-none');
      bienImgProgBar.style.width = '0%';
      bienImgProgBar.textContent = '0%';
      return;
    }
    bienImgProgWrap.classList.remove('d-none');
    var p = Math.max(0, Math.min(100, Math.round(pct)));
    bienImgProgBar.style.width = p + '%';
    bienImgProgBar.textContent = p + '%';
  }

  function imgSetTouched(v) {
    if (bienImgTouched) bienImgTouched.value = v ? '1' : '0';
  }

  function imgClearObjectUrl() {
    if (imgObjUrl) {
      try { URL.revokeObjectURL(imgObjUrl); } catch (e) {}
      imgObjUrl = null;
    }
  }

  function imgPublicUrl(key, bust) {
    if (!CFG.img_public) return '';
    var sep = (CFG.img_public.indexOf('?') >= 0) ? '&' : '?';
    var url = CFG.img_public + sep + 'key=' + encodeURIComponent(String(key || ''));
    if (bust) url += '&v=' + Date.now();
    return url;
  }

  function imgSetPreview(src) {
    if (!bienImgPreview) return;
    if (src) {
      bienImgPreview.src = src;
      bienImgPreview.style.display = '';
      if (btnImgRemove) btnImgRemove.style.display = '';
    } else {
      bienImgPreview.src = '';
      bienImgPreview.style.display = 'none';
      if (btnImgRemove) btnImgRemove.style.display = 'none';
    }
  }

  function imgResetUI() {
    imgUploading = false;
    imgUploadErr = null;
    imgProgressShow(null);
    imgClearObjectUrl();

    if (bienImgFile) bienImgFile.value = '';
    if (bienImgKey) bienImgKey.value = '';
    imgSetTouched(false);
    imgSetPreview('');
  }

  function imgLoadExistingKey(key) {
    imgUploading = false;
    imgUploadErr = null;
    imgProgressShow(null);
    imgClearObjectUrl();

    if (bienImgKey) bienImgKey.value = key ? String(key) : '';
    if (bienImgFile) bienImgFile.value = '';
    imgSetTouched(false);

    if (key) imgSetPreview(imgPublicUrl(key, true));
    else imgSetPreview('');
  }

  function parseSignResp(d) {
    var x = d && d.data ? d.data : d;
    if (!x) return null;
    return {
      key: x.key || x.img_key || x.object_key || x.s4_key || '',
      put_url: x.put_url || x.url || x.signed_url || x.s4_put_url || x.uploadUrl || ''
    };
  }

  function putWithProgress(url, file) {
    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open('PUT', url, true);

      if (file && file.type) {
        try { xhr.setRequestHeader('Content-Type', file.type); } catch (e) {}
      }

      xhr.upload.onprogress = function (ev) {
        if (ev.lengthComputable) imgProgressShow((ev.loaded / ev.total) * 100);
      };
      xhr.onerror = function () { reject(new Error('Error de red al subir imagen')); };
      xhr.onabort = function () { reject(new Error('Subida cancelada')); };
      xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) resolve(true);
        else reject(new Error('Falló la subida (HTTP ' + xhr.status + ')'));
      };
      xhr.send(file);
    });
  }

  async function uploadBienImage(file) {
    if (!CFG.sign_img || !CFG.img_public) {
      throw new Error('Falta configurar INV_CFG.sign_img / INV_CFG.img_public');
    }
    if (!file) throw new Error('Archivo inválido');

    var maxMb = 8;
    if (file.size > maxMb * 1024 * 1024) {
      throw new Error('La imagen pesa demasiado. Máximo ' + maxMb + 'MB.');
    }

    // 1) pedir firma (tu api_bien_img.php actual usa JSON, pero aquí usas FormData en tu versión original;
    // si ya lo tienes funcionando, mantenlo así. Si no, ajusta api_bien_img.php para aceptar FormData.
    // (En tu caso ya lo tenías funcionando.)
    var fd = new FormData();
    fd.append('filename', file.name || 'imagen');
    fd.append('content_type', file.type || 'application/octet-stream');
    fd.append('size', String(file.size || 0));

    var sig = await j(CFG.sign_img, { method: 'POST', body: fd });
    var s = parseSignResp(sig);
    if (!s || !s.key || !s.put_url) throw new Error('Respuesta inválida de firma de imagen');

    imgProgressShow(0);
    await putWithProgress(s.put_url, file);
    imgProgressShow(100);

    return s.key;
  }

  if (bienImgFile) {
    bienImgFile.addEventListener('change', async function () {
      imgUploadErr = null;

      var file = (bienImgFile.files && bienImgFile.files[0]) ? bienImgFile.files[0] : null;
      if (!file) return;

      imgClearObjectUrl();
      imgObjUrl = URL.createObjectURL(file);
      imgSetPreview(imgObjUrl);

      imgSetTouched(true);

      try {
        imgUploading = true;
        setBienBusy(true);
        imgProgressShow(1);

        var key = await uploadBienImage(file);
        if (bienImgKey) bienImgKey.value = String(key);

        imgClearObjectUrl();
        imgSetPreview(imgPublicUrl(key, true));

        imgUploading = false;
        setBienBusy(false);
        setTimeout(function () { imgProgressShow(null); }, 400);

      } catch (e) {
        imgUploading = false;
        imgUploadErr = e;
        setBienBusy(false);
        imgProgressShow(null);

        if (mBienErr) {
          mBienErr.textContent = (e && e.message) ? e.message : 'Error al subir imagen';
          mBienErr.classList.remove('d-none');
        }
      }
    });
  }

  if (btnImgRemove) {
    btnImgRemove.addEventListener('click', function () {
      imgClearObjectUrl();
      if (bienImgFile) bienImgFile.value = '';
      if (bienImgKey) bienImgKey.value = '';
      imgSetTouched(true);
      imgUploadErr = null;
      imgUploading = false;
      imgProgressShow(null);
      imgSetPreview('');
    });
  }

  // ---- Modal Bien ----
  function setRespMode(mode) {
    if (mode === 'TEXT') {
      respTextWrap.classList.remove('d-none');
      respUserWrap.classList.add('d-none');
      respTextRadio.checked = true;
    } else {
      respTextWrap.classList.add('d-none');
      respUserWrap.classList.remove('d-none');
      respUserRadio.checked = true;
    }
  }

  respUserRadio.addEventListener('change', function () { if (respUserRadio.checked) setRespMode('USER'); });
  respTextRadio.addEventListener('change', function () { if (respTextRadio.checked) setRespMode('TEXT'); });

  btnNew.addEventListener('click', function () { openBien(0); });

  async function openBien(id) {
    mBienErr.classList.add('d-none');
    mBienErr.textContent = '';
    frmBien.reset();
    bienId.value = id ? String(id) : '';

    if (bienImgFile) imgResetUI();
    bienCats.innerHTML = buildCatsUI(state.meta.categorias || [], []);

    bienCodeHint.textContent = id ? 'Código se calcula automáticamente (INV-...)' : 'Al guardar se crea su código (INV-...)';

    if (id > 0) {
      mBienTitle.textContent = 'Editar bien';

      var d = await j(API + '?action=get&id=' + encodeURIComponent(String(id)));
      var r = d.data || {};
      var catsSel = d.categorias || [];

      bienId.value = String(r.id || id);
      bienTipo.value = r.tipo || 'EQUIPO';
      bienEstado.value = r.estado || 'BUENO';
      bienActivo.value = String((r.activo != null) ? r.activo : 1);

      bienNombre.value = r.nombre || '';
      bienDesc.value = r.descripcion || '';

      bienMarca.value = r.marca || '';
      bienModelo.value = r.modelo || '';
      bienSerie.value = r.serie || '';

      bienCant.value = (r.cantidad != null) ? String(r.cantidad) : '1';
      bienUnidad.value = r.unidad || 'UND';

      bienUbic.value = (r.id_ubicacion != null && String(r.id_ubicacion) !== '0') ? String(r.id_ubicacion) : '';

      if (r.id_responsable != null && String(r.id_responsable) !== '' && String(r.id_responsable) !== '0') {
        setRespMode('USER');
        bienRespUser.value = String(r.id_responsable);
        bienRespNom.value = '';
        bienRespApe.value = '';
        bienRespDni.value = '';
      } else if ((r.responsable_nombres || r.responsable_apellidos || r.responsable_dni)) {
        setRespMode('TEXT');
        bienRespUser.value = '';
        bienRespNom.value = r.responsable_nombres || '';
        bienRespApe.value = r.responsable_apellidos || '';
        bienRespDni.value = r.responsable_dni || '';
      } else {
        setRespMode('USER');
        bienRespUser.value = '';
        bienRespNom.value = '';
        bienRespApe.value = '';
        bienRespDni.value = '';
      }

      bienCats.innerHTML = buildCatsUI(state.meta.categorias || [], catsSel);
      bienNotas.value = r.notas || '';

      if (r.codigo_inv) bienCodeHint.textContent = 'Código: ' + r.codigo_inv;

      if (bienImgFile) {
        var key = r.img_key || r.imgKey || '';
        imgLoadExistingKey(key ? String(key) : '');
      }

    } else {
      mBienTitle.textContent = 'Nuevo bien';
      bienId.value = '';
      bienTipo.value = 'EQUIPO';
      bienEstado.value = 'BUENO';
      bienActivo.value = '1';

      bienNombre.value = '';
      bienDesc.value = '';
      bienMarca.value = '';
      bienModelo.value = '';
      bienSerie.value = '';

      bienCant.value = '1';
      bienUnidad.value = (bienUnidad.value || 'UND');
      bienUbic.value = '';

      setRespMode('USER');
      bienRespUser.value = '';
      bienRespNom.value = '';
      bienRespApe.value = '';
      bienRespDni.value = '';

      bienCats.innerHTML = buildCatsUI(state.meta.categorias || [], []);
      bienNotas.value = '';
      bienCodeHint.textContent = 'Al guardar se crea su código (INV-...)';

      if (bienImgFile) imgLoadExistingKey('');
    }

    showModal(mdlBien);
  }

  frmBien.addEventListener('submit', async function (e) {
    e.preventDefault();

    mBienErr.classList.add('d-none');
    mBienErr.textContent = '';

    if (bienImgFile) {
      if (imgUploading) {
        mBienErr.textContent = 'Espera: la imagen todavía se está subiendo...';
        mBienErr.classList.remove('d-none');
        return;
      }
      if (imgUploadErr) {
        mBienErr.textContent = (imgUploadErr && imgUploadErr.message) ? imgUploadErr.message : 'Error al subir imagen';
        mBienErr.classList.remove('d-none');
        return;
      }
    }

    var fd = new FormData();
    fd.append('action', 'save');
    fd.append('id', bienId.value ? String(bienId.value) : '0');

    fd.append('tipo', bienTipo.value || 'EQUIPO');
    fd.append('estado', bienEstado.value || 'BUENO');
    fd.append('activo', bienActivo.value || '1');

    fd.append('nombre', (bienNombre.value || '').trim());
    fd.append('descripcion', (bienDesc.value || '').trim());

    fd.append('marca', (bienMarca.value || '').trim());
    fd.append('modelo', (bienModelo.value || '').trim());
    fd.append('serie', (bienSerie.value || '').trim());

    fd.append('cantidad', (bienCant.value || '1').trim());
    fd.append('unidad', bienUnidad.value || 'UND');

    fd.append('id_ubicacion', (bienUbic.value || '').trim());

    var respMode = (document.querySelector('input[name="respMode"]:checked') || {}).value || 'USER';
    fd.append('resp_mode', respMode);

    if (respMode === 'TEXT') {
      fd.append('id_responsable', '');
      fd.append('resp_nombres', (bienRespNom.value || '').trim());
      fd.append('resp_apellidos', (bienRespApe.value || '').trim());
      fd.append('resp_dni', (bienRespDni.value || '').trim());
    } else {
      fd.append('id_responsable', (bienRespUser.value || '').trim());
      fd.append('resp_nombres', '');
      fd.append('resp_apellidos', '');
      fd.append('resp_dni', '');
    }

    var cats = getCatsSelectedFromUI(bienCats);
    fd.append('categorias', cats.join(','));

    fd.append('notas', (bienNotas.value || '').trim());

    if (bienImgFile && bienImgTouched && bienImgTouched.value === '1') {
      fd.append('img_key', (bienImgKey && bienImgKey.value) ? String(bienImgKey.value).trim() : '');
    }

    try {
      await j(API, { method: 'POST', body: fd });
      hideModal(mdlBien);
      await loadList();
    } catch (err) {
      mBienErr.textContent = (err && err.message) ? err.message : 'Error al guardar';
      mBienErr.classList.remove('d-none');
    }
  });

  // ---- Modal Movimiento ----
  var mvCtx = { id: 0, curUb: null, curRespId: null, curN: null, curA: null, curD: null, code: '', name: '' };

  function setMvRespMode(mode) {
    if (mode === 'TEXT') {
      mvRespTextWrap.classList.remove('d-none');
      mvRespUserWrap.classList.add('d-none');
      mvRespTextRadio.checked = true;
    } else {
      mvRespTextWrap.classList.add('d-none');
      mvRespUserWrap.classList.remove('d-none');
      mvRespUserRadio.checked = true;
    }
  }

  mvRespUserRadio.addEventListener('change', function () { if (mvRespUserRadio.checked) setMvRespMode('USER'); });
  mvRespTextRadio.addEventListener('change', function () { if (mvRespTextRadio.checked) setMvRespMode('TEXT'); });

  async function openMove(id) {
    mMoveErr.classList.add('d-none');
    mMoveErr.textContent = '';
    frmMove.reset();

    var r = rowById(id);
    if (!r) {
      try {
        var d = await j(API + '?action=get&id=' + encodeURIComponent(String(id)));
        r = d.data || {};
        if (!r.id) r.id = id;
      } catch (e) {
        alert(e.message || 'No se pudo cargar el bien');
        return;
      }
    }

    mvCtx.id = id;
    mvCtx.curUb = (r.id_ubicacion != null && String(r.id_ubicacion) !== '0' && String(r.id_ubicacion) !== '') ? String(r.id_ubicacion) : '';
    mvCtx.curRespId = (r.id_responsable != null && String(r.id_responsable) !== '0' && String(r.id_responsable) !== '') ? String(r.id_responsable) : '';
    mvCtx.curN = r.responsable_nombres || '';
    mvCtx.curA = r.responsable_apellidos || '';
    mvCtx.curD = r.responsable_dni || '';
    mvCtx.code = r.codigo_inv || '';
    mvCtx.name = r.nombre || '';

    mvId.value = String(id);
    mvCode.textContent = mvCtx.code || '—';
    mvName.textContent = mvCtx.name || '—';

    if (mvUbic.querySelector('option[value="0"]') == null) {
      var opt0 = document.createElement('option');
      opt0.value = '0';
      opt0.textContent = '— Quitar ubicación —';
      mvUbic.insertBefore(opt0, mvUbic.children[1] || null);
    }
    if (mvRespUserSel.querySelector('option[value="0"]') == null) {
      var optR0 = document.createElement('option');
      optR0.value = '0';
      optR0.textContent = '— Quitar responsable —';
      mvRespUserSel.insertBefore(optR0, mvRespUserSel.children[1] || null);
    }

    mvUbic.value = '';
    mvNota.value = '';

    setMvRespMode('USER');
    mvRespUserSel.value = '';
    mvRespNom.value = '';
    mvRespApe.value = '';
    mvRespDni.value = '';

    showModal(mdlMove);
  }

  frmMove.addEventListener('submit', async function (e) {
    e.preventDefault();

    mMoveErr.classList.add('d-none');
    mMoveErr.textContent = '';

    var id = parseInt(mvId.value || '0', 10);
    if (!id) return;

    var ubicVal = (mvUbic.value || '').trim();
    var sendUbic = '';
    if (ubicVal === '') sendUbic = mvCtx.curUb || '';
    else if (ubicVal === '0') sendUbic = '';
    else sendUbic = ubicVal;

    var respMode = (document.querySelector('input[name="mvRespMode"]:checked') || {}).value || 'USER';

    var sendRespMode = respMode;
    var sendRespId = '';
    var sendN = '';
    var sendA = '';
    var sendD = '';

    if (respMode === 'USER') {
      var ru = (mvRespUserSel.value || '').trim();
      if (ru === '') {
        if (mvCtx.curRespId) {
          sendRespId = mvCtx.curRespId;
          sendRespMode = 'USER';
        } else if (mvCtx.curN || mvCtx.curA || mvCtx.curD) {
          sendRespMode = 'TEXT';
          sendN = mvCtx.curN || '';
          sendA = mvCtx.curA || '';
          sendD = mvCtx.curD || '';
        } else {
          sendRespId = '';
          sendRespMode = 'USER';
        }
      } else if (ru === '0') {
        sendRespId = '';
        sendRespMode = 'USER';
      } else {
        sendRespId = ru;
        sendRespMode = 'USER';
      }
    } else {
      sendRespMode = 'TEXT';
      sendN = (mvRespNom.value || '').trim();
      sendA = (mvRespApe.value || '').trim();
      sendD = (mvRespDni.value || '').trim();

      if (!sendN && !sendA && !sendD) {
        sendRespMode = 'USER';
        sendRespId = '';
      }
    }

    var changedUb = (String(sendUbic || '') !== String(mvCtx.curUb || ''));
    var curRespKind = mvCtx.curRespId ? 'USER' : ((mvCtx.curN || mvCtx.curA || mvCtx.curD) ? 'TEXT' : 'NONE');
    var newRespKind = (sendRespMode === 'USER')
      ? (sendRespId ? 'USER' : 'NONE')
      : ((sendN || sendA || sendD) ? 'TEXT' : 'NONE');

    var changedResp = false;
    if (curRespKind !== newRespKind) changedResp = true;
    else if (curRespKind === 'USER' && String(mvCtx.curRespId || '') !== String(sendRespId || '')) changedResp = true;
    else if (curRespKind === 'TEXT') {
      if (String(mvCtx.curN || '') !== String(sendN || '')) changedResp = true;
      if (String(mvCtx.curA || '') !== String(sendA || '')) changedResp = true;
      if (String(mvCtx.curD || '') !== String(sendD || '')) changedResp = true;
    }

    var nota = (mvNota.value || '').trim();
    if (!changedUb && !changedResp && !nota) {
      mMoveErr.textContent = 'No has cambiado ubicación ni responsable (ni agregaste nota).';
      mMoveErr.classList.remove('d-none');
      return;
    }

    var fd = new FormData();
    fd.append('action', 'move');
    fd.append('id', String(id));
    fd.append('id_ubicacion', sendUbic);
    fd.append('nota', nota);

    fd.append('resp_mode', sendRespMode);
    fd.append('id_responsable', sendRespId);
    fd.append('resp_nombres', sendN);
    fd.append('resp_apellidos', sendA);
    fd.append('resp_dni', sendD);

    try {
      await j(API, { method: 'POST', body: fd });
      hideModal(mdlMove);
      await loadList();
    } catch (err) {
      mMoveErr.textContent = (err && err.message) ? err.message : 'Error al registrar movimiento';
      mMoveErr.classList.remove('d-none');
    }
  });

// ---- Modal QR ----
function openQR(id) {
  var r = rowById(id);
  if (!r) return;

  // Llenado de UI (modular, para no inflar inventario.js)
  if (window.INV_QR && typeof window.INV_QR.open === 'function') {
    window.INV_QR.open(id, r);
  } else {
    // Fallback mínimo (por si no cargó el script)
    var code = r.codigo_inv || '';
    var elCode = document.getElementById('qrCode');
    var elQrImg = document.getElementById('qrImg');
    var elOpen = document.getElementById('qrOpen');

    if (elCode) elCode.textContent = code || '—';
    if (elQrImg) elQrImg.src = (CFG.qr + '?id=' + encodeURIComponent(String(id)) + '&s=6');

    if (elOpen) {
      if (code) {
        elOpen.href = CFG.detalle + '?code=' + encodeURIComponent(code);
        elOpen.classList.remove('d-none');
      } else {
        elOpen.href = '#';
        elOpen.classList.add('d-none');
      }
    }
  }

  showModal(mdlQR);
}

  // ---- Modal Historial ----
  function fmtDate(dt) {
    if (!dt) return '';
    var s = String(dt);
    var d = s.substring(0, 10);
    var t = s.substring(11, 16);
    var p = d.split('-');
    if (p.length === 3) return p[2] + '/' + p[1] + '/' + p[0] + ' ' + t;
    return s;
  }

  function mvWho(m, side) {
    var u = (side === 'desde') ? (m.desde_resp_user || '') : (m.hacia_resp_user || '');
    if (u) return u;

    var tx = (side === 'desde') ? (m.desde_resp_texto || '') : (m.hacia_resp_texto || '');
    tx = String(tx || '').trim();
    if (tx) return tx;

    return '—';
  }

  function mvWhere(m, side) {
    var x = (side === 'desde') ? (m.desde_ubic_nombre || '') : (m.hacia_ubic_nombre || '');
    x = String(x || '').trim();
    return x || '—';
  }

  async function openHist(id) {
    var r = rowById(id);
    if (!r) return;

    histCode.textContent = r.codigo_inv || '—';
    histName.textContent = r.nombre || '—';
    histBody.innerHTML = '<div class="text-muted py-2">Cargando…</div>';

    showModal(mdlHist);

    try {
      var d = await j(API + '?action=mov_list&id=' + encodeURIComponent(String(id)));
      var rows = d.data || [];

      if (!rows.length) {
        histBody.innerHTML = '<div class="text-muted py-2">Sin movimientos registrados.</div>';
        return;
      }

      histBody.innerHTML = rows.map(function (m) {
        var tipo = esc(m.tipo || '');
        var fecha = esc(fmtDate(m.creado));
        var nota = esc(m.nota || '');
        var by = esc(m.hecho_por || '');

        var desdeU = esc(mvWhere(m, 'desde'));
        var haciaU = esc(mvWhere(m, 'hacia'));
        var desdeR = esc(mvWho(m, 'desde'));
        var haciaR = esc(mvWho(m, 'hacia'));

        return (
          '<div class="invx-hitem">' +
            '<div class="tline"></div>' +
            '<div class="dot"></div>' +
            '<div class="box">' +
              '<div class="top">' +
                '<span class="tag">' + tipo + '</span>' +
                '<span class="date">' + fecha + '</span>' +
              '</div>' +
              '<div class="mid">' +
                '<div class="row">' +
                  '<div class="col-12 col-md-6 mb-1"><i class="fas fa-map-marker-alt mr-1"></i><b>Ubicación:</b> ' +
                    '<span class="text-muted">' + desdeU + '</span> <i class="fas fa-arrow-right mx-1"></i> <span>' + haciaU + '</span>' +
                  '</div>' +
                  '<div class="col-12 col-md-6 mb-1"><i class="fas fa-user mr-1"></i><b>Responsable:</b> ' +
                    '<span class="text-muted">' + desdeR + '</span> <i class="fas fa-arrow-right mx-1"></i> <span>' + haciaR + '</span>' +
                  '</div>' +
                '</div>' +
              '</div>' +
              (nota ? ('<div class="note"><i class="far fa-sticky-note mr-1"></i>' + nota + '</div>') : '') +
              '<div class="bot text-muted small">Hecho por: ' + by + '</div>' +
            '</div>' +
          '</div>'
        );
      }).join('');

    } catch (err) {
      histBody.innerHTML = '<div class="alert alert-danger">Error: ' + esc(err.message || 'No se pudo cargar') + '</div>';
    }
  }

 // ---- Imprimir QRs (seleccionados) ----
btnPrintSelected.addEventListener('click', function (e) {
  e.preventDefault();

  var ids = selectedIds();
  if (!ids.length) {
    alert('Selecciona al menos un bien para imprimir.');
    return;
  }

  // No mandes margen: deja que qr_pdf.php use sus defaults (A4=10, Ticket=2)
  var url = CFG.pdf
    + '?ids=' + encodeURIComponent(ids.join(','))
    + '&paper=' + encodeURIComponent(String(printPaper))
    + '&mm=' + encodeURIComponent(String(printMm))
    + '&b=1';

  window.open(url, '_blank');

  // Cerrar el dropdown al imprimir (como pediste)
  if (window.jQuery && btnPrintToggle) {
    try { window.jQuery(btnPrintToggle).dropdown('toggle'); } catch (err) {}
  }
});

  // ---- Init ----
  (async function init() {
    try {
      paintPrintMenu();
      await loadMeta();
      await loadStats();
      await loadList();
    } catch (e) {
      showErr(e.message || 'Error al iniciar');
    }
  })();

})();
