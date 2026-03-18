<?php
// modules/egresos/multicaja_service.php

if (!function_exists('egm_parse_date')) {
    function egm_parse_date(?string $raw): ?string
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return null;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $raw);
        if (!$dt instanceof DateTime) {
            return null;
        }

        return $dt->format('Y-m-d') === $raw ? $raw : null;
    }
}

if (!function_exists('egm_stmt_bind')) {
    function egm_stmt_bind(mysqli_stmt $st, string $types, array $params): void
    {
        $bind = [$types];
        foreach ($params as $k => $value) {
            $bind[] = $params[$k];
        }

        $refs = [];
        foreach ($bind as $k => &$value) {
            $refs[$k] = &$value;
        }

        call_user_func_array([$st, 'bind_param'], $refs);
    }
}

if (!function_exists('egm_resumen_fuentes_diaria')) {
    function egm_resumen_fuentes_diaria(mysqli $db, int $empId, int $cajaDiariaId): array
    {
        $full = fin_disponible_por_fuente_diaria($db, $empId, $cajaDiariaId);

        return [
            'por_medio' => array_values($full['rows'] ?? []),
            'saldo' => [
                'ingresos' => round((float)($full['totales']['ingresos'] ?? 0), 2),
                'devoluciones' => round((float)($full['totales']['devoluciones'] ?? 0), 2),
                'monto_neto' => round((float)($full['totales']['monto_neto'] ?? 0), 2),
                'egresos_activos' => round((float)($full['totales']['egresos_activos'] ?? 0), 2),
                'saldo_disponible' => round((float)($full['totales']['saldo_disponible'] ?? 0), 2),
            ],
        ];
    }
}

if (!function_exists('egm_hidratar_caja_row')) {
    function egm_hidratar_caja_row(mysqli $db, int $empId, array $row): array
    {
        $idCaja = (int)($row['id'] ?? 0);
        $resumen = egm_resumen_fuentes_diaria($db, $empId, $idCaja);

        return [
            'id' => $idCaja,
            'id_caja_mensual' => (int)($row['id_caja_mensual'] ?? 0),
            'codigo' => (string)($row['codigo'] ?? ''),
            'fecha' => (string)($row['fecha'] ?? ''),
            'estado' => (string)($row['estado'] ?? ''),
            'caja_mensual' => [
                'codigo' => (string)($row['caja_mensual_codigo'] ?? ''),
                'anio' => (int)($row['caja_mensual_anio'] ?? 0),
                'mes' => (int)($row['caja_mensual_mes'] ?? 0),
                'estado' => (string)($row['caja_mensual_estado'] ?? ''),
            ],
            'saldo' => $resumen['saldo'],
            'por_medio' => $resumen['por_medio'],
            'seleccionable' => ((float)($resumen['saldo']['saldo_disponible'] ?? 0) > 0),
        ];
    }
}

if (!function_exists('egm_listar_cajas_fuente')) {
    function egm_listar_cajas_fuente(mysqli $db, int $empId, array $filters = []): array
    {
        $q = trim((string)($filters['q'] ?? ''));
        $fecha = egm_parse_date($filters['fecha'] ?? null);
        $desde = egm_parse_date($filters['desde'] ?? null);
        $hasta = egm_parse_date($filters['hasta'] ?? null);
        $limit = (int)($filters['limit'] ?? 40);
        $limit = max(1, min(80, $limit));

        $sql = "SELECT
                    cd.id,
                    cd.id_caja_mensual,
                    cd.codigo,
                    cd.fecha,
                    cd.estado,
                    cm.codigo AS caja_mensual_codigo,
                    cm.anio AS caja_mensual_anio,
                    cm.mes AS caja_mensual_mes,
                    cm.estado AS caja_mensual_estado
                FROM mod_caja_diaria cd
                INNER JOIN mod_caja_mensual cm ON cm.id = cd.id_caja_mensual
                WHERE cd.id_empresa=?";

        $types = 'i';
        $params = [$empId];

        if ($fecha !== null) {
            $sql .= " AND cd.fecha=?";
            $types .= 's';
            $params[] = $fecha;
        } else {
            if ($desde !== null) {
                $sql .= " AND cd.fecha>=?";
                $types .= 's';
                $params[] = $desde;
            }
            if ($hasta !== null) {
                $sql .= " AND cd.fecha<=?";
                $types .= 's';
                $params[] = $hasta;
            }
        }

        if ($q !== '') {
            $sql .= " AND (cd.codigo LIKE ? OR cm.codigo LIKE ?)";
            $types .= 'ss';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY cd.fecha DESC, cd.id DESC LIMIT {$limit}";

        $st = $db->prepare($sql);
        egm_stmt_bind($st, $types, $params);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $st->close();

        $out = [];
        foreach ($rows as $row) {
            $out[] = egm_hidratar_caja_row($db, $empId, $row);
        }

        return $out;
    }
}

if (!function_exists('egm_detalle_caja_fuente')) {
    function egm_detalle_caja_fuente(mysqli $db, int $empId, int $cajaDiariaId): ?array
    {
        if ($cajaDiariaId <= 0) {
            return null;
        }

        $sql = "SELECT
                    cd.id,
                    cd.id_caja_mensual,
                    cd.codigo,
                    cd.fecha,
                    cd.estado,
                    cm.codigo AS caja_mensual_codigo,
                    cm.anio AS caja_mensual_anio,
                    cm.mes AS caja_mensual_mes,
                    cm.estado AS caja_mensual_estado
                FROM mod_caja_diaria cd
                INNER JOIN mod_caja_mensual cm ON cm.id = cd.id_caja_mensual
                WHERE cd.id_empresa=? AND cd.id=?
                LIMIT 1";

        $st = $db->prepare($sql);
        $st->bind_param('ii', $empId, $cajaDiariaId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$row) {
            return null;
        }

        return egm_hidratar_caja_row($db, $empId, $row);
    }
}
