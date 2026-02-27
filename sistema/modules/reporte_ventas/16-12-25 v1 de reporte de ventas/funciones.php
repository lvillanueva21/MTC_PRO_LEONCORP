<?php
// modules/reportes/funciones.php
// Lógica clara y ligera para la grilla + fila secundaria (conductores, abonos con devoluciones) y helpers.

/**
 * Lista ventas de la empresa (seguras por empresa) y agrega:
 *  - servicio_principal por venta
 *  - conductores[] por venta
 *  - abonos[] por venta, cada abono con devuelto_monto (Suma de devoluciones)
 */
if (!function_exists('listar_ventas_con_detalles')) {
  function listar_ventas_con_detalles(mysqli $db, int $empresaId): array {
    // Base: ventas de la empresa, orden recientes primero
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
              c.nombre AS cliente
            FROM pos_ventas v
            LEFT JOIN pos_clientes c ON c.id = v.cliente_id
            WHERE v.id_empresa = ?
            ORDER BY v.id DESC";
    $st = $db->prepare($sql);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $res = $st->get_result();
    $ventas = $res->fetch_all(MYSQLI_ASSOC) ?: [];

    if (!$ventas) return [];

    // Indexar por id para adjuntar detalles
    $ids = [];
    foreach ($ventas as $r) { $ids[] = (int)$r['id']; }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    // ---------- Servicio principal ----------
    // Tomamos el primer detalle por venta (el de menor id) como "servicio_principal"
    $sqlS = "SELECT t.venta_id, vd.servicio_nombre
             FROM (
               SELECT venta_id, MIN(id) AS min_id
               FROM pos_venta_detalles
               WHERE venta_id IN ($in)
               GROUP BY venta_id
             ) t
             JOIN pos_venta_detalles vd
               ON vd.venta_id = t.venta_id AND vd.id = t.min_id";
    $stS = $db->prepare($sqlS);
    $paramsS = array_merge([$types], $ids);
    $refsS = [];
    foreach ($paramsS as $k => $v) { $refsS[$k] = &$paramsS[$k]; }
    call_user_func_array([$stS, 'bind_param'], $refsS);
    $stS->execute();
    $rsS = $stS->get_result();
    $svcByVenta = [];
    while ($s = $rsS->fetch_assoc()) {
      $svcByVenta[(int)$s['venta_id']] = (string)$s['servicio_nombre'];
    }

    // ---------- Conductores ----------
    $sqlC = "SELECT vc.venta_id, vc.es_principal,
                    co.doc_tipo, co.doc_numero, co.nombres, co.apellidos, co.telefono
             FROM pos_venta_conductores vc
             LEFT JOIN pos_conductores co ON co.id = vc.conductor_id
             WHERE vc.venta_id IN ($in)
             ORDER BY vc.venta_id, vc.es_principal DESC, vc.id ASC";
    $stC = $db->prepare($sqlC);
    $paramsC = array_merge([$types], $ids);
    $refsC = [];
    foreach ($paramsC as $k => $v) { $refsC[$k] = &$paramsC[$k]; }
    call_user_func_array([$stC, 'bind_param'], $refsC);
    $stC->execute();
    $rsC = $stC->get_result();
    $conductoresByVenta = [];
    while ($c = $rsC->fetch_assoc()) {
      $vid = (int)$c['venta_id'];
      if (!isset($conductoresByVenta[$vid])) $conductoresByVenta[$vid] = [];
      $conductoresByVenta[$vid][] = $c;
    }

    // ---------- Mapa de devoluciones por aplicacion_id ----------
    $sqlD = "SELECT d.abono_aplicacion_id, SUM(d.monto_devuelto) AS devuelto
             FROM pos_devoluciones d
             WHERE d.venta_id IN ($in) AND d.abono_aplicacion_id IS NOT NULL
             GROUP BY d.abono_aplicacion_id";
    $stD = $db->prepare($sqlD);
    $paramsD = array_merge([$types], $ids);
    $refsD = [];
    foreach ($paramsD as $k => $v) { $refsD[$k] = &$paramsD[$k]; }
    call_user_func_array([$stD, 'bind_param'], $refsD);
    $stD->execute();
    $rsD = $stD->get_result();
    $devByAplicacion = [];
    while ($d = $rsD->fetch_assoc()) {
      $devByAplicacion[(int)$d['abono_aplicacion_id']] = (float)$d['devuelto'];
    }

    // ---------- Abonos aplicados (con aplicacion_id) ----------
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
    $stA = $db->prepare($sqlA);
    $paramsA = array_merge([$types], $ids);
    $refsA = [];
    foreach ($paramsA as $k => $v) { $refsA[$k] = &$paramsA[$k]; }
    call_user_func_array([$stA, 'bind_param'], $refsA);
    $stA->execute();
    $rsA = $stA->get_result();
    $abonosByVenta = [];
    while ($a = $rsA->fetch_assoc()) {
      $vid = (int)$a['venta_id'];
      $aplId = (int)$a['aplicacion_id'];
      $a['devuelto_monto'] = isset($devByAplicacion[$aplId]) ? (float)$devByAplicacion[$aplId] : 0.0;
      if (!isset($abonosByVenta[$vid])) $abonosByVenta[$vid] = [];
      $abonosByVenta[$vid][] = $a;
    }

    // Adjuntar arrays/servicio a cada venta
    foreach ($ventas as &$r) {
      $vid = (int)$r['id'];
      $r['servicio_principal'] = $svcByVenta[$vid] ?? '';
      $r['conductores']        = $conductoresByVenta[$vid] ?? [];
      $r['abonos']             = $abonosByVenta[$vid] ?? [];
    }
    unset($r);

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
 * Versión paginada + con filtros de listar_ventas_con_detalles.
 * Devuelve:
 *  - rows        => array de ventas (con servicio_principal, conductores[], abonos[])
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
    if ($page < 1) $page = 1;

    $perPage = isset($opts['por_pagina']) ? (int)$opts['por_pagina'] : 10;
    $allowed = [10, 20, 50, 100];
    if (!in_array($perPage, $allowed, true)) {
      $perPage = 10;
    }

    // Filtros
    $q          = isset($opts['q']) ? trim((string)$opts['q']) : '';
    $servicioId = isset($opts['servicio_id']) ? (int)$opts['servicio_id'] : 0;
    $estado     = isset($opts['estado']) ? (string)$opts['estado'] : '';
    if (!in_array($estado, ['pagado','pendiente','anulada'], true)) {
      $estado = '';
    }
    $fdesde = isset($opts['fdesde']) ? (string)$opts['fdesde'] : '';
    $fhasta = isset($opts['fhasta']) ? (string)$opts['fhasta'] : '';

    // WHERE dinámico
    $where = ['v.id_empresa = ?'];
    $types = 'i';
    $vals  = [$empresaId];

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

    // Fecha de venta (fecha_emision, día o rango)
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
      $types .= 'ss';
      $vals[] = $fdesdeDT;
      $vals[] = $fhastaDT;
    } elseif ($fdesdeDT !== '') {
      $where[] = 'v.fecha_emision >= ?';
      $types .= 's';
      $vals[] = $fdesdeDT;
    } elseif ($fhastaDT !== '') {
      $where[] = 'v.fecha_emision <= ?';
      $types .= 's';
      $vals[] = $fhastaDT;
    }

    $whereSql = implode(' AND ', $where);

    // ---- Total de filas ----
    $sqlCount = "SELECT COUNT(*) AS total
                 FROM pos_ventas v
                 LEFT JOIN pos_clientes c ON c.id = v.cliente_id
                 WHERE $whereSql";
    $stCount = $db->prepare($sqlCount);
    $paramsCnt = array_merge([$types], $vals);
    $refsCnt = [];
    foreach ($paramsCnt as $k => $v) { $refsCnt[$k] = &$paramsCnt[$k]; }
    call_user_func_array([$stCount, 'bind_param'], $refsCnt);
    $stCount->execute();
    $rowCnt = $stCount->get_result()->fetch_assoc();
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
    if ($page > $totalPages) $page = $totalPages;
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
              c.nombre AS cliente
            FROM pos_ventas v
            LEFT JOIN pos_clientes c ON c.id = v.cliente_id
            WHERE $whereSql
            ORDER BY v.fecha_emision DESC, v.id DESC
            LIMIT ? OFFSET ?";

    $st = $db->prepare($sql);
    $typesMain  = $types . 'ii';
    $valsMain   = array_merge($vals, [$perPage, $offset]);
    $paramsMain = array_merge([$typesMain], $valsMain);
    $refsMain = [];
    foreach ($paramsMain as $k => $v) { $refsMain[$k] = &$paramsMain[$k]; }
    call_user_func_array([$st, 'bind_param'], $refsMain);
    $st->execute();
    $res = $st->get_result();
    $ventas = $res->fetch_all(MYSQLI_ASSOC) ?: [];

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

    // ---- Igual que listar_ventas_con_detalles, pero solo para las ventas de esta página ----
    $ids = [];
    foreach ($ventas as $r) { $ids[] = (int)$r['id']; }
    $in       = implode(',', array_fill(0, count($ids), '?'));
    $typesIds = str_repeat('i', count($ids));

    // Servicio principal
    $sqlS = "SELECT t.venta_id, vd.servicio_nombre
             FROM (
               SELECT venta_id, MIN(id) AS min_id
               FROM pos_venta_detalles
               WHERE venta_id IN ($in)
               GROUP BY venta_id
             ) t
             JOIN pos_venta_detalles vd
               ON vd.venta_id = t.venta_id AND vd.id = t.min_id";
    $stS = $db->prepare($sqlS);
    $paramsS = array_merge([$typesIds], $ids);
    $refsS = [];
    foreach ($paramsS as $k => $v) { $refsS[$k] = &$paramsS[$k]; }
    call_user_func_array([$stS, 'bind_param'], $refsS);
    $stS->execute();
    $rsS = $stS->get_result();
    $svcByVenta = [];
    while ($s = $rsS->fetch_assoc()) {
      $svcByVenta[(int)$s['venta_id']] = (string)$s['servicio_nombre'];
    }

    // Conductores
    $sqlC = "SELECT vc.venta_id, vc.es_principal,
                    co.doc_tipo, co.doc_numero, co.nombres, co.apellidos, co.telefono
             FROM pos_venta_conductores vc
             LEFT JOIN pos_conductores co ON co.id = vc.conductor_id
             WHERE vc.venta_id IN ($in)
             ORDER BY vc.venta_id, vc.es_principal DESC, vc.id ASC";
    $stC = $db->prepare($sqlC);
    $paramsC = array_merge([$typesIds], $ids);
    $refsC = [];
    foreach ($paramsC as $k => $v) { $refsC[$k] = &$paramsC[$k]; }
    call_user_func_array([$stC, 'bind_param'], $refsC);
    $stC->execute();
    $rsC = $stC->get_result();
    $conductoresByVenta = [];
    while ($c = $rsC->fetch_assoc()) {
      $vid = (int)$c['venta_id'];
      if (!isset($conductoresByVenta[$vid])) $conductoresByVenta[$vid] = [];
      $conductoresByVenta[$vid][] = $c;
    }

    // Devoluciones por aplicacion_id
    $sqlD = "SELECT d.abono_aplicacion_id, SUM(d.monto_devuelto) AS devuelto
             FROM pos_devoluciones d
             WHERE d.venta_id IN ($in) AND d.abono_aplicacion_id IS NOT NULL
             GROUP BY d.abono_aplicacion_id";
    $stD = $db->prepare($sqlD);
    $paramsD = array_merge([$typesIds], $ids);
    $refsD = [];
    foreach ($paramsD as $k => $v) { $refsD[$k] = &$paramsD[$k]; }
    call_user_func_array([$stD, 'bind_param'], $refsD);
    $stD->execute();
    $rsD = $stD->get_result();
    $devByAplicacion = [];
    while ($d = $rsD->fetch_assoc()) {
      $devByAplicacion[(int)$d['abono_aplicacion_id']] = (float)$d['devuelto'];
    }

    // Abonos aplicados
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
    $stA = $db->prepare($sqlA);
    $paramsA = array_merge([$typesIds], $ids);
    $refsA = [];
    foreach ($paramsA as $k => $v) { $refsA[$k] = &$paramsA[$k]; }
    call_user_func_array([$stA, 'bind_param'], $refsA);
    $stA->execute();
    $rsA = $stA->get_result();
    $abonosByVenta = [];
    while ($a = $rsA->fetch_assoc()) {
      $vid   = (int)$a['venta_id'];
      $aplId = (int)$a['aplicacion_id'];
      $a['devuelto_monto'] = isset($devByAplicacion[$aplId]) ? (float)$devByAplicacion[$aplId] : 0.0;
      if (!isset($abonosByVenta[$vid])) $abonosByVenta[$vid] = [];
      $abonosByVenta[$vid][] = $a;
    }

    foreach ($ventas as &$r) {
      $vid = (int)$r['id'];
      $r['servicio_principal'] = $svcByVenta[$vid] ?? '';
      $r['conductores']        = $conductoresByVenta[$vid] ?? [];
      $r['abonos']             = $abonosByVenta[$vid] ?? [];
    }
    unset($r);

    // Rango mostrado
    $from = ($page - 1) * $perPage + 1;
    $to   = $from + count($ventas) - 1;
    if ($from > $total) $from = $total;
    if ($to > $total)   $to   = $total;

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
              v.id, v.serie, v.numero, v.fecha_emision, v.total, v.saldo, v.estado,
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
    } catch (Exception $e) { // defensivo
      return (string)$dt;
    }
  }
}
