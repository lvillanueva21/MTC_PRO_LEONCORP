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
