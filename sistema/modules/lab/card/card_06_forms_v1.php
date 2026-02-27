<?php
/**
 * CARD 06 - Inspector de formularios (DOM)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_06_forms_v1.php
 * ID interno: LAB-CARD-06
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>

<div class="card card-outline card-secondary shadow-sm" data-card="06" data-version="1.0" data-card-id="LAB-CARD-06">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 06 v1.0]</strong> Formularios e inputs (qué envía la pantalla)</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-2" style="gap:10px;">
      <div class="small text-muted">Te ayuda a ubicar endpoints, names y campos “muertos” (sin name).</div>
      <button class="btn btn-sm btn-secondary" id="labFormsScan06">Escanear</button>
    </div>

    <div class="row mb-2">
      <div class="col-md-3"><div class="small text-muted">Forms</div><div><code id="labFormsCount06">—</code></div></div>
      <div class="col-md-3"><div class="small text-muted">Inputs</div><div><code id="labInputsCount06">—</code></div></div>
      <div class="col-md-3"><div class="small text-muted">Sin name</div><div><code id="labNoNameCount06">—</code></div></div>
      <div class="col-md-3"><div class="small text-muted">CSRF detectado</div><div><code id="labCsrfCount06">—</code></div></div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labFormsTable06">
        <thead><tr>
          <th style="width:60px;">#</th>
          <th style="width:90px;">Method</th>
          <th>Action</th>
          <th style="width:250px;">Campos (name:type)</th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-06 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  function abs(u){ try { return new URL(u, location.href).href; } catch(e){ return ''; } }
  function scan(){
    var forms = Array.from(document.querySelectorAll('form'));
    var rows = [];
    var inputCount = 0, noNameCount = 0, csrfCount = 0;

    forms.forEach(function(f, idx){
      var method = (f.getAttribute('method') || 'GET').toUpperCase();
      var action = f.getAttribute('action') || location.href;
      var actionAbs = abs(action) || '';

      var fields = [];
      var controls = f.querySelectorAll('input,select,textarea,button');
      controls.forEach(function(el){
        var tag = el.tagName.toLowerCase();
        var type = (tag === 'input') ? (el.getAttribute('type') || 'text') : tag;
        var name = el.getAttribute('name') || '';
        var id = el.id || '';

        inputCount++;

        if(!name) noNameCount++;

        var isCsrf = false;
        var low = (name || id).toLowerCase();
        if (low.includes('csrf') || low.includes('token')) isCsrf = true;
        if(isCsrf) csrfCount++;

        // No listar botones sin name para no ensuciar
        if(tag === 'button' && !name) return;

        var label = (name ? name : ('(sin name)' + (id ? '#'+id : ''))) + ':' + type;
        fields.push(label);
      });

      rows.push({idx: idx+1, method: method, action: action, actionAbs: actionAbs, fields: fields});
    });

    return {rows: rows, formsCount: forms.length, inputCount: inputCount, noNameCount: noNameCount, csrfCount: csrfCount};
  }

  function render(data){
    document.getElementById('labFormsCount06').innerText = data.formsCount;
    document.getElementById('labInputsCount06').innerText = data.inputCount;
    document.getElementById('labNoNameCount06').innerText = data.noNameCount;
    document.getElementById('labCsrfCount06').innerText = data.csrfCount;

    var tbody = document.querySelector('#labFormsTable06 tbody');
    tbody.innerHTML = '';
    data.rows.forEach(function(r){
      var tr = document.createElement('tr');
      var actionHtml = r.actionAbs ? ('<a href="'+r.actionAbs+'" target="_blank" rel="noopener"><code>'+r.action+'</code></a>') : ('<code>'+r.action+'</code>');
      var fields = r.fields.slice(0, 20).map(function(x){ return '<code>'+x+'</code>'; }).join(' ');
      if(r.fields.length > 20) fields += ' <span class="text-muted">…</span>';
      tr.innerHTML =
        '<td><code>'+r.idx+'</code></td>'+
        '<td><code>'+r.method+'</code></td>'+
        '<td>'+actionHtml+'</td>'+
        '<td style="white-space:normal;">'+(fields || '<span class="text-muted">—</span>')+'</td>';
      tbody.appendChild(tr);
    });
  }

  function run(){ render(scan()); }

  var btn = document.getElementById('labFormsScan06');
  if(btn) btn.addEventListener('click', run);
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
</script>
