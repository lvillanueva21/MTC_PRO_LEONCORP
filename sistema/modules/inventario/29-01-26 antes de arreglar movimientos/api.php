<?php
// modules/inventario/api.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/inv_lib.php';
require_once __DIR__ . '/inv_s4.php';

app_log_init(__DIR__ . '/../../logs/inventario_api.log');

header('Content-Type: application/json; charset=utf-8');

function jerror($code, $msg, $extra = []) { http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr = []) { echo json_encode(['ok'=>true]+$arr); exit; }
function norm_s($s){ return trim((string)$s); }
function i0($v){ $n=(int)$v; return $n>0?$n:0; }
function dec0($v){ $v=str_replace(',','.',(string)$v); if(!is_numeric($v)) return 0.0; $f=(float)$v; return $f<0?0.0:$f; }
function nz($s){ $s=norm_s($s); return $s===''?null:$s; }

function stmt_bind($stmt, $types, array &$params) {
  $refs = [];
  $refs[] = $types;
  foreach ($params as $k => $v) $refs[] = &$params[$k];
  call_user_func_array([$stmt, 'bind_param'], $refs);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

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
  if ($empresaId <= 0) jerror(403,'Empresa no asignada');
  if ($userId <= 0)    jerror(403,'Usuario no válido');

  $mysqli = db();

  app_log('INFO','INV API hit',['action'=>$action,'empresa'=>$empresaId,'user'=>$userId,'method'=>($_SERVER['REQUEST_METHOD']??'')]);

  // -------- META --------
  if ($action === 'meta') {
    $st = $mysqli->prepare("SELECT id,nombre FROM inv_ubicaciones WHERE id_empresa=? AND activo=1 ORDER BY nombre ASC");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $ubic = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $st = $mysqli->prepare("SELECT id,nombre FROM inv_categorias WHERE id_empresa=? AND activo=1 ORDER BY nombre ASC");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $cats = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $st = $mysqli->prepare("SELECT id, CONCAT(nombres,' ',apellidos) nombre FROM mtp_usuarios WHERE id_empresa=? ORDER BY nombres ASC, apellidos ASC");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $users = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    jok(['data'=>[
      'ubicaciones'=>$ubic,
      'categorias'=>$cats,
      'usuarios'=>$users,
      'tipos'=>['EQUIPO','HERRAMIENTA','CONSUMIBLE','MUEBLE','OTRO'],
      'estados'=>['BUENO','REGULAR','AVERIADO'],
      'unidades'=>['UND','CAJA','PACK','MT','ROLLO']
    ]]);
  }

  // -------- STATS --------
  if ($action === 'stats') {
    $st = $mysqli->prepare("
      SELECT
        COUNT(*) total,
        SUM(activo=1) activos,
        SUM(tipo='CONSUMIBLE') consumibles,
        SUM(estado='AVERIADO') averiados
      FROM inv_bienes
      WHERE id_empresa=?
    ");
    $st->bind_param('i', $empresaId);
    $st->execute();
    $r = $st->get_result()->fetch_assoc() ?: ['total'=>0,'activos'=>0,'consumibles'=>0,'averiados'=>0];

    jok(['data'=>[
      'total'=>(int)$r['total'],
      'activos'=>(int)$r['activos'],
      'consumibles'=>(int)$r['consumibles'],
      'averiados'=>(int)$r['averiados'],
    ]]);
  }

  // -------- LIST --------
  if ($action === 'list') {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $per    = max(1, min(60, (int)($_GET['per'] ?? 12)));
    $q      = norm_s($_GET['q'] ?? '');
    $tipo   = norm_s($_GET['tipo'] ?? '');
    $estado = norm_s($_GET['estado'] ?? '');
    $activo = (string)($_GET['activo'] ?? '');
    $catRaw = norm_s($_GET['cat_ids'] ?? '');
    $offset = ($page-1)*$per;

    // búsqueda directa por código INV-...
    $parsed = $q ? inv_parse_codigo($q) : false;

    $W = ["b.id_empresa=?"];
    $types = "i";
    $pars = [$empresaId];

    if ($parsed && (int)$parsed['empresa'] === $empresaId) {
      $W[] = "b.id=?";
      $types .= "i";
      $pars[] = (int)$parsed['id'];
    } else {
      if ($tipo !== '')   { $W[]="b.tipo=?";   $types.="s"; $pars[]=$tipo; }
      if ($estado !== '') { $W[]="b.estado=?"; $types.="s"; $pars[]=$estado; }
      if ($activo === '0' || $activo === '1') { $W[]="b.activo=?"; $types.="i"; $pars[]=(int)$activo; }

      if ($q !== '') {
        $W[]="(b.nombre LIKE ? OR b.descripcion LIKE ? OR b.marca LIKE ? OR b.modelo LIKE ? OR b.serie LIKE ?)";
        $types.="sssss";
        $like="%".$q."%";
        $pars[]=$like; $pars[]=$like; $pars[]=$like; $pars[]=$like; $pars[]=$like;
      }

      $catIds = [];
      if ($catRaw !== '') {
        foreach (explode(',', $catRaw) as $p) { $n=(int)trim($p); if($n>0) $catIds[$n]=$n; }
        $catIds = array_values($catIds);
      }
      if ($catIds) {
        $ph = implode(',', array_fill(0, count($catIds), '?'));
        $W[]="EXISTS (SELECT 1 FROM inv_bien_categoria bc2 WHERE bc2.id_bien=b.id AND bc2.id_categoria IN ($ph))";
        $types .= str_repeat('i', count($catIds));
        foreach ($catIds as $cid) $pars[] = $cid;
      }
    }

    $where = "WHERE " . implode(" AND ", $W);

    // total
    $st = $mysqli->prepare("SELECT COUNT(*) c FROM inv_bienes b $where");
    $bp = $pars;
    stmt_bind($st, $types, $bp);
    $st->execute();
    $total = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);

    $sql = "
      SELECT
        b.*,
        CONCAT('INV-', b.id_empresa, '-', DATE_FORMAT(b.creado,'%Y%m%d'), '-', LPAD(b.id,6,'0')) AS codigo_inv,
        ub.nombre AS ubicacion_nombre,
        CONCAT(u2.nombres,' ',u2.apellidos) AS responsable_user,
        TRIM(CONCAT_WS(' ', b.responsable_nombres, b.responsable_apellidos)) AS responsable_texto,
        GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS categorias_txt,
        GROUP_CONCAT(DISTINCT c.id ORDER BY c.id SEPARATOR ',') AS categorias_ids
      FROM inv_bienes b
      LEFT JOIN inv_ubicaciones ub ON ub.id = b.id_ubicacion
      LEFT JOIN mtp_usuarios u2 ON u2.id = b.id_responsable
      LEFT JOIN inv_bien_categoria bc ON bc.id_bien = b.id
      LEFT JOIN inv_categorias c ON c.id = bc.id_categoria
      $where
      GROUP BY b.id
      ORDER BY b.id DESC
      LIMIT ? OFFSET ?
    ";
    $st = $mysqli->prepare($sql);
    $types2 = $types . "ii";
    $pars2 = $pars; $pars2[]=$per; $pars2[]=$offset;
    $bp2 = $pars2;
    stmt_bind($st, $types2, $bp2);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per'=>$per]);
  }

  // -------- GET --------
  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) jerror(400,'ID inválido');

    $st = $mysqli->prepare("SELECT *, CONCAT('INV-', id_empresa, '-', DATE_FORMAT(creado,'%Y%m%d'), '-', LPAD(id,6,'0')) AS codigo_inv FROM inv_bienes WHERE id=? AND id_empresa=? LIMIT 1");
    $st->bind_param('ii', $id, $empresaId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) jerror(404,'Registro no encontrado');

    $st = $mysqli->prepare("SELECT id_categoria FROM inv_bien_categoria WHERE id_bien=?");
    $st->bind_param('i', $id);
    $st->execute();
    $cats = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $catIds = [];
    foreach ($cats as $c) $catIds[] = (int)$c['id_categoria'];

    jok(['data'=>$row,'categorias'=>$catIds]);
  }

  // -------- Permisos escritura (solo DES/ADM) --------
  $write = ['save','delete','move','cat_add','ubic_add'];
  if (in_array($action, $write, true)) {
    verificarPermiso(['Desarrollo','Administración']);
  }
  if ($action === 'mov_list') {
    verificarPermiso(['Desarrollo','Administración','Gerente']);
  }

  // -------- cat_add --------
  if ($action === 'cat_add') {
    $nombre = norm_s($_POST['nombre'] ?? '');
    if ($nombre==='') jerror(400,'Nombre requerido');
    $st = $mysqli->prepare("INSERT INTO inv_categorias (id_empresa,nombre,activo) VALUES (?,?,1)");
    $st->bind_param('is', $empresaId, $nombre);
    $st->execute();
    jok(['id'=>(int)$mysqli->insert_id,'nombre'=>$nombre]);
  }

  // -------- ubic_add --------
  if ($action === 'ubic_add') {
    $nombre = norm_s($_POST['nombre'] ?? '');
    if ($nombre==='') jerror(400,'Nombre requerido');
    $st = $mysqli->prepare("INSERT INTO inv_ubicaciones (id_empresa,nombre,activo) VALUES (?,?,1)");
    $st->bind_param('is', $empresaId, $nombre);
    $st->execute();
    jok(['id'=>(int)$mysqli->insert_id,'nombre'=>$nombre]);
  }

  // -------- save --------
  if ($action === 'save') {
    app_log('INFO','INV save request',['post'=>$_POST]);

    $id = (int)($_POST['id'] ?? 0);

    $tipo   = norm_s($_POST['tipo'] ?? 'EQUIPO');
    $nombre = norm_s($_POST['nombre'] ?? '');
    $desc   = norm_s($_POST['descripcion'] ?? '');
    $marca  = norm_s($_POST['marca'] ?? '');
    $modelo = norm_s($_POST['modelo'] ?? '');

    $serie  = nz($_POST['serie'] ?? null);

    $cantidad = dec0($_POST['cantidad'] ?? '1');
    $unidad   = norm_s($_POST['unidad'] ?? 'UND'); if ($unidad==='') $unidad='UND';
    $estado   = norm_s($_POST['estado'] ?? 'BUENO');

    $idUbic = i0($_POST['id_ubicacion'] ?? 0);
    $activo = (int)($_POST['activo'] ?? 1);

    $respMode = norm_s($_POST['resp_mode'] ?? 'USER');
    $idResp = 0;
    $rNom=null; $rApe=null; $rDni=null;

    if ($respMode === 'TEXT') {
      $rNom = nz($_POST['resp_nombres'] ?? null);
      $rApe = nz($_POST['resp_apellidos'] ?? null);
      $rDni = nz($_POST['resp_dni'] ?? null);
      $idResp = 0;
    } else {
      $idResp = i0($_POST['id_responsable'] ?? 0);
      $rNom=null; $rApe=null; $rDni=null;
    }

    $notas = norm_s($_POST['notas'] ?? '');

    // ---- imagen (S4) ----
    // Si NO viene img_key => NO tocar imagen (solo update normal)
    // Si viene vacío => quitar imagen (NULL)
    // Si viene con valor => cambiar imagen
    $hasImgKey = array_key_exists('img_key', $_POST);
    $imgKeyIn  = $hasImgKey ? trim((string)($_POST['img_key'] ?? '')) : null; // null => no vino
    $imgKeyNew = ($imgKeyIn === null) ? null : ($imgKeyIn === '' ? null : $imgKeyIn);

    if ($imgKeyNew !== null && !inv_s4_key_allowed($imgKeyNew, $empresaId)) {
      jerror(400, 'img_key no permitido');
    }

    $catsRaw = norm_s($_POST['categorias'] ?? '');
    $catIds = [];
    if ($catsRaw !== '') {
      foreach (explode(',', $catsRaw) as $p) { $n=(int)trim($p); if($n>0) $catIds[$n]=$n; }
      $catIds = array_values($catIds);
    }

    if ($nombre==='') jerror(400,'Nombre requerido');

    $idUb = $idUbic>0 ? $idUbic : null;
    $idRp = $idResp>0 ? $idResp : null;

    $mysqli->begin_transaction();

    // -------- UPDATE --------
    if ($id > 0) {
      $chk = $mysqli->prepare("
        SELECT id, creado, id_empresa, img_key
        FROM inv_bienes
        WHERE id=? AND id_empresa=?
        LIMIT 1
      ");
      $chk->bind_param('ii', $id, $empresaId);
      $chk->execute();
      $cur = $chk->get_result()->fetch_assoc();
      if (!$cur) { $mysqli->rollback(); jerror(404,'Registro no encontrado'); }

      $oldImgKey = trim((string)($cur['img_key'] ?? ''));

      // si NO enviaron img_key, conservamos la imagen anterior
      $imgKeySave = $hasImgKey ? $imgKeyNew : (($oldImgKey === '') ? null : $oldImgKey);

      $sql = "UPDATE inv_bienes
              SET tipo=?, nombre=?, descripcion=?, marca=?, modelo=?, serie=?,
                  cantidad=?, unidad=?, estado=?, id_ubicacion=?, id_responsable=?,
                  responsable_nombres=?, responsable_apellidos=?, responsable_dni=?,
                  img_key=?,
                  notas=?, activo=?
              WHERE id=? AND id_empresa=? LIMIT 1";

      $st = $mysqli->prepare($sql);

      // 19 parámetros:
      // tipo..serie (6s), cantidad(d), unidad/estado(2s), idUb/idRp(2i),
      // respNom/respApe/respDni/imgKey/notas (5s), activo(i), id(i), empresa(i)
      $types = "ssssssdssiisssssiii";

      $st->bind_param(
        $types,
        $tipo, $nombre, $desc, $marca, $modelo, $serie,
        $cantidad, $unidad, $estado,
        $idUb, $idRp,
        $rNom, $rApe, $rDni,
        $imgKeySave,
        $notas, $activo,
        $id, $empresaId
      );
      $st->execute();

      // categorías
      $st = $mysqli->prepare("DELETE FROM inv_bien_categoria WHERE id_bien=?");
      $st->bind_param('i', $id);
      $st->execute();

      if ($catIds) {
        $st = $mysqli->prepare("INSERT INTO inv_bien_categoria (id_bien,id_categoria) VALUES (?,?)");
        foreach ($catIds as $cid) { $st->bind_param('ii',$id,$cid); $st->execute(); }
      }

      $mysqli->commit();

      // borrar imagen anterior si el usuario intentó cambiarla (vino img_key)
      if ($hasImgKey && $oldImgKey !== '' && $oldImgKey !== (string)($imgKeySave ?? '')) {
        try {
          if (inv_s4_key_allowed($oldImgKey, $empresaId)) inv_s4_delete($oldImgKey);
        } catch (Throwable $e) {
          app_log_exception($e, ['op'=>'s4_delete_old','key'=>$oldImgKey,'id'=>$id,'empresa'=>$empresaId]);
        }
      }

      jok(['id'=>$id]);
    }

    // -------- INSERT --------
    $imgKeySave = $imgKeyNew; // si no vino, será null

    $sql = "INSERT INTO inv_bienes
      (id_empresa,tipo,nombre,descripcion,marca,modelo,serie,cantidad,unidad,estado,id_ubicacion,id_responsable,
       responsable_nombres,responsable_apellidos,responsable_dni,img_key,notas,activo)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $st = $mysqli->prepare($sql);

    // 18 vars
    $types = "issssssdssiisssssi";

    $st->bind_param(
      $types,
      $empresaId, $tipo, $nombre, $desc, $marca, $modelo, $serie,
      $cantidad, $unidad, $estado,
      $idUb, $idRp,
      $rNom, $rApe, $rDni,
      $imgKeySave,
      $notas, $activo
    );
    $st->execute();
    $newId = (int)$mysqli->insert_id;

    if ($catIds) {
      $st = $mysqli->prepare("INSERT INTO inv_bien_categoria (id_bien,id_categoria) VALUES (?,?)");
      foreach ($catIds as $cid) { $st->bind_param('ii',$newId,$cid); $st->execute(); }
    }

    // movimiento inicial (INGRESO)
    $sqlM = "INSERT INTO inv_movimientos
      (id_empresa,id_bien,tipo,desde_ubicacion,hacia_ubicacion,desde_responsable,hacia_responsable,
       desde_resp_nombres,desde_resp_apellidos,desde_resp_dni,
       hacia_resp_nombres,hacia_resp_apellidos,hacia_resp_dni,
       cantidad,nota,id_usuario)
      VALUES (?,?, 'INGRESO', NULL, ?, NULL, ?, NULL,NULL,NULL, ?,?,?, ?, 'Ingreso inicial', ?)";
    $st = $mysqli->prepare($sqlM);

    $haciaUb = $idUb;
    $haciaRp = $idRp;
    $hn = $rNom; $ha = $rApe; $hd = $rDni;

    // empresa(i) bien(i) haciaUb(i) haciaRp(i) hn(s) ha(s) hd(s) cantidad(d) user(i)
    $typesM = "iiiisssdi";
    $st->bind_param($typesM, $empresaId, $newId, $haciaUb, $haciaRp, $hn, $ha, $hd, $cantidad, $userId);
    $st->execute();

    $mysqli->commit();
    jok(['id'=>$newId]);
  }

  // -------- delete --------
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) jerror(400,'ID inválido');

    // leer img_key antes de borrar
    $st = $mysqli->prepare("SELECT img_key FROM inv_bienes WHERE id=? AND id_empresa=? LIMIT 1");
    $st->bind_param('ii', $id, $empresaId);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $delKey = trim((string)($r['img_key'] ?? ''));

    // borrar registro
    $st = $mysqli->prepare("DELETE FROM inv_bienes WHERE id=? AND id_empresa=? LIMIT 1");
    $st->bind_param('ii', $id, $empresaId);
    $st->execute();
    if ($st->affected_rows<=0) jerror(404,'Registro no encontrado');

    // borrar objeto en S4 (no romper si falla)
    if ($delKey !== '' && inv_s4_key_allowed($delKey, $empresaId)) {
      try { inv_s4_delete($delKey); }
      catch (Throwable $e) { app_log_exception($e, ['op'=>'s4_delete_on_delete','key'=>$delKey,'id'=>$id,'empresa'=>$empresaId]); }
    }

    jok(['id'=>$id]);
  }

  // -------- move --------
  if ($action === 'move') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) jerror(400,'ID inválido');

    $newUbic = i0($_POST['id_ubicacion'] ?? 0);
    $nota    = norm_s($_POST['nota'] ?? '');

    $respMode = norm_s($_POST['resp_mode'] ?? 'USER');
    $newRespId = 0;
    $newN=null; $newA=null; $newD=null;

    if ($respMode === 'TEXT') {
      $newN = nz($_POST['resp_nombres'] ?? null);
      $newA = nz($_POST['resp_apellidos'] ?? null);
      $newD = nz($_POST['resp_dni'] ?? null);
      $newRespId = 0;
    } else {
      $newRespId = i0($_POST['id_responsable'] ?? 0);
      $newN=null; $newA=null; $newD=null;
    }

    $st = $mysqli->prepare("SELECT id_ubicacion,id_responsable,responsable_nombres,responsable_apellidos,responsable_dni,cantidad FROM inv_bienes WHERE id=? AND id_empresa=? LIMIT 1");
    $st->bind_param('ii', $id, $empresaId);
    $st->execute();
    $cur = $st->get_result()->fetch_assoc();
    if (!$cur) jerror(404,'Registro no encontrado');

    $desdeUb = (int)($cur['id_ubicacion'] ?? 0);
    $desdeRp = (int)($cur['id_responsable'] ?? 0);
    $dN = $cur['responsable_nombres'] ?? null;
    $dA = $cur['responsable_apellidos'] ?? null;
    $dD = $cur['responsable_dni'] ?? null;
    $cant = (float)($cur['cantidad'] ?? 0);

    $haciaUb = $newUbic>0 ? $newUbic : null;
    $haciaRp = $newRespId>0 ? $newRespId : null;

    $tipo = 'TRASLADO';
    if (($haciaRp !== null && $haciaRp !== ($desdeRp ?: null)) && ($haciaUb === null || $haciaUb == ($desdeUb ?: null))) $tipo = 'ASIGNACION';
    if ($haciaUb !== null && $haciaUb != ($desdeUb ?: null)) $tipo = 'TRASLADO';

    $mysqli->begin_transaction();

    $sqlM = "INSERT INTO inv_movimientos
      (id_empresa,id_bien,tipo,desde_ubicacion,hacia_ubicacion,desde_responsable,hacia_responsable,
       desde_resp_nombres,desde_resp_apellidos,desde_resp_dni,
       hacia_resp_nombres,hacia_resp_apellidos,hacia_resp_dni,
       cantidad,nota,id_usuario)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $st = $mysqli->prepare($sqlM);

    $desdeUb2 = $desdeUb>0 ? $desdeUb : null;
    $desdeRp2 = $desdeRp>0 ? $desdeRp : null;

        // 16 parámetros => 16 tipos
    $types = "iisiiiissssssdsi";
    $st->bind_param(
      $types,
      $empresaId, $id, $tipo,
      $desdeUb2, $haciaUb,
      $desdeRp2, $haciaRp,
      $dN, $dA, $dD,
      $newN, $newA, $newD,
      $cant, $nota, $userId
    );
    $st->execute();


    $sqlU = "UPDATE inv_bienes
      SET id_ubicacion=?, id_responsable=?, responsable_nombres=?, responsable_apellidos=?, responsable_dni=?
      WHERE id=? AND id_empresa=? LIMIT 1";
    $st = $mysqli->prepare($sqlU);
    $st->bind_param('iisssii', $haciaUb, $haciaRp, $newN, $newA, $newD, $id, $empresaId);
    $st->execute();

    $mysqli->commit();
    jok(['id'=>$id]);
  }

  // -------- mov_list --------
  if ($action === 'mov_list') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id<=0) jerror(400,'ID inválido');

    $sql = "
      SELECT
        m.*,
        ud.nombre AS desde_ubic_nombre,
        uh.nombre AS hacia_ubic_nombre,
        CONCAT(usd.nombres,' ',usd.apellidos) AS desde_resp_user,
        CONCAT(ush.nombres,' ',ush.apellidos) AS hacia_resp_user,
        TRIM(CONCAT_WS(' ', m.desde_resp_nombres, m.desde_resp_apellidos)) AS desde_resp_texto,
        TRIM(CONCAT_WS(' ', m.hacia_resp_nombres, m.hacia_resp_apellidos)) AS hacia_resp_texto,
        CONCAT(u2.nombres,' ',u2.apellidos) AS hecho_por
      FROM inv_movimientos m
      LEFT JOIN inv_ubicaciones ud ON ud.id = m.desde_ubicacion
      LEFT JOIN inv_ubicaciones uh ON uh.id = m.hacia_ubicacion
      LEFT JOIN mtp_usuarios usd ON usd.id = m.desde_responsable
      LEFT JOIN mtp_usuarios ush ON ush.id = m.hacia_responsable
      INNER JOIN mtp_usuarios u2 ON u2.id = m.id_usuario
      WHERE m.id_empresa=? AND m.id_bien=?
      ORDER BY m.id DESC
      LIMIT 30
    ";
    $st = $mysqli->prepare($sql);
    $st->bind_param('ii', $empresaId, $id);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    jok(['data'=>$rows]);
  }

  jerror(400,'Acción no válida');

} catch (Throwable $e) {
  $errId = uniqid('inv_', true);
  app_log_exception($e, ['err_id'=>$errId,'action'=>$action,'get'=>$_GET,'post'=>$_POST]);
  jerror(500,'Error de servidor',['err_id'=>$errId,'dev'=>$e->getMessage()]);
}
