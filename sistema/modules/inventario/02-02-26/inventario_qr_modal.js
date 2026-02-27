/* Modal QR: UI (info arriba + imagen/qr abajo) - AdminLTE3/Bootstrap4 - ES5 */
(function () {
  var CFG = window.INV_CFG || {};

  function $(id) { return document.getElementById(id); }

  function norm(v) {
    v = (v == null) ? '' : String(v);
    v = v.replace(/\s+/g, ' ').trim();
    return v ? v : '—';
  }

  function imgPublicUrl(key) {
    if (!CFG.img_public) return '';
    var base = String(CFG.img_public);
    var sep = (base.indexOf('?') >= 0) ? '&' : '?';
    return base + sep + 'key=' + encodeURIComponent(String(key || '')) + '&v=' + new Date().getTime();
  }

  function setEstadoBadge(el, estado) {
    if (!el) return;

    var e = String(estado || '').toUpperCase().trim();

    el.classList.remove('invx-bueno', 'invx-regular', 'invx-averiado');

    if (e === 'REGULAR') el.classList.add('invx-regular');
    else if (e === 'AVERIADO') el.classList.add('invx-averiado');
    else if (e) el.classList.add('invx-bueno');

    el.textContent = e ? e : '—';
  }

  function setBtnEnabled(btn, enabled) {
    if (!btn) return;
    if (enabled) {
      btn.classList.remove('is-disabled');
      btn.disabled = false;
    } else {
      btn.classList.add('is-disabled');
      btn.disabled = true;
    }
  }

  function openInNewTab(url) {
    if (!url) return;
    window.open(url, '_blank');
  }

  function forceDownload(url) {
    if (!url) return;

    // Descarga sin navegar (no recarga página)
    var a = document.createElement('a');
    a.href = url;
    a.setAttribute('download', '');
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }

  // Bind de botones una sola vez
  function bindOnce(btn, fn) {
    if (!btn) return;
    if (btn.getAttribute('data-inv-bound') === '1') return;
    btn.setAttribute('data-inv-bound', '1');
    btn.addEventListener('click', fn);
  }

  // IDs UI
  var elNombre = $('qrBienNombre');
  var elCode   = $('qrCode');
  var elMarca  = $('qrBienMarca');
  var elModelo = $('qrBienModelo');
  var elSerie  = $('qrBienSerie');
  var elEstado = $('qrBienEstado');

  var elImg = $('qrBienImg');
  var elImgEmpty = $('qrBienImgEmpty');

  var elQrImg = $('qrImg');
  var elQrOpen = $('qrOpen');

  var btnImgView = $('btnQrImgView');
  var btnImgDl   = $('btnQrImgDl');
  var btnPngView = $('btnQrPngView');
  var btnPngDl   = $('btnQrPngDl');

  // Handlers (leen data-url)
  bindOnce(btnImgView, function () {
    var url = btnImgView.getAttribute('data-url') || '';
    if (url) openInNewTab(url);
  });
  bindOnce(btnImgDl, function () {
    var url = btnImgDl.getAttribute('data-url') || '';
    if (url) forceDownload(url);
  });
  bindOnce(btnPngView, function () {
    var url = btnPngView.getAttribute('data-url') || '';
    if (url) openInNewTab(url);
  });
  bindOnce(btnPngDl, function () {
    var url = btnPngDl.getAttribute('data-url') || '';
    if (url) forceDownload(url);
  });

  // API pública
  window.INV_QR = window.INV_QR || {};

  window.INV_QR.open = function (id, row) {
    row = row || {};
    var code = row.codigo_inv || '';

    // Texto
    if (elNombre) elNombre.textContent = norm(row.nombre);
    if (elCode) elCode.textContent = code ? String(code) : '—';
    if (elMarca) elMarca.textContent = norm(row.marca);
    if (elModelo) elModelo.textContent = norm(row.modelo);
    if (elSerie) elSerie.textContent = norm(row.serie);
    setEstadoBadge(elEstado, row.estado);

    // QR (imagen dentro del contenedor)
    if (elQrImg) {
      elQrImg.src = (CFG.qr + '?id=' + encodeURIComponent(String(id)) + '&s=6');
    }

    // Botones QR PNG
    var qrView = CFG.qr + '?id=' + encodeURIComponent(String(id)) + '&s=10';
    var qrDl   = CFG.qr + '?id=' + encodeURIComponent(String(id)) + '&dl=1&s=10';

    if (btnPngView) btnPngView.setAttribute('data-url', qrView);
    if (btnPngDl)   btnPngDl.setAttribute('data-url', qrDl);

    setBtnEnabled(btnPngView, true);
    setBtnEnabled(btnPngDl, true);

    // Detalle
    if (elQrOpen) {
      if (code) {
        elQrOpen.href = (CFG.detalle + '?code=' + encodeURIComponent(String(code)));
        elQrOpen.classList.remove('d-none');
      } else {
        elQrOpen.href = '#';
        elQrOpen.classList.add('d-none');
      }
    }

    // Imagen del bien
    var key = '';
    if (row.img_key) key = row.img_key;
    else if (row.imgKey) key = row.imgKey;
    key = String(key || '').trim();

    if (key) {
      var u = imgPublicUrl(key);

      if (elImg) {
        elImg.src = u;
        elImg.classList.remove('d-none');
      }
      if (elImgEmpty) elImgEmpty.classList.add('d-none');

      if (btnImgView) btnImgView.setAttribute('data-url', u);
      if (btnImgDl)   btnImgDl.setAttribute('data-url', u);

      setBtnEnabled(btnImgView, true);
      setBtnEnabled(btnImgDl, true);

    } else {
      if (elImg) {
        elImg.src = '';
        elImg.classList.add('d-none');
      }
      if (elImgEmpty) elImgEmpty.classList.remove('d-none');

      if (btnImgView) btnImgView.setAttribute('data-url', '');
      if (btnImgDl)   btnImgDl.setAttribute('data-url', '');

      setBtnEnabled(btnImgView, false);
      setBtnEnabled(btnImgDl, false);
    }
  };

})();
