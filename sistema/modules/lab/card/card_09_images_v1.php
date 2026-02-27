<?php
/**
 * CARD 09 - Imágenes/recursos gráficos usados en la interfaz (DOM scan)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_09_images_v1.php
 * ID interno: LAB-CARD-09
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>

<div class="card card-outline card-info shadow-sm" data-card="09" data-version="1.0" data-card-id="LAB-CARD-09">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 09 v1.0]</strong> Imágenes/recursos gráficos usados</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap:8px;">
      <div class="small text-muted">
        Detecta: <code>&lt;img&gt;</code>, <code>&lt;picture&gt;</code>, <code>link rel=icon</code>,
        <code>background-image</code>, <code>svg use/image</code>, <code>meta og:image</code>.
      </div>
      <div class="btn-group">
        <button type="button" class="btn btn-sm btn-info" id="labImgScan09">Escanear</button>
        <button type="button" class="btn btn-sm btn-outline-info" id="labImgScanAll09" title="Más lento">Escaneo profundo</button>
      </div>
    </div>

    <div class="row mb-2">
      <div class="col-md-4"><div class="small text-muted">Total</div><div><code id="labImgCount09">—</code></div></div>
      <div class="col-md-4"><div class="small text-muted">Únicos</div><div><code id="labImgUnique09">—</code></div></div>
      <div class="col-md-4"><div class="small text-muted">Modo</div><div><code id="labImgMode09">—</code></div></div>
    </div>

    <div class="mb-2" style="max-width: 360px;"><input id="labImgSearch09" class="form-control form-control-sm" placeholder="Filtrar..."></div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labImgTable09">
        <thead><tr><th style="width:40px;">#</th><th style="width:110px;">Tipo</th><th>URL</th><th>URL resuelta</th><th style="width:220px;">Origen</th><th style="width:70px;">Prev</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>

    <div class="small text-muted mt-2">
      Iconos tipo FontAwesome (<code>&lt;i class="fa..."&gt;</code>) no son imágenes; son fuentes/CSS.
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-09 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  function toAbs(url){ try { return new URL(url, window.location.href).href; } catch(e){ return ''; } }
  function elSelector(el){
    if(!el || !el.tagName) return '';
    var sel = el.tagName.toLowerCase();
    if(el.id) sel += '#' + el.id;
    if(el.classList && el.classList.length){
      sel += '.' + Array.from(el.classList).slice(0,3).join('.');
      if(el.classList.length > 3) sel += '…';
    }
    return sel;
  }
  function extractUrlsFromBg(bg){
    var out = []; if(!bg || bg === 'none') return out;
    var re = /url\((['"]?)(.*?)\1\)/g, m;
    while((m = re.exec(bg)) !== null){ if(m[2]) out.push(m[2]); }
    return out;
  }
  function addItem(list, item){
    if(!item || !item.url) return;
    item.url = (item.url+'').trim();
    if(!item.url) return;
    item.abs = toAbs(item.url) || '';
    list.push(item);
  }
  function scan(mode){
    var items = [];
    var deep = (mode === 'deep');

    document.querySelectorAll('img').forEach(function(img){
      var u = img.currentSrc || img.src || '';
      addItem(items, { kind:'img', url:u, from: elSelector(img), el: img });
      if(img.getAttribute('srcset')) addItem(items, { kind:'img-srcset', url: img.getAttribute('srcset'), from: elSelector(img), el: img });
    });

    document.querySelectorAll('picture source').forEach(function(src){
      var u = src.srcset || src.getAttribute('srcset') || src.getAttribute('src') || '';
      if(u) addItem(items, { kind:'picture-source', url:u, from: elSelector(src), el: src });
    });

    document.querySelectorAll('link[rel]').forEach(function(lnk){
      var rel = (lnk.getAttribute('rel') || '').toLowerCase();
      if(rel.includes('icon')){
        var u = lnk.href || lnk.getAttribute('href') || '';
        addItem(items, { kind:'link-icon', url:u, from: elSelector(lnk), el: lnk });
      }
    });

    document.querySelectorAll('meta[property="og:image"], meta[name="twitter:image"], meta[name="twitter:image:src"]').forEach(function(m){
      var u = m.getAttribute('content') || '';
      addItem(items, { kind:'meta-image', url:u, from: elSelector(m), el: m });
    });

    document.querySelectorAll('svg use, svg image').forEach(function(n){
      var u = n.getAttribute('href') || n.getAttribute('xlink:href') || n.getAttribute('src') || '';
      if(u) addItem(items, { kind:'svg-ref', url:u, from: elSelector(n), el: n });
    });

    var bgCandidates = Array.from(document.querySelectorAll('[style*="background"], [style*="background-image"]'));
    if(deep){
      var all = document.querySelectorAll('*');
      var limit = Math.min(all.length, 1200);
      for(var i=0;i<limit;i++) bgCandidates.push(all[i]);
    }

    var seenNodes = new Set();
    bgCandidates = bgCandidates.filter(function(el){
      if(!el || seenNodes.has(el)) return false;
      seenNodes.add(el); return true;
    });

    bgCandidates.forEach(function(el){
      var bg = ''; try { bg = getComputedStyle(el).backgroundImage; } catch(e) {}
      extractUrlsFromBg(bg).forEach(function(u){
        addItem(items, { kind:'bg-image', url:u, from: elSelector(el), el: el });
      });
    });

    return items;
  }

  function render(items, mode){
    var tbody = document.querySelector('#labImgTable09 tbody');
    tbody.innerHTML = '';

    document.getElementById('labImgCount09').innerText = String(items.length);
    document.getElementById('labImgMode09').innerText = mode;

    var uniq = new Map();
    items.forEach(function(it){
      var k = it.abs || it.url;
      if(!uniq.has(k)) uniq.set(k, it);
    });
    document.getElementById('labImgUnique09').innerText = String(uniq.size);

    items.sort(function(a,b){
      if(a.kind !== b.kind) return a.kind.localeCompare(b.kind);
      return (a.abs || a.url).localeCompare(b.abs || b.url);
    });

    items.forEach(function(it, idx){
      var tr = document.createElement('tr');

      var td0 = document.createElement('td'); td0.innerHTML = '<code>'+(idx+1)+'</code>'; tr.appendChild(td0);
      var td1 = document.createElement('td'); td1.innerHTML = '<code>'+it.kind+'</code>'; tr.appendChild(td1);

      var td2 = document.createElement('td');
      td2.innerHTML = '<code style="white-space:nowrap;">'+(it.url.length>220 ? it.url.slice(0,220)+'…' : it.url)+'</code>';
      tr.appendChild(td2);

      var td3 = document.createElement('td');
      if(it.abs){
        td3.innerHTML = '<a href="'+it.abs+'" target="_blank" rel="noopener"><code style="white-space:nowrap;">'+(it.abs.length>220 ? it.abs.slice(0,220)+'…' : it.abs)+'</code></a>';
      } else td3.innerHTML = '<span class="text-muted">—</span>';
      tr.appendChild(td3);

      var td4 = document.createElement('td'); td4.innerHTML = '<code>'+it.from+'</code>'; tr.appendChild(td4);

      var td5 = document.createElement('td');
      var prevUrl = it.abs || it.url;
      var canPrev = prevUrl && !it.kind.includes('srcset') && !prevUrl.includes(' ');
      if(canPrev){
        var img = document.createElement('img');
        img.src = prevUrl; img.style.maxWidth='46px'; img.style.maxHeight='46px'; img.style.objectFit='contain'; img.loading='lazy';
        td5.appendChild(img);
      } else td5.innerHTML = '<span class="text-muted">—</span>';
      tr.appendChild(td5);

      tbody.appendChild(tr);
    });

    var input = document.getElementById('labImgSearch09');
    input.oninput = function(){
      var q = (input.value||'').toLowerCase();
      Array.from(tbody.querySelectorAll('tr')).forEach(function(row){
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    };
  }

  function run(mode){
    try{ render(scan(mode), mode); }
    catch(e){
      console.error(e);
      var tbody = document.querySelector('#labImgTable09 tbody');
      tbody.innerHTML = '<tr><td colspan="6"><div class="alert alert-warning mb-0">Error: '+(e && e.message ? e.message : '—')+'</div></td></tr>';
    }
  }

  document.getElementById('labImgScan09').addEventListener('click', function(){ run('quick'); });
  document.getElementById('labImgScanAll09').addEventListener('click', function(){ run('deep'); });

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ run('quick'); });
  else run('quick');
})();
</script>
