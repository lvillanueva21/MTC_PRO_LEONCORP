<?php
// modules/consola/cursos/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db(); $db->set_charset('utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jerror($code,$msg,$extra=[]){ http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($a=[]){ echo json_encode(['ok'=>true]+$a); exit; }

function slugify($s){
  $s=trim($s); $s=mb_strtolower($s,'UTF-8');
  $s=strtr($s,['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n']);
  $s=preg_replace('~[^a-z0-9]+~u','-',$s); return trim(preg_replace('~-+~','-',$s),'-');
}

try{
  switch($action){

  // ----------------------- Cursos (CRUD básico) -----------------------
  case 'create': {
    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $csv    = trim($_POST['etiquetas'] ?? '');

    if ($nombre==='') jerror(400,'El nombre es obligatorio');
    if (mb_strlen($nombre)>150) jerror(400,'Nombre demasiado largo');

    // tags CSV -> array único
    $tags = [];
    if ($csv!==''){
      foreach (explode(',',$csv) as $t){ $t=trim($t); if ($t!=='') $tags[$t]=true; }
      $tags=array_keys($tags);
    }

    $dirAbs = __DIR__.'/../../../almacen/img_cursos';
    $dirRel = 'almacen/img_cursos';
    if (!is_dir($dirAbs)) @mkdir($dirAbs,0775,true);

    $db->begin_transaction();

    // Insert base (sin imagen)
    $st=$db->prepare("INSERT INTO cr_cursos (nombre, descripcion, activo, imagen_path) VALUES (?,?,1,NULL)");
    $st->bind_param('ss',$nombre,$desc); $st->execute();
    $cid=(int)$db->insert_id;

    // Imagen opcional
    $ruta=null;
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error']!==UPLOAD_ERR_NO_FILE){
      if ($_FILES['imagen']['error']!==UPLOAD_ERR_OK) jerror(400,'Error al subir la imagen');
      if ($_FILES['imagen']['size']>5*1024*1024) jerror(400,'La imagen excede 5MB');
      $tmp=$_FILES['imagen']['tmp_name'];
      $fi=new finfo(FILEINFO_MIME_TYPE); $mime=$fi->file($tmp);
      $exts=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; if(!isset($exts[$mime])) jerror(400,'Formato no permitido (PNG/JPG/WebP)');
      $ext=$exts[$mime];
      $slug=slugify($nombre); $stamp=date('Ymd'); $cidP=str_pad((string)$cid,6,'0',STR_PAD_LEFT);
      $file="{$stamp}-{$slug}-cur{$cidP}.{$ext}";
      foreach(glob($dirAbs."/*-cur{$cidP}.*") as $old){ @unlink($old); }
      if(!move_uploaded_file($tmp,$dirAbs.'/'.$file)) jerror(500,'No se pudo guardar la imagen');
      $ruta=$dirRel.'/'.$file;
      $up=$db->prepare("UPDATE cr_cursos SET imagen_path=? WHERE id=?"); $up->bind_param('si',$ruta,$cid); $up->execute();
    }

    // Etiquetas
    if ($tags){
      $sel=$db->prepare("SELECT id FROM cr_etiquetas WHERE nombre=?");
      $ins=$db->prepare("INSERT INTO cr_etiquetas (nombre) VALUES (?)");
      $pv =$db->prepare("INSERT IGNORE INTO cr_curso_etiqueta (curso_id, etiqueta_id) VALUES (?,?)");
      foreach($tags as $t){
        $sel->bind_param('s',$t); $sel->execute();
        $rid=$sel->get_result()->fetch_assoc()['id']??null;
        if(!$rid){ $ins->bind_param('s',$t); $ins->execute(); $rid=(int)$db->insert_id; }
        $pv->bind_param('ii',$cid,$rid); $pv->execute();
      }
    }

    $db->commit();
    jok(['id'=>$cid,'imagen'=>$ruta,'tags'=>count($tags)]);
  }

  case 'list': {
    $q       = trim($_GET['q'] ?? '');
    $estado  = $_GET['estado'] ?? '';
    $page    = max(1,(int)($_GET['page'] ?? 1));
    $perPage = max(1,min(50,(int)($_GET['per_page'] ?? 5)));
    $offset  = ($page-1)*$perPage;

    $where=[]; $types=''; $pars=[];
    if ($q!==''){
      $like="%$q%";
      $where[]="(c.nombre LIKE ? COLLATE utf8mb4_spanish_ci OR c.descripcion LIKE ? COLLATE utf8mb4_spanish_ci)";
      $types.='ss'; $pars[]=$like; $pars[]=$like;
    }
    if ($estado==='0' || $estado==='1'){ $where[]="c.activo=?"; $types.='i'; $pars[]=(int)$estado; }
    $W = $where ? 'WHERE '.implode(' AND ',$where) : '';

    // total
    $stC=$db->prepare("SELECT COUNT(*) c FROM cr_cursos c $W");
    if ($types) $stC->bind_param($types,...$pars);
    $stC->execute(); $total=(int)$stC->get_result()->fetch_assoc()['c'];

    // datos
    $sqlD="SELECT c.id, c.nombre, c.descripcion, c.activo, c.imagen_path, c.creado, c.actualizado
           FROM cr_cursos c
           $W
           ORDER BY c.id ASC
           LIMIT ? OFFSET ?";
    $types2=$types.'ii'; $pars2=$pars; $pars2[]=$perPage; $pars2[]=$offset;
    $stD=$db->prepare($sqlD); $stD->bind_param($types2,...$pars2); $stD->execute();
    $rows=$stD->get_result()->fetch_all(MYSQLI_ASSOC);

    // etiquetas
    $ids=array_column($rows,'id'); $tagMap=[];
    if ($ids){
      $place=implode(',',array_fill(0,count($ids),'?')); $tTypes=str_repeat('i',count($ids));
      $tSql="SELECT cce.curso_id cid, e.nombre tag
             FROM cr_curso_etiqueta cce
             JOIN cr_etiquetas e ON e.id=cce.etiqueta_id
             WHERE cce.curso_id IN ($place) ORDER BY e.nombre";
      $stT=$db->prepare($tSql); $stT->bind_param($tTypes,...$ids); $stT->execute();
      $rt=$stT->get_result(); while($r=$rt->fetch_assoc()){ $tagMap[(int)$r['cid']][]=$r['tag']; }
    }
    foreach($rows as &$r){ $r['tags']=$tagMap[$r['id']] ?? []; }

    jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
  }

  case 'update': {
    $id     = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $csv    = trim($_POST['etiquetas'] ?? '');

    if ($id<=0) jerror(400,'ID inválido');
    if ($nombre==='') jerror(400,'El nombre es obligatorio');
    if (mb_strlen($nombre)>150) jerror(400,'Nombre demasiado largo');

    // CSV -> array
    $tags = null; // null = no tocar. array = reemplazar
    if ($csv!==''){
      $tmp=[]; foreach(explode(',',$csv) as $t){ $t=trim($t); if($t!=='') $tmp[$t]=true; }
      $tags=array_values(array_keys($tmp));
    }

    $dirAbs=__DIR__.'/../../../almacen/img_cursos';
    $dirRel='almacen/img_cursos';
    if (!is_dir($dirAbs)) @mkdir($dirAbs,0775,true);

    $db->begin_transaction();

    // 1) nombre/desc
    $st=$db->prepare("UPDATE cr_cursos SET nombre=?, descripcion=? WHERE id=?");
    $st->bind_param('ssi',$nombre,$desc,$id); $st->execute();

    // 2) reemplazo de imagen (opcional)
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error']!==UPLOAD_ERR_NO_FILE){
      if ($_FILES['imagen']['error']!==UPLOAD_ERR_OK) jerror(400,'Error al subir la imagen');
      if ($_FILES['imagen']['size']>5*1024*1024) jerror(400,'La imagen excede 5MB');
      $tmp=$_FILES['imagen']['tmp_name'];
      $fi=new finfo(FILEINFO_MIME_TYPE); $mime=$fi->file($tmp);
      $exts=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; if(!isset($exts[$mime])) jerror(400,'Formato no permitido (PNG/JPG/WebP)');
      $ext=$exts[$mime];
      $idP=str_pad((string)$id,6,'0',STR_PAD_LEFT);
      foreach(glob($dirAbs."/*-cur{$idP}.*") as $old){ @unlink($old); }
      $slug=slugify($nombre); $stamp=date('Ymd'); $file="{$stamp}-{$slug}-cur{$idP}.{$ext}";
      if(!move_uploaded_file($tmp,$dirAbs.'/'.$file)) jerror(500,'No se pudo guardar la imagen');
      $ruta=$dirRel.'/'.$file; $up=$db->prepare("UPDATE cr_cursos SET imagen_path=? WHERE id=?"); $up->bind_param('si',$ruta,$id); $up->execute();
    }

    // 3) reemplazar etiquetas si mandaron CSV
    if (is_array($tags)){
      $del=$db->prepare("DELETE FROM cr_curso_etiqueta WHERE curso_id=?"); $del->bind_param('i',$id); $del->execute();
      if ($tags){
        $sel=$db->prepare("SELECT id FROM cr_etiquetas WHERE nombre=?");
        $ins=$db->prepare("INSERT INTO cr_etiquetas (nombre) VALUES (?)");
        $pv =$db->prepare("INSERT IGNORE INTO cr_curso_etiqueta (curso_id, etiqueta_id) VALUES (?,?)");
        foreach($tags as $t){
          $sel->bind_param('s',$t); $sel->execute();
          $rid=$sel->get_result()->fetch_assoc()['id']??null;
          if(!$rid){ $ins->bind_param('s',$t); $ins->execute(); $rid=(int)$db->insert_id; }
          $pv->bind_param('ii',$id,$rid); $pv->execute();
        }
      }
    }

    $db->commit();
    jok(['id'=>$id]);
  }

  case 'set_activo': {
    $id=(int)($_POST['id'] ?? 0);
    $activo=($_POST['activo'] ?? '')!=='' ? (int)$_POST['activo'] : null;
    if ($id<=0 || !in_array($activo,[0,1],true)) jerror(400,'Parámetros inválidos');
    $st=$db->prepare("UPDATE cr_cursos SET activo=? WHERE id=?"); $st->bind_param('ii',$activo,$id); $st->execute();
    if ($st->affected_rows<0) jerror(500,'No se pudo actualizar el estado');
    jok(['id'=>$id,'activo'=>$activo]);
  }

  // ----------------------- Temas -----------------------
  case 'temas_list': {
    $curso_id=(int)($_GET['curso_id'] ?? 0);
    if ($curso_id<=0) jerror(400,'Curso requerido');
    $st=$db->prepare("SELECT id, titulo, clase, video_url, miniatura_path, creado, actualizado
                      FROM cr_temas
                      WHERE curso_id=?
                      ORDER BY id ASC");
    $st->bind_param('i',$curso_id); $st->execute();
    $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);
    jok(['data'=>$rows]);
  }

  case 'tema_create': {
    $curso_id=(int)($_POST['curso_id'] ?? 0);
    $titulo=trim($_POST['titulo'] ?? '');
    $clase =trim($_POST['clase'] ?? '');
    $video =trim($_POST['video_url'] ?? '');
    if ($curso_id<=0) jerror(400,'Curso requerido');
    if ($titulo==='') jerror(400,'El título es obligatorio');

    $dirAbs=__DIR__.'/../../../almacen/img_temas';
    $dirRel='almacen/img_temas';
    if (!is_dir($dirAbs)) @mkdir($dirAbs,0775,true);

    $db->begin_transaction();
    $st=$db->prepare("INSERT INTO cr_temas (curso_id, titulo, clase, video_url, miniatura_path) VALUES (?,?,?,?,NULL)");
    $st->bind_param('isss',$curso_id,$titulo,$clase,$video); $st->execute();
    $tid=(int)$db->insert_id;

    // miniatura opcional
    $ruta=null;
    if (!empty($_FILES['miniatura']) && $_FILES['miniatura']['error']!==UPLOAD_ERR_NO_FILE){
      if ($_FILES['miniatura']['error']!==UPLOAD_ERR_OK) jerror(400,'Error al subir la miniatura');
      if ($_FILES['miniatura']['size']>5*1024*1024) jerror(400,'La miniatura excede 5MB');
      $tmp=$_FILES['miniatura']['tmp_name'];
      $fi=new finfo(FILEINFO_MIME_TYPE); $mime=$fi->file($tmp);
      $exts=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; if(!isset($exts[$mime])) jerror(400,'Formato no permitido (PNG/JPG/WebP)');
      $ext=$exts[$mime];
      $stamp=date('Ymd'); $tidP=str_pad((string)$tid,6,'0',STR_PAD_LEFT);
      $file="{$stamp}-tema-tem{$tidP}.{$ext}";
      foreach(glob($dirAbs."/*-tem{$tidP}.*") as $old){ @unlink($old); }
      if(!move_uploaded_file($tmp,$dirAbs.'/'.$file)) jerror(500,'No se pudo guardar la miniatura');
      $ruta=$dirRel.'/'.$file;
      $up=$db->prepare("UPDATE cr_temas SET miniatura_path=? WHERE id=?"); $up->bind_param('si',$ruta,$tid); $up->execute();
    }
    $db->commit();
    jok(['id'=>$tid,'miniatura'=>$ruta]);
  }

  case 'tema_update': {
    $id=(int)($_POST['id'] ?? 0);
    $curso_id=(int)($_POST['curso_id'] ?? 0);
    $titulo=trim($_POST['titulo'] ?? '');
    $clase =trim($_POST['clase'] ?? '');
    $video =trim($_POST['video_url'] ?? '');
    if ($id<=0 || $curso_id<=0) jerror(400,'Parámetros inválidos');
    if ($titulo==='') jerror(400,'El título es obligatorio');

    $dirAbs=__DIR__.'/../../../almacen/img_temas';
    $dirRel='almacen/img_temas';
    if (!is_dir($dirAbs)) @mkdir($dirAbs,0775,true);

    $db->begin_transaction();
    $st=$db->prepare("UPDATE cr_temas SET titulo=?, clase=?, video_url=? WHERE id=? AND curso_id=?");
    $st->bind_param('sssii',$titulo,$clase,$video,$id,$curso_id); $st->execute();

    if (!empty($_FILES['miniatura']) && $_FILES['miniatura']['error']!==UPLOAD_ERR_NO_FILE){
      if ($_FILES['miniatura']['error']!==UPLOAD_ERR_OK) jerror(400,'Error al subir la miniatura');
      if ($_FILES['miniatura']['size']>5*1024*1024) jerror(400,'La miniatura excede 5MB');
      $tmp=$_FILES['miniatura']['tmp_name'];
      $fi=new finfo(FILEINFO_MIME_TYPE); $mime=$fi->file($tmp);
      $exts=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; if(!isset($exts[$mime])) jerror(400,'Formato no permitido (PNG/JPG/WebP)');
      $ext=$exts[$mime];
      $idP=str_pad((string)$id,6,'0',STR_PAD_LEFT);
      foreach(glob($dirAbs."/*-tem{$idP}.*") as $old){ @unlink($old); }
      $stamp=date('Ymd'); $file="{$stamp}-tema-tem{$idP}.{$ext}";
      if(!move_uploaded_file($tmp,$dirAbs.'/'.$file)) jerror(500,'No se pudo guardar la miniatura');
      $ruta=$dirRel.'/'.$file; $up=$db->prepare("UPDATE cr_temas SET miniatura_path=? WHERE id=?"); $up->bind_param('si',$ruta,$id); $up->execute();
    }

    $db->commit();
    jok(['id'=>$id]);
  }

  case 'tema_delete': {
    $id=(int)($_POST['id'] ?? 0);
    if ($id<=0) jerror(400,'ID inválido');
    // limpiar archivo
    $dirAbs=__DIR__.'/../../../almacen/img_temas';
    $idP=str_pad((string)$id,6,'0',STR_PAD_LEFT);
    foreach(glob($dirAbs."/*-tem{$idP}.*") as $old){ @unlink($old); }
    $st=$db->prepare("DELETE FROM cr_temas WHERE id=?"); $st->bind_param('i',$id); $st->execute();
    jok(['id'=>$id]);
  }

    // ----------------------- Asignación de cursos a usuarios -----------------------
  // Lista solo los cursos asignados a un usuario (compatibles con el JS: {data:[...]})
  case 'usuario_cursos_list': {
    $usuario_id = (int)($_GET['usuario_id'] ?? 0);
    if ($usuario_id <= 0) jerror(400, 'usuario_id requerido');

    $st = $db->prepare(
      "SELECT c.id, c.nombre, c.imagen_path, c.activo
       FROM cr_usuario_curso uc
       JOIN cr_cursos c ON c.id = uc.curso_id
       WHERE uc.usuario_id = ?
       ORDER BY c.nombre ASC"
    );
    $st->bind_param('i', $usuario_id);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    // Respuesta esperada por gestion.js: r.data
    jok(['data' => $rows]);
  }

    // Agregar curso a usuario (sin columnas adicionales)
  case 'usuario_curso_add': {
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    $curso_id   = (int)($_POST['curso_id'] ?? 0);
    if ($usuario_id <= 0 || $curso_id <= 0) jerror(400, 'Parámetros requeridos');

    // validar curso
    $st = $db->prepare("SELECT id FROM cr_cursos WHERE id=?");
    $st->bind_param('i', $curso_id); $st->execute();
    if (!$st->get_result()->fetch_assoc()) jerror(404, 'Curso no existe');

    // insertar (idempotente)
    $ins = $db->prepare("INSERT IGNORE INTO cr_usuario_curso (usuario_id, curso_id) VALUES (?, ?)");
    $ins->bind_param('ii', $usuario_id, $curso_id);
    $ins->execute();

    jok(['usuario_id' => $usuario_id, 'curso_id' => $curso_id, 'inserted' => $ins->affected_rows > 0 ? 1 : 0]);
  }

  // Quitar curso a usuario
  case 'usuario_curso_remove': {
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    $curso_id   = (int)($_POST['curso_id'] ?? 0);
    if ($usuario_id<=0 || $curso_id<=0) jerror(400,'Parámetros requeridos');

    $del = $db->prepare("DELETE FROM cr_usuario_curso WHERE usuario_id=? AND curso_id=?");
    $del->bind_param('ii',$usuario_id,$curso_id); $del->execute();

    jok(['usuario_id'=>$usuario_id,'curso_id'=>$curso_id,'eliminado'=>true]);
  }

   // Listado de usuarios con al menos un curso (sin columnas inexistentes)
  case 'usuarios_con_cursos_list': {
    $empresa  = (int)($_GET['empresa'] ?? 0);
    $curso_id = (int)($_GET['curso_id'] ?? 0);
    $q        = trim($_GET['q'] ?? '');
    $page     = max(1,(int)($_GET['page'] ?? 1));
    $perPage  = max(1,min(50,(int)($_GET['per_page'] ?? 5)));
    $offset   = ($page-1)*$perPage;

    $where=[]; $types=''; $pars=[];
    $from ="FROM mtp_usuarios u
            JOIN mtp_empresas e ON e.id=u.id_empresa
            JOIN cr_usuario_curso uc ON uc.usuario_id=u.id";
    if ($empresa>0){ $where[]='u.id_empresa=?'; $types.='i'; $pars[]=$empresa; }
    if ($curso_id>0){ $where[]='uc.curso_id=?'; $types.='i'; $pars[]=$curso_id; }
    if ($q!==''){
      $like="%$q%";
      $where[]='(u.usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)';
      $types.='sss'; array_push($pars,$like,$like,$like);
    }
    $W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

    // total (distinct usuarios)
    $sqlC="SELECT COUNT(DISTINCT u.id) c $from $W";
    $stC=$db->prepare($sqlC); if($types) $stC->bind_param($types,...$pars); $stC->execute();
    $total=(int)$stC->get_result()->fetch_assoc()['c'];

    // page de usuarios
    $sql="SELECT u.id, u.usuario, u.nombres, u.apellidos, u.id_empresa, e.nombre empresa,
                 COUNT(DISTINCT uc.curso_id) cursos_count
          $from
          $W
          GROUP BY u.id, u.usuario, u.nombres, u.apellidos, u.id_empresa, e.nombre
          ORDER BY u.id DESC
          LIMIT ? OFFSET ?";
    $types2=$types.'ii'; $pars2=$pars; $pars2[]=$perPage; $pars2[]=$offset;
    $st=$db->prepare($sql); if($types2) $st->bind_param($types2,...$pars2); $st->execute();
    $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);

    // cursos por cada usuario listado (para uso futuro; opcional)
    $ids=array_column($rows,'id');
    $map=[]; if($ids){
      $place=implode(',',array_fill(0,count($ids),'?')); $t=str_repeat('i',count($ids));
      $sq="SELECT uc.usuario_id uid, c.id cid, c.nombre
           FROM cr_usuario_curso uc
           JOIN cr_cursos c ON c.id=uc.curso_id
           WHERE uc.usuario_id IN ($place)
           ORDER BY c.nombre";
      $st2=$db->prepare($sq); $st2->bind_param($t, ...$ids); $st2->execute();
      $rs=$st2->get_result();
      while($r=$rs->fetch_assoc()){ $map[(int)$r['uid']][]=['id'=>(int)$r['cid'],'nombre'=>$r['nombre']]; }
    }
    foreach($rows as &$r){ $r['cursos']=$map[(int)$r['id']] ?? []; }

    jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
  }

  default:
    jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  if ($db->errno) { $db->rollback(); }
  if ($e->getCode()==1062) jerror(409,'Registro duplicado');
  if ($e->getCode()==1451) jerror(409,'No se puede eliminar: registro en uso');
  jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
}
