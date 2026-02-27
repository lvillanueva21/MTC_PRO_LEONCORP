<?php
// modules/consola/cajas/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
@require_once __DIR__ . '/../../../includes/acl.php';
@require_once __DIR__ . '/../../../includes/permisos.php';

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = db();
$db->set_charset('utf8mb4');

/* === Hora Lima (backend) === */
if (function_exists('date_default_timezone_set')) {
  date_default_timezone_set('America/Lima');
}
try { $db->query("SET time_zone = 'America/Lima'"); }
catch (mysqli_sql_exception $e) { $db->query("SET time_zone = '-05:00'"); }

// ==== helpers ====
function jerror($code,$msg,$extra=[]){ http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr=[]){ echo json_encode(['ok'=>true]+$arr); exit; }

function client_ip(): ?string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $cand  = trim($parts[0] ?? '');
    if (filter_var($cand, FILTER_VALIDATE_IP)) $ip = $cand;
  }
  elseif (!empty($_SERVER['HTTP_X_REAL_IP']) && filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) $ip = $_SERVER['HTTP_X_REAL_IP'];
  if (!$ip) return null;
  if (strpos($ip, ':') !== false) {
    $packed = @inet_pton($ip);
    if ($packed !== false && strlen($packed) === 16) {
      $is_v4_mapped = (substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff");
      if ($is_v4_mapped) {
        $v4 = @inet_ntop(substr($packed, 12));
        if ($v4 && filter_var($v4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $v4;
      }
    }
  }
  return $ip;
}

function actor(){
  if (function_exists('acl_require_ids')) { acl_require_ids([1,6]); }
  if (function_exists('verificarPermiso')) { verificarPermiso(['Desarrollo','Gerente']); }
  $u = function_exists('currentUser') ? currentUser() : null;
  if (!$u || !isset($u['id'])) jerror(401,'No autenticado');

  return [
    'id'      => (int)$u['id'],
    'usuario' => substr((string)($u['usuario']??''),0,64),
    'nombre'  => substr(trim(($u['nombres']??'').' '.($u['apellidos']??'')),0,150),
    'ip'      => client_ip()
  ];
}

function audit(mysqli $db, $act, $id_emp, $id_cm=null, $id_cd=null, $detalle=null){
  $a = actor();
  $st = $db->prepare("INSERT INTO mod_caja_auditoria
    (id_empresa,id_caja_mensual,id_caja_diaria,evento,detalle,actor_id,actor_usuario,actor_nombre,ip)
    VALUES (?,?,?,?,?,?,?,?,?)");
  $st->bind_param('iiississs', $id_emp, $id_cm, $id_cd, $act, $detalle, $a['id'], $a['usuario'], $a['nombre'], $a['ip']);
  $st->execute();
}

function audit_counted(mysqli $db, $act, $id_emp, $id_cm=null, $id_cd=null, $extra=null){
  // Etiqueta numerada bonita: Apertura/Cierre/Eliminación
  $label = (strpos($act,'abrir_')===0)
            ? 'Apertura'
            : ((strpos($act,'cerrar_')===0)
                ? 'Cierre'
                : ((strpos($act,'eliminar_')===0) ? 'Eliminación' : $act));

  $cnt = 0;
  if ($id_cm) {
    $stc = $db->prepare("SELECT COUNT(*) c FROM mod_caja_auditoria WHERE id_caja_mensual=? AND evento=?");
    $stc->bind_param('is', $id_cm, $act);
    $stc->execute();
    $cnt = (int)($stc->get_result()->fetch_assoc()['c'] ?? 0);
  } elseif ($id_cd) {
    $stc = $db->prepare("SELECT COUNT(*) c FROM mod_caja_auditoria WHERE id_caja_diaria=? AND evento=?");
    $stc->bind_param('is', $id_cd, $act);
    $stc->execute();
    $cnt = (int)($stc->get_result()->fetch_assoc()['c'] ?? 0);
  }

  $detalle = sprintf('%s # %02d', $label, $cnt + 1);
  if ($extra) $detalle .= ' | '.$extra;

  audit($db, $act, $id_emp, $id_cm, $id_cd, $detalle);
}

function gen_codigo_mensual($emp,$anio,$mes){ return sprintf('CM-%04d%02d-E%s', $anio, $mes, $emp); }
function gen_codigo_diaria($emp,$fecha){ return 'CD-'.str_replace('-','',$fecha).'-E'.$emp; }

function mensual_abierta(mysqli $db, int $emp){
  $st = $db->prepare("SELECT * FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' LIMIT 1");
  $st->bind_param('i',$emp); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}
function diaria_abierta(mysqli $db, int $emp){
  $st = $db->prepare("SELECT * FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' LIMIT 1");
  $st->bind_param('i',$emp); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}

try{
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action){

    /* ---------- combos/util ---------- */
    case 'empresas_combo': {
      $rs = $db->query("SELECT id,nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'ahora': {
      $r = $db->query("SELECT NOW() AS ahora, DATE(NOW()) AS hoy, DATE_FORMAT(NOW(),'%Y-%m') AS mes_actual, YEAR(NOW()) AS anio_actual");
      $row = $r->fetch_assoc();
      jok([
        'ahora'       => $row['ahora'],
        'hoy'         => $row['hoy'],
        'mes_actual'  => $row['mes_actual'],
        'anio_actual' => (int)$row['anio_actual'],
      ]);
    }

    /* ---------- resumen cabecera (mockup sección 1) ---------- */
    case 'resumen_empresa': {
      $emp = (int)($_GET['empresa_id'] ?? 0);
      if ($emp<=0) jerror(400,'Empresa requerida');

      // Empresa
      $stE = $db->prepare("SELECT nombre FROM mtp_empresas WHERE id=?");
      $stE->bind_param('i',$emp); $stE->execute();
      $empresa = $stE->get_result()->fetch_assoc()['nombre'] ?? '';

      // Abiertas
      $stM = $db->prepare("SELECT id, anio, mes, codigo FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' LIMIT 1");
      $stM->bind_param('i',$emp); $stM->execute();
      $mens = $stM->get_result()->fetch_assoc() ?: null;

      $stD = $db->prepare("SELECT id, fecha, codigo FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' LIMIT 1");
      $stD->bind_param('i',$emp); $stD->execute();
      $dia = $stD->get_result()->fetch_assoc() ?: null;

      // Totales (año/mes corrientes de Lima)
      $totM = $db->prepare("SELECT COUNT(*) c FROM mod_caja_mensual WHERE id_empresa=? AND anio=YEAR(CURDATE())");
      $totM->bind_param('i',$emp); $totM->execute(); $mens_y = (int)$totM->get_result()->fetch_assoc()['c'];

      $totD = $db->prepare("SELECT COUNT(*) c
                            FROM mod_caja_diaria
                            WHERE id_empresa=? AND YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE())");
      $totD->bind_param('i',$emp); $totD->execute(); $dias_m = (int)$totD->get_result()->fetch_assoc()['c'];

      jok([
        'empresa' => $empresa,
        'mensual_actual' => $mens ? [
          'id'=>(int)$mens['id'],
          'anio'=>(int)$mens['anio'],
          'mes'=>(int)$mens['mes'],
          'codigo'=>$mens['codigo']
        ] : null,
        'diaria_actual' => $dia ? [
          'id'=>(int)$dia['id'],
          'fecha'=>$dia['fecha'],
          'codigo'=>$dia['codigo']
        ] : null,
        'tot_mensuales_anio' => $mens_y,
        'tot_diarias_mes'    => $dias_m
      ]);
    }

    /* ---------- listados con filtros (mockup secciones 2 y 3) ---------- */
    case 'list_mensuales': {
      $empresa = (int)($_GET['empresa_id'] ?? 0);
      $estado  = $_GET['estado'] ?? ''; // '', 'abierta','cerrada'
      $mini    = trim($_GET['mes_ini'] ?? ''); // 'YYYY-MM'
      $mfin    = trim($_GET['mes_fin'] ?? ''); // 'YYYY-MM'
      $page    = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50,(int)($_GET['per_page'] ?? 10)));
      $off     = ($page-1)*$perPage;

      $where=[]; $types=''; $pars=[];
      if ($empresa>0){ $where[]="m.id_empresa=?"; $types.='i'; $pars[]=$empresa; }
      if ($estado==='abierta' || $estado==='cerrada'){ $where[]="m.estado=?"; $types.='s'; $pars[]=$estado; }

      // Si existe columna generada periodo, úsala; si no, usa anio/mes (ambos funcionan)
      if ($mini!=='' && preg_match('/^\d{4}-\d{2}$/',$mini)){
        $where[]="(m.anio*100 + m.mes) >= ?"; $types.='i'; $pars[]=(int)(substr($mini,0,4).substr($mini,5,2));
      }
      if ($mfin!=='' && preg_match('/^\d{4}-\d{2}$/',$mfin)){
        $where[]="(m.anio*100 + m.mes) <= ?"; $types.='i'; $pars[]=(int)(substr($mfin,0,4).substr($mfin,5,2));
      }
      $W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

      $stC = $db->prepare("SELECT COUNT(*) c FROM mod_caja_mensual m $W");
      if ($types) $stC->bind_param($types, ...$pars);
      $stC->execute(); $total = (int)$stC->get_result()->fetch_assoc()['c'];

      $sql = "SELECT m.id, m.id_empresa, e.nombre empresa, m.anio, m.mes, m.codigo, m.estado, m.abierto_en, m.cerrado_en
              FROM mod_caja_mensual m
              JOIN mtp_empresas e ON e.id = m.id_empresa
              $W
              ORDER BY m.anio DESC, m.mes DESC, m.id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii'; $pars2 = $pars; $pars2[]=$perPage; $pars2[]=$off;
      $st = $db->prepare($sql); $st->bind_param($types2, ...$pars2);
      $st->execute(); $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
    }

    case 'list_diarias': {
      $empresa = (int)($_GET['empresa_id'] ?? 0);
      $estado  = $_GET['estado'] ?? ''; // '', 'abierta','cerrada'
      $fini    = trim($_GET['fecha_ini'] ?? ''); // 'YYYY-MM-DD'
      $ffin    = trim($_GET['fecha_fin'] ?? ''); // 'YYYY-MM-DD'
      $page    = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50,(int)($_GET['per_page'] ?? 10)));
      $off     = ($page-1)*$perPage;

      $where=[]; $types=''; $pars=[];
      if ($empresa>0){ $where[]="d.id_empresa=?"; $types.='i'; $pars[]=$empresa; }
      if ($estado==='abierta' || $estado==='cerrada'){ $where[]="d.estado=?"; $types.='s'; $pars[]=$estado; }
      if ($fini!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$fini)){ $where[]="d.fecha>=?"; $types.='s'; $pars[]=$fini; }
      if ($ffin!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$ffin)){ $where[]="d.fecha<=?"; $types.='s'; $pars[]=$ffin; }
      $W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

      $stC = $db->prepare("SELECT COUNT(*) c FROM mod_caja_diaria d $W");
      if ($types) $stC->bind_param($types, ...$pars);
      $stC->execute(); $total = (int)$stC->get_result()->fetch_assoc()['c'];

      $sql = "SELECT d.id, d.id_empresa, e.nombre empresa, d.id_caja_mensual, d.fecha, d.codigo, d.estado, d.abierto_en, d.cerrado_en
              FROM mod_caja_diaria d
              JOIN mtp_empresas e ON e.id = d.id_empresa
              $W
              ORDER BY d.fecha DESC, d.id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii'; $pars2 = $pars; $pars2[]=$perPage; $pars2[]=$off;
      $st = $db->prepare($sql); $st->bind_param($types2, ...$pars2);
      $st->execute(); $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
    }

    /* ---------- crear (nuevas acciones) ---------- */
    case 'crear_mensual': {
  // Apertura rápida de MENSUAL
  // Reglas:
  // - Si ya hay una mensual ABIERTA de OTRO período => 409 (pide cerrar primero).
  // - Si ya existe la del mismo período => reabrir idempotente.
  $a    = actor();
  $emp  = (int)($_POST['empresa_id'] ?? 0);
  $anio = (int)($_POST['anio'] ?? 0);
  $mes  = (int)($_POST['mes'] ?? 0);
  if ($emp<=0 || $anio<=0 || $mes<=0) jerror(400,'Datos inválidos');

  $db->begin_transaction();
  try{
    // Lock de la mensual actualmente abierta (si la hay)
    $lk = $db->prepare("SELECT id, anio, mes, codigo FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
    $lk->bind_param('i',$emp); $lk->execute();
    $mOpen = $lk->get_result()->fetch_assoc();

    if ($mOpen && ((int)$mOpen['anio'] !== $anio || (int)$mOpen['mes'] !== $mes)) {
      // Hay otra mensual abierta: no abrimos automáticamente en 'crear_*'
      $db->rollback();
      jerror(409, 'Ya existe una caja mensual abierta ('.$mOpen['codigo'].'). Ciérrela primero o use "Abrir" en la lista para cambiar de período.');
    }

    // Upsert/Idempotente del período solicitado
    $q1 = $db->prepare("SELECT id, codigo FROM mod_caja_mensual WHERE id_empresa=? AND anio=? AND mes=? LIMIT 1 FOR UPDATE");
    $q1->bind_param('iii',$emp,$anio,$mes); $q1->execute();
    $row = $q1->get_result()->fetch_assoc();

    if ($row){
      $cm_id = (int)$row['id'];
      $up = $db->prepare("UPDATE mod_caja_mensual
                          SET estado='abierta', abierto_por=?, abierto_en=NOW(),
                              cerrado_por=NULL, cerrado_en=NULL
                          WHERE id=?");
      $up->bind_param('ii',$a['id'],$cm_id); $up->execute();
    } else {
      $cod = gen_codigo_mensual($emp,$anio,$mes);
      $ins = $db->prepare("INSERT INTO mod_caja_mensual (id_empresa, anio, mes, codigo, estado, abierto_por)
                           VALUES (?,?,?,?, 'abierta', ?)");
      $ins->bind_param('iiisi',$emp,$anio,$mes,$cod,$a['id']); $ins->execute();
      $cm_id = (int)$db->insert_id;
    }

    audit_counted($db,'abrir_mensual',$emp,$cm_id,null,sprintf('%04d-%02d',$anio,$mes));
    $db->commit();
    jok(['id'=>$cm_id]);
  }catch(Throwable $e){
    $db->rollback();
    jerror(500,'No se pudo crear/abrir mensual',['dev'=>$e->getMessage()]);
  }
}

case 'crear_diaria': {
  // Apertura rápida de DIARIA
  // Reglas:
  // - Requiere mensual del período ABIERTA.
  // - Si ya hay una diaria ABIERTA de OTRO día => 409 (pide cerrar primero).
  // - Si ya existe la misma fecha => reabrir idempotente.
  $a     = actor();
  $emp   = (int)($_POST['empresa_id'] ?? 0);
  $fecha = trim($_POST['fecha'] ?? '');
  if ($emp<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)) jerror(400,'Datos inválidos');

  $anio = (int)substr($fecha,0,4);
  $mes  = (int)substr($fecha,5,2);

  $db->begin_transaction();
  try{
    // Mensual del período (ABIERTA)
    $m = $db->prepare("SELECT id, anio, mes FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
    $m->bind_param('i',$emp); $m->execute();
    $mOpen = $m->get_result()->fetch_assoc();
    if (!$mOpen || (int)$mOpen['anio'] !== $anio || (int)$mOpen['mes'] !== $mes) {
      $db->rollback();
      jerror(400,'La mensual del período no está abierta');
    }
    $id_cm = (int)$mOpen['id'];

    // ¿Hay otra diaria abierta de otro día?
    $d = $db->prepare("SELECT id, fecha, codigo FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
    $d->bind_param('i',$emp); $d->execute();
    $dOpen = $d->get_result()->fetch_assoc();
    if ($dOpen && $dOpen['fecha'] !== $fecha) {
      $db->rollback();
      jerror(409, 'Ya existe una caja diaria abierta ('.$dOpen['codigo'].'). Ciérrela primero o use "Abrir" en la lista para cambiar de día.');
    }

    // Idempotente por fecha
    $qd = $db->prepare("SELECT id FROM mod_caja_diaria WHERE id_empresa=? AND fecha=? LIMIT 1 FOR UPDATE");
    $qd->bind_param('is',$emp,$fecha); $qd->execute();
    $row = $qd->get_result()->fetch_assoc();

    if ($row){
      $cd_id = (int)$row['id'];
      $up = $db->prepare("UPDATE mod_caja_diaria
                          SET estado='abierta', id_caja_mensual=?, abierto_por=?, abierto_en=NOW(),
                              cerrado_por=NULL, cerrado_en=NULL
                          WHERE id=?");
      $up->bind_param('iii',$id_cm,$a['id'],$cd_id); $up->execute();
    } else {
      $cod = gen_codigo_diaria($emp,$fecha);
      $ins = $db->prepare("INSERT INTO mod_caja_diaria (id_empresa,id_caja_mensual,fecha,codigo,estado,abierto_por)
                           VALUES (?,?,?,?, 'abierta', ?)");
      $ins->bind_param('iissi',$emp,$id_cm,$fecha,$cod,$a['id']); $ins->execute();
      $cd_id = (int)$db->insert_id;
    }

    audit_counted($db,'abrir_diaria',$emp,$id_cm,$cd_id,$fecha);
    $db->commit();
    jok(['id'=>$cd_id]);
  }catch(Throwable $e){
    $db->rollback();
    jerror(500,'No se pudo crear/abrir diaria',['dev'=>$e->getMessage()]);
  }
}

    /* ---------- acciones caja ---------- */
    case 'abrir_mensual': {
  // Acción desde la lista: abre el período indicado
  // y cierra la mensual/diaria previamente abiertas.
  $a    = actor();
  $emp  = (int)($_POST['empresa_id'] ?? 0);
  $anio = (int)($_POST['anio'] ?? 0);
  $mes  = (int)($_POST['mes'] ?? 0);
  if ($emp<=0 || $anio<=0 || $mes<=0) jerror(400,'Datos inválidos');

  $db->begin_transaction();
  try{
    // Lock de la mensual abierta actual (si la hay)
    $m = $db->prepare("SELECT id FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
    $m->bind_param('i',$emp); $m->execute();
    $prevM = $m->get_result()->fetch_assoc();

    // Lock de la diaria abierta actual (si la hay)
    $d = $db->prepare("SELECT id, id_caja_mensual FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
    $d->bind_param('i',$emp); $d->execute();
    $prevD = $d->get_result()->fetch_assoc();

    // Cierres (si existían)
    if ($prevM) {
      if ($prevD) {
        $u = $db->prepare("UPDATE mod_caja_diaria SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=?");
        $u->bind_param('ii',$a['id'],$prevD['id']); $u->execute();
        audit_counted($db,'cerrar_diaria',$emp,(int)$prevM['id'],(int)$prevD['id'],'Cierre por apertura de otra mensual');
      }
      $st = $db->prepare("UPDATE mod_caja_mensual SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=?");
      $st->bind_param('ii',$a['id'],$prevM['id']); $st->execute();
      audit_counted($db,'cerrar_mensual',$emp,(int)$prevM['id'],null,'Cierre por apertura de otra mensual');
    }

    // Upsert del período solicitado
    $st = $db->prepare("SELECT id FROM mod_caja_mensual WHERE id_empresa=? AND anio=? AND mes=? LIMIT 1 FOR UPDATE");
    $st->bind_param('iii',$emp,$anio,$mes); $st->execute();
    $row = $st->get_result()->fetch_assoc();

    if ($row){
      $cm_id = (int)$row['id'];
      $up = $db->prepare("UPDATE mod_caja_mensual
                          SET estado='abierta', abierto_por=?, abierto_en=NOW(),
                              cerrado_por=NULL, cerrado_en=NULL
                          WHERE id=?");
      $up->bind_param('ii',$a['id'],$cm_id); $up->execute();
    } else {
      $cod = gen_codigo_mensual($emp,$anio,$mes);
      $ins = $db->prepare("INSERT INTO mod_caja_mensual (id_empresa, anio, mes, codigo, estado, abierto_por)
                           VALUES (?,?,?,?, 'abierta', ?)");
      $ins->bind_param('iiisi',$emp,$anio,$mes,$cod,$a['id']); $ins->execute();
      $cm_id = (int)$db->insert_id;
    }

    audit_counted($db,'abrir_mensual',$emp,$cm_id,null,sprintf('%04d-%02d',$anio,$mes));
    $db->commit();
    jok(['id'=>$cm_id]);
  }catch(mysqli_sql_exception $e){
    $db->rollback();
    if ((int)$e->getCode()===1062) jerror(409,'Ya existe una mensual abierta para esta empresa.');
    jerror(500,'No se pudo abrir mensual',['dev'=>$e->getMessage()]);
  }catch(Throwable $e){
    $db->rollback();
    jerror(500,'No se pudo abrir mensual',['dev'=>$e->getMessage()]);
  }
}

    case 'cerrar_mensual': {
      $a = actor();
      $emp = (int)($_POST['empresa_id'] ?? 0);
      if ($emp<=0) jerror(400,'Empresa requerida');

      $db->begin_transaction();
      try{
        $lk1 = $db->prepare("SELECT id FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
        $lk1->bind_param('i',$emp); $lk1->execute();
        $m = $lk1->get_result()->fetch_assoc();
        if (!$m) { $db->rollback(); jerror(400,'No hay mensual abierta'); }

        $lk2 = $db->prepare("SELECT id FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
        $lk2->bind_param('i',$emp); $lk2->execute();
        $d = $lk2->get_result()->fetch_assoc();

        if ($d) {
          $st = $db->prepare("UPDATE mod_caja_diaria SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=?");
          $st->bind_param('ii',$a['id'],$d['id']); $st->execute();
          audit_counted($db,'cerrar_diaria',$emp,(int)$m['id'],(int)$d['id'],'Cierre por cierre de mensual');
        }

        $st2 = $db->prepare("UPDATE mod_caja_mensual SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=?");
        $st2->bind_param('ii',$a['id'],$m['id']); $st2->execute();
        audit_counted($db,'cerrar_mensual',$emp,(int)$m['id'],null,'Cierre manual');

        $db->commit();
        jok(['id'=>$m['id']]);
      }catch(Throwable $e){
        $db->rollback(); jerror(500,'No se pudo cerrar mensual',['dev'=>$e->getMessage()]);
      }
    }

    case 'abrir_diaria': {
      $a = actor();
      $emp = (int)($_POST['empresa_id'] ?? 0);
      $fecha = trim($_POST['fecha'] ?? '');
      if ($emp<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)) jerror(400,'Datos inválidos');
      $anio = (int)substr($fecha,0,4); $mes=(int)substr($fecha,5,2);

      $db->begin_transaction();
      try{
        $lkM = $db->prepare("SELECT id, anio, mes FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
        $lkM->bind_param('i',$emp); $lkM->execute();
        $mOpen = $lkM->get_result()->fetch_assoc();
        if (!$mOpen || (int)$mOpen['anio'] !== $anio || (int)$mOpen['mes'] !== $mes) {
          $db->rollback(); jerror(400,'La mensual del período no está abierta');
        }

        $lkD = $db->prepare("SELECT id FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' FOR UPDATE");
        $lkD->bind_param('i',$emp); $lkD->execute();
        $dprev = $lkD->get_result()->fetch_assoc();

        if ($dprev){
          $u = $db->prepare("UPDATE mod_caja_diaria SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=?");
          $u->bind_param('ii',$a['id'],$dprev['id']); $u->execute();
          audit_counted($db,'cerrar_diaria',$emp,(int)$mOpen['id'],(int)$dprev['id'],'Cierre por apertura de otra diaria');
        }

        $q = $db->prepare("SELECT id FROM mod_caja_diaria WHERE id_empresa=? AND fecha=? LIMIT 1 FOR UPDATE");
        $q->bind_param('is',$emp,$fecha); $q->execute();
        $row = $q->get_result()->fetch_assoc();

        if ($row){
          $id = (int)$row['id'];
          $up = $db->prepare("UPDATE mod_caja_diaria
                              SET estado='abierta', id_caja_mensual=?, abierto_por=?, abierto_en=NOW(),
                                  cerrado_por=NULL, cerrado_en=NULL
                              WHERE id=?");
          $up->bind_param('iii',$mOpen['id'],$a['id'],$id); $up->execute();
          $cd_id = $id;
        } else {
          $cod = gen_codigo_diaria($emp,$fecha);
          $ins = $db->prepare("INSERT INTO mod_caja_diaria (id_empresa,id_caja_mensual,fecha,codigo,estado,abierto_por)
                               VALUES (?,?,?,?, 'abierta', ?)");
          $ins->bind_param('iissi',$emp,$mOpen['id'],$fecha,$cod,$a['id']); $ins->execute();
          $cd_id = (int)$db->insert_id;
        }

        audit_counted($db,'abrir_diaria',$emp,(int)$mOpen['id'],$cd_id,$fecha);
        $db->commit();
        jok(['id'=>$cd_id]);
      }catch(mysqli_sql_exception $e){
        $db->rollback();
        if ((int)$e->getCode()===1062) jerror(409,'Ya existe una diaria abierta para esta empresa.');
        jerror(500,'No se pudo abrir diaria',['dev'=>$e->getMessage()]);
      }catch(Throwable $e){
        $db->rollback(); jerror(500,'No se pudo abrir diaria',['dev'=>$e->getMessage()]);
      }
    }

    case 'cerrar_diaria': {
      $a = actor();
      $emp = (int)($_POST['empresa_id'] ?? 0);
      if ($emp<=0) jerror(400,'Empresa requerida');

      $db->begin_transaction();
      try{
        $d = diaria_abierta($db,$emp);
        if (!$d) { $db->rollback(); jerror(400,'No hay diaria abierta'); }

        $st = $db->prepare("UPDATE mod_caja_diaria SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=?");
        $st->bind_param('ii',$a['id'],$d['id']); $st->execute();

        audit_counted($db,'cerrar_diaria',$emp,(int)$d['id_caja_mensual'],$d['id'],'Cierre manual');
        $db->commit();
        jok(['id'=>$d['id']]);
      }catch(Throwable $e){ $db->rollback(); jerror(500,'No se pudo cerrar diaria',['dev'=>$e->getMessage()]); }
    }

    /* ---------- eliminar (privilegio modal) ---------- */
    case 'eliminar_mensual': {
  $a = actor();
  $id   = (int)($_POST['id'] ?? 0);
  $emp  = (int)($_POST['empresa_id'] ?? 0);
  $anio = (int)($_POST['anio'] ?? 0);
  $mes  = (int)($_POST['mes'] ?? 0);

  if ($id<=0 && !($emp>0 && $anio>0 && $mes>0)) jerror(400,'Parámetros inválidos');

  $db->begin_transaction();
  try{
    if ($id<=0){
      $s = $db->prepare("SELECT id,codigo,anio,mes FROM mod_caja_mensual WHERE id_empresa=? AND anio=? AND mes=? LIMIT 1");
      $s->bind_param('iii',$emp,$anio,$mes); $s->execute();
      $row = $s->get_result()->fetch_assoc();
      if (!$row) { $db->rollback(); jerror(404,'Mensual no encontrada'); }
      $id  = (int)$row['id'];  $cod = $row['codigo'];
      $anio= (int)$row['anio']; $mes = (int)$row['mes'];
    } else {
      $s = $db->prepare("SELECT id_empresa,codigo,anio,mes FROM mod_caja_mensual WHERE id=? LIMIT 1");
      $s->bind_param('i',$id); $s->execute();
      $row = $s->get_result()->fetch_assoc();
      if (!$row) { $db->rollback(); jerror(404,'Mensual no encontrada'); }
      $emp = (int)$row['id_empresa']; $cod=$row['codigo'];
      $anio= (int)$row['anio'];       $mes=(int)$row['mes'];
    }

    $periodo = sprintf('%04d-%02d', $anio, $mes);
    audit_counted($db,'eliminar_mensual',$emp,$id,null,"periodo=$periodo | codigo=$cod");

    $del = $db->prepare("DELETE FROM mod_caja_mensual WHERE id=?");
    $del->bind_param('i',$id); $del->execute();

    $db->commit();
    jok(['id'=>$id]);
  }catch(mysqli_sql_exception $e){
    $db->rollback();
    if ((int)$e->getCode()===1451) jerror(409,'No se pudo eliminar mensual: existen registros relacionados.');
    jerror(500,'No se pudo eliminar mensual',['dev'=>$e->getMessage()]);
  }
}

    case 'eliminar_diaria': {
  $a = actor();
  $id    = (int)($_POST['id'] ?? 0);
  $emp   = (int)($_POST['empresa_id'] ?? 0);
  $fecha = trim($_POST['fecha'] ?? '');

  if ($id<=0 && !($emp>0 && preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha))) jerror(400,'Parámetros inválidos');

  $db->begin_transaction();
  try{
    if ($id<=0){
      $s = $db->prepare("SELECT id,codigo,fecha FROM mod_caja_diaria WHERE id_empresa=? AND fecha=? LIMIT 1");
      $s->bind_param('is',$emp,$fecha); $s->execute();
      $row = $s->get_result()->fetch_assoc();
      if (!$row) { $db->rollback(); jerror(404,'Diaria no encontrada'); }
      $id = (int)$row['id']; $cod=$row['codigo']; $fecha=$row['fecha'];
    } else {
      $s = $db->prepare("SELECT id_empresa,codigo,fecha FROM mod_caja_diaria WHERE id=? LIMIT 1");
      $s->bind_param('i',$id); $s->execute();
      $row = $s->get_result()->fetch_assoc();
      if (!$row) { $db->rollback(); jerror(404,'Diaria no encontrada'); }
      $emp = (int)$row['id_empresa']; $cod=$row['codigo']; $fecha=$row['fecha'];
    }

    audit_counted($db,'eliminar_diaria',$emp,null,$id,"fecha=$fecha | codigo=$cod");

    $del = $db->prepare("DELETE FROM mod_caja_diaria WHERE id=?");
    $del->bind_param('i',$id); $del->execute();

    $db->commit();
    jok(['id'=>$id]);
  }catch(mysqli_sql_exception $e){
    $db->rollback();
    if ((int)$e->getCode()===1451) jerror(409,'No se pudo eliminar diaria: existen registros relacionados.');
    jerror(500,'No se pudo eliminar diaria',['dev'=>$e->getMessage()]);
  }
}

    default: jerror(400,'Acción no válida');
  }

}catch(mysqli_sql_exception $e){
  if ($db->errno) { @ $db->rollback(); }
  if ((int)$e->getCode() === 1062) jerror(409,'Conflicto de unicidad (posible caja abierta duplicada).');
  jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
}
