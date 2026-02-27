<?php
/**
 * CARD 03 - Mapa de includes (runtime)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_03_includes_v1.php
 * ID interno: LAB-CARD-03
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$files = get_included_files();
$projectRoot = realpath(__DIR__ . '/../../../..') ?: null;

$rows = [];
foreach ($files as $f) {
    $rp = realpath($f);
    if (!$rp) continue;
    $rel = $projectRoot ? str_replace($projectRoot, '', $rp) : $rp;
    $size = @filesize($rp);
    $mtime = @filemtime($rp);
    $rows[] = [
        'path' => $rel,
        'abs'  => $rp,
        'size' => is_int($size) ? $size : null,
        'mtime'=> is_int($mtime) ? $mtime : null,
    ];
}

usort($rows, fn($a,$b)=> strcasecmp($a['path'], $b['path']));
$total = count($rows);
$sumSize = array_sum(array_map(fn($r)=> (int)($r['size'] ?? 0), $rows));
?>

<div class="card card-outline card-warning shadow-sm" data-card="03" data-version="1.0" data-card-id="LAB-CARD-03">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 03 v1.0]</strong> Archivos incluidos (participan en la página)</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="row mb-2">
      <div class="col-md-4"><div class="small text-muted">Total</div><div><code><?= (int)$total ?></code></div></div>
      <div class="col-md-4"><div class="small text-muted">Tamaño acumulado</div><div><code><?= h(number_format($sumSize/1024, 1)) ?> KB</code></div></div>
      <div class="col-md-4"><div class="small text-muted">Root</div><div><code><?= h($projectRoot ?: '—') ?></code></div></div>
    </div>

    <div style="max-width: 360px;" class="mb-2">
      <input id="labIncSearch03" class="form-control form-control-sm" placeholder="Filtrar por ruta...">
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labIncTable03">
        <thead><tr>
          <th style="width:50px;">#</th>
          <th>Ruta</th>
          <th style="width:120px;">KB</th>
          <th style="width:180px;">Modificado</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td><code><?= $i+1 ?></code></td>
            <td><code><?= h($r['path']) ?></code></td>
            <td><code><?= h($r['size'] !== null ? number_format($r['size']/1024, 1) : '—') ?></code></td>
            <td><code><?= h($r['mtime'] ? date('Y-m-d H:i:s', $r['mtime']) : '—') ?></code></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-03 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  var input = document.getElementById('labIncSearch03');
  var table = document.getElementById('labIncTable03');
  if(!input || !table) return;
  input.addEventListener('input', function(){
    var q = (input.value || '').toLowerCase();
    table.querySelectorAll('tbody tr').forEach(function(tr){
      tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  });
})();
</script>
