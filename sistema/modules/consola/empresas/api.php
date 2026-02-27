<?php
// modules/consola/empresas/api.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../gestion_archivos.php'; // módulo central (una carpeta arriba de esta tríada)

acl_require_ids([1,6]);            // Ajusta a tus roles
verificarPermiso(['Desarrollo','Gerente']);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = db();
$db->set_charset('utf8mb4');

$A = $_POST['action'] ?? $_GET['action'] ?? '';

function jerror($code, $msg, $extra=[]) { http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr=[]) { echo json_encode(['ok'=>true]+$arr); exit; }

try {
  switch ($A) {
    // ------------------------ COMBOS ------------------------
    case 'combos': {
      $tipos = $db->query("SELECT id, nombre FROM mtp_tipos_empresas ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
      $depas = $db->query("SELECT id, nombre FROM mtp_departamentos ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
      $repleg = $db->query("SELECT id, CONCAT(nombres,' ',apellidos,' (',documento,')') AS nom FROM mtp_representante_legal ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
      jok(['tipos'=>$tipos,'depas'=>$depas,'repleg'=>$repleg]);
    }

    // ------------------------ REPRESENTANTES ------------------------
    case 'rep_list': {
      $q = trim($_GET['q'] ?? '');
      $page = max(1, (int)($_GET['page'] ?? 1));
      $per  = max(1, min(50, (int)($_GET['per_page'] ?? 8)));
      $off  = ($page-1)*$per;

      $where=''; $types=''; $pars=[];
      if ($q!=='') {
        $like = "%$q%";
        $where = "WHERE (CONCAT(rl.nombres,' ',rl.apellidos) LIKE ? COLLATE utf8mb4_spanish_ci OR rl.documento LIKE ?)";
        $types='ss'; $pars=[$like,$like];
      }

      // total
      $st = $db->prepare("SELECT COUNT(*) c FROM mtp_representante_legal rl $where");
      if ($types) $st->bind_param($types, ...$pars);
      $st->execute(); $total=(int)$st->get_result()->fetch_assoc()['c'];

      // data
      $sql = "SELECT id, nombres, apellidos, documento
              FROM mtp_representante_legal rl
              $where
              ORDER BY id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii'; $pars2 = $pars; $pars2[]=$per; $pars2[]=$off;
      $st = $db->prepare($sql); $st->bind_param($types2, ...$pars2); $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per]);
    }

    case 'rep_create': {
      $nombres   = trim($_POST['nombres'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $documento = trim($_POST['documento'] ?? '');
      $clave     = (string)($_POST['clave'] ?? '');
      if ($nombres==='' || $apellidos==='') jerror(400,'Nombres y apellidos son obligatorios');
      if (!preg_match('/^\d{8}$/',$documento)) jerror(400,'Documento debe ser DNI de 8 dígitos');
      if ($clave==='') jerror(400,'La contraseña no puede estar vacía');
      $st = $db->prepare("INSERT INTO mtp_representante_legal(nombres, apellidos, documento, clave_mana) VALUES (?,?,?,?)");
      $st->bind_param('ssss',$nombres,$apellidos,$documento,$clave);
      $st->execute(); $id=(int)$db->insert_id;
      jok(['id'=>$id]);
    }

    case 'rep_update': {
      $id        = (int)($_POST['id'] ?? 0);
      $nombres   = trim($_POST['nombres'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $documento = trim($_POST['documento'] ?? '');
      $clave     = (string)($_POST['clave'] ?? '');
      if ($id<=0) jerror(400,'ID inválido');
      if ($nombres==='' || $apellidos==='') jerror(400,'Nombres y apellidos son obligatorios');
      if (!preg_match('/^\d{8}$/',$documento)) jerror(400,'Documento debe ser DNI de 8 dígitos');

      if ($clave!=='') {
        $st = $db->prepare("UPDATE mtp_representante_legal SET nombres=?, apellidos=?, documento=?, clave_mana=? WHERE id=?");
        $st->bind_param('ssssi',$nombres,$apellidos,$documento,$clave,$id);
      } else {
        $st = $db->prepare("UPDATE mtp_representante_legal SET nombres=?, apellidos=?, documento=? WHERE id=?");
        $st->bind_param('sssi',$nombres,$apellidos,$documento,$id);
      }
      $st->execute();
      jok(['id'=>$id]);
    }

    case 'rep_delete': {
      $id=(int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $st=$db->prepare("DELETE FROM mtp_representante_legal WHERE id=?");
      $st->bind_param('i',$id); $st->execute();
      jok(['id'=>$id]);
    }

    // ------------------------ EMPRESAS ------------------------
    case 'emp_list': {
      $q = trim($_GET['q'] ?? '');
      $page = max(1, (int)($_GET['page'] ?? 1));
      $per  = max(1, min(50, (int)($_GET['per_page'] ?? 8)));
      $off  = ($page-1)*$per;

      $where=''; $types=''; $pars=[];
      if ($q!=='') {
        $like = "%$q%";
        $where = "WHERE (e.nombre LIKE ? COLLATE utf8mb4_spanish_ci
                      OR e.razon_social LIKE ? COLLATE utf8mb4_spanish_ci
                      OR e.ruc LIKE ?
                      OR te.nombre LIKE ? COLLATE utf8mb4_spanish_ci
                      OR d.nombre LIKE ? COLLATE utf8mb4_spanish_ci
                      OR CONCAT(rl.nombres,' ',rl.apellidos) LIKE ? COLLATE utf8mb4_spanish_ci)";
        $types='ssssss'; $pars=[$like,$like,$like,$like,$like,$like];
      }

      // total
      $sqlC = "SELECT COUNT(*) c
              FROM mtp_empresas e
              JOIN mtp_tipos_empresas te ON te.id=e.id_tipo
              JOIN mtp_departamentos d ON d.id=e.id_depa
              JOIN mtp_representante_legal rl ON rl.id=e.id_repleg
              $where";
      $st = $db->prepare($sqlC);
      if ($types) $st->bind_param($types, ...$pars);
      $st->execute(); $total=(int)$st->get_result()->fetch_assoc()['c'];

      // data
      $sql = "SELECT e.id, e.nombre, e.razon_social, e.ruc, e.direccion, e.id_tipo, e.id_depa, e.id_repleg,
       e.logo_path,te.nombre AS tipo, d.nombre AS depa, CONCAT(rl.nombres,' ',rl.apellidos) AS repleg
              FROM mtp_empresas e
              JOIN mtp_tipos_empresas te ON te.id=e.id_tipo
              JOIN mtp_departamentos d ON d.id=e.id_depa
              JOIN mtp_representante_legal rl ON rl.id=e.id_repleg
              $where
              ORDER BY e.id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii'; $pars2=$pars; $pars2[]=$per; $pars2[]=$off;
      $st = $db->prepare($sql); $st->bind_param($types2, ...$pars2); $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per]);
    }

case 'emp_create': {
  $nombre       = trim($_POST['nombre'] ?? '');
  $razon_social = trim($_POST['razon_social'] ?? '');
  $ruc          = trim($_POST['ruc'] ?? '');
  $direccion    = trim($_POST['direccion'] ?? '');
  $id_tipo      = (int)($_POST['id_tipo'] ?? 0);
  $id_depa      = (int)($_POST['id_depa'] ?? 0);
  $id_repleg    = (int)($_POST['id_repleg'] ?? 0);

  if ($nombre==='' || $razon_social==='' || $direccion==='') jerror(400,'Todos los campos de texto son obligatorios');
  if (!preg_match('/^\d{11}$/', $ruc)) jerror(400,'RUC debe tener 11 dígitos');
  if ($id_tipo<=0 || $id_depa<=0 || $id_repleg<=0) jerror(400,'Selecciona tipo, departamento y representante');

  $db->begin_transaction();
  try {
    // 1) Insertar empresa (sin logo)
    $st = $db->prepare("INSERT INTO mtp_empresas(nombre, razon_social, ruc, direccion, id_tipo, id_depa, id_repleg, logo_path)
                        VALUES (?,?,?,?,?,?,?, NULL)");
    $st->bind_param('ssssiii', $nombre, $razon_social, $ruc, $direccion, $id_tipo, $id_depa, $id_repleg);
    $st->execute();
    $eid = (int)$db->insert_id;

    // 2) Logo opcional vía módulo central
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
      if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) jerror(400, 'Error al subir el logo');
      if (($_FILES['logo']['size'] ?? 0) > 5 * 1024 * 1024) jerror(400, 'El logo excede 5MB');
      $fi = new finfo(FILEINFO_MIME_TYPE);
      $mime = $fi->file($_FILES['logo']['tmp_name']);
      $ok = in_array($mime, ['image/jpeg','image/png','image/webp'], true);
      if (!$ok) jerror(400, 'Formato no permitido (PNG/JPG/WebP)');

      $ga = ga_save_upload($db, $_FILES['logo'], 'img_logos_empresas', 'logo-empresa', 'empresas', 'empresa', $eid);
      $ruta_rel = $ga['ruta_relativa'] ?? '';
      if ($ruta_rel !== '') {
        $up = $db->prepare("UPDATE mtp_empresas SET logo_path=? WHERE id=?");
        $up->bind_param('si', $ruta_rel, $eid);
        $up->execute();
      }
    }

    $db->commit();
    jok(['id'=>$eid]);
  } catch (Throwable $e) {
    $db->rollback();
    jerror(500,'Error del servidor', ['dev'=>$e->getMessage()]);
  }
}

case 'emp_update': {
  $id           = (int)($_POST['id'] ?? 0);
  $nombre       = trim($_POST['nombre'] ?? '');
  $razon_social = trim($_POST['razon_social'] ?? '');
  $ruc          = trim($_POST['ruc'] ?? '');
  $direccion    = trim($_POST['direccion'] ?? '');
  $id_tipo      = (int)($_POST['id_tipo'] ?? 0);
  $id_depa      = (int)($_POST['id_depa'] ?? 0);
  $id_repleg    = (int)($_POST['id_repleg'] ?? 0);

  if ($id<=0) jerror(400,'ID inválido');
  if ($nombre==='' || $razon_social==='' || $direccion==='') jerror(400,'Todos los campos de texto son obligatorios');
  if (!preg_match('/^\d{11}$/',$ruc)) jerror(400,'RUC debe tener 11 dígitos');
  if ($id_tipo<=0 || $id_depa<=0 || $id_repleg<=0) jerror(400,'Selecciona tipo, departamento y representante');

  $db->begin_transaction();
  try {
    // 1) Actualizar datos base
    $st = $db->prepare("UPDATE mtp_empresas
                        SET nombre=?, razon_social=?, ruc=?, direccion=?, id_tipo=?, id_depa=?, id_repleg=?
                        WHERE id=?");
    $st->bind_param('ssssiiii', $nombre, $razon_social, $ruc, $direccion, $id_tipo, $id_depa, $id_repleg, $id);
    $st->execute();

    // 2) Si suben nuevo logo => guardar centralizado y marcar anterior
    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
      if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) jerror(400, 'Error al subir el logo');
      if (($_FILES['logo']['size'] ?? 0) > 5 * 1024 * 1024) jerror(400, 'El logo excede 5MB');
      $fi = new finfo(FILEINFO_MIME_TYPE);
      $mime = $fi->file($_FILES['logo']['tmp_name']);
      $ok = in_array($mime, ['image/jpeg','image/png','image/webp'], true);
      if (!$ok) jerror(400, 'Formato no permitido (PNG/JPG/WebP)');

      // Ruta anterior (si existe)
      $prev = null;
      $stp = $db->prepare("SELECT logo_path FROM mtp_empresas WHERE id=?");
      $stp->bind_param('i', $id);
      $stp->execute();
      if ($r = $stp->get_result()->fetch_assoc()) { $prev = $r['logo_path'] ?? null; }
      $stp->close();

      // Guardar nuevo
      $ga = ga_save_upload($db, $_FILES['logo'], 'img_logos_empresas', 'logo-empresa', 'empresas', 'empresa', $id);
      $ruta_rel = $ga['ruta_relativa'] ?? '';

      if ($ruta_rel !== '') {
        $up = $db->prepare("UPDATE mtp_empresas SET logo_path=? WHERE id=?");
        $up->bind_param('si', $ruta_rel, $id);
        $up->execute();
      }

      // Marcar/borrar anterior
      if ($prev && $prev !== $ruta_rel) {
        ga_mark_and_delete($db, $prev, 'reemplazado');
      }
    }

    $db->commit();
    jok(['id'=>$id]);
  } catch (Throwable $e) {
    $db->rollback();
    jerror(500,'Error del servidor', ['dev'=>$e->getMessage()]);
  }
}

case 'emp_delete': {
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) jerror(400,'ID inválido');

  // Borrar logo si existe (compatible con rutas antiguas y nuevas)
  try {
    $prev = null;
    $stp = $db->prepare("SELECT logo_path FROM mtp_empresas WHERE id=?");
    $stp->bind_param('i', $id);
    $stp->execute();
    if ($r = $stp->get_result()->fetch_assoc()) { $prev = $r['logo_path'] ?? null; }
    $stp->close();

    if ($prev) {
      ga_mark_and_delete($db, $prev, 'borrado');
    }
  } catch (Throwable $e) {
    // No bloqueamos la eliminación por fallos de archivo
  }

  $st = $db->prepare("DELETE FROM mtp_empresas WHERE id=?");
  $st->bind_param('i',$id); $st->execute();
  jok(['id'=>$id]);
}

    default: jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  // 1062 duplicado, 1451 restricción FK, otros -> 500
  if ((int)$e->getCode() === 1062) jerror(409, 'Registro duplicado (verifica RUC o Documento).');
  if ((int)$e->getCode() === 1451) jerror(409, 'No se puede eliminar: el registro está en uso por otras tablas.');
  jerror(500, 'Error del servidor', ['dev'=>$e->getMessage()]);
} catch (Throwable $e) {
  jerror(500, 'Error inesperado', ['dev'=>$e->getMessage()]);
}
