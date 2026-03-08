<?php
// Ver 07-03-26
// modules/aula_virtual/api_formularios_fast.php
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

function ensure_fast_form_published(mysqli $db, string $code): array {
  $form = avf_load_form_public_by_code($db, $code);
  if (!$form) {
    jerror(404, 'Formulario FAST no encontrado.');
  }
  if (strtoupper((string)$form['tipo']) !== 'EXAMEN') {
    jerror(400, 'En esta fase solo se permite tipo EXAMEN.');
  }
  if (strtoupper((string)$form['estado']) !== 'PUBLICADO') {
    jerror(403, 'El formulario no esta disponible para resolver.');
  }
  return $form;
}

function fast_participant_from_post(mysqli $db, array $form): array {
  $camposFast = avf_parse_campos_fast($form['campos_fast'] ?? '');
  $tiposMap = avf_load_tipos_doc_map($db);
  $allowedDocs = array_map('intval', (array)$camposFast['tipos_doc_permitidos']);
  $dniId = avf_doc_id_by_code($db, 'DNI');
  if ($dniId > 0 && !in_array($dniId, $allowedDocs, true)) {
    array_unshift($allowedDocs, $dniId);
  }
  $allowedDocs = array_values(array_unique(array_filter($allowedDocs, function ($v) {
    return (int)$v > 0;
  })));
  if (!$allowedDocs) {
    $allowedDocs = [1];
  }

  $tipoDocId = (int)($_POST['tipo_doc_id'] ?? 0);
  if ($tipoDocId <= 0) {
    $tipoDocId = ($dniId > 0) ? $dniId : (int)($allowedDocs[0] ?? 1);
  }
  if (!isset($tiposMap[$tipoDocId])) {
    jerror(400, 'Tipo de documento invalido.');
  }
  if (!in_array($tipoDocId, $allowedDocs, true)) {
    jerror(400, 'El tipo de documento no esta permitido para este formulario.');
  }

  $docCode = strtoupper((string)$tiposMap[$tipoDocId]['codigo']);
  $docRaw = (string)($_POST['nro_doc'] ?? '');
  $docValidation = avf_validate_doc_number($docCode, $docRaw);
  if (empty($docValidation['ok'])) {
    jerror(400, (string)($docValidation['msg'] ?? 'Documento invalido.'));
  }
  $nroDoc = (string)$docValidation['doc'];

  $nombres = trim((string)($_POST['nombres'] ?? ''));
  $apellidos = trim((string)($_POST['apellidos'] ?? ''));
  $celular = trim((string)($_POST['celular'] ?? ''));

  if (!empty($camposFast['pedir_nombres']) && $nombres !== '') {
    $nombres = function_exists('mb_substr') ? mb_substr($nombres, 0, 120, 'UTF-8') : substr($nombres, 0, 120);
  } elseif (empty($camposFast['pedir_nombres'])) {
    $nombres = '';
  }
  if (!empty($camposFast['pedir_apellidos']) && $apellidos !== '') {
    $apellidos = function_exists('mb_substr') ? mb_substr($apellidos, 0, 120, 'UTF-8') : substr($apellidos, 0, 120);
  } elseif (empty($camposFast['pedir_apellidos'])) {
    $apellidos = '';
  }
  if (!empty($camposFast['pedir_celular']) && $celular !== '') {
    $celular = preg_replace('/[^0-9+]/', '', $celular);
    $celular = substr((string)$celular, 0, 20);
  } elseif (empty($camposFast['pedir_celular'])) {
    $celular = '';
  }

  $categorias = [];
  if (!empty($camposFast['pedir_categorias'])) {
    $categorias = avf_parse_int_list($_POST['categorias'] ?? []);
    if ($categorias) {
      $valid = [];
      $st = $db->prepare("SELECT id FROM cq_categorias_licencia");
      $st->execute();
      $rs = $st->get_result();
      while ($r = $rs->fetch_assoc()) {
        $valid[(int)$r['id']] = true;
      }
      $clean = [];
      foreach ($categorias as $cid) {
        if (isset($valid[(int)$cid])) $clean[(int)$cid] = (int)$cid;
      }
      $categorias = array_values($clean);
    }
  }

  return [
    'tipo_doc_id' => $tipoDocId,
    'tipo_doc_codigo' => $docCode,
    'nro_doc' => $nroDoc,
    'nombres' => $nombres,
    'apellidos' => $apellidos,
    'celular' => $celular,
    'categorias' => $categorias,
    'campos_fast' => $camposFast,
  ];
}

function fast_remaining_attempts(mysqli $db, int $formId, int $intentosMax, int $tipoDocId, string $nroDoc): int {
  $st = $db->prepare(
    "SELECT COUNT(*) AS c
     FROM cr_formulario_intentos
     WHERE formulario_id = ?
       AND tipo_doc_id = ?
       AND nro_doc = ?"
  );
  $st->bind_param('iis', $formId, $tipoDocId, $nroDoc);
  $st->execute();
  $used = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
  return max(0, $intentosMax - $used);
}

function fast_submit_response(array $final, array $form): array {
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
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'form_public_info': {
      $code = trim((string)($_GET['code'] ?? ''));
      if ($code === '') jerror(400, 'code requerido.');

      $form = ensure_fast_form_published($db, $code);
      $camposFast = avf_parse_campos_fast($form['campos_fast'] ?? '');
      $allDocs = avf_load_tipos_doc($db);
      $allowedIds = array_map('intval', (array)$camposFast['tipos_doc_permitidos']);
      $dniId = avf_doc_id_by_code($db, 'DNI');
      if ($dniId > 0 && !in_array($dniId, $allowedIds, true)) {
        array_unshift($allowedIds, $dniId);
      }
      $allowedIds = array_values(array_unique(array_filter($allowedIds, function ($v) {
        return (int)$v > 0;
      })));
      $tiposDoc = array_values(array_filter($allDocs, function ($r) use ($allowedIds) {
        return in_array((int)$r['id'], $allowedIds, true);
      }));

      $remaining = null;
      $tipoDocId = (int)($_GET['tipo_doc_id'] ?? 0);
      $nroDoc = trim((string)($_GET['nro_doc'] ?? ''));
      if ($tipoDocId > 0 && $nroDoc !== '') {
        $remaining = fast_remaining_attempts(
          $db,
          (int)$form['id'],
          (int)$form['intentos_max'],
          $tipoDocId,
          avf_normalize_doc_number($nroDoc)
        );
      }

      $questions = avf_load_form_questions_public($db, (int)$form['id']);
      jok([
        'data' => [
          'form' => [
            'id' => (int)$form['id'],
            'modo' => (string)$form['modo'],
            'tipo' => (string)$form['tipo'],
            'titulo' => (string)$form['titulo'],
            'descripcion' => (string)$form['descripcion'],
            'estado' => (string)$form['estado'],
            'intentos_max' => (int)$form['intentos_max'],
            'tiempo_activo' => (int)$form['tiempo_activo'],
            'duracion_min' => ($form['duracion_min'] !== null) ? (int)$form['duracion_min'] : null,
            'nota_min' => (float)$form['nota_min'],
            'mostrar_resultado' => (int)$form['mostrar_resultado'],
            'public_code' => (string)$form['public_code'],
            'campos_fast' => $camposFast,
          ],
          'tipos_doc' => $tiposDoc,
          'categorias' => !empty($camposFast['pedir_categorias']) ? avf_load_categorias($db) : [],
          'questions' => $questions,
          'intentos_restantes' => $remaining,
        ],
      ]);
    }

    case 'attempt_status': {
      $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
      if ($token === '') jerror(400, 'token requerido.');

      $db->begin_transaction();
      try {
        $attempt = avf_load_attempt_by_token($db, $token, true);
        if (!$attempt) {
          $db->rollback();
          jerror(404, 'Intento no encontrado.');
        }
        if (strtoupper((string)$attempt['form_modo']) !== 'FAST') {
          $db->rollback();
          jerror(403, 'Token invalido para FAST.');
        }

        $form = [
          'id' => (int)$attempt['formulario_id'],
          'mostrar_resultado' => (int)$attempt['form_mostrar_resultado'],
          'nota_min' => (float)$attempt['form_nota_min'],
        ];

        if (avf_is_attempt_expired($attempt)) {
          $final = avf_score_and_finalize_attempt($db, $attempt, $form, null);
          $db->commit();
          jok(['data' => fast_submit_response($final, $form)]);
        }

        $responses = avf_fetch_saved_responses_map($db, (int)$attempt['id']);
        $payload = avf_build_attempt_status_payload($attempt, $form, $responses);
        $db->commit();
        jok(['data' => $payload]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'attempt_start': {
      $code = trim((string)($_POST['code'] ?? ''));
      if ($code === '') jerror(400, 'code requerido.');

      $form = ensure_fast_form_published($db, $code);
      $participant = fast_participant_from_post($db, $form);

      $db->begin_transaction();
      try {
        $stForm = $db->prepare(
          "SELECT id, modo, tipo, estado, intentos_max, tiempo_activo, duracion_min, nota_min, mostrar_resultado
           FROM cr_formularios
           WHERE id = ?
           LIMIT 1
           FOR UPDATE"
        );
        $formId = (int)$form['id'];
        $stForm->bind_param('i', $formId);
        $stForm->execute();
        $lockedForm = $stForm->get_result()->fetch_assoc();
        if (!$lockedForm) {
          $db->rollback();
          jerror(404, 'Formulario no encontrado.');
        }
        if (strtoupper((string)$lockedForm['estado']) !== 'PUBLICADO') {
          $db->rollback();
          jerror(403, 'El formulario no esta disponible para resolver.');
        }

        $expiresAt = null;
        if ((int)$lockedForm['tiempo_activo'] === 1 && (int)$lockedForm['duracion_min'] > 0) {
          $now = avf_now_lima();
          $now->modify('+' . (int)$lockedForm['duracion_min'] . ' minutes');
          $expiresAt = $now->format('Y-m-d H:i:s');
        }

        $categoriasJson = $participant['categorias'] ? avf_json_encode($participant['categorias']) : null;
        $nombres = ($participant['nombres'] !== '') ? $participant['nombres'] : null;
        $apellidos = ($participant['apellidos'] !== '') ? $participant['apellidos'] : null;
        $celular = ($participant['celular'] !== '') ? $participant['celular'] : null;

        $attemptId = 0;
        $token = '';
        $attempts = avf_load_fast_attempts_for_identity(
          $db,
          $formId,
          (int)$participant['tipo_doc_id'],
          (string)$participant['nro_doc'],
          true
        );

        for ($retry = 0; $retry < 4; $retry++) {
          foreach ($attempts as $a) {
            if (strtoupper((string)$a['status']) !== 'EN_PROGRESO') continue;
            $attemptOpen = avf_load_attempt_by_token($db, (string)$a['token'], true);
            if (!$attemptOpen) continue;
            if (!avf_is_attempt_expired($attemptOpen)) {
              $responses = avf_fetch_saved_responses_map($db, (int)$attemptOpen['id']);
              $payload = avf_build_attempt_status_payload($attemptOpen, $lockedForm, $responses);
              $db->commit();
              jok([
                'msg' => 'Ya tienes un intento en progreso. Se reutilizo el mismo token.',
                'data' => $payload,
              ]);
            }
            avf_score_and_finalize_attempt($db, $attemptOpen, $lockedForm, null);
          }

          $attempts = avf_load_fast_attempts_for_identity(
            $db,
            $formId,
            (int)$participant['tipo_doc_id'],
            (string)$participant['nro_doc'],
            true
          );

          $usedNumbers = array_map(function ($r) {
            return (int)$r['intento_nro'];
          }, $attempts);
          $nextNro = avf_find_next_attempt_number($usedNumbers, (int)$lockedForm['intentos_max']);
          if ($nextNro <= 0) {
            $db->rollback();
            jerror(409, 'No tienes intentos disponibles para este formulario.');
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
               VALUES (?, 'FAST', NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'EN_PROGRESO', NOW(), ?, NOW(), NOW())"
            );
            $stIns->bind_param(
              'iisssssiss',
              $formId,
              $participant['tipo_doc_id'],
              $participant['nro_doc'],
              $nombres,
              $apellidos,
              $celular,
              $categoriasJson,
              $nextNro,
              $token,
              $expiresAt
            );
            $stIns->execute();
            $attemptId = (int)$db->insert_id;
            break;
          } catch (mysqli_sql_exception $e) {
            if ((int)$e->getCode() !== 1062) {
              throw $e;
            }
            $attempts = avf_load_fast_attempts_for_identity(
              $db,
              $formId,
              (int)$participant['tipo_doc_id'],
              (string)$participant['nro_doc'],
              true
            );
          }
        }

        if ($attemptId <= 0) {
          $attempts = avf_load_fast_attempts_for_identity(
            $db,
            $formId,
            (int)$participant['tipo_doc_id'],
            (string)$participant['nro_doc'],
            true
          );
          foreach ($attempts as $a) {
            if (strtoupper((string)$a['status']) !== 'EN_PROGRESO') continue;
            $attemptOpen = avf_load_attempt_by_token($db, (string)$a['token'], true);
            if (!$attemptOpen) continue;
            if (!avf_is_attempt_expired($attemptOpen)) {
              $responses = avf_fetch_saved_responses_map($db, (int)$attemptOpen['id']);
              $payload = avf_build_attempt_status_payload($attemptOpen, $lockedForm, $responses);
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

        if ($participant['categorias']) {
          $stCat = $db->prepare(
            "INSERT INTO cr_formulario_intento_categoria (intento_id, categoria_id)
             VALUES (?, ?)"
          );
          foreach ($participant['categorias'] as $cid) {
            $cid = (int)$cid;
            if ($cid <= 0) continue;
            $stCat->bind_param('ii', $attemptId, $cid);
            $stCat->execute();
          }
        }

        $attemptNew = avf_load_attempt_by_token($db, $token, false);
        $responses = [];
        $payload = avf_build_attempt_status_payload($attemptNew, $lockedForm, $responses);

        $db->commit();
        jok(['msg' => 'Intento iniciado correctamente.', 'data' => $payload]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'attempt_save': {
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
        if (strtoupper((string)$attempt['form_modo']) !== 'FAST') {
          $db->rollback();
          jerror(403, 'Token invalido para FAST.');
        }

        $form = [
          'id' => (int)$attempt['formulario_id'],
          'mostrar_resultado' => (int)$attempt['form_mostrar_resultado'],
          'nota_min' => (float)$attempt['form_nota_min'],
        ];

        if (strtoupper((string)$attempt['status']) === 'ENVIADO') {
          $responsesMap = avf_fetch_saved_responses_map($db, (int)$attempt['id']);
          $payload = avf_build_attempt_status_payload($attempt, $form, $responsesMap);
          $db->commit();
          jok(['msg' => 'El intento ya fue enviado.', 'data' => $payload]);
        }

        if (avf_is_attempt_expired($attempt)) {
          $final = avf_score_and_finalize_attempt($db, $attempt, $form, $responses);
          $db->commit();
          jok(['msg' => 'El tiempo expiro. El intento fue auto-enviado.', 'data' => fast_submit_response($final, $form)]);
        }

        $saved = avf_upsert_attempt_responses($db, (int)$attempt['id'], (int)$attempt['formulario_id'], $responses);
        $stTouch = $db->prepare("UPDATE cr_formulario_intentos SET last_saved_at = NOW(), updated_at = NOW() WHERE id = ?");
        $aid = (int)$attempt['id'];
        $stTouch->bind_param('i', $aid);
        $stTouch->execute();

        $attemptAfter = avf_load_attempt_by_token($db, $token, false);
        $responsesMap = avf_fetch_saved_responses_map($db, $aid);
        $payload = avf_build_attempt_status_payload($attemptAfter, $form, $responsesMap);
        $payload['saved_count'] = $saved;

        $db->commit();
        jok(['msg' => 'Respuestas guardadas correctamente.', 'data' => $payload]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'attempt_submit': {
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
        if (strtoupper((string)$attempt['form_modo']) !== 'FAST') {
          $db->rollback();
          jerror(403, 'Token invalido para FAST.');
        }

        $form = [
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
          jok(['msg' => 'El intento ya habia sido enviado.', 'data' => fast_submit_response($final, $form)]);
        }

        if (strtoupper((string)$attempt['status']) !== 'EN_PROGRESO') {
          $db->rollback();
          jerror(409, 'El intento no se encuentra en estado EN_PROGRESO.');
        }

        $final = avf_score_and_finalize_attempt($db, $attempt, $form, $responses);
        $db->commit();
        jok(['msg' => 'Examen enviado correctamente.', 'data' => fast_submit_response($final, $form)]);
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
