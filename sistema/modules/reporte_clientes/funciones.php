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
 * Detecta si el esquema de perfil opcional de conductor ya existe.
 * Si no existe, la central sigue funcionando sin esos campos.
 */
if (!function_exists('reporte_clientes_soporta_perfil_conductor')) {
    function reporte_clientes_soporta_perfil_conductor(mysqli $db) {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $rs = $db->query("SHOW TABLES LIKE 'pos_perfil_conductor'");
        $cache = ($rs && $rs->num_rows > 0);
        return $cache;
    }
}

/**
 * JOIN reutilizable para traer el ultimo conductor principal asociado
 * a cada cliente (por ultima venta id de ese cliente en la empresa).
 */
if (!function_exists('reporte_clientes_join_conductor_reciente_sql')) {
    function reporte_clientes_join_conductor_reciente_sql() {
        return "LEFT JOIN (
                    SELECT x.cliente_id,
                           COALESCE(vc.conductor_doc_tipo, co.doc_tipo) AS conductor_doc_tipo,
                           COALESCE(vc.conductor_doc_numero, co.doc_numero) AS conductor_doc_numero,
                           COALESCE(vc.conductor_nombres, co.nombres) AS conductor_nombres,
                           COALESCE(vc.conductor_apellidos, co.apellidos) AS conductor_apellidos,
                           COALESCE(vc.conductor_telefono, co.telefono) AS conductor_telefono,
                           COALESCE(
                             vc.conductor_origen,
                             CASE
                               WHEN vc.conductor_id IS NOT NULL THEN 'conductor_otra_persona'
                               WHEN v.contratante_doc_tipo IS NOT NULL THEN 'contratante_juridica'
                               ELSE 'cliente_natural'
                             END
                           ) AS conductor_origen,
                           COALESCE(
                             vc.conductor_es_mismo_cliente,
                             CASE
                               WHEN vc.conductor_id IS NULL AND v.contratante_doc_tipo IS NULL THEN 1
                               ELSE 0
                             END
                           ) AS conductor_es_mismo_cliente,
                           v.fecha_emision AS conductor_ultima_venta
                    FROM (
                        SELECT v.cliente_id, MAX(v.id) AS venta_id
                        FROM pos_ventas v
                        WHERE v.id_empresa = ?
                        GROUP BY v.cliente_id
                    ) x
                    INNER JOIN pos_ventas v ON v.id = x.venta_id
                    LEFT JOIN pos_venta_conductores vc ON vc.venta_id = v.id AND vc.es_principal = 1
                    LEFT JOIN pos_conductores co ON co.id = vc.conductor_id
                ) lc ON lc.cliente_id = c.id";
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
        $usarPerfil   = !empty($opts['usar_perfil_conductor']);

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
            $parts = [
                'c.nombre LIKE ?',
                'c.doc_numero LIKE ?',
                'c.telefono LIKE ?',
                'lc.conductor_doc_numero LIKE ?',
                'CONCAT_WS(" ", lc.conductor_nombres, lc.conductor_apellidos) LIKE ?',
                'lc.conductor_telefono LIKE ?'
            ];
            $types .= 'ssssss';
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;
            $vals[] = $like;

            if ($usarPerfil) {
                $parts[] = 'pc.email LIKE ?';
                $parts[] = 'pc.canal LIKE ?';
                $parts[] = 'ca.codigo LIKE ?';
                $parts[] = 'cm.codigo LIKE ?';
                $parts[] = 'pc.nota LIKE ?';
                $types  .= 'sssss';
                $vals[]  = $like;
                $vals[]  = $like;
                $vals[]  = $like;
                $vals[]  = $like;
                $vals[]  = $like;
            }

            $where[] = '(' . implode(' OR ', $parts) . ')';
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
        $usarPerfil = reporte_clientes_soporta_perfil_conductor($db);
        $optsConPerfil = $opts;
        $optsConPerfil['usar_perfil_conductor'] = $usarPerfil ? 1 : 0;

        $whereSql    = '';
        $typesFilter = '';
        $valsFilter  = [];
        $tieneVentas = false;
        $tieneAbonos = false;

        armar_filtros_clientes($empresaId, $optsConPerfil, $whereSql, $typesFilter, $valsFilter, $tieneVentas, $tieneAbonos);

        $joinConductor = reporte_clientes_join_conductor_reciente_sql();
        $joinPerfil = '';
        if ($usarPerfil) {
            $joinPerfil = "LEFT JOIN pos_perfil_conductor pc
                               ON pc.id_empresa = c.id_empresa
                               AND pc.doc_tipo   = COALESCE(lc.conductor_doc_tipo, c.doc_tipo)
                               AND pc.doc_numero = COALESCE(lc.conductor_doc_numero, c.doc_numero)
                            LEFT JOIN cq_categorias_licencia ca ON ca.id = pc.categoria_auto_id
                            LEFT JOIN cq_categorias_licencia cm ON cm.id = pc.categoria_moto_id";
        }

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
                " . $joinConductor . "
                " . $joinPerfil . "
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
        $typesAll  = 'iii' . $typesFilter;
        $paramsAll = array_merge([$typesAll], [$empresaId, $empresaId, $empresaId], $valsFilter);

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
        $usarPerfil = reporte_clientes_soporta_perfil_conductor($db);
        $optsConPerfil = $opts;
        $optsConPerfil['usar_perfil_conductor'] = $usarPerfil ? 1 : 0;

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

        armar_filtros_clientes($empresaId, $optsConPerfil, $whereSql, $typesFilter, $valsFilter, $tieneVentas, $tieneAbonos);

        $joinConductor = reporte_clientes_join_conductor_reciente_sql();
        $joinPerfil = '';
        if ($usarPerfil) {
            $joinPerfil = "LEFT JOIN pos_perfil_conductor pc
                               ON pc.id_empresa = c.id_empresa
                               AND pc.doc_tipo   = COALESCE(lc.conductor_doc_tipo, c.doc_tipo)
                               AND pc.doc_numero = COALESCE(lc.conductor_doc_numero, c.doc_numero)
                            LEFT JOIN cq_categorias_licencia ca ON ca.id = pc.categoria_auto_id
                            LEFT JOIN cq_categorias_licencia cm ON cm.id = pc.categoria_moto_id";
        }

        // Total de filas
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM pos_clientes c
                     " . $joinConductor . "
                     " . $joinPerfil . "
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
        $typesCnt  = 'iii' . $typesFilter;
        $paramsCnt = array_merge([$typesCnt], [$empresaId, $empresaId, $empresaId], $valsFilter);

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
        $selectPerfil = $usarPerfil
            ? "pc.canal AS perfil_canal,
               pc.email AS perfil_email,
               pc.nacimiento AS perfil_nacimiento,
               ca.codigo AS perfil_categoria_auto,
               cm.codigo AS perfil_categoria_moto,
               pc.nota AS perfil_nota,
               pc.actualizado AS perfil_actualizado,"
            : "NULL AS perfil_canal,
               NULL AS perfil_email,
               NULL AS perfil_nacimiento,
               NULL AS perfil_categoria_auto,
               NULL AS perfil_categoria_moto,
               NULL AS perfil_nota,
               NULL AS perfil_actualizado,";

        $sql = "SELECT
                    c.id,
                    c.tipo_persona,
                    c.doc_tipo,
                    c.doc_numero,
                    c.nombre,
                    c.telefono,
                    lc.conductor_doc_tipo,
                    lc.conductor_doc_numero,
                    lc.conductor_nombres,
                    lc.conductor_apellidos,
                    lc.conductor_telefono,
                    lc.conductor_origen,
                    lc.conductor_es_mismo_cliente,
                    lc.conductor_ultima_venta,
                    " . $selectPerfil . "
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
                " . $joinConductor . "
                " . $joinPerfil . "
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
                ORDER BY
                    GREATEST(
                        IFNULL(v.ultima_venta, '1000-01-01 00:00:00'),
                        IFNULL(a.ultimo_abono, '1000-01-01 00:00:00'),
                        IFNULL(c.actualizado, '1000-01-01 00:00:00'),
                        IFNULL(c.creado, '1000-01-01 00:00:00')
                    ) DESC,
                    c.id DESC
                LIMIT ? OFFSET ?";

        $stMain   = $db->prepare($sql);
        $typesAll = 'iii' . $typesFilter . 'ii';
        $params   = array_merge([$typesAll], [$empresaId, $empresaId, $empresaId], $valsFilter, [$perPage, $offset]);

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
