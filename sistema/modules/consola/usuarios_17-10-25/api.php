<?php
// modules/consola/usuarios/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = db(); $mysqli->set_charset('utf8mb4');

function jerror($code,$msg,$extra=[]){ http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr=[]){ echo json_encode(['ok'=>true]+$arr); exit; }
function is_user($s){ return preg_match('/^\d{8,11}$/', (string)$s) === 1; }

// === Foto de perfil ===
define('ROOT_DIR', dirname(__DIR__, 3)); // raíz del proyecto
const PERFIL_REL_DIR = '/almacen/img_perfil'; // carpeta pública para servir imágenes

function perfil_abs_dir(): string {
  return rtrim(ROOT_DIR, DIRECTORY_SEPARATOR) . PERFIL_REL_DIR;
}

function ensure_upload_dir(): void {
  $dir = perfil_abs_dir();
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  if (!is_writable($dir)) throw new RuntimeException('La carpeta no es escribible: ' . $dir);
}

/** Guarda/reemplaza la foto y retorna la ruta relativa web. */
function guardar_foto_perfil(mysqli $mysqli, int $userId, array $file): ?string {
  if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Error de subida de archivo.');
  if (($file['size'] ?? 0) > 4 * 1024 * 1024) throw new RuntimeException('La imagen supera 4MB.');
  $info = @getimagesize($file['tmp_name']);
  if (!$info) throw new RuntimeException('El archivo no es una imagen válida.');
  $mime = $info['mime'] ?? '';
  if     ($mime === 'image/jpeg') $ext = 'jpg';
  elseif ($mime === 'image/png')  $ext = 'png';
  elseif ($mime === 'image/webp') $ext = 'webp';
  else throw new RuntimeException('Formatos permitidos: JPG, PNG o WEBP.');

  ensure_upload_dir();

  // anterior
  $prev = null;
  $st = $mysqli->prepare("SELECT ruta_foto FROM mtp_detalle_usuario WHERE id_usuario=?");
  $st->bind_param('i', $userId); $st->execute();
  if ($r = $st->get_result()->fetch_assoc()) $prev = $r['ruta_foto'] ?? null;
  $st->close();

  // guardar
  $name = $userId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
  $abs = rtrim(perfil_abs_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
  if (!move_uploaded_file($file['tmp_name'], $abs)) throw new RuntimeException('No se pudo guardar el archivo en disco.');
  $ruta_rel = PERFIL_REL_DIR . '/' . $name;

  // upsert
  $st2 = $mysqli->prepare("
    INSERT INTO mtp_detalle_usuario (id_usuario, ruta_foto) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE ruta_foto = VALUES(ruta_foto)
  ");
  $st2->bind_param('is', $userId, $ruta_rel);
  $st2->execute();
  $st2->close();

  // borrar anterior si cambió
  if ($prev && $prev !== $ruta_rel) {
    $absPrev = rtrim(ROOT_DIR, DIRECTORY_SEPARATOR) . $prev;
    if (is_file($absPrev)) @unlink($absPrev);
  }
  return $ruta_rel;
}

/** Borra foto (archivo + fila) si existiera. */
function borrar_foto_perfil(mysqli $mysqli, int $userId): void {
  $prev = null;
  $st = $mysqli->prepare("SELECT ruta_foto FROM mtp_detalle_usuario WHERE id_usuario=?");
  $st->bind_param('i', $userId); $st->execute();
  if ($r = $st->get_result()->fetch_assoc()) $prev = $r['ruta_foto'] ?? null;
  $st->close();

  $del = $mysqli->prepare("DELETE FROM mtp_detalle_usuario WHERE id_usuario=?");
  $del->bind_param('i', $userId); $del->execute(); $del->close();

  if ($prev) {
    $absPrev = rtrim(ROOT_DIR, DIRECTORY_SEPARATOR) . $prev;
    if (is_file($absPrev)) @unlink($absPrev);
  }
}

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

        if (!empty($_FILES['foto'])) {
          guardar_foto_perfil($mysqli, $uid, $_FILES['foto']);
        }

        $mysqli->commit();
        jok(['id'=>$uid]);
      } catch (Throwable $e) {
        $mysqli->rollback();
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

        if (!empty($_FILES['foto'])) {
          guardar_foto_perfil($mysqli, $id, $_FILES['foto']);
        }

        $mysqli->commit();
        jok(['id'=>$id]);
      } catch (Throwable $e) {
        $mysqli->rollback();
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
