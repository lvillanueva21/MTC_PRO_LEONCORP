<?php
// Ver 08-03-26
// modules/aula_virtual/api_cursos_admin.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/conexion.php';

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

function schedule_label(?string $inicioAt, ?string $finAt): string {
  if ($inicioAt && $finAt) {
    return format_dt_human($inicioAt) . ' - ' . format_dt_human($finAt);
  }
  return 'Indefinido';
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

function fetch_group_with_count(mysqli $db, int $groupId, int $empresaId): ?array {
  $st = $db->prepare(
    "SELECT
       g.id,
       g.curso_id,
       g.empresa_id,
       g.nombre,
       g.descripcion,
       g.inicio_at,
       g.fin_at,
       g.codigo,
       g.activo,
       g.created_at,
       g.updated_at,
       (
         SELECT COUNT(*)
         FROM cr_matriculas_grupo mg
         WHERE mg.grupo_id = g.id
           AND mg.estado = 1
       ) AS matriculados_activos_count,
       (
         SELECT COUNT(*)
         FROM cr_matriculas_grupo mg
         WHERE mg.grupo_id = g.id
       ) AS matriculas_total_count
     FROM cr_grupos g
     WHERE g.id = ?
       AND g.empresa_id = ?
     LIMIT 1"
  );
  $st->bind_param('ii', $groupId, $empresaId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if (!$row) return null;
  $row['rango_text'] = schedule_label($row['inicio_at'] ?? null, $row['fin_at'] ?? null);
  $row['estado_text'] = ((int)($row['activo'] ?? 0) === 1) ? 'ACTIVO' : 'INACTIVO';
  return $row;
}

try {
  acl_require_ids([1, 4]);

  if (current_role_id() !== 4) {
    jerror(403, 'Solo Administracion puede usar este endpoint.');
  }

  $empresaId = current_company_id();
  if ($empresaId <= 0) {
    jerror(400, 'No se pudo identificar la empresa activa.');
  }

  $actorId = current_user_id();
  if ($actorId <= 0) {
    jerror(401, 'No se pudo identificar al usuario autenticado.');
  }

  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'cursos_ro_list': {
      $q = trim((string)($_GET['q'] ?? ''));
      $estado = trim((string)($_GET['estado'] ?? ''));
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
      $offset = ($page - 1) * $perPage;

      $where = [];
      $types = '';
      $pars = [];

      if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(c.nombre LIKE ? OR c.descripcion LIKE ?)';
        $types .= 'ss';
        $pars[] = $like;
        $pars[] = $like;
      }
      if ($estado === '0' || $estado === '1') {
        $where[] = 'c.activo = ?';
        $types .= 'i';
        $pars[] = (int)$estado;
      }
      $wSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

      $stC = $db->prepare("SELECT COUNT(*) AS c FROM cr_cursos c {$wSql}");
      if ($types !== '') $stC->bind_param($types, ...$pars);
      $stC->execute();
      $total = (int)($stC->get_result()->fetch_assoc()['c'] ?? 0);

      $sql = "SELECT
                c.id,
                c.nombre,
                c.descripcion,
                c.activo,
                c.imagen_path,
                c.creado,
                c.actualizado,
                (
                  SELECT COUNT(*)
                  FROM cr_temas t
                  WHERE t.curso_id = c.id
                ) AS temas_count,
                (
                  SELECT COUNT(*)
                  FROM cr_grupos g
                  WHERE g.curso_id = c.id
                    AND g.empresa_id = ?
                ) AS grupos_count,
                (
                  SELECT COUNT(*)
                  FROM cr_matriculas_grupo mg
                  JOIN cr_grupos gg ON gg.id = mg.grupo_id
                  WHERE mg.curso_id = c.id
                    AND mg.estado = 1
                    AND gg.empresa_id = ?
                ) AS matriculados_count
              FROM cr_cursos c
              {$wSql}
              ORDER BY c.id DESC
              LIMIT ? OFFSET ?";

      $types2 = 'ii' . $types . 'ii';
      $pars2 = [$empresaId, $empresaId];
      foreach ($pars as $p) $pars2[] = $p;
      $pars2[] = $perPage;
      $pars2[] = $offset;

      $st = $db->prepare($sql);
      $st->bind_param($types2, ...$pars2);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      foreach ($rows as &$r) {
        $r['estado_text'] = ((int)($r['activo'] ?? 0) === 1) ? 'ACTIVO' : 'INACTIVO';
      }
      unset($r);

      jok([
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
      ]);
    }

    case 'temas_ro_list': {
      $cursoId = (int)($_GET['curso_id'] ?? 0);
      if ($cursoId <= 0) jerror(400, 'curso_id requerido.');

      $stCourse = $db->prepare("SELECT id, nombre FROM cr_cursos WHERE id = ? LIMIT 1");
      $stCourse->bind_param('i', $cursoId);
      $stCourse->execute();
      $course = $stCourse->get_result()->fetch_assoc();
      if (!$course) jerror(404, 'Curso no encontrado.');

      $st = $db->prepare(
        "SELECT id, curso_id, titulo, clase, video_url, miniatura_path, creado, actualizado
         FROM cr_temas
         WHERE curso_id = ?
         ORDER BY id ASC"
      );
      $st->bind_param('i', $cursoId);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      foreach ($rows as &$r) {
        $plain = trim(strip_tags((string)($r['clase'] ?? '')));
        if (function_exists('mb_substr')) {
          $r['clase_resumen'] = mb_substr($plain, 0, 220, 'UTF-8');
        } else {
          $r['clase_resumen'] = substr($plain, 0, 220);
        }
      }
      unset($r);

      jok([
        'curso' => $course,
        'data' => $rows,
      ]);
    }

    case 'grupos_list': {
      $cursoId = (int)($_GET['curso_id'] ?? 0);
      if ($cursoId <= 0) jerror(400, 'curso_id requerido.');

      $st = $db->prepare(
        "SELECT
           g.id,
           g.curso_id,
           g.empresa_id,
           g.nombre,
           g.descripcion,
           g.inicio_at,
           g.fin_at,
           g.codigo,
           g.activo,
           g.created_at,
           g.updated_at,
           (
             SELECT COUNT(*)
             FROM cr_matriculas_grupo mg
             WHERE mg.grupo_id = g.id
               AND mg.estado = 1
           ) AS matriculados_activos_count,
           (
             SELECT COUNT(*)
             FROM cr_matriculas_grupo mg
             WHERE mg.grupo_id = g.id
           ) AS matriculas_total_count
         FROM cr_grupos g
         WHERE g.curso_id = ?
           AND g.empresa_id = ?
         ORDER BY g.id DESC"
      );
      $st->bind_param('ii', $cursoId, $empresaId);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      foreach ($rows as &$r) {
        $r['rango_text'] = schedule_label($r['inicio_at'] ?? null, $r['fin_at'] ?? null);
        $r['estado_text'] = ((int)($r['activo'] ?? 0) === 1) ? 'ACTIVO' : 'INACTIVO';
      }
      unset($r);

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
        jerror(400, 'Debes completar inicio y fin en pareja, o dejar ambos vacios.');
      }
      if ($inicioAt !== null && $finAt !== null && strtotime($finAt) < strtotime($inicioAt)) {
        jerror(400, 'La fecha/hora fin no puede ser menor que inicio.');
      }

      $stCourse = $db->prepare("SELECT id FROM cr_cursos WHERE id = ? LIMIT 1");
      $stCourse->bind_param('i', $cursoId);
      $stCourse->execute();
      if (!$stCourse->get_result()->fetch_assoc()) {
        jerror(404, 'El curso no existe.');
      }

      $db->begin_transaction();
      try {
        $descParam = ($descripcion !== '') ? $descripcion : null;
        $st = $db->prepare(
          "INSERT INTO cr_grupos (curso_id, empresa_id, nombre, descripcion, inicio_at, fin_at, codigo, activo, created_at)
           VALUES (?, ?, ?, ?, ?, ?, NULL, ?, NOW())"
        );
        $st->bind_param('iissssi', $cursoId, $empresaId, $nombre, $descParam, $inicioAt, $finAt, $activo);
        $st->execute();
        $gid = (int)$db->insert_id;

        $tz = new DateTimeZone('America/Lima');
        $baseDt = ($inicioAt !== null) ? new DateTime($inicioAt, $tz) : new DateTime('now', $tz);
        $codigo = 'CR_' . $gid . $baseDt->format('my');

        $stUp = $db->prepare("UPDATE cr_grupos SET codigo = ?, updated_at = NOW() WHERE id = ?");
        $stUp->bind_param('si', $codigo, $gid);
        $stUp->execute();

        $db->commit();
        $group = fetch_group_with_count($db, $gid, $empresaId);
        jok([
          'msg' => 'Grupo creado correctamente.',
          'data' => $group,
        ]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'grupo_update': {
      $groupId = (int)($_POST['grupo_id'] ?? 0);
      $nombre = trim((string)($_POST['nombre'] ?? ''));
      $descripcion = trim((string)($_POST['descripcion'] ?? ''));
      $inicioAt = parse_nullable_datetime((string)($_POST['inicio_at'] ?? ''), 'inicio_at');
      $finAt = parse_nullable_datetime((string)($_POST['fin_at'] ?? ''), 'fin_at');

      if ($groupId <= 0) jerror(400, 'grupo_id requerido.');
      if ($nombre === '') jerror(400, 'El nombre del grupo es obligatorio.');
      if (($inicioAt === null && $finAt !== null) || ($inicioAt !== null && $finAt === null)) {
        jerror(400, 'Debes completar inicio y fin en pareja, o dejar ambos vacios.');
      }
      if ($inicioAt !== null && $finAt !== null && strtotime($finAt) < strtotime($inicioAt)) {
        jerror(400, 'La fecha/hora fin no puede ser menor que inicio.');
      }

      $stExists = $db->prepare("SELECT id FROM cr_grupos WHERE id = ? AND empresa_id = ? LIMIT 1");
      $stExists->bind_param('ii', $groupId, $empresaId);
      $stExists->execute();
      if (!$stExists->get_result()->fetch_assoc()) {
        jerror(404, 'Grupo no encontrado en tu empresa.');
      }

      $db->begin_transaction();
      try {
        $descParam = ($descripcion !== '') ? $descripcion : null;
        $st = $db->prepare(
          "UPDATE cr_grupos
           SET nombre = ?,
               descripcion = ?,
               inicio_at = ?,
               fin_at = ?,
               updated_at = NOW()
           WHERE id = ?
             AND empresa_id = ?"
        );
        $st->bind_param('ssssii', $nombre, $descParam, $inicioAt, $finAt, $groupId, $empresaId);
        $st->execute();

        $db->commit();
        $group = fetch_group_with_count($db, $groupId, $empresaId);
        jok([
          'msg' => 'Grupo actualizado correctamente.',
          'data' => $group,
        ]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'grupo_set_activo': {
      $groupId = (int)($_POST['grupo_id'] ?? 0);
      $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : -1;
      if ($groupId <= 0 || !in_array($activo, [0, 1], true)) {
        jerror(400, 'Parametros invalidos.');
      }

      $st = $db->prepare(
        "UPDATE cr_grupos
         SET activo = ?, updated_at = NOW()
         WHERE id = ?
           AND empresa_id = ?"
      );
      $st->bind_param('iii', $activo, $groupId, $empresaId);
      $st->execute();

      if ($st->affected_rows <= 0) {
        $stCheck = $db->prepare("SELECT id FROM cr_grupos WHERE id = ? AND empresa_id = ? LIMIT 1");
        $stCheck->bind_param('ii', $groupId, $empresaId);
        $stCheck->execute();
        if (!$stCheck->get_result()->fetch_assoc()) {
          jerror(404, 'Grupo no encontrado en tu empresa.');
        }
      }

      $group = fetch_group_with_count($db, $groupId, $empresaId);
      jok([
        'msg' => ($activo === 1) ? 'Grupo activado correctamente.' : 'Grupo desactivado correctamente.',
        'data' => $group,
      ]);
    }

    case 'grupo_delete': {
      $groupId = (int)($_POST['grupo_id'] ?? 0);
      if ($groupId <= 0) jerror(400, 'grupo_id requerido.');

      $db->begin_transaction();
      try {
        $stGroup = $db->prepare(
          "SELECT id, curso_id, codigo, nombre
           FROM cr_grupos
           WHERE id = ?
             AND empresa_id = ?
           LIMIT 1
           FOR UPDATE"
        );
        $stGroup->bind_param('ii', $groupId, $empresaId);
        $stGroup->execute();
        $group = $stGroup->get_result()->fetch_assoc();
        if (!$group) {
          $db->rollback();
          jerror(404, 'Grupo no encontrado en tu empresa.');
        }

        $activePairs = [];
        $stPairs = $db->prepare(
          "SELECT usuario_id, curso_id
           FROM cr_matriculas_grupo
           WHERE grupo_id = ?
             AND estado = 1
           FOR UPDATE"
        );
        $stPairs->bind_param('i', $groupId);
        $stPairs->execute();
        $rsPairs = $stPairs->get_result();
        while ($row = $rsPairs->fetch_assoc()) {
          $uid = (int)($row['usuario_id'] ?? 0);
          $cid = (int)($row['curso_id'] ?? 0);
          if ($uid > 0 && $cid > 0) {
            $activePairs[$uid . '|' . $cid] = [$uid, $cid];
          }
        }

        $stCount = $db->prepare("SELECT COUNT(*) AS c FROM cr_matriculas_grupo WHERE grupo_id = ?");
        $stCount->bind_param('i', $groupId);
        $stCount->execute();
        $matriculasTotal = (int)($stCount->get_result()->fetch_assoc()['c'] ?? 0);

        $stDeleteMg = $db->prepare("DELETE FROM cr_matriculas_grupo WHERE grupo_id = ?");
        $stDeleteMg->bind_param('i', $groupId);
        $stDeleteMg->execute();

        $stDeleteG = $db->prepare("DELETE FROM cr_grupos WHERE id = ? AND empresa_id = ?");
        $stDeleteG->bind_param('ii', $groupId, $empresaId);
        $stDeleteG->execute();
        if ($stDeleteG->affected_rows <= 0) {
          $db->rollback();
          jerror(404, 'No se pudo eliminar el grupo.');
        }

        foreach ($activePairs as $pair) {
          sync_usuario_curso($db, (int)$pair[0], (int)$pair[1], 0, $actorId);
        }

        $db->commit();
        jok([
          'msg' => 'Grupo eliminado correctamente.',
          'data' => [
            'grupo_id' => $groupId,
            'codigo' => (string)($group['codigo'] ?? ''),
            'nombre' => (string)($group['nombre'] ?? ''),
            'matriculas_eliminadas' => $matriculasTotal,
            'accesos_sincronizados' => count($activePairs),
          ],
        ]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    default:
      jerror(400, 'Accion no valida.');
  }
} catch (mysqli_sql_exception $e) {
  if ($db->errno) {
    @$db->rollback();
  }
  $errCode = (int)$e->getCode();
  $errMsg = (string)$e->getMessage();

  if ($errCode === 1062) {
    jerror(409, 'Ya existe un registro con esos datos.');
  }
  if (in_array($errCode, [1451, 1452], true)) {
    jerror(409, 'No se pudo completar la operacion por referencias relacionadas.');
  }
  if ($errCode === 1146) {
    jerror(500, 'Faltan tablas cr_grupos o cr_matriculas_grupo. Ejecuta la migracion SQL de Aula Virtual.');
  }
  if ($errCode === 1054 && stripos($errMsg, 'empresa_id') !== false) {
    jerror(500, 'Falta la columna empresa_id en cr_grupos. Ejecuta la migracion SQL de Aula Virtual.');
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $errMsg]);
} catch (Throwable $e) {
  if ($db->errno) {
    @$db->rollback();
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $e->getMessage()]);
}
