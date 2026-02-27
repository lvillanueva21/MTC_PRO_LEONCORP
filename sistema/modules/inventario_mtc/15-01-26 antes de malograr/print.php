<?php
// modules/inventario/print.php
require_once __DIR__.'/../../includes/acl.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/conexion.php';

acl_require_ids([1,3,4]);
verificarPermiso(['Desarrollo','Recepción','Administración']);

$u = currentUser();
$empresaId  = (int)($u['empresa']['id'] ?? 0);
$empresaNom = (string)($u['empresa']['nombre'] ?? '—');
if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada'); }

$mysqli = db();
$h = fn($s)=>htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

// cargar datos (orden y estructura según documento que exige MTC)
$computadoras = $mysqli->query("SELECT * FROM iv_computadoras WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$camaras      = $mysqli->query("SELECT * FROM iv_camaras WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$dvrs         = $mysqli->query("SELECT * FROM iv_dvrs WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$huelleros    = $mysqli->query("SELECT * FROM iv_huelleros WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$switches     = $mysqli->query("SELECT * FROM iv_switches WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$red          = $mysqli->query("SELECT * FROM iv_red WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$tx           = $mysqli->query("SELECT * FROM iv_transmision WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Inventario — <?= $h($empresaNom) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root { --ink:#111; --mut:#666; --pri:#0ea5e9; }
  *{ box-sizing:border-box; }
  body{ font-family:Arial,Helvetica,sans-serif; color:var(--ink); margin:24px; }
  h1,h2,h3{ margin:.35rem 0; }
  h1{ font-size:20px; text-transform:uppercase; letter-spacing:.3px; }
  h2{ font-size:16px; text-transform:uppercase; }
  h3{ font-size:14px; text-transform:uppercase; }
  .mut{ color:var(--mut); }
  .section{ margin-top:16px; }
  .kv{ border:1px solid #ddd; border-radius:8px; overflow:hidden; width:100%; border-collapse:collapse; margin:6px 0 16px; }
  .kv tr{ border-bottom:1px solid #eee; }
  .kv tr:last-child{ border-bottom:0; }
  .kv th,.kv td{ padding:8px 10px; vertical-align:top; font-size:13px; }
  .kv th{ width:38%; background:#f8fafc; text-align:left; font-weight:700; }
  .kv td{ width:62%; }
  .pill{ display:inline-block; padding:2px 8px; border-radius:999px; background:#e8f6fd; color:#0369a1; font-weight:700; font-size:12px; }
  .hr{ height:2px; background:#e5e7eb; border:0; margin:12px 0; }
  .grid-2{ display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
  @media print {
    .noprint{ display:none !important; }
    body{ margin:10mm; }
    .grid-2{ grid-template-columns:repeat(2,1fr); gap:8px; }
    .kv th,.kv td{ padding:6px 8px; font-size:12px; }
    h1{ font-size:16px; }
    h2{ font-size:14px; }
    h3{ font-size:13px; }
  }
</style>
</head>
<body>
  <div class="noprint" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
    <div><span class="pill">Vista de impresión</span></div>
    <div>
      <button onclick="window.print()" style="padding:8px 12px; border:1px solid #ddd; background:#10b981; color:#fff; border-radius:8px; cursor:pointer;">
        Imprimir / Guardar PDF
      </button>
    </div>
  </div>

  <h1>Actualización de Equipos — <?= $h($empresaNom) ?></h1>
  <div class="mut">Generado: <?= date('Y-m-d H:i') ?></div>
  <hr class="hr">

  <div class="section">
    <h2>Local Administrativo</h2>
  </div>

  <!-- COMPUTADORAS -->
  <div class="section">
    <h3>Computadoras</h3>
    <?php if (!$computadoras): ?>
      <div class="mut">Sin registros.</div>
    <?php else: ?>
      <?php foreach ($computadoras as $pc): ?>
        <table class="kv">
          <tr><th>Ambiente</th><td><?= $h($pc['ambiente']) ?></td></tr>
          <tr><th>Nombre</th><td><?= $h($pc['nombre_equipo']) ?></td></tr>
          <tr><th>Marca</th><td><?= $h($pc['marca']) ?></td></tr>
          <tr><th>Modelo</th><td><?= $h($pc['modelo']) ?></td></tr>
          <tr><th>Número de Serie</th><td><?= $h($pc['serie']) ?></td></tr>
          <tr><th>Procesador</th><td><?= $h($pc['procesador']) ?></td></tr>
          <tr><th>Disco Duro</th><td><?= $h($pc['disco_gb']) ?></td></tr>
          <tr><th>Memoria RAM</th><td><?= $h($pc['ram_gb']) ?></td></tr>
          <tr><th>Sistema Operativo</th><td><?= $h($pc['sistema_operativo']) ?></td></tr>
          <tr><th>MAC</th><td><?= $h($pc['mac']) ?></td></tr>
          <tr><th>IP</th><td><?= $h($pc['ip']) ?></td></tr>
        </table>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- EQUIPOS DE GRABACIÓN/TRANSMISIÓN -->
  <div class="section">
    <h3>Equipos de grabación/transmisión – Local administrativo</h3>

    <!-- Cámaras -->
    <?php if ($camaras): ?>
      <?php $n=1; foreach ($camaras as $cam): ?>
        <h3 style="margin-top:10px;">Cámara <?= str_pad((string)$n++,2,'0',STR_PAD_LEFT) ?></h3>
        <table class="kv">
          <tr><th>Ambiente</th><td><?= $h($cam['ambiente']) ?></td></tr>
          <tr><th>Marca</th><td><?= $h($cam['marca']) ?></td></tr>
          <tr><th>Modelo</th><td><?= $h($cam['modelo']) ?></td></tr>
          <tr><th>Número de Serie</th><td><?= $h($cam['serie']) ?></td></tr>
        </table>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="mut">Sin cámaras registradas.</div>
    <?php endif; ?>

    <!-- DVR -->
    <?php if ($dvrs): $d=$dvrs[0]; ?>
      <h3>DVR (Grabador de Video)</h3>
      <table class="kv">
        <tr><th>Marca</th><td><?= $h($d['marca']) ?></td></tr>
        <tr><th>Modelo</th><td><?= $h($d['modelo']) ?></td></tr>
        <tr><th>Número de Serie</th><td><?= $h($d['serie']) ?></td></tr>
      </table>
    <?php endif; ?>

    <!-- Huelleros -->
    <h3>Identificadores biométricos</h3>
    <?php if ($huelleros): ?>
      <?php $m=1; foreach ($huelleros as $hx): ?>
        <h3 style="margin-top:6px; font-weight:700; text-transform:none;">Identificador biométrico <?= $m++ ?></h3>
        <table class="kv">
          <tr><th>Marca</th><td><?= $h($hx['marca']) ?></td></tr>
          <tr><th>Modelo</th><td><?= $h($hx['modelo']) ?></td></tr>
          <tr><th>Número de Serie</th><td><?= $h($hx['serie']) ?></td></tr>
        </table>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="mut">Sin huelleros registrados.</div>
    <?php endif; ?>

    <!-- Switch -->
    <?php if ($switches): $s=$switches[0]; ?>
      <h3>Switch de comunicaciones</h3>
      <table class="kv">
        <tr><th>Marca</th><td><?= $h($s['marca']) ?></td></tr>
        <tr><th>Modelo</th><td><?= $h($s['modelo']) ?></td></tr>
        <tr><th>Número de Serie</th><td><?= $h($s['serie']) ?></td></tr>
      </table>
    <?php endif; ?>
  </div>

  <!-- DATOS DE LA RED -->
  <div class="section">
    <h3>Datos de la red</h3>
    <?php if ($red): $r=$red[0]; ?>
      <table class="kv">
        <tr><th>IP pública fija</th><td><?= $h($r['ip_publica']) ?></td></tr>
        <tr><th>Transmisión en línea</th><td><?= $h($r['transmision_online']) ?></td></tr>
        <tr><th>Ancho de banda</th><td>BAJ <?= $h($r['bajada_txt']) ?> - SUB <?= $h($r['subida_txt']) ?></td></tr>
      </table>
    <?php else: ?>
      <div class="mut">Sin datos de red registrados.</div>
    <?php endif; ?>
  </div>

  <!-- DATOS DE ACCESO A LA TRANSMISIÓN -->
  <div class="section">
    <h3>Datos de acceso a la transmisión</h3>
    <?php if ($tx): $t=$tx[0]; ?>
      <table class="kv">
        <tr><th>Acceso</th><td><?= $h($t['acceso_url']) ?></td></tr>
        <tr><th>Usuario</th><td><?= $h($t['usuario']) ?></td></tr>
        <tr><th>Contraseña</th><td><?= $h($t['clave']) ?></td></tr>
      </table>
    <?php else: ?>
      <div class="mut">Sin credenciales de transmisión registradas.</div>
    <?php endif; ?>
  </div>
</body>
</html>
