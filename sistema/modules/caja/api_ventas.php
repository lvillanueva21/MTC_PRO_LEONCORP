<?php
// /modules/caja/api_ventas.php
// API para "Ventas pendientes": buscar ventas, ver detalle, registrar abonos,
// anular ventas, devolución total y devolución por abono.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([3,4]); // Recepción (3) o Administración (4)
verificarPermiso(['Recepción','Administración']);

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
  $hasConductorRegistrado = row_has_any($row, ['d_doc_tipo', 'd_doc_numero', 'd_nombres', 'd_apellidos', 'd_telefono']);
  if (($tipoRelacion === 'REGISTRADO' || $tipoRelacion === '') && $hasConductorRegistrado) {
    return [
      'doc_tipo'   => (string)row_pick($row, ['d_doc_tipo']),
      'doc_numero' => (string)row_pick($row, ['d_doc_numero', 'd_doc_num']),
      'nombres'    => (string)row_pick($row, ['d_nombres']),
      'apellidos'  => (string)row_pick($row, ['d_apellidos']),
      'telefono'   => (string)row_pick($row, ['d_telefono'])
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
      v.contratante_doc_tipo, v.contratante_doc_numero, v.contratante_nombres, v.contratante_apellidos, v.contratante_telefono,
      c.doc_tipo AS c_doc_tipo, c.doc_numero AS c_doc_numero, c.nombre AS c_nombre, c.telefono AS c_telefono
    FROM pos_ventas v
    LEFT JOIN pos_clientes c ON c.id=v.cliente_id
    WHERE v.id_empresa=? AND v.id=? LIMIT 1");
  $q->bind_param('ii',$empId,$ventaId); $q->execute();
  return $q->get_result()->fetch_assoc() ?: null;
}

function fetch_principal_conductor(mysqli $db, int $ventaId){
  $q = $db->prepare("SELECT vc.conductor_tipo AS vc_conductor_tipo,
                            d.doc_tipo AS d_doc_tipo, d.doc_numero AS d_doc_numero,
                            d.nombres AS d_nombres, d.apellidos AS d_apellidos, d.telefono AS d_telefono
                     FROM pos_venta_conductores vc
                     LEFT JOIN pos_conductores d ON d.id=vc.conductor_id
                     WHERE vc.venta_id=? AND vc.es_principal=1
                     LIMIT 1");
  $q->bind_param('i',$ventaId); $q->execute();
  return $q->get_result()->fetch_assoc() ?: null;
}

/* =========================
 * Ruteo
 * ========================= */
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? $_POST['accion'] ?? '';

try {

  /* =======================
   * GET ventas_buscar
   * ======================= */
  if ($accion === 'ventas_buscar') {
    $q      = trim((string)($_GET['q'] ?? ''));
    $estado = $_GET['estado'] ?? 'pending'; // pending | paid | void | refund | all
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
        . "c.doc_tipo LIKE ? OR c.doc_numero LIKE ? OR c.nombre LIKE ?"
        . " OR v.contratante_doc_tipo LIKE ? OR v.contratante_doc_numero LIKE ?"
        . " OR CONCAT_WS(' ', v.contratante_nombres, v.contratante_apellidos) LIKE ?"
        . " OR d.doc_tipo LIKE ? OR d.doc_numero LIKE ?"
        . " OR CONCAT_WS(' ', d.nombres, d.apellidos) LIKE ?"
        . " OR CONCAT(v.serie,'-',LPAD(v.numero,4,'0')) LIKE ?"
        . ")";
      $types .= "ssssssssss";
      $like = like_wrap($q);
      array_push($pars, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like);
    }

    // Filtro por estado (basado en saldo + devoluciones + anulación)
    // NOTA: usamos alias r.devuelto_total, así que debe existir en los JOINs
    switch ($estado) {
      case 'pending':
        $where[] = "v.estado<>'ANULADA' AND v.saldo > 0";
        break;
      case 'paid':
        $where[] = "v.estado<>'ANULADA' AND v.saldo = 0";
        break;
      case 'void':
        $where[] = "v.estado='ANULADA'";
        break;
      case 'refund':
        $where[] = "COALESCE(r.devuelto_total,0) > 0";
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
              c.doc_tipo  AS c_doc_tipo,
              c.doc_numero AS c_doc_num,
              c.nombre     AS c_nombre,
              c.telefono   AS c_telefono,
              v.contratante_doc_tipo   AS ct_doc_tipo,
              v.contratante_doc_numero AS ct_doc_num,
              v.contratante_nombres    AS ct_nombres,
              v.contratante_apellidos  AS ct_apellidos,
              v.contratante_telefono   AS ct_telefono,
              vc.conductor_tipo        AS vc_conductor_tipo,
              d.doc_tipo   AS d_doc_tipo,
              d.doc_numero AS d_doc_num,
              d.nombres    AS d_nombres,
              d.apellidos  AS d_apellidos,
              d.telefono   AS d_telefono
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

      // Precedencia visual: refund > void > pending > paid
      if ($devuelto > 0.000001) {
        $estado_code = 'refund'; $estado_text = 'Con devolución';
      } elseif ($estadoVenta === 'ANULADA') {
        $estado_code = 'void';   $estado_text = 'Anulada';
      } elseif ($saldo > 0.000001) {
        $estado_code = 'pending'; $estado_text = 'Pendiente';
      } else {
        $estado_code = 'paid';    $estado_text = 'Pagado';
      }

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

    // Abonos aplicados (con id de aplicación para posibles devoluciones)
    $qa = $db->prepare("SELECT apl.id AS aplicacion_id, mp.nombre AS medio, a.monto,
                               apl.monto_aplicado, a.referencia, a.fecha
                        FROM pos_abono_aplicaciones apl
                        JOIN pos_abonos a ON a.id=apl.abono_id
                        JOIN pos_medios_pago mp ON mp.id=a.medio_id
                        WHERE apl.venta_id=?
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
        'nombre'   => $V['c_nombre']
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
      $total_pagado   = (float)$V['total_pagado'];
      $total_devuelto = (float)$V['total_devuelto']; // no se incrementa aquí

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
        $total_pagado = money2($total_pagado + $aplicar);

        $nuevos[] = [
          'abono_id'  => $abono_id,
          'medio'     => $med[$mid]['nombre'],
          'monto'     => $monto,
          'aplicado'  => $aplicar,
          'referencia'=> $ref
        ];
      }

      $saldo = max(0.00, money2(((float)$V['total']) - $total_pagado));
      $upV = $db->prepare("UPDATE pos_ventas SET total_pagado=?, saldo=?, total_devuelto=? WHERE id=? LIMIT 1");
      $upV->bind_param('dddi', $total_pagado, $saldo, $total_devuelto, $ventaId);
      $upV->execute();

      $db->commit();
      json_ok([
        'ticket'=> $V['serie'].'-'.pad4($V['numero']),
        'total'=> (float)$V['total'],
        'pagado'=> (float)$total_pagado,
        'saldo'=> (float)$saldo,
        'nuevos'=> $nuevos
      ]);
    }catch(Throwable $e){
      $db->rollback();
      json_err_code(500,'No se pudo registrar abonos',['dev'=>$e->getMessage()]);
    }
  }

  /* =======================
   * POST venta_anular (sin devoluciones automáticas)
   * ======================= */
  if ($accion === 'venta_anular') {
    if ($method!=='POST') json_err('Método no permitido');
    $ventaId = (int)($_POST['venta_id'] ?? 0);
    $motivo  = trim((string)($_POST['motivo'] ?? ''));
    if ($ventaId<=0) json_err('Venta inválida');
    if ($motivo==='') json_err('Debes indicar un motivo.');

    $V = fetch_venta_head($db,$empId,$ventaId);
    if (!$V) json_err('Venta no encontrada');
    if ($V['estado']==='ANULADA') json_err('La venta ya está anulada.');

    $db->begin_transaction();
    try{
      $up = $db->prepare("UPDATE pos_ventas SET estado='ANULADA', observacion=CONCAT(COALESCE(observacion,''),' | ANULADA: ',?) WHERE id=? LIMIT 1");
      $up->bind_param('si',$motivo,$ventaId); $up->execute();

      $ins = $db->prepare("INSERT INTO pos_ventas_anulaciones(venta_id,motivo,anulado_por,anulado_en)
                           VALUES (?,?,?,NOW())");
      $ins->bind_param('isi',$ventaId,$motivo,$uid); $ins->execute();

      $db->commit();
      json_ok(['msg'=>'Venta anulada.']);
    }catch(Throwable $e){
      $db->rollback();
      json_err_code(500,'No se pudo anular la venta',['dev'=>$e->getMessage()]);
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

      // Marcar ANULADA y llevar totales a 0 (no queda saldo ni pagado)
      $upV = $db->prepare("UPDATE pos_ventas SET estado='ANULADA', total_pagado=0.00, saldo=0.00, observacion=CONCAT(COALESCE(observacion,''),' | DEVOLUCIÓN: ',?) WHERE id=? LIMIT 1");
      $upV->bind_param('si',$motivo,$ventaId); $upV->execute();

      $insA = $db->prepare("INSERT INTO pos_ventas_anulaciones(venta_id,motivo,anulado_por,anulado_en) VALUES (?,?,?,NOW())");
      $insA->bind_param('isi',$ventaId,$motivo,$uid); $insA->execute();

      $db->commit();
      json_ok(['msg'=>'Venta anulada con devolución total.','devuelto'=>$total_refund]);
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

      // Actualizar totales de la venta (si no está anulada)
      $new_pagado = max(0.00, money2(((float)$V['total_pagado']) - $pend));
      $new_saldo  = max(0.00, money2(((float)$V['total']) - $new_pagado));
      $upV = $db->prepare("UPDATE pos_ventas SET total_pagado=?, saldo=? WHERE id=? LIMIT 1");
      $upV->bind_param('ddi', $new_pagado, $new_saldo, $ventaId);
      $upV->execute();

      $db->commit();
      json_ok(['msg'=>'Devolución registrada.','total'=>$V['total'],'pagado'=>$new_pagado,'saldo'=>$new_saldo,'devuelto'=>$pend]);
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
