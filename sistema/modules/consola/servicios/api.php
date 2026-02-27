<?php
// modules/consola/servicios/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$mysqli = db();
$mysqli->set_charset('utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jerror($code, $msg, $extra = []) {
  http_response_code($code);
  echo json_encode(['ok'=>false, 'msg'=>$msg] + $extra);
  exit;
}
function jok($arr = []) { echo json_encode(['ok'=>true] + $arr); exit; }

function slugify($s) {
  $s = trim($s);
  $s = mb_strtolower($s, 'UTF-8');
  $s = strtr($s, [
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
    'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n',
  ]);
  $s = preg_replace('~[^a-z0-9]+~u', '-', $s);
  return trim(preg_replace('~-+~', '-', $s), '-');
}

try {
  switch ($action) {

  case 'create': {
    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $csv    = trim($_POST['etiquetas'] ?? '');

    if ($nombre === '') jerror(400, 'El nombre es obligatorio');
    if (mb_strlen($nombre) > 150) jerror(400, 'Nombre demasiado largo');

    // Normalizar etiquetas (CSV -> array único)
    $tags = [];
    if ($csv !== '') {
      foreach (explode(',', $csv) as $t) {
        $t = trim($t);
        if ($t !== '') $tags[$t] = true;
      }
      $tags = array_keys($tags);
    }

    // Almacén
    $dirAbs = __DIR__ . '/../../../almacen/img_servicios';
    $dirRel = 'almacen/img_servicios';
    if (!is_dir($dirAbs)) @mkdir($dirAbs, 0775, true);

    $mysqli->begin_transaction();

    // 1) Insertar servicio (sin imagen)
    $st = $mysqli->prepare("INSERT INTO mod_servicios (nombre, descripcion, activo, imagen_path) VALUES (?, ?, 1, NULL)");
    $st->bind_param('ss', $nombre, $desc);
    $st->execute();
    $sid = (int)$mysqli->insert_id;

    // 2) Imagen opcional
    $ruta = null;
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
      if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) jerror(400, 'Error al subir la imagen');
      if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) jerror(400, 'La imagen excede 5MB');

      $tmp  = $_FILES['imagen']['tmp_name'];
      $fi   = new finfo(FILEINFO_MIME_TYPE);
      $mime = $fi->file($tmp);
      $exts = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      if (!isset($exts[$mime])) jerror(400, 'Formato no permitido (PNG/JPG/WebP)');
      $ext  = $exts[$mime];

      $slug  = slugify($nombre);
      $stamp = date('Ymd');
      $sidP  = str_pad((string)$sid, 6, '0', STR_PAD_LEFT);
      $file  = "{$stamp}-{$slug}-srv{$sidP}.{$ext}";

      // Solo 1 imagen por servicio
      foreach (glob($dirAbs."/*-srv{$sidP}.*") as $old) { @unlink($old); }

      if (!move_uploaded_file($tmp, $dirAbs . '/' . $file)) jerror(500, 'No se pudo guardar la imagen');
      $ruta = $dirRel . '/' . $file;

      $up = $mysqli->prepare("UPDATE mod_servicios SET imagen_path=? WHERE id=?");
      $up->bind_param('si', $ruta, $sid);
      $up->execute();
    }

    // 3) Etiquetas: crear si no existen y vincular
    if ($tags) {
      $sel = $mysqli->prepare("SELECT id FROM mod_etiquetas WHERE nombre=?");
      $ins = $mysqli->prepare("INSERT INTO mod_etiquetas (nombre) VALUES (?)");
      $pv  = $mysqli->prepare("INSERT IGNORE INTO mod_servicio_etiqueta (servicio_id, etiqueta_id) VALUES (?,?)");

      foreach ($tags as $t) {
        $sel->bind_param('s', $t);
        $sel->execute();
        $rid = $sel->get_result()->fetch_assoc()['id'] ?? null;
        if (!$rid) { $ins->bind_param('s', $t); $ins->execute(); $rid = (int)$mysqli->insert_id; }
        $pv->bind_param('ii', $sid, $rid);
        $pv->execute();
      }
    }

    $mysqli->commit();
    jok(['id'=>$sid, 'imagen'=>$ruta, 'tags'=>count($tags)]);
  }

  case 'empresas': { // combo de empresas para el filtro
    $rs = $mysqli->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre");
    jok(['data' => $rs->fetch_all(MYSQLI_ASSOC)]);
  }

  case 'list': { // listar con filtros y paginación (5 por página por defecto)
  $empresa = (int)($_GET['empresa'] ?? 0);      // 0 = todas
  $q       = trim($_GET['q'] ?? '');
  $estado  = $_GET['estado'] ?? '';             // '', '1', '0'
  $page    = max(1, (int)($_GET['page'] ?? 1));
  $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 5)));
  $offset  = ($page - 1) * $perPage;

  $where = [];
  $types = '';
  $pars  = [];

  if ($q !== '') {
    $like = "%$q%";
    $where[] = "(s.nombre LIKE ? COLLATE utf8mb4_spanish_ci OR s.descripcion LIKE ? COLLATE utf8mb4_spanish_ci)";
    $types .= 'ss'; $pars[] = $like; $pars[] = $like;
  }
  if ($estado === '0' || $estado === '1') {
    $where[] = "s.activo = ?"; $types .= 'i'; $pars[] = (int)$estado;
  }
  if ($empresa > 0) {
    $where[] = "EXISTS (SELECT 1 FROM mod_empresa_servicio mes WHERE mes.servicio_id = s.id AND mes.empresa_id = ?)";
    $types .= 'i'; $pars[] = $empresa;
  }
  $W = $where ? 'WHERE '.implode(' AND ', $where) : '';

  // total
  $stC = $mysqli->prepare("SELECT COUNT(*) c FROM mod_servicios s $W");
  if ($types) $stC->bind_param($types, ...$pars);
  $stC->execute();
  $total = (int)$stC->get_result()->fetch_assoc()['c'];

  // datos
  $sqlD = "SELECT s.id, s.nombre, s.descripcion, s.activo, s.imagen_path, s.creado, s.actualizado
           FROM mod_servicios s
           $W
           ORDER BY s.id ASC
           LIMIT ? OFFSET ?";
  $stD = $mysqli->prepare($sqlD);
  $types2 = $types.'ii';
  $pars2  = $pars; $pars2[] = $perPage; $pars2[] = $offset;
  $stD->bind_param($types2, ...$pars2);
  $stD->execute();
  $rows = $stD->get_result()->fetch_all(MYSQLI_ASSOC);

  // ---- etiquetas por servicio (map) ----
  $ids = array_column($rows, 'id');
  $tagMap = [];
  if ($ids) {
    $place = implode(',', array_fill(0, count($ids), '?'));
    $tSql  = "SELECT mse.servicio_id sid, e.nombre tag
              FROM mod_servicio_etiqueta mse
              JOIN mod_etiquetas e ON e.id = mse.etiqueta_id
              WHERE mse.servicio_id IN ($place)
              ORDER BY e.nombre";
    $tTypes = str_repeat('i', count($ids));
    $stT = $mysqli->prepare($tSql);
    $stT->bind_param($tTypes, ...$ids);
    $stT->execute();
    $rt = $stT->get_result();
    while ($r = $rt->fetch_assoc()) {
      $sid = (int)$r['sid'];
      $tagMap[$sid][] = $r['tag'];
    }
  }
  foreach ($rows as &$r) { $r['tags'] = $tagMap[$r['id']] ?? []; }

  jok(['data'=>$rows, 'total'=>$total, 'page'=>$page, 'per_page'=>$perPage]);
}

case 'update': {
  $id     = (int)($_POST['id'] ?? 0);
  $nombre = trim($_POST['nombre'] ?? '');
  $desc   = trim($_POST['descripcion'] ?? '');
  $csv    = trim($_POST['etiquetas'] ?? '');

  if ($id <= 0) jerror(400, 'ID inválido');
  if ($nombre === '') jerror(400, 'El nombre es obligatorio');
  if (mb_strlen($nombre) > 150) jerror(400, 'Nombre demasiado largo');

  // Normalizar etiquetas CSV -> array único
  $tags = [];
  if ($csv !== '') {
    foreach (explode(',', $csv) as $t) {
      $t = trim($t);
      if ($t !== '') $tags[$t] = true;
    }
    $tags = array_values(array_keys($tags));
  }

  // Paths
  $dirAbs = __DIR__ . '/../../../almacen/img_servicios';
  $dirRel = 'almacen/img_servicios';
  if (!is_dir($dirAbs)) @mkdir($dirAbs, 0775, true);

  $mysqli->begin_transaction();

  // 1) Actualizar nombre y descripción
  $st = $mysqli->prepare("UPDATE mod_servicios SET nombre=?, descripcion=? WHERE id=?");
  $st->bind_param('ssi', $nombre, $desc, $id);
  $st->execute();

  // 2) Si suben nueva imagen => reemplazar física y en DB
  if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) jerror(400, 'Error al subir la imagen');
    if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) jerror(400, 'La imagen excede 5MB');

    $tmp  = $_FILES['imagen']['tmp_name'];
    $fi   = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($tmp);
    $exts = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($exts[$mime])) jerror(400, 'Formato no permitido (PNG/JPG/WebP)');
    $ext  = $exts[$mime];

    // Borrar cualquier imagen previa del servicio
    $sidP = str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    foreach (glob($dirAbs."/*-srv{$sidP}.*") as $old) { @unlink($old); }

    // Nuevo nombre de archivo (usa fecha+slug+srvID)
    $slug  = slugify($nombre);
    $stamp = date('Ymd');
    $file  = "{$stamp}-{$slug}-srv{$sidP}.{$ext}";

    if (!move_uploaded_file($tmp, $dirAbs . '/' . $file)) jerror(500, 'No se pudo guardar la imagen');
    $ruta = $dirRel . '/' . $file;

    $up = $mysqli->prepare("UPDATE mod_servicios SET imagen_path=? WHERE id=?");
    $up->bind_param('si', $ruta, $id);
    $up->execute();
  }

  // 3) Reemplazar etiquetas por las nuevas (si mandaron)
  //    Nota: si no mandan nada, se dejan como están.
  if ($csv !== '') {
    // Eliminar pivotes actuales
    $del = $mysqli->prepare("DELETE FROM mod_servicio_etiqueta WHERE servicio_id=?");
    $del->bind_param('i', $id);
    $del->execute();

    if ($tags) {
      $sel = $mysqli->prepare("SELECT id FROM mod_etiquetas WHERE nombre=?");
      $ins = $mysqli->prepare("INSERT INTO mod_etiquetas (nombre) VALUES (?)");
      $pv  = $mysqli->prepare("INSERT IGNORE INTO mod_servicio_etiqueta (servicio_id, etiqueta_id) VALUES (?,?)");

      foreach ($tags as $t) {
        $sel->bind_param('s', $t);
        $sel->execute();
        $rid = $sel->get_result()->fetch_assoc()['id'] ?? null;
        if (!$rid) { $ins->bind_param('s', $t); $ins->execute(); $rid = (int)$mysqli->insert_id; }
        $pv->bind_param('ii', $id, $rid);
        $pv->execute();
      }
    }
  }

  $mysqli->commit();
  jok(['id'=>$id]);
}
case 'set_activo': {
  $id = (int)($_POST['id'] ?? 0);
  $activo = ($_POST['activo'] ?? '') !== '' ? (int)$_POST['activo'] : null;

  if ($id <= 0 || !in_array($activo, [0,1], true)) {
    jerror(400, 'Parámetros inválidos');
  }

  $st = $mysqli->prepare("UPDATE mod_servicios SET activo=? WHERE id=?");
  $st->bind_param('ii', $activo, $id);
  $st->execute();

  if ($st->affected_rows < 0) jerror(500, 'No se pudo actualizar el estado');

  jok(['id' => $id, 'activo' => $activo]);
}

case 'empresas_srv': {
  $servicio_id = (int)($_GET['servicio_id'] ?? 0);
  if ($servicio_id <= 0) jerror(400, 'Servicio requerido');

  $empresa_id = (int)($_GET['empresa_id'] ?? 0); // 0=todas
  $estado     = $_GET['estado'] ?? '';           // '' | '1' | '0'
  $page       = max(1, (int)($_GET['page'] ?? 1));
  $per_page   = min(50, max(1, (int)($_GET['per_page'] ?? 5)));
  $offset     = ($page - 1) * $per_page;

  $join   = 'LEFT JOIN mod_empresa_servicio mes ON mes.empresa_id = e.id AND mes.servicio_id = ?';
  $where  = [];
  $params = [$servicio_id];
  $types  = 'i';

  if ($empresa_id > 0) { $where[] = 'e.id = ?'; $params[] = $empresa_id; $types .= 'i'; }
  if ($estado === '1') { $where[] = 'mes.id IS NOT NULL'; }
  if ($estado === '0') { $where[] = 'mes.id IS NULL'; }
  $W = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  // total
  $st = $mysqli->prepare("SELECT COUNT(*) c FROM mtp_empresas e $join $W");
  $st->bind_param($types, ...$params);
  $st->execute();
  $total = (int)$st->get_result()->fetch_assoc()['c'];

  // datos (ASC por nombre)
  $sql = "SELECT e.id, e.nombre, (mes.id IS NOT NULL) AS asignado
          FROM mtp_empresas e
          $join
          $W
          ORDER BY e.nombre ASC
          LIMIT ? OFFSET ?";
  $params2 = $params; $types2 = $types . 'ii';
  $params2[] = $per_page; $params2[] = $offset;
  $st = $mysqli->prepare($sql);
  $st->bind_param($types2, ...$params2);
  $st->execute();
  $rows = [];
  $rs = $st->get_result();
  while ($r = $rs->fetch_assoc()) { $r['asignado'] = (int)$r['asignado']; $rows[] = $r; }

  jok(['ok'=>true,'data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per_page]);
}

case 'set_emp_srv': {
  $empresa_id  = (int)($_POST['empresa_id'] ?? 0);
  $servicio_id = (int)($_POST['servicio_id'] ?? 0);
  $assign      = isset($_POST['assign']) ? (int)$_POST['assign'] : -1; // 1=asignar, 0=quitar
  if ($empresa_id <= 0 || $servicio_id <= 0 || !in_array($assign,[0,1], true)) {
    jerror(400, 'Parámetros inválidos');
  }
  if ($assign === 1) {
    $st = $mysqli->prepare("INSERT IGNORE INTO mod_empresa_servicio (empresa_id, servicio_id) VALUES (?,?)");
    $st->bind_param('ii', $empresa_id, $servicio_id);
    $st->execute();
  } else {
    $st = $mysqli->prepare("DELETE FROM mod_empresa_servicio WHERE empresa_id=? AND servicio_id=?");
    $st->bind_param('ii', $empresa_id, $servicio_id);
    $st->execute();
  }
  jok(['empresa_id'=>$empresa_id,'servicio_id'=>$servicio_id,'assign'=>$assign]);
}

  default:
    jerror(400, 'Acción no válida');
}

} catch (mysqli_sql_exception $e) {
  if ($mysqli->errno) { $mysqli->rollback(); }
  if ($e->getCode() == 1062) jerror(409, 'El nombre de servicio ya existe');
  jerror(500, 'Error del servidor', ['dev'=>$e->getMessage()]);
}
