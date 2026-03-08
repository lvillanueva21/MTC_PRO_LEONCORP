<?php
// Ver 07-03-26
// modules/aula_virtual/api_administracion.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../consola/gestion_archivos.php';

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('America/Lima');

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

function current_user_id(): int {
  $u = currentUser();
  return (int)($u['id'] ?? $_SESSION['user']['id'] ?? $_SESSION['uid'] ?? 0);
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

function parse_nullable_datetime(string $raw, string $label): ?string {
  $raw = trim($raw);
  if ($raw === '') return null;

  $candidate = str_replace('T', ' ', $raw);
  if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $candidate)) {
    $candidate .= ':00';
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $candidate)) {
    jerror(400, "Formato de fecha/hora invalido para {$label}.");
  }

  $tz = new DateTimeZone('America/Lima');
  $dt = DateTime::createFromFormat('Y-m-d H:i:s', $candidate, $tz);
  if (!$dt || $dt->format('Y-m-d H:i:s') !== $candidate) {
    jerror(400, "Fecha/hora invalida para {$label}.");
  }

  return $dt->format('Y-m-d H:i:s');
}

function format_dt_human(?string $raw): string {
  if (!$raw) return '';
  $dt = new DateTime($raw, new DateTimeZone('America/Lima'));
  return $dt->format('d/m/Y H:i');
}

function sync_usuario_curso(mysqli $db, int $usuarioId, int $cursoId, int $activo, int $actorId): void {
  $st = $db->prepare(
    "INSERT INTO cr_usuario_curso (usuario_id, curso_id, activo, asignado_por, creado, actualizado)
     VALUES (?, ?, ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE
       activo = VALUES(activo),
       asignado_por = VALUES(asignado_por),
       actualizado = NOW()"
  );
  $st->bind_param('iiii', $usuarioId, $cursoId, $activo, $actorId);
  $st->execute();
}

function fetch_active_group_for_course(mysqli $db, int $cursoId): ?array {
  $st = $db->prepare(
    "SELECT id, curso_id, nombre, descripcion, inicio_at, fin_at, codigo, activo
     FROM cr_grupos
     WHERE curso_id = ?
       AND activo = 1
     ORDER BY id ASC
     LIMIT 1"
  );
  $st->bind_param('i', $cursoId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  return $row ?: null;
}

function matricular_cliente_en_grupo(mysqli $db, int $usuarioId, int $cursoId, int $grupoId, int $actorId): array {
  $stC = $db->prepare("SELECT id, nombre FROM cr_cursos WHERE id = ? AND activo = 1");
  $stC->bind_param('i', $cursoId);
  $stC->execute();
  $curso = $stC->get_result()->fetch_assoc();
  if (!$curso) {
    jerror(404, 'El curso no existe o no esta activo.');
  }

  $stG = $db->prepare(
    "SELECT id, curso_id, nombre, descripcion, inicio_at, fin_at, codigo, activo
     FROM cr_grupos
     WHERE id = ?
     LIMIT 1"
  );
  $stG->bind_param('i', $grupoId);
  $stG->execute();
  $grupo = $stG->get_result()->fetch_assoc();
  if (!$grupo) {
    jerror(404, 'El grupo no existe.');
  }
  if ((int)$grupo['activo'] !== 1) {
    jerror(400, 'Solo puedes matricular en grupos activos.');
  }
  if ((int)$grupo['curso_id'] !== $cursoId) {
    jerror(400, 'El grupo no pertenece al curso seleccionado.');
  }

  $db->begin_transaction();
  try {
    $stM = $db->prepare(
      "SELECT mg.id, mg.estado, mg.grupo_id, g.nombre AS grupo_nombre
       FROM cr_matriculas_grupo mg
       LEFT JOIN cr_grupos g ON g.id = mg.grupo_id
       WHERE mg.usuario_id = ?
         AND mg.curso_id = ?
       LIMIT 1
       FOR UPDATE"
    );
    $stM->bind_param('ii', $usuarioId, $cursoId);
    $stM->execute();
    $prev = $stM->get_result()->fetch_assoc();

    if ($prev && (int)$prev['estado'] === 1) {
      $db->rollback();
      $gname = trim((string)($prev['grupo_nombre'] ?? ''));
      if ($gname === '') $gname = '#' . (int)($prev['grupo_id'] ?? 0);
      jerror(409, "El usuario ya esta matriculado en el grupo {$gname} de este curso. Expulsalo primero si deseas cambiarlo.");
    }

    if ($prev) {
      $mid = (int)$prev['id'];
      $stU = $db->prepare(
        "UPDATE cr_matriculas_grupo
         SET grupo_id = ?,
             estado = 1,
             matriculado_at = NOW(),
             expulsado_at = NULL,
             expulsado_by = NULL,
             updated_at = NOW()
         WHERE id = ?"
      );
      $stU->bind_param('ii', $grupoId, $mid);
      $stU->execute();
    } else {
      $stI = $db->prepare(
        "INSERT INTO cr_matriculas_grupo (curso_id, grupo_id, usuario_id, estado, matriculado_at, created_at)
         VALUES (?, ?, ?, 1, NOW(), NOW())"
      );
      $stI->bind_param('iii', $cursoId, $grupoId, $usuarioId);
      $stI->execute();
    }

    sync_usuario_curso($db, $usuarioId, $cursoId, 1, $actorId);
    $db->commit();
  } catch (Throwable $e) {
    $db->rollback();
    throw $e;
  }

  return [
    'curso_id' => $cursoId,
    'curso_nombre' => (string)$curso['nombre'],
    'grupo_id' => (int)$grupo['id'],
    'grupo_nombre' => (string)$grupo['nombre'],
    'grupo_codigo' => (string)($grupo['codigo'] ?? ''),
    'grupo_inicio_at' => $grupo['inicio_at'],
    'grupo_fin_at' => $grupo['fin_at'],
    'estado' => 'ACTIVO',
  ];
}

function expulsar_cliente_de_curso(mysqli $db, int $usuarioId, int $cursoId, int $actorId): array {
  $db->begin_transaction();
  try {
    $st = $db->prepare(
      "SELECT mg.id, mg.curso_id, mg.grupo_id, c.nombre AS curso_nombre, g.nombre AS grupo_nombre, g.codigo AS grupo_codigo
       FROM cr_matriculas_grupo mg
       JOIN cr_cursos c ON c.id = mg.curso_id
       LEFT JOIN cr_grupos g ON g.id = mg.grupo_id
       WHERE mg.usuario_id = ?
         AND mg.curso_id = ?
         AND mg.estado = 1
       LIMIT 1
       FOR UPDATE"
    );
    $st->bind_param('ii', $usuarioId, $cursoId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) {
      $db->rollback();
      jerror(404, 'No existe una matricula activa para ese curso.');
    }

    $mid = (int)$row['id'];
    $stU = $db->prepare(
      "UPDATE cr_matriculas_grupo
       SET estado = 0,
           expulsado_at = NOW(),
           expulsado_by = ?,
           updated_at = NOW()
       WHERE id = ?"
    );
    $stU->bind_param('ii', $actorId, $mid);
    $stU->execute();

    sync_usuario_curso($db, $usuarioId, $cursoId, 0, $actorId);
    $db->commit();
  } catch (Throwable $e) {
    $db->rollback();
    throw $e;
  }

  return [
    'curso_id' => (int)($row['curso_id'] ?? 0),
    'curso_nombre' => (string)($row['curso_nombre'] ?? ''),
    'grupo_id' => (int)(($row['grupo_id'] ?? 0)),
    'grupo_nombre' => (string)($row['grupo_nombre'] ?? ''),
    'grupo_codigo' => (string)($row['grupo_codigo'] ?? ''),
    'estado' => 'EXPULSADO',
  ];
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

  $actorId = current_user_id();
  if ($actorId <= 0) {
    jerror(401, 'No se pudo identificar al usuario autenticado.');
  }

  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'cursos_list_activos': {
      $st = $db->prepare("SELECT id, nombre FROM cr_cursos WHERE activo = 1 ORDER BY nombre ASC");
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data' => $rows]);
    }

    case 'grupos_list': {
      $cursoId = (int)($_GET['curso_id'] ?? 0);
      if ($cursoId <= 0) jerror(400, 'curso_id requerido.');

      $st = $db->prepare(
        "SELECT id, curso_id, nombre, descripcion, inicio_at, fin_at, codigo, activo, created_at, updated_at
         FROM cr_grupos
         WHERE curso_id = ?
           AND activo = 1
         ORDER BY id DESC"
      );
      $st->bind_param('i', $cursoId);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data' => $rows]);
    }

    case 'grupo_create': {
      $cursoId = (int)($_POST['curso_id'] ?? 0);
      $nombre = trim((string)($_POST['nombre'] ?? ''));
      $descripcion = trim((string)($_POST['descripcion'] ?? ''));
      $inicioAt = parse_nullable_datetime((string)($_POST['inicio_at'] ?? ''), 'inicio_at');
      $finAt = parse_nullable_datetime((string)($_POST['fin_at'] ?? ''), 'fin_at');
      $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
      $activo = ($activo === 0) ? 0 : 1;

      if ($cursoId <= 0) jerror(400, 'curso_id requerido.');
      if ($nombre === '') jerror(400, 'El nombre del grupo es obligatorio.');

      if (($inicioAt === null && $finAt !== null) || ($inicioAt !== null && $finAt === null)) {
        jerror(400, 'Para rango de fechas debes completar inicio y fin en pareja, o dejar ambos vacios para grupo indefinido.');
      }
      if ($inicioAt !== null && $finAt !== null && strtotime($finAt) < strtotime($inicioAt)) {
        jerror(400, 'La fecha/hora de fin no puede ser menor que la fecha/hora de inicio.');
      }

      $stC = $db->prepare("SELECT id FROM cr_cursos WHERE id = ? AND activo = 1");
      $stC->bind_param('i', $cursoId);
      $stC->execute();
      if (!$stC->get_result()->fetch_assoc()) {
        jerror(404, 'El curso no existe o no esta activo.');
      }

      $db->begin_transaction();
      try {
        $descParam = ($descripcion !== '') ? $descripcion : null;
        $st = $db->prepare(
          "INSERT INTO cr_grupos (curso_id, nombre, descripcion, inicio_at, fin_at, codigo, activo, created_at)
           VALUES (?, ?, ?, ?, ?, NULL, ?, NOW())"
        );
        $st->bind_param('issssi', $cursoId, $nombre, $descParam, $inicioAt, $finAt, $activo);
        $st->execute();
        $gid = (int)$db->insert_id;

        $baseDt = ($inicioAt !== null)
          ? new DateTime($inicioAt, new DateTimeZone('America/Lima'))
          : new DateTime('now', new DateTimeZone('America/Lima'));
        $codigo = 'CR_' . $gid . $baseDt->format('my');

        $stU = $db->prepare("UPDATE cr_grupos SET codigo = ?, updated_at = NOW() WHERE id = ?");
        $stU->bind_param('si', $codigo, $gid);
        $stU->execute();

        $db->commit();

        $stR = $db->prepare(
          "SELECT id, curso_id, nombre, descripcion, inicio_at, fin_at, codigo, activo, created_at, updated_at
           FROM cr_grupos
           WHERE id = ?"
        );
        $stR->bind_param('i', $gid);
        $stR->execute();
        $grupo = $stR->get_result()->fetch_assoc();

        jok([
          'msg' => 'Grupo creado correctamente.',
          'data' => $grupo,
        ]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
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
        $where[] = 'EXISTS (SELECT 1 FROM cr_matriculas_grupo mg2 WHERE mg2.usuario_id = u.id AND mg2.curso_id = ? AND mg2.estado = 1)';
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
                COUNT(DISTINCT CASE WHEN mg.estado = 1 THEN mg.curso_id END) AS cursos_count
              FROM mtp_usuarios u
              LEFT JOIN cr_matriculas_grupo mg ON mg.usuario_id = u.id
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

        $stMG = $db->prepare("DELETE FROM cr_matriculas_grupo WHERE usuario_id = ?");
        $stMG->bind_param('i', $id);
        $stMG->execute();

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

    case 'cliente_matriculas_list': {
      $usuarioId = (int)($_GET['usuario_id'] ?? 0);
      if ($usuarioId <= 0) jerror(400, 'usuario_id requerido.');
      if (!is_client_in_company($db, $usuarioId, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');

      $st = $db->prepare(
        "SELECT
           mg.id,
           mg.curso_id,
           c.nombre AS curso_nombre,
           mg.grupo_id,
           g.nombre AS grupo_nombre,
           g.codigo AS grupo_codigo,
           g.inicio_at AS grupo_inicio_at,
           g.fin_at AS grupo_fin_at,
           mg.estado,
           mg.matriculado_at,
           mg.expulsado_at,
           mg.updated_at
         FROM cr_matriculas_grupo mg
         JOIN cr_cursos c ON c.id = mg.curso_id
         LEFT JOIN cr_grupos g ON g.id = mg.grupo_id
         WHERE mg.usuario_id = ?
         ORDER BY mg.updated_at DESC, mg.id DESC"
      );
      $st->bind_param('i', $usuarioId);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      foreach ($rows as &$r) {
        $isActive = ((int)$r['estado'] === 1);
        $r['estado_text'] = $isActive ? 'ACTIVO' : 'EXPULSADO';
        if (!empty($r['grupo_inicio_at']) && !empty($r['grupo_fin_at'])) {
          $r['rango_text'] = format_dt_human($r['grupo_inicio_at']) . ' - ' . format_dt_human($r['grupo_fin_at']);
        } else {
          $r['rango_text'] = 'Indefinido';
        }
      }
      unset($r);

      jok(['data' => $rows]);
    }

    case 'cliente_matricular': {
      $usuarioId = (int)($_POST['usuario_id'] ?? 0);
      $cursoId   = (int)($_POST['curso_id'] ?? 0);
      $grupoId   = (int)($_POST['grupo_id'] ?? 0);

      if ($usuarioId <= 0 || $cursoId <= 0 || $grupoId <= 0) {
        jerror(400, 'Parametros invalidos para matricular.');
      }
      if (!is_client_in_company($db, $usuarioId, $empresaId)) {
        jerror(404, 'Cliente no encontrado en tu empresa.');
      }

      $matricula = matricular_cliente_en_grupo($db, $usuarioId, $cursoId, $grupoId, $actorId);
      jok([
        'msg' => 'Cliente matriculado correctamente.',
        'data' => $matricula,
      ]);
    }

    case 'cliente_expulsar': {
      $usuarioId = (int)($_POST['usuario_id'] ?? 0);
      $cursoId   = (int)($_POST['curso_id'] ?? 0);

      if ($usuarioId <= 0 || $cursoId <= 0) {
        jerror(400, 'Parametros invalidos para expulsar.');
      }
      if (!is_client_in_company($db, $usuarioId, $empresaId)) {
        jerror(404, 'Cliente no encontrado en tu empresa.');
      }

      $info = expulsar_cliente_de_curso($db, $usuarioId, $cursoId, $actorId);
      jok([
        'msg' => 'Cliente expulsado correctamente del curso.',
        'data' => $info,
      ]);
    }

    // Compatibilidad temporal: responde con matriculas activas
    case 'cliente_cursos_list': {
      $usuarioId = (int)($_GET['usuario_id'] ?? 0);
      if ($usuarioId <= 0) jerror(400, 'usuario_id requerido.');
      if (!is_client_in_company($db, $usuarioId, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');

      $st = $db->prepare(
        "SELECT c.id, c.nombre, mg.grupo_id, g.nombre AS grupo_nombre, g.codigo AS grupo_codigo
         FROM cr_matriculas_grupo mg
         JOIN cr_cursos c ON c.id = mg.curso_id
         LEFT JOIN cr_grupos g ON g.id = mg.grupo_id
         WHERE mg.usuario_id = ?
           AND mg.estado = 1
         ORDER BY c.nombre ASC"
      );
      $st->bind_param('i', $usuarioId);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data' => $rows]);
    }

    // Compatibilidad temporal: requiere grupo_id o usa el primer grupo activo del curso
    case 'cliente_curso_add': {
      $usuarioId = (int)($_POST['usuario_id'] ?? 0);
      $cursoId   = (int)($_POST['curso_id'] ?? 0);
      $grupoId   = (int)($_POST['grupo_id'] ?? 0);

      if ($usuarioId <= 0 || $cursoId <= 0) {
        jerror(400, 'Parametros invalidos.');
      }
      if (!is_client_in_company($db, $usuarioId, $empresaId)) {
        jerror(404, 'Cliente no encontrado en tu empresa.');
      }
      if ($grupoId <= 0) {
        $grupo = fetch_active_group_for_course($db, $cursoId);
        if (!$grupo) {
          jerror(409, 'No hay grupos activos para ese curso. Crea un grupo antes de matricular.');
        }
        $grupoId = (int)$grupo['id'];
      }

      $matricula = matricular_cliente_en_grupo($db, $usuarioId, $cursoId, $grupoId, $actorId);
      jok([
        'msg' => 'Cliente matriculado correctamente.',
        'data' => $matricula,
      ]);
    }

    // Compatibilidad temporal: expulsa por curso
    case 'cliente_curso_remove': {
      $usuarioId = (int)($_POST['usuario_id'] ?? 0);
      $cursoId   = (int)($_POST['curso_id'] ?? 0);
      if ($usuarioId <= 0 || $cursoId <= 0) jerror(400, 'Parametros invalidos.');
      if (!is_client_in_company($db, $usuarioId, $empresaId)) jerror(404, 'Cliente no encontrado en tu empresa.');

      $info = expulsar_cliente_de_curso($db, $usuarioId, $cursoId, $actorId);
      jok([
        'msg' => 'Cliente expulsado correctamente del curso.',
        'data' => $info,
      ]);
    }

    default:
      jerror(400, 'Accion no valida.');
  }
} catch (mysqli_sql_exception $e) {
  if ($db->errno) {
    @$db->rollback();
  }
  $errCode = (int)$e->getCode();
  if ($errCode === 1062) {
    jerror(409, 'Ya existe un registro con esos datos.');
  }
  if (in_array($errCode, [1451, 1452], true)) {
    jerror(409, 'No se pudo completar la operacion por referencias relacionadas.');
  }
  if ($errCode === 1146) {
    jerror(500, 'Faltan tablas del Paso 1 (cr_grupos o cr_matriculas_grupo). Ejecuta el SQL de migracion.');
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $e->getMessage()]);
} catch (Throwable $e) {
  if ($db->errno) {
    @$db->rollback();
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $e->getMessage()]);
}
