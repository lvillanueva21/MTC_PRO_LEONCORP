<?php
// modules/alerta/api.php
require_once __DIR__.'/../../includes/acl.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

acl_require_ids([1,3,4]);
verificarPermiso(['Desarrollo','Recepción','Administración']);

$u = currentUser();
$empresaId = (int)($u['empresa']['id'] ?? 0);
if ($empresaId <= 0) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Empresa no asignada']); exit; }

$mysqli = db();
$mysqli->set_charset('utf8mb4');

function jerror($code,$msg,$extra=[]){ http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr=[]){ echo json_encode(['ok'=>true]+$arr); exit; }
function norm_s($s){ return trim((string)$s); }

function compute_next_ts(array $row): ?int {
  // Calcula la próxima ocurrencia (timestamp) según tipo/intervalo/fecha_base
  $tipo = $row['tipo'] ?? 'ONCE';
  $intervalo = (int)($row['intervalo_dias'] ?? 0);
  $baseStr = $row['fecha_base'] ?? null;
  if (!$baseStr) return null;

  try { $base = new DateTimeImmutable($baseStr); }
  catch (Exception $e) { return null; }

  $now  = new DateTimeImmutable('now');
  if ($tipo === 'ONCE') {
    return ($base >= $now) ? $base->getTimestamp() : null;
  }
  if ($tipo === 'MONTHLY') {
    $next = $base;
    while ($next < $now) { $next = $next->add(new DateInterval('P1M')); }
    return $next->getTimestamp();
  }
  if ($tipo === 'YEARLY') {
    $next = $base;
    while ($next < $now) { $next = $next->add(new DateInterval('P1Y')); }
    return $next->getTimestamp();
  }
  if ($tipo === 'INTERVAL') {
    if ($intervalo <= 0) return null;
    $baseTs = $base->getTimestamp();
    $nowTs  = $now->getTimestamp();
    if ($nowTs <= $baseTs) return $baseTs;
    $secs = $intervalo * 86400;
    $k = (int)ceil(($nowTs - $baseTs) / $secs);
    return $baseTs + $k * $secs;
  }
  return null;
}

function hydrate_alert(array $row): array {
  $nextTs = compute_next_ts($row);
  $nowTs  = time();
  $anticip = (int)($row['anticipacion_dias'] ?? 0);
  $warnFromTs = $nextTs ? ($nextTs - $anticip*86400) : null;
  $overdue = $nextTs ? ($nowTs > $nextTs) : false;

  $row['_next_ts'] = $nextTs;
  $row['_next_iso'] = $nextTs ? date('Y-m-d H:i:s', $nextTs) : null;
  $row['_warn_from_ts'] = $warnFromTs;
  $row['_in_seconds'] = $nextTs ? max(0, $nextTs - $nowTs) : null;
  $row['_overdue'] = $overdue;
  $row['_in_window'] = ($warnFromTs !== null) ? ($nowTs >= $warnFromTs) : false;
  return $row;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
  switch ($action) {

    case 'meta': {
      jok(['data'=>[
        'tipos'=>[
          ['value'=>'ONCE','label'=>'Una sola vez'],
          ['value'=>'MONTHLY','label'=>'Mensual'],
          ['value'=>'YEARLY','label'=>'Anual'],
          ['value'=>'INTERVAL','label'=>'Cada N días'],
        ],
      ]]);
    }

    case 'list': {
      $q      = norm_s($_GET['q'] ?? '');
      $estado = $_GET['estado'] ?? '';
      $tipo   = norm_s($_GET['tipo'] ?? '');
      $page   = max(1, (int)($_GET['page'] ?? 1));
      $per    = max(1, min(50, (int)($_GET['per'] ?? 10)));

      // Traemos todos los candidatos (el orden lo haremos en PHP según próxima fecha)
      $W = ["id_empresa = ?"];
      $types='i'; $pars = [$empresaId];

      if ($estado==='0' || $estado==='1') { $W[]="activo=?"; $types.='i'; $pars[]=(int)$estado; }
      if ($tipo !== '') { $W[]="tipo=?"; $types.='s'; $pars[]=$tipo; }
      if ($q!=='') {
        $W[]="(titulo LIKE ? OR categoria LIKE ? OR descripcion LIKE ?)";
        $types.='sss'; $pars[]="%$q%"; $pars[]="%$q%"; $pars[]="%$q%";
      }
      $where = 'WHERE '.implode(' AND ',$W);

      $sql = "SELECT * FROM al_alertas $where";
      $st = $mysqli->prepare($sql);
      $st->bind_param($types, ...$pars);
      $st->execute();
      $all = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      // Enriquecer con próxima fecha y ordenar por cercanía (nulos al final)
      $rows = array_map('hydrate_alert', $all);
      usort($rows, function($a,$b){
        $ta = $a['_next_ts']; $tb = $b['_next_ts'];
        if ($ta === $tb) return 0;
        if ($ta === null) return 1;
        if ($tb === null) return -1;
        return $ta <=> $tb;
      });

      $total = count($rows);
      $slice = array_slice($rows, ($page-1)*$per, $per);

      jok(['data'=>$slice, 'total'=>$total, 'page'=>$page, 'per'=>$per]);
    }

    case 'get': {
      $id = (int)($_GET['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $st = $mysqli->prepare("SELECT * FROM al_alertas WHERE id=? AND id_empresa=? LIMIT 1");
      $st->bind_param('ii', $id, $empresaId);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      if (!$row) jerror(404,'No encontrado');
      jok(['data'=>hydrate_alert($row)]);
    }

    case 'save': {
      $id = (int)($_POST['id'] ?? 0);

      $titulo  = norm_s($_POST['titulo'] ?? '');
      $categoria = norm_s($_POST['categoria'] ?? '');
      $descripcion = norm_s($_POST['descripcion'] ?? '');
      $tipo    = norm_s($_POST['tipo'] ?? 'ONCE');
      $intervalo = (int)($_POST['intervalo_dias'] ?? 0);
      $fecha_in = norm_s($_POST['fecha_base'] ?? '');
      $anticip = (int)($_POST['anticipacion_dias'] ?? 0);
      $activo  = isset($_POST['activo']) ? 1 : 0;

      if ($titulo==='') jerror(422,'El título es requerido');
      if (!in_array($tipo, ['ONCE','MONTHLY','YEARLY','INTERVAL'], true)) jerror(422,'Tipo inválido');
      if ($tipo==='INTERVAL' && $intervalo<=0) jerror(422,'Intervalo de días inválido');

      // Normalizar datetime-local (HTML5)
      $fecha_base = null;
      if ($fecha_in!=='') {
        // Reemplazar 'T' por espacio si viene de input type=datetime-local
        $fecha_in = str_replace('T',' ',$fecha_in);
        $ts = strtotime($fecha_in);
        if ($ts === false) jerror(422,'Fecha base inválida');
        $fecha_base = date('Y-m-d H:i:s', $ts);
      } else {
        jerror(422,'Fecha base requerida');
      }

      if ($id > 0) {
        $chk = $mysqli->prepare("SELECT id FROM al_alertas WHERE id=? AND id_empresa=?");
        $chk->bind_param('ii', $id, $empresaId);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) jerror(404,'No encontrado');

        $sql = "UPDATE al_alertas SET titulo=?, categoria=?, descripcion=?, tipo=?, intervalo_dias=?, fecha_base=?, anticipacion_dias=?, activo=? WHERE id=? AND id_empresa=? LIMIT 1";
        $st = $mysqli->prepare($sql);
        $st->bind_param('ssssissiii', $titulo,$categoria,$descripcion,$tipo,$intervalo,$fecha_base,$anticip,$activo,$id,$empresaId);
        $st->execute();

        // log
        @$mysqli->query("INSERT INTO al_alertas_log(id_alerta,evento,detalle) VALUES ($id,'UPDATED',NULL)");

        jok(['id'=>$id]);
      } else {
        $sql = "INSERT INTO al_alertas (id_empresa,titulo,categoria,descripcion,tipo,intervalo_dias,fecha_base,anticipacion_dias,activo)
                VALUES (?,?,?,?,?,?,?,?,?)";
        $st = $mysqli->prepare($sql);
        $st->bind_param('issssissi', $empresaId,$titulo,$categoria,$descripcion,$tipo,$intervalo,$fecha_base,$anticip,$activo);
        $st->execute();
        $newId = (int)$mysqli->insert_id;

        @$mysqli->query("INSERT INTO al_alertas_log(id_alerta,evento,detalle) VALUES ($newId,'CREATED',NULL)");

        jok(['id'=>$newId]);
      }
    }

    case 'toggle': {
      $id = (int)($_POST['id'] ?? 0);
      $nuevo = (int)($_POST['activo'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $st = $mysqli->prepare("UPDATE al_alertas SET activo=? WHERE id=? AND id_empresa=? LIMIT 1");
      $st->bind_param('iii', $nuevo,$id,$empresaId);
      $st->execute();
      @$mysqli->query("INSERT INTO al_alertas_log(id_alerta,evento,detalle) VALUES ($id,'TOGGLED',NULL)");
      jok(['id'=>$id,'activo'=>$nuevo]);
    }

    default:
      jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  jerror(500,'Error de servidor', ['dev'=>$e->getMessage()]);
}
