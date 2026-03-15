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

function voucher_render_pdf(array $data, string $size, string $kind): void {
  $tcpdfFile = __DIR__ . '/../TCPDF/tcpdf.php';
  if (!file_exists($tcpdfFile)) {
    throw new RuntimeException('TCPDF no encontrado en modules/TCPDF.');
  }
  require_once $tcpdfFile;

  $size = voucher_norm_size($size);
  $kind = ($kind === 'abono') ? 'abono' : 'venta';

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
  $html .= '<tr><td class="b">Fecha</td><td class="right">' . voucher_h((string)($meta['fecha'] ?? '')) . '</td></tr>';
  if ($kind === 'abono' && !empty($meta['fecha_venta'])) {
    $html .= '<tr><td class="b">Fecha venta</td><td class="right">' . voucher_h((string)$meta['fecha_venta']) . '</td></tr>';
  }
  $html .= '<tr><td class="b">Cajero</td><td class="right">' . voucher_h((string)($meta['cajero'] ?? '')) . '</td></tr>';
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
      $metaRef = 'ABN-' . str_pad((string)$abnCode, 6, '0', STR_PAD_LEFT);
      if ($ref !== '') $metaRef .= ' - ' . $ref;
      if ($fechaAb !== '') $metaRef .= ' - ' . voucher_fmt_dt($fechaAb);
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

  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="voucher_' . ($kind === 'abono' ? 'abono' : 'venta') . '_' . preg_replace('/[^A-Za-z0-9\\-]/', '_', (string)($meta['ticket'] ?? 'doc')) . '.pdf"');
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
    if ($ventaId <= 0) json_err('Venta inválida.');

    $kind = strtolower(trim((string)($_GET['kind'] ?? 'venta')));
    $kind = ($kind === 'abono') ? 'abono' : 'venta';
    $size = voucher_norm_size($_GET['size'] ?? 'ticket80');
    $abonoIds = voucher_parse_ids_csv($_GET['abono_ids'] ?? '');

    $venta = fetch_venta_head($db, $empId, $ventaId);
    if (!$venta) json_err_code(404, 'Venta no encontrada.');

    $condRaw = fetch_principal_conductor($db, $ventaId) ?: [];
    $cond = resolve_conductor_payload(array_merge($venta, $condRaw));

    $empresa = voucher_fetch_empresa($db, $empId) ?: [
      'nombre' => '',
      'razon_social' => '',
      'ruc' => '',
      'direccion' => '',
      'logo_path' => ''
    ];

    $items = [];
    foreach (voucher_fetch_items($db, $ventaId) as $it) {
      $items[] = [
        'nombre' => (string)($it['servicio_nombre'] ?? ''),
        'cantidad' => (float)($it['cantidad'] ?? 0),
        'precio' => (float)($it['precio_unitario'] ?? 0),
        'total' => (float)($it['total_linea'] ?? 0)
      ];
    }

    $abonos = [];
    $abonosRaw = voucher_fetch_abonos($db, $ventaId, ($kind === 'abono') ? $abonoIds : []);
    if ($kind === 'abono' && !count($abonosRaw)) {
      json_err('No se encontraron abonos para imprimir.');
    }
    foreach ($abonosRaw as $ab) {
      $abonos[] = [
        'abono_id' => (int)($ab['abono_id'] ?? 0),
        'medio' => (string)($ab['medio'] ?? '—'),
        'referencia' => (string)($ab['referencia'] ?? ''),
        'monto' => (float)($ab['monto_aplicado'] ?? $ab['monto'] ?? 0),
        'fecha' => (string)($ab['fecha'] ?? '')
      ];
    }

    $clienteDocTipo = (string)($venta['c_doc_tipo'] ?? '');
    $clienteDocNum = (string)($venta['c_doc_numero'] ?? '');
    $clienteNombre = trim((string)($venta['c_nombre'] ?? ''));
    $clienteTelefono = trim((string)($venta['c_telefono'] ?? ''));
    $clienteDoc = trim($clienteDocTipo . ' ' . $clienteDocNum);

    $contrDoc = trim(((string)($venta['contratante_doc_tipo'] ?? '')) . ' ' . ((string)($venta['contratante_doc_numero'] ?? '')));
    $contrNombre = trim(((string)($venta['contratante_nombres'] ?? '')) . ' ' . ((string)($venta['contratante_apellidos'] ?? '')));
    $contrTelefono = trim((string)($venta['contratante_telefono'] ?? ''));

    $conductorDoc = trim(((string)($cond['doc_tipo'] ?? '')) . ' ' . ((string)($cond['doc_numero'] ?? '')));
    $conductorNombre = trim(((string)($cond['nombres'] ?? '')) . ' ' . ((string)($cond['apellidos'] ?? '')));
    $conductorTelefono = trim((string)($cond['telefono'] ?? ''));
    if ($conductorNombre === '') {
      $conductorNombre = 'No especificado';
    }

    $cajero = trim((string)(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')));
    if ($cajero === '') {
      $cajero = (string)($u['usuario'] ?? 'Usuario');
    }
    $fechaVenta = (string)($venta['fecha_emision'] ?? '');
    $fechaDoc = $fechaVenta;
    if ($kind === 'abono' && count($abonosRaw)) {
      $lastAb = end($abonosRaw);
      if (is_array($lastAb) && !empty($lastAb['fecha'])) {
        $fechaDoc = (string)$lastAb['fecha'];
      }
    }

    $data = [
      'empresa' => [
        'nombre' => (string)($empresa['nombre'] ?? ''),
        'razon_social' => (string)($empresa['razon_social'] ?? ''),
        'ruc' => (string)($empresa['ruc'] ?? ''),
        'direccion' => (string)($empresa['direccion'] ?? ''),
        'logo_data_uri' => voucher_logo_data_uri((string)($empresa['logo_path'] ?? ''))
      ],
      'meta' => [
        'ticket' => (string)$venta['serie'] . '-' . pad4((int)$venta['numero']),
        'fecha' => voucher_fmt_dt($fechaDoc),
        'fecha_venta' => voucher_fmt_dt($fechaVenta),
        'cajero' => $cajero
      ],
      'cliente' => [
        'doc' => $clienteDoc,
        'nombre' => $clienteNombre,
        'telefono' => $clienteTelefono
      ],
      'contratante' => [
        'doc' => $contrDoc,
        'nombre' => $contrNombre,
        'telefono' => $contrTelefono
      ],
      'conductor' => [
        'doc' => $conductorDoc,
        'nombre' => $conductorNombre,
        'telefono' => $conductorTelefono
      ],
      'items' => $items,
      'abonos' => $abonos,
      'totales' => [
        'total' => (float)($venta['total'] ?? 0),
        'pagado' => (float)($venta['total_pagado'] ?? 0),
        'saldo' => (float)($venta['saldo'] ?? 0),
        'devuelto' => (float)($venta['total_devuelto'] ?? 0)
      ]
    ];

    voucher_render_pdf($data, $size, $kind);
  }

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
