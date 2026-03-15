<?php
// /modules/caja/voucher_history_service.php
// Servicio compartido para construir y persistir snapshots de comprobantes.

if (!function_exists('vh_money2')) {
  function vh_money2($n): float {
    return round((float)$n, 2);
  }
}

if (!function_exists('vh_pad4')) {
  function vh_pad4($n): string {
    return str_pad((string)((int)$n), 4, '0', STR_PAD_LEFT);
  }
}

if (!function_exists('vh_row_pick')) {
  function vh_row_pick(array $row, array $keys, $default = '') {
    foreach ($keys as $key) {
      if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
        return $row[$key];
      }
    }
    return $default;
  }
}

if (!function_exists('vh_row_has_any')) {
  function vh_row_has_any(array $row, array $keys): bool {
    foreach ($keys as $key) {
      if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
        return true;
      }
    }
    return false;
  }
}

if (!function_exists('vh_user_display')) {
  function vh_user_display($usuario, $nombres, $apellidos): string {
    $full = trim((string)$nombres . ' ' . (string)$apellidos);
    if ($full !== '') return $full;
    return trim((string)$usuario);
  }
}

if (!function_exists('vh_fetch_empresa')) {
  function vh_fetch_empresa(mysqli $db, int $empId): ?array {
    $q = $db->prepare("SELECT id, nombre, razon_social, ruc, direccion, logo_path
                       FROM mtp_empresas
                       WHERE id=?
                       LIMIT 1");
    $q->bind_param('i', $empId);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
  }
}

if (!function_exists('vh_fetch_venta_head')) {
  function vh_fetch_venta_head(mysqli $db, int $empId, int $ventaId): ?array {
    $q = $db->prepare("SELECT
        v.id, v.id_empresa, v.cliente_id, v.serie, v.numero, v.fecha_emision, v.moneda,
        v.total, v.total_pagado, v.total_devuelto, v.saldo, v.estado, v.creado_por,
        v.cliente_snapshot_tipo_persona, v.cliente_snapshot_doc_tipo, v.cliente_snapshot_doc_numero, v.cliente_snapshot_nombre, v.cliente_snapshot_telefono,
        v.contratante_doc_tipo, v.contratante_doc_numero, v.contratante_nombres, v.contratante_apellidos, v.contratante_telefono,
        COALESCE(v.cliente_snapshot_doc_tipo, c.doc_tipo) AS c_doc_tipo,
        COALESCE(v.cliente_snapshot_doc_numero, c.doc_numero) AS c_doc_numero,
        COALESCE(v.cliente_snapshot_nombre, c.nombre) AS c_nombre,
        COALESCE(v.cliente_snapshot_telefono, c.telefono) AS c_telefono,
        u.usuario AS venta_user_usuario,
        u.nombres AS venta_user_nombres,
        u.apellidos AS venta_user_apellidos
      FROM pos_ventas v
      LEFT JOIN pos_clientes c ON c.id=v.cliente_id
      LEFT JOIN mtp_usuarios u ON u.id=v.creado_por
      WHERE v.id_empresa=? AND v.id=?
      LIMIT 1");
    $q->bind_param('ii', $empId, $ventaId);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
  }
}

if (!function_exists('vh_fetch_principal_conductor')) {
  function vh_fetch_principal_conductor(mysqli $db, int $ventaId): ?array {
    $q = $db->prepare("SELECT
          vc.conductor_tipo AS vc_conductor_tipo,
          vc.conductor_origen AS vc_conductor_origen,
          vc.conductor_es_mismo_cliente AS vc_conductor_es_mismo_cliente,
          vc.conductor_doc_tipo AS vc_doc_tipo,
          vc.conductor_doc_numero AS vc_doc_numero,
          vc.conductor_nombres AS vc_nombres,
          vc.conductor_apellidos AS vc_apellidos,
          vc.conductor_telefono AS vc_telefono,
          COALESCE(vc.conductor_doc_tipo, d.doc_tipo) AS d_doc_tipo,
          COALESCE(vc.conductor_doc_numero, d.doc_numero) AS d_doc_numero,
          COALESCE(vc.conductor_nombres, d.nombres) AS d_nombres,
          COALESCE(vc.conductor_apellidos, d.apellidos) AS d_apellidos,
          COALESCE(vc.conductor_telefono, d.telefono) AS d_telefono
        FROM pos_venta_conductores vc
        LEFT JOIN pos_conductores d ON d.id=vc.conductor_id
        WHERE vc.venta_id=? AND vc.es_principal=1
        LIMIT 1");
    $q->bind_param('i', $ventaId);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
  }
}

if (!function_exists('vh_resolve_conductor_payload')) {
  function vh_resolve_conductor_payload(array $venta, array $raw): array {
    $row = array_merge($venta, $raw);
    $tipoRelacion = strtoupper((string)vh_row_pick($row, ['vc_conductor_tipo', 'conductor_tipo'], ''));
    $hasConductorData = vh_row_has_any($row, [
      'vc_doc_tipo', 'vc_doc_numero', 'vc_nombres', 'vc_apellidos', 'vc_telefono',
      'd_doc_tipo', 'd_doc_numero', 'd_nombres', 'd_apellidos', 'd_telefono'
    ]);
    if ($tipoRelacion !== 'PENDIENTE' && $hasConductorData) {
      return [
        'doc_tipo'   => (string)vh_row_pick($row, ['vc_doc_tipo', 'd_doc_tipo']),
        'doc_numero' => (string)vh_row_pick($row, ['vc_doc_numero', 'd_doc_numero', 'd_doc_num']),
        'nombres'    => (string)vh_row_pick($row, ['vc_nombres', 'd_nombres']),
        'apellidos'  => (string)vh_row_pick($row, ['vc_apellidos', 'd_apellidos']),
        'telefono'   => (string)vh_row_pick($row, ['vc_telefono', 'd_telefono'])
      ];
    }
    if ($tipoRelacion === 'PENDIENTE') {
      return [
        'doc_tipo'   => '',
        'doc_numero' => '',
        'nombres'    => 'Pendiente de definir',
        'apellidos'  => '',
        'telefono'   => ''
      ];
    }
    if (vh_row_has_any($row, ['contratante_doc_tipo', 'contratante_doc_numero', 'contratante_nombres', 'contratante_apellidos'])) {
      return [
        'doc_tipo'   => (string)vh_row_pick($row, ['contratante_doc_tipo']),
        'doc_numero' => (string)vh_row_pick($row, ['contratante_doc_numero']),
        'nombres'    => (string)vh_row_pick($row, ['contratante_nombres']),
        'apellidos'  => (string)vh_row_pick($row, ['contratante_apellidos']),
        'telefono'   => (string)vh_row_pick($row, ['contratante_telefono'])
      ];
    }
    return [
      'doc_tipo'   => (string)vh_row_pick($row, ['c_doc_tipo']),
      'doc_numero' => (string)vh_row_pick($row, ['c_doc_numero']),
      'nombres'    => (string)vh_row_pick($row, ['c_nombre']),
      'apellidos'  => '',
      'telefono'   => (string)vh_row_pick($row, ['c_telefono'])
    ];
  }
}

if (!function_exists('vh_fetch_items')) {
  function vh_fetch_items(mysqli $db, int $ventaId): array {
    $q = $db->prepare("SELECT servicio_nombre, cantidad, precio_unitario, total_linea
                       FROM pos_venta_detalles
                       WHERE venta_id=?
                       ORDER BY id ASC");
    $q->bind_param('i', $ventaId);
    $q->execute();
    return $q->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  }
}

if (!function_exists('vh_prepare_bind_params')) {
  function vh_prepare_bind_params(array &$params): array {
    $refs = [];
    foreach ($params as $k => $v) {
      $refs[$k] = &$params[$k];
    }
    return $refs;
  }
}

if (!function_exists('vh_fetch_abonos')) {
  function vh_fetch_abonos(mysqli $db, int $ventaId, array $abonoIds = []): array {
    $whereExtra = '';
    $types = 'i';
    $values = [$ventaId];
    if ($abonoIds) {
      $abonoIds = array_values(array_unique(array_map('intval', $abonoIds)));
      $abonoIds = array_values(array_filter($abonoIds, function($x){ return $x > 0; }));
      if ($abonoIds) {
        $whereExtra = ' AND a.id IN (' . implode(',', array_fill(0, count($abonoIds), '?')) . ')';
        $types .= str_repeat('i', count($abonoIds));
        foreach ($abonoIds as $aid) $values[] = $aid;
      }
    }

    $sql = "SELECT
              apl.id AS aplicacion_id,
              a.id AS abono_id,
              apl.venta_id,
              a.fecha,
              a.monto,
              apl.monto_aplicado,
              COALESCE(SUM(d.monto_devuelto),0) AS monto_devuelto,
              a.referencia,
              mp.nombre AS medio,
              a.creado_por,
              u.usuario AS creado_usuario,
              u.nombres AS creado_nombres,
              u.apellidos AS creado_apellidos
            FROM pos_abono_aplicaciones apl
            JOIN pos_abonos a ON a.id=apl.abono_id
            LEFT JOIN pos_medios_pago mp ON mp.id=a.medio_id
            LEFT JOIN mtp_usuarios u ON u.id=a.creado_por
            LEFT JOIN pos_devoluciones d ON d.abono_aplicacion_id=apl.id
            WHERE apl.venta_id=? $whereExtra
            GROUP BY
              apl.id,
              a.id,
              apl.venta_id,
              a.fecha,
              a.monto,
              apl.monto_aplicado,
              a.referencia,
              mp.nombre,
              a.creado_por,
              u.usuario,
              u.nombres,
              u.apellidos
            ORDER BY a.fecha ASC, a.id ASC";

    $q = $db->prepare($sql);
    $params = array_merge([$types], $values);
    $refs = vh_prepare_bind_params($params);
    call_user_func_array([$q, 'bind_param'], $refs);
    $q->execute();
    return $q->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  }
}

if (!function_exists('vh_resolve_venta_id_by_abono')) {
  function vh_resolve_venta_id_by_abono(mysqli $db, int $empId, int $abonoId): int {
    $q = $db->prepare("SELECT apl.venta_id
                       FROM pos_abono_aplicaciones apl
                       JOIN pos_abonos a ON a.id=apl.abono_id
                       WHERE apl.abono_id=? AND a.id_empresa=?
                       ORDER BY apl.id ASC
                       LIMIT 1");
    $q->bind_param('ii', $abonoId, $empId);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    return $r ? (int)$r['venta_id'] : 0;
  }
}

if (!function_exists('vh_build_payload')) {
  function vh_build_payload(
    mysqli $db,
    int $empId,
    int $ventaId,
    string $kind = 'venta',
    array $abonoIds = [],
    string $scope = 'actual',
    string $exactitud = 'EXACTO',
    ?array $reimpresor = null
  ): array {
    $kind = strtolower(trim($kind)) === 'abono' ? 'abono' : 'venta';
    $scope = strtolower(trim($scope)) === 'original' ? 'original' : 'actual';
    $exactitud = strtoupper(trim($exactitud)) === 'APROXIMADO' ? 'APROXIMADO' : 'EXACTO';

    $venta = vh_fetch_venta_head($db, $empId, $ventaId);
    if (!$venta) throw new RuntimeException('Venta no encontrada para construir comprobante.');

    $empresa = vh_fetch_empresa($db, $empId) ?: [
      'nombre' => '',
      'razon_social' => '',
      'ruc' => '',
      'direccion' => '',
      'logo_path' => ''
    ];

    $condRaw = vh_fetch_principal_conductor($db, $ventaId) ?: [];
    $cond = vh_resolve_conductor_payload($venta, $condRaw);

    $items = [];
    foreach (vh_fetch_items($db, $ventaId) as $it) {
      $items[] = [
        'nombre' => (string)($it['servicio_nombre'] ?? ''),
        'cantidad' => (float)($it['cantidad'] ?? 0),
        'precio' => (float)($it['precio_unitario'] ?? 0),
        'total' => (float)($it['total_linea'] ?? 0)
      ];
    }

    $abonosRaw = vh_fetch_abonos($db, $ventaId, ($kind === 'abono') ? $abonoIds : []);
    if ($kind === 'abono' && !count($abonosRaw)) {
      throw new RuntimeException('No se encontraron abonos para el comprobante.');
    }

    $abonos = [];
    foreach ($abonosRaw as $ab) {
      $apl = (float)($ab['monto_aplicado'] ?? 0);
      $dev = (float)($ab['monto_devuelto'] ?? 0);
      $net = max(0.0, vh_money2($apl - $dev));
      $displayMonto = ($scope === 'actual') ? $net : $apl;

      $estadoCode = 'APLICADO';
      $estadoText = 'Aplicado';
      if ($dev > 0 && $dev >= ($apl - 0.01)) {
        $estadoCode = 'DEVUELTO_TOTAL';
        $estadoText = 'Devuelto total';
      } elseif ($dev > 0) {
        $estadoCode = 'DEVUELTO_PARCIAL';
        $estadoText = 'Devuelto parcial';
      }

      $abonos[] = [
        'abono_id' => (int)($ab['abono_id'] ?? 0),
        'aplicacion_id' => (int)($ab['aplicacion_id'] ?? 0),
        'medio' => (string)($ab['medio'] ?? '—'),
        'referencia' => (string)($ab['referencia'] ?? ''),
        'monto' => vh_money2($displayMonto),
        'monto_aplicado' => vh_money2($apl),
        'monto_devuelto' => vh_money2($dev),
        'monto_neto' => vh_money2($net),
        'estado_code' => $estadoCode,
        'estado_text' => $estadoText,
        'fecha' => (string)($ab['fecha'] ?? ''),
        'creado_por' => (int)($ab['creado_por'] ?? 0),
        'creado_usuario' => (string)($ab['creado_usuario'] ?? ''),
        'creado_nombre' => vh_user_display($ab['creado_usuario'] ?? '', $ab['creado_nombres'] ?? '', $ab['creado_apellidos'] ?? '')
      ];
    }

    $ticketCode = (string)$venta['serie'] . '-' . vh_pad4((int)$venta['numero']);
    $fechaVenta = (string)($venta['fecha_emision'] ?? '');
    $fechaDoc = $fechaVenta;
    if ($kind === 'abono' && count($abonosRaw)) {
      $lastAb = end($abonosRaw);
      if (is_array($lastAb) && !empty($lastAb['fecha'])) {
        $fechaDoc = (string)$lastAb['fecha'];
      }
    }

    $opUserId = (int)($venta['creado_por'] ?? 0);
    $opUserUsuario = (string)($venta['venta_user_usuario'] ?? '');
    $opUserNombre = vh_user_display(
      $opUserUsuario,
      (string)($venta['venta_user_nombres'] ?? ''),
      (string)($venta['venta_user_apellidos'] ?? '')
    );
    if ($kind === 'abono' && count($abonos)) {
      $lastAbono = end($abonos);
      if (is_array($lastAbono) && (int)($lastAbono['creado_por'] ?? 0) > 0) {
        $opUserId = (int)$lastAbono['creado_por'];
        $opUserUsuario = (string)($lastAbono['creado_usuario'] ?? '');
        $opUserNombre = (string)($lastAbono['creado_nombre'] ?? $opUserNombre);
      }
    }

    $clienteDocTipo = (string)($venta['c_doc_tipo'] ?? '');
    $clienteDocNum = (string)($venta['c_doc_numero'] ?? '');
    $clienteNombre = trim((string)($venta['c_nombre'] ?? ''));
    $clienteTelefono = trim((string)($venta['c_telefono'] ?? ''));
    $clienteDoc = trim($clienteDocTipo . ' ' . $clienteDocNum);

    $contrDocTipo = (string)($venta['contratante_doc_tipo'] ?? '');
    $contrDocNum = (string)($venta['contratante_doc_numero'] ?? '');
    $contrNombre = trim(((string)($venta['contratante_nombres'] ?? '')) . ' ' . ((string)($venta['contratante_apellidos'] ?? '')));
    $contrTelefono = trim((string)($venta['contratante_telefono'] ?? ''));
    $contrDoc = trim($contrDocTipo . ' ' . $contrDocNum);

    $conductorDoc = trim(((string)($cond['doc_tipo'] ?? '')) . ' ' . ((string)($cond['doc_numero'] ?? '')));
    $conductorNombre = trim(((string)($cond['nombres'] ?? '')) . ' ' . ((string)($cond['apellidos'] ?? '')));
    if ($conductorNombre === '') $conductorNombre = 'No especificado';

    $reimpresoPorId = (int)($reimpresor['id'] ?? 0);
    $reimpresoPorUsuario = (string)($reimpresor['usuario'] ?? '');
    $reimpresoPorNombre = trim((string)($reimpresor['nombre'] ?? ''));
    if ($reimpresoPorNombre === '') {
      $reimpresoPorNombre = vh_user_display($reimpresoPorUsuario, '', '');
    }

    $totales = [
      'total' => (float)($venta['total'] ?? 0),
      'pagado' => (float)($venta['total_pagado'] ?? 0),
      'saldo' => (float)($venta['saldo'] ?? 0),
      'devuelto' => (float)($venta['total_devuelto'] ?? 0)
    ];

    return [
      'version' => 1,
      'kind' => $kind,
      'scope' => $scope,
      'exactitud' => $exactitud,
      'empresa' => [
        'nombre' => (string)($empresa['nombre'] ?? ''),
        'razon_social' => (string)($empresa['razon_social'] ?? ''),
        'ruc' => (string)($empresa['ruc'] ?? ''),
        'direccion' => (string)($empresa['direccion'] ?? ''),
        'logo_path' => (string)($empresa['logo_path'] ?? '')
      ],
      'meta' => [
        'ticket' => $ticketCode,
        'serie' => (string)($venta['serie'] ?? ''),
        'numero' => (int)($venta['numero'] ?? 0),
        'fecha_raw' => $fechaDoc,
        'fecha_venta_raw' => $fechaVenta,
        'alcance' => $scope,
        'alcance_label' => ($scope === 'original') ? 'ORIGINAL' : 'ACTUAL',
        'estado_venta' => (string)($venta['estado'] ?? ''),
        'exactitud' => $exactitud,
        'cajero_operacion_id' => $opUserId,
        'cajero_operacion_usuario' => $opUserUsuario,
        'cajero_operacion_nombre' => $opUserNombre,
        'reimpreso_por_id' => $reimpresoPorId,
        'reimpreso_por_usuario' => $reimpresoPorUsuario,
        'reimpreso_por_nombre' => $reimpresoPorNombre
      ],
      'cliente' => [
        'tipo_persona' => (string)($venta['cliente_snapshot_tipo_persona'] ?? ''),
        'doc_tipo' => $clienteDocTipo,
        'doc_numero' => $clienteDocNum,
        'doc' => $clienteDoc,
        'nombre' => $clienteNombre,
        'telefono' => $clienteTelefono
      ],
      'contratante' => [
        'doc_tipo' => $contrDocTipo,
        'doc_numero' => $contrDocNum,
        'doc' => $contrDoc,
        'nombre' => $contrNombre,
        'telefono' => $contrTelefono
      ],
      'conductor' => [
        'doc_tipo' => (string)($cond['doc_tipo'] ?? ''),
        'doc_numero' => (string)($cond['doc_numero'] ?? ''),
        'doc' => $conductorDoc,
        'nombre' => $conductorNombre,
        'telefono' => (string)($cond['telefono'] ?? '')
      ],
      'items' => $items,
      'abonos' => $abonos,
      'totales' => $totales,
      'refs' => [
        'venta_id' => $ventaId,
        'abono_ids' => array_values(array_map(function($x){ return (int)$x['abono_id']; }, $abonos))
      ]
    ];
  }
}

if (!function_exists('vh_save_original_snapshot')) {
  function vh_save_original_snapshot(
    mysqli $db,
    int $empId,
    string $tipo,
    ?int $ventaId,
    array $payload,
    int $emitidoPorId,
    string $emitidoUsuario,
    string $emitidoNombre,
    string $formatoDefault = 'ticket80',
    string $exactitud = 'EXACTO',
    ?string $observacion = null
  ): int {
    $tipoDb = strtoupper(trim($tipo)) === 'ABONO' ? 'ABONO' : 'VENTA';
    $modo = 'ORIGINAL';
    $exactitudDb = strtoupper(trim($exactitud)) === 'APROXIMADO' ? 'APROXIMADO' : 'EXACTO';

    $meta = $payload['meta'] ?? [];
    $ticketSerie = (string)($meta['serie'] ?? '');
    $ticketNumero = (int)($meta['numero'] ?? 0);
    $ticketCodigo = trim((string)($meta['ticket'] ?? ''));
    if ($ticketCodigo === '' && $ticketSerie !== '' && $ticketNumero > 0) {
      $ticketCodigo = $ticketSerie . '-' . vh_pad4($ticketNumero);
    }
    if ($ticketSerie === '' && $ticketCodigo !== '') {
      $parts = explode('-', $ticketCodigo, 2);
      $ticketSerie = (string)($parts[0] ?? '');
      $ticketNumero = isset($parts[1]) ? (int)$parts[1] : $ticketNumero;
    }
    if ($ticketSerie === '' || $ticketNumero <= 0 || $ticketCodigo === '') {
      throw new RuntimeException('Snapshot invalido: ticket incompleto.');
    }

    $emitidoEn = (string)($meta['fecha_raw'] ?? '');
    if ($emitidoEn === '') $emitidoEn = date('Y-m-d H:i:s');

    $snapshotJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($snapshotJson) || $snapshotJson === '') {
      throw new RuntimeException('No se pudo serializar snapshot del comprobante.');
    }

    $ventaIdDb = ($ventaId !== null && $ventaId > 0) ? (int)$ventaId : null;
    $emitidoPorDb = $emitidoPorId > 0 ? $emitidoPorId : null;
    $emitidoUsuarioDb = trim((string)$emitidoUsuario) ?: null;
    $emitidoNombreDb = trim((string)$emitidoNombre) ?: null;
    $formatoDb = in_array($formatoDefault, ['ticket80', 'ticket58', 'a4'], true) ? $formatoDefault : 'ticket80';
    $observacionDb = $observacion !== null ? trim((string)$observacion) : null;
    if ($observacionDb === '') $observacionDb = null;

    $ins = $db->prepare("INSERT INTO pos_comprobantes(
        id_empresa, tipo, modo, venta_id,
        ticket_serie, ticket_numero, ticket_codigo,
        emitido_en, emitido_por, emitido_por_usuario, emitido_por_nombre,
        formato_default, snapshot_json, exactitud, observacion
      ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?
      )");
    $ins->bind_param(
      'issisississssss',
      $empId,
      $tipoDb,
      $modo,
      $ventaIdDb,
      $ticketSerie,
      $ticketNumero,
      $ticketCodigo,
      $emitidoEn,
      $emitidoPorDb,
      $emitidoUsuarioDb,
      $emitidoNombreDb,
      $formatoDb,
      $snapshotJson,
      $exactitudDb,
      $observacionDb
    );
    $ins->execute();
    return (int)$db->insert_id;
  }
}

if (!function_exists('vh_link_snapshot_abonos')) {
  function vh_link_snapshot_abonos(mysqli $db, int $comprobanteId, int $ventaId, array $abonos): void {
    if ($comprobanteId <= 0 || $ventaId <= 0 || !$abonos) return;
    $ins = $db->prepare("INSERT IGNORE INTO pos_comprobante_abonos(comprobante_id, abono_id, venta_id, monto_aplicado_snapshot)
                         VALUES (?,?,?,?)");
    foreach ($abonos as $ab) {
      $abonoId = (int)($ab['abono_id'] ?? 0);
      if ($abonoId <= 0) continue;
      $montoApl = (float)($ab['monto_aplicado'] ?? $ab['monto'] ?? 0);
      $ins->bind_param('iiid', $comprobanteId, $abonoId, $ventaId, $montoApl);
      $ins->execute();
    }
  }
}

if (!function_exists('vh_find_original_snapshot_venta')) {
  function vh_find_original_snapshot_venta(mysqli $db, int $empId, int $ventaId): ?array {
    $q = $db->prepare("SELECT *
                       FROM pos_comprobantes
                       WHERE id_empresa=? AND tipo='VENTA' AND modo='ORIGINAL' AND venta_id=?
                       ORDER BY emitido_en ASC, id ASC
                       LIMIT 1");
    $q->bind_param('ii', $empId, $ventaId);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
  }
}

if (!function_exists('vh_find_original_snapshot_abono')) {
  function vh_find_original_snapshot_abono(mysqli $db, int $empId, int $abonoId): ?array {
    $q = $db->prepare("SELECT pc.*
                       FROM pos_comprobante_abonos pca
                       JOIN pos_comprobantes pc ON pc.id=pca.comprobante_id
                       WHERE pc.id_empresa=? AND pc.tipo='ABONO' AND pc.modo='ORIGINAL' AND pca.abono_id=?
                       ORDER BY pc.emitido_en ASC, pc.id ASC
                       LIMIT 1");
    $q->bind_param('ii', $empId, $abonoId);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
  }
}

if (!function_exists('vh_decode_snapshot_payload')) {
  function vh_decode_snapshot_payload(?array $row): ?array {
    if (!$row) return null;
    $raw = (string)($row['snapshot_json'] ?? '');
    if ($raw === '') return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
  }
}

