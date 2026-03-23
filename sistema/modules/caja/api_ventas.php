<?php
// /modules/caja/api_ventas.php
// API para "Ventas pendientes": buscar ventas, ver detalle, registrar abonos,
// devolucion total de venta y devolucion por abono.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/voucher_history_service.php';

$ALLOWED_ROLE_IDS = [3,4]; // Recepción (3) o Administración (4)
$CONTROL_SPECIAL_SLUG = 'caja';

$hasNormalRoleByAcl = acl_can_ids($ALLOWED_ROLE_IDS);
acl_require_ids_or_control_special($ALLOWED_ROLE_IDS, $CONTROL_SPECIAL_SLUG);
if ($hasNormalRoleByAcl) {
  verificarPermiso(['Recepción','Administración']);
}

/* ===== Config MySQLi estricto + TZ Lima ===== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');
if (function_exists('date_default_timezone_set')) { date_default_timezone_set('America/Lima'); }
try { $db->query("SET time_zone = 'America/Lima'"); }
catch (Throwable $e) { $db->query("SET time_zone = '-05:00'"); }

/* ===== Helpers JSON ===== */
function json_ok($data=[]){ echo json_encode(['ok'=>true]+$data, JSON_UNESCAPED_UNICODE); exit; }
function json_err_code($code, $msg, $extra=[]){
  http_response_code($code);
  echo json_encode(['ok'=>false,'error'=>$msg]+$extra, JSON_UNESCAPED_UNICODE); exit;
}
function json_err($msg,$extra=[]){ json_err_code(400,$msg,$extra); }
function like_wrap($s){ $s = trim((string)$s); return "%{$s}%"; }
function money2($n){ return round((float)$n, 2); }
function pad4($n){ $n=(int)$n; return str_pad((string)$n,4,'0',STR_PAD_LEFT); }

/* ===== Usuario y empresa ===== */
$u       = currentUser();
$uid     = (int)($u['id'] ?? 0);
$empId   = (int)($u['empresa']['id'] ?? 0);
if ($empId <= 0) json_err('Empresa no asignada.');

/* ===== Utilidades mínimas reutilizadas ===== */
function getDiariaAbierta(mysqli $db, int $empId){
  $st=$db->prepare("SELECT * FROM mod_caja_diaria WHERE id_empresa=? AND estado='abierta' LIMIT 1");
  $st->bind_param('i',$empId); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}
function getUltimaCajaDiaria(mysqli $db, int $empId){
  $st = $db->prepare("SELECT id, fecha, codigo, estado
                      FROM mod_caja_diaria
                      WHERE id_empresa=?
                      ORDER BY fecha DESC, id DESC
                      LIMIT 1");
  $st->bind_param('i',$empId); $st->execute();
  return $st->get_result()->fetch_assoc() ?: null;
}
function is_valid_ymd($value){
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) return false;
  $dt = DateTime::createFromFormat('Y-m-d', (string)$value);
  return $dt && $dt->format('Y-m-d') === (string)$value;
}
function human_date($value){
  if (!is_valid_ymd($value)) return (string)$value;
  $dt = DateTime::createFromFormat('Y-m-d', (string)$value);
  return $dt ? $dt->format('d/m/Y') : (string)$value;
}
function row_pick(array $row, array $keys, $default = ''){
  foreach ($keys as $key) {
    if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
      return $row[$key];
    }
  }
  return $default;
}
function row_has_any(array $row, array $keys): bool {
  foreach ($keys as $key) {
    if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
      return true;
    }
  }
  return false;
}
function resolve_conductor_payload(array $row): array {
  $tipoRelacion = strtoupper((string)row_pick($row, ['vc_conductor_tipo', 'conductor_tipo'], ''));
  $hasConductorData = row_has_any($row, [
    'vc_doc_tipo', 'vc_doc_numero', 'vc_nombres', 'vc_apellidos', 'vc_telefono',
    'd_doc_tipo', 'd_doc_numero', 'd_nombres', 'd_apellidos', 'd_telefono'
  ]);
  if ($tipoRelacion !== 'PENDIENTE' && $hasConductorData) {
    return [
      'doc_tipo'   => (string)row_pick($row, ['vc_doc_tipo', 'd_doc_tipo']),
      'doc_numero' => (string)row_pick($row, ['vc_doc_numero', 'd_doc_numero', 'd_doc_num']),
      'nombres'    => (string)row_pick($row, ['vc_nombres', 'd_nombres']),
      'apellidos'  => (string)row_pick($row, ['vc_apellidos', 'd_apellidos']),
      'telefono'   => (string)row_pick($row, ['vc_telefono', 'd_telefono'])
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
  if (row_has_any($row, ['contratante_doc_tipo', 'ct_doc_tipo', 'contratante_doc_numero', 'ct_doc_num', 'contratante_nombres', 'ct_nombres', 'contratante_apellidos', 'ct_apellidos'])) {
    return [
      'doc_tipo'   => (string)row_pick($row, ['contratante_doc_tipo', 'ct_doc_tipo']),
      'doc_numero' => (string)row_pick($row, ['contratante_doc_numero', 'ct_doc_num']),
      'nombres'    => (string)row_pick($row, ['contratante_nombres', 'ct_nombres']),
      'apellidos'  => (string)row_pick($row, ['contratante_apellidos', 'ct_apellidos']),
      'telefono'   => (string)row_pick($row, ['contratante_telefono', 'ct_telefono'])
    ];
  }
  return [
    'doc_tipo'   => (string)row_pick($row, ['c_doc_tipo']),
    'doc_numero' => (string)row_pick($row, ['c_doc_numero', 'c_doc_num']),
    'nombres'    => (string)row_pick($row, ['c_nombre']),
    'apellidos'  => '',
    'telefono'   => (string)row_pick($row, ['c_telefono'])
  ];
}
function conductor_display(array $cond): string {
  $doc = trim((string)(($cond['doc_tipo'] ?? '') . ' ' . ($cond['doc_numero'] ?? '')));
  $nom = trim((string)(($cond['nombres'] ?? '') . ' ' . ($cond['apellidos'] ?? '')));
  if ($doc !== '' && $nom !== '') return $doc . ' • ' . $nom;
  if ($doc !== '') return $doc;
  return $nom;
}
function map_medios_pago_activos(mysqli $db): array {
  $rs = $db->query("SELECT id, nombre, requiere_ref FROM pos_medios_pago WHERE activo=1");
  $out = [];
  foreach($rs->fetch_all(MYSQLI_ASSOC) as $r){
    $out[(int)$r['id']] = ['id'=>(int)$r['id'],'nombre'=>$r['nombre'],'requiere_ref'=>(int)$r['requiere_ref']===1];
  }
  return $out;
}

/* ===== Utilidades de dominio ===== */
function fetch_venta_head(mysqli $db, int $empId, int $ventaId){
  $q = $db->prepare("SELECT
      v.id, v.id_empresa, v.cliente_id, v.serie, v.numero, v.fecha_emision, v.moneda,
      v.total, v.total_pagado, v.total_devuelto, v.saldo, v.estado,
      v.cliente_snapshot_tipo_persona, v.cliente_snapshot_doc_tipo, v.cliente_snapshot_doc_numero, v.cliente_snapshot_nombre, v.cliente_snapshot_telefono,
      v.contratante_doc_tipo, v.contratante_doc_numero, v.contratante_nombres, v.contratante_apellidos, v.contratante_telefono,
      COALESCE(v.cliente_snapshot_doc_tipo, c.doc_tipo) AS c_doc_tipo,
      COALESCE(v.cliente_snapshot_doc_numero, c.doc_numero) AS c_doc_numero,
      COALESCE(v.cliente_snapshot_nombre, c.nombre) AS c_nombre,
      COALESCE(v.cliente_snapshot_telefono, c.telefono) AS c_telefono
    FROM pos_ventas v
    LEFT JOIN pos_clientes c ON c.id=v.cliente_id
    WHERE v.id_empresa=? AND v.id=? LIMIT 1");
  $q->bind_param('ii',$empId,$ventaId); $q->execute();
  return $q->get_result()->fetch_assoc() ?: null;
}

function fetch_principal_conductor(mysqli $db, int $ventaId){
  $q = $db->prepare("SELECT vc.conductor_tipo AS vc_conductor_tipo,
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
  $q->bind_param('i',$ventaId); $q->execute();
  return $q->get_result()->fetch_assoc() ?: null;
}

function venta_get_aplicado_total(mysqli $db, int $ventaId): float {
  $q = $db->prepare("SELECT COALESCE(SUM(monto_aplicado),0) AS aplicado
                     FROM pos_abono_aplicaciones
                     WHERE venta_id=?");
  $q->bind_param('i', $ventaId);
  $q->execute();
  $row = $q->get_result()->fetch_assoc() ?: [];
  return money2((float)($row['aplicado'] ?? 0));
}

function venta_get_devuelto_total(mysqli $db, int $ventaId): float {
  $q = $db->prepare("SELECT COALESCE(SUM(monto_devuelto),0) AS devuelto
                     FROM pos_devoluciones
                     WHERE venta_id=?");
  $q->bind_param('i', $ventaId);
  $q->execute();
  $row = $q->get_result()->fetch_assoc() ?: [];
  return money2((float)($row['devuelto'] ?? 0));
}

function venta_recalcular_totales(mysqli $db, int $ventaId, float $totalVenta, bool $forzarAnulada = false): array {
  $aplicado = venta_get_aplicado_total($db, $ventaId);
  $devuelto = venta_get_devuelto_total($db, $ventaId);
  $pagadoNeto = max(0.00, money2($aplicado - $devuelto));

  if ($forzarAnulada) {
    return [
      'pagado' => 0.00,
      'saldo' => 0.00,
      'devuelto_total' => $devuelto,
      'aplicado_total' => $aplicado
    ];
  }

  $saldo = max(0.00, money2($totalVenta - $pagadoNeto));
  return [
    'pagado' => $pagadoNeto,
    'saldo' => $saldo,
    'devuelto_total' => $devuelto,
    'aplicado_total' => $aplicado
  ];
}

function venta_build_estado_visual(string $estadoVenta, float $saldo, float $devueltoTotal): array {
  $eps = 0.000001;
  if (strtoupper($estadoVenta) === 'ANULADA') {
    return ['code' => 'refund_total', 'text' => 'Devolucion total'];
  }
  if ($devueltoTotal > $eps) {
    return ['code' => 'refund_partial', 'text' => 'Devolucion parcial'];
  }
  if ($saldo > $eps) {
    return ['code' => 'pending', 'text' => 'Pendiente'];
  }
  return ['code' => 'paid', 'text' => 'Pagado'];
}

function voucher_norm_size($size): string {
  $s = strtolower(trim((string)$size));
  if (in_array($s, ['ticket58', 't58', '58', '58mm'], true)) return 'ticket58';
  if (in_array($s, ['a4'], true)) return 'a4';
  return 'ticket80';
}

function voucher_parse_ids_csv($value): array {
  $raw = trim((string)$value);
  if ($raw === '') return [];
  $parts = preg_split('/\s*,\s*/', $raw);
  $out = [];
  foreach ($parts as $p) {
    $id = (int)$p;
    if ($id > 0) $out[$id] = $id;
  }
  return array_values($out);
}

function voucher_norm_scope($scope): string {
  $s = strtolower(trim((string)$scope));
  return ($s === 'original') ? 'original' : 'actual';
}

function voucher_norm_presentation($presentation): string {
  $s = strtolower(trim((string)$presentation));
  return ($s === 'cliente') ? 'cliente' : 'auditoria';
}

function voucher_payload_to_render_data(array $payload, int $empId, mysqli $db): array {
  $empresaRaw = $payload['empresa'] ?? [];
  $metaRaw = $payload['meta'] ?? [];
  $clienteRaw = $payload['cliente'] ?? [];
  $contrRaw = $payload['contratante'] ?? [];
  $condRaw = $payload['conductor'] ?? [];

  $empresa = [
    'nombre' => (string)($empresaRaw['nombre'] ?? ''),
    'razon_social' => (string)($empresaRaw['razon_social'] ?? ''),
    'ruc' => (string)($empresaRaw['ruc'] ?? ''),
    'direccion' => (string)($empresaRaw['direccion'] ?? ''),
    'logo_data_uri' => voucher_logo_data_uri((string)($empresaRaw['logo_path'] ?? ''))
  ];
  if ($empresa['nombre'] === '' && $empresa['razon_social'] === '' && $empresa['ruc'] === '' && $empresa['direccion'] === '') {
    $empDb = vh_fetch_empresa($db, $empId);
    if ($empDb) {
      $empresa = [
        'nombre' => (string)($empDb['nombre'] ?? ''),
        'razon_social' => (string)($empDb['razon_social'] ?? ''),
        'ruc' => (string)($empDb['ruc'] ?? ''),
        'direccion' => (string)($empDb['direccion'] ?? ''),
        'logo_data_uri' => voucher_logo_data_uri((string)($empDb['logo_path'] ?? ''))
      ];
    }
  }

  $items = [];
  foreach (($payload['items'] ?? []) as $it) {
    $items[] = [
      'nombre' => (string)($it['nombre'] ?? ''),
      'cantidad' => (float)($it['cantidad'] ?? 0),
      'precio' => (float)($it['precio'] ?? 0),
      'total' => (float)($it['total'] ?? 0)
    ];
  }

  $abonos = [];
  foreach (($payload['abonos'] ?? []) as $ab) {
    $abonos[] = [
      'abono_id' => (int)($ab['abono_id'] ?? 0),
      'medio' => (string)($ab['medio'] ?? '—'),
      'referencia' => (string)($ab['referencia'] ?? ''),
      'monto' => (float)($ab['monto'] ?? 0),
      'fecha' => (string)($ab['fecha'] ?? ''),
      'estado_code' => (string)($ab['estado_code'] ?? ''),
      'estado_text' => (string)($ab['estado_text'] ?? ''),
      'monto_aplicado' => (float)($ab['monto_aplicado'] ?? 0),
      'monto_devuelto' => (float)($ab['monto_devuelto'] ?? 0),
      'monto_neto' => (float)($ab['monto_neto'] ?? 0)
    ];
  }

  $cajeroOperacion = trim((string)($metaRaw['cajero_operacion_nombre'] ?? ''));
  if ($cajeroOperacion === '') {
    $cajeroOperacion = trim((string)($metaRaw['cajero_operacion_usuario'] ?? ''));
  }
  if ($cajeroOperacion === '') $cajeroOperacion = 'Usuario';

  $reimpresoPor = trim((string)($metaRaw['reimpreso_por_nombre'] ?? ''));
  if ($reimpresoPor === '') {
    $reimpresoPor = trim((string)($metaRaw['reimpreso_por_usuario'] ?? ''));
  }

  $fechaRaw = (string)($metaRaw['fecha_raw'] ?? '');
  $fechaVentaRaw = (string)($metaRaw['fecha_venta_raw'] ?? '');
  $ticket = (string)($metaRaw['ticket'] ?? '');
  $alcanceLabel = strtoupper((string)($metaRaw['alcance_label'] ?? ''));
  if ($alcanceLabel === '') {
    $alcance = strtolower((string)($payload['scope'] ?? 'actual'));
    $alcanceLabel = ($alcance === 'original') ? 'ORIGINAL' : 'ACTUAL';
  }
  $exactitud = strtoupper((string)($metaRaw['exactitud'] ?? ($payload['exactitud'] ?? 'EXACTO')));
  if (!in_array($exactitud, ['EXACTO', 'APROXIMADO'], true)) $exactitud = 'EXACTO';

  $estadoVenta = strtoupper(trim((string)($metaRaw['estado_venta'] ?? '')));
  $totDevMeta = (float)($payload['totales']['devuelto'] ?? 0);
  if ($estadoVenta === 'ANULADA') {
    $estadoVentaTexto = 'Devolucion total';
  } elseif ($totDevMeta > 0.000001) {
    $estadoVentaTexto = 'Devolucion parcial';
  } else {
    $estadoVentaTexto = 'Emitida';
  }

  return [
    'empresa' => $empresa,
    'meta' => [
      'ticket' => $ticket,
      'fecha' => voucher_fmt_dt($fechaRaw),
      'fecha_venta' => voucher_fmt_dt($fechaVentaRaw),
      'cajero' => $cajeroOperacion,
      'reimpreso_por' => $reimpresoPor,
      'alcance_label' => $alcanceLabel,
      'exactitud' => $exactitud,
      'estado_venta' => $estadoVenta,
      'estado_venta_texto' => $estadoVentaTexto
    ],
    'cliente' => [
      'doc' => (string)($clienteRaw['doc'] ?? ''),
      'nombre' => (string)($clienteRaw['nombre'] ?? ''),
      'telefono' => (string)($clienteRaw['telefono'] ?? '')
    ],
    'contratante' => [
      'doc' => (string)($contrRaw['doc'] ?? ''),
      'nombre' => (string)($contrRaw['nombre'] ?? ''),
      'telefono' => (string)($contrRaw['telefono'] ?? '')
    ],
    'conductor' => [
      'doc' => (string)($condRaw['doc'] ?? ''),
      'nombre' => (string)($condRaw['nombre'] ?? ''),
      'telefono' => (string)($condRaw['telefono'] ?? '')
    ],
    'items' => $items,
    'abonos' => $abonos,
    'totales' => [
      'total' => (float)($payload['totales']['total'] ?? 0),
      'pagado' => (float)($payload['totales']['pagado'] ?? 0),
      'saldo' => (float)($payload['totales']['saldo'] ?? 0),
      'devuelto' => (float)($payload['totales']['devuelto'] ?? 0)
    ],
    'refs' => [
      'venta_id' => (int)($payload['refs']['venta_id'] ?? 0),
      'abono_ids' => array_values(array_map('intval', (array)($payload['refs']['abono_ids'] ?? [])))
    ]
  ];
}

function voucher_payload_to_preview_data(array $payload): array {
  $meta = $payload['meta'] ?? [];
  $cliente = $payload['cliente'] ?? [];
  $contr = $payload['contratante'] ?? [];
  $conductor = $payload['conductor'] ?? [];

  $clienteDocTipo = (string)($cliente['doc_tipo'] ?? '');
  $clienteDocNum = (string)($cliente['doc_numero'] ?? '');
  $clienteTipoPersona = strtoupper((string)($cliente['tipo_persona'] ?? 'NATURAL'));
  if ($clienteTipoPersona !== 'JURIDICA') $clienteTipoPersona = 'NATURAL';

  $clienteOut = [
    'tipo_persona' => $clienteTipoPersona,
    'tipo' => $clienteDocTipo,
    'doc' => $clienteDocNum,
    'razon' => $clienteTipoPersona === 'JURIDICA' ? (string)($cliente['nombre'] ?? '') : '',
    'nombres' => $clienteTipoPersona === 'NATURAL' ? (string)($cliente['nombre'] ?? '') : '',
    'apellidos' => '',
    'telefono' => (string)($cliente['telefono'] ?? '')
  ];

  $contrOut = [
    'tipo' => (string)($contr['doc_tipo'] ?? ''),
    'doc' => (string)($contr['doc_numero'] ?? ''),
    'nombres' => (string)($contr['nombre'] ?? ''),
    'apellidos' => '',
    'telefono' => (string)($contr['telefono'] ?? '')
  ];

  $condOut = [
    'tipo' => (string)($conductor['doc_tipo'] ?? ''),
    'doc' => (string)($conductor['doc_numero'] ?? ''),
    'nombres' => (string)($conductor['nombre'] ?? ''),
    'apellidos' => '',
    'telefono' => (string)($conductor['telefono'] ?? '')
  ];

  $items = [];
  foreach (($payload['items'] ?? []) as $it) {
    $items[] = [
      'nombre' => (string)($it['nombre'] ?? ''),
      'qty' => (float)($it['cantidad'] ?? 0),
      'precio' => (float)($it['precio'] ?? 0)
    ];
  }

  $abonos = [];
  foreach (($payload['abonos'] ?? []) as $ab) {
    $ref = 'Recibo ABN-' . str_pad((string)((int)($ab['abono_id'] ?? 0)), 6, '0', STR_PAD_LEFT);
    $extraRef = trim((string)($ab['referencia'] ?? ''));
    if ($extraRef !== '') $ref .= ' - ' . $extraRef;
    $estadoTxt = trim((string)($ab['estado_text'] ?? ''));
    if ($estadoTxt !== '') $ref .= ' - ' . $estadoTxt;
    $abonos[] = [
      'medio' => (string)($ab['medio'] ?? ''),
      'monto' => (float)($ab['monto'] ?? 0),
      'ref' => $ref
    ];
  }

  $alcanceLabel = strtoupper((string)($meta['alcance_label'] ?? 'ACTUAL'));
  if ($alcanceLabel === '') $alcanceLabel = 'ACTUAL';
  $exactitud = strtoupper((string)($meta['exactitud'] ?? 'EXACTO'));
  if (!in_array($exactitud, ['EXACTO', 'APROXIMADO'], true)) $exactitud = 'EXACTO';

  $cajero = trim((string)($meta['cajero_operacion_nombre'] ?? ''));
  if ($cajero === '') $cajero = trim((string)($meta['cajero_operacion_usuario'] ?? ''));
  if ($cajero === '') $cajero = 'Usuario';

  $reimpresoPor = trim((string)($meta['reimpreso_por_nombre'] ?? ''));
  if ($reimpresoPor === '') $reimpresoPor = trim((string)($meta['reimpreso_por_usuario'] ?? ''));

  $scopeOut = strtolower((string)($payload['scope'] ?? ($meta['alcance'] ?? 'actual')));
  $scopeOut = ($scopeOut === 'original') ? 'original' : 'actual';
  $abonoIdsOut = array_values(array_map('intval', (array)($payload['refs']['abono_ids'] ?? [])));
  $abonoIdOut = (count($abonoIdsOut) === 1) ? (int)$abonoIdsOut[0] : 0;

  return [
    'kind' => strtolower((string)($payload['kind'] ?? 'venta')) === 'abono' ? 'abono' : 'venta',
    'scope' => $scopeOut,
    'venta_id' => (int)($payload['refs']['venta_id'] ?? 0),
    'abono_id' => $abonoIdOut,
    'abono_ids' => $abonoIdsOut,
    'empresa' => (string)($payload['empresa']['nombre'] ?? ''),
    'ticket' => (string)($meta['ticket'] ?? ''),
    'fecha' => voucher_fmt_dt((string)($meta['fecha_raw'] ?? '')),
    'cajero' => $cajero,
    'reimpreso_por' => $reimpresoPor,
    'alcance_label' => $alcanceLabel,
    'exactitud' => $exactitud,
    'estado_venta' => (string)($meta['estado_venta'] ?? ''),
    'estado_venta_texto' => (string)($meta['estado_venta_texto'] ?? ''),
    'cliente' => $clienteOut,
    'contratante' => $contrOut,
    'conductor' => $condOut,
    'items' => $items,
    'abonos' => $abonos,
    'totales' => [
      'total' => (float)($payload['totales']['total'] ?? 0),
      'pagado' => (float)($payload['totales']['pagado'] ?? 0),
      'saldo' => (float)($payload['totales']['saldo'] ?? 0),
      'devuelto' => (float)($payload['totales']['devuelto'] ?? 0)
    ]
  ];
}

function voucher_apply_preview_presentation(array $preview, string $presentation): array {
  if (voucher_norm_presentation($presentation) === 'cliente') {
    $preview['reimpreso_por'] = '';
    $preview['alcance_label'] = '';
    $preview['exactitud'] = 'EXACTO';
  }
  return $preview;
}

function voucher_load_payload_for_scope(
  mysqli $db,
  int $empId,
  array $u,
  int $ventaId,
  string $kind,
  string $scope,
  array $abonoIds,
  int $abonoId
): array {
  $kind = ($kind === 'abono') ? 'abono' : 'venta';
  $scope = voucher_norm_scope($scope);

  if ($kind === 'abono' && $ventaId <= 0 && $abonoId > 0) {
    $ventaId = vh_resolve_venta_id_by_abono($db, $empId, $abonoId);
  }
  if ($ventaId <= 0) {
    throw new RuntimeException('Venta invalida para comprobante.');
  }

  if ($kind === 'abono') {
    if ($abonoId > 0) {
      $abonoIds[] = $abonoId;
    }
    $abonoIds = array_values(array_unique(array_filter(array_map('intval', $abonoIds), function($x){ return $x > 0; })));
  } else {
    $abonoIds = [];
  }

  $reimpresor = [
    'id' => (int)($u['id'] ?? 0),
    'usuario' => (string)($u['usuario'] ?? ''),
    'nombre' => trim((string)($u['nombres'] ?? '') . ' ' . (string)($u['apellidos'] ?? ''))
  ];
  if ($reimpresor['nombre'] === '') $reimpresor['nombre'] = $reimpresor['usuario'];

  if ($scope === 'original') {
    $snapshot = null;
    if ($kind === 'venta') {
      $snapshot = vh_find_original_snapshot_venta($db, $empId, $ventaId);
    } else {
      $findId = $abonoId > 0 ? $abonoId : (int)($abonoIds[0] ?? 0);
      if ($findId > 0) $snapshot = vh_find_original_snapshot_abono($db, $empId, $findId);
    }

    $payload = vh_decode_snapshot_payload($snapshot);
    if (is_array($payload)) {
      if (!isset($payload['meta']) || !is_array($payload['meta'])) $payload['meta'] = [];
      $payload['scope'] = 'original';
      $payload['exactitud'] = (string)($snapshot['exactitud'] ?? ($payload['exactitud'] ?? 'EXACTO'));
      $payload['meta']['alcance'] = 'original';
      $payload['meta']['alcance_label'] = 'ORIGINAL';
      $payload['meta']['exactitud'] = $payload['exactitud'];
      $payload['meta']['reimpreso_por_id'] = $reimpresor['id'];
      $payload['meta']['reimpreso_por_usuario'] = $reimpresor['usuario'];
      $payload['meta']['reimpreso_por_nombre'] = $reimpresor['nombre'];
      return $payload;
    }

    $fallback = vh_build_payload(
      $db,
      $empId,
      $ventaId,
      $kind,
      $abonoIds,
      'original',
      'APROXIMADO',
      $reimpresor
    );
    $fallback['scope'] = 'original';
    $fallback['exactitud'] = 'APROXIMADO';
    $fallback['meta']['alcance'] = 'original';
    $fallback['meta']['alcance_label'] = 'ORIGINAL';
    $fallback['meta']['exactitud'] = 'APROXIMADO';
    return $fallback;
  }

  $actual = vh_build_payload(
    $db,
    $empId,
    $ventaId,
    $kind,
    $abonoIds,
    'actual',
    'EXACTO',
    $reimpresor
  );
  $actual['scope'] = 'actual';
  $actual['exactitud'] = 'EXACTO';
  $actual['meta']['alcance'] = 'actual';
  $actual['meta']['alcance_label'] = 'ACTUAL';
  $actual['meta']['exactitud'] = 'EXACTO';
  return $actual;
}

function voucher_fmt_money($n): string {
  return 'S/ ' . number_format((float)$n, 2, '.', ',');
}

function voucher_fmt_dt($value): string {
  if (!$value) return '';
  try {
    $dt = new DateTime((string)$value);
    return $dt->format('d/m/Y H:i');
  } catch (Throwable $e) {
    return (string)$value;
  }
}

function voucher_h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function voucher_filename_part($value, string $fallback): string {
  $txt = trim((string)$value);
  if ($txt === '') $txt = $fallback;
  if (function_exists('iconv')) {
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
    if (is_string($ascii) && $ascii !== '') $txt = $ascii;
  }
  $txt = preg_replace('/\s+/', '_', $txt);
  $txt = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$txt);
  $txt = preg_replace('/_+/', '_', (string)$txt);
  $txt = trim((string)$txt, '_-');
  if ($txt === '') $txt = $fallback;
  return strtoupper($txt);
}

function voucher_pdf_filename(array $data, string $kind): string {
  $kind = ($kind === 'abono') ? 'abono' : 'venta';
  $tipo = ($kind === 'abono') ? 'comprobante_abono' : 'ticket_venta';

  $codigo = trim((string)($data['meta']['ticket'] ?? ''));
  if ($kind === 'abono') {
    $abonoIds = array_values(array_map('intval', (array)($data['refs']['abono_ids'] ?? [])));
    $abonoId = (int)($abonoIds[0] ?? 0);
    if ($abonoId > 0) {
      $codigo = 'ABN-' . str_pad((string)$abonoId, 6, '0', STR_PAD_LEFT);
    } elseif ($codigo === '') {
      $codigo = 'ABN-SIN-CODIGO';
    }
  } elseif ($codigo === '') {
    $codigo = 'TICKET-SIN-CODIGO';
  }

  $empresa = trim((string)($data['empresa']['razon_social'] ?? ''));
  if ($empresa === '') $empresa = trim((string)($data['empresa']['nombre'] ?? ''));
  if ($empresa === '') $empresa = 'EMPRESA';

  $tipoSafe = voucher_filename_part($tipo, 'COMPROBANTE');
  $codigoSafe = voucher_filename_part($codigo, 'CODIGO');
  $empresaSafe = voucher_filename_part($empresa, 'EMPRESA');

  return strtolower($tipoSafe) . '_' . $codigoSafe . '_' . $empresaSafe . '.pdf';
}

function voucher_norm_person_text($value): string {
  $txt = strtoupper(trim((string)$value));
  if ($txt === '') return '';
  if (function_exists('iconv')) {
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
    if (is_string($ascii) && $ascii !== '') {
      $txt = $ascii;
    }
  }
  $txt = preg_replace('/[^A-Z0-9 ]+/', ' ', $txt);
  $txt = preg_replace('/\s+/', ' ', trim((string)$txt));
  return (string)$txt;
}

function voucher_norm_doc_key($doc): string {
  return str_replace(' ', '', voucher_norm_person_text($doc));
}

function voucher_norm_phone_key($phone): string {
  return preg_replace('/\D+/', '', (string)$phone) ?: '';
}

function voucher_same_person_cliente_conductor(array $cliente, array $conductor): bool {
  $clienteDocRaw = voucher_norm_person_text((string)($cliente['doc'] ?? ''));
  $condNameRaw = voucher_norm_person_text((string)($conductor['nombre'] ?? ''));

  // Si cliente es RUC, el conductor debe mostrarse siempre.
  $clienteDocTight = str_replace(' ', '', $clienteDocRaw);
  if ($clienteDocTight !== '' && substr($clienteDocTight, 0, 3) === 'RUC') {
    return false;
  }
  if ($condNameRaw === '' || $condNameRaw === 'NO ESPECIFICADO' || $condNameRaw === 'PENDIENTE DE DEFINIR') {
    return false;
  }

  $clienteDoc = voucher_norm_doc_key((string)($cliente['doc'] ?? ''));
  $condDoc = voucher_norm_doc_key((string)($conductor['doc'] ?? ''));
  if ($clienteDoc !== '' && $condDoc !== '' && $clienteDoc === $condDoc) {
    return true;
  }

  $clienteName = voucher_norm_person_text((string)($cliente['nombre'] ?? ''));
  $condName = $condNameRaw;
  if ($clienteName !== '' && $condName !== '' && $clienteName === $condName) {
    $clienteTel = voucher_norm_phone_key((string)($cliente['telefono'] ?? ''));
    $condTel = voucher_norm_phone_key((string)($conductor['telefono'] ?? ''));
    if ($clienteTel !== '' && $condTel !== '' && $clienteTel !== $condTel) {
      return false;
    }
    return true;
  }

  return false;
}

function voucher_fetch_empresa(mysqli $db, int $empId): ?array {
  $q = $db->prepare("SELECT id, nombre, razon_social, ruc, direccion, logo_path FROM mtp_empresas WHERE id=? LIMIT 1");
  $q->bind_param('i', $empId);
  $q->execute();
  return $q->get_result()->fetch_assoc() ?: null;
}

function voucher_logo_data_uri(?string $logoPath): string {
  $path = trim((string)$logoPath);
  $candidates = [];
  if ($path !== '') {
    $candidates[] = __DIR__ . '/../../' . ltrim($path, '/');
  }
  $candidates[] = __DIR__ . '/../../dist/img/AdminLTELogo.png';

  $abs = '';
  foreach ($candidates as $c) {
    if (is_file($c)) {
      $abs = $c;
      break;
    }
  }
  if ($abs === '') return '';

  $bin = @file_get_contents($abs);
  if ($bin === false || $bin === '') return '';

  $mime = 'image/png';
  if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
      $m = @finfo_file($fi, $abs);
      if (is_string($m) && strpos($m, 'image/') === 0) {
        $mime = $m;
      }
      @finfo_close($fi);
    }
  }
  return 'data:' . $mime . ';base64,' . base64_encode($bin);
}

function voucher_fetch_items(mysqli $db, int $ventaId): array {
  $q = $db->prepare("SELECT servicio_nombre, cantidad, precio_unitario, total_linea
                     FROM pos_venta_detalles
                     WHERE venta_id=?
                     ORDER BY id ASC");
  $q->bind_param('i', $ventaId);
  $q->execute();
  return $q->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
}

function voucher_fetch_abonos(mysqli $db, int $ventaId, array $abonoIds = []): array {
  if ($abonoIds) {
    $in = implode(',', array_fill(0, count($abonoIds), '?'));
    $types = 'i' . str_repeat('i', count($abonoIds));
    $pars = array_merge([$ventaId], $abonoIds);
    $sql = "SELECT
              a.id AS abono_id,
              a.fecha,
              a.monto,
              COALESCE(SUM(apl.monto_aplicado),0) AS monto_aplicado,
              a.referencia,
              mp.nombre AS medio
            FROM pos_abono_aplicaciones apl
            JOIN pos_abonos a ON a.id=apl.abono_id
            LEFT JOIN pos_medios_pago mp ON mp.id=a.medio_id
            WHERE apl.venta_id=? AND a.id IN ($in)
            GROUP BY a.id, a.fecha, a.monto, a.referencia, mp.nombre
            ORDER BY a.fecha ASC, a.id ASC";
    $q = $db->prepare($sql);
    $refs = [];
    $params = array_merge([$types], $pars);
    foreach ($params as $k => $v) {
      $refs[$k] = &$params[$k];
    }
    call_user_func_array([$q, 'bind_param'], $refs);
    $q->execute();
    return $q->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  }

  $q = $db->prepare("SELECT
                       a.id AS abono_id,
                       a.fecha,
                       a.monto,
                       COALESCE(SUM(apl.monto_aplicado),0) AS monto_aplicado,
                       a.referencia,
                       mp.nombre AS medio
                     FROM pos_abono_aplicaciones apl
                     JOIN pos_abonos a ON a.id=apl.abono_id
                     LEFT JOIN pos_medios_pago mp ON mp.id=a.medio_id
                     WHERE apl.venta_id=?
                     GROUP BY a.id, a.fecha, a.monto, a.referencia, mp.nombre
                     ORDER BY a.fecha ASC, a.id ASC");
  $q->bind_param('i', $ventaId);
  $q->execute();
  return $q->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
}

function voucher_estimate_ticket_height(array $data, string $size): float {
  $charsLine = ($size === 'ticket58') ? 22 : 34;
  $lineMm = ($size === 'ticket58') ? 3.1 : 3.4;
  $baseLines = 34;

  $pieces = [];
  $pieces[] = (string)($data['empresa']['nombre'] ?? '');
  $pieces[] = (string)($data['empresa']['razon_social'] ?? '');
  $pieces[] = (string)($data['empresa']['ruc'] ?? '');
  $pieces[] = (string)($data['empresa']['direccion'] ?? '');
  $pieces[] = (string)($data['meta']['ticket'] ?? '');
  $pieces[] = (string)($data['meta']['fecha'] ?? '');
  $pieces[] = (string)($data['meta']['fecha_venta'] ?? '');
  $pieces[] = (string)($data['meta']['cajero'] ?? '');
  $pieces[] = (string)($data['cliente']['doc'] ?? '');
  $pieces[] = (string)($data['cliente']['nombre'] ?? '');
  $pieces[] = (string)($data['cliente']['telefono'] ?? '');
  $pieces[] = (string)($data['contratante']['doc'] ?? '');
  $pieces[] = (string)($data['contratante']['nombre'] ?? '');
  $pieces[] = (string)($data['contratante']['telefono'] ?? '');
  $pieces[] = (string)($data['conductor']['doc'] ?? '');
  $pieces[] = (string)($data['conductor']['nombre'] ?? '');
  $pieces[] = (string)($data['conductor']['telefono'] ?? '');

  foreach (($data['items'] ?? []) as $it) {
    $pieces[] = (string)($it['nombre'] ?? '');
  }
  foreach (($data['abonos'] ?? []) as $ab) {
    $pieces[] = (string)($ab['medio'] ?? '');
    $pieces[] = (string)($ab['referencia'] ?? '');
    $pieces[] = (string)($ab['fecha'] ?? '');
  }

  $extraLines = 0;
  foreach ($pieces as $p) {
    $len = function_exists('mb_strlen') ? mb_strlen($p, 'UTF-8') : strlen($p);
    if ($len > 0) $extraLines += (int)ceil($len / $charsLine);
  }

  $estimated = ($baseLines + $extraLines) * $lineMm + 14;
  if ($estimated < 95) $estimated = 95;
  if ($estimated > 4000) $estimated = 4000;
  return $estimated;
}

function voucher_render_pdf(array $data, string $size, string $kind, string $presentation = 'auditoria'): void {
  $tcpdfFile = __DIR__ . '/../TCPDF/tcpdf.php';
  if (!file_exists($tcpdfFile)) {
    throw new RuntimeException('TCPDF no encontrado en modules/TCPDF.');
  }
  require_once $tcpdfFile;

  $size = voucher_norm_size($size);
  $kind = ($kind === 'abono') ? 'abono' : 'venta';
  $presentation = voucher_norm_presentation($presentation);
  $showInternalMeta = ($presentation === 'auditoria');

  if ($size === 'a4') {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $margin = 10.0;
  } else {
    $w = ($size === 'ticket58') ? 58.0 : 80.0;
    $h = voucher_estimate_ticket_height($data, $size);
    $pdf = new TCPDF('P', 'mm', [$w, $h], true, 'UTF-8', false);
    $margin = ($size === 'ticket58') ? 1.6 : 2.0;
  }

  $pdf->SetCreator('Sistema de ventas');
  $pdf->SetAuthor((string)($data['empresa']['nombre'] ?? 'Sistema'));
  $pdf->SetTitle(($kind === 'abono' ? 'Comprobante de abono' : 'Comprobante de venta') . ' ' . (string)($data['meta']['ticket'] ?? ''));
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);
  $pdf->SetMargins($margin, $margin, $margin);
  $pdf->SetAutoPageBreak(true, $margin);
  $pdf->AddPage();

  $fontBase = ($size === 'a4') ? 10 : (($size === 'ticket58') ? 7.6 : 8.6);
  $fontSmall = $fontBase - (($size === 'a4') ? 1.5 : 1.0);
  $titleSize = $fontBase + (($size === 'a4') ? 2.0 : 1.1);
  $ticketCodeSize = $fontBase + (($size === 'a4') ? 4.2 : (($size === 'ticket58') ? 2.8 : 3.2));
  $title = ($kind === 'abono') ? 'COMPROBANTE DE ABONO' : 'COMPROBANTE DE VENTA';

  $empresa = $data['empresa'];
  $meta = $data['meta'];
  $cliente = $data['cliente'];
  $contratante = $data['contratante'] ?? [];
  $conductor = $data['conductor'];
  $items = $data['items'] ?? [];
  $abonos = $data['abonos'] ?? [];
  $tot = $data['totales'] ?? ['total'=>0,'pagado'=>0,'saldo'=>0,'devuelto'=>0];

  $empresaNombre = trim((string)($empresa['razon_social'] ?: $empresa['nombre']));
  $empresaRuc = trim((string)($empresa['ruc'] ?? ''));
  $empresaDir = trim((string)($empresa['direccion'] ?? ''));
  $logoData = trim((string)($empresa['logo_data_uri'] ?? ''));
  $clienteTel = trim((string)($cliente['telefono'] ?? ''));
  $contrDoc = trim((string)($contratante['doc'] ?? ''));
  $contrNom = trim((string)($contratante['nombre'] ?? ''));
  $contrTel = trim((string)($contratante['telefono'] ?? ''));
  $condTel = trim((string)($conductor['telefono'] ?? ''));
  $ticketValue = trim((string)($meta['ticket'] ?? ''));
  $alcanceLabel = strtoupper(trim((string)($meta['alcance_label'] ?? '')));
  if ($alcanceLabel === '') $alcanceLabel = 'ACTUAL';
  $exactitud = strtoupper(trim((string)($meta['exactitud'] ?? 'EXACTO')));
  if (!in_array($exactitud, ['EXACTO', 'APROXIMADO'], true)) $exactitud = 'EXACTO';
  $estadoVentaText = trim((string)($meta['estado_venta_texto'] ?? ''));
  if ($estadoVentaText === '') {
    $estadoVentaRaw = strtoupper(trim((string)($meta['estado_venta'] ?? '')));
    $totDevRaw = (float)($tot['devuelto'] ?? 0);
    if ($estadoVentaRaw === 'ANULADA') {
      $estadoVentaText = 'Devolucion total';
    } elseif ($totDevRaw > 0.000001) {
      $estadoVentaText = 'Devolucion parcial';
    } else {
      $estadoVentaText = 'Emitida';
    }
  }
  $reimpresoPor = trim((string)($meta['reimpreso_por'] ?? ''));
  $hideConductorSection = voucher_same_person_cliente_conductor($cliente, $conductor);
  $isTicketPaper = ($size === 'ticket58' || $size === 'ticket80');
  $logoW = ($size === 'a4') ? '18mm' : (($size === 'ticket58') ? '11mm' : '13mm');
  $logoCellW = ($size === 'a4') ? '22mm' : '16mm';
  $useInlineHeader = ($size === 'a4' && $logoData !== '');
  $metaJoin = 'RUC: ' . $empresaRuc . ' | ' . $empresaDir;
  $metaJoinLen = function_exists('mb_strlen') ? mb_strlen($metaJoin, 'UTF-8') : strlen($metaJoin);
  $showOneLineMeta = (
    $size === 'a4' &&
    $empresaRuc !== '' &&
    $empresaDir !== '' &&
    $metaJoinLen <= (($size === 'a4') ? 96 : 56)
  );
  $headWrapBottom = $isTicketPaper ? '0.35mm' : '0.8mm';
  $headLogoGap = $isTicketPaper ? '0.45mm' : '0.9mm';
  $headRowGap = $isTicketPaper ? '0.08mm' : '0.2mm';
  $headTitleGapTop = ($size === 'ticket80') ? '0.30mm' : (($size === 'ticket58') ? '0.20mm' : '0');
  $tkCenterMargin = $isTicketPaper ? '0.45mm 0 0.65mm 0' : '0.8mm 0 1.0mm 0';

  $html = '';
  $html .= '<style>
      * { font-family: helvetica, sans-serif; color:#111; }
      .center { text-align:center; }
      .left { text-align:left; }
      .b { font-weight:bold; }
      .tight { line-height:1.1; }
      .t-main { font-size:' . $titleSize . 'pt; font-weight:bold; }
      .small { font-size:' . $fontSmall . 'pt; color:#444; }
      .base { font-size:' . $fontBase . 'pt; }
      .rule { border-bottom:1px dashed #666; margin:0.8mm 0; }
      .sec { margin-top:3px; margin-bottom:2px; font-weight:bold; font-size:' . max(7.0, $fontSmall) . 'pt; }
      table { width:100%; border-collapse:collapse; }
      td, th { font-size:' . $fontBase . 'pt; padding:0.6px 0; vertical-align:top; }
      th { text-align:left; }
      .right { text-align:right; }
      .muted { color:#444; }
      .mono { font-family: courier, monospace; }
      .head-wrap { margin:0 0 ' . $headWrapBottom . ' 0; }
      .head-table { width:100%; border-collapse:collapse; margin:0; }
      .head-table td { border:none; padding:0; vertical-align:top; }
      .logo-img { width:' . $logoW . '; height:auto; }
      .head-logo-wrap { text-align:center; margin:0 0 ' . $headLogoGap . ' 0; line-height:1; }
      .head-logo-img { width:' . $logoW . '; height:auto; display:block; margin:0 auto; }
      .head-center-row { text-align:center; line-height:1.05; margin:' . $headRowGap . ' 0; }
      .head-title-center { text-align:center; font-size:' . $titleSize . 'pt; font-weight:bold; line-height:1.03; margin:' . $headTitleGapTop . ' 0 0 0; }
      .head-title { font-size:' . $titleSize . 'pt; font-weight:bold; line-height:1.02; margin:0; }
      .head-company { font-size:' . ($fontBase + 0.9) . 'pt; font-weight:bold; line-height:1.05; margin:' . $headRowGap . ' 0 0 0; }
      .head-meta { font-size:' . $fontSmall . 'pt; color:#444; line-height:1.08; margin:' . $headRowGap . ' 0 0 0; }
      .scope { display:inline-block; padding:0.4mm 1.4mm; border:1px solid #444; border-radius:2mm; font-weight:bold; font-size:' . max(6.6, $fontSmall) . 'pt; margin-top:0.5mm; }
      .tk-row { width:100%; border-collapse:collapse; margin:0.6mm 0 0.9mm 0; }
      .tk-row td { border:none; padding:0; vertical-align:baseline; }
      .tk-k { width:30%; text-align:left; font-size:' . max(6.8, $fontSmall) . 'pt; font-weight:bold; letter-spacing:0.5px; }
      .tk-v { text-align:right; font-size:' . $ticketCodeSize . 'pt; font-weight:bold; letter-spacing:0.7px; line-height:1.0; }
      .tk-center { text-align:center; margin:' . $tkCenterMargin . '; }
      .tk-label-inline { font-size:' . max(6.8, $fontSmall) . 'pt; font-weight:bold; letter-spacing:0.5px; }
      .tk-value-inline { font-size:' . $ticketCodeSize . 'pt; font-weight:bold; letter-spacing:0.7px; line-height:1.0; }
    </style>';

  $html .= '<div class="head-wrap">';
  if ($size === 'ticket80') {
    if ($logoData !== '') {
      $html .= '<div class="head-logo-wrap"><img class="head-logo-img" src="' . voucher_h($logoData) . '" alt="logo"></div>';
    }
    if ($empresaNombre !== '') {
      $html .= '<div class="head-center-row head-company">' . voucher_h($empresaNombre) . '</div>';
    }
    if ($empresaRuc !== '') {
      $html .= '<div class="head-center-row head-meta">RUC: ' . voucher_h($empresaRuc) . '</div>';
    }
    if ($empresaDir !== '') {
      $html .= '<div class="head-center-row head-meta">' . voucher_h($empresaDir) . '</div>';
    }
    $html .= '<div class="head-title-center">' . voucher_h($title) . '</div>';
  } elseif ($size === 'ticket58') {
    if ($logoData !== '') {
      $html .= '<div class="head-logo-wrap"><img class="head-logo-img" src="' . voucher_h($logoData) . '" alt="logo"></div>';
    }
    $html .= '<div class="head-title-center">' . voucher_h($title) . '</div>';
    if ($empresaNombre !== '') {
      $html .= '<div class="head-center-row head-company">' . voucher_h($empresaNombre) . '</div>';
    }
    if ($empresaRuc !== '') {
      $html .= '<div class="head-center-row head-meta">RUC: ' . voucher_h($empresaRuc) . '</div>';
    }
    if ($empresaDir !== '') {
      $html .= '<div class="head-center-row head-meta">' . voucher_h($empresaDir) . '</div>';
    }
  } elseif ($useInlineHeader) {
    $html .= '<table class="head-table"><tr>';
    $html .= '<td style="width:' . $logoCellW . '; text-align:center; padding-right:1.2mm;"><img class="logo-img" src="' . voucher_h($logoData) . '" alt="logo"></td>';
    $html .= '<td class="left tight">';
    $html .= '<div class="head-title">' . voucher_h($title) . '</div>';
    if ($empresaNombre !== '') {
      $html .= '<div class="head-company">' . voucher_h($empresaNombre) . '</div>';
    }
    if ($showOneLineMeta) {
      $html .= '<div class="head-meta">' . voucher_h($metaJoin) . '</div>';
    } else {
      if ($empresaRuc !== '') {
        $html .= '<div class="head-meta">RUC: ' . voucher_h($empresaRuc) . '</div>';
      }
      if ($empresaDir !== '') {
        $html .= '<div class="head-meta">' . voucher_h($empresaDir) . '</div>';
      }
    }
    $html .= '</td></tr></table>';
  } else {
    if ($logoData !== '') {
      $html .= '<div class="center"><img class="logo-img" src="' . voucher_h($logoData) . '" alt="logo"></div>';
    }
    $html .= '<div class="center t-main tight">' . voucher_h($title) . '</div>';
    if ($empresaNombre !== '') {
      $html .= '<div class="center b base tight">' . voucher_h($empresaNombre) . '</div>';
    }
    if ($showOneLineMeta) {
      $html .= '<div class="center small tight">' . voucher_h($metaJoin) . '</div>';
    } else {
      if ($empresaRuc !== '') {
        $html .= '<div class="center small tight">RUC: ' . voucher_h($empresaRuc) . '</div>';
      }
      if ($empresaDir !== '') {
        $html .= '<div class="center small tight">' . voucher_h($empresaDir) . '</div>';
      }
    }
  }
  $html .= '</div>';

  $scopeTxt = '';
  if ($showInternalMeta) {
    $scopeTxt = $alcanceLabel;
    if ($exactitud === 'APROXIMADO') $scopeTxt .= ' (APROXIMADO)';
  }
  if ($showInternalMeta && $scopeTxt !== '') {
    $html .= '<div class="center"><span class="scope">' . voucher_h($scopeTxt) . '</span></div>';
  }

  if ($ticketValue !== '') {
    if ($size === 'ticket58' || $size === 'ticket80') {
      $html .= '<div class="tk-center"><span class="tk-label-inline">TICKET:</span> <span class="tk-value-inline mono">' . voucher_h($ticketValue) . '</span></div>';
    } else {
      $html .= '<table class="tk-row"><tr>';
      $html .= '<td class="tk-k">TICKET</td>';
      $html .= '<td class="tk-v mono">' . voucher_h($ticketValue) . '</td>';
      $html .= '</tr></table>';
    }
  }
  $html .= '<div class="rule"></div>';

  $html .= '<table>';
  if ($showInternalMeta && $scopeTxt !== '') {
    $html .= '<tr><td class="b">Alcance</td><td class="right">' . voucher_h($scopeTxt) . '</td></tr>';
  }
  $html .= '<tr><td class="b">Fecha</td><td class="right">' . voucher_h((string)($meta['fecha'] ?? '')) . '</td></tr>';
  if ($kind === 'abono' && !empty($meta['fecha_venta'])) {
    $html .= '<tr><td class="b">Fecha venta</td><td class="right">' . voucher_h((string)$meta['fecha_venta']) . '</td></tr>';
  }
  $html .= '<tr><td class="b">Operacion por</td><td class="right">' . voucher_h((string)($meta['cajero'] ?? '')) . '</td></tr>';
  if ($showInternalMeta && $reimpresoPor !== '') {
    $html .= '<tr><td class="b">Reimpreso por</td><td class="right">' . voucher_h($reimpresoPor) . '</td></tr>';
  }
  if ($showInternalMeta && $alcanceLabel === 'ACTUAL') {
    $html .= '<tr><td class="b">Estado actual</td><td class="right">' . voucher_h($estadoVentaText) . '</td></tr>';
  }
  $html .= '</table>';

  $html .= '<div class="rule"></div><div class="sec">CLIENTE</div>';
  $html .= '<table>';
  $html .= '<tr><td class="b">Documento</td><td class="right">' . voucher_h((string)($cliente['doc'] ?? '')) . '</td></tr>';
  $html .= '<tr><td class="b">Nombre</td><td class="right">' . voucher_h((string)($cliente['nombre'] ?? '')) . '</td></tr>';
  if ($clienteTel !== '') {
    $html .= '<tr><td class="b">Telefono</td><td class="right">' . voucher_h($clienteTel) . '</td></tr>';
  }
  $html .= '</table>';

  if ($contrDoc !== '' || $contrNom !== '' || $contrTel !== '') {
    $html .= '<div class="sec">CONTRATANTE</div>';
    $html .= '<table>';
    $html .= '<tr><td class="b">Documento</td><td class="right">' . voucher_h($contrDoc) . '</td></tr>';
    $html .= '<tr><td class="b">Nombre</td><td class="right">' . voucher_h($contrNom) . '</td></tr>';
    if ($contrTel !== '') {
      $html .= '<tr><td class="b">Telefono</td><td class="right">' . voucher_h($contrTel) . '</td></tr>';
    }
    $html .= '</table>';
  }

  if (!$hideConductorSection) {
    $html .= '<div class="sec">CONDUCTOR</div>';
    $html .= '<table>';
    $html .= '<tr><td class="b">Documento</td><td class="right">' . voucher_h((string)($conductor['doc'] ?? '')) . '</td></tr>';
    $html .= '<tr><td class="b">Nombre</td><td class="right">' . voucher_h((string)($conductor['nombre'] ?? '')) . '</td></tr>';
    if ($condTel !== '') {
      $html .= '<tr><td class="b">Telefono</td><td class="right">' . voucher_h($condTel) . '</td></tr>';
    }
    $html .= '</table>';
  }

  if ($items) {
    $html .= '<div class="rule"></div><div class="sec">DETALLE</div>';
    $html .= '<table>';
    $html .= '<tr><th>Servicio</th><th class="right">Importe</th></tr>';
    foreach ($items as $it) {
      $lineaName = (string)($it['nombre'] ?? '');
      $lineaAux = 'x' . (float)($it['cantidad'] ?? 0) . ' - ' . voucher_fmt_money((float)($it['precio'] ?? 0));
      $lineaTot = voucher_fmt_money((float)($it['total'] ?? 0));
      $html .= '<tr><td>' . voucher_h($lineaName) . '<br><span class="small muted">' . voucher_h($lineaAux) . '</span></td><td class="right">' . voucher_h($lineaTot) . '</td></tr>';
    }
    $html .= '</table>';
  }

  if ($abonos) {
    $html .= '<div class="rule"></div><div class="sec">' . ($kind === 'abono' ? 'ABONOS REGISTRADOS' : 'ABONOS') . '</div>';
    $html .= '<table>';
    $html .= '<tr><th>Medio / Ref.</th><th class="right">Monto</th></tr>';
    foreach ($abonos as $ab) {
      $medio = (string)($ab['medio'] ?? '—');
      $ref = trim((string)($ab['referencia'] ?? ''));
      $monto = voucher_fmt_money((float)($ab['monto'] ?? 0));
      $fechaAb = trim((string)($ab['fecha'] ?? ''));
      $abnCode = (int)($ab['abono_id'] ?? 0);
      $estadoAb = trim((string)($ab['estado_text'] ?? ''));
      $metaRef = 'ABN-' . str_pad((string)$abnCode, 6, '0', STR_PAD_LEFT);
      if ($ref !== '') $metaRef .= ' - ' . $ref;
      if ($fechaAb !== '') $metaRef .= ' - ' . voucher_fmt_dt($fechaAb);
      if ($estadoAb !== '') $metaRef .= ' - ' . $estadoAb;
      $html .= '<tr><td>' . voucher_h($medio) . '<br><span class="small muted">' . voucher_h($metaRef) . '</span></td><td class="right">' . voucher_h($monto) . '</td></tr>';
    }
    $html .= '</table>';
  }

  $html .= '<div class="rule"></div><div class="sec">TOTALES</div>';
  $html .= '<table>';
  $html .= '<tr><td>Total</td><td class="right b">' . voucher_h(voucher_fmt_money((float)($tot['total'] ?? 0))) . '</td></tr>';
  $html .= '<tr><td>Pagado</td><td class="right b">' . voucher_h(voucher_fmt_money((float)($tot['pagado'] ?? 0))) . '</td></tr>';
  $html .= '<tr><td>Saldo</td><td class="right b">' . voucher_h(voucher_fmt_money((float)($tot['saldo'] ?? 0))) . '</td></tr>';
  if ((float)($tot['devuelto'] ?? 0) > 0) {
    $html .= '<tr><td>Devuelto</td><td class="right b">' . voucher_h(voucher_fmt_money((float)$tot['devuelto'])) . '</td></tr>';
  }
  $html .= '</table>';

  $html .= '<div class="center small" style="margin-top:6px;">Gracias por su preferencia.</div>';

  $pdfFilename = voucher_pdf_filename($data, $kind);
  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="' . $pdfFilename . '"');
  $pdf->writeHTML($html, true, false, true, false, '');
  $pdf->Output('', 'I');
  exit;
}

/* =========================
 * Ruteo
 * ========================= */
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? $_POST['accion'] ?? '';

try {

  /* =======================
   * GET voucher_pdf
   * ======================= */
  if ($accion === 'voucher_pdf') {
    $ventaId = (int)($_GET['id'] ?? $_GET['venta_id'] ?? 0);
    $abonoId = (int)($_GET['abono_id'] ?? 0);
    $kind = strtolower(trim((string)($_GET['kind'] ?? 'venta')));
    $kind = ($kind === 'abono') ? 'abono' : 'venta';
    $scopeVoucher = voucher_norm_scope($_GET['scope'] ?? 'actual');
    $presentation = voucher_norm_presentation($_GET['presentation'] ?? 'auditoria');
    $size = voucher_norm_size($_GET['size'] ?? 'ticket80');
    $abonoIds = voucher_parse_ids_csv($_GET['abono_ids'] ?? '');

    try {
      $payload = voucher_load_payload_for_scope(
        $db,
        $empId,
        $u,
        $ventaId,
        $kind,
        $scopeVoucher,
        $abonoIds,
        $abonoId
      );
    } catch (RuntimeException $e) {
      json_err($e->getMessage());
    }
    $dataRender = voucher_payload_to_render_data($payload, $empId, $db);
    voucher_render_pdf($dataRender, $size, $kind, $presentation);
  }

  /* =======================
   * GET voucher_preview
   * ======================= */
  if ($accion === 'voucher_preview') {
    $ventaId = (int)($_GET['id'] ?? $_GET['venta_id'] ?? 0);
    $abonoId = (int)($_GET['abono_id'] ?? 0);
    $kind = strtolower(trim((string)($_GET['kind'] ?? 'venta')));
    $kind = ($kind === 'abono') ? 'abono' : 'venta';
    $scopeVoucher = voucher_norm_scope($_GET['scope'] ?? 'actual');
    $presentation = voucher_norm_presentation($_GET['presentation'] ?? 'auditoria');
    $abonoIds = voucher_parse_ids_csv($_GET['abono_ids'] ?? '');

    try {
      $payload = voucher_load_payload_for_scope(
        $db,
        $empId,
        $u,
        $ventaId,
        $kind,
        $scopeVoucher,
        $abonoIds,
        $abonoId
      );
    } catch (RuntimeException $e) {
      json_err($e->getMessage());
    }
    $preview = voucher_payload_to_preview_data($payload);
    $preview = voucher_apply_preview_presentation($preview, $presentation);
    json_ok(['payload' => $preview]);
  }
  /* =======================
   * GET ventas_buscar
   * ======================= */
  if ($accion === 'ventas_buscar') {
    $q      = trim((string)($_GET['q'] ?? ''));
    $estado = $_GET['estado'] ?? 'pending'; // pending | paid | refund_partial | refund_total | all
    $scope  = trim((string)($_GET['scope'] ?? 'latest')); // latest | date | range
    $fecha  = trim((string)($_GET['fecha'] ?? ''));
    $desde  = trim((string)($_GET['desde'] ?? ''));
    $hasta  = trim((string)($_GET['hasta'] ?? ''));
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $per    = min(50, max(10, (int)($_GET['per'] ?? 10))); // por defecto 10

    $where = ["v.id_empresa=?"];
    $types = "i";
    $pars  = [$empId];
    $context = [
      'scope'  => 'latest',
      'title'  => 'Ultima caja',
      'detail' => 'Sin contexto disponible.'
    ];

    if ($scope !== 'date' && $scope !== 'range') {
      $scope = 'latest';
    }

    if ($scope === 'date') {
      if (!is_valid_ymd($fecha)) json_err('Selecciona una fecha valida.');
      $where[] = "cd.fecha=?";
      $types  .= "s";
      $pars[]  = $fecha;
      $context = [
        'scope'  => 'date',
        'title'  => 'Fecha seleccionada',
        'detail' => human_date($fecha)
      ];
    } elseif ($scope === 'range') {
      if (!is_valid_ymd($desde) || !is_valid_ymd($hasta)) json_err('Selecciona un rango de fechas valido.');
      if ($desde > $hasta) json_err('La fecha inicial no puede ser mayor que la final.');
      $where[] = "cd.fecha BETWEEN ? AND ?";
      $types  .= "ss";
      $pars[]  = $desde;
      $pars[]  = $hasta;
      $context = [
        'scope'  => 'range',
        'title'  => 'Rango seleccionado',
        'detail' => human_date($desde).' al '.human_date($hasta)
      ];
    } else {
      $ultimaCaja = getUltimaCajaDiaria($db, $empId);
      if ($ultimaCaja) {
        $where[] = "v.caja_diaria_id=?";
        $types  .= "i";
        $pars[]  = (int)$ultimaCaja['id'];
        $context = [
          'scope'       => 'latest',
          'title'       => 'Ultima caja',
          'detail'      => $ultimaCaja['codigo'].' | '.human_date($ultimaCaja['fecha']).' | '.ucfirst((string)$ultimaCaja['estado']),
          'caja_id'     => (int)$ultimaCaja['id'],
          'caja_codigo' => $ultimaCaja['codigo'],
          'caja_fecha'  => $ultimaCaja['fecha'],
          'caja_estado' => $ultimaCaja['estado']
        ];
      } else {
        $where[] = "1=0";
        $context = [
          'scope'  => 'latest',
          'title'  => 'Ultima caja',
          'detail' => 'No hay cajas diarias registradas.'
        ];
      }
    }

    // Búsqueda universal (solo si hay query)
    if ($q !== '') {
      $where[] = "("
        . "COALESCE(v.cliente_snapshot_doc_tipo,c.doc_tipo) LIKE ?"
        . " OR COALESCE(v.cliente_snapshot_doc_numero,c.doc_numero) LIKE ?"
        . " OR COALESCE(v.cliente_snapshot_nombre,c.nombre) LIKE ?"
        . " OR v.contratante_doc_tipo LIKE ? OR v.contratante_doc_numero LIKE ?"
        . " OR CONCAT_WS(' ', v.contratante_nombres, v.contratante_apellidos) LIKE ?"
        . " OR COALESCE(vc.conductor_doc_tipo,d.doc_tipo) LIKE ?"
        . " OR COALESCE(vc.conductor_doc_numero,d.doc_numero) LIKE ?"
        . " OR CONCAT_WS(' ', COALESCE(vc.conductor_nombres,d.nombres), COALESCE(vc.conductor_apellidos,d.apellidos)) LIKE ?"
        . " OR CONCAT(v.serie,'-',LPAD(v.numero,4,'0')) LIKE ?"
        . ")";
      $types .= "ssssssssss";
      $like = like_wrap($q);
      array_push($pars, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like);
    }

    // Filtro por estado (semantica de negocio: pendiente, pagado, devolucion parcial, devolucion total)
    // NOTA: usamos alias r.devuelto_total, así que debe existir en los JOINs
    switch ($estado) {
      case 'pending':
        // Incluye cualquier venta emitida con saldo pendiente, tenga o no devoluciones parciales.
        $where[] = "v.estado='EMITIDA' AND v.saldo > 0.000001";
        break;
      case 'paid':
        $where[] = "v.estado='EMITIDA' AND v.saldo <= 0.000001 AND COALESCE(r.devuelto_total,0) <= 0.000001";
        break;
      case 'refund_partial':
      case 'devolucion_parcial':
      case 'refund':
        $where[] = "v.estado='EMITIDA' AND COALESCE(r.devuelto_total,0) > 0.000001";
        break;
      case 'refund_total':
      case 'devolucion_total':
      case 'void':
        $where[] = "v.estado='ANULADA'";
        break;
      case 'all':
      default:
        // sin filtro extra
        break;
    }

    $W = "WHERE ".implode(' AND ', $where);

    // Conteo
    $sqlCount = "SELECT COUNT(DISTINCT v.id) c
                 FROM pos_ventas v
                 LEFT JOIN mod_caja_diaria cd ON cd.id=v.caja_diaria_id
                 LEFT JOIN pos_clientes c ON c.id=v.cliente_id
                 LEFT JOIN pos_venta_conductores vc ON vc.venta_id=v.id AND vc.es_principal=1
                 LEFT JOIN pos_conductores d ON d.id=vc.conductor_id
                 LEFT JOIN (
                   SELECT venta_id, SUM(monto_devuelto) AS devuelto_total
                   FROM pos_devoluciones
                   GROUP BY venta_id
                 ) r ON r.venta_id=v.id
                 $W";
    $stc = $db->prepare($sqlCount);
    $stc->bind_param($types, ...$pars);
    $stc->execute();
    $total = (int)($stc->get_result()->fetch_assoc()['c'] ?? 0);

    $offset = ($page-1)*$per;

    // Datos
    $sql = "SELECT
              v.id, v.fecha_emision, v.serie, v.numero,
              v.total, v.total_pagado, v.saldo, v.estado,
              cd.codigo AS caja_codigo,
              cd.fecha  AS caja_fecha,
              cd.estado AS caja_estado,
              COALESCE(r.devuelto_total,0) AS devuelto_total,
              COALESCE(v.cliente_snapshot_doc_tipo, c.doc_tipo) AS c_doc_tipo,
              COALESCE(v.cliente_snapshot_doc_numero, c.doc_numero) AS c_doc_num,
              COALESCE(v.cliente_snapshot_nombre, c.nombre) AS c_nombre,
              COALESCE(v.cliente_snapshot_telefono, c.telefono) AS c_telefono,
              v.contratante_doc_tipo   AS ct_doc_tipo,
              v.contratante_doc_numero AS ct_doc_num,
              v.contratante_nombres    AS ct_nombres,
              v.contratante_apellidos  AS ct_apellidos,
              v.contratante_telefono   AS ct_telefono,
              vc.conductor_tipo        AS vc_conductor_tipo,
              vc.conductor_origen      AS vc_conductor_origen,
              vc.conductor_es_mismo_cliente AS vc_conductor_es_mismo_cliente,
              vc.conductor_doc_tipo    AS vc_doc_tipo,
              vc.conductor_doc_numero  AS vc_doc_numero,
              vc.conductor_nombres     AS vc_nombres,
              vc.conductor_apellidos   AS vc_apellidos,
              vc.conductor_telefono    AS vc_telefono,
              COALESCE(vc.conductor_doc_tipo, d.doc_tipo)   AS d_doc_tipo,
              COALESCE(vc.conductor_doc_numero, d.doc_numero) AS d_doc_num,
              COALESCE(vc.conductor_nombres, d.nombres)    AS d_nombres,
              COALESCE(vc.conductor_apellidos, d.apellidos)  AS d_apellidos,
              COALESCE(vc.conductor_telefono, d.telefono)   AS d_telefono
            FROM pos_ventas v
            LEFT JOIN mod_caja_diaria cd ON cd.id=v.caja_diaria_id
            LEFT JOIN pos_clientes c ON c.id=v.cliente_id
            LEFT JOIN pos_venta_conductores vc ON vc.venta_id=v.id AND vc.es_principal=1
            LEFT JOIN pos_conductores d ON d.id=vc.conductor_id
            LEFT JOIN (
              SELECT venta_id, SUM(monto_devuelto) AS devuelto_total
              FROM pos_devoluciones
              GROUP BY venta_id
            ) r ON r.venta_id=v.id
            $W
            GROUP BY v.id
            ORDER BY v.fecha_emision DESC, v.id DESC
            LIMIT ?, ?";
    $types2 = $types . "ii";
    $pars2  = array_merge($pars, [$offset, $per]);
    $st = $db->prepare($sql);
    $st->bind_param($types2, ...$pars2);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    // Presentación
    $out = [];
    foreach($rows as $r){
      $ticket = $r['serie'].'-'.pad4($r['numero']);

      $cliDisp = '';
      if ($r['c_doc_tipo'] && $r['c_doc_num']) {
        $cliDisp = $r['c_doc_tipo'].' '.$r['c_doc_num'].' • '.$r['c_nombre'];
      } elseif ($r['c_nombre']) {
        $cliDisp = $r['c_nombre'];
      }

      $ctrDisp = '';
      if ($r['ct_doc_tipo'] && $r['ct_doc_num']) {
        $ctrDisp = $r['ct_doc_tipo'].' '.$r['ct_doc_num'].' • '.$r['ct_nombres'].' '.$r['ct_apellidos'];
      }

      $condDisp = conductor_display(resolve_conductor_payload($r));

      $saldo        = (float)$r['saldo'];
      $estadoVenta  = (string)$r['estado'];
      $devuelto     = (float)($r['devuelto_total'] ?? 0);

      $estadoVisual = venta_build_estado_visual($estadoVenta, $saldo, $devuelto);
      $estado_code = $estadoVisual['code'];
      $estado_text = $estadoVisual['text'];

      $out[] = [
        'id'           => (int)$r['id'],
        'ticket'       => $ticket,
        'fecha'        => $r['fecha_emision'],
        'total'        => (float)$r['total'],
        'pagado'       => (float)$r['total_pagado'],
        'saldo'        => $saldo,
        'devuelto'     => $devuelto,
        'estado'       => $estado_text,
        'estado_code'  => $estado_code,
        'estado_venta' => $estadoVenta,
        'caja_codigo'  => $r['caja_codigo'] ?? '',
        'caja_fecha'   => $r['caja_fecha'] ?? null,
        'caja_estado'  => $r['caja_estado'] ?? '',
        'cliente'      => $cliDisp,
        'contratante'  => $ctrDisp,
        'conductor'    => $condDisp
      ];
    }

    json_ok([
      'data'    => $out,
      'page'    => $page,
      'per'     => $per,
      'total'   => $total,
      'context' => $context
    ]);
  }

  /* =======================
   * GET venta_detalle
   * ======================= */
  if ($accion === 'venta_detalle') {
    $ventaId = (int)($_GET['id'] ?? 0);
    if ($ventaId<=0) json_err('Venta inválida');

    $V = fetch_venta_head($db,$empId,$ventaId);
    if (!$V) json_err('Venta no encontrada');

    // Ítems
    $qi = $db->prepare("SELECT servicio_nombre, cantidad, precio_unitario, total_linea
                        FROM pos_venta_detalles WHERE venta_id=? ORDER BY id");
    $qi->bind_param('i',$ventaId); $qi->execute();
    $items = $qi->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    // Conductor principal
    $condRaw = fetch_principal_conductor($db,$ventaId) ?: [];
    $cond = resolve_conductor_payload(array_merge($V, $condRaw));

    // Abonos aplicados (con saldo pendiente por aplicar para posibles devoluciones)
    $qa = $db->prepare("SELECT apl.id AS aplicacion_id,
                               a.id AS abono_id,
                               mp.nombre AS medio,
                               a.monto,
                               apl.monto_aplicado,
                               COALESCE(SUM(d.monto_devuelto),0) AS devuelto_monto,
                               GREATEST(0, apl.monto_aplicado - COALESCE(SUM(d.monto_devuelto),0)) AS monto_pendiente_devolver,
                               a.referencia,
                               a.fecha
                        FROM pos_abono_aplicaciones apl
                        JOIN pos_abonos a ON a.id=apl.abono_id
                        JOIN pos_medios_pago mp ON mp.id=a.medio_id
                        LEFT JOIN pos_devoluciones d ON d.abono_aplicacion_id=apl.id
                        WHERE apl.venta_id=?
                        GROUP BY apl.id, a.id, mp.nombre, a.monto, apl.monto_aplicado, a.referencia, a.fecha
                        ORDER BY apl.id");
    $qa->bind_param('i',$ventaId); $qa->execute();
    $abonos = $qa->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    $H = [
      'id'     => (int)$V['id'],
      'ticket' => $V['serie'].'-'.pad4($V['numero']),
      'fecha'  => $V['fecha_emision'],
      'moneda' => $V['moneda'],
      'total'  => (float)$V['total'],
      'pagado' => (float)$V['total_pagado'],
      'saldo'  => (float)$V['saldo'],
      'estado' => $V['estado'],
      'cliente'=> [
        'doc_tipo' => $V['c_doc_tipo'],
        'doc'      => $V['c_doc_numero'],
        'nombre'   => $V['c_nombre'],
        'telefono' => $V['c_telefono']
      ],
      'contratante'=>[
        'doc_tipo' => $V['contratante_doc_tipo'],
        'doc'      => $V['contratante_doc_numero'],
        'nombres'  => $V['contratante_nombres'],
        'apellidos'=> $V['contratante_apellidos'],
        'telefono' => $V['contratante_telefono']
      ],
      'conductor'=>[
        'doc_tipo' => $cond['doc_tipo'] ?? '',
        'doc_numero' => $cond['doc_numero'] ?? '',
        'nombres'  => $cond['nombres'] ?? '',
        'apellidos'=> $cond['apellidos'] ?? '',
        'telefono' => $cond['telefono'] ?? ''
      ]
    ];

    json_ok(['cabecera'=>$H, 'items'=>$items, 'abonos'=>$abonos]);
  }

    /* =======================
   * POST venta_abonar
   * ======================= */
  if ($accion === 'venta_abonar') {
    if ($method!=='POST') json_err('Método no permitido');
    $ventaId = (int)($_POST['venta_id'] ?? 0);
    $abonos_json = $_POST['abonos_json'] ?? '[]';

    if ($ventaId<=0) json_err('Venta inválida');

    $V = fetch_venta_head($db,$empId,$ventaId);
    if (!$V) json_err('Venta no encontrada');
    if ($V['estado']==='ANULADA') json_err('La venta está anulada.');
    if ($V['saldo']<=0) json_err('La venta no tiene saldo pendiente.');

    $cd = getDiariaAbierta($db,$empId);
    if(!$cd) json_err('No hay caja diaria abierta. Cierra la pendiente o abre la del día actual.');
    $caja_diaria_id = (int)$cd['id'];

    $abonos = json_decode($abonos_json,true);
    if(!is_array($abonos) || !count($abonos)) json_err('No hay abonos para registrar.');

    $med = map_medios_pago_activos($db);

        // Validaciones por abono + suma total para bloquear sobrepago
    $sumaNuevos = 0.00;
    foreach($abonos as $i=>$a){
      $mid   = (int)($a['medio_id'] ?? 0);
      $monto = money2($a['monto'] ?? 0);
      $ref   = trim($a['referencia'] ?? '');

      if ($mid<=0 || !isset($med[$mid])) {
        json_err("Medio de pago inválido en abono #".($i+1));
      }
      if ($monto<=0) {
        json_err("Monto inválido en abono #".($i+1));
      }

            // Validación 100% basada en BD: si requiere_ref=1, la referencia es obligatoria
      $requiereRef = $med[$mid]['requiere_ref'];
      if ($requiereRef && $ref==='') {
        $nombreMedio = (string)$med[$mid]['nombre'];
        json_err("El medio de pago {$nombreMedio} requiere una referencia en el abono #".($i+1));
      }

      $sumaNuevos = money2($sumaNuevos + $monto);
    }

    // Bloquear sobrepago (limpio, sin "vuelto")
    $restante = money2((float)$V['saldo']);
    if ($sumaNuevos > $restante + 1e-6){
      json_err('El total de abonos excede el saldo pendiente.');
    }

    $db->begin_transaction();
    try{
      $restante = (float)$V['saldo'];

      $nuevos = [];

      $insA  = $db->prepare("INSERT INTO pos_abonos(id_empresa,caja_diaria_id,cliente_id,medio_id,fecha,monto,referencia,observacion,creado_por)
                             VALUES (?,?,?, ?, NOW(), ?, ?, ?, ?)");
      $insAp = $db->prepare("INSERT INTO pos_abono_aplicaciones(abono_id,venta_id,monto_aplicado,aplicado_en)
                             VALUES (?, ?, ?, NOW())");

      foreach($abonos as $a){
        $mid = (int)$a['medio_id'];
        $monto = money2($a['monto']);
        $ref = trim($a['referencia'] ?? '') ?: null;
        $obs = trim($a['observacion'] ?? '') ?: null;

        $insA->bind_param('iiiidssi', $empId, $caja_diaria_id, $V['cliente_id'], $mid, $monto, $ref, $obs, $uid);
        $insA->execute();
        $abono_id = (int)$db->insert_id;

        // Como ya validamos sumaNuevos <= restante, siempre aplica 100%
        $aplicar = $monto;
        $insAp->bind_param('iid', $abono_id, $ventaId, $aplicar);
        $insAp->execute();
        $restante = money2($restante - $aplicar);

        $nuevos[] = [
          'abono_id'  => $abono_id,
          'medio'     => $med[$mid]['nombre'],
          'monto'     => $monto,
          'aplicado'  => $aplicar,
          'referencia'=> $ref
        ];
      }

      $recalc = venta_recalcular_totales($db, $ventaId, (float)$V['total'], false);
      $total_pagado = (float)$recalc['pagado'];
      $saldo = (float)$recalc['saldo'];
      $total_devuelto = (float)$recalc['devuelto_total'];
      $upV = $db->prepare("UPDATE pos_ventas SET total_pagado=?, saldo=?, total_devuelto=? WHERE id=? LIMIT 1");
      $upV->bind_param('dddi', $total_pagado, $saldo, $total_devuelto, $ventaId);
      $upV->execute();

      // Snapshot inmutable del comprobante original de abono (operacion actual)
      $actorUsuario = (string)($u['usuario'] ?? '');
      $actorNombre = trim((string)($u['nombres'] ?? '') . ' ' . (string)($u['apellidos'] ?? ''));
      if ($actorNombre === '') $actorNombre = $actorUsuario;
      $idsNuevos = array_values(array_map(function($x){ return (int)($x['abono_id'] ?? 0); }, $nuevos));
      $idsNuevos = array_values(array_filter($idsNuevos, function($x){ return $x > 0; }));

      $payloadOriginalAbono = vh_build_payload(
        $db,
        $empId,
        $ventaId,
        'abono',
        $idsNuevos,
        'original',
        'EXACTO',
        [
          'id' => $uid,
          'usuario' => $actorUsuario,
          'nombre' => $actorNombre
        ]
      );
      $comprobanteAbonoId = vh_save_original_snapshot(
        $db,
        $empId,
        'ABONO',
        $ventaId,
        $payloadOriginalAbono,
        $uid,
        $actorUsuario,
        $actorNombre,
        'ticket80',
        'EXACTO',
        null
      );
      vh_link_snapshot_abonos($db, $comprobanteAbonoId, $ventaId, (array)($payloadOriginalAbono['abonos'] ?? []));

      $estadoVisual = venta_build_estado_visual((string)$V['estado'], (float)$saldo, (float)$total_devuelto);
      $db->commit();
      json_ok([
        'ticket'=> $V['serie'].'-'.pad4($V['numero']),
        'total'=> (float)$V['total'],
        'pagado'=> (float)$total_pagado,
        'saldo'=> (float)$saldo,
        'devuelto_total' => (float)$total_devuelto,
        'estado_venta' => (string)$V['estado'],
        'estado_code' => (string)$estadoVisual['code'],
        'estado_text' => (string)$estadoVisual['text'],
        'nuevos'=> $nuevos,
        'comprobante_id' => $comprobanteAbonoId
      ]);
    }catch(Throwable $e){
      $db->rollback();
      json_err_code(500,'No se pudo registrar abonos',['dev'=>$e->getMessage()]);
    }
  }

  /* =======================
   * POST venta_devolucion (anula y devuelve TODO lo aplicado)
   * ======================= */
  if ($accion === 'venta_devolucion') {
    if ($method!=='POST') json_err('Método no permitido');
    $ventaId = (int)($_POST['venta_id'] ?? 0);
    $motivo  = trim((string)($_POST['motivo'] ?? ''));
    if ($ventaId<=0) json_err('Venta inválida');
    if ($motivo==='') json_err('Debes indicar un motivo.');

    $V = fetch_venta_head($db,$empId,$ventaId);
    if (!$V) json_err('Venta no encontrada');
    if ((string)$V['estado'] === 'ANULADA') json_err('La venta ya tiene devolucion total.');

    $cd = getDiariaAbierta($db,$empId);
    if(!$cd) json_err('No hay caja diaria abierta. Cierra la pendiente o abre la del día actual.');
    $caja_diaria_id = (int)$cd['id'];

    // Aplicaciones de abonos con saldo aún no devuelto
    $q = $db->prepare("SELECT apl.id AS aplicacion_id, a.medio_id, apl.monto_aplicado,
                              COALESCE(SUM(d.monto_devuelto),0) AS ya_devuelto
                       FROM pos_abono_aplicaciones apl
                       JOIN pos_abonos a ON a.id=apl.abono_id
                       LEFT JOIN pos_devoluciones d ON d.abono_aplicacion_id=apl.id
                       WHERE apl.venta_id=?
                       GROUP BY apl.id, a.medio_id, apl.monto_aplicado
                       ORDER BY apl.id");
    $q->bind_param('i',$ventaId); $q->execute();
    $apps = $q->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];

    $db->begin_transaction();
    try{
      $insDev = $db->prepare("INSERT INTO pos_devoluciones(id_empresa,caja_diaria_id,venta_id,abono_aplicacion_id,medio_id,monto_devuelto,referencia,motivo,devuelto_por,devuelto_en)
                              VALUES (?,?,?,?,?,?,NULL,?,?,NOW())");

      $total_refund = 0.00;
      foreach($apps as $ap){
        $pend = money2(((float)$ap['monto_aplicado']) - ((float)$ap['ya_devuelto']));
        if ($pend <= 0) continue;
        $mid = (int)$ap['medio_id'];
        $apl = (int)$ap['aplicacion_id'];
        $insDev->bind_param('iiiiidsi', $empId, $caja_diaria_id, $ventaId, $apl, $mid, $pend, $motivo, $uid);
        $insDev->execute();
        $total_refund = money2($total_refund + $pend);
      }

      // Marcar ANULADA y luego recalcular totales para mantener consistencia monetaria.
      $upV = $db->prepare("UPDATE pos_ventas SET estado='ANULADA', observacion=CONCAT(COALESCE(observacion,''),' | DEVOLUCION TOTAL: ',?) WHERE id=? LIMIT 1");
      $upV->bind_param('si',$motivo,$ventaId); $upV->execute();

      $recalc = venta_recalcular_totales($db, $ventaId, (float)$V['total'], true);
      $pagadoFinal = (float)$recalc['pagado'];
      $saldoFinal = (float)$recalc['saldo'];
      $devueltoFinal = (float)$recalc['devuelto_total'];
      $upT = $db->prepare("UPDATE pos_ventas SET total_pagado=?, saldo=?, total_devuelto=? WHERE id=? LIMIT 1");
      $upT->bind_param('dddi', $pagadoFinal, $saldoFinal, $devueltoFinal, $ventaId);
      $upT->execute();

      $insA = $db->prepare("INSERT INTO pos_ventas_anulaciones(venta_id,motivo,anulado_por,anulado_en) VALUES (?,?,?,NOW())");
      $insA->bind_param('isi',$ventaId,$motivo,$uid); $insA->execute();

      $estadoVisual = venta_build_estado_visual('ANULADA', $saldoFinal, $devueltoFinal);
      $db->commit();
      json_ok([
        'msg' => 'Devolucion total registrada.',
        'devuelto' => $total_refund,
        'devuelto_total' => $devueltoFinal,
        'total' => (float)$V['total'],
        'pagado' => $pagadoFinal,
        'saldo' => $saldoFinal,
        'estado_venta' => 'ANULADA',
        'estado_code' => (string)$estadoVisual['code'],
        'estado_text' => (string)$estadoVisual['text']
      ]);
    }catch(Throwable $e){
      $db->rollback();
      json_err_code(500,'No se pudo realizar la devolución',['dev'=>$e->getMessage()]);
    }
  }

  /* =======================
   * POST venta_devolver_abono (devolver una aplicación específica)
   * ======================= */
  if ($accion === 'venta_devolver_abono') {
    if ($method!=='POST') json_err('Método no permitido');
    $ventaId = (int)($_POST['venta_id'] ?? 0);
    $aplId   = (int)($_POST['aplicacion_id'] ?? 0);
    $motivo  = trim((string)($_POST['motivo'] ?? ''));

    if ($ventaId<=0 || $aplId<=0) json_err('Parámetros inválidos');
    if ($motivo==='') json_err('Debes indicar un motivo.');

    $V = fetch_venta_head($db,$empId,$ventaId);
    if (!$V) json_err('Venta no encontrada');

    $cd = getDiariaAbierta($db,$empId);
    if(!$cd) json_err('No hay caja diaria abierta. Cierra la pendiente o abre la del día actual.');
    $caja_diaria_id = (int)$cd['id'];

    // Verificar aplicación pertenece a la venta y cuánto queda por devolver
    $q = $db->prepare("SELECT apl.id AS aplicacion_id, a.medio_id, apl.monto_aplicado,
                              COALESCE(SUM(d.monto_devuelto),0) AS ya_devuelto
                       FROM pos_abono_aplicaciones apl
                       JOIN pos_abonos a ON a.id=apl.abono_id
                       LEFT JOIN pos_devoluciones d ON d.abono_aplicacion_id=apl.id
                       WHERE apl.id=? AND apl.venta_id=?
                       GROUP BY apl.id, a.medio_id, apl.monto_aplicado
                       LIMIT 1");
    $q->bind_param('ii',$aplId,$ventaId); $q->execute();
    $ap = $q->get_result()->fetch_assoc();
    if (!$ap) json_err('Aplicación no encontrada');
    $pend = money2(((float)$ap['monto_aplicado']) - ((float)$ap['ya_devuelto']));
    if ($pend<=0) json_err('Esta aplicación ya fue devuelta en su totalidad.');

    $db->begin_transaction();
    try{
      // Registrar devolución
      $insDev = $db->prepare("INSERT INTO pos_devoluciones(id_empresa,caja_diaria_id,venta_id,abono_aplicacion_id,medio_id,monto_devuelto,referencia,motivo,devuelto_por,devuelto_en)
                              VALUES (?,?,?,?,?,?,NULL,?,?,NOW())");
      $mid = (int)$ap['medio_id'];
      $insDev->bind_param('iiiiidsi', $empId, $caja_diaria_id, $ventaId, $aplId, $mid, $pend, $motivo, $uid);
      $insDev->execute();

      $forzarAnulada = ((string)$V['estado'] === 'ANULADA');
      $recalc = venta_recalcular_totales($db, $ventaId, (float)$V['total'], $forzarAnulada);
      $new_pagado = (float)$recalc['pagado'];
      $new_saldo  = (float)$recalc['saldo'];
      $new_devuelto_total = (float)$recalc['devuelto_total'];

      $upV = $db->prepare("UPDATE pos_ventas SET total_pagado=?, saldo=?, total_devuelto=? WHERE id=? LIMIT 1");
      $upV->bind_param('dddi', $new_pagado, $new_saldo, $new_devuelto_total, $ventaId);
      $upV->execute();

      $estadoVisual = venta_build_estado_visual((string)$V['estado'], (float)$new_saldo, (float)$new_devuelto_total);
      $db->commit();
      json_ok([
        'msg' => 'Devolucion registrada.',
        'total' => (float)$V['total'],
        'pagado' => $new_pagado,
        'saldo' => $new_saldo,
        'devuelto' => $pend,
        'devuelto_total' => $new_devuelto_total,
        'estado_venta' => (string)$V['estado'],
        'estado_code' => (string)$estadoVisual['code'],
        'estado_text' => (string)$estadoVisual['text']
      ]);
    }catch(Throwable $e){
      $db->rollback();
      json_err_code(500,'No se pudo registrar la devolución',['dev'=>$e->getMessage()]);
    }
  }

  // Acción no reconocida
  json_err('Acción no reconocida.');
}
catch (Throwable $e) {
  json_err_code(500, 'Error no controlado', ['dev'=>$e->getMessage()]);
}


