<?php
/**
 * CARD 04 - SQL detectado + tablas (escaneo de archivos incluidos)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_04_sql_tables_v1.php
 * ID interno: LAB-CARD-04
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$projectRoot = realpath(__DIR__ . '/../../../..') ?: null;

if (!function_exists('lab04_rel')) {
    function lab04_rel(string $path, ?string $root): string {
        $rp = realpath($path) ?: $path;
        return $root ? str_replace($root, '', $rp) : $rp;
    }
}

if (!function_exists('lab04_extract_tables')) {
    function lab04_extract_tables(string $text): array {
        $tables = [];

        // FROM / JOIN
        if (preg_match_all('/\b(?:from|join)\s+`?([a-zA-Z0-9_\.]+)`?/i', $text, $m)) {
            foreach ($m[1] as $t) $tables[] = $t;
        }
        // UPDATE
        if (preg_match_all('/\bupdate\s+`?([a-zA-Z0-9_\.]+)`?/i', $text, $m)) {
            foreach ($m[1] as $t) $tables[] = $t;
        }
        // INSERT INTO
        if (preg_match_all('/\binsert\s+into\s+`?([a-zA-Z0-9_\.]+)`?/i', $text, $m)) {
            foreach ($m[1] as $t) $tables[] = $t;
        }
        // DELETE FROM
        if (preg_match_all('/\bdelete\s+from\s+`?([a-zA-Z0-9_\.]+)`?/i', $text, $m)) {
            foreach ($m[1] as $t) $tables[] = $t;
        }

        $tables = array_values(array_unique(array_filter($tables)));
        sort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        return $tables;
    }
}

if (!function_exists('lab04_extract_sql_snippets')) {
    function lab04_extract_sql_snippets(string $text, int $max = 50): array {
        $snips = [];
        // muy simple: buscar keywords SQL y capturar alrededor
        $re = '/\b(select|insert|update|delete)\b[\s\S]{0,400}?(?:;|"\s*\)|\'\s*\)|\n\n)/i';
        if (preg_match_all($re, $text, $m)) {
            foreach ($m[0] as $s) {
                $s = trim(preg_replace('/\s+/', ' ', $s));
                $snips[] = $s;
                if (count($snips) >= $max) break;
            }
        }
        return $snips;
    }
}

$maxBytes = 900000; // 0.9 MB por archivo
$files = get_included_files();
$scan = [];

foreach ($files as $f) {
    $rp = realpath($f);
    if (!$rp) continue;
    if ($projectRoot && strncmp($rp, $projectRoot, strlen($projectRoot)) !== 0) continue; // fuera del proyecto, ignorar

    $size = @filesize($rp);
    if (is_int($size) && $size > $maxBytes) continue;

    $code = @file_get_contents($rp);
    if ($code === false) continue;

    $snips = lab04_extract_sql_snippets($code, 30);
    $tables = lab04_extract_tables($code);

    if ($snips || $tables) {
        $scan[] = [
            'file' => lab04_rel($rp, $projectRoot),
            'sql_count' => count($snips),
            'tables' => $tables,
            'examples' => array_slice($snips, 0, 3),
        ];
    }
}

usort($scan, fn($a,$b)=> $b['sql_count'] <=> $a['sql_count']);
$allTables = [];
foreach ($scan as $s) foreach ($s['tables'] as $t) $allTables[$t] = true;
$allTables = array_keys($allTables);
sort($allTables, SORT_NATURAL | SORT_FLAG_CASE);
?>

<div class="card card-outline card-danger shadow-sm" data-card="04" data-version="1.0" data-card-id="LAB-CARD-04">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 04 v1.0]</strong> SQL detectado y tablas involucradas</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="row mb-2">
      <div class="col-md-4"><div class="small text-muted">Archivos con SQL</div><div><code><?= (int)count($scan) ?></code></div></div>
      <div class="col-md-8"><div class="small text-muted">Tablas únicas detectadas</div><div><code><?= h($allTables ? implode(', ', $allTables) : '—') ?></code></div></div>
    </div>

    <div style="max-width: 420px;" class="mb-2">
      <input id="labSqlSearch04" class="form-control form-control-sm" placeholder="Filtrar por archivo o tabla...">
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labSqlTable04">
        <thead><tr>
          <th>Archivo</th>
          <th style="width:110px;">SQL count</th>
          <th>Tablas</th>
          <th>Ejemplos</th>
        </tr></thead>
        <tbody>
        <?php foreach ($scan as $r): ?>
          <tr>
            <td><code><?= h($r['file']) ?></code></td>
            <td><code><?= (int)$r['sql_count'] ?></code></td>
            <td><code><?= h($r['tables'] ? implode(', ', $r['tables']) : '—') ?></code></td>
            <td>
              <?php if ($r['examples']): ?>
                <ul class="mb-0 pl-3">
                  <?php foreach ($r['examples'] as $ex): ?>
                    <li><code><?= h(mb_substr($ex, 0, 220)) ?><?= mb_strlen($ex) > 220 ? '…' : '' ?></code></li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="small text-muted mt-2">
      Esto es escaneo estático. Si el SQL se arma “dinámico” (concatenaciones raras), puede no detectarlo.
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-04 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  var input = document.getElementById('labSqlSearch04');
  var table = document.getElementById('labSqlTable04');
  if(!input || !table) return;
  input.addEventListener('input', function(){
    var q = (input.value || '').toLowerCase();
    table.querySelectorAll('tbody tr').forEach(function(tr){
      tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  });
})();
</script>
