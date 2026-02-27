<?php
/**
 * CARD 07 - Monitor AJAX (fetch/XHR) en runtime
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_07_ajax_monitor_v1.php
 * ID interno: LAB-CARD-07
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>

<div class="card card-outline card-primary shadow-sm" data-card="07" data-version="1.0" data-card-id="LAB-CARD-07">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 07 v1.0]</strong> Monitor AJAX (fetch + XHR)</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap:10px;">
      <div class="small text-muted">Registra endpoint, método, status y tiempo (no guarda body completo).</div>
      <div class="btn-group">
        <button class="btn btn-sm btn-primary" id="labAjaxStart07">Activar</button>
        <button class="btn btn-sm btn-outline-primary" id="labAjaxStop07">Desactivar</button>
        <button class="btn btn-sm btn-outline-danger" id="labAjaxClear07">Limpiar</button>
      </div>
    </div>

    <div class="row mb-2">
      <div class="col-md-3"><div class="small text-muted">Logs</div><div><code id="labAjaxCount07">—</code></div></div>
      <div class="col-md-9"><div class="small text-muted">Estado</div><div><code id="labAjaxState07">—</code></div></div>
    </div>

    <div style="max-width: 420px;" class="mb-2">
      <input id="labAjaxSearch07" class="form-control form-control-sm" placeholder="Filtrar...">
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labAjaxTable07">
        <thead><tr>
          <th style="width:55px;">#</th>
          <th style="width:80px;">Tipo</th>
          <th style="width:90px;">Método</th>
          <th>URL</th>
          <th style="width:90px;">Status</th>
          <th style="width:110px;">ms</th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-07 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  window.__LAB_AJAX_LOG = window.__LAB_AJAX_LOG || [];
  window.__LAB_AJAX_PATCHED = window.__LAB_AJAX_PATCHED || false;

  function abs(u){ try { return new URL(u, location.href).href; } catch(e){ return u || ''; } }

  function render(){
    var tbody = document.querySelector('#labAjaxTable07 tbody');
    var input = document.getElementById('labAjaxSearch07');
    var q = (input && input.value ? input.value.toLowerCase() : '');

    tbody.innerHTML = '';
    var logs = window.__LAB_AJAX_LOG || [];

    document.getElementById('labAjaxCount07').innerText = logs.length;
    document.getElementById('labAjaxState07').innerText = window.__LAB_AJAX_PATCHED ? 'ACTIVO' : 'INACTIVO';

    logs.slice().reverse().forEach(function(r, idx){
      var txt = (r.type+' '+r.method+' '+r.url+' '+r.status+' '+r.ms).toLowerCase();
      if(q && !txt.includes(q)) return;

      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><code>'+(logs.length-idx)+'</code></td>'+
        '<td><code>'+r.type+'</code></td>'+
        '<td><code>'+r.method+'</code></td>'+
        '<td><a href="'+r.url+'" target="_blank" rel="noopener"><code>'+r.url+'</code></a></td>'+
        '<td><code>'+r.status+'</code></td>'+
        '<td><code>'+r.ms+'</code></td>';
      tbody.appendChild(tr);
    });
  }

  function patch(){
    if(window.__LAB_AJAX_PATCHED) return;

    // fetch
    if(!window.__LAB_FETCH_ORIG) window.__LAB_FETCH_ORIG = window.fetch;
    window.fetch = function(input, init){
      var method = (init && init.method) ? init.method : 'GET';
      var url = (typeof input === 'string') ? input : (input && input.url ? input.url : '');
      var full = abs(url);
      var t0 = performance.now();

      return window.__LAB_FETCH_ORIG.apply(this, arguments).then(function(res){
        var t1 = performance.now();
        window.__LAB_AJAX_LOG.push({type:'fetch', method: (method||'GET').toUpperCase(), url: full, status: res.status, ms: Math.round(t1-t0)});
        render();
        return res;
      }).catch(function(err){
        var t1 = performance.now();
        window.__LAB_AJAX_LOG.push({type:'fetch', method: (method||'GET').toUpperCase(), url: full, status: 'ERR', ms: Math.round(t1-t0)});
        render();
        throw err;
      });
    };

    // XHR
    if(!window.__LAB_XHR_ORIG) window.__LAB_XHR_ORIG = window.XMLHttpRequest;
    function XHRProxy(){
      var xhr = new window.__LAB_XHR_ORIG();
      var _open = xhr.open;
      var _send = xhr.send;

      var req = {method:'GET', url:''};
      xhr.open = function(method, url){
        req.method = (method||'GET').toUpperCase();
        req.url = abs(url);
        return _open.apply(xhr, arguments);
      };

      xhr.send = function(){
        var t0 = performance.now();
        xhr.addEventListener('loadend', function(){
          var t1 = performance.now();
          window.__LAB_AJAX_LOG.push({type:'xhr', method:req.method, url:req.url, status:xhr.status || 0, ms: Math.round(t1-t0)});
          render();
        }, {once:true});
        return _send.apply(xhr, arguments);
      };
      return xhr;
    }
    window.XMLHttpRequest = XHRProxy;

    window.__LAB_AJAX_PATCHED = true;
    render();
  }

  function unpatch(){
    if(!window.__LAB_AJAX_PATCHED) return;
    if(window.__LAB_FETCH_ORIG) window.fetch = window.__LAB_FETCH_ORIG;
    if(window.__LAB_XHR_ORIG) window.XMLHttpRequest = window.__LAB_XHR_ORIG;
    window.__LAB_AJAX_PATCHED = false;
    render();
  }

  document.getElementById('labAjaxStart07').addEventListener('click', patch);
  document.getElementById('labAjaxStop07').addEventListener('click', unpatch);
  document.getElementById('labAjaxClear07').addEventListener('click', function(){ window.__LAB_AJAX_LOG = []; render(); });
  document.getElementById('labAjaxSearch07').addEventListener('input', render);

  render();
})();
</script>
