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
    
    if (!function_exists('egm_cargar_cajas_fuente_map')) {
    function egm_cargar_cajas_fuente_map(mysqli $db, int $empId, array $ids, bool $forUpdate = false): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, static function ($id) {
            return $id > 0;
        }));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
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
                WHERE cd.id_empresa=? AND cd.id IN ({$placeholders})
                ORDER BY cd.fecha ASC, cd.id ASC";
        if ($forUpdate) {
            $sql .= " FOR UPDATE";
        }

        $types = 'i' . str_repeat('i', count($ids));
        $params = array_merge([$empId], $ids);

        $st = $db->prepare($sql);
        egm_stmt_bind($st, $types, $params);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $st->close();

        $out = [];
        foreach ($rows as $row) {
            $hydrated = egm_hidratar_caja_row($db, $empId, $row);
            $out[(int)$hydrated['id']] = $hydrated;
        }

        return $out;
    }
}

if (!function_exists('egm_validar_distribucion_multicaja')) {
    function egm_validar_distribucion_multicaja(mysqli $db, int $empId, array $fuentesDetalle, int $cajaRegistroId, string $tipoEgreso = 'NORMAL'): array
    {
        $tipo = strtoupper(trim($tipoEgreso)) === 'MULTICAJA' ? 'MULTICAJA' : 'NORMAL';
        $items = [];
        $cajaIds = [];

        foreach ($fuentesDetalle as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = strtoupper(trim((string)($item['key'] ?? '')));
            $monto = round((float)($item['monto'] ?? 0), 2);
            if ($key === '' || $monto <= 0) {
                continue;
            }

            $fuenteCajaId = (int)($item['id_caja_diaria'] ?? 0);
            if ($fuenteCajaId <= 0) {
                $fuenteCajaId = $cajaRegistroId;
            }

            if ($tipo === 'NORMAL' && $fuenteCajaId !== $cajaRegistroId) {
                return [
                    'ok' => false,
                    'error' => 'El egreso normal solo puede usar dinero de la caja diaria abierta actual.',
                    'tipo_egreso' => $tipo,
                    'caja_registro_actual' => $cajaRegistroId,
                    'caja_fuente_detectada' => $fuenteCajaId,
                ];
            }

            $items[] = [
                'id_caja_diaria' => $fuenteCajaId,
                'key' => $key,
                'monto' => $monto,
            ];
            $cajaIds[$fuenteCajaId] = $fuenteCajaId;
        }

        if ($items === []) {
            return [
                'ok' => false,
                'error' => 'La distribucion por fuente es obligatoria. Selecciona al menos una fuente.',
                'tipo_egreso' => $tipo,
            ];
        }

        $cajasMap = egm_cargar_cajas_fuente_map($db, $empId, array_values($cajaIds), true);
        if (count($cajasMap) !== count($cajaIds)) {
            return [
                'ok' => false,
                'error' => 'Hay cajas fuente invalidas o que no pertenecen a la empresa actual.',
                'tipo_egreso' => $tipo,
                'cajas_solicitadas' => array_values($cajaIds),
                'cajas_validas' => array_keys($cajasMap),
            ];
        }

        $porCajaKey = [];
        foreach ($cajasMap as $cajaId => $cajaRow) {
            $byKey = [];
            foreach ((array)($cajaRow['por_medio'] ?? []) as $medioRow) {
                $medioKey = strtoupper(trim((string)($medioRow['key'] ?? '')));
                if ($medioKey === '') {
                    continue;
                }
                $byKey[$medioKey] = $medioRow;
            }
            $porCajaKey[$cajaId] = $byKey;
        }

        $totalesPorCaja = [];
        foreach ($items as $idx => $item) {
            $cajaId = (int)$item['id_caja_diaria'];
            $key = (string)$item['key'];
            $monto = round((float)$item['monto'], 2);
            $cajaRow = $cajasMap[$cajaId] ?? null;
            if (!$cajaRow) {
                return [
                    'ok' => false,
                    'error' => 'No se pudo validar una de las cajas fuente solicitadas.',
                    'tipo_egreso' => $tipo,
                    'caja_fuente_detectada' => $cajaId,
                ];
            }

            $medioRow = $porCajaKey[$cajaId][$key] ?? [];
            $disponibleFuente = round((float)($medioRow['saldo_disponible'] ?? 0), 2);
            if ($monto > $disponibleFuente + 0.0001) {
                return [
                    'ok' => false,
                    'error' => 'El monto solicitado supera el disponible en la fuente ' . $key . ' de la caja ' . (string)($cajaRow['codigo'] ?? $cajaId) . '.',
                    'tipo_egreso' => $tipo,
                    'fuente_error' => [
                        'key' => $key,
                        'monto_solicitado' => $monto,
                        'disponible' => $disponibleFuente,
                        'caja_id' => $cajaId,
                        'caja_codigo' => (string)($cajaRow['codigo'] ?? ''),
                        'caja_fecha' => (string)($cajaRow['fecha'] ?? ''),
                    ],
                    'caja_error' => $cajaRow,
                ];
            }

            if (!isset($totalesPorCaja[$cajaId])) {
                $totalesPorCaja[$cajaId] = 0.0;
            }
            $totalesPorCaja[$cajaId] = round((float)$totalesPorCaja[$cajaId] + $monto, 2);

            $saldoCaja = round((float)(($cajaRow['saldo'] ?? [])['saldo_disponible'] ?? 0), 2);
            if ($totalesPorCaja[$cajaId] > $saldoCaja + 0.0001) {
                return [
                    'ok' => false,
                    'error' => 'La suma extraida de la caja ' . (string)($cajaRow['codigo'] ?? $cajaId) . ' supera su saldo disponible.',
                    'tipo_egreso' => $tipo,
                    'caja_error' => $cajaRow,
                    'monto_solicitado' => $totalesPorCaja[$cajaId],
                    'disponible' => $saldoCaja,
                ];
            }

            $items[$idx]['label'] = (string)((fin_canonical_rows()[$key]['label'] ?? $key));
            $items[$idx]['caja_codigo'] = (string)($cajaRow['codigo'] ?? '');
            $items[$idx]['caja_fecha'] = (string)($cajaRow['fecha'] ?? '');
        }

        return [
            'ok' => true,
            'tipo_egreso' => $tipo,
            'items' => $items,
            'cajas' => $cajasMap,
            'totales_por_caja' => $totalesPorCaja,
        ];
    }
}

if (!function_exists('egm_agrupar_fuentes_por_caja')) {
    function egm_agrupar_fuentes_por_caja(array $fuentes): array
    {
        $out = [];
        foreach ($fuentes as $src) {
            if (!is_array($src)) {
                continue;
            }
            $cajaId = (int)($src['id_caja_diaria'] ?? 0);
            $groupKey = (string)$cajaId;
            if (!isset($out[$groupKey])) {
                $out[$groupKey] = [
                    'id_caja_diaria' => $cajaId,
                    'caja_diaria_codigo' => (string)($src['caja_diaria_codigo'] ?? $src['caja_codigo'] ?? ''),
                    'caja_diaria_fecha' => (string)($src['caja_diaria_fecha'] ?? $src['caja_fecha'] ?? ''),
                    'total' => 0.0,
                    'rows' => [],
                ];
            }
            $monto = round((float)($src['monto'] ?? 0), 2);
            $out[$groupKey]['total'] = round((float)$out[$groupKey]['total'] + $monto, 2);
            $out[$groupKey]['rows'][] = $src;
        }

        return array_values($out);
    }
}
}
