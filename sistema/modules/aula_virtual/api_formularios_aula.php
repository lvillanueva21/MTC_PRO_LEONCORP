<?php
// Ver 07-03-26
// modules/aula_virtual/api_formularios_aula.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/formularios_lib.php';

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

function grupo_bloqueado(string $inicioAt = null, string $finAt = null): array {
  $inicioAt = trim((string)$inicioAt);
  $finAt = trim((string)$finAt);
  if ($inicioAt === '' || $finAt === '') {
    return ['blocked' => false, 'msg' => ''];
  }
  try {
    $tz = new DateTimeZone('America/Lima');
    $now = new DateTime('now', $tz);
    $ini = new DateTime($inicioAt, $tz);
    $fin = new DateTime($finAt, $tz);
    if ($now < $ini || $now > $fin) {
      return [
        'blocked' => true,
        'msg' => 'Este examen esta bloqueado por el rango horario del grupo: ' . $ini->format('d/m/Y H:i') . ' - ' . $fin->format('d/m/Y H:i') . '.',
      ];
    }
  } catch (Throwable $ignore) {
    return ['blocked' => true, 'msg' => 'No se pudo validar el rango horario del grupo.'];
  }
  return ['blocked' => false, 'msg' => ''];
}

function load_aula_access(mysqli $db, int $formId, int $userId, int $empresaId, bool $forUpdate = false): ?array {
  $sql = "SELECT
            f.id,
            f.empresa_id,
            f.modo,
            f.tipo,
            f.grupo_id,
            f.curso_id,
            f.tema_id,
            f.titulo,
            f.descripcion,
            f.estado,
            f.intentos_max,
            f.tiempo_activo,
            f.duracion_min,
            f.nota_min,
            f.mostrar_resultado,
            f.requisito_cumplimiento,
            f.created_at,
            f.updated_at,
            g.nombre AS grupo_nombre,
            g.codigo AS grupo_codigo,
            g.inicio_at AS grupo_inicio_at,
            g.fin_at AS grupo_fin_at,
            c.nombre AS curso_nombre,
            t.titulo AS tema_titulo
          FROM cr_formularios f
          JOIN cr_grupos g ON g.id = f.grupo_id
          JOIN cr_matriculas_grupo mg ON mg.grupo_id = f.grupo_id
            AND mg.curso_id = f.curso_id
            AND mg.usuario_id = ?
            AND mg.estado = 1
          JOIN mtp_usuarios ux ON ux.id = mg.usuario_id
          LEFT JOIN cr_cursos c ON c.id = f.curso_id
          LEFT JOIN cr_temas t ON t.id = f.tema_id
          WHERE f.id = ?
            AND f.empresa_id = ?
            AND ux.id_empresa = ?
            AND g.empresa_id = ux.id_empresa
            AND g.activo = 1
            AND f.modo = 'AULA'
            AND f.tipo = 'EXAMEN'
          LIMIT 1";
  if ($forUpdate) $sql .= " FOR UPDATE";
  $st = $db->prepare($sql);
  $st->bind_param('iiii', $userId, $formId, $empresaId, $empresaId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  return $row ?: null;
}

function aula_submit_response(array $final, array $form): array {
  $show = ((int)($form['mostrar_resultado'] ?? 1) === 1);
  if ($show) {
    return [
      'status' => (string)$final['status'],
      'attempt_id' => (int)$final['attempt_id'],
      'nota_final' => (float)$final['nota_final'],
      'puntaje_obtenido' => (float)$final['puntaje_obtenido'],
      'aprobado' => (int)$final['aprobado'],
      'submitted_at' => (string)$final['submitted_at'],
      'mostrar_resultado' => 1,
      'already_submitted' => (int)($final['already_submitted'] ?? 0),
    ];
  }
  return [
    'status' => (string)$final['status'],
    'attempt_id' => (int)$final['attempt_id'],
    'submitted_at' => (string)$final['submitted_at'],
    'mostrar_resultado' => 0,
    'already_submitted' => (int)($final['already_submitted'] ?? 0),
    'msg' => 'Tu examen fue enviado correctamente.',
  ];
}

try {
  acl_require_ids([7, 1, 4, 6]);
  if (current_role_id() !== 7) {
    jerror(403, 'Solo Cliente (rol activo 7) puede usar esta API.');
  }

  $empresaId = current_company_id();
  $userId = current_user_id();
  if ($empresaId <= 0 || $userId <= 0) {
    jerror(401, 'No se pudo identificar al usuario autenticado.');
  }

  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'aula_forms_list': {
      $cursoId = (int)($_GET['curso_id'] ?? 0);
      $temaId = (int)($_GET['tema_id'] ?? 0);

      $where = [
        'mg.usuario_id = ?',
        'mg.estado = 1',
        'f.empresa_id = ?',
        "f.modo = 'AULA'",
        "f.tipo = 'EXAMEN'",
        "f.estado = 'PUBLICADO'",
        'g.empresa_id = ux.id_empresa',
        'ux.id_empresa = ?',
        'g.activo = 1',
      ];
      $types = 'iii';
      $pars = [$userId, $empresaId, $empresaId];

      if ($cursoId > 0) {
        $where[] = 'f.curso_id = ?';
        $types .= 'i';
        $pars[] = $cursoId;
      }
      if ($temaId > 0) {
        $where[] = 'f.tema_id = ?';
        $types .= 'i';
        $pars[] = $temaId;
      }

      $wSql = 'WHERE ' . implode(' AND ', $where);
      $sql = "SELECT
                f.id,
                f.titulo,
                f.descripcion,
                f.curso_id,
                f.tema_id,
                f.grupo_id,
                f.nota_min,
                f.intentos_max,
                f.tiempo_activo,
                f.duracion_min,
                f.mostrar_resultado,
                f.requisito_cumplimiento,
                g.nombre AS grupo_nombre,
                g.codigo AS grupo_codigo,
                g.inicio_at AS grupo_inicio_at,
                g.fin_at AS grupo_fin_at,
                c.nombre AS curso_nombre,
                t.titulo AS tema_titulo,
                (
                  SELECT COUNT(*) FROM cr_formulario_intentos i
                  WHERE i.formulario_id = f.id
                    AND i.usuario_id = mg.usuario_id
                ) AS intentos_usados
              FROM cr_formularios f
              JOIN cr_matriculas_grupo mg ON mg.grupo_id = f.grupo_id
                AND mg.curso_id = f.curso_id
              JOIN mtp_usuarios ux ON ux.id = mg.usuario_id
              JOIN cr_grupos g ON g.id = f.grupo_id
              LEFT JOIN cr_cursos c ON c.id = f.curso_id
              LEFT JOIN cr_temas t ON t.id = f.tema_id
              {$wSql}
              ORDER BY f.id DESC";

      $st = $db->prepare($sql);
      $st->bind_param($types, ...$pars);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      foreach ($rows as &$r) {
        $bloqueo = grupo_bloqueado((string)$r['grupo_inicio_at'], (string)$r['grupo_fin_at']);
        $r['blocked_by_group_range'] = $bloqueo['blocked'] ? 1 : 0;
        $r['blocked_msg'] = $bloqueo['msg'];
        $used = (int)($r['intentos_usados'] ?? 0);
        $max = (int)($r['intentos_max'] ?? 1);
        $r['intentos_restantes'] = max(0, $max - $used);
      }
      unset($r);

      jok(['data' => $rows]);
    }

    case 'aula_form_info': {
      $formId = (int)($_GET['form_id'] ?? 0);
      if ($formId <= 0) jerror(400, 'form_id requerido.');

      $form = load_aula_access($db, $formId, $userId, $empresaId, false);
      if (!$form) jerror(404, 'No tienes acceso a este examen.');
      if (strtoupper((string)$form['estado']) !== 'PUBLICADO') {
        jerror(403, 'El examen no esta publicado.');
      }

      $blocked = grupo_bloqueado((string)$form['grupo_inicio_at'], (string)$form['grupo_fin_at']);
      $questions = avf_load_form_questions_public($db, $formId);

      jok([
        'data' => [
          'form' => $form,
          'questions' => $questions,
          'blocked' => $blocked['blocked'] ? 1 : 0,
          'blocked_msg' => $blocked['msg'],
        ],
      ]);
    }

    case 'aula_attempt_status': {
      $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
      if ($token === '') jerror(400, 'token requerido.');

      $db->begin_transaction();
      try {
        $attempt = avf_load_attempt_by_token($db, $token, true);
        if (!$attempt) {
          $db->rollback();
          jerror(404, 'Intento no encontrado.');
        }
        if (strtoupper((string)$attempt['form_modo']) !== 'AULA' || (int)$attempt['usuario_id'] !== $userId) {
          $db->rollback();
          jerror(403, 'No tienes acceso a este intento.');
        }

        $form = load_aula_access($db, (int)$attempt['formulario_id'], $userId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(403, 'No tienes acceso al examen.');
        }
        $blocked = grupo_bloqueado((string)$form['grupo_inicio_at'], (string)$form['grupo_fin_at']);
        if ($blocked['blocked'] && strtoupper((string)$attempt['status']) === 'EN_PROGRESO') {
          $db->rollback();
          jerror(403, $blocked['msg']);
        }

        $formMini = [
          'id' => (int)$attempt['formulario_id'],
          'mostrar_resultado' => (int)$attempt['form_mostrar_resultado'],
          'nota_min' => (float)$attempt['form_nota_min'],
        ];

        if (avf_is_attempt_expired($attempt)) {
          $final = avf_score_and_finalize_attempt($db, $attempt, $formMini, null);
          $db->commit();
          jok(['data' => aula_submit_response($final, $formMini)]);
        }

        $responses = avf_fetch_saved_responses_map($db, (int)$attempt['id']);
        $payload = avf_build_attempt_status_payload($attempt, $formMini, $responses);
        $db->commit();
        jok(['data' => $payload]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'aula_attempt_start': {
      $formId = (int)($_POST['form_id'] ?? 0);
      if ($formId <= 0) jerror(400, 'form_id requerido.');

      $db->begin_transaction();
      try {
        $form = load_aula_access($db, $formId, $userId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(404, 'No tienes acceso a este examen.');
        }
        if (strtoupper((string)$form['estado']) !== 'PUBLICADO') {
          $db->rollback();
          jerror(403, 'El examen no esta publicado.');
        }

        $blocked = grupo_bloqueado((string)$form['grupo_inicio_at'], (string)$form['grupo_fin_at']);
        if ($blocked['blocked']) {
          $db->rollback();
          jerror(403, $blocked['msg']);
        }

        $stForm = $db->prepare(
          "SELECT id, intentos_max, tiempo_activo, duracion_min, nota_min, mostrar_resultado
           FROM cr_formularios
           WHERE id = ?
           LIMIT 1
           FOR UPDATE"
        );
        $stForm->bind_param('i', $formId);
        $stForm->execute();
        $formLock = $stForm->get_result()->fetch_assoc();
        if (!$formLock) {
          $db->rollback();
          jerror(404, 'Formulario no encontrado.');
        }

        $expiresAt = null;
        if ((int)$formLock['tiempo_activo'] === 1 && (int)$formLock['duracion_min'] > 0) {
          $now = avf_now_lima();
          $now->modify('+' . (int)$formLock['duracion_min'] . ' minutes');
          $expiresAt = $now->format('Y-m-d H:i:s');
        }

        $attemptId = 0;
        $token = '';
        $attempts = avf_load_aula_attempts_for_user($db, $formId, $userId, true);

        for ($retry = 0; $retry < 4; $retry++) {
          foreach ($attempts as $a) {
            if (strtoupper((string)$a['status']) !== 'EN_PROGRESO') continue;
            $attemptOpen = avf_load_attempt_by_token($db, (string)$a['token'], true);
            if (!$attemptOpen) continue;
            if (!avf_is_attempt_expired($attemptOpen)) {
              $responses = avf_fetch_saved_responses_map($db, (int)$attemptOpen['id']);
              $payload = avf_build_attempt_status_payload($attemptOpen, $formLock, $responses);
              $db->commit();
              jok([
                'msg' => 'Ya tienes un intento en progreso. Se reutilizo el mismo token.',
                'data' => $payload,
              ]);
            }
            avf_score_and_finalize_attempt($db, $attemptOpen, $formLock, null);
          }

          $attempts = avf_load_aula_attempts_for_user($db, $formId, $userId, true);
          $usedNumbers = array_map(function ($r) {
            return (int)$r['intento_nro'];
          }, $attempts);
          $nextNro = avf_find_next_attempt_number($usedNumbers, (int)$formLock['intentos_max']);
          if ($nextNro <= 0) {
            $db->rollback();
            jerror(409, 'No tienes intentos disponibles para este examen.');
          }

          $token = '';
          for ($i = 0; $i < 8; $i++) {
            $candidate = avf_random_token(20);
            $stTok = $db->prepare("SELECT id FROM cr_formulario_intentos WHERE token = ? LIMIT 1");
            $stTok->bind_param('s', $candidate);
            $stTok->execute();
            if (!$stTok->get_result()->fetch_assoc()) {
              $token = $candidate;
              break;
            }
          }
          if ($token === '') {
            $db->rollback();
            jerror(500, 'No se pudo generar token de intento.');
          }

          try {
            $stIns = $db->prepare(
              "INSERT INTO cr_formulario_intentos
                 (formulario_id, modo, usuario_id, tipo_doc_id, nro_doc, nombres, apellidos, celular, categorias_json,
                  intento_nro, token, status, start_at, expires_at, created_at, updated_at)
               VALUES (?, 'AULA', ?, NULL, NULL, NULL, NULL, NULL, NULL, ?, ?, 'EN_PROGRESO', NOW(), ?, NOW(), NOW())"
            );
            $stIns->bind_param('iiiss', $formId, $userId, $nextNro, $token, $expiresAt);
            $stIns->execute();
            $attemptId = (int)$db->insert_id;
            break;
          } catch (mysqli_sql_exception $e) {
            if ((int)$e->getCode() !== 1062) {
              throw $e;
            }
            $attempts = avf_load_aula_attempts_for_user($db, $formId, $userId, true);
          }
        }

        if ($attemptId <= 0) {
          $attempts = avf_load_aula_attempts_for_user($db, $formId, $userId, true);
          foreach ($attempts as $a) {
            if (strtoupper((string)$a['status']) !== 'EN_PROGRESO') continue;
            $attemptOpen = avf_load_attempt_by_token($db, (string)$a['token'], true);
            if (!$attemptOpen) continue;
            if (!avf_is_attempt_expired($attemptOpen)) {
              $responses = avf_fetch_saved_responses_map($db, (int)$attemptOpen['id']);
              $payload = avf_build_attempt_status_payload($attemptOpen, $formLock, $responses);
              $db->commit();
              jok([
                'msg' => 'Ya tienes un intento en progreso. Se reutilizo el mismo token.',
                'data' => $payload,
              ]);
            }
          }
          $db->rollback();
          jerror(409, 'No se pudo iniciar el intento. Intenta nuevamente.');
        }

        $attemptNew = avf_load_attempt_by_token($db, $token, false);
        $payload = avf_build_attempt_status_payload($attemptNew, $formLock, []);

        $db->commit();
        jok(['msg' => 'Intento iniciado correctamente.', 'data' => $payload]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'aula_attempt_save': {
      $token = trim((string)($_POST['token'] ?? ''));
      if ($token === '') jerror(400, 'token requerido.');
      $responses = avf_parse_respuestas_payload($_POST['respuestas'] ?? '');

      $db->begin_transaction();
      try {
        $attempt = avf_load_attempt_by_token($db, $token, true);
        if (!$attempt) {
          $db->rollback();
          jerror(404, 'Intento no encontrado.');
        }
        if (strtoupper((string)$attempt['form_modo']) !== 'AULA' || (int)$attempt['usuario_id'] !== $userId) {
          $db->rollback();
          jerror(403, 'No tienes acceso a este intento.');
        }

        $form = load_aula_access($db, (int)$attempt['formulario_id'], $userId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(403, 'No tienes acceso al examen.');
        }

        $blocked = grupo_bloqueado((string)$form['grupo_inicio_at'], (string)$form['grupo_fin_at']);
        if ($blocked['blocked'] && strtoupper((string)$attempt['status']) === 'EN_PROGRESO') {
          $db->rollback();
          jerror(403, $blocked['msg']);
        }

        $formMini = [
          'id' => (int)$attempt['formulario_id'],
          'mostrar_resultado' => (int)$attempt['form_mostrar_resultado'],
          'nota_min' => (float)$attempt['form_nota_min'],
        ];

        if (strtoupper((string)$attempt['status']) === 'ENVIADO') {
          $responsesMap = avf_fetch_saved_responses_map($db, (int)$attempt['id']);
          $payload = avf_build_attempt_status_payload($attempt, $formMini, $responsesMap);
          $db->commit();
          jok(['msg' => 'El intento ya fue enviado.', 'data' => $payload]);
        }

        if (avf_is_attempt_expired($attempt)) {
          $final = avf_score_and_finalize_attempt($db, $attempt, $formMini, $responses);
          $db->commit();
          jok(['msg' => 'El tiempo expiro. El intento fue auto-enviado.', 'data' => aula_submit_response($final, $formMini)]);
        }

        $saved = avf_upsert_attempt_responses($db, (int)$attempt['id'], (int)$attempt['formulario_id'], $responses);
        $stTouch = $db->prepare("UPDATE cr_formulario_intentos SET last_saved_at = NOW(), updated_at = NOW() WHERE id = ?");
        $aid = (int)$attempt['id'];
        $stTouch->bind_param('i', $aid);
        $stTouch->execute();

        $attemptAfter = avf_load_attempt_by_token($db, $token, false);
        $responsesMap = avf_fetch_saved_responses_map($db, $aid);
        $payload = avf_build_attempt_status_payload($attemptAfter, $formMini, $responsesMap);
        $payload['saved_count'] = $saved;

        $db->commit();
        jok(['msg' => 'Respuestas guardadas correctamente.', 'data' => $payload]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'aula_attempt_submit': {
      $token = trim((string)($_POST['token'] ?? ''));
      if ($token === '') jerror(400, 'token requerido.');
      $responses = avf_parse_respuestas_payload($_POST['respuestas'] ?? '');

      $db->begin_transaction();
      try {
        $attempt = avf_load_attempt_by_token($db, $token, true);
        if (!$attempt) {
          $db->rollback();
          jerror(404, 'Intento no encontrado.');
        }
        if (strtoupper((string)$attempt['form_modo']) !== 'AULA' || (int)$attempt['usuario_id'] !== $userId) {
          $db->rollback();
          jerror(403, 'No tienes acceso a este intento.');
        }

        $form = load_aula_access($db, (int)$attempt['formulario_id'], $userId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(403, 'No tienes acceso al examen.');
        }
        $blocked = grupo_bloqueado((string)$form['grupo_inicio_at'], (string)$form['grupo_fin_at']);
        if ($blocked['blocked'] && strtoupper((string)$attempt['status']) === 'EN_PROGRESO') {
          $db->rollback();
          jerror(403, $blocked['msg']);
        }

        $formMini = [
          'id' => (int)$attempt['formulario_id'],
          'mostrar_resultado' => (int)$attempt['form_mostrar_resultado'],
          'nota_min' => (float)$attempt['form_nota_min'],
        ];

        if (strtoupper((string)$attempt['status']) === 'ENVIADO') {
          $final = [
            'attempt_id' => (int)$attempt['id'],
            'status' => 'ENVIADO',
            'puntaje_obtenido' => (float)($attempt['puntaje_obtenido'] ?? 0),
            'nota_final' => (float)($attempt['nota_final'] ?? 0),
            'aprobado' => (int)($attempt['aprobado'] ?? 0),
            'submitted_at' => (string)($attempt['submitted_at'] ?? ''),
            'already_submitted' => 1,
          ];
          $db->commit();
          jok(['msg' => 'El intento ya habia sido enviado.', 'data' => aula_submit_response($final, $formMini)]);
        }

        if (strtoupper((string)$attempt['status']) !== 'EN_PROGRESO') {
          $db->rollback();
          jerror(409, 'El intento no se encuentra en estado EN_PROGRESO.');
        }

        $final = avf_score_and_finalize_attempt($db, $attempt, $formMini, $responses);
        $db->commit();
        jok(['msg' => 'Examen enviado correctamente.', 'data' => aula_submit_response($final, $formMini)]);
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
  $code = (int)$e->getCode();
  if ($code === 1062) {
    jerror(409, 'Ya existe un registro con esos datos.');
  }
  if (in_array($code, [1451, 1452], true)) {
    jerror(409, 'No se pudo completar la operacion por referencias relacionadas.');
  }
  if ($code === 1146) {
    jerror(500, 'Faltan tablas de Formularios. Ejecuta la migracion SQL 2026_03_formularios_examen_v1.sql');
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $e->getMessage()]);
} catch (Throwable $e) {
  if ($db->errno) {
    @$db->rollback();
  }
  jerror(500, 'Error interno del servidor.', ['dev' => $e->getMessage()]);
}
