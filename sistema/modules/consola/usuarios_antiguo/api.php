<?php
// modules/consola/usuarios/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = db();
$mysqli->set_charset('utf8mb4');

function jerror($code,$msg,$extra=[]){ http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr=[]){ echo json_encode(['ok'=>true]+$arr); exit; }

function is_user($s){ return preg_match('/^\d{8,11}$/', (string)$s) === 1; }

try {
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {

    // -------- combos --------
    case 'empresas': {
      $rs = $mysqli->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'roles': {
      // asume tabla mtp_roles(id, nombre)
      $rs = $mysqli->query("SELECT id, nombre FROM mtp_roles ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    // -------- listado con filtros + paginación --------
    case 'list': {
      $empresa = (int)($_GET['empresa'] ?? 0);
      $rol     = (int)($_GET['rol'] ?? 0);
      $q       = trim($_GET['q'] ?? '');
      $page    = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 5)));
      $offset  = ($page - 1) * $perPage;

      $where = [];
      $types = '';
      $pars  = [];

      if ($empresa > 0) { $where[]='u.id_empresa=?'; $types.='i'; $pars[]=$empresa; }
      if ($rol > 0)     { $where[]='EXISTS (SELECT 1 FROM mtp_usuario_roles ur WHERE ur.id_usuario=u.id AND ur.id_rol=?)'; $types.='i'; $pars[]=$rol; }
      if ($q !== '')    { $like='%'.$q.'%'; $where[]='(u.usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)'; $types.='sss'; array_push($pars,$like,$like,$like); }

      $W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

      // total
      $stC = $mysqli->prepare("SELECT COUNT(*) c FROM mtp_usuarios u $W");
      if ($types) $stC->bind_param($types, ...$pars);
      $stC->execute();
      $total = (int)$stC->get_result()->fetch_assoc()['c'];

      // listado: rol principal = MIN(id_rol)
      $sql = "SELECT u.id, u.usuario, u.nombres, u.apellidos, u.id_empresa, e.nombre empresa,
                     rmin.rol_id, r.nombre AS rol
              FROM mtp_usuarios u
              JOIN mtp_empresas e ON e.id = u.id_empresa
              LEFT JOIN (
                SELECT id_usuario, MIN(id_rol) AS rol_id
                FROM mtp_usuario_roles
                GROUP BY id_usuario
              ) rmin ON rmin.id_usuario = u.id
              LEFT JOIN mtp_roles r ON r.id = rmin.rol_id
              $W
              ORDER BY u.id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii';
      $pars2  = $pars; $pars2[] = $perPage; $pars2[] = $offset;
      $stD = $mysqli->prepare($sql);
      if ($types2) $stD->bind_param($types2, ...$pars2);
      $stD->execute();
      $rows = $stD->get_result()->fetch_all(MYSQLI_ASSOC);

      jok(['data'=>$rows, 'total'=>$total, 'page'=>$page, 'per_page'=>$perPage]);
    }

    // -------- crear --------
    case 'create': {
      $usuario   = trim($_POST['usuario'] ?? '');
      $clave     = (string)($_POST['clave'] ?? '');
      $nombres   = trim($_POST['nombres'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $id_emp    = (int)($_POST['id_empresa'] ?? 0);
      $id_rol    = (int)($_POST['id_rol'] ?? 0);

      if (!is_user($usuario)) jerror(400,'Usuario debe ser DNI/CE (8–11 dígitos)');
      if (strlen($clave) < 6) jerror(400,'La contraseña debe tener al menos 6 caracteres');
      if ($nombres==='' || $apellidos==='') jerror(400,'Nombres y apellidos son obligatorios');
      if ($id_emp<=0) jerror(400,'Empresa requerida');

      $mysqli->begin_transaction();
      $hash = password_hash($clave, PASSWORD_BCRYPT);
      $st = $mysqli->prepare("INSERT INTO mtp_usuarios (usuario, clave, nombres, apellidos, id_empresa) VALUES (?,?,?,?,?)");
      $st->bind_param('ssssi', $usuario, $hash, $nombres, $apellidos, $id_emp);
      $st->execute();
      $uid = (int)$mysqli->insert_id;

      if ($id_rol > 0) {
        $st2 = $mysqli->prepare("INSERT INTO mtp_usuario_roles (id_usuario, id_rol) VALUES (?,?)");
        $st2->bind_param('ii', $uid, $id_rol); $st2->execute();
      }

      $mysqli->commit();
      jok(['id'=>$uid]);
    }

    // -------- actualizar --------
    case 'update': {
      $id        = (int)($_POST['id'] ?? 0);
      $usuario   = trim($_POST['usuario'] ?? '');
      $clave     = (string)($_POST['clave'] ?? '');
      $nombres   = trim($_POST['nombres'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $id_emp    = (int)($_POST['id_empresa'] ?? 0);
      $id_rol    = (int)($_POST['id_rol'] ?? 0);

      if ($id<=0) jerror(400,'ID inválido');
      if (!is_user($usuario)) jerror(400,'Usuario debe ser DNI/CE (8–11 dígitos)');
      if ($nombres==='' || $apellidos==='') jerror(400,'Nombres y apellidos son obligatorios');
      if ($id_emp<=0) jerror(400,'Empresa requerida');
      if ($clave !== '' && strlen($clave) < 6) jerror(400,'La contraseña debe tener al menos 6 caracteres');

      $mysqli->begin_transaction();

      if ($clave === '') {
        $st = $mysqli->prepare("UPDATE mtp_usuarios SET usuario=?, nombres=?, apellidos=?, id_empresa=? WHERE id=?");
        $st->bind_param('sssii', $usuario, $nombres, $apellidos, $id_emp, $id);
      } else {
        $hash = password_hash($clave, PASSWORD_BCRYPT);
        $st = $mysqli->prepare("UPDATE mtp_usuarios SET usuario=?, clave=?, nombres=?, apellidos=?, id_empresa=? WHERE id=?");
        $st->bind_param('ssssii', $usuario, $hash, $nombres, $apellidos, $id_emp, $id);
      }
      $st->execute();

      // rol único: reemplaza
      $del = $mysqli->prepare("DELETE FROM mtp_usuario_roles WHERE id_usuario=?");
      $del->bind_param('i', $id); $del->execute();
      if ($id_rol > 0) {
        $ins = $mysqli->prepare("INSERT INTO mtp_usuario_roles (id_usuario, id_rol) VALUES (?,?)");
        $ins->bind_param('ii', $id, $id_rol); $ins->execute();
      }

      $mysqli->commit();
      jok(['id'=>$id]);
    }

    // -------- eliminar --------
    case 'delete': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $st = $mysqli->prepare("DELETE FROM mtp_usuarios WHERE id=?");
      $st->bind_param('i', $id);
      $st->execute();
      jok(['id'=>$id]);
    }

    default: jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  if ($mysqli->errno) { @$mysqli->rollback(); }
  // Duplicado usuario
  if ((int)$e->getCode() === 1062) jerror(409,'El usuario ya existe (duplicado)');
  // FKs
  if (in_array((int)$e->getCode(), [1451,1452], true)) jerror(409,'No se puede eliminar/actualizar por referencias en otras tablas');
  jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
}
