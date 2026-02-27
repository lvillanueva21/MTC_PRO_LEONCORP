<?php
// modules/consola/usuarios/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../gestion_archivos.php'; // módulo central de archivos (una carpeta arriba)
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = db(); $mysqli->set_charset('utf8mb4');

function jerror($code,$msg,$extra=[]){ http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr=[]){ echo json_encode(['ok'=>true]+$arr); exit; }
function is_user($s){ return preg_match('/^\d{8,11}$/', (string)$s) === 1; }



try {
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'empresas': {
      $rs = $mysqli->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'roles': {
      $rs = $mysqli->query("SELECT id, nombre FROM mtp_roles ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'list': {
  $empresa = (int)($_GET['empresa'] ?? 0);
  $rol     = (int)($_GET['rol'] ?? 0);
  $q       = trim($_GET['q'] ?? '');
  $page    = max(1, (int)($_GET['page'] ?? 1));
  $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 5)));
  $offset  = ($page - 1) * $perPage;

  $where = []; $types = ''; $pars = [];
  if ($empresa > 0) { $where[]='u.id_empresa=?'; $types.='i'; $pars[]=$empresa; }
  if ($rol > 0)     { $where[]='EXISTS (SELECT 1 FROM mtp_usuario_roles ur WHERE ur.id_usuario=u.id AND ur.id_rol=?)'; $types.='i'; $pars[]=$rol; }
  if ($q !== '') {
    $like='%'.$q.'%';
    $where[]='(u.usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)';
    $types.='sss'; array_push($pars,$like,$like,$like);
  }
  $W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

  $stC = $mysqli->prepare("SELECT COUNT(*) c FROM mtp_usuarios u $W");
  if ($types) $stC->bind_param($types, ...$pars);
  $stC->execute();
  $total = (int)$stC->get_result()->fetch_assoc()['c'];

  $sql = "SELECT u.id, u.usuario, u.nombres, u.apellidos, u.id_empresa, e.nombre AS empresa,
                 rmin.rol_id, r.nombre AS rol,
                 du.ruta_foto AS foto
          FROM mtp_usuarios u
          JOIN mtp_empresas e ON e.id = u.id_empresa
          LEFT JOIN (
            SELECT id_usuario, MIN(id_rol) AS rol_id
            FROM mtp_usuario_roles
            GROUP BY id_usuario
          ) rmin ON rmin.id_usuario = u.id
          LEFT JOIN mtp_roles r ON r.id = rmin.rol_id
          LEFT JOIN mtp_detalle_usuario du ON du.id_usuario = u.id
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


    case 'create': {
      $usuario  = trim($_POST['usuario'] ?? '');
      $clave    = (string)($_POST['clave'] ?? '');
      $nombres  = trim($_POST['nombres'] ?? '');
      $apellidos= trim($_POST['apellidos'] ?? '');
      $id_emp   = (int)($_POST['id_empresa'] ?? 0);
      $id_rol   = (int)($_POST['id_rol'] ?? 0);

      if (!is_user($usuario)) jerror(400,'Usuario debe ser DNI/CE (8–11 dígitos)');
      if (strlen($clave) < 6) jerror(400,'La contraseña debe tener al menos 6 caracteres');
      if ($nombres==='' || $apellidos==='') jerror(400,'Nombres y apellidos son obligatorios');
      if ($id_emp<=0) jerror(400,'Empresa requerida');

      $mysqli->begin_transaction();
      try {
        $hash = password_hash($clave, PASSWORD_BCRYPT);
        $st = $mysqli->prepare("INSERT INTO mtp_usuarios (usuario, clave, nombres, apellidos, id_empresa) VALUES (?,?,?,?,?)");
        $st->bind_param('ssssi', $usuario, $hash, $nombres, $apellidos, $id_emp);
        $st->execute();
        $uid = (int)$mysqli->insert_id;

        if ($id_rol > 0) {
          $st2 = $mysqli->prepare("INSERT INTO mtp_usuario_roles (id_usuario, id_rol) VALUES (?,?)");
          $st2->bind_param('ii', $uid, $id_rol);
          $st2->execute();
        }

                // === Foto de perfil (vía módulo central) ===
        $newFileAbs = null;
        if (!empty($_FILES['foto'])) {
          $ga = ga_save_upload($mysqli, $_FILES['foto'], 'img_perfil', 'perfil-usuario', 'usuarios', 'usuario', $uid);
          $newFileAbs = $ga['abs_path'] ?? null;

          // Upsert en detalle usuario (ruta relativa devuelta por el módulo)
          $ruta_rel = $ga['ruta_relativa'] ?? '';
          if ($ruta_rel !== '') {
            $stFoto = $mysqli->prepare("
              INSERT INTO mtp_detalle_usuario (id_usuario, ruta_foto)
              VALUES (?, ?)
              ON DUPLICATE KEY UPDATE ruta_foto = VALUES(ruta_foto)
            ");
            $stFoto->bind_param('is', $uid, $ruta_rel);
            $stFoto->execute();
            $stFoto->close();
          }
        }

        $mysqli->commit();
        jok(['id'=>$uid]);
      } catch (Throwable $e) {
        $mysqli->rollback();
        if (isset($newFileAbs) && $newFileAbs && is_file($newFileAbs)) { @unlink($newFileAbs); }
        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) jerror(409,'El usuario ya existe (duplicado)');
        jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
      }
    }

    case 'update': {
      $id       = (int)($_POST['id'] ?? 0);
      $usuario  = trim($_POST['usuario'] ?? '');
      $clave    = (string)($_POST['clave'] ?? '');
      $nombres  = trim($_POST['nombres'] ?? '');
      $apellidos= trim($_POST['apellidos'] ?? '');
      $id_emp   = (int)($_POST['id_empresa'] ?? 0);
      $id_rol   = (int)($_POST['id_rol'] ?? 0);

      if ($id<=0) jerror(400,'ID inválido');
      if (!is_user($usuario)) jerror(400,'Usuario debe ser DNI/CE (8–11 dígitos)');
      if ($nombres==='' || $apellidos==='') jerror(400,'Nombres y apellidos son obligatorios');
      if ($id_emp<=0) jerror(400,'Empresa requerida');
      if ($clave !== '' && strlen($clave) < 6) jerror(400,'La contraseña debe tener al menos 6 caracteres');

      $mysqli->begin_transaction();
      try {
        if ($clave === '') {
          $st = $mysqli->prepare("UPDATE mtp_usuarios SET usuario=?, nombres=?, apellidos=?, id_empresa=? WHERE id=?");
          $st->bind_param('sssii', $usuario, $nombres, $apellidos, $id_emp, $id);
        } else {
          $hash = password_hash($clave, PASSWORD_BCRYPT);
          $st = $mysqli->prepare("UPDATE mtp_usuarios SET usuario=?, clave=?, nombres=?, apellidos=?, id_empresa=? WHERE id=?");
          $st->bind_param('ssssii', $usuario, $hash, $nombres, $apellidos, $id_emp, $id);
        }
        $st->execute();

        $del = $mysqli->prepare("DELETE FROM mtp_usuario_roles WHERE id_usuario=?");
        $del->bind_param('i', $id); $del->execute();

        if ($id_rol > 0) {
          $ins = $mysqli->prepare("INSERT INTO mtp_usuario_roles (id_usuario, id_rol) VALUES (?,?)");
          $ins->bind_param('ii', $id, $id_rol); $ins->execute();
        }

                // === Foto de perfil (reemplazo vía módulo central) ===
        $newFileAbs = null;
        if (!empty($_FILES['foto'])) {
          // 1) Ruta previa (si existe)
          $prev = null;
          $stPrev = $mysqli->prepare("SELECT ruta_foto FROM mtp_detalle_usuario WHERE id_usuario=?");
          $stPrev->bind_param('i', $id);
          $stPrev->execute();
          if ($r = $stPrev->get_result()->fetch_assoc()) { $prev = $r['ruta_foto'] ?? null; }
          $stPrev->close();

          // 2) Guardar nuevo archivo centralizado
          $ga = ga_save_upload($mysqli, $_FILES['foto'], 'img_perfil', 'perfil-usuario', 'usuarios', 'usuario', $id);
          $newFileAbs = $ga['abs_path'] ?? null;
          $ruta_rel   = $ga['ruta_relativa'] ?? '';

          // 3) Upsert detalle con la nueva ruta
          if ($ruta_rel !== '') {
            $stFoto = $mysqli->prepare("
              INSERT INTO mtp_detalle_usuario (id_usuario, ruta_foto)
              VALUES (?, ?)
              ON DUPLICATE KEY UPDATE ruta_foto = VALUES(ruta_foto)
            ");
            $stFoto->bind_param('is', $id, $ruta_rel);
            $stFoto->execute();
            $stFoto->close();
          }

          // 4) Marcar y borrar el anterior si cambió
          if ($prev && $prev !== $ruta_rel) {
            ga_mark_and_delete($mysqli, $prev, 'reemplazado');
          }
        }

        $mysqli->commit();
        jok(['id'=>$id]);
      } catch (Throwable $e) {
        $mysqli->rollback();
        if (isset($newFileAbs) && $newFileAbs && is_file($newFileAbs)) { @unlink($newFileAbs); }
        if ($e instanceof mysqli_sql_exception) {
          if ((int)$e->getCode() === 1062) jerror(409,'El usuario ya existe (duplicado)');
          if (in_array((int)$e->getCode(), [1451,1452], true)) jerror(409,'No se puede eliminar/actualizar por referencias en otras tablas');
        }
        jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
      }
    }

    case 'delete': {
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) jerror(400,'ID inválido');

  // Si tiene foto, marcar y borrar el archivo antes de eliminar el usuario
  try {
    $prev = null;
    $stPrev = $mysqli->prepare("SELECT ruta_foto FROM mtp_detalle_usuario WHERE id_usuario=?");
    $stPrev->bind_param('i', $id);
    $stPrev->execute();
    if ($r = $stPrev->get_result()->fetch_assoc()) { $prev = $r['ruta_foto'] ?? null; }
    $stPrev->close();

    if ($prev) {
      ga_mark_and_delete($mysqli, $prev, 'borrado');
    }
  } catch (Throwable $e) {
    // no bloqueamos el borrado del usuario por un fallo al borrar/actualizar el archivo
  }

  $st = $mysqli->prepare("DELETE FROM mtp_usuarios WHERE id=?");
  $st->bind_param('i', $id); $st->execute();
  jok(['id'=>$id]);
}

    default: jerror(400,'Acción no válida');
  }
} catch (mysqli_sql_exception $e) {
  if ($mysqli->errno) { @$mysqli->rollback(); }
  if ((int)$e->getCode() === 1062) jerror(409,'El usuario ya existe (duplicado)');
  if (in_array((int)$e->getCode(), [1451,1452], true)) jerror(409,'No se puede eliminar/actualizar por referencias en otras tablas');
  jerror(500,'Error del servidor',['dev'=>$e->getMessage()]);
}
