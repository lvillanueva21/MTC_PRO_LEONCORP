<?php
// modules/consola/comprobantes/api.php
require_once __DIR__ . '/../../../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = db();
$db->set_charset('utf8mb4');

function cmp_error(int $code, string $msg, array $extra = []): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
  exit;
}

function cmp_ok(array $payload = []): void {
  echo json_encode(['ok' => true] + $payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function cmp_serie_normalize(string $serie): string {
  $serie = strtoupper(trim($serie));
  $serie = preg_replace('/\s+/', '', $serie);
  return (string)$serie;
}

function cmp_serie_validate(string $serie): bool {
  return (bool)preg_match('/^[A-Z0-9][A-Z0-9-]{0,9}$/', $serie);
}

function cmp_get_empresa(mysqli $db, int $empresaId): ?array {
  $st = $db->prepare("SELECT id, nombre, razon_social, ruc FROM mtp_empresas WHERE id = ? LIMIT 1");
  $st->bind_param('i', $empresaId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  return $row ?: null;
}

function cmp_get_serie_usage(mysqli $db, int $serieId): array {
  $st = $db->prepare("
    SELECT
      COUNT(*) AS used_count,
      COALESCE(MAX(numero), 0) AS max_numero
    FROM pos_ventas
    WHERE serie_id = ?
  ");
  $st->bind_param('i', $serieId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc() ?: [];
  $st->close();

  return [
    'used_count' => (int)($row['used_count'] ?? 0),
    'max_numero' => (int)($row['max_numero'] ?? 0),
  ];
}

function cmp_find_active_series(mysqli $db, int $empresaId, int $excludeId = 0): ?array {
  if ($excludeId > 0) {
    $st = $db->prepare("
      SELECT id, serie
      FROM pos_series
      WHERE id_empresa = ? AND tipo_comprobante = 'TICKET' AND activo = 1 AND id <> ?
      ORDER BY id ASC
      LIMIT 1
    ");
    $st->bind_param('ii', $empresaId, $excludeId);
  } else {
    $st = $db->prepare("
      SELECT id, serie
      FROM pos_series
      WHERE id_empresa = ? AND tipo_comprobante = 'TICKET' AND activo = 1
      ORDER BY id ASC
      LIMIT 1
    ");
    $st->bind_param('i', $empresaId);
  }
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  return $row ?: null;
}

function cmp_exists_same_serie(mysqli $db, int $empresaId, string $serie, int $excludeId = 0): bool {
  if ($excludeId > 0) {
    $st = $db->prepare("
      SELECT id
      FROM pos_series
      WHERE id_empresa = ? AND tipo_comprobante = 'TICKET' AND serie = ? AND id <> ?
      LIMIT 1
    ");
    $st->bind_param('isi', $empresaId, $serie, $excludeId);
  } else {
    $st = $db->prepare("
      SELECT id
      FROM pos_series
      WHERE id_empresa = ? AND tipo_comprobante = 'TICKET' AND serie = ?
      LIMIT 1
    ");
    $st->bind_param('is', $empresaId, $serie);
  }
  $st->execute();
  $exists = (bool)$st->get_result()->fetch_assoc();
  $st->close();
  return $exists;
}

function cmp_get_series_row(mysqli $db, int $id): ?array {
  $st = $db->prepare("
    SELECT
      ps.id,
      ps.id_empresa,
      e.nombre AS empresa,
      e.razon_social,
      e.ruc,
      ps.tipo_comprobante,
      ps.serie,
      ps.siguiente_numero,
      ps.activo,
      DATE_FORMAT(ps.creado, '%Y-%m-%d %H:%i:%s') AS creado,
      DATE_FORMAT(ps.actualizado, '%Y-%m-%d %H:%i:%s') AS actualizado
    FROM pos_series ps
    INNER JOIN mtp_empresas e ON e.id = ps.id_empresa
    WHERE ps.id = ?
    LIMIT 1
  ");
  $st->bind_param('i', $id);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  if (!$row) {
    return null;
  }

  $usage = cmp_get_serie_usage($db, (int)$id);
  $row['used_count'] = $usage['used_count'];
  $row['max_numero'] = $usage['max_numero'];
  $row['last_ticket'] = $usage['used_count'] > 0
    ? ($row['serie'] . '-' . str_pad((string)$usage['max_numero'], 4, '0', STR_PAD_LEFT))
    : '';
  $row['can_change_series'] = $usage['used_count'] === 0;

  return $row;
}

try {
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'empresas': {
      $rs = $db->query("SELECT id, nombre, razon_social, ruc FROM mtp_empresas ORDER BY nombre ASC");
      cmp_ok(['data' => $rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'summary': {
      $summary = [
        'total_empresas' => 0,
        'empresas_con_activa' => 0,
        'empresas_sin_series' => 0,
        'series_activas' => 0,
      ];

      $row1 = $db->query("SELECT COUNT(*) AS c FROM mtp_empresas")->fetch_assoc();
      $summary['total_empresas'] = (int)($row1['c'] ?? 0);

      $row2 = $db->query("
        SELECT COUNT(DISTINCT id_empresa) AS c
        FROM pos_series
        WHERE tipo_comprobante = 'TICKET' AND activo = 1
      ")->fetch_assoc();
      $summary['empresas_con_activa'] = (int)($row2['c'] ?? 0);

      $row3 = $db->query("
        SELECT COUNT(*) AS c
        FROM mtp_empresas e
        WHERE NOT EXISTS (
          SELECT 1
          FROM pos_series ps
          WHERE ps.id_empresa = e.id AND ps.tipo_comprobante = 'TICKET'
        )
      ")->fetch_assoc();
      $summary['empresas_sin_series'] = (int)($row3['c'] ?? 0);

      $row4 = $db->query("
        SELECT COUNT(*) AS c
        FROM pos_series
        WHERE tipo_comprobante = 'TICKET' AND activo = 1
      ")->fetch_assoc();
      $summary['series_activas'] = (int)($row4['c'] ?? 0);

      cmp_ok(['data' => $summary]);
    }

    case 'company_status_list': {
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 6)));
      $offset = ($page - 1) * $perPage;
      $q = trim((string)($_GET['q'] ?? ''));
      $status = trim((string)($_GET['status'] ?? 'all'));
      $allowedStatus = ['all', 'with_active', 'only_inactive', 'no_series'];
      if (!in_array($status, $allowedStatus, true)) {
        $status = 'all';
      }

      $where = [];
      $types = '';
      $vals = [];

      if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "(e.nombre LIKE ? OR e.razon_social LIKE ? OR e.ruc LIKE ?)";
        $types .= 'sss';
        $vals[] = $like;
        $vals[] = $like;
        $vals[] = $like;
      }

      if ($status === 'with_active') {
        $where[] = "COALESCE(s.active_series, 0) > 0";
      } elseif ($status === 'only_inactive') {
        $where[] = "COALESCE(s.total_series, 0) > 0 AND COALESCE(s.active_series, 0) = 0";
      } elseif ($status === 'no_series') {
        $where[] = "COALESCE(s.total_series, 0) = 0";
      }

      $sqlFrom = "
        FROM mtp_empresas e
        LEFT JOIN (
          SELECT
            ps.id_empresa,
            COUNT(*) AS total_series,
            SUM(CASE WHEN ps.activo = 1 THEN 1 ELSE 0 END) AS active_series,
            SUBSTRING_INDEX(
              GROUP_CONCAT(CASE WHEN ps.activo = 1 THEN ps.serie END ORDER BY ps.id ASC SEPARATOR '||'),
              '||',
              1
            ) AS active_series_label,
            CAST(SUBSTRING_INDEX(
              GROUP_CONCAT(CASE WHEN ps.activo = 1 THEN ps.id END ORDER BY ps.id ASC SEPARATOR ','),
              ',',
              1
            ) AS UNSIGNED) AS active_series_id,
            CAST(SUBSTRING_INDEX(
              GROUP_CONCAT(ps.id ORDER BY ps.activo DESC, ps.id DESC SEPARATOR ','),
              ',',
              1
            ) AS UNSIGNED) AS latest_series_id
          FROM pos_series ps
          WHERE ps.tipo_comprobante = 'TICKET'
          GROUP BY ps.id_empresa
        ) s ON s.id_empresa = e.id
      ";

      $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

      $sqlCount = "SELECT COUNT(*) AS c {$sqlFrom}{$whereSql}";
      $stCount = $db->prepare($sqlCount);
      if ($types !== '') {
        $params = array_merge([$types], $vals);
        $refs = [];
        foreach ($params as $k => $v) $refs[$k] = &$params[$k];
        call_user_func_array([$stCount, 'bind_param'], $refs);
      }
      $stCount->execute();
      $total = (int)(($stCount->get_result()->fetch_assoc()['c'] ?? 0));
      $stCount->close();

      $sql = "
        SELECT
          e.id,
          e.nombre,
          e.razon_social,
          e.ruc,
          COALESCE(s.total_series, 0) AS total_series,
          COALESCE(s.active_series, 0) AS active_series,
          COALESCE(s.active_series_label, '') AS active_series_label,
          COALESCE(s.active_series_id, 0) AS active_series_id,
          COALESCE(s.latest_series_id, 0) AS latest_series_id
        {$sqlFrom}
        {$whereSql}
        ORDER BY e.nombre ASC
        LIMIT ? OFFSET ?
      ";
      $st = $db->prepare($sql);
      $typesList = $types . 'ii';
      $valsList = array_merge($vals, [$perPage, $offset]);
      $paramsList = array_merge([$typesList], $valsList);
      $refsList = [];
      foreach ($paramsList as $k => $v) $refsList[$k] = &$paramsList[$k];
      call_user_func_array([$st, 'bind_param'], $refsList);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      $st->close();

      cmp_ok(['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
    }

    case 'list': {
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 8)));
      $offset = ($page - 1) * $perPage;
      $empresa = (int)($_GET['empresa'] ?? 0);
      $q = trim((string)($_GET['q'] ?? ''));
      $activo = trim((string)($_GET['activo'] ?? 'all'));

      $where = ["ps.tipo_comprobante = 'TICKET'"];
      $types = '';
      $vals = [];

      if ($empresa > 0) {
        $where[] = "ps.id_empresa = ?";
        $types .= 'i';
        $vals[] = $empresa;
      }

      if ($activo === '1' || $activo === '0') {
        $where[] = "ps.activo = ?";
        $types .= 'i';
        $vals[] = (int)$activo;
      }

      if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "(ps.serie LIKE ? OR e.nombre LIKE ? OR e.ruc LIKE ?)";
        $types .= 'sss';
        $vals[] = $like;
        $vals[] = $like;
        $vals[] = $like;
      }

      $whereSql = ' WHERE ' . implode(' AND ', $where);
      $sqlFrom = "
        FROM pos_series ps
        INNER JOIN mtp_empresas e ON e.id = ps.id_empresa
        LEFT JOIN (
          SELECT
            serie_id,
            COUNT(*) AS used_count,
            MAX(numero) AS max_numero
          FROM pos_ventas
          GROUP BY serie_id
        ) pv ON pv.serie_id = ps.id
      ";

      $sqlCount = "SELECT COUNT(*) AS c {$sqlFrom}{$whereSql}";
      $stCount = $db->prepare($sqlCount);
      if ($types !== '') {
        $params = array_merge([$types], $vals);
        $refs = [];
        foreach ($params as $k => $v) $refs[$k] = &$params[$k];
        call_user_func_array([$stCount, 'bind_param'], $refs);
      }
      $stCount->execute();
      $total = (int)(($stCount->get_result()->fetch_assoc()['c'] ?? 0));
      $stCount->close();

      $sql = "
        SELECT
          ps.id,
          ps.id_empresa,
          e.nombre AS empresa,
          e.ruc,
          ps.tipo_comprobante,
          ps.serie,
          ps.siguiente_numero,
          ps.activo,
          COALESCE(pv.used_count, 0) AS used_count,
          COALESCE(pv.max_numero, 0) AS max_numero
        {$sqlFrom}
        {$whereSql}
        ORDER BY e.nombre ASC, ps.activo DESC, ps.id DESC
        LIMIT ? OFFSET ?
      ";
      $st = $db->prepare($sql);
      $typesList = $types . 'ii';
      $valsList = array_merge($vals, [$perPage, $offset]);
      $paramsList = array_merge([$typesList], $valsList);
      $refsList = [];
      foreach ($paramsList as $k => $v) $refsList[$k] = &$paramsList[$k];
      call_user_func_array([$st, 'bind_param'], $refsList);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      $st->close();

      foreach ($rows as &$row) {
        $usedCount = (int)($row['used_count'] ?? 0);
        $maxNumero = (int)($row['max_numero'] ?? 0);
        $row['last_ticket'] = $usedCount > 0
          ? ($row['serie'] . '-' . str_pad((string)$maxNumero, 4, '0', STR_PAD_LEFT))
          : '';
      }
      unset($row);

      cmp_ok(['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
    }

    case 'get': {
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) {
        cmp_error(400, 'Serie requerida.');
      }

      $row = cmp_get_series_row($db, $id);
      if (!$row) {
        cmp_error(404, 'No se encontro el ticket solicitado.');
      }

      cmp_ok(['data' => $row]);
    }

    case 'create': {
      $empresaId = (int)($_POST['id_empresa'] ?? 0);
      $serie = cmp_serie_normalize((string)($_POST['serie'] ?? ''));
      $siguienteNumero = (int)($_POST['siguiente_numero'] ?? 0);
      $activo = (int)($_POST['activo'] ?? 1);

      if ($empresaId <= 0) cmp_error(400, 'Selecciona una empresa valida.');
      if (!cmp_get_empresa($db, $empresaId)) cmp_error(404, 'La empresa indicada no existe.');
      if (!cmp_serie_validate($serie)) cmp_error(400, 'La serie solo admite letras, numeros y guion, con maximo 10 caracteres.');
      if ($siguienteNumero < 1) cmp_error(400, 'El siguiente numero debe ser mayor o igual a 1.');
      if (!in_array($activo, [0, 1], true)) cmp_error(400, 'Estado invalido.');
      if (cmp_exists_same_serie($db, $empresaId, $serie)) cmp_error(400, 'La empresa ya tiene registrada esa serie.');
      if ($activo === 1) {
        $otherActive = cmp_find_active_series($db, $empresaId);
        if ($otherActive) {
          cmp_error(400, 'La empresa ya tiene una serie activa. Desactiva la actual antes de activar otra.');
        }
      }

      $st = $db->prepare("
        INSERT INTO pos_series (id_empresa, tipo_comprobante, serie, siguiente_numero, activo)
        VALUES (?, 'TICKET', ?, ?, ?)
      ");
      $st->bind_param('isii', $empresaId, $serie, $siguienteNumero, $activo);
      $st->execute();
      $newId = (int)$db->insert_id;
      $st->close();

      cmp_ok(['id' => $newId, 'msg' => 'Ticket POS creado correctamente.']);
    }

    case 'update': {
      $id = (int)($_POST['id'] ?? 0);
      $empresaId = (int)($_POST['id_empresa'] ?? 0);
      $serie = cmp_serie_normalize((string)($_POST['serie'] ?? ''));
      $siguienteNumero = (int)($_POST['siguiente_numero'] ?? 0);
      $activo = (int)($_POST['activo'] ?? 0);

      if ($id <= 0) cmp_error(400, 'Serie invalida.');
      if ($empresaId <= 0) cmp_error(400, 'Empresa invalida.');
      if (!cmp_serie_validate($serie)) cmp_error(400, 'La serie solo admite letras, numeros y guion, con maximo 10 caracteres.');
      if ($siguienteNumero < 1) cmp_error(400, 'El siguiente numero debe ser mayor o igual a 1.');
      if (!in_array($activo, [0, 1], true)) cmp_error(400, 'Estado invalido.');

      $current = cmp_get_series_row($db, $id);
      if (!$current) cmp_error(404, 'La serie indicada no existe.');

      $usedCount = (int)$current['used_count'];
      $maxNumero = (int)$current['max_numero'];

      if ($empresaId !== (int)$current['id_empresa']) {
        cmp_error(400, 'Por seguridad, la empresa no se puede cambiar desde esta edicion.');
      }

      if ($usedCount > 0 && $serie !== (string)$current['serie']) {
        cmp_error(400, 'No puedes cambiar la serie porque ya tiene ventas emitidas.');
      }

      if ($usedCount > 0 && $siguienteNumero <= $maxNumero) {
        cmp_error(400, 'El siguiente numero debe ser mayor al ultimo correlativo ya usado en la serie.');
      }

      if (cmp_exists_same_serie($db, $empresaId, $serie, $id)) {
        cmp_error(400, 'Ya existe otra serie igual en esta empresa.');
      }

      if ($activo === 1) {
        $otherActive = cmp_find_active_series($db, $empresaId, $id);
        if ($otherActive) {
          cmp_error(400, 'La empresa ya tiene otra serie activa. Desactivala antes de activar esta.');
        }
      }

      $st = $db->prepare("
        UPDATE pos_series
        SET serie = ?, siguiente_numero = ?, activo = ?
        WHERE id = ?
        LIMIT 1
      ");
      $st->bind_param('siii', $serie, $siguienteNumero, $activo, $id);
      $st->execute();
      $st->close();

      cmp_ok(['id' => $id, 'msg' => 'Ticket POS actualizado correctamente.']);
    }

    case 'toggle_active': {
      $id = (int)($_POST['id'] ?? 0);
      $activo = (int)($_POST['activo'] ?? -1);
      if ($id <= 0) cmp_error(400, 'Serie invalida.');
      if (!in_array($activo, [0, 1], true)) cmp_error(400, 'Estado invalido.');

      $current = cmp_get_series_row($db, $id);
      if (!$current) cmp_error(404, 'La serie indicada no existe.');

      if ($activo === 1) {
        $otherActive = cmp_find_active_series($db, (int)$current['id_empresa'], $id);
        if ($otherActive) {
          cmp_error(400, 'La empresa ya tiene otra serie activa. Desactivala antes de activar esta.');
        }
      }

      $st = $db->prepare("UPDATE pos_series SET activo = ? WHERE id = ? LIMIT 1");
      $st->bind_param('ii', $activo, $id);
      $st->execute();
      $st->close();

      cmp_ok(['msg' => $activo === 1 ? 'Serie activada correctamente.' : 'Serie desactivada correctamente.']);
    }

    default:
      cmp_error(400, 'Accion no valida.');
  }
} catch (mysqli_sql_exception $e) {
  cmp_error(500, 'Error del servidor.', ['dev' => $e->getMessage()]);
} catch (Throwable $e) {
  cmp_error(500, 'Error del servidor.', ['dev' => $e->getMessage()]);
}
