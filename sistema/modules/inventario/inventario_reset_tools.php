<?php
// modules/inventario/inventario_reset_tools.php
// Panel de testing + API (AJAX) para reiniciar inventario por empresa actual.
// - Se puede borrar este archivo en el futuro sin afectar el inventario normal.
// - Solo toca tablas: inv_bien_categoria, inv_bienes, inv_categorias, inv_movimientos, inv_ubicaciones
// - Si toca bienes/imágenes: borra objetos en S4 (sin cabos sueltos).
//
// Modo UI: cuando se incluye desde index.php.
// Modo API: cuando se accede directo con ?ajax=1

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/inv_s4.php';
require_once __DIR__ . '/inv_lib.php';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function inv_reset_json_error($code, $msg, $extra = []) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'msg'=>$msg] + (array)$extra);
  exit;
}
function inv_reset_json_ok($arr = []) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>true] + (array)$arr);
  exit;
}

function inv_reset_norm($s){ return trim((string)$s); }

function inv_reset_token_new() {
  // PHP 7+ soporta random_bytes; fallback openssl por seguridad.
  if (function_exists('random_bytes')) {
    return bin2hex(random_bytes(16));
  }
  if (function_exists('openssl_random_pseudo_bytes')) {
    return bin2hex(openssl_random_pseudo_bytes(16));
  }
  // último recurso: no ideal, pero evita romper en entornos raros
  return bin2hex(md5(uniqid('inv_reset', true), true));
}

function inv_reset_stmt_all($mysqli, $sql, $types, $params) {
  $st = $mysqli->prepare($sql);
  if (!$st) throw new Exception('SQL prepare failed');
  if ($types !== '') {
    $refs = [];
    $refs[] = $types;
    for ($i=0; $i<count($params); $i++) $refs[] = &$params[$i];
    call_user_func_array([$st,'bind_param'], $refs);
  }
  $st->execute();
  $res = $st->get_result();
  if (!$res) return [];
  return $res->fetch_all(MYSQLI_ASSOC);
}

function inv_reset_stmt_one($mysqli, $sql, $types, $params) {
  $rows = inv_reset_stmt_all($mysqli, $sql, $types, $params);
  return $rows ? $rows[0] : null;
}

function inv_reset_count_table($mysqli, $sql, $types, $params) {
  $r = inv_reset_stmt_one($mysqli, $sql, $types, $params);
  if (!$r) return 0;
  foreach ($r as $v) return (int)$v;
  return 0;
}

function inv_reset_try_autoinc($mysqli, $table, $idCol) {
  // OJO: AUTO_INCREMENT es GLOBAL por tabla (no por empresa).
  // Solo lo reiniciamos si la tabla queda totalmente vacía (sin registros de otras empresas).
  $r = inv_reset_stmt_one($mysqli, "SELECT COUNT(*) c, MAX($idCol) mx FROM `$table`", '', []);
  $c = (int)($r['c'] ?? 0);
  $mx = (int)($r['mx'] ?? 0);

  if ($c === 0) {
    // Si está vacía, reinicia a 1
    $mysqli->query("ALTER TABLE `$table` AUTO_INCREMENT=1");
    return ['ok'=>1,'did'=>1,'reason'=>'vacía'];
  }

  // Si aún hay registros (otras empresas), MySQL no puede volver a 1 sin borrar todo.
  return ['ok'=>1,'did'=>0,'reason'=>'no_vacía','count'=>$c,'max'=>$mx];
}

function inv_reset_collect_img_keys($mysqli, $empresaId, $limit = 100000) {
  $st = $mysqli->prepare("SELECT img_key FROM inv_bienes WHERE id_empresa=? AND img_key IS NOT NULL AND img_key<>'' ORDER BY id DESC LIMIT $limit");
  $st->bind_param('i', $empresaId);
  $st->execute();
  $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

  $keys = [];
  foreach ($rows as $r) {
    $k = trim((string)($r['img_key'] ?? ''));
    if ($k !== '') $keys[$k] = true;
  }
  return array_keys($keys);
}

function inv_reset_delete_s4_keys_or_fail($keys, $empresaId) {
  // Sin cabos sueltos: si hay keys fuera del patrón permitido, ABORTAMOS (seguridad).
  $bad = [];
  foreach ($keys as $k) {
    if (!inv_s4_key_allowed($k, $empresaId)) $bad[] = $k;
  }
  if (!empty($bad)) {
    throw new Exception('Hay img_key fuera del patrón permitido (seguridad). No se borró nada. Ejemplos: ' . implode(', ', array_slice($bad, 0, 3)));
  }

  // Borra con reintentos. Si falla una, aborta.
  foreach ($keys as $k) {
    $tries = 0;
    while (true) {
      $tries++;
      try {
        inv_s4_delete($k);
        break;
      } catch (Throwable $e) {
        if ($tries >= 3) {
          throw new Exception('Falló borrado en S4 para key: ' . $k . ' (' . $e->getMessage() . ')');
        }
        usleep(250000); // 250ms
      }
    }
  }
}

// ==========================
// MODO API (AJAX)
// ==========================
$ajax = (string)($_GET['ajax'] ?? $_POST['ajax'] ?? '');
if ($ajax === '1') {
  try {
    set_error_handler(function($severity,$message,$file,$line){
      if (!(error_reporting() & $severity)) return false;
      throw new ErrorException($message, 0, $severity, $file, $line);
    });

    acl_require_ids([1,4,6]);
    verificarPermiso(['Desarrollo','Administración','Gerente']);

    $u = currentUser();
    $empresaId = (int)($u['empresa']['id'] ?? 0);
    $userId    = (int)($u['id'] ?? 0);
    if ($empresaId <= 0) inv_reset_json_error(403,'Empresa no asignada');
    if ($userId <= 0)    inv_reset_json_error(403,'Usuario no válido');

    $mysqli = db();

    $action = inv_reset_norm($_GET['action'] ?? $_POST['action'] ?? '');
    $mode   = inv_reset_norm($_GET['mode'] ?? $_POST['mode'] ?? '');

    $validModes = ['all','bienes','movimientos','categorias','ubicaciones','imagenes'];
    if (!in_array($mode, $validModes, true)) inv_reset_json_error(400,'Modo inválido');

    if ($action === 'preview') {
      // Preview: lista EXACTA de qué se borrará (por empresa actual)
      $out = [
        'mode' => $mode,
        'empresa_id' => $empresaId,
        'tables' => [],
        's4' => ['keys_count'=>0,'keys_sample'=>[]],
        'warnings' => []
      ];

      // Helper para anexar tabla + muestra
      $add = function($table, $count, $sample) use (&$out) {
        $out['tables'][] = [
          'table' => $table,
          'count' => (int)$count,
          'sample' => $sample
        ];
      };

      if ($mode === 'all' || $mode === 'bienes' || $mode === 'imagenes') {
        $keys = inv_reset_collect_img_keys($mysqli, $empresaId);
        $out['s4']['keys_count'] = count($keys);
        $out['s4']['keys_sample'] = array_slice($keys, 0, 20);

        $cntBienes = inv_reset_count_table($mysqli, "SELECT COUNT(*) c FROM inv_bienes WHERE id_empresa=?", 'i', [$empresaId]);
        $sampleBienes = inv_reset_stmt_all($mysqli,
          "SELECT id,nombre,tipo,estado,cantidad,unidad,activo,img_key,creado
           FROM inv_bienes WHERE id_empresa=? ORDER BY id DESC LIMIT 30",
          'i', [$empresaId]
        );
        $add('inv_bienes', $cntBienes, $sampleBienes);

        $cntMovCas = inv_reset_count_table($mysqli,
          "SELECT COUNT(*) c FROM inv_movimientos m
           INNER JOIN inv_bienes b ON b.id=m.id_bien
           WHERE b.id_empresa=?",
          'i', [$empresaId]
        );
        $sampleMovCas = inv_reset_stmt_all($mysqli,
          "SELECT m.id,m.id_bien,m.tipo,m.nota,m.creado
           FROM inv_movimientos m
           INNER JOIN inv_bienes b ON b.id=m.id_bien
           WHERE b.id_empresa=?
           ORDER BY m.id DESC LIMIT 30",
          'i', [$empresaId]
        );
        $add('inv_movimientos (por cascade al borrar bienes)', $cntMovCas, $sampleMovCas);

        $cntBcCas = inv_reset_count_table($mysqli,
          "SELECT COUNT(*) c FROM inv_bien_categoria bc
           INNER JOIN inv_bienes b ON b.id=bc.id_bien
           WHERE b.id_empresa=?",
          'i', [$empresaId]
        );
        $sampleBcCas = inv_reset_stmt_all($mysqli,
          "SELECT bc.id_bien, bc.id_categoria
           FROM inv_bien_categoria bc
           INNER JOIN inv_bienes b ON b.id=bc.id_bien
           WHERE b.id_empresa=?
           ORDER BY bc.id_bien DESC LIMIT 30",
          'i', [$empresaId]
        );
        $add('inv_bien_categoria (por cascade al borrar bienes)', $cntBcCas, $sampleBcCas);
      }

      if ($mode === 'all' || $mode === 'movimientos') {
        $cntMov = inv_reset_count_table($mysqli, "SELECT COUNT(*) c FROM inv_movimientos WHERE id_empresa=?", 'i', [$empresaId]);
        $sampleMov = inv_reset_stmt_all($mysqli,
          "SELECT id,id_bien,tipo,nota,creado
           FROM inv_movimientos WHERE id_empresa=? ORDER BY id DESC LIMIT 30",
          'i', [$empresaId]
        );
        $add('inv_movimientos', $cntMov, $sampleMov);
      }

      if ($mode === 'all' || $mode === 'categorias') {
        $cntCat = inv_reset_count_table($mysqli, "SELECT COUNT(*) c FROM inv_categorias WHERE id_empresa=?", 'i', [$empresaId]);
        $sampleCat = inv_reset_stmt_all($mysqli,
          "SELECT id,nombre,activo,creado FROM inv_categorias WHERE id_empresa=? ORDER BY id DESC LIMIT 30",
          'i', [$empresaId]
        );
        $add('inv_categorias', $cntCat, $sampleCat);

        $cntBc = inv_reset_count_table($mysqli,
          "SELECT COUNT(*) c FROM inv_bien_categoria bc
           INNER JOIN inv_categorias c ON c.id=bc.id_categoria
           WHERE c.id_empresa=?",
          'i', [$empresaId]
        );
        $sampleBc = inv_reset_stmt_all($mysqli,
          "SELECT bc.id_bien, bc.id_categoria
           FROM inv_bien_categoria bc
           INNER JOIN inv_categorias c ON c.id=bc.id_categoria
           WHERE c.id_empresa=?
           ORDER BY bc.id_categoria DESC LIMIT 30",
          'i', [$empresaId]
        );
        $add('inv_bien_categoria (por cascade al borrar categorías)', $cntBc, $sampleBc);
      }

      if ($mode === 'all' || $mode === 'ubicaciones') {
        $cntUb = inv_reset_count_table($mysqli, "SELECT COUNT(*) c FROM inv_ubicaciones WHERE id_empresa=?", 'i', [$empresaId]);
        $sampleUb = inv_reset_stmt_all($mysqli,
          "SELECT id,nombre,activo,creado FROM inv_ubicaciones WHERE id_empresa=? ORDER BY id DESC LIMIT 30",
          'i', [$empresaId]
        );
        $add('inv_ubicaciones', $cntUb, $sampleUb);

        $cntBienUb = inv_reset_count_table($mysqli,
          "SELECT COUNT(*) c FROM inv_bienes WHERE id_empresa=? AND id_ubicacion IS NOT NULL",
          'i', [$empresaId]
        );
        $add('inv_bienes (refs a ubicaciones -> quedarán NULL por ON DELETE SET NULL)', $cntBienUb, []);

        $cntMovUb = inv_reset_count_table($mysqli,
          "SELECT COUNT(*) c FROM inv_movimientos
           WHERE id_empresa=? AND (desde_ubicacion IS NOT NULL OR hacia_ubicacion IS NOT NULL)",
          'i', [$empresaId]
        );
        $add('inv_movimientos (refs a ubicaciones -> quedarán NULL por ON DELETE SET NULL)', $cntMovUb, []);
      }

      if ($mode === 'imagenes') {
        $out['warnings'][] = 'Este modo NO borra bienes: solo borra objetos en S4 y deja img_key=NULL en inv_bienes (empresa actual).';
      }

      // Advertencia AUTO_INCREMENT (global)
      $out['warnings'][] = 'AUTO_INCREMENT es GLOBAL por tabla. Se reinicia a 1 SOLO si la tabla queda totalmente vacía (sin registros de otras empresas).';

      inv_reset_json_ok(['data'=>$out]);
    }

    if ($action === 'run') {
      // RUN: solo DES/ADM
      verificarPermiso(['Desarrollo','Administración']);

      if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

      $token = inv_reset_norm($_POST['token'] ?? '');
      $confirm = strtoupper(inv_reset_norm($_POST['confirm'] ?? ''));
      if ($token === '' || empty($_SESSION['inv_reset_token']) || !hash_equals((string)$_SESSION['inv_reset_token'], $token)) {
        inv_reset_json_error(403,'Token inválido. Recarga la página.');
      }
      if ($confirm !== 'REINICIAR') {
        inv_reset_json_error(400,'Confirmación inválida. Escribe REINICIAR.');
      }

      $res = [
        'mode'=>$mode,
        'empresa_id'=>$empresaId,
        's4_deleted'=>0,
        'db'=>[],
        'autoinc'=>[],
        'warnings'=>[]
      ];

      $mysqli->set_charset('utf8mb4');

      // =========
      // S4 primero (para no dejar huérfanos)
      // =========
      if ($mode === 'all' || $mode === 'bienes' || $mode === 'imagenes') {
        $keys = inv_reset_collect_img_keys($mysqli, $empresaId);
        if (!empty($keys)) {
          inv_reset_delete_s4_keys_or_fail($keys, $empresaId);
          $res['s4_deleted'] = count($keys);
        }
      }

      // =========
      // DB
      // =========
      $mysqli->begin_transaction();

      try {
        if ($mode === 'all' || $mode === 'bienes') {
          // Borra bienes por empresa (cascades: inv_movimientos + inv_bien_categoria)
          $st = $mysqli->prepare("DELETE FROM inv_bienes WHERE id_empresa=?");
          $st->bind_param('i', $empresaId);
          $st->execute();
          $res['db'][] = ['op'=>'DELETE inv_bienes (empresa)', 'affected'=>$st->affected_rows];
        }

        if ($mode === 'all' || $mode === 'movimientos') {
          // Borra movimientos por empresa
          $st = $mysqli->prepare("DELETE FROM inv_movimientos WHERE id_empresa=?");
          $st->bind_param('i', $empresaId);
          $st->execute();
          $res['db'][] = ['op'=>'DELETE inv_movimientos (empresa)', 'affected'=>$st->affected_rows];
        }

        if ($mode === 'all' || $mode === 'categorias') {
          // Por seguridad, limpiamos relaciones por bienes de esta empresa
          $st = $mysqli->prepare(
            "DELETE bc FROM inv_bien_categoria bc
             INNER JOIN inv_bienes b ON b.id=bc.id_bien
             WHERE b.id_empresa=?"
          );
          $st->bind_param('i', $empresaId);
          $st->execute();
          $res['db'][] = ['op'=>'DELETE inv_bien_categoria (por bienes empresa)', 'affected'=>$st->affected_rows];

          // Y también por categorías de esta empresa
          $st = $mysqli->prepare(
            "DELETE bc FROM inv_bien_categoria bc
             INNER JOIN inv_categorias c ON c.id=bc.id_categoria
             WHERE c.id_empresa=?"
          );
          $st->bind_param('i', $empresaId);
          $st->execute();
          $res['db'][] = ['op'=>'DELETE inv_bien_categoria (por categorías empresa)', 'affected'=>$st->affected_rows];

          // Borra categorías
          $st = $mysqli->prepare("DELETE FROM inv_categorias WHERE id_empresa=?");
          $st->bind_param('i', $empresaId);
          $st->execute();
          $res['db'][] = ['op'=>'DELETE inv_categorias (empresa)', 'affected'=>$st->affected_rows];
        }

        if ($mode === 'all' || $mode === 'ubicaciones') {
          // Borra ubicaciones (FK ON DELETE SET NULL en bienes y movimientos)
          $st = $mysqli->prepare("DELETE FROM inv_ubicaciones WHERE id_empresa=?");
          $st->bind_param('i', $empresaId);
          $st->execute();
          $res['db'][] = ['op'=>'DELETE inv_ubicaciones (empresa)', 'affected'=>$st->affected_rows];
        }

        if ($mode === 'imagenes') {
          // Dejar img_key NULL (empresa actual)
          $st = $mysqli->prepare("UPDATE inv_bienes SET img_key=NULL WHERE id_empresa=?");
          $st->bind_param('i', $empresaId);
          $st->execute();
          $res['db'][] = ['op'=>'UPDATE inv_bienes SET img_key=NULL (empresa)', 'affected'=>$st->affected_rows];
        }

        $mysqli->commit();
      } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
      }

      // =========
      // AUTO_INCREMENT (global): solo si tabla queda vacía
      // =========
      // Nota: inv_bien_categoria no tiene autoinc.
      if ($mode === 'all' || $mode === 'bienes') {
        $res['autoinc']['inv_bienes'] = inv_reset_try_autoinc($mysqli, 'inv_bienes', 'id');
      }
      if ($mode === 'all' || $mode === 'movimientos' || $mode === 'bienes') {
        // si borraste bienes, también puede haber quedado vacía inv_movimientos por cascade
        $res['autoinc']['inv_movimientos'] = inv_reset_try_autoinc($mysqli, 'inv_movimientos', 'id');
      }
      if ($mode === 'all' || $mode === 'categorias') {
        $res['autoinc']['inv_categorias'] = inv_reset_try_autoinc($mysqli, 'inv_categorias', 'id');
      }
      if ($mode === 'all' || $mode === 'ubicaciones') {
        $res['autoinc']['inv_ubicaciones'] = inv_reset_try_autoinc($mysqli, 'inv_ubicaciones', 'id');
      }

      $res['warnings'][] = 'AUTO_INCREMENT se reinicia a 1 SOLO si la tabla quedó totalmente vacía (sin registros de otras empresas).';

      // Invalidar token (un uso)
      $_SESSION['inv_reset_token'] = inv_reset_token_new();

      inv_reset_json_ok(['data'=>$res, 'new_token'=>$_SESSION['inv_reset_token']]);
    }

    inv_reset_json_error(400,'Acción no válida');

  } catch (Throwable $e) {
    inv_reset_json_error(500,'Error de servidor', ['dev'=>$e->getMessage()]);
  }
}

// ==========================
// MODO UI (INCLUDE)
// ==========================
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
if (empty($_SESSION['inv_reset_token'])) $_SESSION['inv_reset_token'] = inv_reset_token_new();

$u = currentUser();
$empresaId  = (int)($u['empresa']['id'] ?? 0);
$empresaNom = (string)($u['empresa']['nombre'] ?? '—');
?>
<div class="container-fluid mt-4 mb-5" id="invResetPanel">
  <div class="card card-outline card-warning shadow-sm">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-flask mr-1"></i> Herramientas de Testing: reinicios del inventario
      </h3>
      <div class="card-tools">
        <button type="button" class="btn btn-tool" data-card-widget="collapse">
          <i class="fas fa-minus"></i>
        </button>
      </div>
    </div>

    <div class="card-body">
      <div class="row">
        <!-- Izquierda: opciones -->
        <div class="col-12 col-md-3 mb-3 mb-md-0">
          <div class="list-group" id="invResetMenu">
            <button type="button" class="list-group-item list-group-item-action active" data-mode="all">
              <i class="fas fa-bomb mr-1"></i> Reset completo
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-mode="bienes">
              <i class="fas fa-box-open mr-1"></i> Reset bienes (productos)
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-mode="movimientos">
              <i class="fas fa-stream mr-1"></i> Reset movimientos (historial)
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-mode="categorias">
              <i class="fas fa-tags mr-1"></i> Reset categorías
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-mode="ubicaciones">
              <i class="fas fa-map-marker-alt mr-1"></i> Reset ubicaciones
            </button>
            <button type="button" class="list-group-item list-group-item-action" data-mode="imagenes">
              <i class="fas fa-image mr-1"></i> Reset imágenes (S4 + BD)
            </button>
          </div>

          <div class="small text-muted mt-3">
            Empresa actual: <b><?= h($empresaNom) ?></b> (ID <?= (int)$empresaId ?>)
            <div class="mt-1">
              Solo toca tablas de inventario.
            </div>
          </div>
        </div>

        <!-- Derecha: preview + ejecutar -->
        <div class="col-12 col-md-9">
          <div class="alert alert-warning py-2 mb-2">
            <b>Ojo:</b> esto es para <b>testing</b>. Si borras este archivo en el futuro, desaparece este panel.
          </div>

          <div id="invResetPreview" class="border rounded p-3" style="background:#fff;">
            <div class="text-muted">Cargando vista previa…</div>
          </div>

          <hr>

          <div class="form-row">
            <div class="form-group col-12 col-md-6 mb-2">
              <label class="small mb-1">Escribe <b>REINICIAR</b> para habilitar el botón</label>
              <input type="text" class="form-control" id="invResetConfirm" autocomplete="off" placeholder="REINICIAR">
            </div>
            <div class="form-group col-12 col-md-6 mb-2">
              <label class="small mb-1 d-none d-md-block">&nbsp;</label>
              <button type="button" class="btn btn-danger btn-block" id="invResetRun" disabled>
                <i class="fas fa-skull-crossbones mr-1"></i> Ejecutar reinicio seleccionado
              </button>
            </div>
          </div>

          <div class="small text-muted" id="invResetNote"></div>
          <div class="alert alert-danger d-none mt-2" id="invResetErr"></div>
          <div class="alert alert-success d-none mt-2" id="invResetOk"></div>

        </div>
      </div>
    </div>
  </div>
</div>

<style>
#invResetPanel .list-group-item { font-weight: 700; }
#invResetPanel .list-group-item i { width: 18px; text-align: center; }
#invResetPanel .invrs-table { width:100%; font-size: 12px; }
#invResetPanel .invrs-table th { white-space: nowrap; }
#invResetPanel .invrs-k { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-weight: 800; }
#invResetPanel .badge { font-weight: 800; }
</style>

<script>
(function(){
  var panel = document.getElementById('invResetPanel');
  if (!panel) return;

  var menu = document.getElementById('invResetMenu');
  var preview = document.getElementById('invResetPreview');
  var inp = document.getElementById('invResetConfirm');
  var btnRun = document.getElementById('invResetRun');
  var note = document.getElementById('invResetNote');
  var errBox = document.getElementById('invResetErr');
  var okBox = document.getElementById('invResetOk');

  var token = "<?= h($_SESSION['inv_reset_token']) ?>";
  var apiUrl = "<?= h(BASE_URL) ?>/modules/inventario/inventario_reset_tools.php";

  var state = { mode: 'all', lastPreview: null };

  function esc(s){
    s = (s == null) ? '' : String(s);
    return s.replace(/[&<>\"']/g, function (m) {
      return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m]);
    });
  }

  async function j(url, opts){
    var r = await fetch(url, Object.assign({ credentials:'same-origin' }, (opts||{})));
    var txt = await r.text();
    var d = null;
    try { d = JSON.parse(txt); } catch(e){
      var snip = (txt || '').slice(0, 400).replace(/\s+/g,' ').trim();
      throw new Error('Respuesta inválida. Preview: ' + snip);
    }
    if (!r.ok || !d || !d.ok) throw new Error((d && d.msg) ? d.msg : ('HTTP '+r.status));
    return d;
  }

  function setActiveMode(mode){
    state.mode = mode;
    var items = menu.querySelectorAll('.list-group-item');
    for (var i=0;i<items.length;i++){
      items[i].classList.toggle('active', items[i].getAttribute('data-mode') === mode);
    }
    okBox.classList.add('d-none');
    errBox.classList.add('d-none');
    okBox.textContent = '';
    errBox.textContent = '';
  }

  function enableRunByConfirm(){
    var v = (inp.value || '').trim().toUpperCase();
    btnRun.disabled = (v !== 'REINICIAR');
  }

  function renderPreview(data){
    state.lastPreview = data;

    var html = '';
    html += '<div class="d-flex align-items-center justify-content-between flex-wrap">';
    html +=   '<div><b>Modo:</b> <span class="badge badge-warning">' + esc(data.mode) + '</span></div>';
    html +=   '<div class="text-muted small"><b>Empresa ID:</b> ' + esc(data.empresa_id) + '</div>';
    html += '</div>';

    if (data.s4 && (data.mode === 'all' || data.mode === 'bienes' || data.mode === 'imagenes')) {
      html += '<hr class="my-2">';
      html += '<div><b>S4:</b> se borrarán <span class="invrs-k">' + esc(data.s4.keys_count || 0) + '</span> imágenes (img_key).</div>';
      if ((data.s4.keys_sample || []).length) {
        html += '<div class="text-muted small mt-1">Ejemplos: <span class="invrs-k">' + esc(data.s4.keys_sample.join(' | ')) + '</span></div>';
      }
    }

    html += '<hr class="my-2">';
    html += '<div class="table-responsive">';
    html += '<table class="table table-sm table-bordered mb-2 invrs-table">';
    html += '<thead class="thead-light"><tr><th>Tabla</th><th class="text-right">Registros afectados</th><th>Ejemplos</th></tr></thead><tbody>';

    (data.tables || []).forEach(function(t){
      html += '<tr>';
      html += '<td><span class="invrs-k">' + esc(t.table) + '</span></td>';
      html += '<td class="text-right"><b>' + esc(t.count) + '</b></td>';
      html += '<td>';

      if (!t.sample || !t.sample.length) {
        html += '<span class="text-muted">—</span>';
      } else {
        // Render simple (máx 5 líneas)
        var lines = [];
        for (var i=0; i<t.sample.length && i<5; i++){
          var row = t.sample[i];
          if (row.id != null && row.nombre != null) {
            lines.push('#'+row.id+' • '+row.nombre);
          } else if (row.id != null && row.tipo != null) {
            lines.push('#'+row.id+' • '+row.tipo);
          } else if (row.id_bien != null && row.tipo != null) {
            lines.push('Bien '+row.id_bien+' • '+row.tipo);
          } else if (row.id_bien != null && row.id_categoria != null) {
            lines.push('Bien '+row.id_bien+' ↔ Cat '+row.id_categoria);
          } else if (row.id != null) {
            lines.push('#'+row.id);
          } else {
            // fallback: primera propiedad
            for (var k in row) { lines.push(k+': '+row[k]); break; }
          }
        }
        html += '<div class="text-muted small">' + esc(lines.join(' | ')) + '</div>';
      }

      html += '</td>';
      html += '</tr>';
    });

    html += '</tbody></table></div>';

    if (data.warnings && data.warnings.length) {
      html += '<div class="alert alert-info py-2 mb-0">';
      html += '<b>Notas:</b><ul class="mb-0 pl-3">';
      data.warnings.forEach(function(w){
        html += '<li>' + esc(w) + '</li>';
      });
      html += '</ul></div>';
    }

    preview.innerHTML = html;

    note.textContent = (data.mode === 'imagenes')
      ? 'Este modo no borra bienes. Solo elimina imágenes en S4 y limpia img_key en la BD.'
      : 'Se borrará SOLO lo mostrado arriba (por empresa actual).';
  }

  async function loadPreview(){
    preview.innerHTML = '<div class="text-muted">Cargando vista previa…</div>';
    try {
      var d = await j(apiUrl + '?ajax=1&action=preview&mode=' + encodeURIComponent(state.mode));
      renderPreview(d.data || {});
    } catch(e){
      errBox.textContent = e.message || 'Error';
      errBox.classList.remove('d-none');
      preview.innerHTML = '<div class="text-muted">No se pudo cargar la vista previa.</div>';
    }
  }

  menu.addEventListener('click', function(e){
    var b = e.target.closest('button[data-mode]');
    if (!b) return;
    e.preventDefault();
    setActiveMode(b.getAttribute('data-mode'));
    loadPreview();
  });

  inp.addEventListener('input', enableRunByConfirm);

  btnRun.addEventListener('click', async function(){
    errBox.classList.add('d-none'); errBox.textContent = '';
    okBox.classList.add('d-none'); okBox.textContent = '';

    var v = (inp.value || '').trim().toUpperCase();
    if (v !== 'REINICIAR') {
      errBox.textContent = 'Escribe REINICIAR para habilitar.';
      errBox.classList.remove('d-none');
      return;
    }

    if (!confirm('¿Seguro? Se borrarán datos del inventario (empresa actual) según el modo seleccionado.')) return;

    btnRun.disabled = true;
    btnRun.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Ejecutando...';

    try {
      var fd = new FormData();
      fd.append('ajax','1');
      fd.append('action','run');
      fd.append('mode', state.mode);
      fd.append('token', token);
      fd.append('confirm', 'REINICIAR');

      var d = await j(apiUrl, { method:'POST', body: fd });

      // si devuelve token nuevo (rotación)
      if (d.new_token) token = String(d.new_token);

      okBox.textContent = 'Listo. Reinicio ejecutado. Se recargará la lista.';
      okBox.classList.remove('d-none');

      // Recarga la página para refrescar meta/listas/estadísticas sin acoplarse a inventario.js
      setTimeout(function(){ window.location.reload(); }, 900);

    } catch(e){
      errBox.textContent = e.message || 'Error';
      errBox.classList.remove('d-none');
      btnRun.disabled = false;
      btnRun.innerHTML = '<i class="fas fa-skull-crossbones mr-1"></i> Ejecutar reinicio seleccionado';
    }
  });

  // Init
  enableRunByConfirm();
  loadPreview();

})();
</script>
