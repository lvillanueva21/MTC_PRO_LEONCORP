<?php
// /modules/caja/api.php
// POLÍTICA: Recepción/Administración NUNCA reabre cajas (ni mensual ni diaria).
// Esta versión incluye validaciones adicionales de servidor (servicios válidos por empresa,
// medios de pago válidos y referencias obligatorias, manejo de sobrepago con "devuelto",
// y saneo/seguridad general).

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([3,4]); // Recepción (3) o Administración (4)
verificarPermiso(['Recepción','Administración']);

/* ===== Config MySQLi estricto + TZ Lima ===== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');
if (function_exists('date_default_timezone_set')) { date_default_timezone_set('America/Lima'); }
try { $db->query("SET time_zone = 'America/Lima'"); }
catch (Throwable $e) { $db->query("SET time_zone = '-05:00'"); }

/* ===== Helpers JSON ===== */
function json_ok($data=[]){ echo json_encode(['ok'=>true]+$data, JSON_UNESCAPED_UNICODE); exit; }
function json_err_code($code, $msg, $extra=[]){
  http_response_code($code);
  echo json_encode(['ok'=>false,'error'=>$msg]+$extra, JSON_UNESCAPED_UNICODE); exit;
}
function json_err($msg,$extra=[]){ json_err_code(400,$msg,$extra); }
function like_wrap($s){ $s = trim((string)$s); return "%{$s}%"; }
function money2($n){ return round((float)$n, 2); }

/* ===== Usuario y empresa ===== */
$u       = currentUser();
$uid     = (int)($u['id'] ?? 0);
$empId   = (int)($u['empresa']['id'] ?? 0);
if ($empId <= 0) json_err('Empresa no asignada.');

/* ===== Tiempo actual (Lima) ===== */
$Y = (int)$db->query("SELECT YEAR(CURDATE()) y")->fetch_assoc()['y'];
$m = (int)$db->query("SELECT MONTH(CURDATE()) m")->fetch_assoc()['m'];
$d = (int)$db->query("SELECT DAY(CURDATE()) d")->fetch_assoc()['d'];
$today = $db->query("SELECT CURDATE() t")->fetch_assoc()['t'];

/* =========================
 * Utilidades CAJA
 * ========================= */
function codigoMensual($empId,$Y,$m){ return "CM-{$empId}-".sprintf('%04d%02d',$Y,$m); }
function codigoDiaria($empId,$Y,$m,$d){ return "CD-{$empId}-".sprintf('%04d%02d%02d',$Y,$m,$d); }
function shortMY($Y,$m){ return sprintf('%02d-%02d',$m,$Y%100); }
function shortDMY($Y,$m,$d){ return sprintf('%02d-%02d-%02d',$d,$m,$Y%100); }

function getMensualPeriodo(mysqli $db, int $empId, int $Y, int $m){
  $st = $db->prepare("SELECT * FROM mod_caja_mensual WHERE id_empresa=? AND anio=? AND mes=? LIMIT 1");
  $st->bind_param('iii',$empId,$Y,$m); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}
function getMensualAbierta(mysqli $db, int $empId){
  $st = $db->prepare("SELECT * FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' ORDER BY anio ASC, mes ASC, id ASC LIMIT 1");
  $st->bind_param('i',$empId); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}
function getDiariaFecha(mysqli $db, int $empId, string $fecha){
  $st = $db->prepare("SELECT * FROM mod_caja_diaria WHERE id_empresa=? AND fecha=? LIMIT 1");
  $st->bind_param('is',$empId,$fecha); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}
function getDiariaAbierta(mysqli $db, int $empId){
  $st = $db->prepare("SELECT * FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' ORDER BY fecha ASC, id ASC LIMIT 1");
  $st->bind_param('i',$empId); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}
function hayDiariasAbiertasMensual(mysqli $db, int $idMensual){
  $st = $db->prepare("SELECT COUNT(*) c FROM mod_caja_diaria WHERE id_caja_mensual=? AND estado='abierta'");
  $st->bind_param('i',$idMensual); $st->execute();
  return (int)$st->get_result()->fetch_assoc()['c'] > 0;
}
function cajaTextoLimite($texto, $max=255){
  $texto = trim((string)$texto);
  if ($texto === '') return null;

  $max = max(4, (int)$max);
  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($texto, 'UTF-8') <= $max) return $texto;
    return rtrim(mb_substr($texto, 0, $max - 3, 'UTF-8')).'...';
  }

  if (strlen($texto) <= $max) return $texto;
  return rtrim(substr($texto, 0, $max - 3)).'...';
}
function normalizarEventoAuditoriaCaja($evento){
  $evento = trim((string)$evento);
  $map = [
    'abrir_mensual'               => 'abrir_mensual',
    'cerrar_mensual'              => 'cerrar_mensual',
    'cerrar_mensual_extemporanea' => 'cerrar_mensual',
    'abrir_diaria'                => 'abrir_diaria',
    'cerrar_diaria'               => 'cerrar_diaria',
    'cerrar_diaria_extemporanea'  => 'cerrar_diaria',
    'eliminar_mensual'            => 'eliminar_mensual',
    'eliminar_diaria'             => 'eliminar_diaria'
  ];
  if (isset($map[$evento])) return $map[$evento];

  $evt = strtolower($evento);
  if (strpos($evt, 'abrir_') === 0 && strpos($evt, 'mensual') !== false) return 'abrir_mensual';
  if (strpos($evt, 'cerrar_') === 0 && strpos($evt, 'mensual') !== false) return 'cerrar_mensual';
  if (strpos($evt, 'eliminar_') === 0 && strpos($evt, 'mensual') !== false) return 'eliminar_mensual';
  if (strpos($evt, 'abrir_') === 0 && strpos($evt, 'diaria') !== false) return 'abrir_diaria';
  if (strpos($evt, 'cerrar_') === 0 && strpos($evt, 'diaria') !== false) return 'cerrar_diaria';
  if (strpos($evt, 'eliminar_') === 0 && strpos($evt, 'diaria') !== false) return 'eliminar_diaria';

  return null;
}
function logCajaLocal($scope, array $context=[]){
  static $logFile = null;
  if ($logFile === null) {
    $logFile = __DIR__ . '/caja_cierre_pendiente.log';
  }

  $payload = [
    'ts'    => date('Y-m-d H:i:s'),
    'scope' => (string)$scope,
    'ip'    => $_SERVER['REMOTE_ADDR'] ?? null
  ] + $context;

  $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($line === false) {
    $line = '['.date('Y-m-d H:i:s').'] '.$scope;
  }

  try {
    error_log($line . PHP_EOL, 3, $logFile);
  } catch (Throwable $e) {
    // El log local nunca debe romper el flujo principal.
  }
}
function logAuditoriaCaja(mysqli $db, $evento, $idEmpresa, $idMensual=null, $idDiaria=null, $detalle=null, $u=null){
  $uid = (int)($u['id'] ?? 0);
  $actorUsuario = (string)($u['usuario'] ?? '');
  $actorNombre  = trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? ''));
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $eventoOriginal = trim((string)$evento);
  $eventoSeguro   = normalizarEventoAuditoriaCaja($eventoOriginal);
  $detalleSeguro  = cajaTextoLimite($detalle, 255);

  if ($eventoSeguro === null) {
    logCajaLocal('auditoria.evento_no_soportado', [
      'empresa_id'      => $idEmpresa,
      'usuario_id'      => $uid,
      'evento_original' => $eventoOriginal,
      'id_caja_mensual' => $idMensual,
      'id_caja_diaria'  => $idDiaria
    ]);
    return false;
  }

  try {
    $st = $db->prepare("INSERT INTO mod_caja_auditoria(id_empresa,id_caja_mensual,id_caja_diaria,evento,detalle,actor_id,actor_usuario,actor_nombre,ip)
                       VALUES (?,?,?,?,?,?,?,?,?)");
    $st->bind_param('iiississs', $idEmpresa, $idMensual, $idDiaria, $eventoSeguro, $detalleSeguro, $uid, $actorUsuario, $actorNombre, $ip);
    $st->execute();
    return true;
  } catch (Throwable $e) {
    $detalleLen = 0;
    if ($detalleSeguro !== null) {
      $detalleLen = function_exists('mb_strlen')
        ? mb_strlen($detalleSeguro, 'UTF-8')
        : strlen($detalleSeguro);
    }

    // La auditoría no debe impedir abrir/cerrar cajas; dejamos traza local para revisión.
    logCajaLocal('auditoria.insert_error', [
      'empresa_id'         => $idEmpresa,
      'usuario_id'         => $uid,
      'evento_original'    => $eventoOriginal,
      'evento_normalizado' => $eventoSeguro,
      'detalle_len'        => $detalleLen,
      'id_caja_mensual'    => $idMensual,
      'id_caja_diaria'     => $idDiaria,
      'error_class'        => get_class($e),
      'error_code'         => $e->getCode(),
      'error_message'      => $e->getMessage()
    ]);
    return false;
  }
}

/* =========================
 * Utilidades PRECIOS
 * ========================= */
function ensure_price_slots(mysqli $db, int $empresa_id, int $servicio_id){
  $sql = "INSERT IGNORE INTO mod_precios (empresa_id,servicio_id,rol,precio,activo,nota,es_principal)
          VALUES (?,?,?,?,?,?,?)";
  $st = $db->prepare($sql);

  $rol='A'; $precio=1.00; $activo=1; $nota=null; $principal=1;
  $st->bind_param('iisdisi',$empresa_id,$servicio_id,$rol,$precio,$activo,$nota,$principal);
  $st->execute();

  foreach(['B','C','D','E'] as $rol){
    $precio=0.00; $activo=0; $nota=null; $principal=0;
    $st->bind_param('iisdisi',$empresa_id,$servicio_id,$rol,$precio,$activo,$nota,$principal);
    $st->execute();
  }
}
function list_prices(mysqli $db, int $empresa_id, int $servicio_id, ?string $estado=''){
  ensure_price_slots($db,$empresa_id,$servicio_id);
  $where = "empresa_id=? AND servicio_id=?";
  $types = 'ii'; $pars = [$empresa_id,$servicio_id];
  if ($estado==='1'||$estado==='0'){ $where.=" AND activo=?"; $types.='i'; $pars[]=(int)$estado; }
  $sql = "SELECT id,empresa_id,servicio_id,rol,precio,activo,nota,es_principal
          FROM mod_precios
          WHERE $where
          ORDER BY (es_principal=1) DESC, FIELD(rol,'A','B','C','D','E')";
  $st = $db->prepare($sql);
  $st->bind_param($types, ...$pars);
  $st->execute();
  return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}
function get_principal_price(mysqli $db, int $empresa_id, int $servicio_id){
  ensure_price_slots($db,$empresa_id,$servicio_id);
  $st = $db->prepare("SELECT id,rol,precio,nota FROM mod_precios 
                      WHERE empresa_id=? AND servicio_id=? AND es_principal=1 LIMIT 1");
  $st->bind_param('ii',$empresa_id,$servicio_id);
  $st->execute();
  $r = $st->get_result()->fetch_assoc();
  if(!$r){ return ['precio'=>1.00,'nota'=>null,'rol'=>'A']; }
  return ['precio'=>(float)$r['precio'],'nota'=>$r['nota'],'rol'=>$r['rol'],'id'=>(int)$r['id']];
}

/* =========================
 * Utilidades POS (ventas)
 * ========================= */
function pos_next_ticket(mysqli $db, int $empresa_id){
  // Serie T por empresa; bloqueo e incremento seguro
  $db->begin_transaction();
  try{
    $st = $db->prepare("SELECT id, serie, siguiente_numero
                        FROM pos_series
                        WHERE id_empresa=? AND tipo_comprobante='TICKET' AND activo=1
                        ORDER BY id
                        LIMIT 1 FOR UPDATE");
    $st->bind_param('i', $empresa_id);
    $st->execute();
    $s = $st->get_result()->fetch_assoc();
    if(!$s){ throw new Exception('No hay series activas configuradas para esta empresa.'); }
    $upd = $db->prepare("UPDATE pos_series SET siguiente_numero=siguiente_numero+1 WHERE id=? LIMIT 1");
    $upd->bind_param('i', $s['id']);
    $upd->execute();
    $db->commit();
    return ['serie_id'=>(int)$s['id'], 'serie'=>$s['serie'], 'numero'=>(int)$s['siguiente_numero']];
  }catch(Throwable $e){
    $db->rollback(); throw $e;
  }
}

function upsert_pos_cliente(mysqli $db, array $cli){
  // $cli: id_empresa, tipo_persona, doc_tipo, doc_numero, nombre, email, telefono, direccion
  $st = $db->prepare("SELECT id FROM pos_clientes WHERE id_empresa=? AND doc_tipo=? AND doc_numero=? LIMIT 1");
  $st->bind_param('iss', $cli['id_empresa'], $cli['doc_tipo'], $cli['doc_numero']);
  $st->execute();
  $r = $st->get_result()->fetch_assoc();
  if ($r){
    $id = (int)$r['id'];
    $up = $db->prepare("UPDATE pos_clientes
                        SET nombre=?, email=?, telefono=?, direccion=?, tipo_persona=?
                        WHERE id=?");
    $up->bind_param('sssssi', $cli['nombre'], $cli['email'], $cli['telefono'], $cli['direccion'], $cli['tipo_persona'], $id);
    $up->execute();
    return $id;
  }else{
    $ins = $db->prepare("INSERT INTO pos_clientes(id_empresa,tipo_persona,doc_tipo,doc_numero,nombre,email,telefono,direccion)
                         VALUES (?,?,?,?,?,?,?,?)");
    $ins->bind_param('isssssss', $cli['id_empresa'],$cli['tipo_persona'],$cli['doc_tipo'],$cli['doc_numero'],$cli['nombre'],$cli['email'],$cli['telefono'],$cli['direccion']);
    $ins->execute();
    return (int)$db->insert_id;
  }
}

function upsert_pos_conductor(mysqli $db, array $co){
  // $co: id_empresa, doc_tipo, doc_numero, nombres, apellidos, telefono, email
  $st = $db->prepare("SELECT id FROM pos_conductores WHERE id_empresa=? AND doc_tipo=? AND doc_numero=? LIMIT 1");
  $st->bind_param('iss', $co['id_empresa'],$co['doc_tipo'],$co['doc_numero']);
  $st->execute();
  $r = $st->get_result()->fetch_assoc();
  if ($r){
    $id = (int)$r['id'];
    $up = $db->prepare("UPDATE pos_conductores SET nombres=?, apellidos=?, telefono=?, email=? WHERE id=?");
    $up->bind_param('ssssi', $co['nombres'],$co['apellidos'],$co['telefono'],$co['email'],$id);
    $up->execute();
    return $id;
  }else{
    $ins = $db->prepare("INSERT INTO pos_conductores(id_empresa,doc_tipo,doc_numero,nombres,apellidos,telefono,email)
                         VALUES (?,?,?,?,?,?,?)");
    $ins->bind_param('issssss', $co['id_empresa'],$co['doc_tipo'],$co['doc_numero'],$co['nombres'],$co['apellidos'],$co['telefono'],$co['email']);
    $ins->execute();
    return (int)$db->insert_id;
  }
}

/* ====== Validaciones auxiliares POS ====== */
function map_medios_pago_activos(mysqli $db): array {
  $rs = $db->query("SELECT id, nombre, requiere_ref FROM pos_medios_pago WHERE activo=1");
  $out = [];
  foreach($rs->fetch_all(MYSQLI_ASSOC) as $r){
    $out[(int)$r['id']] = ['id'=>(int)$r['id'],'nombre'=>$r['nombre'],'requiere_ref'=>(int)$r['requiere_ref']===1];
  }
  return $out;
}
function map_servicios_validos(mysqli $db, int $empId, array $ids): array {
  if (!count($ids)) return [];
  $place = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids)+1);
  $pars  = array_merge([$empId], $ids);
  $sql = "SELECT s.id, s.nombre
          FROM mod_servicios s
          JOIN mod_empresa_servicio mes ON mes.servicio_id=s.id AND mes.empresa_id=?
          WHERE s.activo=1 AND s.id IN ($place)";
  $st = $db->prepare($sql);
  $st->bind_param($types, ...$pars);
  $st->execute();
  $map=[];
  foreach($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r){
    $map[(int)$r['id']] = $r['nombre'];
  }
  return $map;
}

/* =========================
 * Ruteo
 * ========================= */
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? $_POST['accion'] ?? 'estado';

try {

  /* ==== ESTADO de caja ==== */
  if ($accion === 'estado') {
  $cm = getMensualPeriodo($db,$empId,$Y,$m);           // mensual del período actual (si existe)
  $cd = getDiariaFecha($db,$empId,$today);             // diaria de HOY (si existe)
  $mensualAbierta = getMensualAbierta($db,$empId);     // alguna mensual abierta (solo puede existir 1)
  $diariaAbierta  = getDiariaAbierta($db,$empId);      // alguna diaria abierta (solo puede existir 1)

  $cmExiste = !!$cm;
  $cmOpen   = $cmExiste && $cm['estado']==='abierta';
  $cdExiste = !!$cd;
  $cdOpen   = $cdExiste && $cd['estado']==='abierta';

  // Si hay mensual abierta y no es la del periodo actual, la consideramos "otra mensual abierta"
  $otraMensualAbierta = $mensualAbierta && ( !$cmOpen || (int)$mensualAbierta['id'] !== (int)($cm['id'] ?? 0) );

  // Si hay diaria abierta y no es la de hoy, la consideramos "otra diaria abierta"
  $otraDiariaAbierta  = $diariaAbierta  && ( !$cdOpen || (int)$diariaAbierta['id']   !== (int)($cd['id'] ?? 0) );

  // Cierre de mensual disponible (actual sin diarias abiertas, o existe otra mensual abierta)
  $canCloseMensualActual   = $cmOpen ? !hayDiariasAbiertasMensual($db,(int)$cm['id']) : false;
  $canCloseMensualPendiente= false;
  if ($otraMensualAbierta) {
    $canCloseMensualPendiente = !hayDiariasAbiertasMensual($db,(int)$mensualAbierta['id']);
  }

  $cmOut = [
    'existe'      => $cmExiste,
    'estado'      => $cm['estado'] ?? 'inexistente',
    'codigo'      => $cm['codigo'] ?? codigoMensual($empId,$Y,$m),
    'chip'        => shortMY($Y,$m),
    'abierto_en'  => $cmExiste ? ($cm['abierto_en'] ?? null) : null,
    'cerrado_en'  => $cmExiste ? ($cm['cerrado_en'] ?? null) : null
  ];

  $cdOut = [
    'existe'      => $cdExiste,
    'estado'      => $cd['estado'] ?? 'inexistente',
    'codigo'      => $cd['codigo'] ?? codigoDiaria($empId,$Y,$m,$d),
    'chip'        => shortDMY($Y,$m,$d),
    'abierto_en'  => $cdExiste ? ($cd['abierto_en'] ?? null) : null,
    'cerrado_en'  => $cdExiste ? ($cd['cerrado_en'] ?? null) : null
  ];

  json_ok([
    'cm'=>$cmOut,
    'cd'=>$cdOut,
    'botones'=>[
      'abrir_mensual' => (!$cmExiste && !$otraMensualAbierta),
      // Habilitamos "Cerrar mensual" si: (a) mensual actual cerrable, o (b) existe otra mensual abierta (el endpoint pendiente validará diarias abiertas)
      'cerrar_mensual'=> ($canCloseMensualActual || $otraMensualAbierta),
      'abrir_diaria'  => ($cmOpen && !$cdExiste && !$otraDiariaAbierta),
      // Habilitamos "Cerrar diaria" si hoy está abierta O existe otra diaria abierta pendiente
      'cerrar_diaria' => ($cdOpen || $otraDiariaAbierta)
    ],
    'locks'=>[
      'otra_mensual_abierta'     => !!$otraMensualAbierta,
      'otra_diaria_abierta'      => !!$otraDiariaAbierta,
      'mensual_abierta_codigo'   => $mensualAbierta['codigo'] ?? null,
      'diaria_abierta_fecha'     => $diariaAbierta['fecha'] ?? null,
      'diaria_abierta_codigo'    => $diariaAbierta['codigo'] ?? null,
      'mensual_pendiente_cerrable'=> $canCloseMensualPendiente
    ]
  ]);
}

  /* ==== TAGS para chips ==== */
  if ($accion === 'svc_tags') {
    $sql = "SELECT e.id, e.nombre, COUNT(DISTINCT s.id) AS total
            FROM mod_etiquetas e
            JOIN mod_servicio_etiqueta se ON se.etiqueta_id = e.id
            JOIN mod_servicios s         ON s.id = se.servicio_id AND s.activo=1
            JOIN mod_empresa_servicio mes ON mes.servicio_id=s.id AND mes.empresa_id=?
            GROUP BY e.id, e.nombre
            ORDER BY e.nombre";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empId);
    $st->execute();
    $tags = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $st2 = $db->prepare("SELECT COUNT(*) c
                         FROM mod_servicios s
                         JOIN mod_empresa_servicio mes ON mes.servicio_id=s.id AND mes.empresa_id=?
                         WHERE s.activo=1");
    $st2->bind_param('i', $empId); $st2->execute();
    $all = (int)($st2->get_result()->fetch_assoc()['c'] ?? 0);

    json_ok(['tags'=>$tags,'all'=>$all]);
  }

  /* ==== POS: medios de pago (global, activos) ==== */
  if ($accion === 'pos_medios_pago') {
    $rs = $db->query("SELECT id, nombre, requiere_ref FROM pos_medios_pago WHERE activo=1 ORDER BY id");
    $rows = [];
    foreach (($rs->fetch_all(MYSQLI_ASSOC) ?: []) as $r) {
      $rows[] = [
        'id'           => (int)$r['id'],
        'nombre'       => (string)$r['nombre'],
        // Normalizamos a número 0/1 para que el front no trate "0" como truthy
        'requiere_ref' => ((int)$r['requiere_ref'] === 1 ? 1 : 0),
      ];
    }
    json_ok(['data'=>$rows]);
  }


  /* ==== POS: paneles de prueba (últimos registros) ==== */
  if ($accion === 'pos_debug_last') {
    // Ventas
    $qv = $db->prepare("SELECT v.id,
                               CONCAT(v.serie,'-',LPAD(v.numero,4,'0')) ticket,
                               v.fecha_emision fecha,
                               c.nombre cliente,
                               v.total, v.total_pagado pagado, v.total_devuelto devuelto, v.saldo
                        FROM pos_ventas v
                        LEFT JOIN pos_clientes c ON c.id=v.cliente_id
                        WHERE v.id_empresa=?
                        ORDER BY v.id DESC
                        LIMIT 5");
    $qv->bind_param('i',$empId); $qv->execute();
    $ventas = $qv->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    // Abonos
    $qa = $db->prepare("SELECT a.id, a.fecha,
                               mp.nombre medio,
                               c.nombre cliente,
                               a.monto, a.referencia
                        FROM pos_abonos a
                        JOIN pos_medios_pago mp ON mp.id=a.medio_id
                        LEFT JOIN pos_clientes c ON c.id=a.cliente_id
                        WHERE a.id_empresa=?
                        ORDER BY a.id DESC
                        LIMIT 5");
    $qa->bind_param('i',$empId); $qa->execute();
    $abonos = $qa->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    // Clientes
    $qc = $db->prepare("SELECT id, doc_tipo, doc_numero, nombre, telefono, activo
                        FROM pos_clientes
                        WHERE id_empresa=?
                        ORDER BY id DESC
                        LIMIT 5");
    $qc->bind_param('i',$empId); $qc->execute();
    $clientes = $qc->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    // Conductores
    $qd = $db->prepare("SELECT id, doc_tipo, doc_numero, nombres, apellidos, telefono, activo
                        FROM pos_conductores
                        WHERE id_empresa=?
                        ORDER BY id DESC
                        LIMIT 5");
    $qd->bind_param('i',$empId); $qd->execute();
    $conductores = $qd->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    json_ok(['ventas'=>$ventas,'abonos'=>$abonos,'clientes'=>$clientes,'conductores'=>$conductores]);
  }

  /* ==== LISTADO de servicios ==== */
  if ($accion === 'servicios') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = min(36, max(6, (int)($_GET['per'] ?? 9)));
    $q    = trim($_GET['q'] ?? '');
    $tag  = trim($_GET['tag'] ?? '*');

    $where = ["s.activo=1", "mes.empresa_id=?"];
    $types = "i";
    $pars  = [$empId];

    if ($q !== '') {
      $where[] = "(s.nombre LIKE ? OR s.descripcion LIKE ?)";
      $types  .= "ss";
      $like = like_wrap($q);
      $pars[] = $like; $pars[] = $like;
    }
    if ($tag !== '*' && $tag !== '') {
      $where[] = "EXISTS(SELECT 1 FROM mod_servicio_etiqueta se2 WHERE se2.servicio_id=s.id AND se2.etiqueta_id=?)";
      $types  .= "i";
      $pars[]  = (int)$tag;
    }
    $W = "WHERE ".implode(' AND ',$where);

    $sqlCount = "SELECT COUNT(DISTINCT s.id) c
                 FROM mod_servicios s
                 JOIN mod_empresa_servicio mes ON mes.servicio_id=s.id
                 $W";
    $stc = $db->prepare($sqlCount);
    $stc->bind_param($types, ...$pars);
    $stc->execute();
    $total = (int)($stc->get_result()->fetch_assoc()['c'] ?? 0);

    $offset = ($page-1)*$per;
    $sql = "SELECT
              s.id,
              s.nombre,
              s.descripcion,
              s.imagen_path,
              COALESCE(p.precio, 0.00)  AS precio,
              p.nota                    AS nota,
              p.rol                     AS rol,
              GROUP_CONCAT(DISTINCT e.nombre ORDER BY e.nombre SEPARATOR '|') AS tags
            FROM mod_servicios s
            JOIN mod_empresa_servicio mes ON mes.servicio_id=s.id
            LEFT JOIN (
              SELECT mp.empresa_id, mp.servicio_id, mp.precio, mp.nota, mp.rol
              FROM mod_precios mp
              WHERE mp.es_principal=1 AND mp.activo=1
            ) p ON p.empresa_id=mes.empresa_id AND p.servicio_id=s.id
            LEFT JOIN mod_servicio_etiqueta se ON se.servicio_id=s.id
            LEFT JOIN mod_etiquetas e ON e.id=se.etiqueta_id
            $W
            GROUP BY s.id
            ORDER BY s.nombre
            LIMIT ?, ?";
    $types2 = $types . "ii";
    $pars2  = array_merge($pars, [$offset, $per]);
    $st = $db->prepare($sql);
    $st->bind_param($types2, ...$pars2);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    json_ok(['data'=>$rows,'page'=>$page,'per'=>$per,'total'=>$total]);
  }

  /* ==== PRECIOS activos de un servicio ==== */
  if ($accion === 'svc_precios') {
    $sid = (int)($_GET['servicio_id'] ?? 0);
    if ($sid<=0) json_err('Servicio inválido');

    $sql = "SELECT id, rol, precio, activo, nota, es_principal
            FROM mod_precios
            WHERE empresa_id=? AND servicio_id=? AND activo=1
            ORDER BY (es_principal=1) DESC, FIELD(rol,'A','B','C','D','E')";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $empId, $sid);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    json_ok(['data'=>$rows]);
  }

  /* ==== SOLO POST para acciones de caja / POS ==== */
  if (in_array($accion, ['abrir_mensual','cerrar_mensual','cerrar_mensual_pendiente','abrir_diaria','cerrar_diaria','cerrar_diaria_pendiente','venta_crear'], true) && $method !== 'POST') {
    json_err('Método no permitido.');
  }

  /* ======== ABRIR MENSUAL (NO REABRIR) ======== */
  if ($accion === 'abrir_mensual') {
    $db->begin_transaction();
    try{
      // ¿Hay otra mensual abierta?
      $st = $db->prepare("SELECT id, anio, mes, codigo FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' LIMIT 1 FOR UPDATE");
      $st->bind_param('i',$empId); $st->execute();
      $mOpen = $st->get_result()->fetch_assoc();
      if ($mOpen) {
        $db->rollback();
        json_err_code(409, 'Ya existe una caja mensual abierta ('.$mOpen['codigo'].'). Ciérrala primero.');
      }

      // ¿Existe la mensual del período actual (abierta o cerrada)?
      $q = $db->prepare("SELECT id, estado, codigo FROM mod_caja_mensual WHERE id_empresa=? AND anio=? AND mes=? LIMIT 1 FOR UPDATE");
      $q->bind_param('iii',$empId,$Y,$m); $q->execute();
      $row = $q->get_result()->fetch_assoc();
      if ($row) {
        // Política: NO REABRIR
        $db->rollback();
        json_err_code(409, 'La caja mensual del período ya existe ('.$row['codigo'].'). Reapertura no permitida.');
      }

      // Crear nueva mensual (abierta)
      $codigo = codigoMensual($empId,$Y,$m);
      $ins = $db->prepare("INSERT INTO mod_caja_mensual(id_empresa,anio,mes,codigo,estado,abierto_por)
                           VALUES (?,?,?,?, 'abierta', ?)");
      $ins->bind_param('iiisi',$empId,$Y,$m,$codigo,$uid);
      $ins->execute();
      $cmId = (int)$db->insert_id;

      logAuditoriaCaja($db,'abrir_mensual',$empId,$cmId,null,"Apertura CM {$codigo}",$u);
      $db->commit();
      json_ok(['msg'=>'Caja mensual abierta.']);
    }catch(Throwable $e){
      $db->rollback(); json_err_code(500,'No se pudo abrir mensual',['dev'=>$e->getMessage()]);
    }
  }

  /* ======== CERRAR MENSUAL ======== */
  if ($accion === 'cerrar_mensual') {
    $db->begin_transaction();
    try{
      // Mensual del período actual ABIERTA
      $q = $db->prepare("SELECT id, codigo FROM mod_caja_mensual WHERE id_empresa=? AND anio=? AND mes=? AND estado='abierta' LIMIT 1 FOR UPDATE");
      $q->bind_param('iii',$empId,$Y,$m); $q->execute();
      $cm = $q->get_result()->fetch_assoc();
      if (!$cm) { $db->rollback(); json_err_code(400,'No hay caja mensual abierta para cerrar.'); }

      // No puede haber diarias abiertas
      if (hayDiariasAbiertasMensual($db,(int)$cm['id'])) {
        $db->rollback(); json_err_code(409,'Hay caja(s) diaria(s) abierta(s) en este período.');
      }

      $up = $db->prepare("UPDATE mod_caja_mensual SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=? LIMIT 1");
      $up->bind_param('ii',$uid,$cm['id']); $up->execute();

      logAuditoriaCaja($db,'cerrar_mensual',$empId,(int)$cm['id'],null,"Cierre CM {$cm['codigo']}",$u);
      $db->commit();
      json_ok(['msg'=>'Caja mensual cerrada.']);
    }catch(Throwable $e){
      $db->rollback(); json_err_code(500,'No se pudo cerrar mensual',['dev'=>$e->getMessage()]);
    }
  }

/* ======== CERRAR MENSUAL PENDIENTE (cierre extemporáneo) ======== */
if ($accion === 'cerrar_mensual_pendiente') {
  $motivo = trim($_POST['motivo'] ?? '');
  if ($motivo === '') {
    logCajaLocal('cerrar_mensual_pendiente.validacion', [
      'empresa_id'  => $empId,
      'usuario_id'  => $uid,
      'motivo_len'  => 0,
      'motivo_vacio'=> true
    ]);
    json_err('Debes indicar un motivo para el cierre extemporáneo.');
  }

  $db->begin_transaction();
  try{
    // Mensual ABIERTA (la que sea)
    $q = $db->prepare("SELECT id, codigo FROM mod_caja_mensual WHERE id_empresa=? AND estado='abierta' ORDER BY anio ASC, mes ASC, id ASC LIMIT 1 FOR UPDATE");
    $q->bind_param('i',$empId); $q->execute();
    $cm = $q->get_result()->fetch_assoc();
    if (!$cm) {
      $db->rollback();
      logCajaLocal('cerrar_mensual_pendiente.fail', [
        'empresa_id' => $empId,
        'usuario_id' => $uid,
        'motivo_len' => strlen($motivo),
        'reason'     => 'no_hay_mensual_abierta'
      ]);
      json_err_code(400,'No hay caja mensual abierta para cerrar.');
    }

    // No puede tener diarias abiertas
    if (hayDiariasAbiertasMensual($db,(int)$cm['id'])) {
      $db->rollback();
      logCajaLocal('cerrar_mensual_pendiente.fail', [
        'empresa_id'  => $empId,
        'usuario_id'  => $uid,
        'motivo_len'  => strlen($motivo),
        'reason'      => 'hay_diarias_abiertas',
        'mensual_id'  => (int)$cm['id'],
        'mensual_codigo' => $cm['codigo']
      ]);
      json_err_code(409,'Hay caja(s) diaria(s) abierta(s) en este período. Ciérralas primero.');
    }

    // Cerrar mensual
    $up = $db->prepare("UPDATE mod_caja_mensual SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=? LIMIT 1");
    $up->bind_param('ii',$uid,$cm['id']); $up->execute();

    // Auditoría
    $detalle = "Cierre extemporáneo CM {$cm['codigo']}. Motivo: ".$motivo;
    logAuditoriaCaja($db,'cerrar_mensual',$empId,(int)$cm['id'],null,$detalle,$u);

    $db->commit();
    json_ok(['msg'=>'Caja mensual cerrada (extemporánea).']);
  }catch(Throwable $e){
    $db->rollback();
    logCajaLocal('cerrar_mensual_pendiente.exception', [
      'empresa_id'    => $empId,
      'usuario_id'    => $uid,
      'motivo_len'    => strlen($motivo),
      'error_class'   => get_class($e),
      'error_code'    => $e->getCode(),
      'error_message' => $e->getMessage(),
      'error_file'    => $e->getFile(),
      'error_line'    => $e->getLine()
    ]);
    json_err_code(500,'No se pudo cerrar la mensual pendiente',['dev'=>$e->getMessage()]);
  }
}

  /* ======== ABRIR DIARIA (NO REABRIR) ======== */
  if ($accion === 'abrir_diaria') {
    $db->begin_transaction();
    try{
      // Mensual del período actual debe estar ABIERTA
      $qm = $db->prepare("SELECT id, codigo FROM mod_caja_mensual WHERE id_empresa=? AND anio=? AND mes=? AND estado='abierta' LIMIT 1 FOR UPDATE");
      $qm->bind_param('iii',$empId,$Y,$m); $qm->execute();
      $cm = $qm->get_result()->fetch_assoc();
      if (!$cm) { $db->rollback(); json_err_code(400,'Primero abre la caja mensual.'); }

      // ¿Hay alguna diaria ABIERTA (de otro día)?
      $qdOpen = $db->prepare("SELECT id, fecha, codigo FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' LIMIT 1 FOR UPDATE");
      $qdOpen->bind_param('i',$empId); $qdOpen->execute();
      $dOpen = $qdOpen->get_result()->fetch_assoc();
      if ($dOpen && $dOpen['fecha'] !== $today) {
        $db->rollback();
        json_err_code(409,'Ya existe una caja diaria abierta ('.$dOpen['codigo'].' - '.$dOpen['fecha'].'). Ciérrala primero.');
      }

      // ¿Existe diaria de HOY (abierta o cerrada)?
      $qd = $db->prepare("SELECT id, estado, codigo FROM mod_caja_diaria WHERE id_empresa=? AND fecha=? LIMIT 1 FOR UPDATE");
      $qd->bind_param('is',$empId,$today); $qd->execute();
      $row = $qd->get_result()->fetch_assoc();
      if ($row) {
        // Política: NO REABRIR
        $db->rollback();
        json_err_code(409,'La caja diaria de hoy ya existe ('.$row['codigo'].'). Reapertura no permitida.');
      }

      // Crear diaria (abierta)
      $codigo = codigoDiaria($empId,$Y,$m,$d);
      $ins = $db->prepare("INSERT INTO mod_caja_diaria(id_empresa,id_caja_mensual,fecha,codigo,estado,abierto_por)
                           VALUES (?,?,?,?, 'abierta', ?)");
      $ins->bind_param('iissi',$empId,$cm['id'],$today,$codigo,$uid);
      $ins->execute();
      $cdId = (int)$db->insert_id;

      logAuditoriaCaja($db,'abrir_diaria',$empId,(int)$cm['id'],$cdId,"Apertura CD {$codigo}",$u);
      $db->commit();
      json_ok(['msg'=>'Caja diaria abierta.']);
    }catch(Throwable $e){
      $db->rollback(); json_err_code(500,'No se pudo abrir diaria',['dev'=>$e->getMessage()]);
    }
  }

  /* ======== CERRAR DIARIA ======== */
  if ($accion === 'cerrar_diaria') {
    $db->begin_transaction();
    try{
      $qd = $db->prepare("SELECT id, codigo FROM mod_caja_diaria WHERE id_empresa=? AND fecha=? AND estado='abierta' LIMIT 1 FOR UPDATE");
      $qd->bind_param('is',$empId,$today); $qd->execute();
      $cd = $qd->get_result()->fetch_assoc();
      if (!$cd) { $db->rollback(); json_err_code(400,'No hay caja diaria abierta hoy.'); }

      $up = $db->prepare("UPDATE mod_caja_diaria SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=? LIMIT 1");
      $up->bind_param('ii',$uid,$cd['id']); $up->execute();

      // id_caja_mensual para auditoría
      $qcm = $db->prepare("SELECT id_caja_mensual FROM mod_caja_diaria WHERE id=? LIMIT 1");
      $qcm->bind_param('i',$cd['id']); $qcm->execute();
      $id_cm = (int)($qcm->get_result()->fetch_assoc()['id_caja_mensual'] ?? 0);

      logAuditoriaCaja($db,'cerrar_diaria',$empId,$id_cm,(int)$cd['id'],"Cierre CD {$cd['codigo']}",$u);
      $db->commit();
      json_ok(['msg'=>'Caja diaria cerrada.']);
    }catch(Throwable $e){
      $db->rollback(); json_err_code(500,'No se pudo cerrar diaria',['dev'=>$e->getMessage()]);
    }
  }

/* ======== CERRAR DIARIA PENDIENTE (cierre extemporáneo) ======== */
if ($accion === 'cerrar_diaria_pendiente') {
  $motivo = trim($_POST['motivo'] ?? '');
  if ($motivo === '') {
    logCajaLocal('cerrar_diaria_pendiente.validacion', [
      'empresa_id'  => $empId,
      'usuario_id'  => $uid,
      'motivo_len'  => 0,
      'motivo_vacio'=> true
    ]);
    json_err('Debes indicar un motivo para el cierre extemporáneo.');
  }

  $db->begin_transaction();
  try{
    // Buscar la diaria ABIERTA (la que sea)
    $q = $db->prepare("SELECT id, fecha, codigo, id_caja_mensual FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' ORDER BY fecha ASC, id ASC LIMIT 1 FOR UPDATE");
    $q->bind_param('i',$empId); $q->execute();
    $cd = $q->get_result()->fetch_assoc();
    if (!$cd) {
      $db->rollback();
      logCajaLocal('cerrar_diaria_pendiente.fail', [
        'empresa_id' => $empId,
        'usuario_id' => $uid,
        'motivo_len' => strlen($motivo),
        'reason'     => 'no_hay_diaria_abierta'
      ]);
      json_err_code(400,'No hay caja diaria abierta para cerrar.');
    }

    // La mensual a la que pertenece debe estar ABIERTA
    $qm = $db->prepare("SELECT id, codigo, estado FROM mod_caja_mensual WHERE id=? LIMIT 1 FOR UPDATE");
    $qm->bind_param('i',$cd['id_caja_mensual']); $qm->execute();
    $cm = $qm->get_result()->fetch_assoc();
    if (!$cm || $cm['estado']!=='abierta') {
      $db->rollback();
      logCajaLocal('cerrar_diaria_pendiente.fail', [
        'empresa_id'     => $empId,
        'usuario_id'     => $uid,
        'motivo_len'     => strlen($motivo),
        'reason'         => 'mensual_no_abierta',
        'diaria_id'      => (int)$cd['id'],
        'diaria_codigo'  => $cd['codigo'],
        'diaria_fecha'   => $cd['fecha'],
        'mensual_id'     => (int)($cm['id'] ?? 0),
        'mensual_codigo' => $cm['codigo'] ?? null,
        'mensual_estado' => $cm['estado'] ?? null
      ]);
      $cmCodigo = $cm['codigo'] ?? ('ID '.$cd['id_caja_mensual']);
      json_err_code(409,'La caja mensual del período ('.$cmCodigo.') no está abierta. Ábrela primero para cerrar la diaria pendiente.');
    }

    // Cerrar diaria
    $up = $db->prepare("UPDATE mod_caja_diaria SET estado='cerrada', cerrado_por=?, cerrado_en=NOW() WHERE id=? LIMIT 1");
    $up->bind_param('ii',$uid,$cd['id']); $up->execute();

    // Auditoría
    $detalle = "Cierre extemporáneo CD {$cd['codigo']} (fecha {$cd['fecha']}). Motivo: ".$motivo;
    logAuditoriaCaja($db,'cerrar_diaria',$empId,(int)$cm['id'],(int)$cd['id'],$detalle,$u);

    $db->commit();
    json_ok(['msg'=>'Caja diaria cerrada (extemporánea).']);
  }catch(Throwable $e){
    $db->rollback();
    logCajaLocal('cerrar_diaria_pendiente.exception', [
      'empresa_id'     => $empId,
      'usuario_id'     => $uid,
      'motivo_len'     => strlen($motivo),
      'error_class'    => get_class($e),
      'error_code'     => $e->getCode(),
      'error_message'  => $e->getMessage(),
      'error_file'     => $e->getFile(),
      'error_line'     => $e->getLine(),
      'diaria_id'      => isset($cd['id']) ? (int)$cd['id'] : null,
      'diaria_codigo'  => $cd['codigo'] ?? null,
      'diaria_fecha'   => $cd['fecha'] ?? null,
      'mensual_id'     => isset($cm['id']) ? (int)$cm['id'] : null,
      'mensual_codigo' => $cm['codigo'] ?? null,
      'mensual_estado' => $cm['estado'] ?? null
    ]);
    json_err_code(500,'No se pudo cerrar la diaria pendiente',['dev'=>$e->getMessage()]);
  }
}

  /* ======== POS: crear venta (cliente puede ser NATURAL o JURIDICA; conductor puede ser otro) ======== */
  if ($accion === 'venta_crear') {
    // 1) Verificar caja diaria de HOY abierta
    $cd = getDiariaAbierta($db,$empId);
if (!$cd) json_err('No hay caja diaria abierta. Cierra la pendiente o abre la del día actual.');
    $caja_diaria_id = (int)$cd['id'];

    // 2) Leer payload
    $cli_doc_tipo      = strtoupper(trim($_POST['cliente_doc_tipo'] ?? ''));
    $cli_doc_num       = trim($_POST['cliente_doc_numero'] ?? '');
    $cli_nombres       = trim($_POST['cliente_nombres'] ?? '');
    $cli_apellidos     = trim($_POST['cliente_apellidos'] ?? '');
    $cli_razon_social  = trim($_POST['cliente_razon_social'] ?? '');
    $cli_telefono      = trim($_POST['cliente_telefono'] ?? '');
    // NUEVO: documento del contratante (solo aplica si cliente es RUC)
$ct_doc_tipo  = strtoupper(trim($_POST['contratante_doc_tipo']  ?? ''));
$ct_doc_num   = trim($_POST['contratante_doc_numero'] ?? '');

    $items_json        = $_POST['items_json']  ?? '[]';
    $abonos_json       = $_POST['abonos_json'] ?? '[]';

    // Conductor "otra persona"
    $conductor_otro        = (int)($_POST['conductor_otro'] ?? 0) === 1;
    $co_doc_tipo           = strtoupper(trim($_POST['conductor_doc_tipo'] ?? ''));
    $co_doc_num            = trim($_POST['conductor_doc_numero'] ?? '');
    $co_nombres            = trim($_POST['conductor_nombres'] ?? '');
    $co_apellidos          = trim($_POST['conductor_apellidos'] ?? '');
    $co_telefono           = trim($_POST['conductor_telefono'] ?? '');

    // 3) Validaciones de cliente
    $valid_docs_all = ['DNI','CE','BREVETE','RUC'];
    if (!in_array($cli_doc_tipo, $valid_docs_all, true)) json_err('Tipo de documento de cliente inválido.');

    $cliente_tipo = ($cli_doc_tipo === 'RUC') ? 'JURIDICA' : 'NATURAL';

        if ($cliente_tipo === 'JURIDICA') {
      if ($cli_doc_tipo !== 'RUC') json_err('Para persona jurídica el documento debe ser RUC.');
      if ($cli_doc_num === '' || $cli_razon_social === '') json_err('RUC y razón social son obligatorios.');
      // El contratante es obligatorio (nombres y apellidos, servirán como conductor por defecto si no declara otro)
      if ($cli_nombres === '' || $cli_apellidos === '') json_err('Debes indicar nombres y apellidos del contratante.');
      // Documento del contratante obligatorio (DNI/CE/BREVETE)
      if (!in_array($ct_doc_tipo, ['DNI','CE','BREVETE'], true)) {
        json_err('Tipo de documento del contratante inválido (DNI/CE/BREVETE).');
      }
      if ($ct_doc_num === '') {
        json_err('El número de documento del contratante es obligatorio.');
      }
    } else {
      // NATURAL: se admite DNI / CE / BREVETE
      if ($cli_doc_num === '' || $cli_nombres === '' || $cli_apellidos === '') {
        json_err('Datos de cliente incompletos (documento, nombres y apellidos son obligatorios).');
      }
    }

    // 4) Validar items/abonos
    $items  = json_decode($items_json, true);
    $abonos = json_decode($abonos_json, true);
    if (!is_array($items) || !count($items)) json_err('Items de venta vacíos.');
    if (count($items) > 200) json_err('Demasiados ítems en la venta.');
    if (!is_array($abonos)) $abonos = [];

    // Servicios válidos + totales
    $svcIds = [];
    foreach($items as $i=>$it){
      $sid = (int)($it['servicio_id'] ?? 0);
      $cant = (float)($it['cantidad'] ?? 0);
      $pre  = (float)($it['precio_unitario'] ?? 0);
      if ($sid<=0 || $cant<=0 || $pre<0) json_err("Ítem inválido en posición ".($i+1));
      $svcIds[] = $sid;
    }
    $svcIds = array_values(array_unique($svcIds));
    $mapSvc = map_servicios_validos($db, $empId, $svcIds);
    if (count($mapSvc) !== count($svcIds)) json_err('Hay servicios no válidos o no vinculados a la empresa.');

    $total = 0.00;
    foreach($items as $it){
      $cant = max(0, (float)$it['cantidad']);
      $pre  = max(0, (float)$it['precio_unitario']);
      $total += $cant*$pre;
    }
    $total = money2($total);

    // Medios de pago
        // Medios de pago
    $mediosMap = map_medios_pago_activos($db);
    foreach($abonos as $j=>$ab){
      $mid   = (int)($ab['medio_id'] ?? 0);
      $monto = money2($ab['monto'] ?? 0);
      $ref   = trim($ab['referencia'] ?? '');

      if ($mid<=0 || !isset($mediosMap[$mid])) {
        json_err("Medio de pago inválido en el abono #".($j+1));
      }
      if ($monto<=0) {
        json_err("Monto inválido en el abono #".($j+1));
      }
      // Validación 100% basada en BD: si requiere_ref=1, la referencia es obligatoria
      $requiereRef = $mediosMap[$mid]['requiere_ref'];
      if ($requiereRef && $ref==='') {
        $nombreMedio = (string)$mediosMap[$mid]['nombre'];
        json_err("El medio de pago {$nombreMedio} requiere una referencia en el abono #".($j+1));
      }
    }

    // 5) Validaciones de conductor (si es otra persona)
    if ($conductor_otro) {
      if (!in_array($co_doc_tipo, ['DNI','CE','BREVETE'], true)) json_err('Tipo de documento del conductor inválido.');
      if ($co_doc_num==='' || $co_nombres==='' || $co_apellidos==='') json_err('Datos de conductor incompletos.');
    }

    $db->begin_transaction();
    try{
      // 6) Upsert cliente
      $cliente_nombre = ($cliente_tipo==='JURIDICA') ? $cli_razon_social : trim($cli_nombres.' '.$cli_apellidos);
      $cliente_id = upsert_pos_cliente($db, [
        'id_empresa'   => $empId,
        'tipo_persona' => $cliente_tipo,                         // NATURAL | JURIDICA
        'doc_tipo'     => $cli_doc_tipo,
        'doc_numero'   => $cli_doc_num,
        'nombre'       => $cliente_nombre,
        'email'        => null,
        'telefono'     => $cli_telefono?:null,
        'direccion'    => null
      ]);

      // 7) Upsert conductor
      if ($conductor_otro) {
        // Conductor indicado explícitamente
        $conductor_id = upsert_pos_conductor($db, [
          'id_empresa' => $empId,
          'doc_tipo'   => $co_doc_tipo,
          'doc_numero' => $co_doc_num,
          'nombres'    => $co_nombres,
          'apellidos'  => $co_apellidos,
          'telefono'   => $co_telefono?:null,
          'email'      => null
        ]);
      } else {
        // Conductor = cliente (si NATURAL) | contratante (si JURIDICA)
        if ($cliente_tipo === 'NATURAL') {
          $conductor_id = upsert_pos_conductor($db, [
            'id_empresa' => $empId,
            'doc_tipo'   => $cli_doc_tipo,
            'doc_numero' => $cli_doc_num,
            'nombres'    => $cli_nombres,
            'apellidos'  => $cli_apellidos,
            'telefono'   => $cli_telefono?:null,
            'email'      => null
          ]);
        } else {
          // Persona jurídica: usamos los datos del CONTRATANTE (doc_tipo/doc_num del contratante).
          $conductor_id = upsert_pos_conductor($db, [
            'id_empresa' => $empId,
            'doc_tipo'   => $ct_doc_tipo,
            'doc_numero' => $ct_doc_num,
            'nombres'    => $cli_nombres,     // contratante
            'apellidos'  => $cli_apellidos,   // contratante
            'telefono'   => $cli_telefono?:null,
            'email'      => null
          ]);
        }
      }

// NUEVO: preparar snapshot del contratante solo si el cliente es JURIDICA
$contr_doc_tipo   = ($cliente_tipo==='JURIDICA') ? $ct_doc_tipo   : null;
$contr_doc_num    = ($cliente_tipo==='JURIDICA') ? $ct_doc_num    : null;
$contr_nombres    = ($cliente_tipo==='JURIDICA') ? $cli_nombres   : null;
$contr_apellidos  = ($cliente_tipo==='JURIDICA') ? $cli_apellidos : null;
$contr_telefono   = ($cliente_tipo==='JURIDICA') ? ($cli_telefono ?: null) : null;

      // 8) Serie y cabecera
      $ticket   = pos_next_ticket($db, $empId); // bloquea/incrementa
      $saldoIni = $total;

      $insV = $db->prepare("INSERT INTO pos_ventas(
        id_empresa, caja_diaria_id, serie_id, cliente_id,
        contratante_doc_tipo, contratante_doc_numero, contratante_nombres, contratante_apellidos, contratante_telefono,
        tipo_comprobante, serie, numero, fecha_emision,
        moneda, estado, total, total_pagado, total_devuelto, saldo, creado_por
      ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        'TICKET', ?, ?, NOW(),
        'PEN', 'EMITIDA', ?, 0.00, 0.00, ?, ?
      )");
      $insV->bind_param(
        'iiiissssssiddi',
        $empId,               // i
        $caja_diaria_id,      // i
        $ticket['serie_id'],  // i
        $cliente_id,          // i
        $contr_doc_tipo,      // s
        $contr_doc_num,       // s
        $contr_nombres,       // s
        $contr_apellidos,     // s
        $contr_telefono,      // s
        $ticket['serie'],     // s
        $ticket['numero'],    // i
        $total,               // d
        $saldoIni,            // d
        $uid                  // i
      );
      
      $insV->execute();
      $venta_id = (int)$db->insert_id;

      // 9) Detalles
      $insD  = $db->prepare("INSERT INTO pos_venta_detalles(venta_id, servicio_id, servicio_nombre, descripcion, cantidad, precio_unitario, descuento, total_linea)
                             VALUES (?,?,?,?,?,?,0.00,?)");
      foreach($items as $it){
        $sid = (int)$it['servicio_id'];
        $cant = max(0, (float)$it['cantidad']);
        $pre  = max(0, (float)$it['precio_unitario']);
        $line = money2($cant*$pre);
        $null = null;
        $name = $mapSvc[$sid] ?? ('Servicio '.$sid);
        $insD->bind_param('iissddd', $venta_id, $sid, $name, $null, $cant, $pre, $line);
        $insD->execute();
      }

      // 10) Conductor principal
      $insC = $db->prepare("INSERT INTO pos_venta_conductores(venta_id, conductor_tipo, conductor_id, estado, es_principal)
                            VALUES (?, 'REGISTRADO', ?, 'ASIGNADO', 1)");
      $insC->bind_param('ii', $venta_id, $conductor_id);
      $insC->execute();

      // 11) Abonos (+ sobrepago -> devuelto)
      $total_pagado = 0.00;
      $total_abonos = 0.00;
      $total_devuelto = 0.00;

      if (count($abonos)){
        $insA  = $db->prepare("INSERT INTO pos_abonos(id_empresa, caja_diaria_id, cliente_id, medio_id, fecha, monto, referencia, observacion, creado_por)
                               VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
        $insAp = $db->prepare("INSERT INTO pos_abono_aplicaciones(abono_id, venta_id, monto_aplicado, aplicado_en)
                               VALUES (?, ?, ?, NOW())");

        $restante = $total;

        foreach($abonos as $ab){
          $mid   = (int)($ab['medio_id'] ?? 0);
          $monto = money2($ab['monto'] ?? 0);
          $ref   = trim($ab['referencia'] ?? '') ?: null;
          $obs   = trim($ab['observacion'] ?? '') ?: null;

          $insA->bind_param('iiiidssi', $empId, $caja_diaria_id, $cliente_id, $mid, $monto, $ref, $obs, $uid);
          $insA->execute();
          $abono_id = (int)$db->insert_id;

          // Aplicación: solo hasta cubrir el total
          $aplicar = 0.00;
          if ($restante > 0) {
            $aplicar = min($monto, $restante);
            $insAp->bind_param('iid', $abono_id, $venta_id, $aplicar);
            $insAp->execute();
            $restante = money2($restante - $aplicar);
            $total_pagado = money2($total_pagado + $aplicar);
          }

          // Sobrepago
          if ($monto > $aplicar) {
            $total_devuelto = money2($total_devuelto + ($monto - $aplicar));
          }
          $total_abonos = money2($total_abonos + $monto);
        }
      }

      $saldo = max(0.00, money2($total - $total_pagado));
      $upV = $db->prepare("UPDATE pos_ventas SET total_pagado=?, saldo=?, total_devuelto=? WHERE id=? LIMIT 1");
      $upV->bind_param('dddi', $total_pagado, $saldo, $total_devuelto, $venta_id);
      $upV->execute();

      // 12) Auditoría
      $aud = $db->prepare("INSERT INTO pos_auditoria(id_empresa, tabla, registro_id, evento, datos, actor_id, actor_usuario, actor_nombre, ip, creado_en)
                           VALUES (?, 'pos_ventas', ?, 'VENTA_CREADA', NULL, ?, ?, ?, ?, NOW())");
      $actorUsuario = (string)($u['usuario'] ?? '');
      $actorNombre  = trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? '')) ?: null;
      $ip = $_SERVER['REMOTE_ADDR'] ?? null;
      $aud->bind_param('iiisss', $empId, $venta_id, $uid, $actorUsuario, $actorNombre, $ip);
      $aud->execute();

      $db->commit();
      json_ok([
        'venta_id'=>$venta_id,
        'ticket'=> $ticket['serie'].'-'.str_pad((string)$ticket['numero'],4,'0',STR_PAD_LEFT),
        'total'=> (float)$total,
        'pagado'=> (float)$total_pagado,
        'saldo'=> (float)$saldo,
        'devuelto'=> (float)$total_devuelto
      ]);
    }catch(Throwable $e){
      $db->rollback();
      json_err_code(500, 'No se pudo crear la venta', ['dev'=>$e->getMessage()]);
    }
  }

  // Si ninguna acción coincidió:
  if (!in_array($accion, ['estado','svc_tags','pos_medios_pago','pos_debug_last','servicios','svc_precios','abrir_mensual','cerrar_mensual','abrir_diaria','cerrar_diaria','venta_crear'], true)){
    json_err('Acción no reconocida.');
  }

} catch (Throwable $e) {
  // Falla inesperada: siempre en JSON
  json_err_code(500, 'Error no controlado', ['dev'=>$e->getMessage()]);
}
