<?php
/**
 * CARD 08 - Assets CSS/JS cargados (DOM + Performance API)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_08_assets_v1.php
 * ID interno: LAB-CARD-08
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>

<div class="card card-outline card-info shadow-sm" data-card="08" data-version="1.0" data-card-id="LAB-CARD-08">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 08 v1.0]</strong> CSS/JS cargados + métricas</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-2" style="gap:10px;">
      <div class="small text-muted">Lista links <code>rel=stylesheet</code> y scripts, con tamaño/tiempo si el navegador lo expone.</div>
      <button class="btn btn-sm btn-info" id="labAssetsScan08">Escanear</button>
    </div>

    <div class="row mb-2">
      <div class="col-md-3"><div class="small text-muted">CSS</div><div><code id="labCssCount08">—</code></div></div>
      <div class="col-md-3"><div class="small text-muted">JS</div><div><code id="labJsCount08">—</code></div></div>
      <div class="col-md-3"><div class="small text-muted">Perf entries</div><div><code id="labPerfCount08">—</code></div></div>
      <div class="col-md-3"><div class="small text-muted">Cache/transfer</div><div><code id="labPerfNote08">—</code></div></div>
    </div>

    <div style="max-width: 420px;" class="mb-2">
      <input id="labAssetsSearch08" class="form-control form-control-sm" placeholder="Filtrar...">
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labAssetsTable08">
        <thead><tr>
          <th style="width:70px;">Tipo</th>
          <th>URL</th>
          <th style="width:110px;">KB</th>
          <th style="width:110px;">ms</th>
          <th style="width:100px;">init</th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-08 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  function abs(u){ try { return new URL(u, location.href).href; } catch(e){ return ''; } }

  function perfMap(){
    var m = new Map();
    try {
      var entries = performance.getEntriesByType('resource') || [];
      entries.forEach(function(e){
        // key por URL completa
        if(e && e.name) m.set(e.name, e);
      });
      document.getElementById('labPerfCount08').innerText = entries.length;
      document.getElementById('labPerfNote08').innerText = 'depende del navegador';
    } catch(e) {
      document.getElementById('labPerfCount08').innerText = '0';
      document.getElementById('labPerfNote08').innerText = 'sin Performance API';
    }
    return m;
  }

  function collect(){
    var rows = [];
    // CSS
    document.querySelectorAll('link[rel="stylesheet"]').forEach(function(l){
      var href = l.getAttribute('href') || '';
      var full = abs(href) || '';
      rows.push({type:'css', url: href, abs: full});
    });
    // JS
    document.querySelectorAll('script[src]').forEach(function(s){
      var src = s.getAttribute('src') || '';
      var full = abs(src) || '';
      rows.push({type:'js', url: src, abs: full});
    });
    return rows;
  }

  function render(rows){
    var tbody = document.querySelector('#labAssetsTable08 tbody');
    tbody.innerHTML = '';

    var css = rows.filter(r=>r.type==='css').length;
    var js  = rows.filter(r=>r.type==='js').length;
    document.getElementById('labCssCount08').innerText = css;
    document.getElementById('labJsCount08').innerText = js;

    var pmap = perfMap();

    rows.forEach(function(r){
      var e = r.abs ? pmap.get(r.abs) : null;
      var kb = e && e.transferSize ? (e.transferSize/1024) : (e && e.encodedBodySize ? (e.encodedBodySize/1024) : null);
      var ms = e ? (e.duration || 0) : null;
      var init = e ? (e.initiatorType || '—') : '—';

      var tr = document.createElement('tr');
      var link = r.abs ? '<a href="'+r.abs+'" target="_blank" rel="noopener"><code>'+r.url+'</code></a>' : '<code>'+r.url+'</code>';
      tr.innerHTML =
        '<td><code>'+r.type+'</code></td>'+
        '<td>'+link+'</td>'+
        '<td><code>'+(kb!==null ? kb.toFixed(1) : '—')+'</code></td>'+
        '<td><code>'+(ms!==null ? Math.round(ms) : '—')+'</code></td>'+
        '<td><code>'+init+'</code></td>';
      tbody.appendChild(tr);
    });

    var input = document.getElementById('labAssetsSearch08');
    input.oninput = function(){
      var q = (input.value||'').toLowerCase();
      tbody.querySelectorAll('tr').forEach(function(tr){
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    };
  }

  function run(){ render(collect()); }

  document.getElementById('labAssetsScan08').addEventListener('click', run);
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
</script>
