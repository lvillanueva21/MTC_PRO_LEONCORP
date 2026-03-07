<?php
// modules/aula_virtual/api_administracion.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../consola/gestion_archivos.php';

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

function jerror(int $code, string $msg, array $extra = []): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
  exit;
}

function jok(array $data = []): void {
  echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}

function current_role_id(): int {
  return (int)($_SESSION['user']['rol_activo_id'] ?? 0);
}

function current_company_id(): int {
  $u = currentUser();
  return (int)($u['empresa']['id'] ?? 0);
}

function is_client_in_company(mysqli $db, int $userId, int $companyId): bool {
  if ($userId <= 0 || $companyId <= 0) return false;

  $st = $db->prepare(
    "SELECT 1
     FROM mtp_usuarios u
     WHERE u.id = ?
       AND u.id_empresa = ?
       AND EXISTS (
         SELECT 1
         FROM mtp_usuario_roles ur
         WHERE ur.id_usuario = u.id
           AND ur.id_rol = 7
       )
     LIMIT 1"
  );
  $st->bind_param('ii', $userId, $companyId);
  $st->execute();
  return (bool)$st->get_result()->fetch_assoc();
}

try {
  acl_require_ids([1, 4]); // Desarrollo o Administracion

  $roleId = current_role_id();
  if (!in_array($roleId, [1, 4], true)) {
    jerror(403, 'No tienes permisos para esta operacion.');
  }

  $empresaId = current_company_id();
  if ($empresaId <= 0) {
    jerror(400, 'No se pudo identificar la empresa activa del usuario.');
  }

  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'cursos_list_activos': {
      $st = $db->prepare("SELECT id, nombre FROM cr_cursos WHERE activo = 1 ORDER BY nombre ASC");
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data' => $rows]);
    }

    case 'clientes_list': {
      $q       = trim((string)($_GET['q'] ?? ''));
      $cursoId = (int)($_GET['curso_id'] ?? 0);
      $page    = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
      $offset  = ($page - 1) * $perPage;

      $where = [
        'u.id_empresa = ?',
        'EXISTS (SELECT 1 FROM mtp_usuario_roles ur WHERE ur.id_usuario = u.id AND ur.id_rol = 7)',
      ];
      $types = 'i';
      $pars  = [$empresaId];

      if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(u.usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)';
        $types .= 'sss';
        $pars[] = $like;
        $pars[] = $like;
        $pars[] = $like;
      }

      if ($cursoId > 0) {
        $where[] = 'EXISTS (SELECT 1 FROM cr_usuario_curso uc2 WHERE uc2.usuario_id = u.id AND uc2.curso_id = ? AND uc2.activo = 1)';
        $types .= 'i';
        $pars[] = $cursoId;
      }

      $wSql = 'WHERE ' . implode(' AND ', $where);

      $stCount = $db->prepare("SELECT COUNT(*) AS c FROM mtp_usuarios u {$wSql}");
      $stCount->bind_param($types, ...$pars);
      $stCount->execute();
      $total = (int)($stCount->get_result()->fetch_assoc()['c'] ?? 0);

      $sql = "SELECT
                u.id,
                u.usuario,
                u.nombres,
                u.apellidos,
                MAX(du.ruta_foto) AS foto,
                COUNT(DISTINCT CASE WHEN uc.activo = 1 THEN uc.curso_id END) AS cursos_count
              FROM mtp_usuarios u
              LEFT JOIN cr_usuario_curso uc ON uc.usuario_id = u.id
              LEFT JOIN mtp_detalle_usuario du ON du.id_usuario = u.id
              {$wSql}
              GROUP BY u.id, u.usuario, u.nombres, u.apellidos
              ORDER BY u.id DESC
              LIMIT ? OFFSET ?";

      $types2 = $types . 'ii';
      $pars2  = $pars;
      $pars2[] = $perPage;
      $pars2[] = $offset;

      $st = $db->prepare($sql);
      $st->bind_param($types2, ...$pars2);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      jok([
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
      ]);
    }

    case 'cliente_create': {
      $usuario   = trim((string)($_POST['usuario'] ?? ''));
      $clave     = (string)($_POST['clave'] ?? '');
      $nombres   = trim((string)($_POST['nombres'] ?? ''));
      $apellidos = trim((string)($_POST['apellidos'] ?? ''));

      if (!preg_match('/^\d{8,11}$/', $usuario)) {
        jerror(400, 'El usuario debe ser DNI/CE de 8 a 11 digitos.');
      }
      if (strlen($clave) < 6) {
        jerror(400, 'La clave debe tener al menos 6 caracteres.');
      }
      if ($nombres === '' || $apellidos === '') {
        jerror(400, 'Nombres y apellidos son obligatorios.');
      }

      $db->begin_transaction();
      $newFileAbs = null;
      try {
        $hash = password_hash($clave, PASSWORD_BCRYPT);
        $st = $db->prepare(
          "INSERT INTO mtp_usuarios (usuario, clave, nombres, apellidos, id_empresa)
           VALUES (?, ?, ?, ?, ?)"
        );
        $st->bind_param('ssssi', $usuario, $hash, $nombres, $apellidos, $empresaId);
        $st->execute();
        $uid = (int)$db->insert_id;

        $stRole = $db->prepare("INSERT INTO mtp_usuario_roles (id_usuario, id_rol) VALUES (?, 7)");
        $stRole->bind_param('i', $uid);
        $stRole->execute();

        if (!empty($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
          $ga = ga_save_upload($db, $_FILES['foto'], 'img_perfil', 'perfil-usuario', 'usuarios', 'usuario', $uid);
          $newFileAbs = $ga['abs_path'] ?? null;
          $rutaRel = $ga['ruta_relativa'] ?? '';

          if ($rutaRel !== '') {
            $stFoto = $db->prepare(
              "INSERT INTO mtp_detalle_usuario (id_usuario, ruta_foto)
               VALUES (?, ?)
               ON DUPLICATE KEY UPDATE ruta_foto = VALUES(ruta_foto)"
            );
            $stFoto->bind_param('is', $uid, $rutaRel);
            $stFoto->execute();
          }
        }

        $db->commit();
        jok(['id' => $uid, 'msg' => 'Cliente creado correctamente.']);
      } catch (Throwable $e) {
        $db->rollback();
        if ($newFileAbs && is_file($newFileAbs)) @unlink($newFileAbs);
        throw $e;
      }
    }

    case 'cliente_update': {
      $id        = (int)($_POST['id'] ?? 0);
      $usuario   = trim((string)($_POST['usuario'] ?? ''));
      $clave     = (string)($_POST['clave'] ?? '');
      $nombres   = trim((string)($_POST['nombres'] ?? ''));
      $apellidos = trim((string)($_POST['apellidos'] ?? ''));

      if ($id <= 0) jerror(400, 'ID de cliente invalido.');
      if (!is_client_in_company($db, $id, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');
      if (!preg_match('/^\d{8,11}$/', $usuario)) jerror(400, 'El usuario debe ser DNI/CE de 8 a 11 digitos.');
      if ($nombres === '' || $apellidos === '') jerror(400, 'Nombres y apellidos son obligatorios.');
      if ($clave !== '' && strlen($clave) < 6) jerror(400, 'La clave debe tener al menos 6 caracteres.');

      $db->begin_transaction();
      $newFileAbs = null;
      try {
        if ($clave === '') {
          $st = $db->prepare(
            "UPDATE mtp_usuarios
             SET usuario = ?, nombres = ?, apellidos = ?, id_empresa = ?
             WHERE id = ? AND id_empresa = ?"
          );
          $st->bind_param('sssiii', $usuario, $nombres, $apellidos, $empresaId, $id, $empresaId);
        } else {
          $hash = password_hash($clave, PASSWORD_BCRYPT);
          $st = $db->prepare(
            "UPDATE mtp_usuarios
             SET usuario = ?, clave = ?, nombres = ?, apellidos = ?, id_empresa = ?
             WHERE id = ? AND id_empresa = ?"
          );
          $st->bind_param('ssssiii', $usuario, $hash, $nombres, $apellidos, $empresaId, $id, $empresaId);
        }
        $st->execute();

        $stDelRoles = $db->prepare("DELETE FROM mtp_usuario_roles WHERE id_usuario = ?");
        $stDelRoles->bind_param('i', $id);
        $stDelRoles->execute();

        $stInsRole = $db->prepare("INSERT INTO mtp_usuario_roles (id_usuario, id_rol) VALUES (?, 7)");
        $stInsRole->bind_param('i', $id);
        $stInsRole->execute();

        if (!empty($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
          $prev = null;
          $stPrev = $db->prepare("SELECT ruta_foto FROM mtp_detalle_usuario WHERE id_usuario = ?");
          $stPrev->bind_param('i', $id);
          $stPrev->execute();
          if ($r = $stPrev->get_result()->fetch_assoc()) $prev = $r['ruta_foto'] ?? null;

          $ga = ga_save_upload($db, $_FILES['foto'], 'img_perfil', 'perfil-usuario', 'usuarios', 'usuario', $id);
          $newFileAbs = $ga['abs_path'] ?? null;
          $rutaRel = $ga['ruta_relativa'] ?? '';

          if ($rutaRel !== '') {
            $stFoto = $db->prepare(
              "INSERT INTO mtp_detalle_usuario (id_usuario, ruta_foto)
               VALUES (?, ?)
               ON DUPLICATE KEY UPDATE ruta_foto = VALUES(ruta_foto)"
            );
            $stFoto->bind_param('is', $id, $rutaRel);
            $stFoto->execute();
          }

          if ($prev && $prev !== $rutaRel) {
            ga_mark_and_delete($db, $prev, 'reemplazado');
          }
        }

        $db->commit();
        jok(['id' => $id, 'msg' => 'Cliente actualizado correctamente.']);
      } catch (Throwable $e) {
        $db->rollback();
        if ($newFileAbs && is_file($newFileAbs)) @unlink($newFileAbs);
        throw $e;
      }
    }

    case 'cliente_delete': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) jerror(400, 'ID de cliente invalido.');
      if (!is_client_in_company($db, $id, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');

      $db->begin_transaction();
      try {
        $prev = null;
        $stPrev = $db->prepare("SELECT ruta_foto FROM mtp_detalle_usuario WHERE id_usuario = ?");
        $stPrev->bind_param('i', $id);
        $stPrev->execute();
        if ($r = $stPrev->get_result()->fetch_assoc()) $prev = $r['ruta_foto'] ?? null;

        if ($prev) {
          ga_mark_and_delete($db, $prev, 'borrado');
        }

        $stA = $db->prepare("DELETE FROM cr_usuario_curso WHERE usuario_id = ?");
        $stA->bind_param('i', $id);
        $stA->execute();

        $stR = $db->prepare("DELETE FROM mtp_usuario_roles WHERE id_usuario = ?");
        $stR->bind_param('i', $id);
        $stR->execute();

        $stD = $db->prepare("DELETE FROM mtp_detalle_usuario WHERE id_usuario = ?");
        $stD->bind_param('i', $id);
        $stD->execute();

        $stU = $db->prepare("DELETE FROM mtp_usuarios WHERE id = ? AND id_empresa = ?");
        $stU->bind_param('ii', $id, $empresaId);
        $stU->execute();

        if ($stU->affected_rows <= 0) {
          $db->rollback();
          jerror(404, 'No se pudo eliminar el cliente.');
        }

        $db->commit();
        jok(['id' => $id, 'msg' => 'Cliente eliminado correctamente.']);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'cliente_cursos_list': {
      $usuarioId = (int)($_GET['usuario_id'] ?? 0);
      if ($usuarioId <= 0) jerror(400, 'usuario_id requerido.');
      if (!is_client_in_company($db, $usuarioId, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');

      $st = $db->prepare(
        "SELECT c.id, c.nombre
         FROM cr_usuario_curso uc
         JOIN cr_cursos c ON c.id = uc.curso_id
         WHERE uc.usuario_id = ?
           AND uc.activo = 1
           AND c.activo = 1
         ORDER BY c.nombre ASC"
      );
      $st->bind_param('i', $usuarioId);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data' => $rows]);
    }

    case 'cliente_curso_add': {
      $usuarioId = (int)($_POST['usuario_id'] ?? 0);
      $cursoId   = (int)($_POST['curso_id'] ?? 0);
      if ($usuarioId <= 0 || $cursoId <= 0) jerror(400, 'Parametros invalidos.');
      if (!is_client_in_company($db, $usuarioId, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');

      $stC = $db->prepare("SELECT id FROM cr_cursos WHERE id = ? AND activo = 1");
      $stC->bind_param('i', $cursoId);
      $stC->execute();
      if (!$stC->get_result()->fetch_assoc()) {
        jerror(404, 'El curso no existe o no esta activo.');
      }

      $st = $db->prepare(
        "INSERT INTO cr_usuario_curso (usuario_id, curso_id, activo)
         VALUES (?, ?, 1)
         ON DUPLICATE KEY UPDATE activo = 1"
      );
      $st->bind_param('ii', $usuarioId, $cursoId);
      $st->execute();

      jok(['usuario_id' => $usuarioId, 'curso_id' => $cursoId, 'msg' => 'Curso asignado correctamente.']);
    }

    case 'cliente_curso_remove': {
      $usuarioId = (int)($_POST['usuario_id'] ?? 0);
      $cursoId   = (int)($_POST['curso_id'] ?? 0);
      if ($usuarioId <= 0 || $cursoId <= 0) jerror(400, 'Parametros invalidos.');
      if (!is_client_in_company($db, $usuarioId, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');

      $st = $db->prepare("UPDATE cr_usuario_curso SET activo = 0 WHERE usuario_id = ? AND curso_id = ?");
      $st->bind_param('ii', $usuarioId, $cursoId);
      $st->execute();

      jok(['usuario_id' => $usuarioId, 'curso_id' => $cursoId, 'msg' => 'Curso retirado correctamente.']);
    }

    default:
      jerror(400, 'Accion no valida.');
  }
} catch (mysqli_sql_exception $e) {
  if ($db->errno) {
    @$db->rollback();
  }
  if ((int)$e->getCode() === 1062) {
    jerror(409, 'Ya existe un usuario con ese documento.');
  }
  if (in_array((int)$e->getCode(), [1451, 1452], true)) {
    jerror(409, 'No se pudo completar la operacion por referencias relacionadas.');
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $e->getMessage()]);
} catch (Throwable $e) {
  if ($db->errno) {
    @$db->rollback();
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $e->getMessage()]);
}
