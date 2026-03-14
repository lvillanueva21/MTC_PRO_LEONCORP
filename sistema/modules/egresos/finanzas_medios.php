<?php
// modules/egresos/finanzas_medios.php
// Utilidades para resumen de ingresos/devoluciones por medio de pago.
// Se mantiene dentro del modulo egresos para facilitar evolucion funcional.

if (!function_exists('fin_normalize_token')) {
    function fin_normalize_token(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtoupper')) {
            $value = mb_strtoupper($value, 'UTF-8');
        } else {
            $value = strtoupper($value);
        }

        if (function_exists('iconv')) {
            $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($tmp) && $tmp !== '') {
                $value = $tmp;
            }
        }

        $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        return $value;
    }
}

if (!function_exists('fin_medio_key_from_name')) {
    function fin_medio_key_from_name(string $name): string
    {
        $n = fin_normalize_token($name);
        if ($n === '') {
            return '';
        }

        if (strpos($n, 'EFECTIVO') !== false || strpos($n, 'CASH') !== false) {
            return 'EFECTIVO';
        }
        if (strpos($n, 'YAPE') !== false) {
            return 'YAPE';
        }
        if (strpos($n, 'PLIN') !== false) {
            return 'PLIN';
        }
        if (strpos($n, 'TRANSFER') !== false || strpos($n, 'TRASNFER') !== false) {
            return 'TRANSFERENCIA';
        }
        return '';
    }
}

if (!function_exists('fin_canonical_rows')) {
    function fin_canonical_rows(): array
    {
        return [
            'EFECTIVO' => [
                'key' => 'EFECTIVO',
                'label' => 'Efectivo',
                'medio_id' => 0,
                'ingresos' => 0.0,
                'devoluciones' => 0.0,
                'monto_neto' => 0.0,
                'egresos_activos' => 0.0,
                'saldo_disponible' => 0.0,
            ],
            'YAPE' => [
                'key' => 'YAPE',
                'label' => 'Yape',
                'medio_id' => 0,
                'ingresos' => 0.0,
                'devoluciones' => 0.0,
                'monto_neto' => 0.0,
                'egresos_activos' => 0.0,
                'saldo_disponible' => 0.0,
            ],
            'PLIN' => [
                'key' => 'PLIN',
                'label' => 'Plin',
                'medio_id' => 0,
                'ingresos' => 0.0,
                'devoluciones' => 0.0,
                'monto_neto' => 0.0,
                'egresos_activos' => 0.0,
                'saldo_disponible' => 0.0,
            ],
            'TRANSFERENCIA' => [
                'key' => 'TRANSFERENCIA',
                'label' => 'Transferencia',
                'medio_id' => 0,
                'ingresos' => 0.0,
                'devoluciones' => 0.0,
                'monto_neto' => 0.0,
                'egresos_activos' => 0.0,
                'saldo_disponible' => 0.0,
            ],
        ];
    }
}

if (!function_exists('fin_fuente_keys')) {
    function fin_fuente_keys(): array
    {
        return ['EFECTIVO', 'YAPE', 'PLIN', 'TRANSFERENCIA'];
    }
}

if (!function_exists('fin_catalogo_medios_pago')) {
    function fin_catalogo_medios_pago(mysqli $db): array
    {
        $out = [];
        $rs = $db->query("SELECT id, nombre, activo FROM pos_medios_pago ORDER BY id");
        foreach (($rs->fetch_all(MYSQLI_ASSOC) ?: []) as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $nombre = (string)($r['nombre'] ?? '');
            $out[$id] = [
                'id' => $id,
                'nombre' => $nombre,
                'activo' => ((int)($r['activo'] ?? 0) === 1),
                'key' => fin_medio_key_from_name($nombre),
            ];
        }
        return $out;
    }
}

if (!function_exists('fin_source_key_from_input')) {
    function fin_source_key_from_input(string $raw): string
    {
        $n = fin_normalize_token($raw);
        if ($n === '') {
            return '';
        }
        foreach (fin_fuente_keys() as $k) {
            if ($n === $k) {
                return $k;
            }
        }
        return fin_medio_key_from_name($n);
    }
}

if (!function_exists('fin_table_exists')) {
    function fin_table_exists(mysqli $db, string $name): bool
    {
        $esc = $db->real_escape_string($name);
        $rs = $db->query("SHOW TABLES LIKE '{$esc}'");
        return $rs && $rs->num_rows > 0;
    }
}

if (!function_exists('fin_disponible_por_fuente_diaria')) {
    function fin_disponible_por_fuente_diaria(mysqli $db, int $empId, int $cajaDiariaId): array
    {
        $rows = fin_canonical_rows();
        $catalogo = fin_catalogo_medios_pago($db);

        foreach ($catalogo as $medio) {
            $key = (string)($medio['key'] ?? '');
            if ($key === '' || !isset($rows[$key])) {
                continue;
            }
            if ((int)$rows[$key]['medio_id'] === 0) {
                $rows[$key]['medio_id'] = (int)$medio['id'];
            }
        }

        $sqlIngresos = "SELECT
                          a.medio_id,
                          mp.nombre AS medio_nombre,
                          COALESCE(SUM(apl.monto_aplicado),0) AS monto
                        FROM pos_abonos a
                        INNER JOIN pos_abono_aplicaciones apl ON apl.abono_id = a.id
                        LEFT JOIN pos_medios_pago mp ON mp.id = a.medio_id
                        WHERE a.id_empresa=? AND a.caja_diaria_id=?
                        GROUP BY a.medio_id, mp.nombre";
        $stIn = $db->prepare($sqlIngresos);
        $stIn->bind_param('ii', $empId, $cajaDiariaId);
        $stIn->execute();
        $ing = $stIn->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $stIn->close();

        foreach ($ing as $r) {
            $medioId = (int)($r['medio_id'] ?? 0);
            $medioNom = (string)($r['medio_nombre'] ?? '');
            $key = '';
            if ($medioId > 0 && isset($catalogo[$medioId])) {
                $key = (string)($catalogo[$medioId]['key'] ?? '');
            }
            if ($key === '') {
                $key = fin_medio_key_from_name($medioNom);
            }
            if ($key === '' || !isset($rows[$key])) {
                continue;
            }
            if ((int)$rows[$key]['medio_id'] === 0 && $medioId > 0) {
                $rows[$key]['medio_id'] = $medioId;
            }
            $rows[$key]['ingresos'] = round((float)$rows[$key]['ingresos'] + (float)($r['monto'] ?? 0), 2);
        }

        $sqlDev = "SELECT
                     dv.medio_id,
                     mp.nombre AS medio_nombre,
                     COALESCE(SUM(dv.monto_devuelto),0) AS monto
                   FROM pos_devoluciones dv
                   LEFT JOIN pos_medios_pago mp ON mp.id = dv.medio_id
                   WHERE dv.id_empresa=? AND dv.caja_diaria_id=?
                   GROUP BY dv.medio_id, mp.nombre";
        $stDev = $db->prepare($sqlDev);
        $stDev->bind_param('ii', $empId, $cajaDiariaId);
        $stDev->execute();
        $dev = $stDev->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
        $stDev->close();

        foreach ($dev as $r) {
            $medioId = (int)($r['medio_id'] ?? 0);
            $medioNom = (string)($r['medio_nombre'] ?? '');
            $key = '';
            if ($medioId > 0 && isset($catalogo[$medioId])) {
                $key = (string)($catalogo[$medioId]['key'] ?? '');
            }
            if ($key === '') {
                $key = fin_medio_key_from_name($medioNom);
            }
            if ($key === '' || !isset($rows[$key])) {
                continue;
            }
            if ((int)$rows[$key]['medio_id'] === 0 && $medioId > 0) {
                $rows[$key]['medio_id'] = $medioId;
            }
            $rows[$key]['devoluciones'] = round((float)$rows[$key]['devoluciones'] + (float)($r['monto'] ?? 0), 2);
        }

        $egresosByKey = [];
        if (fin_table_exists($db, 'egr_egreso_fuentes')) {
            $sqlEgr = "SELECT
                         f.fuente_key,
                         f.medio_id,
                         mp.nombre AS medio_nombre,
                         COALESCE(SUM(f.monto),0) AS monto
                       FROM egr_egreso_fuentes f
                       INNER JOIN egr_egresos e
                         ON e.id = f.id_egreso
                        AND e.id_empresa = f.id_empresa
                       LEFT JOIN pos_medios_pago mp ON mp.id = f.medio_id
                       WHERE f.id_empresa=? AND f.id_caja_diaria=? AND e.estado='ACTIVO'
                       GROUP BY f.fuente_key, f.medio_id, mp.nombre";
            $stEgr = $db->prepare($sqlEgr);
            $stEgr->bind_param('ii', $empId, $cajaDiariaId);
            $stEgr->execute();
            $egr = $stEgr->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
            $stEgr->close();

            foreach ($egr as $r) {
                $medioId = (int)($r['medio_id'] ?? 0);
                $key = fin_source_key_from_input((string)($r['fuente_key'] ?? ''));
                if ($key === '' && $medioId > 0 && isset($catalogo[$medioId])) {
                    $key = fin_source_key_from_input((string)($catalogo[$medioId]['key'] ?? ''));
                }
                if ($key === '') {
                    $key = fin_medio_key_from_name((string)($r['medio_nombre'] ?? ''));
                }
                if ($key === '' || !isset($rows[$key])) {
                    continue;
                }
                $egresosByKey[$key] = round((float)($egresosByKey[$key] ?? 0) + (float)($r['monto'] ?? 0), 2);
                if ((int)$rows[$key]['medio_id'] === 0 && $medioId > 0) {
                    $rows[$key]['medio_id'] = $medioId;
                }
            }
        }

        $totIngresos = 0.0;
        $totDevoluciones = 0.0;
        $totEgresosActivos = 0.0;
        foreach ($rows as $k => $row) {
            $ingreso = (float)($row['ingresos'] ?? 0);
            $devol = (float)($row['devoluciones'] ?? 0);
            $neto = $ingreso - $devol;
            $egresoActivo = (float)($egresosByKey[$k] ?? 0);
            $disponible = $neto - $egresoActivo;
            $rows[$k]['ingresos'] = round($ingreso, 2);
            $rows[$k]['devoluciones'] = round($devol, 2);
            $rows[$k]['monto_neto'] = round($neto, 2);
            $rows[$k]['egresos_activos'] = round($egresoActivo, 2);
            $rows[$k]['saldo_disponible'] = round($disponible, 2);
            $totIngresos += $ingreso;
            $totDevoluciones += $devol;
            $totEgresosActivos += $egresoActivo;
        }

        return [
            'rows' => array_values($rows),
            'totales' => [
                'ingresos' => round($totIngresos, 2),
                'devoluciones' => round($totDevoluciones, 2),
                'monto_neto' => round($totIngresos - $totDevoluciones, 2),
                'egresos_activos' => round($totEgresosActivos, 2),
                'saldo_disponible' => round(($totIngresos - $totDevoluciones) - $totEgresosActivos, 2),
            ],
        ];
    }
}

if (!function_exists('fin_totales_ingreso_por_medio_diaria')) {
    function fin_totales_ingreso_por_medio_diaria(mysqli $db, int $empId, int $cajaDiariaId): array
    {
        $full = fin_disponible_por_fuente_diaria($db, $empId, $cajaDiariaId);
        return [
            'rows' => $full['rows'] ?? [],
            'totales' => [
                'ingresos' => (float)($full['totales']['ingresos'] ?? 0),
                'devoluciones' => (float)($full['totales']['devoluciones'] ?? 0),
                'monto_neto' => (float)($full['totales']['monto_neto'] ?? 0),
            ],
        ];
    }
}
