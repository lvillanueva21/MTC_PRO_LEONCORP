<?php
// modules/consola/comunicados/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$mysqli = db();
$mysqli->set_charset('utf8mb4');

function jerror($code, $msg, $extra = []) { http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr = []) { echo json_encode(['ok'=>true] + $arr); exit; }

function slugify($s){
  $s = trim($s);
  $s = mb_strtolower($s,'UTF-8');
  $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n']);
  $s = preg_replace('~[^a-z0-9]+~u','-',$s);
  return trim(preg_replace('~-+~','-',$s),'-');
}
function upsert_image($fileArr, $nombre, $id, $dirAbs, $dirRel, $prefix){
  if (empty($fileArr) || $fileArr['error'] === UPLOAD_ERR_NO_FILE) return null;
  if ($fileArr['error'] !== UPLOAD_ERR_OK) jerror(400,'Error al subir la imagen');
  if ($fileArr['size'] > 5*1024*1024) jerror(400,'La imagen excede 5MB');

  $fi = new finfo(FILEINFO_MIME_TYPE);
  $mime = $fi->file($fileArr['tmp_name']);
  $exts = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($exts[$mime])) jerror(400,'Formato no permitido (PNG/JPG/WebP)');
  $ext = $exts[$mime];

  if (!is_dir($dirAbs)) @mkdir($dirAbs,0775,true);
  $idP  = str_pad((string)$id, 6, '0', STR_PAD_LEFT);

  // limpiar previas
  foreach (glob($dirAbs."/*-{$prefix}{$idP}.*") as $old) @unlink($old);

  $slug  = slugify($nombre);
  $stamp = date('Ymd');
  $file  = "{$stamp}-{$slug}-{$prefix}{$idP}.{$ext}";
  if (!move_uploaded_file($fileArr['tmp_name'], $dirAbs.'/'.$file)) jerror(500,'No se pudo guardar la imagen');
  return $dirRel.'/'.$file;
}
function delete_image_by_idpattern($dirAbs, $prefix, $id){
  $idP = str_pad((string)$id,6,'0',STR_PAD_LEFT);
  foreach (glob($dirAbs."/*-{$prefix}{$idP}.*") as $old) @unlink($old);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try{
  switch ($action){

    /* --- Catálogos auxiliares --- */
    case 'empresas': {
      $rs = $mysqli->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }
    case 'roles': {
      $rs = $mysqli->query("SELECT id, nombre FROM mtp_roles ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }
    case 'users_search': {
      $q = trim($_GET['q'] ?? '');
      $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
      if ($q === '') jok(['data'=>[]]);
      $like = "%$q%";
      $st = $mysqli->prepare("
        SELECT u.id, u.usuario, u.nombres, u.apellidos, e.nombre empresa
        FROM mtp_usuarios u
        JOIN mtp_empresas e ON e.id = u.id_empresa
        WHERE (u.usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)
        ORDER BY u.nombres, u.apellidos
        LIMIT ?");
      $st->bind_param('sssi', $like,$like,$like,$limit);
      $st->execute();
      jok(['data'=>$st->get_result()->fetch_all(MYSQLI_ASSOC)]);
    }

    /* --- CRUD comunicados --- */
    case 'create': {
      $titulo = trim($_POST['titulo'] ?? '');
      $cuerpo = trim($_POST['cuerpo'] ?? '');
      $fi     = trim($_POST['fecha_inicio'] ?? '');
      $ff     = trim($_POST['fecha_fin'] ?? '');
      $fl     = trim($_POST['fecha_limite'] ?? '');
      $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

      if ($titulo === '') jerror(400,'El título es obligatorio');
      if (mb_strlen($titulo) > 300) jerror(400,'Título demasiado largo');

      $fi = $fi !== '' ? date('Y-m-d H:i:s', strtotime($fi)) : null;
      $ff = $ff !== '' ? date('Y-m-d H:i:s', strtotime($ff)) : null;
      $fl = $fl !== '' ? date('Y-m-d H:i:s', strtotime($fl)) : null;

      $mysqli->begin_transaction();

      $st = $mysqli->prepare("INSERT INTO com_comunicados (titulo,cuerpo,fecha_inicio,fecha_fin,fecha_limite,activo) VALUES (?,?,?,?,?,?)");
      $st->bind_param('sssssi', $titulo,$cuerpo,$fi,$ff,$fl,$activo);
      $st->execute();
      $cid = (int)$mysqli->insert_id;

      // imagen opcional
      $dirAbs = __DIR__ . '/../../../almacen/img_comunicados';
      $dirRel = 'almacen/img_comunicados';
      $ruta = upsert_image($_FILES['imagen'] ?? null, $titulo, $cid, $dirAbs, $dirRel, 'cmd');

      if ($ruta){
        $up = $mysqli->prepare("UPDATE com_comunicados SET imagen_path=? WHERE id=?");
        $up->bind_param('si', $ruta, $cid);
        $up->execute();
      }

      $mysqli->commit();
      jok(['id'=>$cid, 'imagen'=>$ruta]);
    }

    case 'update': {
      $id     = (int)($_POST['id'] ?? 0);
      $titulo = trim($_POST['titulo'] ?? '');
      $cuerpo = trim($_POST['cuerpo'] ?? '');
      $fi     = trim($_POST['fecha_inicio'] ?? '');
      $ff     = trim($_POST['fecha_fin'] ?? '');
      $fl     = trim($_POST['fecha_limite'] ?? '');
      if ($id <= 0) jerror(400,'ID inválido');
      if ($titulo === '') jerror(400,'El título es obligatorio');
      if (mb_strlen($titulo) > 300) jerror(400,'Título demasiado largo');

      $fi = $fi !== '' ? date('Y-m-d H:i:s', strtotime($fi)) : null;
      $ff = $ff !== '' ? date('Y-m-d H:i:s', strtotime($ff)) : null;
      $fl = $fl !== '' ? date('Y-m-d H:i:s', strtotime($fl)) : null;

      $mysqli->begin_transaction();

      $st = $mysqli->prepare("UPDATE com_comunicados SET titulo=?, cuerpo=?, fecha_inicio=?, fecha_fin=?, fecha_limite=? WHERE id=?");
      $st->bind_param('sssssi', $titulo,$cuerpo,$fi,$ff,$fl,$id);
      $st->execute();

      // nueva imagen opcional (reemplaza)
      $dirAbs = __DIR__ . '/../../../almacen/img_comunicados';
      $dirRel = 'almacen/img_comunicados';
      $ruta = upsert_image($_FILES['imagen'] ?? null, $titulo, $id, $dirAbs, $dirRel, 'cmd');
      if ($ruta){
        $up = $mysqli->prepare("UPDATE com_comunicados SET imagen_path=? WHERE id=?");
        $up->bind_param('si', $ruta, $id);
        $up->execute();
      }

      $mysqli->commit();
      jok(['id'=>$id, 'imagen'=>$ruta]);
    }

    case 'set_activo': {
      $id = (int)($_POST['id'] ?? 0);
      $activo = ($_POST['activo'] ?? '') !== '' ? (int)$_POST['activo'] : null;
      if ($id<=0 || !in_array($activo,[0,1],true)) jerror(400,'Parámetros inválidos');

      $st = $mysqli->prepare("UPDATE com_comunicados SET activo=? WHERE id=?");
      $st->bind_param('ii',$activo,$id);
      $st->execute();
      jok(['id'=>$id,'activo'=>$activo]);
    }

    case 'delete': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');

      // borrar imagen física (si sigue existiendo)
      $dirAbs = __DIR__ . '/../../../almacen/img_comunicados';
      delete_image_by_idpattern($dirAbs, 'cmd', $id);

      // borrar físico (cascade en targets/vistas)
      $st = $mysqli->prepare("DELETE FROM com_comunicados WHERE id=?");
      $st->bind_param('i',$id);
      $st->execute();
      if ($st->affected_rows === 0) jerror(404,'No encontrado');
      jok(['id'=>$id]);
    }

    case 'list': {
      $q       = trim($_GET['q'] ?? '');
      $estado  = $_GET['estado'] ?? '';      // '', '1', '0'
      $vig     = $_GET['vigencia'] ?? '';    // '', 'vigente','programado','expirado'
      $page    = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 5)));
      $offset  = ($page - 1) * $perPage;

      $where=[]; $types=''; $pars=[];
      if ($q !== ''){
        $like = "%$q%";
        $where[] = "(c.titulo LIKE ? COLLATE utf8mb4_spanish_ci OR c.cuerpo LIKE ? COLLATE utf8mb4_spanish_ci)";
        $types .= 'ss'; $pars[]=$like; $pars[]=$like;
      }
      if ($estado==='0' || $estado==='1'){
        $where[]="c.activo=?"; $types.='i'; $pars[]=(int)$estado;
      }
      if ($vig !== ''){
        if ($vig === 'vigente')    $where[]="( (c.fecha_inicio IS NULL OR c.fecha_inicio<=NOW()) AND (c.fecha_fin IS NULL OR c.fecha_fin>=NOW()) )";
        if ($vig === 'programado') $where[]="( c.fecha_inicio IS NOT NULL AND c.fecha_inicio>NOW() )";
        if ($vig === 'expirado')   $where[]="( c.fecha_fin IS NOT NULL AND c.fecha_fin<NOW() )";
      }
      $W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

      $stC = $mysqli->prepare("SELECT COUNT(*) c FROM com_comunicados c $W");
      if ($types) $stC->bind_param($types, ...$pars);
      $stC->execute(); $total = (int)($stC->get_result()->fetch_assoc()['c'] ?? 0);

      $sql = "SELECT c.id,c.titulo,c.activo,c.imagen_path,c.fecha_inicio,c.fecha_fin,c.fecha_limite,c.creado,c.actualizado
              FROM com_comunicados c
              $W
              ORDER BY c.id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii'; $pars2 = $pars; $pars2[]=$perPage; $pars2[]=$offset;
      $st = $mysqli->prepare($sql);
      $st->bind_param($types2, ...$pars2);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
    }

    /* --- Targets (reglas de audiencia) --- */
    case 'targets_list': {
      $cid = (int)($_GET['comunicado_id'] ?? 0);
      if ($cid<=0) jerror(400,'Comunicado requerido');
      $rs = $mysqli->query("
        SELECT t.id, t.tipo, t.usuario_id, t.rol_id, t.empresa_id,
               u.usuario as u_usuario, CONCAT(u.nombres,' ',u.apellidos) as u_nombre,
               r.nombre as rol_nombre,
               e.nombre as emp_nombre
        FROM com_comunicado_target t
        LEFT JOIN mtp_usuarios u ON u.id=t.usuario_id
        LEFT JOIN mtp_roles r ON r.id=t.rol_id
        LEFT JOIN mtp_empresas e ON e.id=t.empresa_id
        WHERE t.comunicado_id={$cid}
        ORDER BY t.id DESC");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'target_add': {
      $cid = (int)($_POST['comunicado_id'] ?? 0);
      $tipo = trim($_POST['tipo'] ?? '');
      $usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
      $rol_id     = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : null;
      $empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
      if ($cid<=0) jerror(400,'Comunicado requerido');
      $valid = ['TODOS','USUARIO','ROL','EMPRESA','EMPRESA_ROL'];
      if (!in_array($tipo,$valid,true)) jerror(400,'Tipo inválido');

      // Validación por tipo
      if ($tipo==='USUARIO' && $usuario_id<=0) jerror(400,'Usuario requerido');
      if ($tipo==='ROL'     && $rol_id<=0)     jerror(400,'Rol requerido');
      if ($tipo==='EMPRESA' && $empresa_id<=0) jerror(400,'Empresa requerida');
      if ($tipo==='EMPRESA_ROL' && ($empresa_id<=0 || $rol_id<=0)) jerror(400,'Empresa y rol requeridos');

      // Evitar duplicados básicos (app-level)
      $where = "comunicado_id=? AND tipo=?";
      $types='is'; $pars=[$cid,$tipo];
      if ($tipo==='USUARIO'){ $where.=" AND usuario_id=?"; $types.='i'; $pars[]=$usuario_id; }
      if ($tipo==='ROL'){ $where.=" AND rol_id=?"; $types.='i'; $pars[]=$rol_id; }
      if ($tipo==='EMPRESA'){ $where.=" AND empresa_id=?"; $types.='i'; $pars[]=$empresa_id; }
      if ($tipo==='EMPRESA_ROL'){ $where.=" AND empresa_id=? AND rol_id=?"; $types.='ii'; $pars[]=$empresa_id; $pars[]=$rol_id; }

      $st = $mysqli->prepare("SELECT COUNT(*) c FROM com_comunicado_target WHERE $where");
      $st->bind_param($types, ...$pars);
      $st->execute();
      if (((int)$st->get_result()->fetch_assoc()['c'])>0) jerror(409,'La regla ya existe');

      $stI = $mysqli->prepare("INSERT INTO com_comunicado_target (comunicado_id,tipo,usuario_id,rol_id,empresa_id) VALUES (?,?,?,?,?)");
      $u = $usuario_id>0?$usuario_id:null; $r=$rol_id>0?$rol_id:null; $e=$empresa_id>0?$empresa_id:null;
      $stI->bind_param('isiii', $cid,$tipo,$u,$r,$e);
      $stI->execute();
      jok(['id'=>$mysqli->insert_id]);
    }

    case 'target_del': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $st = $mysqli->prepare("DELETE FROM com_comunicado_target WHERE id=?");
      $st->bind_param('i',$id);
      $st->execute();
      if ($st->affected_rows===0) jerror(404,'No encontrado');
      jok(['id'=>$id]);
    }

    /* --- Preview de destinatarios (expande reglas a usuarios únicos) --- */
    case 'preview': {
      $cid = (int)($_GET['comunicado_id'] ?? 0);
      if ($cid<=0) jerror(400,'Comunicado requerido');

      // Si existe "TODOS"
      $hasAll = $mysqli->query("SELECT 1 FROM com_comunicado_target WHERE comunicado_id={$cid} AND tipo='TODOS' LIMIT 1")->fetch_row();
      if ($hasAll) {
        $rs = $mysqli->query("SELECT COUNT(*) c FROM mtp_usuarios")->fetch_assoc();
        jok(['total'=>(int)$rs['c'], 'sample'=>[]]);
      }

      $parts = [];

      // USUARIO
      $parts[] = "
        SELECT u.id FROM com_comunicado_target t
        JOIN mtp_usuarios u ON u.id=t.usuario_id
        WHERE t.comunicado_id={$cid} AND t.tipo='USUARIO'
      ";

      // ROL
      $parts[] = "
        SELECT ur.id_usuario AS id FROM com_comunicado_target t
        JOIN mtp_usuario_roles ur ON ur.id_rol=t.rol_id
        WHERE t.comunicado_id={$cid} AND t.tipo='ROL'
      ";

      // EMPRESA
      $parts[] = "
        SELECT u.id FROM com_comunicado_target t
        JOIN mtp_usuarios u ON u.id_empresa=t.empresa_id
        WHERE t.comunicado_id={$cid} AND t.tipo='EMPRESA'
      ";

      // EMPRESA_ROL
      $parts[] = "
        SELECT ur.id_usuario AS id FROM com_comunicado_target t
        JOIN mtp_usuario_roles ur ON ur.id_rol=t.rol_id
        JOIN mtp_usuarios u ON u.id=ur.id_usuario AND u.id_empresa=t.empresa_id
        WHERE t.comunicado_id={$cid} AND t.tipo='EMPRESA_ROL'
      ";

      $sql = "SELECT COUNT(DISTINCT id) c FROM (".implode(' UNION ALL ', $parts).") X";
      $total = (int)($mysqli->query($sql)->fetch_assoc()['c'] ?? 0);

      $sample = $mysqli->query("
        SELECT DISTINCT X.id
        FROM (".implode(' UNION ALL ', $parts).") X
        JOIN mtp_usuarios u ON u.id = X.id
        JOIN mtp_empresas e ON e.id = u.id_empresa
        ORDER BY u.nombres, u.apellidos
        LIMIT 10
      ")->fetch_all(MYSQLI_ASSOC);

      // enriquecer sample
      $out = [];
      if ($sample){
        $ids = array_map(fn($r)=>(int)$r['id'],$sample);
        $in  = implode(',', array_map('intval',$ids));
        $det = $mysqli->query("
          SELECT u.id, u.usuario, u.nombres, u.apellidos, e.nombre empresa
          FROM mtp_usuarios u JOIN mtp_empresas e ON e.id=u.id_empresa
          WHERE u.id IN ($in) ORDER BY u.nombres,u.apellidos
        ")->fetch_all(MYSQLI_ASSOC);
        $out = $det;
      }

      jok(['total'=>$total,'sample'=>$out]);
    }

    default: jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e){
  if ($mysqli->errno) $mysqli->rollback();
  jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
}
