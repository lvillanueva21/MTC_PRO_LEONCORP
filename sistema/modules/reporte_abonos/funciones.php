<?php
// modules/reporte_abonos/funciones.php
// Lógica de búsqueda y resumen para la central de abonos (rastreo de ingresos).

/**
 * Lista medios de pago activos.
 */
if (!function_exists('listar_medios_pago')) {
    function listar_medios_pago(mysqli $db): array {
        $sql = "SELECT id, nombre
                FROM pos_medios_pago
                WHERE activo = 1
                ORDER BY nombre";
        $st = $db->prepare($sql);
        $st->execute();
        $res = $st->get_result();
        return $res->fetch_all(MYSQLI_ASSOC) ?: [];
    }
}

/**
 * Lista usuarios que han registrado abonos para la empresa (para combo de filtro).
 */
if (!function_exists('listar_usuarios_abonos')) {
    function listar_usuarios_abonos(mysqli $db, int $empresaId): array {
        $sql = "SELECT DISTINCT u.id, u.usuario, u.nombres, u.apellidos
                FROM pos_abonos a
                JOIN mtp_usuarios u ON u.id = a.creado_por
                WHERE a.id_empresa = ?
                ORDER BY u.nombres, u.apellidos, u.usuario";
        $st = $db->prepare($sql);
        $st->bind_param('i', $empresaId);
        $st->execute();
        $res = $st->get_result();
        return $res->fetch_all(MYSQLI_ASSOC) ?: [];
    }
}

/**
 * Busca abonos de la empresa con filtros + paginación y adjunta:
 *  - aplicaciones[] por abono (ventas vinculadas y devoluciones por aplicación)
 *  - totales por abono (monto_aplicado_total, monto_devuelto_total, num_ventas, ticket_principal)
 * Además calcula totales del periodo filtrado (abonos / devoluciones / neto).
 */
if (!function_exists('buscar_abonos_con_detalles')) {
    function buscar_abonos_con_detalles(mysqli $db, int $empresaId, array $opts = []): array {
        // Paginación
        $page = isset($opts['pagina']) ? (int)$opts['pagina'] : 1;
        if ($page < 1) $page = 1;

        $perPage  = isset($opts['por_pagina']) ? (int)$opts['por_pagina'] : 10;
        $allowed  = [10, 20, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        // Filtros
        $q          = isset($opts['q']) ? trim((string)$opts['q']) : '';
        $medioId    = isset($opts['medio_id']) ? (int)$opts['medio_id'] : 0;
        $usuarioId  = isset($opts['usuario_id']) ? (int)$opts['usuario_id'] : 0;
        $aplEstado  = isset($opts['aplicacion_estado']) ? (string)$opts['aplicacion_estado'] : '';
        $devEstado  = isset($opts['tiene_devolucion']) ? (string)$opts['tiene_devolucion'] : '';
        $fdesde     = isset($opts['fdesde']) ? (string)$opts['fdesde'] : '';
        $fhasta     = isset($opts['fhasta']) ? (string)$opts['fhasta'] : '';

        $where = ['a.id_empresa = ?'];
        $types = 'i';
        $vals  = [$empresaId];

        // Búsqueda por cliente / doc / ticket / referencia / observación
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(c.nombre LIKE ?
                         OR c.doc_numero LIKE ?
                         OR a.referencia LIKE ?
                         OR a.observacion LIKE ?
                         OR EXISTS (
                             SELECT 1
                             FROM pos_abono_aplicaciones aa
                             JOIN pos_ventas v ON v.id = aa.venta_id
                             WHERE aa.abono_id = a.id
                               AND CONCAT(v.serie, "-", LPAD(v.numero, 4, "0")) LIKE ?
                         ))';
            $types .= 'sssss';
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;
        }

        // Medio de pago
        if ($medioId > 0) {
            $where[] = 'a.medio_id = ?';
            $types  .= 'i';
            $vals[]  = $medioId;
        }

        // Usuario que registró
        if ($usuarioId > 0) {
            $where[] = 'a.creado_por = ?';
            $types  .= 'i';
            $vals[]  = $usuarioId;
        }

        // Fecha de abono
        $fdesdeDT = '';
        $fhastaDT = '';
        if ($fdesde !== '') {
            $fdesdeDT = $fdesde . ' 00:00:00';
        }
        if ($fhasta !== '') {
            $fhastaDT = $fhasta . ' 23:59:59';
        }

        if ($fdesdeDT !== '' && $fhastaDT !== '') {
            $where[] = 'a.fecha BETWEEN ? AND ?';
            $types  .= 'ss';
            $vals[]  = $fdesdeDT;
            $vals[]  = $fhastaDT;
        } elseif ($fdesdeDT !== '') {
            $where[] = 'a.fecha >= ?';
            $types  .= 's';
            $vals[]  = $fdesdeDT;
        } elseif ($fhastaDT !== '') {
            $where[] = 'a.fecha <= ?';
            $types  .= 's';
            $vals[]  = $fhastaDT;
        }

        // Estado de aplicación del abono (sin ventas / parcial / completo)
        if ($aplEstado === 'sin_venta') {
            $where[] = 'NOT EXISTS (
                SELECT 1
                FROM pos_abono_aplicaciones aa
                WHERE aa.abono_id = a.id
            )';
        } elseif ($aplEstado === 'parcial') {
            $where[] = 'EXISTS (
                SELECT 1
                FROM pos_abono_aplicaciones aa
                WHERE aa.abono_id = a.id
            )
            AND COALESCE((
                SELECT SUM(aa2.monto_aplicado)
                FROM pos_abono_aplicaciones aa2
                WHERE aa2.abono_id = a.id
            ), 0) < a.monto - 0.01';
        } elseif ($aplEstado === 'completo') {
            $where[] = 'COALESCE((
                SELECT SUM(aa2.monto_aplicado)
                FROM pos_abono_aplicaciones aa2
                WHERE aa2.abono_id = a.id
            ), 0) >= a.monto - 0.01';
        }

        // Filtro de devoluciones (con / sin)
        if ($devEstado === 'con_dev') {
            $where[] = 'EXISTS (
                SELECT 1
                FROM pos_devoluciones d2
                JOIN pos_abono_aplicaciones aa2 ON aa2.id = d2.abono_aplicacion_id
                WHERE aa2.abono_id = a.id
            )';
        } elseif ($devEstado === 'sin_dev') {
            $where[] = 'NOT EXISTS (
                SELECT 1
                FROM pos_devoluciones d2
                JOIN pos_abono_aplicaciones aa2 ON aa2.id = d2.abono_aplicacion_id
                WHERE aa2.abono_id = a.id
            )';
        }

        $whereSql = implode(' AND ', $where);

        // ---- Totales del periodo (abonos) ----
        $sqlCount = "SELECT
                        COUNT(*) AS total,
                        COALESCE(SUM(a.monto), 0) AS total_monto
                     FROM pos_abonos a
                     LEFT JOIN pos_clientes c ON c.id = a.cliente_id
                     WHERE $whereSql";
        $stCount = $db->prepare($sqlCount);
        $paramsCnt = array_merge([$types], $vals);
        $refsCnt   = [];
        foreach ($paramsCnt as $k => $v) {
            $refsCnt[$k] = &$paramsCnt[$k];
        }
        call_user_func_array([$stCount, 'bind_param'], $refsCnt);
        $stCount->execute();
        $rowCnt = $stCount->get_result()->fetch_assoc();
        $total      = (int)($rowCnt['total'] ?? 0);
        $totalMonto = (float)($rowCnt['total_monto'] ?? 0.0);

        // Totales de devoluciones asociadas al mismo conjunto de abonos
        $totalDevuelto = 0.0;
        if ($total > 0) {
            $sqlDev = "SELECT COALESCE(SUM(d.monto_devuelto), 0) AS total_devuelto
                       FROM pos_devoluciones d
                       JOIN pos_abono_aplicaciones aa ON aa.id = d.abono_aplicacion_id
                       JOIN pos_abonos a ON a.id = aa.abono_id
                       LEFT JOIN pos_clientes c ON c.id = a.cliente_id
                       WHERE $whereSql";
            $stDev = $db->prepare($sqlDev);
            $paramsDev = array_merge([$types], $vals);
            $refsDev   = [];
            foreach ($paramsDev as $k => $v) {
                $refsDev[$k] = &$paramsDev[$k];
            }
            call_user_func_array([$stDev, 'bind_param'], $refsDev);
            $stDev->execute();
            $rowDev = $stDev->get_result()->fetch_assoc();
            $totalDevuelto = (float)($rowDev['total_devuelto'] ?? 0.0);
        }

        if ($total === 0) {
            return [
                'rows'        => [],
                'total'       => 0,
                'page'        => 1,
                'per_page'    => $perPage,
                'total_pages' => 0,
                'from'        => 0,
                'to'          => 0,
                'sum_monto'   => 0.0,
                'sum_devuelto'=> 0.0,
                'sum_neto'    => 0.0,
            ];
        }

        $totalPages = (int)ceil($total / $perPage);
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        // ---- Consulta principal (página actual) ----
        $sql = "SELECT
                    a.id,
                    a.fecha AS fecha_abono,
                    a.monto,
                    a.referencia,
                    a.observacion,
                    a.caja_diaria_id,
                    a.medio_id,
                    a.cliente_id,
                    a.creado_por,
                    c.nombre AS cliente_nombre,
                    c.doc_tipo AS cliente_doc_tipo,
                    c.doc_numero AS cliente_doc_numero,
                    c.telefono AS cliente_telefono,
                    mp.nombre AS medio_nombre,
                    u.usuario AS usuario_username,
                    u.nombres AS usuario_nombres,
                    u.apellidos AS usuario_apellidos,
                    cd.codigo AS caja_codigo,
                    cd.fecha  AS caja_fecha
                FROM pos_abonos a
                LEFT JOIN pos_clientes   c  ON c.id  = a.cliente_id
                LEFT JOIN pos_medios_pago mp ON mp.id = a.medio_id
                LEFT JOIN mtp_usuarios   u  ON u.id  = a.creado_por
                LEFT JOIN mod_caja_diaria cd ON cd.id = a.caja_diaria_id
                WHERE $whereSql
                ORDER BY a.fecha DESC, a.id DESC
                LIMIT ? OFFSET ?";

        $st = $db->prepare($sql);
        $typesMain  = $types . 'ii';
        $valsMain   = array_merge($vals, [$perPage, $offset]);
        $paramsMain = array_merge([$typesMain], $valsMain);
        $refsMain   = [];
        foreach ($paramsMain as $k => $v) {
            $refsMain[$k] = &$paramsMain[$k];
        }
        call_user_func_array([$st, 'bind_param'], $refsMain);
        $st->execute();
        $res    = $st->get_result();
        $abonos = $res->fetch_all(MYSQLI_ASSOC) ?: [];

        if (!$abonos) {
            return [
                'rows'        => [],
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $totalPages,
                'from'        => 0,
                'to'          => 0,
                'sum_monto'   => $totalMonto,
                'sum_devuelto'=> $totalDevuelto,
                'sum_neto'    => $totalMonto - $totalDevuelto,
            ];
        }

        // ---- Aplicaciones a ventas para los abonos de esta página ----
        $ids = [];
        foreach ($abonos as $r) {
            $ids[] = (int)$r['id'];
        }
        $ids = array_values(array_unique($ids));

        $appsByAbono = [];
        if ($ids) {
            $in       = implode(',', array_fill(0, count($ids), '?'));
            $typesIds = str_repeat('i', count($ids));

            $sqlA = "SELECT
                        aa.id AS aplicacion_id,
                        aa.abono_id,
                        aa.venta_id,
                        aa.monto_aplicado,
                        aa.aplicado_en,
                        v.serie,
                        v.numero,
                        v.fecha_emision,
                        v.estado AS venta_estado,
                        v.total  AS venta_total
                     FROM pos_abono_aplicaciones aa
                     JOIN pos_ventas v ON v.id = aa.venta_id
                     WHERE aa.abono_id IN ($in)
                     ORDER BY aa.abono_id, aa.id";
            $stA = $db->prepare($sqlA);
            $paramsA = array_merge([$typesIds], $ids);
            $refsA   = [];
            foreach ($paramsA as $k => $v) {
                $refsA[$k] = &$paramsA[$k];
            }
            call_user_func_array([$stA, 'bind_param'], $refsA);
            $stA->execute();
            $rsA          = $stA->get_result();
            $aplicaciones = $rsA->fetch_all(MYSQLI_ASSOC) ?: [];

            // Mapa de devoluciones por aplicacion_id
            $devByAplicacion = [];
            if ($aplicaciones) {
                $appIds = [];
                foreach ($aplicaciones as $a) {
                    $appIds[] = (int)$a['aplicacion_id'];
                }
                $appIds = array_values(array_unique($appIds));

                if ($appIds) {
                    $inApp   = implode(',', array_fill(0, count($appIds), '?'));
                    $typesAp = str_repeat('i', count($appIds));

                    $sqlD = "SELECT d.abono_aplicacion_id, SUM(d.monto_devuelto) AS devuelto
                             FROM pos_devoluciones d
                             WHERE d.abono_aplicacion_id IN ($inApp)
                             GROUP BY d.abono_aplicacion_id";
                    $stD = $db->prepare($sqlD);
                    $paramsD = array_merge([$typesAp], $appIds);
                    $refsD   = [];
                    foreach ($paramsD as $k => $v) {
                        $refsD[$k] = &$paramsD[$k];
                    }
                    call_user_func_array([$stD, 'bind_param'], $refsD);
                    $stD->execute();
                    $rsD = $stD->get_result();
                    while ($d = $rsD->fetch_assoc()) {
                        $devByAplicacion[(int)$d['abono_aplicacion_id']] = (float)$d['devuelto'];
                    }
                }
            }

            // Adjuntar devuelto_monto a cada aplicación y agrupar por abono
            foreach ($aplicaciones as &$a) {
                $aplId = (int)$a['aplicacion_id'];
                $a['devuelto_monto'] = isset($devByAplicacion[$aplId]) ? (float)$devByAplicacion[$aplId] : 0.0;
            }
            unset($a);

            foreach ($aplicaciones as $a) {
                $aid = (int)$a['abono_id'];
                if (!isset($appsByAbono[$aid])) {
                    $appsByAbono[$aid] = [];
                }
                $appsByAbono[$aid][] = $a;
            }
        }

        // Adjuntar arrays y totales por abono
        foreach ($abonos as &$r) {
            $id   = (int)$r['id'];
            $apls = isset($appsByAbono[$id]) ? $appsByAbono[$id] : [];

            $sumApl = 0.0;
            $sumDev = 0.0;
            $ventaIds = [];
            foreach ($apls as $a) {
                $sumApl += (float)($a['monto_aplicado'] ?? 0);
                $sumDev += (float)($a['devuelto_monto'] ?? 0);
                $ventaIds[(int)$a['venta_id']] = true;
            }

            $r['aplicaciones']          = $apls;
            $r['monto_aplicado_total']  = $sumApl;
            $r['monto_devuelto_total']  = $sumDev;
            $r['num_ventas']            = count($ventaIds);

            if ($apls) {
                $prim = $apls[0];
                $r['ticket_principal']      = ticket_string($prim['serie'] ?? '', (int)($prim['numero'] ?? 0));
                $r['venta_principal_fecha'] = $prim['fecha_emision'] ?? null;
            } else {
                $r['ticket_principal']      = '';
                $r['venta_principal_fecha'] = null;
            }
        }
        unset($r);

        // Rango mostrado
        $from = ($page - 1) * $perPage + 1;
        $to   = $from + count($abonos) - 1;
        if ($from > $total) $from = $total;
        if ($to > $total)   $to   = $total;

        return [
            'rows'        => $abonos,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'from'        => $from,
            'to'          => $to,
            'sum_monto'   => $totalMonto,
            'sum_devuelto'=> $totalDevuelto,
            'sum_neto'    => $totalMonto - $totalDevuelto,
        ];
    }
}

/**
 * Resumen de caja diaria y mensual actual para la empresa:
 *  - caja diaria abierta más reciente (si existe) + abonos/devoluciones/neto
 *  - caja mensual abierta más reciente (si existe) + abonos/devoluciones/neto
 */
if (!function_exists('obtener_resumen_caja_abonos')) {
    function obtener_resumen_caja_abonos(mysqli $db, int $empresaId): array {
        $res = [
            'diaria'  => null,
            'mensual' => null,
        ];

        // Caja mensual abierta más reciente
        $sqlCM = "SELECT *
                  FROM mod_caja_mensual
                  WHERE id_empresa = ? AND estado = 'abierta'
                  ORDER BY periodo DESC, id DESC
                  LIMIT 1";
        $stCM = $db->prepare($sqlCM);
        $stCM->bind_param('i', $empresaId);
        $stCM->execute();
        $cmRow = $stCM->get_result()->fetch_assoc();

        if ($cmRow) {
            $cmId = (int)$cmRow['id'];

            // Abonos del mes (todas las cajas diarias ligadas)
            $sqlAbMes = "SELECT COALESCE(SUM(a.monto), 0) AS total_abonos
                         FROM pos_abonos a
                         JOIN mod_caja_diaria cd ON cd.id = a.caja_diaria_id
                         WHERE cd.id_caja_mensual = ?";
            $stAbMes = $db->prepare($sqlAbMes);
            $stAbMes->bind_param('i', $cmId);
            $stAbMes->execute();
            $rowAbMes = $stAbMes->get_result()->fetch_assoc();
            $totAbMes = (float)($rowAbMes['total_abonos'] ?? 0.0);

            // Devoluciones del mes
            $sqlDevMes = "SELECT COALESCE(SUM(d.monto_devuelto), 0) AS total_dev
                          FROM pos_devoluciones d
                          JOIN mod_caja_diaria cd ON cd.id = d.caja_diaria_id
                          WHERE cd.id_caja_mensual = ?";
            $stDevMes = $db->prepare($sqlDevMes);
            $stDevMes->bind_param('i', $cmId);
            $stDevMes->execute();
            $rowDevMes = $stDevMes->get_result()->fetch_assoc();
            $totDevMes = (float)($rowDevMes['total_dev'] ?? 0.0);

            $res['mensual'] = [
                'data'             => $cmRow,
                'total_abonos'     => $totAbMes,
                'total_devoluciones'=> $totDevMes,
                'total_neto'       => $totAbMes - $totDevMes,
            ];
        }

        // Caja diaria abierta más reciente
        $sqlCD = "SELECT *
                  FROM mod_caja_diaria
                  WHERE id_empresa = ? AND estado = 'abierta'
                  ORDER BY fecha DESC, id DESC
                  LIMIT 1";
        $stCD = $db->prepare($sqlCD);
        $stCD->bind_param('i', $empresaId);
        $stCD->execute();
        $cdRow = $stCD->get_result()->fetch_assoc();

        if ($cdRow) {
            $cdId = (int)$cdRow['id'];

            $sqlAbDia = "SELECT COALESCE(SUM(a.monto), 0) AS total_abonos
                         FROM pos_abonos a
                         WHERE a.caja_diaria_id = ?";
            $stAbDia = $db->prepare($sqlAbDia);
            $stAbDia->bind_param('i', $cdId);
            $stAbDia->execute();
            $rowAbDia = $stAbDia->get_result()->fetch_assoc();
            $totAbDia = (float)($rowAbDia['total_abonos'] ?? 0.0);

            $sqlDevDia = "SELECT COALESCE(SUM(d.monto_devuelto), 0) AS total_dev
                          FROM pos_devoluciones d
                          WHERE d.caja_diaria_id = ?";
            $stDevDia = $db->prepare($sqlDevDia);
            $stDevDia->bind_param('i', $cdId);
            $stDevDia->execute();
            $rowDevDia = $stDevDia->get_result()->fetch_assoc();
            $totDevDia = (float)($rowDevDia['total_dev'] ?? 0.0);

            $res['diaria'] = [
                'data'              => $cdRow,
                'total_abonos'      => $totAbDia,
                'total_devoluciones'=> $totDevDia,
                'total_neto'        => $totAbDia - $totDevDia,
            ];
        }

        return $res;
    }
}

/** Helpers compartidos */

/** Formatea TICKET como SERIE-0001 */
if (!function_exists('ticket_string')) {
    function ticket_string(?string $serie, ?int $numero): string {
        $serie  = trim((string)$serie);
        $numero = (int)($numero ?? 0);
        if ($serie === '' && $numero === 0) return '';
        return $serie . '-' . str_pad((string)$numero, 4, '0', STR_PAD_LEFT);
    }
}

/** Moneda PEN con 2 decimales: S/ 1,234.56 */
if (!function_exists('fmt_money')) {
    function fmt_money($n): string {
        $v = is_numeric($n) ? (float)$n : 0.0;
        return 'S/ ' . number_format($v, 2, '.', ',');
    }
}

/** Datetime a dd/mm/yyyy hh:mm (24h) */
if (!function_exists('fmt_dt')) {
    function fmt_dt($dt): string {
        if (!$dt) return '';
        try {
            $d = new DateTime(is_string($dt) ? $dt : (string)$dt);
            return $d->format('d/m/Y H:i');
        } catch (Exception $e) {
            return (string)$dt;
        }
    }
}

/** Date a dd/mm/yyyy */
if (!function_exists('fmt_date')) {
    function fmt_date($d): string {
        if (!$d) return '';
        try {
            $dt = new DateTime(is_string($d) ? $d : (string)$d);
            return $dt->format('d/m/Y');
        } catch (Exception $e) {
            return (string)$d;
        }
    }
}
