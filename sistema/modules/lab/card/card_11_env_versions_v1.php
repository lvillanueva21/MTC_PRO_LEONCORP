<?php
/**
 * CARD 11 - Entorno / Versiones / Capacidades (PHP + DB + ini + extensiones)
 * Versión: v1.0
 * Archivo: /modules/lab/card/card_11_env_versions_v1.php
 * ID interno: LAB-CARD-11
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$php = [
    'PHP_VERSION' => PHP_VERSION,
    'SAPI' => php_sapi_name(),
    'OS' => PHP_OS_FAMILY . ' / ' . PHP_OS,
    'Timezone' => date_default_timezone_get(),
];

$iniKeys = [
    'memory_limit','max_execution_time','post_max_size','upload_max_filesize','max_input_vars',
    'display_errors','log_errors','error_reporting','default_charset'
];
$ini = [];
foreach ($iniKeys as $k) $ini[$k] = ini_get($k);

$ext = [
    'mysqli' => extension_loaded('mysqli'),
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'curl' => extension_loaded('curl'),
    'openssl' => extension_loaded('openssl'),
    'gd' => extension_loaded('gd'),
    'imagick' => extension_loaded('imagick'),
    'intl' => extension_loaded('intl'),
    'zip' => extension_loaded('zip'),
];

$app = [
    'BASE_URL' => defined('BASE_URL') ? BASE_URL : '(no definido)',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '—',
    'SCRIPT' => $_SERVER['SCRIPT_FILENAME'] ?? '—',
    'METHOD' => $_SERVER['REQUEST_METHOD'] ?? '—',
];

$dbInfo = [
    'driver' => '—',
    'server_version' => '—',
    'db_name' => '—',
    'charset' => '—',
];

try {
    if (function_exists('db')) {
        $conn = db();
        if ($conn instanceof mysqli) {
            $dbInfo['driver'] = 'mysqli';
            $dbInfo['server_version'] = $conn->server_info ?? '—';

            if ($res = @$conn->query("SELECT DATABASE() AS db, @@character_set_connection AS cs")) {
                if ($row = $res->fetch_assoc()) {
                    $dbInfo['db_name'] = $row['db'] ?? '—';
                    $dbInfo['charset'] = $row['cs'] ?? '—';
                }
                $res->free();
            }
        } elseif ($conn instanceof PDO) {
            $dbInfo['driver'] = 'PDO';
            $dbInfo['server_version'] = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
            $dbInfo['db_name'] = $conn->query("SELECT DATABASE()")->fetchColumn() ?: '—';
            $dbInfo['charset'] = $conn->query("SELECT @@character_set_connection")->fetchColumn() ?: '—';
        }
    }
} catch (Throwable $e) {
    $dbInfo['driver'] = $dbInfo['driver'] . ' (error)';
}

$frontend = [
    'jQuery' => '—',
    'Bootstrap' => '—',
    'AdminLTE' => '—',
];
?>

<div class="card card-outline card-teal shadow-sm" data-card="11" data-version="1.0" data-card-id="LAB-CARD-11">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 11 v1.0]</strong> Entorno (PHP/DB/ini/extensiones) + Front</h3>
    <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
  </div>

  <div class="card-body">
    <div class="row">
      <div class="col-lg-4">
        <h6 class="text-muted mb-2">PHP</h6>
        <?php foreach ($php as $k=>$v): ?>
          <div><span class="text-muted"><?= h($k) ?>:</span> <code><?= h($v) ?></code></div>
        <?php endforeach; ?>
      </div>

      <div class="col-lg-4">
        <h6 class="text-muted mb-2">DB</h6>
        <?php foreach ($dbInfo as $k=>$v): ?>
          <div><span class="text-muted"><?= h($k) ?>:</span> <code><?= h($v) ?></code></div>
        <?php endforeach; ?>
      </div>

      <div class="col-lg-4">
        <h6 class="text-muted mb-2">App</h6>
        <?php foreach ($app as $k=>$v): ?>
          <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <span class="text-muted"><?= h($k) ?>:</span> <code><?= h($v) ?></code>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <hr>

    <div class="row">
      <div class="col-lg-6">
        <h6 class="text-muted mb-2">php.ini</h6>
        <div class="table-responsive">
          <table class="table table-sm">
            <tbody>
              <?php foreach ($ini as $k=>$v): ?>
              <tr><td><code><?= h($k) ?></code></td><td><code><?= h((string)$v) ?></code></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="col-lg-6">
        <h6 class="text-muted mb-2">Extensiones</h6>
        <div class="table-responsive">
          <table class="table table-sm">
            <tbody>
              <?php foreach ($ext as $k=>$v): ?>
              <tr><td><code><?= h($k) ?></code></td><td><?= $v ? '<span class="badge badge-success">ON</span>' : '<span class="badge badge-secondary">OFF</span>' ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <h6 class="text-muted mt-3 mb-2">Front (detectado en navegador)</h6>
        <div class="small text-muted">Se completa abajo automáticamente.</div>
        <div id="labFrontDetect11" class="mt-1">
          <code>jQuery=—</code> · <code>Bootstrap=—</code> · <code>AdminLTE=—</code>
        </div>
      </div>
    </div>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-11 · v1.0 · <?= h(basename(__FILE__)) ?></div>
</div>

<script>
(function(){
  function yesno(v){ return v ? 'SI' : 'NO'; }

  var jq = (window.jQuery && window.jQuery.fn && window.jQuery.fn.jquery) ? window.jQuery.fn.jquery : 'NO';
  // Bootstrap: puede variar; probamos v5 (bootstrap) y v4 (jQuery plugin)
  var bs = (window.bootstrap && window.bootstrap.Tooltip) ? 'v5+' :
           (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) ? 'v4' : 'NO';

  // AdminLTE: suele existir $.AdminLTE o AdminLTE
  var ad = (window.AdminLTE) ? 'SI' :
           (window.jQuery && window.jQuery.AdminLTE) ? 'SI' :
           (document.querySelector('body') && document.body.className.includes('sidebar-mini')) ? 'probable' : 'NO';

  var el = document.getElementById('labFrontDetect11');
  if(el){
    el.innerHTML =
      '<code>jQuery=' + jq + '</code> · ' +
      '<code>Bootstrap=' + bs + '</code> · ' +
      '<code>AdminLTE=' + ad + '</code>';
  }
})();
</script>
