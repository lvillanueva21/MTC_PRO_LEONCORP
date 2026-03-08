<?php
// Ver 07-03-26
// modules/aula_virtual/api_formularios_admin.php
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

function normalize_estado(string $raw): string {
  $s = strtoupper(trim($raw));
  return in_array($s, ['BORRADOR', 'PUBLICADO', 'CERRADO'], true) ? $s : '';
}

function normalize_modo(string $raw): string {
  $s = strtoupper(trim($raw));
  return in_array($s, ['FAST', 'AULA'], true) ? $s : '';
}

function normalize_tipo(string $raw): string {
  $s = strtoupper(trim($raw));
  return in_array($s, ['EXAMEN', 'TEST', 'ENCUESTA'], true) ? $s : '';
}

function bool_post(string $key, int $default = 0): int {
  if (!isset($_POST[$key])) return $default ? 1 : 0;
  return ((int)$_POST[$key] === 1) ? 1 : 0;
}

function parse_question_options($raw): array {
  $arr = [];
  if (is_array($raw)) {
    $arr = $raw;
  } else {
    $arr = avf_json_decode_array((string)$raw, []);
  }

  $out = [];
  foreach ($arr as $opt) {
    if (!is_array($opt)) continue;
    $txt = trim((string)($opt['texto'] ?? ''));
    if ($txt === '') continue;
    $out[] = [
      'texto' => $txt,
      'es_correcta' => !empty($opt['es_correcta']) ? 1 : 0,
    ];
  }
  return $out;
}

function ensure_fast_campos_with_dni(mysqli $db, array $camposFast): array {
  $camposFast = avf_parse_campos_fast($camposFast);
  $dniId = avf_doc_id_by_code($db, 'DNI');
  if ($dniId > 0) {
    $docs = array_map('intval', (array)($camposFast['tipos_doc_permitidos'] ?? []));
    if (!in_array($dniId, $docs, true)) {
      array_unshift($docs, $dniId);
    }
    $docs = array_values(array_unique(array_filter($docs, function ($v) {
      return (int)$v > 0;
    })));
    $camposFast['tipos_doc_permitidos'] = $docs;
  }
  return $camposFast;
}

function form_summary_row(mysqli $db, int $formId, int $empresaId): ?array {
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
       f.updated_at,
       g.nombre AS grupo_nombre,
       c.nombre AS curso_nombre,
       t.titulo AS tema_titulo,
       (
         SELECT COUNT(*) FROM cr_formulario_preguntas q
         WHERE q.formulario_id = f.id
       ) AS preguntas_count,
       (
         SELECT COALESCE(SUM(q.puntos), 0) FROM cr_formulario_preguntas q
         WHERE q.formulario_id = f.id
       ) AS puntos_total,
       (
         SELECT COUNT(*) FROM cr_formulario_intentos i
         WHERE i.formulario_id = f.id
       ) AS intentos_total,
       (
         SELECT COUNT(*) FROM cr_formulario_intentos i
         WHERE i.formulario_id = f.id AND i.status = 'ENVIADO'
       ) AS intentos_enviados,
       (
         SELECT COUNT(*) FROM cr_formulario_intentos i
         WHERE i.formulario_id = f.id AND i.status = 'ENVIADO' AND i.aprobado = 1
       ) AS aprobados_total
     FROM cr_formularios f
     LEFT JOIN cr_grupos g ON g.id = f.grupo_id
     LEFT JOIN cr_cursos c ON c.id = f.curso_id
     LEFT JOIN cr_temas t ON t.id = f.tema_id
     WHERE f.id = ?
       AND f.empresa_id = ?
     LIMIT 1"
  );
  $st->bind_param('ii', $formId, $empresaId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if (!$row) return null;
  $row['campos_fast'] = avf_parse_campos_fast($row['campos_fast'] ?? '');
  return $row;
}

function ensure_form_publishable(mysqli $db, int $formId): array {
  $stQ = $db->prepare(
    "SELECT id, tipo, puntos
     FROM cr_formulario_preguntas
     WHERE formulario_id = ?
     ORDER BY orden ASC, id ASC"
  );
  $stQ->bind_param('i', $formId);
  $stQ->execute();
  $questions = $stQ->get_result()->fetch_all(MYSQLI_ASSOC);
  if (!$questions) {
    return ['ok' => false, 'msg' => 'No puedes publicar: el examen no tiene preguntas.'];
  }

  $sum = 0.0;
  foreach ($questions as $q) {
    $sum += (float)$q['puntos'];
  }
  if (abs($sum - 20.0) > 0.01) {
    return ['ok' => false, 'msg' => 'No puedes publicar: el total de puntos debe ser exactamente 20.'];
  }

  $stO = $db->prepare(
    "SELECT
       pregunta_id,
       COUNT(*) AS opciones_total,
       SUM(CASE WHEN es_correcta = 1 THEN 1 ELSE 0 END) AS correctas_total
     FROM cr_formulario_opciones
     WHERE pregunta_id IN (
       SELECT id FROM cr_formulario_preguntas WHERE formulario_id = ?
     )
     GROUP BY pregunta_id"
  );
  $stO->bind_param('i', $formId);
  $stO->execute();
  $optionsStat = [];
  foreach ($stO->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $optionsStat[(int)$row['pregunta_id']] = [
      'opciones_total' => (int)$row['opciones_total'],
      'correctas_total' => (int)$row['correctas_total'],
    ];
  }

  foreach ($questions as $q) {
    $qid = (int)$q['id'];
    $tipo = strtoupper((string)$q['tipo']);
    $stat = $optionsStat[$qid] ?? ['opciones_total' => 0, 'correctas_total' => 0];
    if ($stat['opciones_total'] < 2) {
      return ['ok' => false, 'msg' => 'No puedes publicar: cada pregunta debe tener al menos 2 opciones.'];
    }
    if ($tipo === 'OM_UNICA' && $stat['correctas_total'] !== 1) {
      return ['ok' => false, 'msg' => 'No puedes publicar: una pregunta OM_UNICA debe tener solo 1 respuesta correcta.'];
    }
    if ($tipo === 'OM_MULTIPLE' && $stat['correctas_total'] < 1) {
      return ['ok' => false, 'msg' => 'No puedes publicar: una pregunta OM_MULTIPLE debe tener al menos 1 respuesta correcta.'];
    }
  }

  return ['ok' => true];
}

try {
  acl_require_ids([1, 4]);

  if (current_role_id() !== 4) {
    jerror(403, 'Solo Administracion puede usar esta API.');
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
    case 'catalog_data': {
      $grupos = [];
      $stG = $db->prepare(
        "SELECT g.id, g.curso_id, g.nombre, g.codigo, g.activo, c.nombre AS curso_nombre
         FROM cr_grupos g
         JOIN cr_cursos c ON c.id = g.curso_id
         WHERE g.empresa_id = ?
         ORDER BY g.id DESC"
      );
      $stG->bind_param('i', $empresaId);
      $stG->execute();
      $grupos = $stG->get_result()->fetch_all(MYSQLI_ASSOC);

      $cursos = [];
      $stC = $db->prepare("SELECT id, nombre, activo FROM cr_cursos ORDER BY nombre ASC");
      $stC->execute();
      $cursos = $stC->get_result()->fetch_all(MYSQLI_ASSOC);

      $cursoId = (int)($_GET['curso_id'] ?? 0);
      $temas = [];
      if ($cursoId > 0) {
        $stT = $db->prepare("SELECT id, curso_id, titulo FROM cr_temas WHERE curso_id = ? ORDER BY id ASC");
        $stT->bind_param('i', $cursoId);
        $stT->execute();
        $temas = $stT->get_result()->fetch_all(MYSQLI_ASSOC);
      }

      jok([
        'data' => [
          'grupos' => $grupos,
          'cursos' => $cursos,
          'temas' => $temas,
          'tipos_doc' => avf_load_tipos_doc($db),
          'categorias' => avf_load_categorias($db),
        ],
      ]);
    }

    case 'temas_by_curso': {
      $cursoId = (int)($_GET['curso_id'] ?? 0);
      if ($cursoId <= 0) {
        jok(['data' => []]);
      }
      $st = $db->prepare("SELECT id, curso_id, titulo FROM cr_temas WHERE curso_id = ? ORDER BY id ASC");
      $st->bind_param('i', $cursoId);
      $st->execute();
      jok(['data' => $st->get_result()->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'forms_fast_list': {
      $q = trim((string)($_GET['q'] ?? ''));
      $estado = normalize_estado((string)($_GET['estado'] ?? ''));
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
      $offset = ($page - 1) * $perPage;

      $where = ["f.empresa_id = ?", "f.modo = 'FAST'", "f.tipo = 'EXAMEN'"];
      $types = 'i';
      $pars = [$empresaId];

      if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(f.titulo LIKE ? OR f.descripcion LIKE ?)';
        $types .= 'ss';
        $pars[] = $like;
        $pars[] = $like;
      }
      if ($estado !== '') {
        $where[] = 'f.estado = ?';
        $types .= 's';
        $pars[] = $estado;
      }
      $wSql = 'WHERE ' . implode(' AND ', $where);

      $stCount = $db->prepare("SELECT COUNT(*) AS c FROM cr_formularios f {$wSql}");
      $stCount->bind_param($types, ...$pars);
      $stCount->execute();
      $total = (int)($stCount->get_result()->fetch_assoc()['c'] ?? 0);

      $sql = "SELECT
                f.id,
                f.titulo,
                f.descripcion,
                f.estado,
                f.intentos_max,
                f.tiempo_activo,
                f.duracion_min,
                f.nota_min,
                f.mostrar_resultado,
                f.public_code,
                f.updated_at,
                (
                  SELECT COUNT(*) FROM cr_formulario_preguntas q
                  WHERE q.formulario_id = f.id
                ) AS preguntas_count,
                (
                  SELECT COALESCE(SUM(q.puntos), 0) FROM cr_formulario_preguntas q
                  WHERE q.formulario_id = f.id
                ) AS puntos_total,
                (
                  SELECT COUNT(*) FROM cr_formulario_intentos i
                  WHERE i.formulario_id = f.id
                ) AS intentos_total,
                (
                  SELECT COUNT(*) FROM cr_formulario_intentos i
                  WHERE i.formulario_id = f.id AND i.status = 'ENVIADO'
                ) AS intentos_enviados
              FROM cr_formularios f
              {$wSql}
              ORDER BY f.id DESC
              LIMIT ? OFFSET ?";

      $types2 = $types . 'ii';
      $pars2 = $pars;
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

    case 'forms_aula_list': {
      $grupoId = (int)($_GET['grupo_id'] ?? 0);
      $q = trim((string)($_GET['q'] ?? ''));
      $cursoId = (int)($_GET['curso_id'] ?? 0);
      $temaId = (int)($_GET['tema_id'] ?? 0);
      $estado = normalize_estado((string)($_GET['estado'] ?? ''));

      if ($grupoId <= 0) {
        jok(['data' => []]);
      }

      $stGroup = $db->prepare("SELECT id FROM cr_grupos WHERE id = ? AND empresa_id = ? LIMIT 1");
      $stGroup->bind_param('ii', $grupoId, $empresaId);
      $stGroup->execute();
      if (!$stGroup->get_result()->fetch_assoc()) {
        jerror(404, 'El grupo seleccionado no existe en tu empresa.');
      }

      $where = ["f.empresa_id = ?", "f.modo = 'AULA'", "f.tipo = 'EXAMEN'", "f.grupo_id = ?"];
      $types = 'ii';
      $pars = [$empresaId, $grupoId];

      if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(f.titulo LIKE ? OR f.descripcion LIKE ?)';
        $types .= 'ss';
        $pars[] = $like;
        $pars[] = $like;
      }
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
      if ($estado !== '') {
        $where[] = 'f.estado = ?';
        $types .= 's';
        $pars[] = $estado;
      }
      $wSql = 'WHERE ' . implode(' AND ', $where);

      $sql = "SELECT
                f.id,
                f.titulo,
                f.descripcion,
                f.estado,
                f.grupo_id,
                f.curso_id,
                f.tema_id,
                f.intentos_max,
                f.tiempo_activo,
                f.duracion_min,
                f.nota_min,
                f.requisito_cumplimiento,
                f.updated_at,
                c.nombre AS curso_nombre,
                t.titulo AS tema_titulo,
                (
                  SELECT COUNT(*) FROM cr_formulario_preguntas q
                  WHERE q.formulario_id = f.id
                ) AS preguntas_count,
                (
                  SELECT COALESCE(SUM(q.puntos), 0) FROM cr_formulario_preguntas q
                  WHERE q.formulario_id = f.id
                ) AS puntos_total,
                (
                  SELECT COUNT(*) FROM cr_formulario_intentos i
                  WHERE i.formulario_id = f.id
                ) AS intentos_total,
                (
                  SELECT COUNT(*) FROM cr_formulario_intentos i
                  WHERE i.formulario_id = f.id AND i.status = 'ENVIADO'
                ) AS intentos_enviados
              FROM cr_formularios f
              LEFT JOIN cr_cursos c ON c.id = f.curso_id
              LEFT JOIN cr_temas t ON t.id = f.tema_id
              {$wSql}
              ORDER BY f.id DESC";

      $st = $db->prepare($sql);
      $st->bind_param($types, ...$pars);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data' => $rows]);
    }

    case 'form_create': {
      $modo = normalize_modo((string)($_POST['modo'] ?? ''));
      $tipo = normalize_tipo((string)($_POST['tipo'] ?? 'EXAMEN'));
      $titulo = trim((string)($_POST['titulo'] ?? ''));
      $descripcion = trim((string)($_POST['descripcion'] ?? ''));
      $estado = normalize_estado((string)($_POST['estado'] ?? 'BORRADOR'));
      $intentosMax = (int)($_POST['intentos_max'] ?? 1);
      $tiempoActivo = bool_post('tiempo_activo', 0);
      $duracionMin = (int)($_POST['duracion_min'] ?? 0);
      $notaMin = (float)($_POST['nota_min'] ?? 11);
      $mostrarResultado = bool_post('mostrar_resultado', 1);
      $requisito = strtoupper(trim((string)($_POST['requisito_cumplimiento'] ?? 'ENVIAR')));
      $grupoId = (int)($_POST['grupo_id'] ?? 0);
      $cursoId = (int)($_POST['curso_id'] ?? 0);
      $temaId = (int)($_POST['tema_id'] ?? 0);

      if ($modo === '') jerror(400, 'Modo invalido.');
      if ($tipo !== 'EXAMEN') jerror(400, 'En esta fase solo se permite tipo EXAMEN.');
      if ($titulo === '') jerror(400, 'El titulo es obligatorio.');
      if ($estado === '') $estado = 'BORRADOR';

      $intentosMax = max(1, min(50, $intentosMax));
      $notaMin = max(0.0, min(20.0, $notaMin));
      if ($tiempoActivo === 1) {
        if ($duracionMin <= 0) jerror(400, 'Debes indicar duracion en minutos cuando el tiempo esta activo.');
        $duracionMin = min(720, $duracionMin);
      } else {
        $duracionMin = null;
      }

      if (!in_array($requisito, ['ENVIAR', 'APROBAR'], true)) {
        $requisito = 'ENVIAR';
      }

      $publicCode = null;
      $camposFastJson = null;

      if ($modo === 'FAST') {
        $grupoId = null;
        $cursoId = null;
        $temaId = null;
        $requisito = 'ENVIAR';
        $camposFast = ensure_fast_campos_with_dni($db, avf_json_decode_array($_POST['campos_fast'] ?? '', []));
        $camposFastJson = avf_json_encode($camposFast);
        $estado = 'BORRADOR';

        for ($i = 0; $i < 8; $i++) {
          $candidate = 'FAST' . strtoupper(substr(avf_random_token(6), 0, 10));
          $stCheck = $db->prepare("SELECT id FROM cr_formularios WHERE public_code = ? LIMIT 1");
          $stCheck->bind_param('s', $candidate);
          $stCheck->execute();
          if (!$stCheck->get_result()->fetch_assoc()) {
            $publicCode = $candidate;
            break;
          }
        }
        if ($publicCode === null) {
          jerror(500, 'No se pudo generar un codigo publico unico para FAST.');
        }
      } else {
        if ($grupoId <= 0) jerror(400, 'Para AULA debes seleccionar un grupo.');

        $stG = $db->prepare("SELECT id, curso_id FROM cr_grupos WHERE id = ? AND empresa_id = ? LIMIT 1");
        $stG->bind_param('ii', $grupoId, $empresaId);
        $stG->execute();
        $group = $stG->get_result()->fetch_assoc();
        if (!$group) jerror(404, 'El grupo seleccionado no existe en tu empresa.');

        if ($cursoId <= 0) $cursoId = (int)$group['curso_id'];
        if ($cursoId !== (int)$group['curso_id']) {
          jerror(400, 'El curso del formulario AULA debe coincidir con el curso del grupo.');
        }

        if ($temaId > 0) {
          $stT = $db->prepare("SELECT id FROM cr_temas WHERE id = ? AND curso_id = ? LIMIT 1");
          $stT->bind_param('ii', $temaId, $cursoId);
          $stT->execute();
          if (!$stT->get_result()->fetch_assoc()) {
            jerror(400, 'El tema seleccionado no pertenece al curso.');
          }
        } else {
          $temaId = null;
        }
      }

      $descParam = ($descripcion !== '') ? $descripcion : null;
      $st = $db->prepare(
        "INSERT INTO cr_formularios
           (empresa_id, modo, tipo, grupo_id, curso_id, tema_id, titulo, descripcion, estado,
            intentos_max, tiempo_activo, duracion_min, nota_min, mostrar_resultado,
            requisito_cumplimiento, campos_fast, public_code, created_at, updated_at)
         VALUES
           (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
      );
      $st->bind_param(
        'issiiisssiiidisss',
        $empresaId,
        $modo,
        $tipo,
        $grupoId,
        $cursoId,
        $temaId,
        $titulo,
        $descParam,
        $estado,
        $intentosMax,
        $tiempoActivo,
        $duracionMin,
        $notaMin,
        $mostrarResultado,
        $requisito,
        $camposFastJson,
        $publicCode
      );
      $st->execute();
      $newId = (int)$db->insert_id;

      $row = form_summary_row($db, $newId, $empresaId);
      jok(['msg' => 'Formulario creado correctamente.', 'data' => $row]);
    }

    case 'form_update': {
      $formId = (int)($_POST['form_id'] ?? 0);
      if ($formId <= 0) jerror(400, 'form_id requerido.');

      $form = avf_load_form_by_id_company($db, $formId, $empresaId, false);
      if (!$form) jerror(404, 'Formulario no encontrado.');
      if (strtoupper((string)$form['estado']) !== 'BORRADOR') {
        jerror(409, 'Solo puedes editar la configuracion cuando el formulario esta en BORRADOR.');
      }

      $titulo = trim((string)($_POST['titulo'] ?? (string)$form['titulo']));
      $descripcion = trim((string)($_POST['descripcion'] ?? (string)$form['descripcion']));
      $intentosMax = isset($_POST['intentos_max']) ? (int)$_POST['intentos_max'] : (int)$form['intentos_max'];
      $tiempoActivo = isset($_POST['tiempo_activo']) ? bool_post('tiempo_activo', 0) : (int)$form['tiempo_activo'];
      $duracionMin = isset($_POST['duracion_min']) ? (int)$_POST['duracion_min'] : (int)$form['duracion_min'];
      $notaMin = isset($_POST['nota_min']) ? (float)$_POST['nota_min'] : (float)$form['nota_min'];
      $mostrarResultado = isset($_POST['mostrar_resultado']) ? bool_post('mostrar_resultado', 1) : (int)$form['mostrar_resultado'];
      $requisito = strtoupper(trim((string)($_POST['requisito_cumplimiento'] ?? (string)$form['requisito_cumplimiento'])));
      $grupoId = isset($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : (int)$form['grupo_id'];
      $cursoId = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : (int)$form['curso_id'];
      $temaId = isset($_POST['tema_id']) ? (int)$_POST['tema_id'] : (int)$form['tema_id'];

      if ($titulo === '') jerror(400, 'El titulo es obligatorio.');
      $intentosMax = max(1, min(50, $intentosMax));
      $notaMin = max(0.0, min(20.0, $notaMin));
      if ($tiempoActivo === 1) {
        if ($duracionMin <= 0) jerror(400, 'Debes indicar duracion en minutos cuando el tiempo esta activo.');
        $duracionMin = min(720, $duracionMin);
      } else {
        $duracionMin = null;
      }

      if (!in_array($requisito, ['ENVIAR', 'APROBAR'], true)) $requisito = 'ENVIAR';

      $modo = strtoupper((string)$form['modo']);
      $camposFastJson = null;

      if ($modo === 'FAST') {
        $grupoId = null;
        $cursoId = null;
        $temaId = null;
        $requisito = 'ENVIAR';
        $camposFast = ensure_fast_campos_with_dni(
          $db,
          avf_json_decode_array($_POST['campos_fast'] ?? $form['campos_fast'] ?? '', [])
        );
        $camposFastJson = avf_json_encode($camposFast);
      } else {
        if ($grupoId <= 0) jerror(400, 'Para AULA debes seleccionar un grupo.');
        $stG = $db->prepare("SELECT id, curso_id FROM cr_grupos WHERE id = ? AND empresa_id = ? LIMIT 1");
        $stG->bind_param('ii', $grupoId, $empresaId);
        $stG->execute();
        $group = $stG->get_result()->fetch_assoc();
        if (!$group) jerror(404, 'El grupo seleccionado no existe en tu empresa.');

        if ($cursoId <= 0) $cursoId = (int)$group['curso_id'];
        if ($cursoId !== (int)$group['curso_id']) {
          jerror(400, 'El curso del formulario AULA debe coincidir con el curso del grupo.');
        }

        if ($temaId > 0) {
          $stT = $db->prepare("SELECT id FROM cr_temas WHERE id = ? AND curso_id = ? LIMIT 1");
          $stT->bind_param('ii', $temaId, $cursoId);
          $stT->execute();
          if (!$stT->get_result()->fetch_assoc()) {
            jerror(400, 'El tema seleccionado no pertenece al curso.');
          }
        } else {
          $temaId = null;
        }
      }

      $descParam = ($descripcion !== '') ? $descripcion : null;
      $st = $db->prepare(
        "UPDATE cr_formularios
         SET grupo_id = ?,
             curso_id = ?,
             tema_id = ?,
             titulo = ?,
             descripcion = ?,
             intentos_max = ?,
             tiempo_activo = ?,
             duracion_min = ?,
             nota_min = ?,
             mostrar_resultado = ?,
             requisito_cumplimiento = ?,
             campos_fast = ?,
             updated_at = NOW()
         WHERE id = ?
           AND empresa_id = ?"
      );
      $st->bind_param(
        'iiissiiidissii',
        $grupoId,
        $cursoId,
        $temaId,
        $titulo,
        $descParam,
        $intentosMax,
        $tiempoActivo,
        $duracionMin,
        $notaMin,
        $mostrarResultado,
        $requisito,
        $camposFastJson,
        $formId,
        $empresaId
      );
      $st->execute();

      $row = form_summary_row($db, $formId, $empresaId);
      jok(['msg' => 'Formulario actualizado correctamente.', 'data' => $row]);
    }

    case 'form_set_estado': {
      $formId = (int)($_POST['form_id'] ?? 0);
      $estado = normalize_estado((string)($_POST['estado'] ?? ''));
      if ($formId <= 0 || $estado === '') jerror(400, 'Parametros invalidos.');

      $db->begin_transaction();
      try {
        $form = avf_load_form_by_id_company($db, $formId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(404, 'Formulario no encontrado.');
        }

        $actual = strtoupper((string)$form['estado']);
        if ($actual === $estado) {
          $db->commit();
          $row = form_summary_row($db, $formId, $empresaId);
          jok(['msg' => 'El formulario ya tenia ese estado.', 'data' => $row]);
        }

        if ($estado === 'PUBLICADO') {
          $chk = ensure_form_publishable($db, $formId);
          if (empty($chk['ok'])) {
            $db->rollback();
            jerror(409, (string)($chk['msg'] ?? 'No se pudo publicar el formulario.'));
          }
        }

        $st = $db->prepare("UPDATE cr_formularios SET estado = ?, updated_at = NOW() WHERE id = ? AND empresa_id = ?");
        $st->bind_param('sii', $estado, $formId, $empresaId);
        $st->execute();

        $db->commit();
        $row = form_summary_row($db, $formId, $empresaId);
        jok(['msg' => 'Estado actualizado correctamente.', 'data' => $row]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'form_delete': {
      $formId = (int)($_POST['form_id'] ?? 0);
      if ($formId <= 0) jerror(400, 'form_id requerido.');

      $db->begin_transaction();
      try {
        $form = avf_load_form_by_id_company($db, $formId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(404, 'Formulario no encontrado.');
        }

        $stCnt = $db->prepare("SELECT COUNT(*) AS c FROM cr_formulario_intentos WHERE formulario_id = ?");
        $stCnt->bind_param('i', $formId);
        $stCnt->execute();
        $attempts = (int)($stCnt->get_result()->fetch_assoc()['c'] ?? 0);

        if ($attempts > 0) {
          $stClose = $db->prepare("UPDATE cr_formularios SET estado = 'CERRADO', updated_at = NOW() WHERE id = ? AND empresa_id = ?");
          $stClose->bind_param('ii', $formId, $empresaId);
          $stClose->execute();
          $db->commit();
          jok([
            'msg' => 'El formulario tiene intentos registrados. Se cerro (soft) en lugar de eliminarse.',
            'data' => ['form_id' => $formId, 'attempts' => $attempts, 'soft_closed' => 1],
          ]);
        }

        $stDel = $db->prepare("DELETE FROM cr_formularios WHERE id = ? AND empresa_id = ?");
        $stDel->bind_param('ii', $formId, $empresaId);
        $stDel->execute();
        if ($stDel->affected_rows <= 0) {
          $db->rollback();
          jerror(404, 'No se pudo eliminar el formulario.');
        }

        $db->commit();
        jok(['msg' => 'Formulario eliminado correctamente.', 'data' => ['form_id' => $formId]]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'form_detail': {
      $formId = (int)($_GET['form_id'] ?? 0);
      if ($formId <= 0) jerror(400, 'form_id requerido.');

      $form = form_summary_row($db, $formId, $empresaId);
      if (!$form) jerror(404, 'Formulario no encontrado.');

      $stQ = $db->prepare(
        "SELECT id, formulario_id, tipo, enunciado, puntos, orden, created_at, updated_at
         FROM cr_formulario_preguntas
         WHERE formulario_id = ?
         ORDER BY orden ASC, id ASC"
      );
      $stQ->bind_param('i', $formId);
      $stQ->execute();
      $questions = $stQ->get_result()->fetch_all(MYSQLI_ASSOC);

      $optionsByQuestion = [];
      $stO = $db->prepare(
        "SELECT id, pregunta_id, texto, es_correcta, orden
         FROM cr_formulario_opciones
         WHERE pregunta_id IN (
           SELECT id FROM cr_formulario_preguntas WHERE formulario_id = ?
         )
         ORDER BY pregunta_id ASC, orden ASC, id ASC"
      );
      $stO->bind_param('i', $formId);
      $stO->execute();
      foreach ($stO->get_result()->fetch_all(MYSQLI_ASSOC) as $o) {
        $qid = (int)$o['pregunta_id'];
        if (!isset($optionsByQuestion[$qid])) $optionsByQuestion[$qid] = [];
        $optionsByQuestion[$qid][] = $o;
      }

      foreach ($questions as &$q) {
        $qid = (int)$q['id'];
        $q['opciones'] = $optionsByQuestion[$qid] ?? [];
      }
      unset($q);

      $share = null;
      if (strtoupper((string)$form['modo']) === 'FAST' && !empty($form['public_code'])) {
        $code = (string)$form['public_code'];
        $share = [
          'code' => $code,
          'link' => BASE_URL . '/modules/aula_virtual/form_fast.php?c=' . rawurlencode($code),
          'qr_url' => BASE_URL . '/modules/aula_virtual/qr_fast.php?c=' . rawurlencode($code),
        ];
      }

      jok([
        'data' => [
          'form' => $form,
          'questions' => $questions,
          'share' => $share,
        ],
      ]);
    }

    case 'form_share_info': {
      $formId = (int)($_GET['form_id'] ?? 0);
      if ($formId <= 0) jerror(400, 'form_id requerido.');

      $form = avf_load_form_by_id_company($db, $formId, $empresaId, false);
      if (!$form) jerror(404, 'Formulario no encontrado.');
      if (strtoupper((string)$form['modo']) !== 'FAST') jerror(400, 'Solo los formularios FAST tienen link publico.');

      $code = trim((string)($form['public_code'] ?? ''));
      if ($code === '') jerror(500, 'El formulario FAST no tiene codigo publico.');

      jok([
        'data' => [
          'code' => $code,
          'link' => BASE_URL . '/modules/aula_virtual/form_fast.php?c=' . rawurlencode($code),
          'qr_url' => BASE_URL . '/modules/aula_virtual/qr_fast.php?c=' . rawurlencode($code),
        ],
      ]);
    }

    case 'question_add': {
      $formId = (int)($_POST['form_id'] ?? 0);
      $tipo = strtoupper(trim((string)($_POST['tipo'] ?? 'OM_UNICA')));
      $enunciado = trim((string)($_POST['enunciado'] ?? ''));
      $puntos = (float)($_POST['puntos'] ?? 0);
      $orden = (int)($_POST['orden'] ?? 0);
      $options = parse_question_options($_POST['opciones'] ?? '');

      if ($formId <= 0) jerror(400, 'form_id requerido.');
      if (!in_array($tipo, ['OM_UNICA', 'OM_MULTIPLE'], true)) jerror(400, 'Tipo de pregunta invalido.');
      if ($enunciado === '') jerror(400, 'El enunciado es obligatorio.');
      if ($puntos <= 0) jerror(400, 'Los puntos deben ser mayores a 0.');
      if (count($options) < 2) jerror(400, 'Debes registrar al menos 2 opciones.');

      $correctCount = 0;
      foreach ($options as $op) {
        if ((int)$op['es_correcta'] === 1) $correctCount++;
      }
      if ($tipo === 'OM_UNICA' && $correctCount !== 1) {
        jerror(400, 'Una pregunta OM_UNICA debe tener exactamente 1 opcion correcta.');
      }
      if ($tipo === 'OM_MULTIPLE' && $correctCount < 1) {
        jerror(400, 'Una pregunta OM_MULTIPLE debe tener al menos 1 opcion correcta.');
      }

      $db->begin_transaction();
      try {
        $form = avf_load_form_by_id_company($db, $formId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(404, 'Formulario no encontrado.');
        }
        if (strtoupper((string)$form['estado']) !== 'BORRADOR') {
          $db->rollback();
          jerror(409, 'Solo puedes editar preguntas cuando el formulario esta en BORRADOR.');
        }

        $stCnt = $db->prepare("SELECT COUNT(*) AS c FROM cr_formulario_intentos WHERE formulario_id = ?");
        $stCnt->bind_param('i', $formId);
        $stCnt->execute();
        $attempts = (int)($stCnt->get_result()->fetch_assoc()['c'] ?? 0);
        if ($attempts > 0) {
          $db->rollback();
          jerror(409, 'No puedes editar preguntas porque ya existen intentos registrados.');
        }

        if ($orden <= 0) {
          $stMax = $db->prepare("SELECT COALESCE(MAX(orden),0) AS m FROM cr_formulario_preguntas WHERE formulario_id = ?");
          $stMax->bind_param('i', $formId);
          $stMax->execute();
          $orden = (int)($stMax->get_result()->fetch_assoc()['m'] ?? 0) + 1;
        }

        $stQ = $db->prepare(
          "INSERT INTO cr_formulario_preguntas
             (formulario_id, tipo, enunciado, puntos, orden, created_at, updated_at)
           VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stQ->bind_param('issdi', $formId, $tipo, $enunciado, $puntos, $orden);
        $stQ->execute();
        $qid = (int)$db->insert_id;

        $stO = $db->prepare(
          "INSERT INTO cr_formulario_opciones
             (pregunta_id, texto, es_correcta, orden, created_at, updated_at)
           VALUES (?, ?, ?, ?, NOW(), NOW())"
        );
        $idx = 1;
        foreach ($options as $op) {
          $txt = (string)$op['texto'];
          $ok = (int)$op['es_correcta'];
          $stO->bind_param('isii', $qid, $txt, $ok, $idx);
          $stO->execute();
          $idx++;
        }

        $db->commit();
        $detail = form_summary_row($db, $formId, $empresaId);
        jok(['msg' => 'Pregunta creada correctamente.', 'data' => $detail]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'question_update': {
      $questionId = (int)($_POST['question_id'] ?? 0);
      $tipo = strtoupper(trim((string)($_POST['tipo'] ?? 'OM_UNICA')));
      $enunciado = trim((string)($_POST['enunciado'] ?? ''));
      $puntos = (float)($_POST['puntos'] ?? 0);
      $orden = (int)($_POST['orden'] ?? 0);
      $options = parse_question_options($_POST['opciones'] ?? '');

      if ($questionId <= 0) jerror(400, 'question_id requerido.');
      if (!in_array($tipo, ['OM_UNICA', 'OM_MULTIPLE'], true)) jerror(400, 'Tipo de pregunta invalido.');
      if ($enunciado === '') jerror(400, 'El enunciado es obligatorio.');
      if ($puntos <= 0) jerror(400, 'Los puntos deben ser mayores a 0.');
      if (count($options) < 2) jerror(400, 'Debes registrar al menos 2 opciones.');

      $correctCount = 0;
      foreach ($options as $op) {
        if ((int)$op['es_correcta'] === 1) $correctCount++;
      }
      if ($tipo === 'OM_UNICA' && $correctCount !== 1) {
        jerror(400, 'Una pregunta OM_UNICA debe tener exactamente 1 opcion correcta.');
      }
      if ($tipo === 'OM_MULTIPLE' && $correctCount < 1) {
        jerror(400, 'Una pregunta OM_MULTIPLE debe tener al menos 1 opcion correcta.');
      }

      $db->begin_transaction();
      try {
        $stQ0 = $db->prepare(
          "SELECT q.id, q.formulario_id
           FROM cr_formulario_preguntas q
           JOIN cr_formularios f ON f.id = q.formulario_id
           WHERE q.id = ?
             AND f.empresa_id = ?
           LIMIT 1
           FOR UPDATE"
        );
        $stQ0->bind_param('ii', $questionId, $empresaId);
        $stQ0->execute();
        $rowQ = $stQ0->get_result()->fetch_assoc();
        if (!$rowQ) {
          $db->rollback();
          jerror(404, 'Pregunta no encontrada.');
        }
        $formId = (int)$rowQ['formulario_id'];

        $form = avf_load_form_by_id_company($db, $formId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(404, 'Formulario no encontrado.');
        }
        if (strtoupper((string)$form['estado']) !== 'BORRADOR') {
          $db->rollback();
          jerror(409, 'Solo puedes editar preguntas cuando el formulario esta en BORRADOR.');
        }

        $stCnt = $db->prepare("SELECT COUNT(*) AS c FROM cr_formulario_intentos WHERE formulario_id = ?");
        $stCnt->bind_param('i', $formId);
        $stCnt->execute();
        $attempts = (int)($stCnt->get_result()->fetch_assoc()['c'] ?? 0);
        if ($attempts > 0) {
          $db->rollback();
          jerror(409, 'No puedes editar preguntas porque ya existen intentos registrados.');
        }

        if ($orden <= 0) {
          $stOld = $db->prepare("SELECT orden FROM cr_formulario_preguntas WHERE id = ? LIMIT 1");
          $stOld->bind_param('i', $questionId);
          $stOld->execute();
          $orden = (int)($stOld->get_result()->fetch_assoc()['orden'] ?? 1);
        }

        $stUp = $db->prepare(
          "UPDATE cr_formulario_preguntas
           SET tipo = ?, enunciado = ?, puntos = ?, orden = ?, updated_at = NOW()
           WHERE id = ?"
        );
        $stUp->bind_param('ssdii', $tipo, $enunciado, $puntos, $orden, $questionId);
        $stUp->execute();

        $stDel = $db->prepare("DELETE FROM cr_formulario_opciones WHERE pregunta_id = ?");
        $stDel->bind_param('i', $questionId);
        $stDel->execute();

        $stIns = $db->prepare(
          "INSERT INTO cr_formulario_opciones
             (pregunta_id, texto, es_correcta, orden, created_at, updated_at)
           VALUES (?, ?, ?, ?, NOW(), NOW())"
        );
        $idx = 1;
        foreach ($options as $op) {
          $txt = (string)$op['texto'];
          $ok = (int)$op['es_correcta'];
          $stIns->bind_param('isii', $questionId, $txt, $ok, $idx);
          $stIns->execute();
          $idx++;
        }

        $db->commit();
        $detail = form_summary_row($db, $formId, $empresaId);
        jok(['msg' => 'Pregunta actualizada correctamente.', 'data' => $detail]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'question_delete': {
      $questionId = (int)($_POST['question_id'] ?? 0);
      if ($questionId <= 0) jerror(400, 'question_id requerido.');

      $db->begin_transaction();
      try {
        $stQ0 = $db->prepare(
          "SELECT q.id, q.formulario_id
           FROM cr_formulario_preguntas q
           JOIN cr_formularios f ON f.id = q.formulario_id
           WHERE q.id = ?
             AND f.empresa_id = ?
           LIMIT 1
           FOR UPDATE"
        );
        $stQ0->bind_param('ii', $questionId, $empresaId);
        $stQ0->execute();
        $rowQ = $stQ0->get_result()->fetch_assoc();
        if (!$rowQ) {
          $db->rollback();
          jerror(404, 'Pregunta no encontrada.');
        }
        $formId = (int)$rowQ['formulario_id'];

        $form = avf_load_form_by_id_company($db, $formId, $empresaId, true);
        if (!$form) {
          $db->rollback();
          jerror(404, 'Formulario no encontrado.');
        }
        if (strtoupper((string)$form['estado']) !== 'BORRADOR') {
          $db->rollback();
          jerror(409, 'Solo puedes editar preguntas cuando el formulario esta en BORRADOR.');
        }

        $stCnt = $db->prepare("SELECT COUNT(*) AS c FROM cr_formulario_intentos WHERE formulario_id = ?");
        $stCnt->bind_param('i', $formId);
        $stCnt->execute();
        $attempts = (int)($stCnt->get_result()->fetch_assoc()['c'] ?? 0);
        if ($attempts > 0) {
          $db->rollback();
          jerror(409, 'No puedes eliminar preguntas porque ya existen intentos registrados.');
        }

        $stDel = $db->prepare("DELETE FROM cr_formulario_preguntas WHERE id = ?");
        $stDel->bind_param('i', $questionId);
        $stDel->execute();
        if ($stDel->affected_rows <= 0) {
          $db->rollback();
          jerror(404, 'No se pudo eliminar la pregunta.');
        }

        $db->commit();
        $detail = form_summary_row($db, $formId, $empresaId);
        jok(['msg' => 'Pregunta eliminada correctamente.', 'data' => $detail]);
      } catch (Throwable $e) {
        $db->rollback();
        throw $e;
      }
    }

    case 'attempts_list': {
      $formId = (int)($_GET['form_id'] ?? 0);
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
      $offset = ($page - 1) * $perPage;
      if ($formId <= 0) jerror(400, 'form_id requerido.');

      $form = avf_load_form_by_id_company($db, $formId, $empresaId, false);
      if (!$form) jerror(404, 'Formulario no encontrado.');

      $stCount = $db->prepare("SELECT COUNT(*) AS c FROM cr_formulario_intentos WHERE formulario_id = ?");
      $stCount->bind_param('i', $formId);
      $stCount->execute();
      $total = (int)($stCount->get_result()->fetch_assoc()['c'] ?? 0);

      $st = $db->prepare(
        "SELECT
           i.id,
           i.formulario_id,
           i.modo,
           i.usuario_id,
           i.tipo_doc_id,
           td.codigo AS tipo_doc_codigo,
           i.nro_doc,
           i.nombres,
           i.apellidos,
           i.celular,
           i.categorias_json,
           i.intento_nro,
           i.token,
           i.status,
           i.start_at,
           i.expires_at,
           i.submitted_at,
           i.last_saved_at,
           i.puntaje_obtenido,
           i.nota_final,
           i.aprobado
         FROM cr_formulario_intentos i
         LEFT JOIN cq_tipos_documento td ON td.id = i.tipo_doc_id
         WHERE i.formulario_id = ?
         ORDER BY i.id DESC
         LIMIT ? OFFSET ?"
      );
      $st->bind_param('iii', $formId, $perPage, $offset);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      jok([
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
      ]);
    }

    case 'attempt_delete': {
      $formId = (int)($_POST['form_id'] ?? 0);
      $attemptId = (int)($_POST['attempt_id'] ?? 0);
      if ($formId <= 0 || $attemptId <= 0) jerror(400, 'Parametros invalidos.');

      $db->begin_transaction();
      try {
        $st = $db->prepare(
          "SELECT i.id
           FROM cr_formulario_intentos i
           JOIN cr_formularios f ON f.id = i.formulario_id
           WHERE i.id = ?
             AND i.formulario_id = ?
             AND f.empresa_id = ?
           LIMIT 1
           FOR UPDATE"
        );
        $st->bind_param('iii', $attemptId, $formId, $empresaId);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) {
          $db->rollback();
          jerror(404, 'Intento no encontrado.');
        }

        $stDel = $db->prepare("DELETE FROM cr_formulario_intentos WHERE id = ?");
        $stDel->bind_param('i', $attemptId);
        $stDel->execute();
        if ($stDel->affected_rows <= 0) {
          $db->rollback();
          jerror(404, 'No se pudo eliminar el intento.');
        }

        $db->commit();
        jok(['msg' => 'Intento eliminado correctamente.', 'data' => ['attempt_id' => $attemptId]]);
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
