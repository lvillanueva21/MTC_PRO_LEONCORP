<?php
/**
 * CARD 05 - Rutas/URLs usadas por la interfaz (DOM)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_05_routes_v1.php
 * ID interno: LAB-CARD-05
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>

<div class="card card-outline card-info shadow-sm" data-card="05" data-version="1.0" data-card-id="LAB-CARD-05">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 05 v1.0]</strong> Rutas/URLs detectadas en la UI</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-2" style="gap:10px;">
      <div class="small text-muted">Detecta: <code>href</code>, <code>src</code>, <code>action</code>, <code>data-url</code>, <code>data-href</code></div>
      <button class="btn btn-sm btn-info" id="labRoutesScan05">Escanear</button>
    </div>

    <div class="row mb-2">
      <div class="col-md-4"><div class="small text-muted">Total</div><div><code id="labRoutesTotal05">—</code></div></div>
      <div class="col-md-4"><div class="small text-muted">Internas</div><div><code id="labRoutesIn05">—</code></div></div>
      <div class="col-md-4"><div class="small text-muted">Externas</div><div><code id="labRoutesOut05">—</code></div></div>
    </div>

    <div style="max-width: 420px;" class="mb-2">
      <input id="labRoutesSearch05" class="form-control form-control-sm" placeholder="Filtrar...">
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labRoutesTable05">
        <thead><tr><th style="width:70px;">Tipo</th><th>URL</th><th>Resuelta</th><th style="width:220px;">Origen</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-05 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  function abs(u){ try { return new URL(u, location.href).href; } catch(e){ return ''; } }
  function sel(el){
    var s = el.tagName.toLowerCase();
    if(el.id) s += '#'+el.id;
    if(el.classList && el.classList.length) s += '.'+Array.from(el.classList).slice(0,3).join('.');
    return s;
  }

  function collect(){
    var out = [];
    var add = function(kind, url, el){
      if(!url) return;
      url = (url+'').trim();
      if(!url) return;
      out.push({kind:kind, url:url, abs:abs(url), from:sel(el)});
    };

    document.querySelectorAll('[href]').forEach(function(el){
      if(el.tagName.toLowerCase()==='link') return; // assets en otro card
      add('href', el.getAttribute('href'), el);
    });
    document.querySelectorAll('[src]').forEach(function(el){
      add('src', el.getAttribute('src'), el);
    });
    document.querySelectorAll('form[action]').forEach(function(el){
      add('action', el.getAttribute('action'), el);
    });
    document.querySelectorAll('[data-url],[data-href]').forEach(function(el){
      add('data-url', el.getAttribute('data-url')||el.getAttribute('data-href'), el);
    });

    // dedup por abs/url+kind
    var m = new Map();
    out.forEach(function(r){
      var k = r.kind+'|'+(r.abs||r.url);
      if(!m.has(k)) m.set(k, r);
    });
    return Array.from(m.values());
  }

  function render(rows){
    var tbody = document.querySelector('#labRoutesTable05 tbody');
    tbody.innerHTML = '';

    var inCnt=0,outCnt=0;
    rows.forEach(function(r){
      var a = r.abs || '';
      var isExt = a && !a.startsWith(location.origin);
      if(isExt) outCnt++; else inCnt++;

      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><code>'+r.kind+'</code></td>'+
        '<td><code>'+r.url.replace(/</g,'&lt;')+'</code></td>'+
        '<td>'+(a ? '<a href="'+a+'" target="_blank" rel="noopener"><code>'+a.replace(/</g,'&lt;')+'</code></a>' : '<span class="text-muted">—</span>')+'</td>'+
        '<td><code>'+r.from+'</code></td>';
      tbody.appendChild(tr);
    });

    document.getElementById('labRoutesTotal05').innerText = rows.length;
    document.getElementById('labRoutesIn05').innerText = inCnt;
    document.getElementById('labRoutesOut05').innerText = outCnt;

    var input = document.getElementById('labRoutesSearch05');
    input.oninput = function(){
      var q = (input.value||'').toLowerCase();
      tbody.querySelectorAll('tr').forEach(function(tr){
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    };
  }

  function run(){ render(collect()); }

  var btn = document.getElementById('labRoutesScan05');
  if(btn) btn.addEventListener('click', run);
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
</script>
