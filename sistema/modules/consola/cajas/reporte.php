<?php
// modules/consola/cajas/reporte.php
require_once __DIR__ . '/../../../includes/conexion.php';
@require_once __DIR__ . '/../../../includes/acl.php';
@require_once __DIR__ . '/../../../includes/permisos.php';

if (function_exists('acl_require_ids')) { acl_require_ids([1,6]); }
if (function_exists('verificarPermiso')) { verificarPermiso(['Desarrollo','Gerente']); }

$db = db();
$db->set_charset('utf8mb4');

// Hora Lima para PHP y para la sesión MySQL
if (function_exists('date_default_timezone_set')) { date_default_timezone_set('America/Lima'); }
try { $db->query("SET time_zone = 'America/Lima'"); }
catch (Throwable $e) { $db->query("SET time_zone = '-05:00'"); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function notfound($msg){ http_response_code(404); echo "<h1 style='font:16px sans-serif;color:#991b1b;'>".h($msg)."</h1>"; exit; }
function badreq($msg){ http_response_code(400); echo "<h1 style='font:16px sans-serif;color:#991b1b;'>".h($msg)."</h1>"; exit; }

$tipo = $_GET['tipo'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

$empresa_id = (int)($_GET['empresa_id'] ?? 0);
$anio  = (int)($_GET['anio'] ?? 0);
$mes   = (int)($_GET['mes'] ?? 0);
$fecha = trim($_GET['fecha'] ?? '');

$empresa = null;
$header  = [];
$audRows = [];

if ($tipo === 'mensual') {
  // 1) Intento normal: leer desde la tabla principal
  $m = null; $st = null;
  if ($id <= 0) {
    if ($empresa_id <= 0 || $anio <= 0 || $mes <= 0) badreq('Parámetros inválidos (mensual).');
    $st = $db->prepare("SELECT m.*, e.nombre AS empresa
                        FROM mod_caja_mensual m
                        JOIN mtp_empresas e ON e.id = m.id_empresa
                        WHERE m.id_empresa=? AND m.anio=? AND m.mes=? LIMIT 1");
    $st->bind_param('iii', $empresa_id, $anio, $mes);
  } else {
    $st = $db->prepare("SELECT m.*, e.nombre AS empresa
                        FROM mod_caja_mensual m
                        JOIN mtp_empresas e ON e.id = m.id_empresa
                        WHERE m.id=? LIMIT 1");
    $st->bind_param('i', $id);
  }
  $st->execute();
  $m = $st->get_result()->fetch_assoc();

  if ($m) {
    // Caja EXISTE => header normal
    $empresa = $m['empresa'];
    $periodo = sprintf('%04d-%02d', (int)$m['anio'], (int)$m['mes']);
    $header = [
      'Tipo'      => 'Mensual',
      'Empresa'   => $m['empresa'],
      'Periodo'   => $periodo,
      'Código'    => $m['codigo'],
      'Estado'    => $m['estado'],
      'Abierta en'=> $m['abierto_en'],
      'Cerrada en'=> $m['cerrado_en'] ?: '—',
    ];

    // Auditoría de esta caja
    $id_cm = (int)$m['id'];
    $q = $db->prepare("SELECT evento, detalle, actor_usuario, actor_nombre, ip, creado_en
                       FROM mod_caja_auditoria
                       WHERE id_caja_mensual=? ORDER BY creado_en ASC");
    $q->bind_param('i', $id_cm);
    $q->execute();
    $audRows = $q->get_result()->fetch_all(MYSQLI_ASSOC);

    // Conteo de eliminaciones previas del MISMO período (en auditoría)
    $qe = $db->prepare("SELECT COUNT(*) c
                        FROM mod_caja_auditoria
                        WHERE id_empresa=? AND evento='eliminar_mensual' AND detalle LIKE ?");
    $like = "%periodo=$periodo%";
    $qe->bind_param('is', $m['id_empresa'], $like);
    $qe->execute();
    $elimCount = (int)($qe->get_result()->fetch_assoc()['c'] ?? 0);

    // Agregar al header (informativo)
    $header['Eliminaciones previas (mismo período)'] = $elimCount;

  } else {
    // 2) Fallback: caja ELIMINADA -> mostrar desde la auditoría si se pasó un id
    if ($id <= 0) notfound('Caja mensual no encontrada (posible eliminación).');

    $empresa = '';
    $periodo = '—';
    $codigo  = '—';

    // Último evento para extraer período/código si están en detalle
    $qa = $db->prepare("SELECT id_empresa, evento, detalle, creado_en
                        FROM mod_caja_auditoria
                        WHERE id_caja_mensual=? ORDER BY creado_en DESC LIMIT 1");
    $qa->bind_param('i',$id); $qa->execute();
    $last = $qa->get_result()->fetch_assoc();
    if (!$last) notfound('Caja mensual no encontrada en auditoría.');

    $empresa_id = (int)$last['id_empresa'];

    // Nombre empresa
    $se = $db->prepare("SELECT nombre FROM mtp_empresas WHERE id=? LIMIT 1");
    $se->bind_param('i',$empresa_id); $se->execute();
    $empresa = $se->get_result()->fetch_assoc()['nombre'] ?? '';

    if (preg_match('/periodo=(\d{4}-\d{2})/',$last['detalle'],$mm)) $periodo=$mm[1];
    if (preg_match('/codigo=([A-Za-z0-9\-]+)/',$last['detalle'],$mc)) $codigo=$mc[1];

    $header = [
      'Tipo'      => 'Mensual (ELIMINADA)',
      'Empresa'   => $empresa ?: ('ID '.$empresa_id),
      'Periodo'   => $periodo,
      'Código'    => $codigo,
      'Estado'    => 'eliminada',
      'Abierta en'=> '—',
      'Cerrada en'=> '—',
    ];

    // Traer TODA la auditoría de ese id eliminado
    $q = $db->prepare("SELECT evento, detalle, actor_usuario, actor_nombre, ip, creado_en
                       FROM mod_caja_auditoria
                       WHERE id_caja_mensual=? ORDER BY creado_en ASC");
    $q->bind_param('i',$id); $q->execute();
    $audRows = $q->get_result()->fetch_all(MYSQLI_ASSOC);

    // ¿Cuántas eliminaciones hubo del MISMO período?
    if ($periodo !== '—') {
      $qe = $db->prepare("SELECT COUNT(*) c
                          FROM mod_caja_auditoria
                          WHERE id_empresa=? AND evento='eliminar_mensual' AND detalle LIKE ?");
      $like = "%periodo=$periodo%";
      $qe->bind_param('is', $empresa_id, $like);
      $qe->execute();
      $elimCount = (int)($qe->get_result()->fetch_assoc()['c'] ?? 0);
      $header['Eliminaciones previas (mismo período)'] = $elimCount;
    }
  }

} elseif ($tipo === 'diaria') {
  if ($id <= 0) {
    if ($empresa_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) badreq('Parámetros inválidos (diaria).');
    $st = $db->prepare("SELECT d.*, e.nombre AS empresa, m.anio, m.mes
                        FROM mod_caja_diaria d
                        JOIN mtp_empresas e ON e.id = d.id_empresa
                        JOIN mod_caja_mensual m ON m.id = d.id_caja_mensual
                        WHERE d.id_empresa=? AND d.fecha=? LIMIT 1");
    $st->bind_param('is', $empresa_id, $fecha);
  } else {
    $st = $db->prepare("SELECT d.*, e.nombre AS empresa, m.anio, m.mes
                        FROM mod_caja_diaria d
                        JOIN mtp_empresas e ON e.id = d.id_empresa
                        JOIN mod_caja_mensual m ON m.id = d.id_caja_mensual
                        WHERE d.id=? LIMIT 1");
    $st->bind_param('i', $id);
  }
  $st->execute();
  $d = $st->get_result()->fetch_assoc();
  if (!$d) notfound('Caja diaria no encontrada.');

  $empresa = $d['empresa'];
  $header = [
    'Tipo'       => 'Diaria',
    'Empresa'    => $d['empresa'],
    'Fecha'      => $d['fecha'],
    'Periodo'    => sprintf('%04d-%02d', (int)$d['anio'], (int)$d['mes']),
    'Código'     => $d['codigo'],
    'Estado'     => $d['estado'],
    'Abierta en' => $d['abierto_en'],
    'Cerrada en' => $d['cerrado_en'] ?: '—',
  ];

  $id_cd = (int)$d['id'];
  $q = $db->prepare("SELECT evento, detalle, actor_usuario, actor_nombre, ip, creado_en
                     FROM mod_caja_auditoria
                     WHERE id_caja_diaria=? ORDER BY creado_en ASC");
  $q->bind_param('i', $id_cd);
  $q->execute();
  $audRows = $q->get_result()->fetch_all(MYSQLI_ASSOC);

} else {
  badreq('Tipo inválido. Use tipo=mensual o tipo=diaria');
}

// Marca temporal de emisión (Lima)
$emitido = $db->query("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s') AS t")->fetch_assoc()['t'] ?? date('Y-m-d H:i:s');

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte de caja - <?php echo h($empresa ?: ''); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{ --fg:#111827; --muted:#6b7280; --line:#e5e7eb; --blue:#1d4ed8; }
    *{ box-sizing:border-box; }
    body{ font:14px/1.45 system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial, "Noto Sans"; color:var(--fg); margin:24px; }
    h1{ font-size:18px; margin:0 0 8px; }
    .meta{ margin:4px 0 16px; color:var(--muted); }
    .card{ border:1px solid var(--line); border-radius:8px; padding:12px; margin-bottom:16px; }
    .grid{ display:grid; grid-template-columns: 160px 1fr; gap:8px 16px; }
    table{ width:100%; border-collapse:collapse; }
    th, td{ padding:8px 10px; border-top:1px solid var(--line); text-align:left; vertical-align:top; }
    thead th{ border-top:0; background:#f9fafb; }
    .small{ font-size:12px; color:var(--muted); }
    .print{ margin:8px 0 16px; }
    .btn{ display:inline-block; padding:6px 10px; border:1px solid var(--line); border-radius:6px; background:#fff; cursor:pointer; }
    @media print{ .print{ display:none; } body{ margin:0; } .card{ margin-bottom:12px; } }
  </style>
</head>
<body>
  <div class="print">
    <button class="btn" onclick="window.print()">Imprimir / Guardar PDF</button>
  </div>

  <h1>Reporte de caja <?php echo h(strtolower($header['Tipo'] ?? '')); ?></h1>
  <div class="meta small">Emitido: <?php echo h($emitido); ?> (America/Lima)</div>

  <div class="card">
    <div class="grid">
      <?php foreach ($header as $k=>$v): ?>
        <div class="small"><?php echo h($k); ?></div>
        <div><strong><?php echo h((string)$v); ?></strong></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h2 style="font-size:16px; margin:0 0 8px;">Auditoría de eventos</h2>
    <table>
      <thead>
        <tr>
          <th style="width:160px;">Fecha/Hora (Lima)</th>
          <th style="width:140px;">Evento</th>
          <th>Detalle</th>
          <th style="width:160px;">Usuario</th>
          <th style="width:120px;">IP</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$audRows): ?>
          <tr><td colspan="5" class="small">Sin eventos registrados.</td></tr>
        <?php else: foreach ($audRows as $r): ?>
          <tr>
            <td><?php echo h($r['creado_en']); ?></td>
            <td><?php echo h($r['evento']); ?></td>
            <td><?php echo h($r['detalle'] ?? ''); ?></td>
            <td><?php echo h(($r['actor_usuario'] ?: '').' - '.($r['actor_nombre'] ?: '')); ?></td>
            <td><?php echo h($r['ip'] ?: ''); ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
