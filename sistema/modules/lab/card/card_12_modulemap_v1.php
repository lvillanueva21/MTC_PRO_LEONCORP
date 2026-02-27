<?php
/**
 * CARD 12 - Mapa del módulo (includes agrupados + últimos cambios)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_12_modulemap_v1.php
 * ID interno: LAB-CARD-12
 */

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$projectRoot = realpath(__DIR__ . '/../../../..') ?: null;

$scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
$reqUri     = $_SERVER['REQUEST_URI'] ?? '';

function lab12_rel(string $path, ?string $root): string {
    $rp = realpath($path) ?: $path;
    return $root ? str_replace($root, '', $rp) : $rp;
}

function lab12_detect_module(string $absPathOrUri): ?string {
    // Busca /modules/<modulo>/
    $p = str_replace('\\', '/', $absPathOrUri);
    $pos = strpos($p, '/modules/');
    if ($pos === false) return null;
    $rest = substr($p, $pos + strlen('/modules/'));
    $parts = explode('/', ltrim($rest, '/'));
    return $parts[0] ?? null;
}

$module = lab12_detect_module($scriptFile) ?: lab12_detect_module($reqUri) ?: '—';

// Scope: por defecto "module", opcional "project"
$scope = $_GET['lab_scope'] ?? 'module';
$scope = in_array($scope, ['module','project'], true) ? $scope : 'module';

// Toma includes reales
$included = get_included_files();

// Construir lista de archivos (solo dentro del proyecto si se puede)
$files = [];
foreach ($included as $f) {
    $rp = realpath($f);
    if (!$rp) continue;

    if ($projectRoot && strncmp($rp, $projectRoot, strlen($projectRoot)) !== 0) {
        continue; // fuera del proyecto
    }

    $rel = lab12_rel($rp, $projectRoot);
    $mtime = @filemtime($rp);
    $size  = @filesize($rp);

    // Filtrar scope
    if ($scope === 'module' && $module !== '—') {
        if (strpos(str_replace('\\','/',$rel), '/modules/'.$module.'/') === false) {
            continue;
        }
    }

    $files[] = [
        'abs' => $rp,
        'rel' => $rel ?: $rp,
        'dir' => dirname($rel ?: $rp),
        'mtime' => is_int($mtime) ? $mtime : null,
        'size' => is_int($size) ? $size : null,
    ];
}

// Helpers
function lab12_age_badge(?int $mtime): array {
    if (!$mtime) return ['cls'=>'badge-secondary','txt'=>'sin mtime'];
    $age = time() - $mtime;
    $day = 86400;
    if ($age < $day) return ['cls'=>'badge-danger','txt'=>'< 1 día'];
    if ($age < 7*$day) return ['cls'=>'badge-warning','txt'=>'< 7 días'];
    if ($age < 30*$day) return ['cls'=>'badge-info','txt'=>'< 30 días'];
    return ['cls'=>'badge-secondary','txt'=>'> 30 días'];
}

$totalFiles = count($files);
$sumSize = array_sum(array_map(fn($r)=> (int)($r['size'] ?? 0), $files));

// Agrupar por carpeta
$folders = [];
foreach ($files as $r) {
    $d = $r['dir'] ?? '—';
    if (!isset($folders[$d])) {
        $folders[$d] = [
            'dir' => $d,
            'count' => 0,
            'size' => 0,
            'newest_mtime' => null,
            'newest_file' => null,
        ];
    }
    $folders[$d]['count']++;
    $folders[$d]['size'] += (int)($r['size'] ?? 0);
    if ($r['mtime'] && (!$folders[$d]['newest_mtime'] || $r['mtime'] > $folders[$d]['newest_mtime'])) {
        $folders[$d]['newest_mtime'] = $r['mtime'];
        $folders[$d]['newest_file']  = $r['rel'];
    }
}
$folderRows = array_values($folders);
usort($folderRows, fn($a,$b)=> ($b['count'] <=> $a['count']) ?: strcasecmp($a['dir'], $b['dir']));

// Top recientes
$recent = $files;
usort($recent, function($a,$b){
    $am = $a['mtime'] ?? 0;
    $bm = $b['mtime'] ?? 0;
    return $bm <=> $am;
});
$recent = array_slice($recent, 0, 20);
?>

<div class="card card-outline card-dark shadow-sm" data-card="12" data-version="1.0" data-card-id="LAB-CARD-12">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 12 v1.0]</strong> Mapa del módulo (includes agrupados + “culpables” recientes)</h3>
    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
    </div>
  </div>

  <div class="card-body">
    <div class="row mb-2">
      <div class="col-md-3">
        <div class="small text-muted">Módulo detectado</div>
        <div><code><?= h($module) ?></code></div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Scope</div>
        <div><code><?= h($scope) ?></code></div>
        <div class="small text-muted mt-1">
          <code>?lab_scope=module</code> / <code>?lab_scope=project</code>
        </div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Archivos en scope</div>
        <div><code><?= (int)$totalFiles ?></code></div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Tamaño total</div>
        <div><code><?= h(number_format($sumSize/1024, 1)) ?> KB</code></div>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-2" style="gap:10px;">
      <div class="small text-muted">
        Útil para módulos con muchos PHP: agrupa por carpeta y marca qué cambió recientemente.
      </div>
      <div style="max-width: 360px; width:100%;">
        <input id="labMapSearch12" class="form-control form-control-sm" placeholder="Filtrar carpeta/archivo...">
      </div>
    </div>

    <div class="row">
      <!-- Carpeta resumen -->
      <div class="col-lg-7">
        <h6 class="text-muted mb-2">Carpetas (en este scope)</h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover" id="labMapFolders12">
            <thead>
              <tr>
                <th>Carpeta</th>
                <th style="width:90px;">Archivos</th>
                <th style="width:120px;">KB</th>
                <th style="width:140px;">Último cambio</th>
                <th>Archivo más nuevo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($folderRows as $r): ?>
                <?php $b = lab12_age_badge($r['newest_mtime']); ?>
                <tr>
                  <td><code><?= h($r['dir']) ?></code></td>
                  <td><code><?= (int)$r['count'] ?></code></td>
                  <td><code><?= h(number_format(($r['size'] ?? 0)/1024, 1)) ?></code></td>
                  <td>
                    <?php if ($r['newest_mtime']): ?>
                      <span class="badge <?= h($b['cls']) ?>"><?= h($b['txt']) ?></span>
                      <div class="small text-muted"><code><?= h(date('Y-m-d H:i:s', $r['newest_mtime'])) ?></code></div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <code><?= h($r['newest_file'] ?: '—') ?></code>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Archivos recientes -->
      <div class="col-lg-5">
        <h6 class="text-muted mb-2">Top recientes (posibles “culpables”)</h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover" id="labMapRecent12">
            <thead>
              <tr>
                <th style="width:40px;">#</th>
                <th>Archivo</th>
                <th style="width:140px;">Cuándo</th>
                <th style="width:80px;">KB</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $i => $r): ?>
                <?php $b = lab12_age_badge($r['mtime']); ?>
                <tr>
                  <td><code><?= $i+1 ?></code></td>
                  <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <code><?= h($r['rel']) ?></code>
                  </td>
                  <td>
                    <?php if ($r['mtime']): ?>
                      <span class="badge <?= h($b['cls']) ?>"><?= h($b['txt']) ?></span>
                      <div class="small text-muted"><code><?= h(date('Y-m-d H:i:s', $r['mtime'])) ?></code></div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td><code><?= h($r['size'] !== null ? number_format($r['size']/1024, 1) : '—') ?></code></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="small text-muted mt-2">
          Regla práctica: si algo “se rompió”, casi siempre está en el top de recientes o en la carpeta con más actividad.
        </div>
      </div>
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-12 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  var input = document.getElementById('labMapSearch12');
  var t1 = document.getElementById('labMapFolders12');
  var t2 = document.getElementById('labMapRecent12');
  if(!input || !t1 || !t2) return;

  function filter(){
    var q = (input.value||'').toLowerCase();
    [t1, t2].forEach(function(tbl){
      tbl.querySelectorAll('tbody tr').forEach(function(tr){
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }
  input.addEventListener('input', filter);
})();
</script>
