// /modules/api_hub/api_hub.js
(function(){
  var cfg = window.API_HUB_CFG || {};
  var apiUrl = cfg.api || '';

  function qs(sel, ctx){ return (ctx || document).querySelector(sel); }
  function esc(s){
    return String(s == null ? '' : s).replace(/[&<>"']/g, function(m){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
    });
  }
  function n(v){ return Number(v || 0); }
  function fmtDT(raw){
    if (!raw) return '-';
    var dt = new Date(String(raw).replace(' ', 'T'));
    if (isNaN(dt.getTime())) return String(raw);
    var dd = String(dt.getDate()).padStart(2, '0');
    var mm = String(dt.getMonth() + 1).padStart(2, '0');
    var yy = dt.getFullYear();
    var hh = String(dt.getHours()).padStart(2, '0');
    var mi = String(dt.getMinutes()).padStart(2, '0');
    return dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;
  }

  async function apiGET(params){
    var usp = new URLSearchParams(params || {});
    var url = apiUrl + '?' + usp.toString();
    var r = await fetch(url, { credentials: 'same-origin' });
    var j = await r.json();
    if (!j.ok) throw new Error(j.error || 'Error');
    return j;
  }

  function renderTotals(t){
    var providers = t.provider_calls || {};
    qs('#ahDniOk').textContent = n(t.dni_ok);
    qs('#ahDniFail').textContent = n(t.dni_fail);
    qs('#ahRucOk').textContent = n(t.ruc_ok);
    qs('#ahRucFail').textContent = n(t.ruc_fail);
    qs('#ahProvApisperu').textContent = n(providers.apisperu);
    qs('#ahProvDecolecta').textContent = n(providers.decolecta);
    qs('#ahProvJsonpe').textContent = n(providers.jsonpe);
  }

  function compactProviderTriplet(prefix, row){
    var ap = n(row[prefix + '_apisperu']);
    var de = n(row[prefix + '_decolecta']);
    var js = n(row[prefix + '_jsonpe']);
    return 'AP:' + ap + ' DE:' + de + ' JS:' + js;
  }

  function providerBadge(provider, fallback){
    var p = String(provider || '').toLowerCase();
    if (!p) return '-';
    var label = (p === 'jsonpe') ? 'JSON.PE' : p.toUpperCase();
    if (fallback === 1 || fallback === '1') {
      return '<span class="badge badge-warning">' + esc(label + ' (FB)') + '</span>';
    }
    return '<span class="badge badge-info">' + esc(label) + '</span>';
  }

  function renderRows(rows){
    var body = qs('#ahBody');
    if (!rows || !rows.length){
      body.innerHTML = '<tr><td colspan="11" class="text-muted small">Sin registros para el periodo.</td></tr>';
      return;
    }

    body.innerHTML = rows.map(function(r){
      var total = n(r.total_consultas);
      var estado = esc(r.ultima_estado || '-');
      if (estado === 'OK') estado = '<span class="badge badge-success">OK</span>';
      else if (estado === 'FAIL') estado = '<span class="badge badge-danger">FAIL</span>';

      var dniProv = compactProviderTriplet('dni_calls', r);
      var rucProv = compactProviderTriplet('ruc_calls', r);
      var ultProv = providerBadge(r.ultima_proveedor, r.ultima_fallback);

      return '' +
        '<tr>' +
          '<td>' + esc(r.empresa_nombre || ('Empresa #' + r.empresa_id)) + '</td>' +
          '<td class="text-end">' + n(r.dni_ok) + '</td>' +
          '<td class="text-end">' + n(r.dni_fail) + '</td>' +
          '<td class="text-end">' + n(r.ruc_ok) + '</td>' +
          '<td class="text-end">' + n(r.ruc_fail) + '</td>' +
          '<td class="text-end fw-bold">' + total + '</td>' +
          '<td class="small">' + esc(dniProv) + '</td>' +
          '<td class="small">' + esc(rucProv) + '</td>' +
          '<td>' + ultProv + '</td>' +
          '<td>' + esc(fmtDT(r.ultima_consulta_at)) + '</td>' +
          '<td>' + estado + '</td>' +
        '</tr>';
    }).join('');
  }

  function setMsg(txt, isErr){
    var el = qs('#ahMsg');
    el.textContent = txt || '';
    el.classList.remove('text-muted', 'text-danger');
    el.classList.add(isErr ? 'text-danger' : 'text-muted');
  }

  async function loadDashboard(){
    try{
      setMsg('Cargando dashboard...', false);
      var v = (qs('#ahPeriodo').value || '').trim();
      var periodo = v || new Date().toISOString().slice(0,7);
      var j = await apiGET({ action: 'dashboard_month', periodo: periodo });
      renderTotals(j.totales || {});
      renderRows(j.rows || []);
      setMsg('Periodo consultado: ' + String(j.periodo || periodo), false);
    }catch(err){
      renderTotals({
        dni_ok:0, dni_fail:0, ruc_ok:0, ruc_fail:0,
        provider_calls: { apisperu:0, decolecta:0, jsonpe:0 }
      });
      renderRows([]);
      setMsg(err.message || 'No se pudo cargar el dashboard.', true);
    }
  }

  document.addEventListener('click', function(e){
    if (e.target.closest('#ahReload')){
      loadDashboard();
    }
  });

  document.addEventListener('DOMContentLoaded', function(){
    var now = new Date();
    var month = now.toISOString().slice(0,7);
    qs('#ahPeriodo').value = month;
    loadDashboard();
  });
})();
