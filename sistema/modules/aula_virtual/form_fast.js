// Ver 08-03-26
(function () {
  var cfg = window.avExamResolverConfig || {};
  if (String(cfg.mode || '').toUpperCase() !== 'FAST') return;

  var code = String(cfg.code || '').trim();
  if (!code) return;

  var storageKey = 'av_exam_fast_identity_' + code;

  function get(id) {
    return document.getElementById(id);
  }

  function readIdentity() {
    try {
      var raw = localStorage.getItem(storageKey);
      if (!raw) return {};
      var obj = JSON.parse(raw);
      return (obj && typeof obj === 'object') ? obj : {};
    } catch (e) {
      return {};
    }
  }

  function writeIdentity(data) {
    try {
      localStorage.setItem(storageKey, JSON.stringify(data || {}));
    } catch (e) {}
  }

  function normalizeDoc(v) {
    return String(v || '')
      .toUpperCase()
      .replace(/\s+/g, '')
      .replace(/[^A-Z0-9\-]/g, '');
  }

  function normalizePhone(v) {
    return String(v || '').replace(/[^0-9+]/g, '').slice(0, 20);
  }

  function collectIdentity() {
    var categorias = [];
    var checks = document.querySelectorAll('.avex-cat-opt');
    for (var i = 0; i < checks.length; i++) {
      if (checks[i].checked) categorias.push(Number(checks[i].value || 0));
    }
    categorias = categorias.filter(function (x) { return x > 0; });

    return {
      tipo_doc_id: Number(get('avExamTipoDoc') ? get('avExamTipoDoc').value : 0) || 0,
      nro_doc: normalizeDoc(get('avExamNroDoc') ? get('avExamNroDoc').value : ''),
      nombres: String(get('avExamNombres') ? get('avExamNombres').value : '').trim(),
      apellidos: String(get('avExamApellidos') ? get('avExamApellidos').value : '').trim(),
      celular: normalizePhone(get('avExamCelular') ? get('avExamCelular').value : ''),
      categorias: categorias
    };
  }

  function applyIdentity(data) {
    if (!data || typeof data !== 'object') return;

    var sel = get('avExamTipoDoc');
    if (sel && data.tipo_doc_id) {
      sel.value = String(Number(data.tipo_doc_id || 0));
    }
    if (get('avExamNroDoc') && data.nro_doc) {
      get('avExamNroDoc').value = normalizeDoc(data.nro_doc);
    }
    if (get('avExamNombres') && data.nombres) {
      get('avExamNombres').value = String(data.nombres);
    }
    if (get('avExamApellidos') && data.apellidos) {
      get('avExamApellidos').value = String(data.apellidos);
    }
    if (get('avExamCelular') && data.celular) {
      get('avExamCelular').value = normalizePhone(data.celular);
    }

    var cats = Array.isArray(data.categorias) ? data.categorias : [];
    if (cats.length) {
      var map = {};
      cats.forEach(function (c) {
        var id = Number(c || 0);
        if (id > 0) map[id] = true;
      });
      var checks = document.querySelectorAll('.avex-cat-opt');
      for (var i = 0; i < checks.length; i++) {
        var cid = Number(checks[i].value || 0);
        checks[i].checked = !!map[cid];
      }
    }
  }

  function selectedDocCode() {
    var sel = get('avExamTipoDoc');
    if (!sel || !sel.options || !sel.options.length) return '';
    var idx = sel.selectedIndex >= 0 ? sel.selectedIndex : 0;
    var txt = sel.options[idx] ? String(sel.options[idx].text || '') : '';
    return txt.trim().toUpperCase();
  }

  function applyDocRules() {
    var docInput = get('avExamNroDoc');
    if (!docInput) return;

    var codeTxt = selectedDocCode();
    if (codeTxt.indexOf('DNI') >= 0) {
      docInput.maxLength = 8;
      docInput.placeholder = '8 digitos';
      return;
    }
    if (codeTxt.indexOf('CE') >= 0) {
      docInput.maxLength = 12;
      docInput.placeholder = '8 a 12 caracteres';
      return;
    }
    if (codeTxt.indexOf('BREVETE') >= 0) {
      docInput.maxLength = 20;
      docInput.placeholder = '6 a 20 caracteres';
      return;
    }
    docInput.maxLength = 20;
    docInput.placeholder = 'Numero de documento';
  }

  function bindInputs() {
    var doc = get('avExamNroDoc');
    var cel = get('avExamCelular');
    var sel = get('avExamTipoDoc');

    if (doc) {
      doc.addEventListener('input', function () {
        this.value = normalizeDoc(this.value);
      });
      doc.addEventListener('blur', function () {
        writeIdentity(collectIdentity());
      });
    }

    if (cel) {
      cel.addEventListener('input', function () {
        this.value = normalizePhone(this.value);
      });
      cel.addEventListener('blur', function () {
        writeIdentity(collectIdentity());
      });
    }

    if (sel) {
      sel.addEventListener('change', function () {
        applyDocRules();
        writeIdentity(collectIdentity());
      });
    }

    var simpleIds = ['avExamNombres', 'avExamApellidos'];
    simpleIds.forEach(function (id) {
      var el = get(id);
      if (!el) return;
      el.addEventListener('blur', function () {
        writeIdentity(collectIdentity());
      });
    });

    document.addEventListener('change', function (e) {
      if (e.target && e.target.classList && e.target.classList.contains('avex-cat-opt')) {
        writeIdentity(collectIdentity());
      }
    });

    var startBtn = get('avExamStartBtn');
    if (startBtn) {
      startBtn.addEventListener('click', function () {
        writeIdentity(collectIdentity());
      });
    }
  }

  function restoreWhenReady() {
    var tries = 0;
    var timer = setInterval(function () {
      tries++;
      var docSel = get('avExamTipoDoc');
      var docInput = get('avExamNroDoc');
      if (docSel && docInput && docSel.options && docSel.options.length > 0) {
        clearInterval(timer);
        var saved = readIdentity();
        applyIdentity(saved);
        applyDocRules();
        bindInputs();
        return;
      }
      if (tries >= 40) {
        clearInterval(timer);
      }
    }, 150);
  }

  restoreWhenReady();
})();
