<?php
// dashboard/administracion/funcion_card_alertas_semana.php
// Widget compacto: carrusel semanal de alertas (sin filtros) + modal detalle.

$admAlertWeekApi = defined('BASE_URL')
    ? rtrim((string)BASE_URL, '/') . '/modules/alerta/api.php'
    : '../../modules/alerta/api.php';

$admAlertModuleUrl = defined('BASE_URL')
    ? rtrim((string)BASE_URL, '/') . '/modules/alerta/index.php'
    : '../../modules/alerta/index.php';
?>

<div class="card mt-3 adm-alert-week-card" id="admAlertWeekCard">
  <div class="card-body p-3">
    <div class="d-flex align-items-start justify-content-between mb-2">
      <div>
        <div class="adm-alert-week-kicker">Productividad</div>
        <h6 class="card-title mb-0">Recordatorios de esta semana</h6>
        <small id="admAlertWeekRange" class="text-muted">Cargando...</small>
      </div>
      <span class="badge badge-light" id="admAlertWeekCounter">0/0</span>
    </div>

    <button type="button" id="admAlertWeekItem" class="adm-alert-week-item is-empty">
      <div class="adm-alert-week-line1">Buscando alertas...</div>
      <div class="adm-alert-week-line2">En este espacio verás los recordatorios próximos.</div>
    </button>

    <div class="adm-alert-week-actions mt-2">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="admAlertWeekPrev" title="Anterior">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="admAlertWeekNext" title="Siguiente">
        <i class="fas fa-chevron-right"></i>
      </button>
      <span class="text-muted small ml-auto">Auto: 30s</span>
    </div>
  </div>
</div>

<div class="modal fade" id="admAlertWeekModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content adm-alert-modal">
      <div class="modal-header">
        <h5 class="modal-title">Detalle del recordatorio</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="admAlertWeekModalBody">
        <div class="text-muted">Sin información.</div>
      </div>
      <div class="modal-footer py-2">
        <a href="<?= htmlspecialchars($admAlertModuleUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">Ver módulo de alertas</a>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var API = <?= json_encode($admAlertWeekApi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var MODULE_URL = <?= json_encode($admAlertModuleUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  function byId(id) { return document.getElementById(id); }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
  }
  function pad2(n) { return String(n).padStart(2, '0'); }

  function fmtDateTime(value) {
    if (!value) return '-';
    var d = new Date(String(value).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(value);
    return pad2(d.getDate()) + '/' + pad2(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
  }

  function friendlyDistance(seconds) {
    var s = Math.max(0, Number(seconds || 0));
    var days = Math.floor(s / 86400); s -= days * 86400;
    var hours = Math.floor(s / 3600); s -= hours * 3600;
    var mins = Math.floor(s / 60);

    if (days > 0) return 'Faltan ' + days + 'd ' + pad2(hours) + 'h';
    if (hours > 0) return 'Faltan ' + hours + 'h ' + pad2(mins) + 'm';
    return 'Faltan ' + mins + 'm';
  }

  function urgency(row) {
    if (!row) return { label: 'Pendiente', className: 'adm-alert-pill-neutral' };
    if (Number(row._overdue)) return { label: 'Vencida', className: 'adm-alert-pill-danger' };
    if (Number(row._in_window)) return { label: 'Atender pronto', className: 'adm-alert-pill-warning' };
    return { label: 'Planificada', className: 'adm-alert-pill-ok' };
  }

  function typeLabel(row) {
    if (!row || !row.tipo) return '-';
    if (row.tipo === 'ONCE') return 'Una sola vez';
    if (row.tipo === 'MONTHLY') return 'Mensual';
    if (row.tipo === 'YEARLY') return 'Anual';
    if (row.tipo === 'INTERVAL') return 'Cada ' + (row.intervalo_dias || '?') + ' días';
    return row.tipo;
  }

  function shortRange(start, end) {
    if (!start || !end) return 'Semana actual';
    return fmtDateTime(start).slice(0, 10) + ' - ' + fmtDateTime(end).slice(0, 10);
  }

  var item = byId('admAlertWeekItem');
  var range = byId('admAlertWeekRange');
  var counter = byId('admAlertWeekCounter');
  var prev = byId('admAlertWeekPrev');
  var next = byId('admAlertWeekNext');
  var modal = byId('admAlertWeekModal');
  var modalBody = byId('admAlertWeekModalBody');
  if (!item || !range || !counter || !prev || !next || !modal || !modalBody || !API) return;

  var state = { rows: [], idx: 0, timer: null };

  function setEmpty(line1, line2) {
    counter.textContent = '0/0';
    item.classList.add('is-empty');
    item.innerHTML = ''
      + '<div class="adm-alert-week-line1">' + esc(line1 || 'Sin alertas esta semana') + '</div>'
      + '<div class="adm-alert-week-line2">' + esc(line2 || 'Todo está al día. Puedes crear nuevas alertas desde el módulo.') + '</div>';
    prev.disabled = true;
    next.disabled = true;
  }

  function render() {
    var total = state.rows.length;
    if (!total) {
      setEmpty('Sin alertas para esta semana', 'Excelente trabajo, no tienes pendientes inmediatos.');
      return;
    }

    if (state.idx < 0) state.idx = 0;
    if (state.idx >= total) state.idx = 0;

    var row = state.rows[state.idx];
    var u = urgency(row);
    counter.textContent = (state.idx + 1) + '/' + total;
    prev.disabled = total <= 1;
    next.disabled = total <= 1;

    var line1 = (row.titulo || 'Recordatorio') + ' ';
    var line2Parts = [];
    if (row.categoria) line2Parts.push('#' + row.categoria);
    line2Parts.push(fmtDateTime(row._next_iso || ''));
    if (row._in_seconds != null) line2Parts.push(friendlyDistance(row._in_seconds));

    item.classList.remove('is-empty');
    item.innerHTML = ''
      + '<div class="adm-alert-week-line1">'
      + '  <span>' + esc(line1) + '</span>'
      + '  <span class="adm-alert-pill ' + esc(u.className) + '">' + esc(u.label) + '</span>'
      + '</div>'
      + '<div class="adm-alert-week-line2">' + esc(line2Parts.join(' | ')) + '</div>';
  }

  function openModal() {
    if (!state.rows.length) return;
    var row = state.rows[state.idx];
    var u = urgency(row);

    modalBody.innerHTML = ''
      + '<div class="adm-alert-modal-head mb-2">'
      + '  <span class="adm-alert-pill ' + esc(u.className) + '">' + esc(u.label) + '</span>'
      + '</div>'
      + '<div class="adm-alert-modal-grid">'
      + '  <div class="adm-alert-modal-label">Título</div>'
      + '  <div class="adm-alert-modal-value">' + esc(row.titulo || '-') + '</div>'
      + '  <div class="adm-alert-modal-label">Categoría</div>'
      + '  <div class="adm-alert-modal-value">' + esc(row.categoria || '-') + '</div>'
      + '  <div class="adm-alert-modal-label">Tipo</div>'
      + '  <div class="adm-alert-modal-value">' + esc(typeLabel(row)) + '</div>'
      + '  <div class="adm-alert-modal-label">Próxima fecha</div>'
      + '  <div class="adm-alert-modal-value">' + esc(fmtDateTime(row._next_iso || '')) + '</div>'
      + '  <div class="adm-alert-modal-label">Anticipación</div>'
      + '  <div class="adm-alert-modal-value">' + esc(String(parseInt(row.anticipacion_dias || 0, 10))) + ' día(s)</div>'
      + '  <div class="adm-alert-modal-label">Estado</div>'
      + '  <div class="adm-alert-modal-value">' + (Number(row.activo) ? 'Activa' : 'Inactiva') + '</div>'
      + '  <div class="adm-alert-modal-label">Descripción</div>'
      + '  <div class="adm-alert-modal-value">' + esc(row.descripcion || 'Sin descripción') + '</div>'
      + '</div>'
      + '<div class="mt-3 text-right">'
      + '  <a class="btn btn-outline-primary btn-sm" href="' + esc(MODULE_URL) + '">Ir al módulo de alertas</a>'
      + '</div>';

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
      window.jQuery(modal).modal('show');
    }
  }

  function stopAuto() {
    if (!state.timer) return;
    clearInterval(state.timer);
    state.timer = null;
  }

  function startAuto() {
    stopAuto();
    if (state.rows.length <= 1) return;
    state.timer = setInterval(function () {
      state.idx = (state.idx + 1) % state.rows.length;
      render();
    }, 30000);
  }

  function load() {
    fetch(API + '?action=week&limit=30', { credentials: 'same-origin' })
      .then(function (resp) {
        return resp.json().then(function (data) {
          if (!resp.ok || !data || !data.ok) {
            throw new Error((data && data.msg) ? data.msg : 'No se pudo cargar alertas');
          }
          return data;
        });
      })
      .then(function (data) {
        state.rows = Array.isArray(data.data) ? data.data : [];
        state.idx = 0;
        range.textContent = shortRange(data.week_start, data.week_end);
        render();
        startAuto();
      })
      .catch(function (err) {
        range.textContent = 'Semana actual';
        setEmpty('No se pudo cargar alertas', err && err.message ? err.message : 'Revisa permisos o conexión del módulo de alertas.');
      });
  }

  prev.addEventListener('click', function () {
    if (!state.rows.length) return;
    state.idx = (state.idx - 1 + state.rows.length) % state.rows.length;
    render();
  });

  next.addEventListener('click', function () {
    if (!state.rows.length) return;
    state.idx = (state.idx + 1) % state.rows.length;
    render();
  });

  item.addEventListener('click', openModal);
  item.addEventListener('mouseenter', stopAuto);
  item.addEventListener('mouseleave', startAuto);

  load();
})();
</script>
