<?php
// modules/reporte_clientes/funciones.php
// Funciones de ayuda para la Central de reporte de clientes.

if (!function_exists('fmt_money')) {
    function fmt_money($n) {
        $v = is_numeric($n) ? (float)$n : 0.0;
        return 'S/ ' . number_format($v, 2, '.', ',');
    }
}

if (!function_exists('fmt_date')) {
    function fmt_date($dt) {
        if ($dt === null || $dt === '') {
            return '';
        }
        $ts = strtotime((string)$dt);
        if ($ts === false) {
            return (string)$dt;
        }
        return date('d/m/Y', $ts);
    }
}

if (!function_exists('fmt_dt')) {
    function fmt_dt($dt) {
        if ($dt === null || $dt === '') {
            return '';
        }
        $ts = strtotime((string)$dt);
        if ($ts === false) {
            return (string)$dt;
        }
        return date('d/m/Y H:i', $ts);
    }
}

/**
 * Datos básicos de la empresa actual.
 */
if (!function_exists('obtener_empresa_info')) {
    function obtener_empresa_info(mysqli $db, $empresaId) {
        $empresaId = (int)$empresaId;
        if ($empresaId <= 0) {
            return [];
        }

        $sql = "SELECT e.id,
                       e.nombre,
                       e.razon_social,
                       e.ruc,
                       e.direccion,
                       d.nombre AS departamento
                FROM mtp_empresas e
                LEFT JOIN mtp_departamentos d ON d.id = e.id_depa
                WHERE e.id = ?
                LIMIT 1";

        $st = $db->prepare($sql);
        $st->bind_param('i', $empresaId);
        $st->execute();
        $res = $st->get_result();
        $row = $res->fetch_assoc();

        return $row ? $row : [];
    }
}

/**
 * Construye el WHERE y los parámetros de filtros para clientes.
 * Deja en $whereSql la cadena WHERE (sin la palabra WHERE),
 * en $types la cadena de tipos para bind_param
 * y en $vals el array de valores.
 *
 * Además setea $tieneVentas y $tieneAbonos, que se usan
 * en las consultas que requieren joins con agregados.
 */
if (!function_exists('armar_filtros_clientes')) {
    function armar_filtros_clientes($empresaId, array $opts, &$whereSql, &$types, &$vals, &$tieneVentas, &$tieneAbonos) {
        $empresaId = (int)$empresaId;

        $q           = isset($opts['q']) ? trim((string)$opts['q']) : '';
        $tipoPersona = isset($opts['tipo_persona']) ? (string)$opts['tipo_persona'] : '';
        $docTipo     = isset($opts['doc_tipo']) ? (string)$opts['doc_tipo'] : '';
        $activo      = isset($opts['activo']) ? (string)$opts['activo'] : '';

        $fcreadoDesde = isset($opts['fcreado_desde']) ? (string)$opts['fcreado_desde'] : '';
        $fcreadoHasta = isset($opts['fcreado_hasta']) ? (string)$opts['fcreado_hasta'] : '';

        $tieneVentas = !empty($opts['tiene_ventas']);
        $tieneAbonos = !empty($opts['tiene_abonos']);

        $where = [];
        $types = '';
        $vals  = [];

        // Siempre filtramos por empresa
        $where[] = 'c.id_empresa = ?';
        $types  .= 'i';
        $vals[]  = $empresaId;

        // Búsqueda textual
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(c.nombre LIKE ? OR c.doc_numero LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)';
            $types  .= 'ssss';
            $vals[]  = $like;
            $vals[]  = $like;
            $vals[]  = $like;
            $vals[]  = $like;
        }

        // Tipo de persona
        $allowedTipoPersona = ['NATURAL', 'JURIDICA'];
        if (in_array($tipoPersona, $allowedTipoPersona, true)) {
            $where[] = 'c.tipo_persona = ?';
            $types  .= 's';
            $vals[]  = $tipoPersona;
        }

        // Tipo de documento
        $allowedDocTipos = ['DNI','RUC','CE','PAS','BREVETE'];
        if (in_array($docTipo, $allowedDocTipos, true)) {
            $where[] = 'c.doc_tipo = ?';
            $types  .= 's';
            $vals[]  = $docTipo;
        }

        // Estado (activo / inactivo)
        if ($activo === '1' || $activo === '0') {
            $where[] = 'c.activo = ?';
            $types  .= 'i';
            $vals[]  = (int)$activo;
        }

        // Rango por fecha de creación del cliente
        if ($fcreadoDesde !== '') {
            $where[] = 'c.creado >= ?';
            $types  .= 's';
            $vals[]  = $fcreadoDesde . ' 00:00:00';
        }

        if ($fcreadoHasta !== '') {
            $where[] = 'c.creado <= ?';
            $types  .= 's';
            $vals[]  = $fcreadoHasta . ' 23:59:59';
        }

        // Filtros que dependen de agregados (ventas / abonos)
        if ($tieneVentas) {
            $where[] = 'COALESCE(v.ventas_count,0) > 0';
        }
        if ($tieneAbonos) {
            $where[] = 'COALESCE(a.abonos_count,0) > 0';
        }

        $whereSql = $where ? implode(' AND ', $where) : '1=1';
    }
}

/**
 * Resumen general para los clientes del filtro actual.
 */
if (!function_exists('obtener_resumen_clientes')) {
    function obtener_resumen_clientes(mysqli $db, $empresaId, array $opts = []) {
        $empresaId = (int)$empresaId;

        $whereSql    = '';
        $typesFilter = '';
        $valsFilter  = [];
        $tieneVentas = false;
        $tieneAbonos = false;

        armar_filtros_clientes($empresaId, $opts, $whereSql, $typesFilter, $valsFilter, $tieneVentas, $tieneAbonos);

        $sql = "SELECT
                    COUNT(*) AS total_clientes,
                    SUM(CASE WHEN COALESCE(v.ventas_count,0) > 0 THEN 1 ELSE 0 END) AS clientes_con_ventas,
                    SUM(CASE WHEN COALESCE(v.ventas_count,0) = 0 THEN 1 ELSE 0 END) AS clientes_sin_ventas,
                    SUM(COALESCE(v.ventas_total,0))        AS total_ventas,
                    SUM(COALESCE(v.ventas_pagado,0))       AS total_pagado,
                    SUM(COALESCE(v.ventas_devuelto,0))     AS total_devuelto,
                    SUM(COALESCE(v.ventas_saldo,0))        AS total_saldo,
                    SUM(COALESCE(a.abonos_total,0))        AS total_abonos
                FROM pos_clientes c
                LEFT JOIN (
                    SELECT v.cliente_id,
                           COUNT(*)           AS ventas_count,
                           SUM(v.total)       AS ventas_total,
                           SUM(v.total_pagado)   AS ventas_pagado,
                           SUM(v.total_devuelto) AS ventas_devuelto,
                           SUM(v.saldo)          AS ventas_saldo
                    FROM pos_ventas v
                    WHERE v.id_empresa = ?
                    GROUP BY v.cliente_id
                ) v ON v.cliente_id = c.id
                LEFT JOIN (
                    SELECT a.cliente_id,
                           COUNT(*)     AS abonos_count,
                           SUM(a.monto) AS abonos_total
                    FROM pos_abonos a
                    WHERE a.id_empresa = ?
                    GROUP BY a.cliente_id
                ) a ON a.cliente_id = c.id
                WHERE " . $whereSql;

        $st = $db->prepare($sql);
        $typesAll  = 'ii' . $typesFilter;
        $paramsAll = array_merge([$typesAll], [$empresaId, $empresaId], $valsFilter);

        $refs = [];
        foreach ($paramsAll as $k => $v) {
            $refs[$k] = &$paramsAll[$k];
        }
        call_user_func_array([$st, 'bind_param'], $refs);

        $st->execute();
        $res = $st->get_result();
        $row = $res->fetch_assoc();

        if (!$row) {
            return [
                'total_clientes'      => 0,
                'clientes_con_ventas' => 0,
                'clientes_sin_ventas' => 0,
                'total_ventas'        => 0.0,
                'total_pagado'        => 0.0,
                'total_devuelto'      => 0.0,
                'total_saldo'         => 0.0,
                'total_abonos'        => 0.0,
            ];
        }

        return [
            'total_clientes'      => (int)$row['total_clientes'],
            'clientes_con_ventas' => (int)$row['clientes_con_ventas'],
            'clientes_sin_ventas' => (int)$row['clientes_sin_ventas'],
            'total_ventas'        => (float)$row['total_ventas'],
            'total_pagado'        => (float)$row['total_pagado'],
            'total_devuelto'      => (float)$row['total_devuelto'],
            'total_saldo'         => (float)$row['total_saldo'],
            'total_abonos'        => (float)$row['total_abonos'],
        ];
    }
}

/**
 * Búsqueda paginada de clientes con métricas de ventas y abonos.
 */
if (!function_exists('buscar_clientes_con_metricas')) {
    function buscar_clientes_con_metricas(mysqli $db, $empresaId, array $opts = []) {
        $empresaId = (int)$empresaId;

        // Paginación
        $page = isset($opts['pagina']) ? (int)$opts['pagina'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage  = isset($opts['por_pagina']) ? (int)$opts['por_pagina'] : 20;
        $allowed  = [10, 20, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 20;
        }

        // Filtros compartidos
        $whereSql    = '';
        $typesFilter = '';
        $valsFilter  = [];
        $tieneVentas = false;
        $tieneAbonos = false;

        armar_filtros_clientes($empresaId, $opts, $whereSql, $typesFilter, $valsFilter, $tieneVentas, $tieneAbonos);

        // Total de filas
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM pos_clientes c
                     LEFT JOIN (
                         SELECT v.cliente_id,
                                COUNT(*) AS ventas_count
                         FROM pos_ventas v
                         WHERE v.id_empresa = ?
                         GROUP BY v.cliente_id
                     ) v ON v.cliente_id = c.id
                     LEFT JOIN (
                         SELECT a.cliente_id,
                                COUNT(*) AS abonos_count
                         FROM pos_abonos a
                         WHERE a.id_empresa = ?
                         GROUP BY a.cliente_id
                     ) a ON a.cliente_id = c.id
                     WHERE " . $whereSql;

        $stCount   = $db->prepare($sqlCount);
        $typesCnt  = 'ii' . $typesFilter;
        $paramsCnt = array_merge([$typesCnt], [$empresaId, $empresaId], $valsFilter);

        $refsCnt = [];
        foreach ($paramsCnt as $k => $v) {
            $refsCnt[$k] = &$paramsCnt[$k];
        }
        call_user_func_array([$stCount, 'bind_param'], $refsCnt);

        $stCount->execute();
        $resCnt = $stCount->get_result();
        $rowCnt = $resCnt->fetch_assoc();
        $total  = $rowCnt ? (int)$rowCnt['total'] : 0;

        if ($total === 0) {
            return [
                'rows'        => [],
                'total'       => 0,
                'page'        => 1,
                'per_page'    => $perPage,
                'total_pages' => 0,
                'from'        => 0,
                'to'          => 0,
            ];
        }

        $totalPages = (int)ceil($total / $perPage);
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // Consulta principal
        $sql = "SELECT
                    c.id,
                    c.tipo_persona,
                    c.doc_tipo,
                    c.doc_numero,
                    c.nombre,
                    c.email,
                    c.telefono,
                    c.direccion,
                    c.activo,
                    c.creado,
                    c.actualizado,
                    COALESCE(v.ventas_count,0)      AS ventas_count,
                    COALESCE(v.ventas_total,0.0)    AS ventas_total,
                    COALESCE(v.ventas_pagado,0.0)   AS ventas_pagado,
                    COALESCE(v.ventas_devuelto,0.0) AS ventas_devuelto,
                    COALESCE(v.ventas_saldo,0.0)    AS ventas_saldo,
                    v.ultima_venta,
                    COALESCE(a.abonos_count,0)      AS abonos_count,
                    COALESCE(a.abonos_total,0.0)    AS abonos_total,
                    a.ultimo_abono
                FROM pos_clientes c
                LEFT JOIN (
                    SELECT v.cliente_id,
                           COUNT(*)           AS ventas_count,
                           SUM(v.total)       AS ventas_total,
                           SUM(v.total_pagado)   AS ventas_pagado,
                           SUM(v.total_devuelto) AS ventas_devuelto,
                           SUM(v.saldo)          AS ventas_saldo,
                           MAX(v.fecha_emision)  AS ultima_venta
                    FROM pos_ventas v
                    WHERE v.id_empresa = ?
                    GROUP BY v.cliente_id
                ) v ON v.cliente_id = c.id
                LEFT JOIN (
                    SELECT a.cliente_id,
                           COUNT(*)     AS abonos_count,
                           SUM(a.monto) AS abonos_total,
                           MAX(a.fecha) AS ultimo_abono
                    FROM pos_abonos a
                    WHERE a.id_empresa = ?
                    GROUP BY a.cliente_id
                ) a ON a.cliente_id = c.id
                WHERE " . $whereSql . "
                ORDER BY c.nombre ASC
                LIMIT ? OFFSET ?";

        $stMain   = $db->prepare($sql);
        $typesAll = 'ii' . $typesFilter . 'ii';
        $params   = array_merge([$typesAll], [$empresaId, $empresaId], $valsFilter, [$perPage, $offset]);

        $refs = [];
        foreach ($params as $k => $v) {
            $refs[$k] = &$params[$k];
        }
        call_user_func_array([$stMain, 'bind_param'], $refs);

        $stMain->execute();
        $res  = $stMain->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        if (!$rows) {
            return [
                'rows'        => [],
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $totalPages,
                'from'        => 0,
                'to'          => 0,
            ];
        }

        $from = ($page - 1) * $perPage + 1;
        $to   = $from + count($rows) - 1;
        if ($from > $total) {
            $from = $total;
        }
        if ($to > $total) {
            $to = $total;
        }

        return [
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'from'        => $from,
            'to'          => $to,
        ];
    }
}
