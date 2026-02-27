<?php
// modules/consola/publicidades/api.php
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

  foreach (glob($dirAbs."/*-{$prefix}{$idP}.*") as $old) @unlink($old); // limpiar previas

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
  switch($action){

    /* =======================
     * Catálogos / utilitarios
     * ======================= */
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
    case 'tags_suggest': {
      $q = trim($_GET['q'] ?? '');
      $limit = max(1, min(30, (int)($_GET['limit'] ?? 10)));
      if ($q==='') jok(['data'=>[]]);
      $like = $q.'%'; // prefijo
      $st = $mysqli->prepare("SELECT nombre FROM pb_etiquetas WHERE nombre LIKE ? ORDER BY nombre LIMIT ?");
      $st->bind_param('si', $like, $limit);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data'=>array_column($rows,'nombre')]);
    }

    /* ==============
     * CRUD Publicidad
     * ============== */
    case 'create': {
      $titulo = trim($_POST['titulo'] ?? '');
      $desc   = trim($_POST['descripcion'] ?? '');
      $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
      $csv    = trim($_POST['etiquetas'] ?? ''); // CSV desde chips

      if ($titulo==='') jerror(400,'El título es obligatorio');
      if (mb_strlen($titulo) > 300) jerror(400,'Título demasiado largo');

      // normalizar etiquetas a array único
      $tags=[];
      if ($csv!==''){
        foreach (explode(',', $csv) as $t){ $t=trim($t); if ($t!=='') $tags[$t]=true; }
        $tags = array_keys($tags);
      }

      $mysqli->begin_transaction();

      $st = $mysqli->prepare("INSERT INTO pb_publicidades (titulo, descripcion, activo, imagen_path) VALUES (?,?,?,NULL)");
      $st->bind_param('ssi', $titulo, $desc, $activo);
      $st->execute();
      $pid = (int)$mysqli->insert_id;

      // imagen opcional
      $dirAbs = __DIR__ . '/../../../almacen/img_publicidades';
      $dirRel = 'almacen/img_publicidades';
      $ruta = upsert_image($_FILES['imagen'] ?? null, $titulo, $pid, $dirAbs, $dirRel, 'pub');
      if ($ruta){
        $up = $mysqli->prepare("UPDATE pb_publicidades SET imagen_path=? WHERE id=?");
        $up->bind_param('si',$ruta,$pid);
        $up->execute();
      }

      // etiquetas
      if ($tags){
        $sel = $mysqli->prepare("SELECT id FROM pb_etiquetas WHERE nombre=?");
        $ins = $mysqli->prepare("INSERT INTO pb_etiquetas (nombre) VALUES (?)");
        $pv  = $mysqli->prepare("INSERT IGNORE INTO pb_publicidad_etiqueta (publicidad_id, etiqueta_id) VALUES (?,?)");
        foreach ($tags as $t){
          $sel->bind_param('s',$t); $sel->execute();
          $rid = $sel->get_result()->fetch_assoc()['id'] ?? null;
          if (!$rid){ $ins->bind_param('s',$t); $ins->execute(); $rid = (int)$mysqli->insert_id; }
          $pv->bind_param('ii',$pid,$rid); $pv->execute();
        }
      }

      $mysqli->commit();
      jok(['id'=>$pid,'imagen'=>$ruta,'tags'=>count($tags)]);
    }

    case 'update': {
      $id     = (int)($_POST['id'] ?? 0);
      $titulo = trim($_POST['titulo'] ?? '');
      $desc   = trim($_POST['descripcion'] ?? '');
      $csv    = isset($_POST['etiquetas']) ? trim($_POST['etiquetas']) : null; // si no viene, no tocar tags
      if ($id<=0) jerror(400,'ID inválido');
      if ($titulo==='') jerror(400,'El título es obligatorio');
      if (mb_strlen($titulo) > 300) jerror(400,'Título demasiado largo');

      // normalizar etiquetas si llegaron
      $tags = null;
      if ($csv !== null){
        $tags = [];
        foreach (explode(',', $csv) as $t){ $t = trim($t); if ($t!=='') $tags[$t]=true; }
        $tags = array_keys($tags);
      }

      $mysqli->begin_transaction();

      $st = $mysqli->prepare("UPDATE pb_publicidades SET titulo=?, descripcion=? WHERE id=?");
      $st->bind_param('ssi',$titulo,$desc,$id);
      $st->execute();

      // imagen opcional (reemplaza)
      $dirAbs = __DIR__ . '/../../../almacen/img_publicidades';
      $dirRel = 'almacen/img_publicidades';
      $ruta = upsert_image($_FILES['imagen'] ?? null, $titulo, $id, $dirAbs, $dirRel, 'pub');
      if ($ruta){
        $up = $mysqli->prepare("UPDATE pb_publicidades SET imagen_path=? WHERE id=?");
        $up->bind_param('si',$ruta,$id);
        $up->execute();
      }

      // reemplazo de etiquetas si llegaron
      if ($tags !== null){
        $del = $mysqli->prepare("DELETE FROM pb_publicidad_etiqueta WHERE publicidad_id=?");
        $del->bind_param('i',$id);
        $del->execute();

        if ($tags){
          $sel = $mysqli->prepare("SELECT id FROM pb_etiquetas WHERE nombre=?");
          $ins = $mysqli->prepare("INSERT INTO pb_etiquetas (nombre) VALUES (?)");
          $pv  = $mysqli->prepare("INSERT IGNORE INTO pb_publicidad_etiqueta (publicidad_id, etiqueta_id) VALUES (?,?)");
          foreach ($tags as $t){
            $sel->bind_param('s',$t); $sel->execute();
            $rid = $sel->get_result()->fetch_assoc()['id'] ?? null;
            if (!$rid){ $ins->bind_param('s',$t); $ins->execute(); $rid=(int)$mysqli->insert_id; }
            $pv->bind_param('ii',$id,$rid); $pv->execute();
          }
        }
      }

      $mysqli->commit();
      jok(['id'=>$id,'imagen'=>$ruta ?? null]);
    }

    case 'set_activo': {
      $id = (int)($_POST['id'] ?? 0);
      $activo = ($_POST['activo'] ?? '') !== '' ? (int)$_POST['activo'] : null;
      if ($id<=0 || !in_array($activo,[0,1],true)) jerror(400,'Parámetros inválidos');

      $st = $mysqli->prepare("UPDATE pb_publicidades SET activo=? WHERE id=?");
      $st->bind_param('ii',$activo,$id);
      $st->execute();
      jok(['id'=>$id,'activo'=>$activo]);
    }

    case 'delete': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');

      // borrar imagen física
      $dirAbs = __DIR__ . '/../../../almacen/img_publicidades';
      delete_image_by_idpattern($dirAbs, 'pub', $id);

      $st = $mysqli->prepare("DELETE FROM pb_publicidades WHERE id=?");
      $st->bind_param('i',$id);
      $st->execute();
      if ($st->affected_rows===0) jerror(404,'No encontrado');
      jok(['id'=>$id]);
    }

    case 'list': {
      $q       = trim($_GET['q'] ?? '');
      $estado  = $_GET['estado'] ?? '';      // '', '1', '0'
      $page    = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 5)));
      $offset  = ($page - 1) * $perPage;

      $where=[]; $types=''; $pars=[];
      if ($q!==''){
        $like="%$q%";
        $where[]="(p.titulo LIKE ? COLLATE utf8mb4_spanish_ci OR p.descripcion LIKE ? COLLATE utf8mb4_spanish_ci)";
        $types.='ss'; $pars[]=$like; $pars[]=$like;
      }
      if ($estado==='0' || $estado==='1'){
        $where[]="p.activo=?"; $types.='i'; $pars[]=(int)$estado;
      }
      $W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

      // total
      $stC = $mysqli->prepare("SELECT COUNT(*) c FROM pb_publicidades p $W");
      if ($types) $stC->bind_param($types, ...$pars); $stC->execute();
      $total = (int)($stC->get_result()->fetch_assoc()['c'] ?? 0);

      // datos
      $sql = "SELECT p.id,p.titulo,p.descripcion,p.activo,p.imagen_path,p.creado,p.actualizado
              FROM pb_publicidades p
              $W
              ORDER BY p.id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii'; $pars2=$pars; $pars2[]=$perPage; $pars2[]=$offset;
      $st = $mysqli->prepare($sql);
      $st->bind_param($types2, ...$pars2);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      // map de etiquetas
      $ids = array_column($rows,'id'); $tagMap=[];
      if ($ids){
        $place = implode(',', array_fill(0,count($ids),'?'));
        $tSql = "SELECT ppe.publicidad_id pid, e.nombre tag
                 FROM pb_publicidad_etiqueta ppe
                 JOIN pb_etiquetas e ON e.id = ppe.etiqueta_id
                 WHERE ppe.publicidad_id IN ($place)
                 ORDER BY e.nombre";
        $tTypes = str_repeat('i', count($ids));
        $stT = $mysqli->prepare($tSql);
        $stT->bind_param($tTypes, ...$ids);
        $stT->execute();
        $rt=$stT->get_result();
        while($r=$rt->fetch_assoc()){
          $pid=(int)$r['pid']; $tagMap[$pid][]=$r['tag'];
        }
      }
      foreach($rows as &$r){ $r['tags']=$tagMap[$r['id']] ?? []; }

      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
    }

    /* =========================
     * Audiencias por Publicidad
     * ========================= */
    case 'targets_list': {
      $pid = (int)($_GET['publicidad_id'] ?? 0);
      if ($pid<=0) jerror(400,'Publicidad requerida');
      $rs = $mysqli->query("
        SELECT t.id, t.tipo, t.usuario_id, t.rol_id, t.empresa_id,
               u.usuario as u_usuario, CONCAT(u.nombres,' ',u.apellidos) as u_nombre,
               r.nombre as rol_nombre, e.nombre as emp_nombre
        FROM pb_publicidad_target t
        LEFT JOIN mtp_usuarios u ON u.id=t.usuario_id
        LEFT JOIN mtp_roles r ON r.id=t.rol_id
        LEFT JOIN mtp_empresas e ON e.id=t.empresa_id
        WHERE t.publicidad_id={$pid}
        ORDER BY t.id DESC
      ");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'target_add': {
      $pid = (int)($_POST['publicidad_id'] ?? 0);
      $tipo = trim($_POST['tipo'] ?? '');
      $usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
      $rol_id     = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : null;
      $empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
      if ($pid<=0) jerror(400,'Publicidad requerida');
      $valid=['TODOS','USUARIO','ROL','EMPRESA','EMPRESA_ROL'];
      if(!in_array($tipo,$valid,true)) jerror(400,'Tipo inválido');

      if ($tipo==='USUARIO' && $usuario_id<=0) jerror(400,'Usuario requerido');
      if ($tipo==='ROL'     && $rol_id<=0)     jerror(400,'Rol requerido');
      if ($tipo==='EMPRESA' && $empresa_id<=0) jerror(400,'Empresa requerida');
      if ($tipo==='EMPRESA_ROL' && ($empresa_id<=0 || $rol_id<=0)) jerror(400,'Empresa y rol requeridos');

      $where="publicidad_id=? AND tipo=?"; $types='is'; $pars=[$pid,$tipo];
      if ($tipo==='USUARIO'){ $where.=" AND usuario_id=?"; $types.='i'; $pars[]=$usuario_id; }
      if ($tipo==='ROL'){ $where.=" AND rol_id=?"; $types.='i'; $pars[]=$rol_id; }
      if ($tipo==='EMPRESA'){ $where.=" AND empresa_id=?"; $types.='i'; $pars[]=$empresa_id; }
      if ($tipo==='EMPRESA_ROL'){ $where.=" AND empresa_id=? AND rol_id=?"; $types.='ii'; $pars[]=$empresa_id; $pars[]=$rol_id; }

      $st=$mysqli->prepare("SELECT COUNT(*) c FROM pb_publicidad_target WHERE $where");
      $st->bind_param($types, ...$pars); $st->execute();
      if (((int)$st->get_result()->fetch_assoc()['c'])>0) jerror(409,'La regla ya existe');

      $stI=$mysqli->prepare("INSERT INTO pb_publicidad_target (publicidad_id,tipo,usuario_id,rol_id,empresa_id) VALUES (?,?,?,?,?)");
      $u=$usuario_id>0?$usuario_id:null; $r=$rol_id>0?$rol_id:null; $e=$empresa_id>0?$empresa_id:null;
      $stI->bind_param('isiii',$pid,$tipo,$u,$r,$e); $stI->execute();
      jok(['id'=>$mysqli->insert_id]);
    }

    case 'target_del': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $st=$mysqli->prepare("DELETE FROM pb_publicidad_target WHERE id=?");
      $st->bind_param('i',$id); $st->execute();
      if ($st->affected_rows===0) jerror(404,'No encontrado');
      jok(['id'=>$id]);
    }

    case 'preview': {
      $pid = (int)($_GET['publicidad_id'] ?? 0);
      if ($pid<=0) jerror(400,'Publicidad requerida');

      $hasAll = $mysqli->query("SELECT 1 FROM pb_publicidad_target WHERE publicidad_id={$pid} AND tipo='TODOS' LIMIT 1")->fetch_row();
      if ($hasAll){ $rs=$mysqli->query("SELECT COUNT(*) c FROM mtp_usuarios")->fetch_assoc(); jok(['total'=>(int)$rs['c'],'sample'=>[]]); }

      $parts=[];
      $parts[] = "SELECT u.id FROM pb_publicidad_target t JOIN mtp_usuarios u ON u.id=t.usuario_id WHERE t.publicidad_id={$pid} AND t.tipo='USUARIO'";
      $parts[] = "SELECT ur.id_usuario AS id FROM pb_publicidad_target t JOIN mtp_usuario_roles ur ON ur.id_rol=t.rol_id WHERE t.publicidad_id={$pid} AND t.tipo='ROL'";
      $parts[] = "SELECT u.id FROM pb_publicidad_target t JOIN mtp_usuarios u ON u.id_empresa=t.empresa_id WHERE t.publicidad_id={$pid} AND t.tipo='EMPRESA'";
      $parts[] = "SELECT ur.id_usuario AS id FROM pb_publicidad_target t JOIN mtp_usuario_roles ur ON ur.id_rol=t.rol_id JOIN mtp_usuarios u ON u.id=ur.id_usuario AND u.id_empresa=t.empresa_id WHERE t.publicidad_id={$pid} AND t.tipo='EMPRESA_ROL'";

      $sqlC = "SELECT COUNT(DISTINCT id) c FROM (".implode(' UNION ALL ',$parts).") X";
      $total = (int)($mysqli->query($sqlC)->fetch_assoc()['c'] ?? 0);

      $sample = $mysqli->query("
        SELECT DISTINCT X.id
        FROM (".implode(' UNION ALL ',$parts).") X
        JOIN mtp_usuarios u ON u.id = X.id
        JOIN mtp_empresas e ON e.id = u.id_empresa
        ORDER BY u.nombres, u.apellidos
        LIMIT 10
      ")->fetch_all(MYSQLI_ASSOC);

      $out=[];
      if ($sample){
        $ids = array_map(fn($r)=>(int)$r['id'],$sample);
        $in  = implode(',', array_map('intval',$ids));
        $det = $mysqli->query("
          SELECT u.id, u.usuario, u.nombres, u.apellidos, e.nombre empresa
          FROM mtp_usuarios u JOIN mtp_empresas e ON e.id=u.id_empresa
          WHERE u.id IN ($in) ORDER BY u.nombres,u.apellidos
        ")->fetch_all(MYSQLI_ASSOC);
        $out=$det;
      }
      jok(['total'=>$total,'sample'=>$out]);
    }

    /* ======================
     * Asistencia para grupos
     * ====================== */
    case 'ads_search': { // para asignar a grupos (excluye ya asignados si se pasa grupo_id)
      $q        = trim($_GET['q'] ?? '');
      $grupo_id = (int)($_GET['grupo_id'] ?? 0);
      $limit    = max(1, min(50, (int)($_GET['limit'] ?? 20)));
      $where = "p.activo=1";
      $types=''; $pars=[];
      if ($q!==''){ $where.=" AND (p.titulo LIKE ? OR p.descripcion LIKE ?)"; $types.='ss'; $like="%$q%"; $pars[]=$like; $pars[]=$like; }
      if ($grupo_id>0){ $where.=" AND NOT EXISTS(SELECT 1 FROM pb_grupo_item gi WHERE gi.publicidad_id=p.id AND gi.grupo_id=?)"; $types.='i'; $pars[]=$grupo_id; }
      $sql = "SELECT p.id,p.titulo FROM pb_publicidades p WHERE $where ORDER BY p.titulo LIMIT ?";
      $types.='i'; $pars[]=$limit;
      $st=$mysqli->prepare($sql); if($types) $st->bind_param($types,...$pars); $st->execute();
      jok(['data'=>$st->get_result()->fetch_all(MYSQLI_ASSOC)]);
    }

    /* =============
     * CRUD de grupos
     * ============= */
    case 'group_create': {
      $nombre = trim($_POST['nombre'] ?? '');
      $slots  = (int)($_POST['layout_slots'] ?? 1);
      $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
      if ($nombre==='') jerror(400,'Nombre requerido');
      if ($slots<=0) $slots=1;
      $st=$mysqli->prepare("INSERT INTO pb_grupos (nombre,layout_slots,activo) VALUES (?,?,?)");
      $st->bind_param('sii',$nombre,$slots,$activo); $st->execute();
      jok(['id'=>$mysqli->insert_id]);
    }
    case 'group_update': {
      $id     = (int)($_POST['id'] ?? 0);
      $nombre = trim($_POST['nombre'] ?? '');
      $slots  = (int)($_POST['layout_slots'] ?? 1);
      if ($id<=0) jerror(400,'ID inválido');
      if ($nombre==='') jerror(400,'Nombre requerido');
      if ($slots<=0) $slots=1;
      $st=$mysqli->prepare("UPDATE pb_grupos SET nombre=?, layout_slots=? WHERE id=?");
      $st->bind_param('sii',$nombre,$slots,$id); $st->execute();
      jok(['id'=>$id]);
    }
    case 'group_set_activo': {
      $id=(int)($_POST['id'] ?? 0); $activo = ($_POST['activo'] ?? '')!=='' ? (int)$_POST['activo'] : null;
      if ($id<=0 || !in_array($activo,[0,1],true)) jerror(400,'Parámetros inválidos');
      $st=$mysqli->prepare("UPDATE pb_grupos SET activo=? WHERE id=?");
      $st->bind_param('ii',$activo,$id); $st->execute(); jok(['id'=>$id,'activo'=>$activo]);
    }
    case 'group_delete': {
      $id=(int)($_POST['id'] ?? 0); if ($id<=0) jerror(400,'ID inválido');
      $st=$mysqli->prepare("DELETE FROM pb_grupos WHERE id=?"); $st->bind_param('i',$id); $st->execute();
      if ($st->affected_rows===0) jerror(404,'No encontrado'); jok(['id'=>$id]);
    }
    case 'groups_list': {
      $q = trim($_GET['q'] ?? ''); $estado = $_GET['estado'] ?? '';
      $page = max(1, (int)($_GET['page'] ?? 1)); $perPage = max(1,min(50,(int)($_GET['per_page'] ?? 5))); $offset = ($page-1)*$perPage;
      $where=[]; $types=''; $pars=[];
      if ($q!==''){ $like="%$q%"; $where[]="g.nombre LIKE ? COLLATE utf8mb4_spanish_ci"; $types.='s'; $pars[]=$like; }
      if ($estado==='0' || $estado==='1'){ $where[]="g.activo=?"; $types.='i'; $pars[]=(int)$estado; }
      $W = $where?('WHERE '.implode(' AND ',$where)):'';
      $stC=$mysqli->prepare("SELECT COUNT(*) c FROM pb_grupos g $W"); if($types) $stC->bind_param($types,...$pars); $stC->execute();
      $total=(int)($stC->get_result()->fetch_assoc()['c']??0);
      $sql="SELECT g.id,g.nombre,g.layout_slots,g.activo,g.creado,g.actualizado
            FROM pb_grupos g $W ORDER BY g.id DESC LIMIT ? OFFSET ?";
      $types2=$types.'ii'; $pars2=$pars; $pars2[]=$perPage; $pars2[]=$offset;
      $st=$mysqli->prepare($sql); $st->bind_param($types2,...$pars2); $st->execute();
      $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
    }

    /* ===========================
     * Items (publicidades por grupo)
     * =========================== */
    case 'group_items_list': {
      $gid = (int)($_GET['grupo_id'] ?? 0);
      if ($gid<=0) jerror(400,'Grupo requerido');
      $rs = $mysqli->query("
        SELECT gi.id, gi.grupo_id, gi.publicidad_id, gi.orden,
               p.titulo, p.activo, p.imagen_path
        FROM pb_grupo_item gi
        JOIN pb_publicidades p ON p.id = gi.publicidad_id
        WHERE gi.grupo_id = {$gid}
        ORDER BY gi.orden ASC, gi.id ASC
      ");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }
    case 'group_item_add': {
      $gid = (int)($_POST['grupo_id'] ?? 0);
      $pid = (int)($_POST['publicidad_id'] ?? 0);
      if ($gid<=0 || $pid<=0) jerror(400,'Parámetros requeridos');

      // siguiente orden
      $mx = (int)($mysqli->query("SELECT COALESCE(MAX(orden),0) m FROM pb_grupo_item WHERE grupo_id={$gid}")->fetch_assoc()['m'] ?? 0);
      $ord = $mx + 1;

      $st = $mysqli->prepare("INSERT INTO pb_grupo_item (grupo_id,publicidad_id,orden) VALUES (?,?,?)");
      $st->bind_param('iii',$gid,$pid,$ord);
      try{ $st->execute(); } catch(mysqli_sql_exception $e){
        if ($e->getCode()==1062) jerror(409,'La publicidad ya está en el grupo');
        throw $e;
      }
      jok(['id'=>$mysqli->insert_id,'orden'=>$ord]);
    }
    case 'group_item_del': {
      $id=(int)($_POST['id'] ?? 0); if ($id<=0) jerror(400,'ID inválido');
      $st=$mysqli->prepare("DELETE FROM pb_grupo_item WHERE id=?"); $st->bind_param('i',$id); $st->execute();
      if ($st->affected_rows===0) jerror(404,'No encontrado'); jok(['id'=>$id]);
    }
    case 'group_item_move': {
      // mueve ↑/↓: dir in {'up','down'}
      $id = (int)($_POST['id'] ?? 0);
      $dir = $_POST['dir'] ?? '';
      if ($id<=0 || !in_array($dir,['up','down'],true)) jerror(400,'Parámetros inválidos');

      $row = $mysqli->query("SELECT id,grupo_id,orden FROM pb_grupo_item WHERE id={$id}")->fetch_assoc();
      if(!$row) jerror(404,'No encontrado');
      $gid = (int)$row['grupo_id']; $ord=(int)$row['orden'];

      if ($dir==='up'){
        $swap = $mysqli->query("SELECT id,orden FROM pb_grupo_item WHERE grupo_id={$gid} AND orden<{$ord} ORDER BY orden DESC LIMIT 1")->fetch_assoc();
      } else {
        $swap = $mysqli->query("SELECT id,orden FROM pb_grupo_item WHERE grupo_id={$gid} AND orden>{$ord} ORDER BY orden ASC LIMIT 1")->fetch_assoc();
      }
      if (!$swap) jok(['id'=>$id,'ok'=>true,'moved'=>false]); // ya está al extremo

      $sid = (int)$swap['id']; $sord=(int)$swap['orden'];
      $mysqli->begin_transaction();
      $mysqli->query("UPDATE pb_grupo_item SET orden={$sord} WHERE id={$id}");
      $mysqli->query("UPDATE pb_grupo_item SET orden={$ord} WHERE id={$sid}");
      $mysqli->commit();
      jok(['id'=>$id,'moved'=>true]);
    }

    /* ============================
     * Audiencias por Grupo + preview
     * ============================ */
    case 'group_targets_list': {
      $gid = (int)($_GET['grupo_id'] ?? 0);
      if ($gid<=0) jerror(400,'Grupo requerido');
      $rs = $mysqli->query("
        SELECT t.id, t.tipo, t.usuario_id, t.rol_id, t.empresa_id,
               u.usuario as u_usuario, CONCAT(u.nombres,' ',u.apellidos) as u_nombre,
               r.nombre as rol_nombre, e.nombre as emp_nombre
        FROM pb_grupo_target t
        LEFT JOIN mtp_usuarios u ON u.id=t.usuario_id
        LEFT JOIN mtp_roles r ON r.id=t.rol_id
        LEFT JOIN mtp_empresas e ON e.id=t.empresa_id
        WHERE t.grupo_id={$gid}
        ORDER BY t.id DESC
      ");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }
    case 'group_target_add': {
      $gid = (int)($_POST['grupo_id'] ?? 0);
      $tipo = trim($_POST['tipo'] ?? '');
      $usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
      $rol_id     = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : null;
      $empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
      if ($gid<=0) jerror(400,'Grupo requerido');
      $valid=['TODOS','USUARIO','ROL','EMPRESA','EMPRESA_ROL'];
      if(!in_array($tipo,$valid,true)) jerror(400,'Tipo inválido');
      if ($tipo==='USUARIO' && $usuario_id<=0) jerror(400,'Usuario requerido');
      if ($tipo==='ROL'     && $rol_id<=0)     jerror(400,'Rol requerido');
      if ($tipo==='EMPRESA' && $empresa_id<=0) jerror(400,'Empresa requerida');
      if ($tipo==='EMPRESA_ROL' && ($empresa_id<=0 || $rol_id<=0)) jerror(400,'Empresa y rol requeridos');

      $where="grupo_id=? AND tipo=?"; $types='is'; $pars=[$gid,$tipo];
      if ($tipo==='USUARIO'){ $where.=" AND usuario_id=?"; $types.='i'; $pars[]=$usuario_id; }
      if ($tipo==='ROL'){ $where.=" AND rol_id=?"; $types.='i'; $pars[]=$rol_id; }
      if ($tipo==='EMPRESA'){ $where.=" AND empresa_id=?"; $types.='i'; $pars[]=$empresa_id; }
      if ($tipo==='EMPRESA_ROL'){ $where.=" AND empresa_id=? AND rol_id=?"; $types.='ii'; $pars[]=$empresa_id; $pars[]=$rol_id; }

      $st=$mysqli->prepare("SELECT COUNT(*) c FROM pb_grupo_target WHERE $where");
      $st->bind_param($types, ...$pars); $st->execute();
      if (((int)$st->get_result()->fetch_assoc()['c'])>0) jerror(409,'La regla ya existe');

      $stI=$mysqli->prepare("INSERT INTO pb_grupo_target (grupo_id,tipo,usuario_id,rol_id,empresa_id) VALUES (?,?,?,?,?)");
      $u=$usuario_id>0?$usuario_id:null; $r=$rol_id>0?$rol_id:null; $e=$empresa_id>0?$empresa_id:null;
      $stI->bind_param('isiii',$gid,$tipo,$u,$r,$e); $stI->execute();
      jok(['id'=>$mysqli->insert_id]);
    }
    case 'group_target_del': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $st=$mysqli->prepare("DELETE FROM pb_grupo_target WHERE id=?");
      $st->bind_param('i',$id); $st->execute();
      if ($st->affected_rows===0) jerror(404,'No encontrado');
      jok(['id'=>$id]);
    }
    case 'group_preview': {
      $gid = (int)($_GET['grupo_id'] ?? 0);
      if ($gid<=0) jerror(400,'Grupo requerido');

      $hasAll = $mysqli->query("SELECT 1 FROM pb_grupo_target WHERE grupo_id={$gid} AND tipo='TODOS' LIMIT 1")->fetch_row();
      if ($hasAll){ $rs=$mysqli->query("SELECT COUNT(*) c FROM mtp_usuarios")->fetch_assoc(); jok(['total'=>(int)$rs['c'],'sample'=>[]]); }

      $parts=[];
      $parts[] = "SELECT u.id FROM pb_grupo_target t JOIN mtp_usuarios u ON u.id=t.usuario_id WHERE t.grupo_id={$gid} AND t.tipo='USUARIO'";
      $parts[] = "SELECT ur.id_usuario AS id FROM pb_grupo_target t JOIN mtp_usuario_roles ur ON ur.id_rol=t.rol_id WHERE t.grupo_id={$gid} AND t.tipo='ROL'";
      $parts[] = "SELECT u.id FROM pb_grupo_target t JOIN mtp_usuarios u ON u.id_empresa=t.empresa_id WHERE t.grupo_id={$gid} AND t.tipo='EMPRESA'";
      $parts[] = "SELECT ur.id_usuario AS id FROM pb_grupo_target t JOIN mtp_usuario_roles ur ON ur.id_rol=t.rol_id JOIN mtp_usuarios u ON u.id=ur.id_usuario AND u.id_empresa=t.empresa_id WHERE t.grupo_id={$gid} AND t.tipo='EMPRESA_ROL'";

      $sqlC = "SELECT COUNT(DISTINCT id) c FROM (".implode(' UNION ALL ',$parts).") X";
      $total = (int)($mysqli->query($sqlC)->fetch_assoc()['c'] ?? 0);

      $sample = $mysqli->query("
        SELECT DISTINCT X.id
        FROM (".implode(' UNION ALL ',$parts).") X
        JOIN mtp_usuarios u ON u.id = X.id
        JOIN mtp_empresas e ON e.id = u.id_empresa
        ORDER BY u.nombres, u.apellidos
        LIMIT 10
      ")->fetch_all(MYSQLI_ASSOC);

      $out=[];
      if ($sample){
        $ids = array_map(fn($r)=>(int)$r['id'],$sample);
        $in  = implode(',', array_map('intval',$ids));
        $det = $mysqli->query("
          SELECT u.id, u.usuario, u.nombres, u.apellidos, e.nombre empresa
          FROM mtp_usuarios u JOIN mtp_empresas e ON e.id=u.id_empresa
          WHERE u.id IN ($in) ORDER BY u.nombres,u.apellidos
        ")->fetch_all(MYSQLI_ASSOC);
        $out=$det;
      }
      jok(['total'=>$total,'sample'=>$out]);
    }

    default: jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e){
  if ($mysqli->errno) $mysqli->rollback();
  if ($e->getCode()==1062) jerror(409,'Registro duplicado');
  jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
}
