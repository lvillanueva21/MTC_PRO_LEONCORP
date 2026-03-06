<?php
// modules/alerta/api.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

acl_require_ids([1, 3, 4]);
verificarPermiso([1, 3, 4]);

$u = currentUser();
$empresaId = (int)($u['empresa']['id'] ?? 0);
if ($empresaId <= 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Tu sesión no tiene empresa asignada.']);
    exit;
}

$db = db();
$db->set_charset('utf8mb4');

function al_error(int $code, string $msg, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function al_ok(array $data = []): void
{
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function al_trim($value): string
{
    return trim((string)$value);
}

function al_cut($value, int $max): string
{
    $txt = trim((string)$value);
    if ($txt === '') {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($txt, 0, $max, 'UTF-8');
    }
    return substr($txt, 0, $max);
}

function al_match_key($value): string
{
    $txt = al_trim($value);
    if ($txt === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $txt = mb_strtolower($txt, 'UTF-8');
    } else {
        $txt = strtolower($txt);
    }

    $from = ['á','à','ä','â','ã','å','Á','À','Ä','Â','Ã','Å','é','è','ë','ê','É','È','Ë','Ê','í','ì','ï','î','Í','Ì','Ï','Î','ó','ò','ö','ô','õ','Ó','Ò','Ö','Ô','Õ','ú','ù','ü','û','Ú','Ù','Ü','Û','ñ','Ñ','ç','Ç'];
    $to =   ['a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','i','i','i','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','n','n','c','c'];
    $txt = str_replace($from, $to, $txt);

    $txt = preg_replace('/[^a-z0-9 ]+/', ' ', $txt);
    $txt = preg_replace('/\s+/', ' ', $txt);
    return trim((string)$txt);
}

function al_week_range(): array
{
    $now = new DateTimeImmutable('now');
    $start = $now->modify('monday this week')->setTime(0, 0, 0);
    $end = $start->modify('+6 days')->setTime(23, 59, 59);
    return [$start, $end];
}

function al_next_ts(array $row): ?int
{
    $tipo = (string)($row['tipo'] ?? 'ONCE');
    $intervalo = (int)($row['intervalo_dias'] ?? 0);
    $fechaBase = (string)($row['fecha_base'] ?? '');
    if ($fechaBase === '') {
        return null;
    }

    try {
        $base = new DateTimeImmutable($fechaBase);
    } catch (Throwable $e) {
        return null;
    }

    $now = new DateTimeImmutable('now');

    if ($tipo === 'ONCE') {
        return ($base >= $now) ? $base->getTimestamp() : null;
    }

    if ($tipo === 'MONTHLY') {
        $next = $base;
        while ($next < $now) {
            $next = $next->add(new DateInterval('P1M'));
        }
        return $next->getTimestamp();
    }

    if ($tipo === 'YEARLY') {
        $next = $base;
        while ($next < $now) {
            $next = $next->add(new DateInterval('P1Y'));
        }
        return $next->getTimestamp();
    }

    if ($tipo === 'INTERVAL') {
        if ($intervalo <= 0) {
            return null;
        }

        $baseTs = $base->getTimestamp();
        $nowTs = $now->getTimestamp();
        if ($nowTs <= $baseTs) {
            return $baseTs;
        }

        $secs = $intervalo * 86400;
        $k = (int)ceil(($nowTs - $baseTs) / $secs);
        return $baseTs + ($k * $secs);
    }

    return null;
}

function al_hydrate_alert(array $row): array
{
    $nextTs = al_next_ts($row);
    $nowTs = time();
    $anticipacionDias = (int)($row['anticipacion_dias'] ?? 0);
    $warnFromTs = $nextTs ? ($nextTs - ($anticipacionDias * 86400)) : null;

    $row['_next_ts'] = $nextTs;
    $row['_next_iso'] = $nextTs ? date('Y-m-d H:i:s', $nextTs) : null;
    $row['_warn_from_ts'] = $warnFromTs;
    $row['_in_seconds'] = $nextTs ? max(0, $nextTs - $nowTs) : null;
    $row['_overdue'] = $nextTs ? ($nowTs > $nextTs) : false;
    $row['_in_window'] = ($warnFromTs !== null) ? ($nowTs >= $warnFromTs) : false;

    return $row;
}

$action = al_trim($_POST['action'] ?? ($_GET['action'] ?? ''));

try {
    if ($action === 'meta') {
        al_ok([
            'data' => [
                'tipos' => [
                    ['value' => 'ONCE', 'label' => 'Una sola vez'],
                    ['value' => 'MONTHLY', 'label' => 'Mensual'],
                    ['value' => 'YEARLY', 'label' => 'Anual'],
                    ['value' => 'INTERVAL', 'label' => 'Cada N días'],
                ],
            ],
        ]);
    }

    if ($action === 'categories') {
        $q = al_trim($_GET['q'] ?? '');
        $limit = max(1, min(30, (int)($_GET['limit'] ?? 12)));

        $sql = "SELECT categoria, COUNT(*) cnt
                FROM al_alertas
                WHERE id_empresa=? AND categoria IS NOT NULL AND categoria<>''
                GROUP BY categoria
                ORDER BY cnt DESC, categoria ASC
                LIMIT 300";
        $st = $db->prepare($sql);
        $st->bind_param('i', $empresaId);
        $st->execute();
        $all = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $st->close();

        $needle = al_match_key($q);
        $out = [];

        foreach ($all as $r) {
            $label = al_trim($r['categoria'] ?? '');
            if ($label === '') {
                continue;
            }

            $key = al_match_key($label);
            if ($key === '') {
                continue;
            }

            $score = 999.0;
            if ($needle === '') {
                $score = 10.0;
            } elseif ($key === $needle) {
                $score = 0.0;
            } elseif (strpos($key, $needle) === 0) {
                $score = 1.0;
            } elseif (strpos($key, $needle) !== false) {
                $score = 2.0;
            } else {
                $dist = levenshtein($needle, $key);
                $maxLen = max(strlen($needle), strlen($key));
                $ratio = ($maxLen > 0) ? ($dist / $maxLen) : 1.0;
                if ($ratio > 0.45) {
                    continue;
                }
                $score = 3.0 + $ratio;
            }

            $out[] = [
                'label' => $label,
                'count' => (int)($r['cnt'] ?? 0),
                'score' => $score,
            ];
        }

        usort($out, function ($a, $b) {
            if ($a['score'] == $b['score']) {
                if ($a['count'] === $b['count']) {
                    return strcasecmp((string)$a['label'], (string)$b['label']);
                }
                return $b['count'] <=> $a['count'];
            }
            return ($a['score'] < $b['score']) ? -1 : 1;
        });

        $out = array_slice($out, 0, $limit);
        foreach ($out as &$row) {
            unset($row['score']);
        }
        unset($row);

        al_ok(['data' => $out]);
    }

    if ($action === 'week') {
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
        $includeInactive = ((int)($_GET['include_inactive'] ?? 0) === 1);
        [$weekStart, $weekEnd] = al_week_range();
        $weekStartTs = $weekStart->getTimestamp();
        $weekEndTs = $weekEnd->getTimestamp();

        $sql = "SELECT * FROM al_alertas WHERE id_empresa=?";
        if (!$includeInactive) {
            $sql .= " AND activo=1";
        }
        $st = $db->prepare($sql);
        $st->bind_param('i', $empresaId);
        $st->execute();
        $all = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $st->close();

        $rows = [];
        foreach ($all as $r) {
            $row = al_hydrate_alert($r);
            $nextTs = (int)($row['_next_ts'] ?? 0);
            if ($nextTs <= 0) {
                continue;
            }
            if ($nextTs < $weekStartTs || $nextTs > $weekEndTs) {
                continue;
            }
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            $ta = (int)($a['_next_ts'] ?? 0);
            $tb = (int)($b['_next_ts'] ?? 0);
            if ($ta === $tb) {
                return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
            }
            return $ta <=> $tb;
        });

        al_ok([
            'data' => array_slice($rows, 0, $limit),
            'total' => count($rows),
            'week_start' => $weekStart->format('Y-m-d H:i:s'),
            'week_end' => $weekEnd->format('Y-m-d H:i:s'),
        ]);
    }

    if ($action === 'list') {
        $q = al_trim($_GET['q'] ?? '');
        $estado = al_trim($_GET['estado'] ?? '');
        $tipo = al_trim($_GET['tipo'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(1, min(50, (int)($_GET['per'] ?? 10)));

        $where = ['id_empresa=?'];
        $types = 'i';
        $params = [$empresaId];

        if ($estado === '0' || $estado === '1') {
            $where[] = 'activo=?';
            $types .= 'i';
            $params[] = (int)$estado;
        }

        if ($tipo !== '') {
            if (!in_array($tipo, ['ONCE', 'MONTHLY', 'YEARLY', 'INTERVAL'], true)) {
                al_error(422, 'Tipo de filtro inválido.');
            }
            $where[] = 'tipo=?';
            $types .= 's';
            $params[] = $tipo;
        }

        if ($q !== '') {
            $where[] = '(titulo LIKE ? OR categoria LIKE ? OR descripcion LIKE ?)';
            $types .= 'sss';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT * FROM al_alertas WHERE " . implode(' AND ', $where);
        $st = $db->prepare($sql);
        $st->bind_param($types, ...$params);
        $st->execute();
        $all = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $st->close();

        $rows = array_map('al_hydrate_alert', $all);
        usort($rows, function ($a, $b) {
            $ta = $a['_next_ts'];
            $tb = $b['_next_ts'];
            if ($ta === $tb) {
                return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
            }
            if ($ta === null) {
                return 1;
            }
            if ($tb === null) {
                return -1;
            }
            return $ta <=> $tb;
        });

        $total = count($rows);
        $offset = ($page - 1) * $per;
        $slice = array_slice($rows, $offset, $per);

        al_ok([
            'data' => $slice,
            'total' => $total,
            'page' => $page,
            'per' => $per,
        ]);
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            al_error(400, 'ID de alerta inválido.');
        }

        $st = $db->prepare("SELECT * FROM al_alertas WHERE id=? AND id_empresa=? LIMIT 1");
        $st->bind_param('ii', $id, $empresaId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$row) {
            al_error(404, 'No se encontró la alerta solicitada.');
        }

        al_ok(['data' => al_hydrate_alert($row)]);
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $titulo = al_cut($_POST['titulo'] ?? '', 160);
        $categoria = al_cut($_POST['categoria'] ?? '', 80);
        $descripcion = al_cut($_POST['descripcion'] ?? '', 255);
        $tipo = al_trim($_POST['tipo'] ?? 'ONCE');
        $intervaloDias = (int)($_POST['intervalo_dias'] ?? 0);
        $fechaInput = al_trim($_POST['fecha_base'] ?? '');
        $anticipacionDias = max(0, (int)($_POST['anticipacion_dias'] ?? 0));
        $activo = isset($_POST['activo']) ? 1 : 0;

        if ($titulo === '') {
            al_error(422, 'El título es obligatorio.');
        }
        if (!in_array($tipo, ['ONCE', 'MONTHLY', 'YEARLY', 'INTERVAL'], true)) {
            al_error(422, 'Tipo de recordatorio inválido.');
        }
        if ($tipo === 'INTERVAL' && $intervaloDias <= 0) {
            al_error(422, 'El intervalo debe ser mayor a 0 días.');
        }

        if ($fechaInput === '') {
            al_error(422, 'La fecha base es obligatoria.');
        }
        $fechaInput = str_replace('T', ' ', $fechaInput);
        $ts = strtotime($fechaInput);
        if ($ts === false) {
            al_error(422, 'La fecha base no es válida.');
        }
        $fechaBase = date('Y-m-d H:i:s', $ts);

        if ($id > 0) {
            $chk = $db->prepare("SELECT id FROM al_alertas WHERE id=? AND id_empresa=? LIMIT 1");
            $chk->bind_param('ii', $id, $empresaId);
            $chk->execute();
            $exists = $chk->get_result()->fetch_assoc();
            $chk->close();

            if (!$exists) {
                al_error(404, 'La alerta que intentas editar no existe.');
            }

            $sql = "UPDATE al_alertas
                    SET titulo=?, categoria=?, descripcion=?, tipo=?, intervalo_dias=?, fecha_base=?, anticipacion_dias=?, activo=?
                    WHERE id=? AND id_empresa=?
                    LIMIT 1";
            $st = $db->prepare($sql);
            $st->bind_param(
                'ssssissiii',
                $titulo,
                $categoria,
                $descripcion,
                $tipo,
                $intervaloDias,
                $fechaBase,
                $anticipacionDias,
                $activo,
                $id,
                $empresaId
            );
            $st->execute();
            $st->close();

            @$db->query("INSERT INTO al_alertas_log(id_alerta, evento, detalle) VALUES ({$id}, 'UPDATED', NULL)");
            al_ok(['id' => $id]);
        }

        $sql = "INSERT INTO al_alertas
                (id_empresa, titulo, categoria, descripcion, tipo, intervalo_dias, fecha_base, anticipacion_dias, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $st = $db->prepare($sql);
        $st->bind_param(
            'issssissi',
            $empresaId,
            $titulo,
            $categoria,
            $descripcion,
            $tipo,
            $intervaloDias,
            $fechaBase,
            $anticipacionDias,
            $activo
        );
        $st->execute();
        $newId = (int)$db->insert_id;
        $st->close();

        @$db->query("INSERT INTO al_alertas_log(id_alerta, evento, detalle) VALUES ({$newId}, 'CREATED', NULL)");
        al_ok(['id' => $newId]);
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $activo = ((int)($_POST['activo'] ?? 0) === 1) ? 1 : 0;
        if ($id <= 0) {
            al_error(400, 'ID de alerta inválido.');
        }

        $st = $db->prepare("UPDATE al_alertas SET activo=? WHERE id=? AND id_empresa=? LIMIT 1");
        $st->bind_param('iii', $activo, $id, $empresaId);
        $st->execute();
        $st->close();

        @$db->query("INSERT INTO al_alertas_log(id_alerta, evento, detalle) VALUES ({$id}, 'TOGGLED', NULL)");
        al_ok(['id' => $id, 'activo' => $activo]);
    }

    al_error(400, 'Acción no reconocida.');
} catch (mysqli_sql_exception $e) {
    al_error(500, 'Ocurrió un error interno al procesar alertas.');
} catch (Throwable $e) {
    al_error(500, 'Ocurrió un error no controlado al procesar alertas.');
}
