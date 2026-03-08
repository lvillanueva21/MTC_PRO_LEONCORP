<?php
// Ver 07-03-26
// modules/aula_virtual/formularios_lib.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

if (!function_exists('avf_now_lima')) {
  function avf_now_lima(): DateTime {
    return new DateTime('now', new DateTimeZone('America/Lima'));
  }
}

if (!function_exists('avf_random_token')) {
  function avf_random_token(int $bytes = 24): string {
    $bytes = max(8, min(64, $bytes));
    if (function_exists('random_bytes')) {
      return bin2hex(random_bytes($bytes));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
      $raw = openssl_random_pseudo_bytes($bytes);
      if ($raw !== false) return bin2hex($raw);
    }
    $seed = uniqid((string)mt_rand(1000, 9999), true) . microtime(true) . mt_rand(1000, 9999);
    return sha1($seed) . sha1(strrev($seed));
  }
}

if (!function_exists('avf_json_decode_array')) {
  function avf_json_decode_array($raw, array $default = []): array {
    if (is_array($raw)) return $raw;
    $raw = trim((string)$raw);
    if ($raw === '') return $default;
    $dec = json_decode($raw, true);
    return is_array($dec) ? $dec : $default;
  }
}

if (!function_exists('avf_json_encode')) {
  function avf_json_encode($data): string {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return ($json === false) ? '{}' : $json;
  }
}

if (!function_exists('avf_parse_campos_fast')) {
  function avf_parse_campos_fast($raw): array {
    $cfg = avf_json_decode_array($raw, []);
    $docs = [];
    foreach ((array)($cfg['tipos_doc_permitidos'] ?? []) as $d) {
      $id = (int)$d;
      if ($id > 0) $docs[$id] = $id;
    }
    if (!$docs) {
      $docs[1] = 1; // DNI por defecto
    }
    return [
      'pedir_nombres' => !empty($cfg['pedir_nombres']) ? 1 : 0,
      'pedir_apellidos' => !empty($cfg['pedir_apellidos']) ? 1 : 0,
      'pedir_celular' => !empty($cfg['pedir_celular']) ? 1 : 0,
      'pedir_categorias' => !empty($cfg['pedir_categorias']) ? 1 : 0,
      'tipos_doc_permitidos' => array_values($docs),
    ];
  }
}

if (!function_exists('avf_normalize_doc_number')) {
  function avf_normalize_doc_number(string $value): string {
    $value = trim($value);
    $value = preg_replace('/\s+/', '', $value);
    $value = strtoupper((string)$value);
    return $value;
  }
}

if (!function_exists('avf_validate_doc_number')) {
  function avf_validate_doc_number(string $docCode, string $docNumber): array {
    $docCode = strtoupper(trim($docCode));
    $docNumber = avf_normalize_doc_number($docNumber);

    if ($docNumber === '') {
      return ['ok' => false, 'msg' => 'El numero de documento es obligatorio.'];
    }

    if ($docCode === 'DNI') {
      if (!preg_match('/^\d{8}$/', $docNumber)) {
        return ['ok' => false, 'msg' => 'Para DNI, el numero debe tener exactamente 8 digitos.'];
      }
      return ['ok' => true, 'doc' => $docNumber];
    }

    if ($docCode === 'CE') {
      if (!preg_match('/^[A-Z0-9]{8,12}$/', $docNumber)) {
        return ['ok' => false, 'msg' => 'Para CE, ingresa entre 8 y 12 caracteres alfanumericos.'];
      }
      return ['ok' => true, 'doc' => $docNumber];
    }

    if ($docCode === 'BREVETE') {
      if (!preg_match('/^[A-Z0-9\-]{6,20}$/', $docNumber)) {
        return ['ok' => false, 'msg' => 'Para BREVETE, ingresa entre 6 y 20 caracteres validos.'];
      }
      return ['ok' => true, 'doc' => $docNumber];
    }

    if (!preg_match('/^[A-Z0-9\-]{3,20}$/', $docNumber)) {
      return ['ok' => false, 'msg' => 'Numero de documento invalido.'];
    }
    return ['ok' => true, 'doc' => $docNumber];
  }
}

if (!function_exists('avf_parse_int_list')) {
  function avf_parse_int_list($value): array {
    $out = [];
    if (is_array($value)) {
      foreach ($value as $v) {
        $id = (int)$v;
        if ($id > 0) $out[$id] = $id;
      }
      return array_values($out);
    }

    $raw = trim((string)$value);
    if ($raw === '') return [];

    $dec = json_decode($raw, true);
    if (is_array($dec)) {
      foreach ($dec as $v) {
        $id = (int)$v;
        if ($id > 0) $out[$id] = $id;
      }
      return array_values($out);
    }

    foreach (explode(',', $raw) as $part) {
      $id = (int)trim($part);
      if ($id > 0) $out[$id] = $id;
    }
    return array_values($out);
  }
}

if (!function_exists('avf_parse_respuestas_payload')) {
  function avf_parse_respuestas_payload($input): array {
    if (is_string($input)) {
      $input = avf_json_decode_array($input, []);
    }
    if (!is_array($input)) return [];

    $map = [];
    foreach ($input as $k => $v) {
      $qid = (int)$k;
      if ($qid <= 0) continue;
      $list = [];
      if (is_array($v)) {
        foreach ($v as $opt) {
          $oid = (int)$opt;
          if ($oid > 0) $list[$oid] = $oid;
        }
      } else {
        $oid = (int)$v;
        if ($oid > 0) $list[$oid] = $oid;
      }
      $vals = array_values($list);
      sort($vals, SORT_NUMERIC);
      $map[$qid] = $vals;
    }
    return $map;
  }
}

if (!function_exists('avf_attempt_remaining_seconds')) {
  function avf_attempt_remaining_seconds($expiresAt): ?int {
    $expiresAt = trim((string)$expiresAt);
    if ($expiresAt === '') return null;
    try {
      $exp = new DateTime($expiresAt, new DateTimeZone('America/Lima'));
      $now = avf_now_lima();
      $diff = $exp->getTimestamp() - $now->getTimestamp();
      return max(0, (int)$diff);
    } catch (Throwable $ignore) {
      return null;
    }
  }
}

if (!function_exists('avf_is_attempt_expired')) {
  function avf_is_attempt_expired(array $attempt): bool {
    $status = strtoupper(trim((string)($attempt['status'] ?? '')));
    if ($status !== 'EN_PROGRESO') return false;
    $remaining = avf_attempt_remaining_seconds($attempt['expires_at'] ?? null);
    return ($remaining !== null && $remaining <= 0);
  }
}

if (!function_exists('avf_find_next_attempt_number')) {
  function avf_find_next_attempt_number(array $usedNumbers, int $maxAttempts): int {
    $used = [];
    foreach ($usedNumbers as $n) {
      $i = (int)$n;
      if ($i > 0) $used[$i] = true;
    }
    for ($i = 1; $i <= $maxAttempts; $i++) {
      if (empty($used[$i])) return $i;
    }
    return 0;
  }
}

if (!function_exists('avf_load_tipos_doc')) {
  function avf_load_tipos_doc(mysqli $db): array {
    $rows = [];
    $st = $db->prepare("SELECT id, codigo FROM cq_tipos_documento ORDER BY id ASC");
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
      $rows[] = [
        'id' => (int)$r['id'],
        'codigo' => (string)$r['codigo'],
      ];
    }
    return $rows;
  }
}

if (!function_exists('avf_load_tipos_doc_map')) {
  function avf_load_tipos_doc_map(mysqli $db): array {
    $map = [];
    foreach (avf_load_tipos_doc($db) as $r) {
      $map[(int)$r['id']] = $r;
    }
    return $map;
  }
}

if (!function_exists('avf_doc_id_by_code')) {
  function avf_doc_id_by_code(mysqli $db, string $code): int {
    $code = strtoupper(trim($code));
    if ($code === '') return 0;
    $st = $db->prepare("SELECT id FROM cq_tipos_documento WHERE UPPER(codigo) = ? LIMIT 1");
    $st->bind_param('s', $code);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return (int)($row['id'] ?? 0);
  }
}

if (!function_exists('avf_load_categorias')) {
  function avf_load_categorias(mysqli $db): array {
    $rows = [];
    $st = $db->prepare("SELECT id, codigo, tipo_categoria FROM cq_categorias_licencia ORDER BY tipo_categoria ASC, id ASC");
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
      $rows[] = [
        'id' => (int)$r['id'],
        'codigo' => (string)$r['codigo'],
        'tipo_categoria' => (string)$r['tipo_categoria'],
      ];
    }
    return $rows;
  }
}

if (!function_exists('avf_load_form_public_by_code')) {
  function avf_load_form_public_by_code(mysqli $db, string $code): ?array {
    $code = trim($code);
    if ($code === '') return null;
    $st = $db->prepare(
      "SELECT
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
         f.campos_fast,
         f.public_code,
         f.created_at,
         f.updated_at
       FROM cr_formularios f
       WHERE f.public_code = ?
         AND f.modo = 'FAST'
       LIMIT 1"
    );
    $st->bind_param('s', $code);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
  }
}

if (!function_exists('avf_load_form_by_id_company')) {
  function avf_load_form_by_id_company(mysqli $db, int $formId, int $companyId, bool $forUpdate = false): ?array {
    if ($formId <= 0 || $companyId <= 0) return null;
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
              f.campos_fast,
              f.public_code,
              f.created_at,
              f.updated_at
            FROM cr_formularios f
            WHERE f.id = ?
              AND f.empresa_id = ?
            LIMIT 1";
    if ($forUpdate) $sql .= " FOR UPDATE";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $formId, $companyId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
  }
}

if (!function_exists('avf_load_form_questions_public')) {
  function avf_load_form_questions_public(mysqli $db, int $formId): array {
    $questions = [];

    $stQ = $db->prepare(
      "SELECT id, formulario_id, tipo, enunciado, puntos, orden
       FROM cr_formulario_preguntas
       WHERE formulario_id = ?
       ORDER BY orden ASC, id ASC"
    );
    $stQ->bind_param('i', $formId);
    $stQ->execute();
    $rsQ = $stQ->get_result();
    while ($q = $rsQ->fetch_assoc()) {
      $qid = (int)$q['id'];
      $questions[$qid] = [
        'id' => $qid,
        'tipo' => (string)$q['tipo'],
        'enunciado' => (string)$q['enunciado'],
        'puntos' => (float)$q['puntos'],
        'orden' => (int)$q['orden'],
        'opciones' => [],
      ];
    }

    if (!$questions) return [];

    $stO = $db->prepare(
      "SELECT id, pregunta_id, texto, orden
       FROM cr_formulario_opciones
       WHERE pregunta_id IN (
         SELECT id FROM cr_formulario_preguntas WHERE formulario_id = ?
       )
       ORDER BY pregunta_id ASC, orden ASC, id ASC"
    );
    $stO->bind_param('i', $formId);
    $stO->execute();
    $rsO = $stO->get_result();
    while ($o = $rsO->fetch_assoc()) {
      $qid = (int)$o['pregunta_id'];
      if (!isset($questions[$qid])) continue;
      $questions[$qid]['opciones'][] = [
        'id' => (int)$o['id'],
        'texto' => (string)$o['texto'],
        'orden' => (int)$o['orden'],
      ];
    }

    return array_values($questions);
  }
}

if (!function_exists('avf_load_question_keys')) {
  function avf_load_question_keys(mysqli $db, int $formId): array {
    $map = [];
    $st = $db->prepare(
      "SELECT
         q.id AS pregunta_id,
         q.tipo,
         q.puntos,
         o.id AS opcion_id,
         o.es_correcta
       FROM cr_formulario_preguntas q
       LEFT JOIN cr_formulario_opciones o ON o.pregunta_id = q.id
       WHERE q.formulario_id = ?
       ORDER BY q.orden ASC, q.id ASC, o.orden ASC, o.id ASC"
    );
    $st->bind_param('i', $formId);
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
      $qid = (int)$r['pregunta_id'];
      if (!isset($map[$qid])) {
        $map[$qid] = [
          'id' => $qid,
          'tipo' => (string)$r['tipo'],
          'puntos' => (float)$r['puntos'],
          'valid' => [],
          'correct' => [],
        ];
      }
      $oid = (int)($r['opcion_id'] ?? 0);
      if ($oid > 0) {
        $map[$qid]['valid'][$oid] = $oid;
        if ((int)$r['es_correcta'] === 1) {
          $map[$qid]['correct'][$oid] = $oid;
        }
      }
    }
    return $map;
  }
}

if (!function_exists('avf_load_attempt_by_token')) {
  function avf_load_attempt_by_token(mysqli $db, string $token, bool $forUpdate = false): ?array {
    $token = trim($token);
    if ($token === '') return null;
    $sql = "SELECT
              i.*,
              f.empresa_id AS form_empresa_id,
              f.modo AS form_modo,
              f.tipo AS form_tipo,
              f.grupo_id AS form_grupo_id,
              f.curso_id AS form_curso_id,
              f.tema_id AS form_tema_id,
              f.titulo AS form_titulo,
              f.estado AS form_estado,
              f.intentos_max AS form_intentos_max,
              f.tiempo_activo AS form_tiempo_activo,
              f.duracion_min AS form_duracion_min,
              f.nota_min AS form_nota_min,
              f.mostrar_resultado AS form_mostrar_resultado
            FROM cr_formulario_intentos i
            JOIN cr_formularios f ON f.id = i.formulario_id
            WHERE i.token = ?
            LIMIT 1";
    if ($forUpdate) $sql .= " FOR UPDATE";
    $st = $db->prepare($sql);
    $st->bind_param('s', $token);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
  }
}

if (!function_exists('avf_load_fast_attempts_for_identity')) {
  function avf_load_fast_attempts_for_identity(mysqli $db, int $formId, int $tipoDocId, string $nroDoc, bool $forUpdate = false): array {
    $sql = "SELECT id, intento_nro, status, token, expires_at
            FROM cr_formulario_intentos
            WHERE formulario_id = ?
              AND tipo_doc_id = ?
              AND nro_doc = ?
            ORDER BY intento_nro ASC, id ASC";
    if ($forUpdate) $sql .= " FOR UPDATE";
    $st = $db->prepare($sql);
    $st->bind_param('iis', $formId, $tipoDocId, $nroDoc);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
  }
}

if (!function_exists('avf_load_aula_attempts_for_user')) {
  function avf_load_aula_attempts_for_user(mysqli $db, int $formId, int $userId, bool $forUpdate = false): array {
    $sql = "SELECT id, intento_nro, status, token, expires_at
            FROM cr_formulario_intentos
            WHERE formulario_id = ?
              AND usuario_id = ?
            ORDER BY intento_nro ASC, id ASC";
    if ($forUpdate) $sql .= " FOR UPDATE";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $formId, $userId);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
  }
}

if (!function_exists('avf_fetch_saved_responses_map')) {
  function avf_fetch_saved_responses_map(mysqli $db, int $attemptId): array {
    $map = [];
    $st = $db->prepare(
      "SELECT pregunta_id, respuesta_json
       FROM cr_formulario_respuestas
       WHERE intento_id = ?"
    );
    $st->bind_param('i', $attemptId);
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
      $qid = (int)$r['pregunta_id'];
      if ($qid <= 0) continue;
      $arr = avf_parse_respuestas_payload([$qid => avf_json_decode_array($r['respuesta_json'] ?? '', [])]);
      $map[$qid] = $arr[$qid] ?? [];
    }
    return $map;
  }
}

if (!function_exists('avf_upsert_attempt_responses')) {
  function avf_upsert_attempt_responses(mysqli $db, int $attemptId, int $formId, array $responses): int {
    $keys = avf_load_question_keys($db, $formId);
    if (!$keys) return 0;

    $saved = 0;
    $st = $db->prepare(
      "INSERT INTO cr_formulario_respuestas
         (intento_id, pregunta_id, respuesta_json, created_at, updated_at)
       VALUES (?, ?, ?, NOW(), NOW())
       ON DUPLICATE KEY UPDATE
         respuesta_json = VALUES(respuesta_json),
         updated_at = NOW()"
    );

    foreach ($responses as $qid => $selected) {
      $qid = (int)$qid;
      if ($qid <= 0 || !isset($keys[$qid])) continue;

      $validSet = $keys[$qid]['valid'];
      $clean = [];
      foreach ((array)$selected as $oid) {
        $oid = (int)$oid;
        if ($oid > 0 && isset($validSet[$oid])) {
          $clean[$oid] = $oid;
        }
      }
      $arr = array_values($clean);
      sort($arr, SORT_NUMERIC);

      $json = avf_json_encode($arr);
      $st->bind_param('iis', $attemptId, $qid, $json);
      $st->execute();
      $saved++;
    }
    return $saved;
  }
}

if (!function_exists('avf_score_answer')) {
  function avf_score_answer(array $questionKey, array $selected): array {
    $selectedSet = [];
    foreach ($selected as $oid) {
      $oid = (int)$oid;
      if ($oid > 0 && isset($questionKey['valid'][$oid])) {
        $selectedSet[$oid] = $oid;
      }
    }
    $selectedVals = array_values($selectedSet);
    sort($selectedVals, SORT_NUMERIC);

    $correctVals = array_values($questionKey['correct']);
    sort($correctVals, SORT_NUMERIC);

    $isCorrect = false;
    $tipo = strtoupper((string)$questionKey['tipo']);
    if ($tipo === 'OM_UNICA') {
      $isCorrect = (count($selectedVals) === 1 && count($correctVals) === 1 && $selectedVals[0] === $correctVals[0]);
    } else {
      // OM_MULTIPLE: todo o nada (match exacto de conjuntos)
      $isCorrect = ($selectedVals === $correctVals);
    }

    $points = $isCorrect ? (float)$questionKey['puntos'] : 0.0;
    return [
      'selected' => $selectedVals,
      'is_correct' => $isCorrect ? 1 : 0,
      'points' => $points,
    ];
  }
}

if (!function_exists('avf_score_and_finalize_attempt')) {
  function avf_score_and_finalize_attempt(mysqli $db, array $attempt, array $form, ?array $incomingResponses = null): array {
    $attemptId = (int)$attempt['id'];
    $formId = (int)$form['id'];

    if ($attemptId <= 0 || $formId <= 0) {
      throw new RuntimeException('Intento o formulario invalido para finalizar.');
    }

    if ($incomingResponses !== null) {
      avf_upsert_attempt_responses($db, $attemptId, $formId, $incomingResponses);
    }

    $keys = avf_load_question_keys($db, $formId);
    if (!$keys) {
      throw new RuntimeException('El formulario no tiene preguntas configuradas.');
    }

    $savedMap = avf_fetch_saved_responses_map($db, $attemptId);

    $sumBase = 0.0;
    $sumObt = 0.0;

    $stUpResp = $db->prepare(
      "INSERT INTO cr_formulario_respuestas
         (intento_id, pregunta_id, respuesta_json, is_correct, puntos_obtenidos, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, NOW(), NOW())
       ON DUPLICATE KEY UPDATE
         respuesta_json = VALUES(respuesta_json),
         is_correct = VALUES(is_correct),
         puntos_obtenidos = VALUES(puntos_obtenidos),
         updated_at = NOW()"
    );

    foreach ($keys as $qid => $key) {
      $sumBase += (float)$key['puntos'];
      $selected = $savedMap[$qid] ?? [];
      $score = avf_score_answer($key, (array)$selected);
      $sumObt += (float)$score['points'];

      $respJson = avf_json_encode($score['selected']);
      $isCorrect = (int)$score['is_correct'];
      $pointsObt = (float)$score['points'];

      $stUpResp->bind_param('iisid', $attemptId, $qid, $respJson, $isCorrect, $pointsObt);
      $stUpResp->execute();
    }

    $nota = 0.0;
    if ($sumBase > 0.0) {
      if (abs($sumBase - 20.0) < 0.00001) {
        $nota = round($sumObt, 2);
      } else {
        $nota = round(($sumObt / $sumBase) * 20.0, 2);
      }
    }

    $notaMin = (float)($form['nota_min'] ?? 11);
    $aprobado = ($nota >= $notaMin) ? 1 : 0;

    $stAttempt = $db->prepare(
      "UPDATE cr_formulario_intentos
       SET status = 'ENVIADO',
           submitted_at = IFNULL(submitted_at, NOW()),
           last_saved_at = NOW(),
           puntaje_obtenido = ?,
           nota_final = ?,
           aprobado = ?,
           updated_at = NOW()
       WHERE id = ?
         AND status = 'EN_PROGRESO'"
    );
    $stAttempt->bind_param('ddii', $sumObt, $nota, $aprobado, $attemptId);
    $stAttempt->execute();

    if ($stAttempt->affected_rows <= 0) {
      $stCheck = $db->prepare("SELECT status, puntaje_obtenido, nota_final, aprobado, submitted_at FROM cr_formulario_intentos WHERE id = ? LIMIT 1");
      $stCheck->bind_param('i', $attemptId);
      $stCheck->execute();
      $cur = $stCheck->get_result()->fetch_assoc();
      if (!$cur) {
        throw new RuntimeException('No se pudo recuperar el intento finalizado.');
      }
      if (strtoupper((string)$cur['status']) !== 'ENVIADO') {
        throw new RuntimeException('El intento no se puede finalizar en su estado actual.');
      }
      return [
        'attempt_id' => $attemptId,
        'status' => 'ENVIADO',
        'puntaje_obtenido' => (float)($cur['puntaje_obtenido'] ?? 0),
        'nota_final' => (float)($cur['nota_final'] ?? 0),
        'aprobado' => (int)($cur['aprobado'] ?? 0),
        'submitted_at' => (string)($cur['submitted_at'] ?? ''),
        'already_submitted' => 1,
      ];
    }

    $stDone = $db->prepare("SELECT status, puntaje_obtenido, nota_final, aprobado, submitted_at FROM cr_formulario_intentos WHERE id = ? LIMIT 1");
    $stDone->bind_param('i', $attemptId);
    $stDone->execute();
    $done = $stDone->get_result()->fetch_assoc() ?: [];

    return [
      'attempt_id' => $attemptId,
      'status' => (string)($done['status'] ?? 'ENVIADO'),
      'puntaje_obtenido' => (float)($done['puntaje_obtenido'] ?? $sumObt),
      'nota_final' => (float)($done['nota_final'] ?? $nota),
      'aprobado' => (int)($done['aprobado'] ?? $aprobado),
      'submitted_at' => (string)($done['submitted_at'] ?? ''),
      'already_submitted' => 0,
    ];
  }
}

if (!function_exists('avf_build_attempt_status_payload')) {
  function avf_build_attempt_status_payload(array $attempt, array $form, array $responsesMap = []): array {
    return [
      'attempt_id' => (int)$attempt['id'],
      'token' => (string)$attempt['token'],
      'status' => (string)$attempt['status'],
      'intento_nro' => (int)$attempt['intento_nro'],
      'start_at' => (string)($attempt['start_at'] ?? ''),
      'expires_at' => (string)($attempt['expires_at'] ?? ''),
      'remaining_seconds' => avf_attempt_remaining_seconds($attempt['expires_at'] ?? ''),
      'submitted_at' => (string)($attempt['submitted_at'] ?? ''),
      'puntaje_obtenido' => ($attempt['puntaje_obtenido'] !== null) ? (float)$attempt['puntaje_obtenido'] : null,
      'nota_final' => ($attempt['nota_final'] !== null) ? (float)$attempt['nota_final'] : null,
      'aprobado' => ($attempt['aprobado'] !== null) ? (int)$attempt['aprobado'] : null,
      'mostrar_resultado' => (int)($form['mostrar_resultado'] ?? 1),
      'respuestas' => $responsesMap,
    ];
  }
}
