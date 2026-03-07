// Ver 07-03-26
(function(){
  const root = document.getElementById('avRoot');
  if (!root) return;

  const config = window.avAulaConfig || {};
  const temaMap = config.temaMap || {};

  const player = root.querySelector('#avPlayer');
  const playlist = root.querySelector('#avPlaylist');
  const progressText = root.querySelector('#avProgressText');
  const countBadge = root.querySelector('#avCountBadge');
  const temaTitulo = root.querySelector('#avTemaTitulo');
  const temaDesc = root.querySelector('#avTemaDesc');
  const tabLinks = Array.from(root.querySelectorAll('.av-tabs .nav-link[href^="#tab_"]'));

  const total = Number(config.totalTemas || 0);
  let done = 0;

  function esc(s){
    return String(s ?? '').replace(/[&<>"']/g, function(m){
      return {
        '&':'&amp;',
        '<':'&lt;',
        '>':'&gt;',
        '"':'&quot;',
        "'":'&#39;'
      }[m];
    });
  }

  function temaFromItem(el){
    const id = String(el?.getAttribute('data-id') || '');
    return id ? (temaMap[id] || {}) : {};
  }

  function renderTemaFromItem(el){
    if (!temaTitulo || !temaDesc || !el) return;
    const tema = temaFromItem(el);
    const title = tema.titulo || 'Tema';
    const clase = tema.clase || '';

    temaTitulo.textContent = title;
    temaDesc.innerHTML = clase.trim() ? esc(clase).replace(/\n/g, '<br>') : 'Sin contenido disponible';
  }

  function hasBootstrapTabs(){
    if (window.bootstrap && window.bootstrap.Tab) return true;
    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.tab === 'function') return true;
    return false;
  }

  function activateTabFallback(link){
    const sel = link.getAttribute('href');
    if (!sel || !sel.startsWith('#')) return;
    const pane = root.querySelector(sel);
    if (!pane) return;

    tabLinks.forEach(function(a){ a.classList.remove('active'); });
    link.classList.add('active');

    root.querySelectorAll('.tab-content .tab-pane').forEach(function(p){
      p.classList.remove('show', 'active');
    });
    pane.classList.add('show', 'active');

    if (history.replaceState) history.replaceState(null, '', sel);
  }

  tabLinks.forEach(function(link){
    link.addEventListener('click', function(e){
      if (hasBootstrapTabs()) return;
      e.preventDefault();
      activateTabFallback(link);
    });
  });

  function updateProgress(){
    const pct = total ? Math.round((done / total) * 100) : 0;
    if (progressText) progressText.textContent = pct + '% completado';
    if (countBadge) countBadge.textContent = done + '/' + total + ' completadas';
  }

  function selectItem(el){
    if (!playlist) return;

    playlist.querySelectorAll('.item.active').forEach(function(i){
      i.classList.remove('active');
    });
    el.classList.add('active');

    const url = el.getAttribute('data-url') || '';
    if (url && player) player.src = url;
    renderTemaFromItem(el);

    const icon = el.querySelector('i');
    if (icon && icon.classList.contains('far')) {
      icon.classList.remove('far', 'fa-play-circle', 'text-muted');
      icon.classList.add('fas', 'fa-check-circle', 'text-success');
      done = Math.min(done + 1, total);
      updateProgress();
    }
  }

  playlist?.addEventListener('click', function(e){
    const item = e.target.closest('.item');
    if (item) selectItem(item);
  });

  root.querySelectorAll('.av-course-item').forEach(function(el){
    el.addEventListener('click', function(){
      const id = el.getAttribute('data-id') || '';
      if (!id) return;
      const url = new URL(location.href);
      url.searchParams.set('curso', id);
      location.href = url.toString();
    });
  });

  const firstItem = playlist?.querySelector('.item');
  if (firstItem) {
    firstItem.classList.add('active');
    renderTemaFromItem(firstItem);
  }

  updateProgress();
})();

(function(){
  const root = document.getElementById('avRoot');
  const btn = document.getElementById('avThemeToggle');
  const txt = document.getElementById('avThemeToggleText');
  if (!root || !btn || !txt) return;

  const config = window.avAulaConfig || {};
  const KEY = config.themeStorageKey || 'av_theme_pref';

  const THEMES = ['light', 'dark', 'auto'];
  const LABELS = {
    light: 'Tema: Claro',
    dark: 'Tema: Oscuro',
    auto: 'Tema: Auto'
  };

  const normalize = (v) => THEMES.includes(v) ? v : 'light';
  const readPref = () => {
    try { return normalize(localStorage.getItem(KEY) || 'light'); }
    catch (_) { return 'light'; }
  };
  const savePref = (v) => {
    try { localStorage.setItem(KEY, v); } catch (_) {}
  };

  function applyTheme(theme){
    const safe = normalize(theme);
    root.setAttribute('data-theme', safe);
    txt.textContent = LABELS[safe];
    btn.title = 'Aula virtual en ' + LABELS[safe].replace('Tema: ', '') + '. Clic para cambiar.';
    btn.setAttribute('aria-label', 'Cambiar tema del Aula Virtual. Estado actual: ' + LABELS[safe].replace('Tema: ', ''));
  }

  let current = readPref();
  applyTheme(current);

  btn.addEventListener('click', () => {
    const idx = THEMES.indexOf(current);
    current = THEMES[(idx + 1) % THEMES.length];
    savePref(current);
    applyTheme(current);
  });

  const mm = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
  if (mm) {
    const onSchemeChange = () => { if (current === 'auto') applyTheme('auto'); };
    if (typeof mm.addEventListener === 'function') mm.addEventListener('change', onSchemeChange);
    else if (typeof mm.addListener === 'function') mm.addListener(onSchemeChange);
  }
})();


