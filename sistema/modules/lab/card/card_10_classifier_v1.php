<?php
/**
 * CARD 10 - Clasificador "quién hace qué" (por archivo incluido)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_10_classifier_v1.php
 * ID interno: LAB-CARD-10
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$projectRoot = realpath(__DIR__ . '/../../../..') ?: null;

function lab10_rel(string $path, ?string $root): string {
    $rp = realpath($path) ?: $path;
    return $root ? str_replace($root, '', $rp) : $rp;
}

function lab10_classify(string $file, string $content): array {
    $name = strtolower(basename($file));
    $c = strtolower($content);

    $tags = [];

    // nombre
    if (strpos($name, 'ajax') !== false) $tags[] = 'AJAX';
    if (strpos($name, 'api') !== false)  $tags[] = 'API';
    if (strpos($name, 'funcion') !== false || strpos($name, 'helper') !== false) $tags[] = 'HELPER';
    if (strpos($name, 'header') !== false || strpos($name, 'footer') !== false || strpos($name, 'sidebar') !== false) $tags[] = 'LAYOUT';
    if (strpos($name, 'form') !== false) $tags[] = 'FORM';

    // contenido
    if (preg_match('/header\s*\(\s*[\'"]content-type:\s*application\/json/i', $content)) $tags[] = 'JSON';
    if (preg_match('/\b(select|insert|update|delete)\b/i', $content)) $tags[] = 'SQL';
    if (preg_match('/<\s*(div|section|table|form|input|button)\b/i', $content)) $tags[] = 'VISTA';
    if (preg_match('/\b(fetch|xmlhttprequest|axios)\b/i', $c)) $tags[] = 'FRONT-AJAX';
    if (preg_match('/\b(require_once|include)\b/i', $c)) $tags[] = 'INCLUDE';

    $tags = array_values(array_unique($tags));
    if (!$tags) $tags = ['OTRO'];
    return $tags;
}

$maxBytes = 700000;
$rows = [];
foreach (get_included_files() as $f) {
    $rp = realpath($f);
    if (!$rp) continue;
    if ($projectRoot && strncmp($rp, $projectRoot, strlen($projectRoot)) !== 0) continue;

    $size = @filesize($rp);
    if (is_int($size) && $size > $maxBytes) continue;

    $content = @file_get_contents($rp);
    if ($content === false) continue;

    $tags = lab10_classify($rp, $content);

    $rows[] = [
        'file' => lab10_rel($rp, $projectRoot),
        'tags' => $tags,
        'size' => is_int($size) ? $size : null,
    ];
}

usort($rows, fn($a,$b)=> strcasecmp($a['file'], $b['file']));
?>

<div class="card card-outline card-dark shadow-sm" data-card="10" data-version="1.0" data-card-id="LAB-CARD-10">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 10 v1.0]</strong> Quién hace qué (clasificador rápido)</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div style="max-width: 420px;" class="mb-2">
      <input id="labClsSearch10" class="form-control form-control-sm" placeholder="Filtrar por tag o archivo...">
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labClsTable10">
        <thead><tr><th>Archivo</th><th style="width:260px;">Tags</th><th style="width:110px;">KB</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><code><?= h($r['file']) ?></code></td>
            <td>
              <?php foreach ($r['tags'] as $t): ?>
                <span class="badge badge-secondary"><?= h($t) ?></span>
              <?php endforeach; ?>
            </td>
            <td><code><?= h($r['size'] !== null ? number_format($r['size']/1024, 1) : '—') ?></code></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="small text-muted mt-2">
      Es heurístico: sirve para ubicar rápido “por dónde pasa” la lógica.
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-10 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  var input = document.getElementById('labClsSearch10');
  var table = document.getElementById('labClsTable10');
  if(!input || !table) return;
  input.addEventListener('input', function(){
    var q = (input.value||'').toLowerCase();
    table.querySelectorAll('tbody tr').forEach(function(tr){
      tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  });
})();
</script>
