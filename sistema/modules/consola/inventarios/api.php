<?php
// modules/consola/inventarios/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/acl.php';

// --- Zona horaria Lima (PHP + MySQL) ---
date_default_timezone_set('America/Lima');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = db();
$mysqli->set_charset('utf8mb4');
// Para MySQL: intenta usar nombre de zona; si no existe, usa offset fijo -05:00
try { $mysqli->query("SET time_zone = 'America/Lima'"); } catch(Throwable $e) {
  try { $mysqli->query("SET time_zone = '-05:00'"); } catch(Throwable $e2) {}
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Respuestas JSON
function jerror($code, $msg, $extra = []) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false, 'msg'=>$msg] + $extra, JSON_UNESCAPED_UNICODE);
  exit;
}
function jok($arr = []) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>true] + $arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Especificaciones por tipo
 * - fields: campos permitidos para create/update
 * - q: columnas donde aplicar LIKE para búsqueda
 */
function spec($tipo) {
  $map = [
    'pc' => [
      'label_sg' => 'Computadora', 'label_pl' => 'Computadoras',
      'table' => 'iv_computadoras',
      'fields' => ['ambiente','nombre_equipo','marca','modelo','serie','procesador','disco_gb','ram_gb','sistema_operativo','mac','ip','notas'],
      'q' => ['ambiente','nombre_equipo','marca','modelo','serie','procesador','sistema_operativo','mac','ip','notas']
    ],
    'cam' => [
      'label_sg' => 'Cámara', 'label_pl' => 'Cámaras',
      'table' => 'iv_camaras',
      'fields' => ['etiqueta','ambiente','marca','modelo','serie','notas'],
      'q' => ['etiqueta','ambiente','marca','modelo','serie','notas']
    ],
    'dvr' => [
      'label_sg' => 'DVR', 'label_pl' => 'DVR',
      'table' => 'iv_dvrs',
      'fields' => ['marca','modelo','serie','notas'],
      'q' => ['marca','modelo','serie','notas']
    ],
    'hue' => [
      'label_sg' => 'Huellero', 'label_pl' => 'Huelleros',
      'table' => 'iv_huelleros',
      'fields' => ['etiqueta','marca','modelo','serie','notas'],
      'q' => ['etiqueta','marca','modelo','serie','notas']
    ],
    'sw' => [
      'label_sg' => 'Switch', 'label_pl' => 'Switches',
      'table' => 'iv_switches',
      'fields' => ['marca','modelo','serie','notas'],
      'q' => ['marca','modelo','serie','notas']
    ],
    'red' => [
      'label_sg' => 'Datos de la red', 'label_pl' => 'Datos de la red',
      'table' => 'iv_red',
      'fields' => ['ip_publica','transmision_online','bajada_txt','subida_txt','notas'],
      'q' => ['ip_publica','transmision_online','bajada_txt','subida_txt','notas']
    ],
    'tx' => [
      'label_sg' => 'Acceso de transmisión', 'label_pl' => 'Datos de acceso a la transmisión',
      'table' => 'iv_transmision',
      'fields' => ['acceso_url','usuario','clave','notas'],
      'q' => ['acceso_url','usuario','clave','notas']
    ],
  ];
  return $map[$tipo] ?? null;
}

// Limita longitud para evitar overflow/errores de collation
function cut($s, $max=255){ $s = (string)($s ?? ''); return mb_substr($s, 0, $max, 'UTF-8'); }

try {
  // =======================================================
  // REPORT: devuelve HTML listo para imprimir (como PDF)
  // =======================================================
  if ($action === 'report') {
    $empresaId = (int)($_GET['empresa_id'] ?? 0);
    if ($empresaId <= 0) { http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo "Empresa inválida"; exit; }

    // Datos empresa
    $empresa = null;
    try {
      $st = $mysqli->prepare("SELECT id, nombre, razon_social, ruc FROM mtp_empresas WHERE id=?");
      $st->bind_param('i', $empresaId); $st->execute();
      $empresa = $st->get_result()->fetch_assoc();
    } catch(Throwable $e){}
    if (!$empresa) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo "Empresa no encontrada"; exit; }

    // Última actualización (vista preferente, luego fallback)
    $ultima = null;
    try {
      $st = $mysqli->prepare("SELECT ultima_actualizacion FROM iv_inventario_ultima_vw WHERE id_empresa=?");
      $st->bind_param('i',$empresaId); $st->execute();
      $ultima = $st->get_result()->fetch_column();
    } catch(Throwable $e){}
    if (!$ultima) {
      try {
        $st = $mysqli->prepare("SELECT ultima_actualizacion FROM iv_inventario_ultima_actualizacion WHERE id_empresa=?");
        $st->bind_param('i',$empresaId); $st->execute();
        $ultima = $st->get_result()->fetch_column();
      } catch(Throwable $e){}
    }

    // Lecturas (solo activos, orden por id asc para impresión estable)
    $pcs=$cams=$dvrs=$hues=$sws=$reds=$txs=[];
    try { $st=$mysqli->prepare("SELECT * FROM iv_computadoras WHERE id_empresa=? AND activo=1 ORDER BY id"); $st->bind_param('i',$empresaId); $st->execute(); $pcs=$st->get_result()->fetch_all(MYSQLI_ASSOC);}catch(Throwable $e){}
    try { $st=$mysqli->prepare("SELECT * FROM iv_camaras      WHERE id_empresa=? AND activo=1 ORDER BY id"); $st->bind_param('i',$empresaId); $st->execute(); $cams=$st->get_result()->fetch_all(MYSQLI_ASSOC);}catch(Throwable $e){}
    try { $st=$mysqli->prepare("SELECT * FROM iv_dvrs         WHERE id_empresa=? AND activo=1 ORDER BY id"); $st->bind_param('i',$empresaId); $st->execute(); $dvrs=$st->get_result()->fetch_all(MYSQLI_ASSOC);}catch(Throwable $e){}
    try { $st=$mysqli->prepare("SELECT * FROM iv_huelleros    WHERE id_empresa=? AND activo=1 ORDER BY id"); $st->bind_param('i',$empresaId); $st->execute(); $hues=$st->get_result()->fetch_all(MYSQLI_ASSOC);}catch(Throwable $e){}
    try { $st=$mysqli->prepare("SELECT * FROM iv_switches     WHERE id_empresa=? AND activo=1 ORDER BY id"); $st->bind_param('i',$empresaId); $st->execute(); $sws=$st->get_result()->fetch_all(MYSQLI_ASSOC);}catch(Throwable $e){}
    try { $st=$mysqli->prepare("SELECT * FROM iv_red          WHERE id_empresa=? AND activo=1 ORDER BY id"); $st->bind_param('i',$empresaId); $st->execute(); $reds=$st->get_result()->fetch_all(MYSQLI_ASSOC);}catch(Throwable $e){}
    try { $st=$mysqli->prepare("SELECT * FROM iv_transmision  WHERE id_empresa=? AND activo=1 ORDER BY id"); $st->bind_param('i',$empresaId); $st->execute(); $txs=$st->get_result()->fetch_all(MYSQLI_ASSOC);}catch(Throwable $e){}

    $hoy = (new DateTime('now'))->format('d/m/Y H:i');

    // Render HTML igual al antiguo inventario_pdf.php
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Inventario - <?= h($empresa['nombre'] ?? ('Empresa '.$empresaId)) ?></title>
  <style>
    @page { size: A4 portrait; margin: 12mm; }  /* ← VERTICAL */
    body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; color:#111; font-size:12px; line-height:1.25; }
    .inv-print-root { }
    h1 { font-size:18px; margin:0 0 4px; }
    h2 { font-size:14px; margin:18px 0 6px; border-bottom:1px solid #ddd; padding-bottom:3px; }
    h3 { font-size:13px; margin:12px 0 4px; }
    .meta { color:#555; margin-bottom:8px; }
    .kv { width:100%; border-collapse: collapse; margin:4px 0 10px; }
    .kv th, .kv td { border:1px solid #ddd; padding:5px 6px; vertical-align: top; }
    .kv th { width:38%; background:#f5f5f5; text-align:left; font-weight:600; }
    .section { break-inside: avoid; }
    .muted { color:#777; }
  </style>
</head>
<body>
<div class="inv-print-root">
  <h1>Inventario: <?= h($empresa['nombre'] ?? '') ?></h1>
  <div class="meta">
    RUC: <strong><?= h($empresa['ruc'] ?? '—') ?></strong><br>
    Razón social: <strong><?= h($empresa['razon_social'] ?? '—') ?></strong><br>
    Generado: <strong><?= h($hoy) ?></strong> · Última actualización: <strong><?= h($ultima ?: '—') ?></strong>
  </div>

  <!-- COMPUTADORAS -->
  <h2>Computadoras</h2>
  <?php if (!$pcs): ?>
    <div class="muted">Sin registros.</div>
  <?php else: foreach($pcs as $i=>$r): ?>
    <div class="section">
      <h3>Computadora #<?= $i+1 ?></h3>
      <table class="kv">
        <tr><th>Ambiente</th><td><?= h($r['ambiente']) ?: '—' ?></td></tr>
        <tr><th>Nombre del equipo</th><td><?= h($r['nombre_equipo']) ?: '—' ?></td></tr>
        <tr><th>Marca</th><td><?= h($r['marca']) ?: '—' ?></td></tr>
        <tr><th>Modelo</th><td><?= h($r['modelo']) ?: '—' ?></td></tr>
        <tr><th>Número de serie</th><td><?= h($r['serie']) ?: '—' ?></td></tr>
        <tr><th>Procesador</th><td><?= h($r['procesador']) ?: '—' ?></td></tr>
        <tr><th>Disco</th><td><?= h($r['disco_gb']) ?: '—' ?></td></tr>
        <tr><th>RAM</th><td><?= h($r['ram_gb']) ?: '—' ?></td></tr>
        <tr><th>Sistema operativo</th><td><?= h($r['sistema_operativo']) ?: '—' ?></td></tr>
        <tr><th>MAC</th><td><?= h($r['mac']) ?: '—' ?></td></tr>
        <tr><th>IP</th><td><?= h($r['ip']) ?: '—' ?></td></tr>
        <tr><th>Notas</th><td><?= h($r['notas']) ?: '—' ?></td></tr>
      </table>
    </div>
  <?php endforeach; endif; ?>

  <!-- CÁMARAS -->
  <h2>Cámaras</h2>
  <?php if (!$cams): ?>
    <div class="muted">Sin registros.</div>
  <?php else: foreach($cams as $i=>$r): ?>
    <div class="section">
      <h3>Cámara #<?= $i+1 ?> <?= $r['etiqueta'] ? '— '.h($r['etiqueta']) : '' ?></h3>
      <table class="kv">
        <tr><th>Ambiente</th><td><?= h($r['ambiente']) ?: '—' ?></td></tr>
        <tr><th>Marca</th><td><?= h($r['marca']) ?: '—' ?></td></tr>
        <tr><th>Modelo</th><td><?= h($r['modelo']) ?: '—' ?></td></tr>
        <tr><th>Número de serie</th><td><?= h($r['serie']) ?: '—' ?></td></tr>
        <tr><th>Notas</th><td><?= h($r['notas']) ?: '—' ?></td></tr>
      </table>
    </div>
  <?php endforeach; endif; ?>

  <!-- DVR -->
  <h2>DVR</h2>
  <?php if (!$dvrs): ?>
    <div class="muted">Sin registros.</div>
  <?php else: foreach($dvrs as $i=>$r): ?>
    <div class="section">
      <h3>DVR #<?= $i+1 ?></h3>
      <table class="kv">
        <tr><th>Marca</th><td><?= h($r['marca']) ?: '—' ?></td></tr>
        <tr><th>Modelo</th><td><?= h($r['modelo']) ?: '—' ?></td></tr>
        <tr><th>Número de serie</th><td><?= h($r['serie']) ?: '—' ?></td></tr>
      </table>
    </div>
  <?php endforeach; endif; ?>

  <!-- HUELLEROS -->
  <h2>Huelleros</h2>
  <?php if (!$hues): ?>
    <div class="muted">Sin registros.</div>
  <?php else: foreach($hues as $i=>$r): ?>
    <div class="section">
      <h3>Huellero #<?= $i+1 ?> <?= $r['etiqueta'] ? '— '.h($r['etiqueta']) : '' ?></h3>
      <table class="kv">
        <tr><th>Marca</th><td><?= h($r['marca']) ?: '—' ?></td></tr>
        <tr><th>Modelo</th><td><?= h($r['modelo']) ?: '—' ?></td></tr>
        <tr><th>Número de serie</th><td><?= h($r['serie']) ?: '—' ?></td></tr>
        <tr><th>Notas</th><td><?= h($r['notas']) ?: '—' ?></td></tr>
      </table>
    </div>
  <?php endforeach; endif; ?>

  <!-- SWITCHES -->
  <h2>Switches</h2>
  <?php if (!$sws): ?>
    <div class="muted">Sin registros.</div>
  <?php else: foreach($sws as $i=>$r): ?>
    <div class="section">
      <h3>Switch #<?= $i+1 ?></h3>
      <table class="kv">
        <tr><th>Marca</th><td><?= h($r['marca']) ?: '—' ?></td></tr>
        <tr><th>Modelo</th><td><?= h($r['modelo']) ?: '—' ?></td></tr>
        <tr><th>Número de serie</th><td><?= h($r['serie']) ?: '—' ?></td></tr>
        <tr><th>Notas</th><td><?= h($r['notas']) ?: '—' ?></td></tr>
      </table>
    </div>
  <?php endforeach; endif; ?>

  <!-- DATOS DE LA RED -->
  <h2>Datos de la red</h2>
  <?php if (!$reds): ?>
    <div class="muted">Sin registros.</div>
  <?php else: foreach($reds as $i=>$r): ?>
    <div class="section">
      <h3>Red #<?= $i+1 ?></h3>
      <table class="kv">
        <tr><th>IP pública</th><td><?= h($r['ip_publica']) ?: '—' ?></td></tr>
        <tr><th>Transmisión en línea</th><td><?= h($r['transmision_online']) ?: '—' ?></td></tr>
        <tr><th>Ancho de banda (bajada)</th><td><?= h($r['bajada_txt']) ?: '—' ?></td></tr>
        <tr><th>Ancho de banda (subida)</th><td><?= h($r['subida_txt']) ?: '—' ?></td></tr>
        <tr><th>Notas</th><td><?= h($r['notas']) ?: '—' ?></td></tr>
      </table>
    </div>
  <?php endforeach; endif; ?>

  <!-- DATOS DE ACCESO A LA TRANSMISIÓN -->
  <h2>Datos de acceso a la transmisión</h2>
  <?php if (!$txs): ?>
    <div class="muted">Sin registros.</div>
  <?php else: foreach($txs as $i=>$r): ?>
    <div class="section">
      <h3>Acceso #<?= $i+1 ?></h3>
      <table class="kv">
        <tr><th>Acceso (URL)</th><td><?= h($r['acceso_url']) ?: '—' ?></td></tr>
        <tr><th>Usuario</th><td><?= h($r['usuario']) ?: '—' ?></td></tr>
        <tr><th>Contraseña</th><td><?= h($r['clave']) ?: '—' ?></td></tr>
        <tr><th>Notas</th><td><?= h($r['notas']) ?: '—' ?></td></tr>
      </table>
    </div>
  <?php endforeach; endif; ?>

</div>
<script>
// Si se abre directamente la URL del reporte, abre diálogo de impresión automáticamente
try { window.print?.(); } catch(_){}
</script>
</body>
</html>
<?php
    exit;
  }

  // =======================================================
  // Resto de acciones JSON (igual que antes)
  // =======================================================
  switch ($action) {

    // ===== Empresas =====
    case 'empresas': {
      $rs = $mysqli->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    // ===== Última actualización (vista A o B) =====
    case 'ultima': {
      $empresa_id = (int)($_GET['empresa_id'] ?? 0);
      if ($empresa_id <= 0) jerror(400, 'Empresa inválida');

      $ultima = null;
      try {
        $st = $mysqli->prepare("SELECT ultima_actualizacion FROM iv_inventario_ultima_vw WHERE id_empresa=?");
        $st->bind_param('i', $empresa_id); $st->execute();
        $ultima = $st->get_result()->fetch_column();
      } catch(Throwable $e) {}

      if (!$ultima) {
        try {
          $st = $mysqli->prepare("SELECT ultima_actualizacion FROM iv_inventario_ultima_actualizacion WHERE id_empresa=?");
          $st->bind_param('i', $empresa_id); $st->execute();
          $ultima = $st->get_result()->fetch_column();
        } catch(Throwable $e) {}
      }
      jok(['empresa_id'=>$empresa_id, 'ultima'=>$ultima]);
    }

    // ===== Listado con filtros/paginación =====
    case 'list': {
      $tipo = $_GET['tipo'] ?? '';
      $S = spec($tipo); if (!$S) jerror(400, 'Tipo inválido');

      $empresa_id = (int)($_GET['empresa_id'] ?? 0);
      if ($empresa_id <= 0) jerror(400, 'Empresa requerida');

      $estado  = $_GET['estado'] ?? ''; // '', '1', '0'
      $q       = trim((string)($_GET['q'] ?? ''));
      $page    = max(1, (int)($_GET['page'] ?? 1));
      $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
      $offset  = ($page - 1) * $perPage;

      $where = ["id_empresa = ?"]; $types='i'; $pars=[$empresa_id];
      if ($estado === '0' || $estado === '1') { $where[]="activo=?"; $types.='i'; $pars[]=(int)$estado; }
      if ($q !== '') {
        $like = "%$q%";
        $ors=[]; foreach($S['q'] as $c){ $ors[]="$c LIKE ? COLLATE utf8mb4_spanish_ci"; $types.='s'; $pars[]=$like; }
        $where[] = '('.implode(' OR ',$ors).')';
      }
      $W = 'WHERE '.implode(' AND ',$where);

      // total
      $stC = $mysqli->prepare("SELECT COUNT(*) c FROM {$S['table']} $W");
      $stC->bind_param($types, ...$pars); $stC->execute();
      $total = (int)$stC->get_result()->fetch_assoc()['c'];

      // datos
      $stD = $mysqli->prepare("SELECT * FROM {$S['table']} $W ORDER BY id DESC LIMIT ? OFFSET ?");
      $types2=$types.'ii'; $pars2=$pars; $pars2[]=$perPage; $pars2[]=$offset;
      $stD->bind_param($types2, ...$pars2); $stD->execute();
      $rows = $stD->get_result()->fetch_all(MYSQLI_ASSOC);

      jok(['data'=>$rows, 'total'=>$total, 'page'=>$page, 'per_page'=>$perPage]);
    }

    // ===== Crear =====
    case 'create': {
      $tipo = $_POST['tipo'] ?? ''; $S = spec($tipo);
      if (!$S) jerror(400, 'Tipo inválido');

      $empresa_id = (int)($_POST['empresa_id'] ?? 0);
      if ($empresa_id <= 0) jerror(400, 'Empresa requerida');

      $cols=['id_empresa']; $phs=['?']; $types='i'; $pars=[$empresa_id];
      foreach ($S['fields'] as $f) { $cols[]=$f; $phs[]='?'; $types.='s'; $pars[] = cut($_POST[$f] ?? null, 255); }

      $sql="INSERT INTO {$S['table']} (".implode(',',$cols).") VALUES (".implode(',',$phs).")";
      $st=$mysqli->prepare($sql);
      $st->bind_param($types, ...$pars);
      $st->execute();
      jok(['id'=>(int)$mysqli->insert_id]);
    }

    // ===== Actualizar (parcial) =====
    case 'update': {
      $tipo = $_POST['tipo'] ?? ''; $S = spec($tipo);
      if (!$S) jerror(400, 'Tipo inválido');

      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) jerror(400, 'ID inválido');

      $sets=[]; $types=''; $pars=[];
      foreach ($S['fields'] as $f) {
        if (array_key_exists($f, $_POST)) { $sets[]="$f=?"; $types.='s'; $pars[] = cut($_POST[$f], 255); }
      }
      if (!$sets) jerror(400, 'Nada para actualizar');

      $sql="UPDATE {$S['table']} SET ".implode(',',$sets)." WHERE id=?";
      $types.='i'; $pars[]=$id;

      $st = $mysqli->prepare($sql);
      $st->bind_param($types, ...$pars);
      $st->execute();
      jok(['id'=>$id]);
    }

    // ===== Eliminar =====
    case 'delete': {
      $tipo = $_POST['tipo'] ?? ''; $S = spec($tipo);
      if (!$S) jerror(400, 'Tipo inválido');
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) jerror(400, 'ID inválido');

      $st=$mysqli->prepare("DELETE FROM {$S['table']} WHERE id=?");
      $st->bind_param('i', $id); $st->execute();
      jok(['id'=>$id]);
    }

    // ===== Activar/Desactivar =====
    case 'set_activo': {
      $tipo=$_POST['tipo']??''; $S=spec($tipo);
      if (!$S) jerror(400, 'Tipo inválido');

      $id=(int)($_POST['id']??0);
      $activo = ($_POST['activo'] ?? '') !== '' ? (int)$_POST['activo'] : null;
      if ($id<=0 || !in_array($activo,[0,1],true)) jerror(400, 'Parámetros inválidos');

      $st=$mysqli->prepare("UPDATE {$S['table']} SET activo=? WHERE id=?");
      $st->bind_param('ii', $activo, $id);
      $st->execute();
      jok(['id'=>$id,'activo'=>$activo]);
    }

    default: jerror(400, 'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  jerror(500, 'Error de base de datos', ['dev'=>$e->getMessage(), 'code'=>$e->getCode()]);
} catch (Throwable $e) {
  jerror(500, 'Error del servidor', ['dev'=>$e->getMessage()]);
}
