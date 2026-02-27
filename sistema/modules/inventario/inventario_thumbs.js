/* modules/inventario/inventario_thumbs.js
   Lazy-load + placeholder para miniaturas de inventario (Bootstrap 4 / AdminLTE 3)
*/
(function () {
  // Placeholder liviano (SVG) para pintar algo instantáneo
  var PLACEHOLDER =
    'data:image/svg+xml;charset=utf-8,' +
    encodeURIComponent(
      '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120">' +
        '<rect width="120" height="120" fill="#f1f5f9"/>' +
        '<path d="M30 84l18-22 16 18 10-12 16 16v8H30z" fill="#e2e8f0"/>' +
        '<circle cx="46" cy="46" r="10" fill="#e2e8f0"/>' +
      '</svg>'
    );

  var io = null;

  function markLoaded(img) {
    img.classList.add('is-loaded');
    img.classList.remove('is-loading');
  }

  function markError(img) {
    img.classList.add('is-error');
    img.classList.remove('is-loading');
  }

  function loadOne(img) {
    if (!img || img.dataset.loaded === '1') return;

    var src = img.getAttribute('data-src') || '';
    if (!src) return;

    img.dataset.loaded = '1';

    // Si aún no tiene src, ponemos el placeholder primero
    if (!img.getAttribute('src')) img.setAttribute('src', PLACEHOLDER);

    img.onload = function () { markLoaded(img); };
    img.onerror = function () { markError(img); };

    // Dispara la carga real
    img.src = src;
  }

  function ensureObserver() {
    if (io) return;
    if (!('IntersectionObserver' in window)) return;

    io = new IntersectionObserver(function (entries) {
      for (var i = 0; i < entries.length; i++) {
        var en = entries[i];
        if (en && en.isIntersecting) {
          loadOne(en.target);
          try { io.unobserve(en.target); } catch (e) {}
        }
      }
    }, {
      root: null,
      rootMargin: '250px 0px', // precarga un poco antes de entrar en vista
      threshold: 0.01
    });
  }

  function mount(root) {
    root = root || document;

    var imgs = root.querySelectorAll('img.invx-lazy[data-src]');
    if (!imgs || !imgs.length) return;

    ensureObserver();

    for (var i = 0; i < imgs.length; i++) {
      var img = imgs[i];

      // evita re-observar
      if (img.dataset.bound === '1') continue;
      img.dataset.bound = '1';

      // siempre parte "cargando"
      if (!img.classList.contains('is-loading') && !img.classList.contains('is-loaded')) {
        img.classList.add('is-loading');
      }

      if (io) {
        io.observe(img);
      } else {
        // fallback: carga inmediata (compat)
        loadOne(img);
      }
    }
  }

  window.INV_THUMBS = { mount: mount, placeholder: PLACEHOLDER };
  window.INV_THUMBS_PLACEHOLDER = PLACEHOLDER;
})();
