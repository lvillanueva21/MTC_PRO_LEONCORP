<?php
// modules/reporte_ventas/funciones.php
// Lógica para el reporte avanzado de ventas: filtros, paginación, detalles, resumenes y contexto.

/**
 * Lista ventas de la empresa y agrega:
 *  - servicio_principal por venta
 *  - conductores[] por venta
 *  - abonos[] por venta (con devuelto_monto)
 *  - detalles[] por venta (líneas de servicios)
 * Esta versión NO usa filtros ni paginación (uso general).
 */
if (!function_exists('listar_ventas_con_detalles')) {
  function listar_ventas_con_detalles(mysqli $db, int $empresaId): array {
    $sql = "SELECT
              v.id,
              v.serie,
              v.numero,
              v.fecha_emision,
              v.total,
              v.total_pagado,
              v.total_devuelto,
              v.saldo,
              v.estado,
              v.caja_diaria_id,
              v.tipo_comprobante,
              v.contratante_doc_tipo,
              v.contratante_doc_numero,
              v.contratante_nombres,
              v.contratante_apellidos,
              v.contratante_telefono,
              v.observacion,
              v.creado_por,
              c.nombre      AS cliente,
              c.doc_tipo    AS cliente_doc_tipo,
              c.doc_numero  AS cliente_doc_numero
            FROM pos_ventas v
            LEFT JOIN pos_clientes c ON c.id = v.cliente_id
            WHERE v.id_empresa = ?
            ORDER BY v.fecha_emision DESC, v.id DESC";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res    = $st->get_result();
    $ventas = $res->fetch_all(MYSQLI_ASSOC) ?: [];

    if (!$ventas) {
      return [];
    }

    reporte_cargar_detalles_ventas($db, $ventas);
    return $ventas;
  }
}

/**
 * Servicios disponibles para la empresa (para el combo de filtro).
 */
if (!function_exists('listar_servicios_empresa')) {
  function listar_servicios_empresa(mysqli $db, int $empresaId): array {
    $sql = "SELECT s.id, s.nombre
            FROM mod_servicios s
            JOIN mod_empresa_servicio es ON es.servicio_id = s.id
            WHERE es.empresa_id = ? AND s.activo = 1
            ORDER BY s.nombre";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC) ?: [];
  }
}

/**
 * Carga en un array de ventas:
 *  - servicio_principal
 *  - conductores[]
 *  - abonos[] con devuelto_monto
 *  - detalles[] de servicios
 * Se trabaja por lote usando los IDs de las ventas.
 */
if (!function_exists('reporte_cargar_detalles_ventas')) {
  function reporte_cargar_detalles_ventas(mysqli $db, array &$ventas): void {
    if (!$ventas) {
      return;
    }

    $ids = [];
    foreach ($ventas as $row) {
      $ids[] = (int)$row['id'];
    }

    if (!$ids) {
      return;
    }

    $in    = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    // ---------- Servicio principal ----------
    $sqlS = "SELECT t.venta_id, vd.servicio_nombre
             FROM (
               SELECT venta_id, MIN(id) AS min_id
               FROM pos_venta_detalles
               WHERE venta_id IN ($in)
               GROUP BY venta_id
             ) t
             JOIN pos_venta_detalles vd
               ON vd.venta_id = t.venta_id AND vd.id = t.min_id";
    $stS      = $db->prepare($sqlS);
    $paramsS  = array_merge([$types], $ids);
    $refsS    = [];
    foreach ($paramsS as $k => $v) {
      $refsS[$k] = &$paramsS[$k];
    }
    call_user_func_array([$stS, 'bind_param'], $refsS);
    $stS->execute();
    $rsS        = $stS->get_result();
    $svcByVenta = [];
    while ($s = $rsS->fetch_assoc()) {
      $svcByVenta[(int)$s['venta_id']] = (string)$s['servicio_nombre'];
    }
    $stS->close();

    // ---------- Conductores ----------
    $sqlC = "SELECT vc.venta_id, vc.es_principal,
                    vc.conductor_tipo,
                    co.doc_tipo, co.doc_numero, co.nombres, co.apellidos, co.telefono
             FROM pos_venta_conductores vc
             LEFT JOIN pos_conductores co ON co.id = vc.conductor_id
             WHERE vc.venta_id IN ($in)
             ORDER BY vc.venta_id, vc.es_principal DESC, vc.id ASC";
    $stC      = $db->prepare($sqlC);
    $paramsC  = array_merge([$types], $ids);
    $refsC    = [];
    foreach ($paramsC as $k => $v) {
      $refsC[$k] = &$paramsC[$k];
    }
    call_user_func_array([$stC, 'bind_param'], $refsC);
    $stC->execute();
    $rsC               = $stC->get_result();
    $conductoresByVenta = [];
    while ($c = $rsC->fetch_assoc()) {
      $vid = (int)$c['venta_id'];
      if (!isset($conductoresByVenta[$vid])) {
        $conductoresByVenta[$vid] = [];
      }
      $conductoresByVenta[$vid][] = $c;
    }
    $stC->close();

    // ---------- Mapa de devoluciones por aplicacion_id ----------
    $sqlD = "SELECT d.abono_aplicacion_id, SUM(d.monto_devuelto) AS devuelto
             FROM pos_devoluciones d
             WHERE d.venta_id IN ($in) AND d.abono_aplicacion_id IS NOT NULL
             GROUP BY d.abono_aplicacion_id";
    $stD      = $db->prepare($sqlD);
    $paramsD  = array_merge([$types], $ids);
    $refsD    = [];
    foreach ($paramsD as $k => $v) {
      $refsD[$k] = &$paramsD[$k];
    }
    call_user_func_array([$stD, 'bind_param'], $refsD);
    $stD->execute();
    $rsD            = $stD->get_result();
    $devByAplicacion = [];
    while ($d = $rsD->fetch_assoc()) {
      $devByAplicacion[(int)$d['abono_aplicacion_id']] = (float)$d['devuelto'];
    }
    $stD->close();

    // ---------- Abonos aplicados ----------
    $sqlA = "SELECT apl.id AS aplicacion_id,
                    apl.venta_id,
                    ab.fecha,
                    ab.monto,
                    apl.monto_aplicado,
                    ab.referencia,
                    mp.nombre AS medio
             FROM pos_abono_aplicaciones apl
             JOIN pos_abonos ab        ON ab.id = apl.abono_id
             LEFT JOIN pos_medios_pago mp ON mp.id = ab.medio_id
             WHERE apl.venta_id IN ($in)
             ORDER BY ab.fecha ASC, apl.id ASC";
    $stA      = $db->prepare($sqlA);
    $paramsA  = array_merge([$types], $ids);
    $refsA    = [];
    foreach ($paramsA as $k => $v) {
      $refsA[$k] = &$paramsA[$k];
    }
    call_user_func_array([$stA, 'bind_param'], $refsA);
    $stA->execute();
    $rsA           = $stA->get_result();
    $abonosByVenta = [];
    while ($a = $rsA->fetch_assoc()) {
      $vid   = (int)$a['venta_id'];
      $aplId = (int)$a['aplicacion_id'];
      $a['devuelto_monto'] = isset($devByAplicacion[$aplId]) ? (float)$devByAplicacion[$aplId] : 0.0;
      if (!isset($abonosByVenta[$vid])) {
        $abonosByVenta[$vid] = [];
      }
      $abonosByVenta[$vid][] = $a;
    }
    $stA->close();

    // ---------- Detalles de servicios ----------
    $sqlVD = "SELECT
                venta_id,
                servicio_nombre,
                descripcion,
                cantidad,
                precio_unitario,
                descuento,
                total_linea
              FROM pos_venta_detalles
              WHERE venta_id IN ($in)
              ORDER BY venta_id, id ASC";
    $stVD      = $db->prepare($sqlVD);
    $paramsVD  = array_merge([$types], $ids);
    $refsVD    = [];
    foreach ($paramsVD as $k => $v) {
      $refsVD[$k] = &$paramsVD[$k];
    }
    call_user_func_array([$stVD, 'bind_param'], $refsVD);
    $stVD->execute();
    $rsVD            = $stVD->get_result();
    $detallesByVenta = [];
    while ($d = $rsVD->fetch_assoc()) {
      $vid = (int)$d['venta_id'];
      if (!isset($detallesByVenta[$vid])) {
        $detallesByVenta[$vid] = [];
      }
      $detallesByVenta[$vid][] = $d;
    }
    $stVD->close();

    // ---------- Adjuntar a cada venta ----------
    foreach ($ventas as &$r) {
      $vid = (int)$r['id'];
      $r['servicio_principal'] = isset($svcByVenta[$vid]) ? $svcByVenta[$vid] : '';
      $r['conductores']        = isset($conductoresByVenta[$vid]) ? $conductoresByVenta[$vid] : [];
      $r['abonos']             = isset($abonosByVenta[$vid]) ? $abonosByVenta[$vid] : [];
      $r['detalles']           = isset($detallesByVenta[$vid]) ? $detallesByVenta[$vid] : [];
    }
    unset($r);
  }
}

/**
 * Construye el WHERE + tipos/valores para las consultas de ventas del reporte.
 * Se usa tanto para el listado paginado como para el resumen.
 *
 * Filtros soportados en $opts:
 *  - q            : texto libre (cliente, doc cliente, doc contratante, nombre contratante)
 *  - servicio_id  : servicio en los detalles
 *  - estado       : 'pagado', 'pendiente', 'anulada'
 *  - fdesde       : fecha venta desde (Y-m-d)
 *  - fhasta       : fecha venta hasta (Y-m-d)
 *  - serie_id     : v.serie_id
 *  - usuario_id   : v.creado_por
 *  - caja_diaria_id : v.caja_diaria_id
 *  - medio_id     : abonos con ese medio de pago
 */
if (!function_exists('reporte_build_ventas_where')) {
  function reporte_build_ventas_where(int $empresaId, array $opts, &$types, &$vals): string {
    $where = ['v.id_empresa = ?'];
    $types = 'i';
    $vals  = [$empresaId];

    $q          = isset($opts['q']) ? trim((string)$opts['q']) : '';
    $servicioId = isset($opts['servicio_id']) ? (int)$opts['servicio_id'] : 0;
    $estado     = isset($opts['estado']) ? (string)$opts['estado'] : '';
    if (!in_array($estado, ['pagado', 'pendiente', 'anulada'], true)) {
      $estado = '';
    }
    $fdesde = isset($opts['fdesde']) ? (string)$opts['fdesde'] : '';
    $fhasta = isset($opts['fhasta']) ? (string)$opts['fhasta'] : '';

    $serieId      = isset($opts['serie_id']) ? (int)$opts['serie_id'] : 0;
    $usuarioId    = isset($opts['usuario_id']) ? (int)$opts['usuario_id'] : 0;
    $cajaDiariaId = isset($opts['caja_diaria_id']) ? (int)$opts['caja_diaria_id'] : 0;
    $medioId      = isset($opts['medio_id']) ? (int)$opts['medio_id'] : 0;

    // Nombre & Documento (cliente o contratante)
    if ($q !== '') {
      $like = '%' . $q . '%';
      $where[] = '(c.nombre LIKE ?
                   OR c.doc_numero LIKE ?
                   OR v.contratante_doc_numero LIKE ?
                   OR CONCAT_WS(" ", v.contratante_nombres, v.contratante_apellidos) LIKE ?)';
      $types .= 'ssss';
      $vals[] = $like;
      $vals[] = $like;
      $vals[] = $like;
      $vals[] = $like;
    }

    // Servicio
    if ($servicioId > 0) {
      $where[] = 'EXISTS (
        SELECT 1
        FROM pos_venta_detalles vd
        WHERE vd.venta_id = v.id AND vd.servicio_id = ?
      )';
      $types .= 'i';
      $vals[] = $servicioId;
    }

    // Estado visual (pagado / pendiente / anulada)
    if ($estado === 'pagado') {
      $where[] = "(v.estado = 'EMITIDA' AND v.saldo <= 0)";
    } elseif ($estado === 'pendiente') {
      $where[] = "(v.estado = 'EMITIDA' AND v.saldo > 0)";
    } elseif ($estado === 'anulada') {
      $where[] = "v.estado = 'ANULADA'";
    }

    // Fecha de venta (fecha_emision)
    $fdesdeDT = '';
    $fhastaDT = '';
    if ($fdesde !== '') {
      $fdesdeDT = $fdesde . ' 00:00:00';
    }
    if ($fhasta !== '') {
      $fhastaDT = $fhasta . ' 23:59:59';
    }

    if ($fdesdeDT !== '' && $fhastaDT !== '') {
      $where[] = 'v.fecha_emision BETWEEN ? AND ?';
      $types  .= 'ss';
      $vals[]  = $fdesdeDT;
      $vals[]  = $fhastaDT;
    } elseif ($fdesdeDT !== '') {
      $where[] = 'v.fecha_emision >= ?';
      $types  .= 's';
      $vals[]  = $fdesdeDT;
    } elseif ($fhastaDT !== '') {
      $where[] = 'v.fecha_emision <= ?';
      $types  .= 's';
      $vals[]  = $fhastaDT;
    }

    // Serie
    if ($serieId > 0) {
      $where[] = 'v.serie_id = ?';
      $types  .= 'i';
      $vals[]  = $serieId;
    }

    // Usuario creador
    if ($usuarioId > 0) {
      $where[] = 'v.creado_por = ?';
      $types  .= 'i';
      $vals[]  = $usuarioId;
    }

    // Caja diaria
    if ($cajaDiariaId > 0) {
      $where[] = 'v.caja_diaria_id = ?';
      $types  .= 'i';
      $vals[]  = $cajaDiariaId;
    }

    // Medio de pago (al menos un abono con ese medio)
    if ($medioId > 0) {
      $where[] = 'EXISTS (
        SELECT 1
        FROM pos_abono_aplicaciones apl
        JOIN pos_abonos ab ON ab.id = apl.abono_id
        WHERE apl.venta_id = v.id AND ab.medio_id = ?
      )';
      $types  .= 'i';
      $vals[]  = $medioId;
    }

    return implode(' AND ', $where);
  }
}

/**
 * Versión paginada + con filtros de listar_ventas_con_detalles.
 * Devuelve:
 *  - rows        => array de ventas (con servicio_principal, conductores[], abonos[], detalles[])
 *  - total       => total de ventas que cumplen el filtro
 *  - page        => página actual
 *  - per_page    => filas por página
 *  - total_pages => número total de páginas
 *  - from, to    => rango de filas mostrado
 */
if (!function_exists('buscar_ventas_con_detalles')) {
  function buscar_ventas_con_detalles(mysqli $db, int $empresaId, array $opts = []): array {
    // Paginación
    $page = isset($opts['pagina']) ? (int)$opts['pagina'] : 1;
    if ($page < 1) {
      $page = 1;
    }

    $perPage  = isset($opts['por_pagina']) ? (int)$opts['por_pagina'] : 10;
    $allowed  = [10, 20, 50, 100];
    if (!in_array($perPage, $allowed, true)) {
      $perPage = 10;
    }

    // WHERE dinámico
    $types   = '';
    $vals    = [];
    $whereSql = reporte_build_ventas_where($empresaId, $opts, $types, $vals);

    // ---- Total de filas ----
    $sqlCount = "SELECT COUNT(*) AS total
                 FROM pos_ventas v
                 LEFT JOIN pos_clientes c ON c.id = v.cliente_id
                 WHERE $whereSql";
    $stCount   = $db->prepare($sqlCount);
    $paramsCnt = array_merge([$types], $vals);
    $refsCnt   = [];
    foreach ($paramsCnt as $k => $v) {
      $refsCnt[$k] = &$paramsCnt[$k];
    }
    call_user_func_array([$stCount, 'bind_param'], $refsCnt);
    $stCount->execute();
    $rowCnt = $stCount->get_result()->fetch_assoc();
    $stCount->close();
    $total  = (int)($rowCnt['total'] ?? 0);

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

    // ---- Consulta principal (solo la página actual) ----
    $sql = "SELECT
              v.id,
              v.serie,
              v.numero,
              v.fecha_emision,
              v.total,
              v.total_pagado,
              v.total_devuelto,
              v.saldo,
              v.estado,
              v.caja_diaria_id,
              v.tipo_comprobante,
              v.contratante_doc_tipo,
              v.contratante_doc_numero,
              v.contratante_nombres,
              v.contratante_apellidos,
              v.contratante_telefono,
              v.observacion,
              v.creado_por,
              c.nombre      AS cliente,
              c.doc_tipo    AS cliente_doc_tipo,
              c.doc_numero  AS cliente_doc_numero
            FROM pos_ventas v
            LEFT JOIN pos_clientes c ON c.id = v.cliente_id
            WHERE $whereSql
            ORDER BY v.fecha_emision DESC, v.id DESC
            LIMIT ? OFFSET ?";

    $st         = $db->prepare($sql);
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
    $ventas = $res->fetch_all(MYSQLI_ASSOC) ?: [];
    $st->close();

    if (!$ventas) {
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

    // Adjuntar servicio_principal, conductores, abonos, detalles
    reporte_cargar_detalles_ventas($db, $ventas);

    // Rango mostrado
    $from = ($page - 1) * $perPage + 1;
    $to   = $from + count($ventas) - 1;
    if ($from > $total) {
      $from = $total;
    }
    if ($to > $total) {
      $to = $total;
    }

    return [
      'rows'        => $ventas,
      'total'       => $total,
      'page'        => $page,
      'per_page'    => $perPage,
      'total_pages' => $totalPages,
      'from'        => $from,
      'to'          => $to,
    ];
  }
}

/**
 * Versión básica (por si quieres reutilizarla en otros listados simples)
 */
if (!function_exists('listar_ventas_basico')) {
  function listar_ventas_basico(mysqli $db, int $empresaId): array {
    $sql = "SELECT
              v.id,
              v.serie,
              v.numero,
              v.fecha_emision,
              v.total,
              v.saldo,
              v.estado,
              c.nombre AS cliente
            FROM pos_ventas v
            LEFT JOIN pos_clientes c ON c.id = v.cliente_id
            WHERE v.id_empresa = ?
            ORDER BY v.id DESC";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC) ?: [];
  }
}

/**
 * Resumen agregado de ventas según los mismos filtros que el listado.
 * Devuelve totales de:
 *  - total_ventas
 *  - total_emitidas
 *  - total_anuladas
 *  - total_bruto
 *  - total_pagado
 *  - total_devuelto
 *  - total_saldo
 *  - total_neto (pagado - devuelto)
 */
if (!function_exists('obtener_resumen_ventas')) {
  function obtener_resumen_ventas(mysqli $db, int $empresaId, array $opts = []): array {
    $types   = '';
    $vals    = [];
    $whereSql = reporte_build_ventas_where($empresaId, $opts, $types, $vals);

    $sql = "SELECT
              COUNT(*) AS total_ventas,
              SUM(v.total) AS total_bruto,
              SUM(v.total_pagado) AS total_pagado,
              SUM(v.total_devuelto) AS total_devuelto,
              SUM(v.saldo) AS total_saldo,
              SUM(CASE WHEN v.estado = 'EMITIDA' THEN 1 ELSE 0 END) AS total_emitidas,
              SUM(CASE WHEN v.estado = 'ANULADA' THEN 1 ELSE 0 END) AS total_anuladas
            FROM pos_ventas v
            LEFT JOIN pos_clientes c ON c.id = v.cliente_id
            WHERE $whereSql";

    $st        = $db->prepare($sql);
    $params    = array_merge([$types], $vals);
    $refs      = [];
    foreach ($params as $k => $v) {
      $refs[$k] = &$params[$k];
    }
    call_user_func_array([$st, 'bind_param'], $refs);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc() ?: [];
    $st->close();

    $totalBruto    = isset($row['total_bruto']) ? (float)$row['total_bruto'] : 0.0;
    $totalPagado   = isset($row['total_pagado']) ? (float)$row['total_pagado'] : 0.0;
    $totalDevuelto = isset($row['total_devuelto']) ? (float)$row['total_devuelto'] : 0.0;
    $totalSaldo    = isset($row['total_saldo']) ? (float)$row['total_saldo'] : 0.0;
    $totalNeto     = $totalPagado - $totalDevuelto;

    return [
      'total_ventas'   => (int)($row['total_ventas'] ?? 0),
      'total_emitidas' => (int)($row['total_emitidas'] ?? 0),
      'total_anuladas' => (int)($row['total_anuladas'] ?? 0),
      'total_bruto'    => $totalBruto,
      'total_pagado'   => $totalPagado,
      'total_devuelto' => $totalDevuelto,
      'total_saldo'    => $totalSaldo,
      'total_neto'     => $totalNeto,
    ];
  }
}

/**
 * Información básica de la empresa actual.
 */
if (!function_exists('obtener_empresa_info')) {
  function obtener_empresa_info(mysqli $db, int $empresaId): ?array {
    $sql = "SELECT
              e.id,
              e.nombre,
              e.razon_social,
              e.ruc,
              e.direccion,
              d.nombre AS departamento
            FROM mtp_empresas e
            LEFT JOIN mtp_departamentos d ON d.id = e.id_depa
            WHERE e.id = ?";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc();
    $st->close();
    if (!$row) {
      return null;
    }
    return $row;
  }
}

/**
 * Caja mensual actual (abierta) de la empresa.
 */
if (!function_exists('obtener_caja_mensual_actual')) {
  function obtener_caja_mensual_actual(mysqli $db, int $empresaId): ?array {
    $sql = "SELECT
              id,
              id_empresa,
              anio,
              mes,
              codigo,
              estado,
              abierto_en,
              cerrado_en
            FROM mod_caja_mensual
            WHERE id_empresa = ? AND estado = 'abierta'
            ORDER BY periodo DESC
            LIMIT 1";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc();
    $st->close();
    if (!$row) {
      return null;
    }
    return $row;
  }
}

/**
 * Caja diaria actual (abierta) de la empresa.
 */
if (!function_exists('obtener_caja_diaria_actual')) {
  function obtener_caja_diaria_actual(mysqli $db, int $empresaId): ?array {
    $sql = "SELECT
              id,
              id_empresa,
              id_caja_mensual,
              fecha,
              codigo,
              estado,
              abierto_en,
              cerrado_en
            FROM mod_caja_diaria
            WHERE id_empresa = ? AND estado = 'abierta'
            ORDER BY fecha DESC
            LIMIT 1";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc();
    $st->close();
    if (!$row) {
      return null;
    }
    return $row;
  }
}

/**
 * Resumen de ventas de una caja mensual.
 */
if (!function_exists('obtener_resumen_caja_mensual')) {
  function obtener_resumen_caja_mensual(mysqli $db, int $empresaId, int $cajaMensualId): array {
    if ($cajaMensualId <= 0) {
      return [
        'total_ventas' => 0,
        'total_bruto'  => 0.0,
        'total_pagado' => 0.0,
        'total_devuelto' => 0.0,
        'total_saldo'  => 0.0,
      ];
    }

    $sql = "SELECT
              COUNT(*) AS total_ventas,
              SUM(v.total) AS total_bruto,
              SUM(v.total_pagado) AS total_pagado,
              SUM(v.total_devuelto) AS total_devuelto,
              SUM(v.saldo) AS total_saldo
            FROM pos_ventas v
            JOIN mod_caja_diaria cd ON cd.id = v.caja_diaria_id
            WHERE v.id_empresa = ? AND cd.id_caja_mensual = ?";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $empresaId, $cajaMensualId);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc() ?: [];
    $st->close();

    return [
      'total_ventas'   => (int)($row['total_ventas'] ?? 0),
      'total_bruto'    => isset($row['total_bruto']) ? (float)$row['total_bruto'] : 0.0,
      'total_pagado'   => isset($row['total_pagado']) ? (float)$row['total_pagado'] : 0.0,
      'total_devuelto' => isset($row['total_devuelto']) ? (float)$row['total_devuelto'] : 0.0,
      'total_saldo'    => isset($row['total_saldo']) ? (float)$row['total_saldo'] : 0.0,
    ];
  }
}

/**
 * Resumen de ventas de una caja diaria.
 */
if (!function_exists('obtener_resumen_caja_diaria')) {
  function obtener_resumen_caja_diaria(mysqli $db, int $empresaId, int $cajaDiariaId): array {
    if ($cajaDiariaId <= 0) {
      return [
        'total_ventas' => 0,
        'total_bruto'  => 0.0,
        'total_pagado' => 0.0,
        'total_devuelto' => 0.0,
        'total_saldo'  => 0.0,
      ];
    }

    $sql = "SELECT
              COUNT(*) AS total_ventas,
              SUM(total) AS total_bruto,
              SUM(total_pagado) AS total_pagado,
              SUM(total_devuelto) AS total_devuelto,
              SUM(saldo) AS total_saldo
            FROM pos_ventas
            WHERE id_empresa = ? AND caja_diaria_id = ?";
    $st = $db->prepare($sql);
    $st->bind_param('ii', $empresaId, $cajaDiariaId);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc() ?: [];
    $st->close();

    return [
      'total_ventas'   => (int)($row['total_ventas'] ?? 0),
      'total_bruto'    => isset($row['total_bruto']) ? (float)$row['total_bruto'] : 0.0,
      'total_pagado'   => isset($row['total_pagado']) ? (float)$row['total_pagado'] : 0.0,
      'total_devuelto' => isset($row['total_devuelto']) ? (float)$row['total_devuelto'] : 0.0,
      'total_saldo'    => isset($row['total_saldo']) ? (float)$row['total_saldo'] : 0.0,
    ];
  }
}

/**
 * Series de comprobantes activas de la empresa.
 */
if (!function_exists('listar_series_empresa')) {
  function listar_series_empresa(mysqli $db, int $empresaId): array {
    $sql = "SELECT id, serie, tipo_comprobante
            FROM pos_series
            WHERE id_empresa = ? AND activo = 1
            ORDER BY serie";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC) ?: [];
  }
}

/**
 * Usuarios que han generado ventas en la empresa (para filtro).
 */
if (!function_exists('listar_usuarios_empresa_con_ventas')) {
  function listar_usuarios_empresa_con_ventas(mysqli $db, int $empresaId): array {
    $sql = "SELECT DISTINCT
              u.id,
              u.usuario,
              u.nombres,
              u.apellidos
            FROM pos_ventas v
            JOIN mtp_usuarios u ON u.id = v.creado_por
            WHERE v.id_empresa = ?
            ORDER BY u.nombres, u.apellidos, u.usuario";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC) ?: [];
  }
}

/**
 * Medios de pago activos (para filtro).
 */
if (!function_exists('listar_medios_pago_activos')) {
  function listar_medios_pago_activos(mysqli $db): array {
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

/** Formatea TICKET como SERIE-0001 */
if (!function_exists('ticket_string')) {
  function ticket_string(?string $serie, ?int $numero): string {
    $serie  = trim((string)$serie);
    $numero = (int)($numero ?? 0);
    if ($serie === '' && $numero === 0) {
      return '';
    }
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
    if (!$dt) {
      return '';
    }
    try {
      $d = new DateTime(is_string($dt) ? $dt : (string)$dt);
      return $d->format('d/m/Y H:i');
    } catch (Exception $e) {
      return (string)$dt;
    }
  }
}

/** Fecha a dd/mm/yyyy */
if (!function_exists('fmt_date')) {
  function fmt_date($date): string {
    if (!$date) {
      return '';
    }
    try {
      $d = new DateTime(is_string($date) ? $date : (string)$date);
      return $d->format('d/m/Y');
    } catch (Exception $e) {
      return (string)$date;
    }
  }
}
