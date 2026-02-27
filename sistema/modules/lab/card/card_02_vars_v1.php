<?php
/**
 * CARD 02 - Variables / datos cargados en la página (runtime)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_02_vars_v1.php
 * ID interno: LAB-CARD-02
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('lab02_value_preview')) {
    function lab02_value_preview($v, int $maxStr = 160): string {
        if (is_null($v)) return 'null';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_int($v) || is_float($v)) return (string)$v;

        if (is_string($v)) {
            $s = str_replace(["\r\n","\r","\n","\t"], ['\\n','\\n','\\n','\\t'], $v);
            if (mb_strlen($s) > $maxStr) $s = mb_substr($s, 0, $maxStr) . '…';
            return '"' . $s . '"';
        }

        if (is_array($v)) {
            $n = count($v);
            $keys = array_keys($v);
            $showKeys = array_slice($keys, 0, 10);
            $kTxt = $showKeys ? implode(', ', array_map(fn($k)=> is_int($k)? $k : ('"'.$k.'"'), $showKeys)) : '';
            $more = ($n > 10) ? '…' : '';
            return 'array(' . $n . ') keys: [' . $kTxt . $more . ']';
        }

        if (is_object($v)) return 'object(' . get_class($v) . ')';
        if (is_resource($v)) return 'resource(' . get_resource_type($v) . ')';
        return gettype($v);
    }
}

if (!function_exists('lab02_type_size')) {
    function lab02_type_size($v): string {
        if (is_array($v)) return 'items=' . count($v);
        if (is_string($v)) return 'len=' . mb_strlen($v);
        if (is_object($v)) return 'class=' . get_class($v);
        if (is_resource($v)) return 'type=' . get_resource_type($v);
        return '—';
    }
}

if (!function_exists('lab02_is_probable_db_data')) {
    function lab02_is_probable_db_data(string $name, $v): bool {
        $n = strtolower($name);
        $hints = ['row','rows','data','datos','lista','list','result','res','fila','reg','regs','items','clientes','users','usuario','empresa','cert','perm'];
        foreach ($hints as $h) if (strpos($n, $h) !== false) return true;

        if (is_array($v) && count($v) > 0) {
            $first = reset($v);
            if (is_array($first)) return true;
            if (count($v) >= 5) return true;
        }

        if (is_object($v)) {
            $c = strtolower(get_class($v));
            if (strpos($c, 'mysqli') !== false) return true;
            if (strpos($c, 'pdo') !== false) return true;
        }
        return false;
    }
}

$mode = $_GET['lab_vars'] ?? 'db';
$mode = in_array($mode, ['all', 'db'], true) ? $mode : 'db';

$__vars = get_defined_vars();
$__skip = ['__vars','__skip','mode'];
$__super = ['GLOBALS','_SERVER','_GET','_POST','_FILES','_COOKIE','_SESSION','_REQUEST','_ENV'];

$varsOut = [];
foreach ($__vars as $name => $val) {
    if (in_array($name, $__skip, true)) continue;
    if (in_array($name, $__super, true)) continue;
    if ($mode === 'db' && !lab02_is_probable_db_data($name, $val)) continue;
    $varsOut[$name] = $val;
}

uksort($varsOut, function($a, $b) use ($varsOut) {
    $pa = lab02_is_probable_db_data($a, $varsOut[$a]) ? 0 : 1;
    $pb = lab02_is_probable_db_data($b, $varsOut[$b]) ? 0 : 1;
    if ($pa !== $pb) return $pa <=> $pb;
    return strcasecmp($a, $b);
});

$mem  = memory_get_usage(true);
$peak = memory_get_peak_usage(true);
$uri  = $_SERVER['REQUEST_URI'] ?? '—';
$script = $_SERVER['SCRIPT_FILENAME'] ?? '—';
?>

<div class="card card-outline card-success shadow-sm" data-card="02" data-version="1.0" data-card-id="LAB-CARD-02">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 02 v1.0]</strong> Variables y datos cargados (runtime)</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="row mb-3">
      <div class="col-md-8">
        <div class="small text-muted">Página</div><div><code><?= h($uri) ?></code></div>
        <div class="small text-muted mt-1">Script</div><div><code><?= h($script) ?></code></div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Memoria</div><div><code><?= h(number_format($mem/1024/1024, 2)) ?> MB</code></div>
        <div class="small text-muted mt-1">Pico</div><div><code><?= h(number_format($peak/1024/1024, 2)) ?> MB</code></div>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-2" style="gap:10px;">
      <div class="small text-muted">Modo: <code><?= h($mode) ?></code> | Mostradas: <code><?= (int)count($varsOut) ?></code></div>
      <div style="max-width: 320px; width: 100%;"><input id="labVarSearch02" type="text" class="form-control form-control-sm" placeholder="Filtrar por nombre..."></div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover" id="labVarTable02">
        <thead><tr><th style="width: 24%;">Variable</th><th style="width: 12%;">Tipo</th><th style="width: 14%;">Tamaño</th><th>Preview</th></tr></thead>
        <tbody>
        <?php foreach ($varsOut as $name => $val): ?>
          <?php $isDb = lab02_is_probable_db_data($name, $val); ?>
          <tr class="<?= $isDb ? 'table-success' : '' ?>">
            <td><code>$<?= h($name) ?></code></td>
            <td><code><?= h(gettype($val)) ?></code></td>
            <td><code><?= h(lab02_type_size($val)) ?></code></td>
            <td><code><?= h(lab02_value_preview($val)) ?></code></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="small text-muted mt-2">
      <code>?lab_vars=db</code> (default) solo probable BD · <code>?lab_vars=all</code> muestra todo
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-02 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  var input = document.getElementById('labVarSearch02');
  var table = document.getElementById('labVarTable02');
  if(!input || !table) return;
  input.addEventListener('input', function(){
    var q = (input.value || '').toLowerCase();
    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(tr){
      var txt = tr.querySelector('td') ? tr.querySelector('td').innerText.toLowerCase() : '';
      tr.style.display = (txt.indexOf(q) !== -1) ? '' : 'none';
    });
  });
})();
</script>
