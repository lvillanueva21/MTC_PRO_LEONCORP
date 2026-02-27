<?php
// modules/inventario_mtc/print.php
require_once __DIR__.'/../../includes/acl.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/conexion.php';

acl_require_ids([1,3,4]);
verificarPermiso(['Desarrollo','Recepción','Administración']);

$u = currentUser();
$empresaId  = (int)($u['empresa']['id'] ?? 0);
if ($empresaId <= 0) { http_response_code(403); exit('Empresa no asignada'); }

$mysqli = db();
$mysqli->set_charset('utf8mb4');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function qAll($mysqli, $sql) {
  $res = $mysqli->query($sql);
  if (!$res) return [];
  return $res->fetch_all(MYSQLI_ASSOC);
}

// ----- Cargar datos (solo activos) -----
$computadoras = qAll($mysqli, "SELECT * FROM iv_computadoras WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC");
$camaras      = qAll($mysqli, "SELECT * FROM iv_camaras      WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC");

// Categorías "únicas": tomar el registro activo más reciente
$dvrs         = qAll($mysqli, "SELECT * FROM iv_dvrs         WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id DESC LIMIT 1");
$huelleros    = qAll($mysqli, "SELECT * FROM iv_huelleros    WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id ASC");
$switches     = qAll($mysqli, "SELECT * FROM iv_switches     WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id DESC LIMIT 1");
$red          = qAll($mysqli, "SELECT * FROM iv_red          WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id DESC LIMIT 1");
$tx           = qAll($mysqli, "SELECT * FROM iv_transmision  WHERE id_empresa={$empresaId} AND activo=1 ORDER BY id DESC LIMIT 1");

// Helpers de render
function tr_kv($k, $v, $boldRow=false, $boldVal=false) {
  $k = h($k);
  $v = h($v);
  $cls = $boldRow ? ' class="row-amb"' : '';
  $vHtml = $boldVal ? '<strong>'.$v.'</strong>' : $v;
  return '<tr'.$cls.'><td class="k">'.$k.'</td><td class="v">'.$vHtml.'</td></tr>';
}
function tr_title($text, $type='area', $align='center') {
  $text = h($text);
  $cls = ($type === 'area') ? 'hdr-area' : 'hdr-sec';
  $al  = ($align === 'left') ? 'left' : 'center';
  return '<tr><td class="'.$cls.' '.$al.'" colspan="2">'.$text.'</td></tr>';
}
function tr_block($text) {
  $text = h($text);
  return '<tr><td class="hdr-block" colspan="2">'.$text.'</td></tr>';
}
function tr_sep() {
  return '<tr><td class="sep" colspan="2"></td></tr>';
}
function table_open() {
  return '<table class="sheet" cellspacing="0" cellpadding="0">';
}
function table_close() {
  return '</table>';
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>RELACIÓN ACTUALIZADA DE BIENES Y EQUIPOS TECNOLOGICOS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  /**
   * OBJETIVO:
   * - Header y footer en blanco por página (estable en PDF): @page margins
   * - Sin saltos forzados dentro de tablas
   * - Si una fila no entra por el footer, baja completa a la siguiente página: tr { break-inside: avoid; }
   */

  /* Espacio en blanco real por hoja */
  @page {
    size: A4;
    margin: 18mm 12mm 18mm 12mm; /* top right bottom left => header/footer en blanco */
  }

  *{ box-sizing:border-box; }

  body{
    margin:0;
    font-family: Arial, Helvetica, sans-serif;
    color:#000;
    background:#fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* En pantalla: simular hoja A4 para copiar cómodo */
  @media screen{
    body{ background:#7a7a7a; padding:14px; }
    .paper{
      width: 21cm;
      margin: 0 auto;
      background:#fff;
      padding: 18mm 12mm; /* mismo espacio visual que @page */
      box-shadow: 0 0 0 1px #d9d9d9, 0 10px 30px rgba(0,0,0,.35);
    }
  }

  /* En impresión/PDF: el espacio lo da @page, no padding */
  @media print{
    body{ background:#fff; padding:0; }
    .paper{
      width:auto;
      margin:0;
      padding:0;
      box-shadow:none;
    }
    .noprint{ display:none !important; }
  }

  /* Botón flotante PDF */
  .btn-pdf{
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9999;
    border: 0;
    background: #111;
    color: #fff;
    padding: 12px 16px;
    border-radius: 999px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0,0,0,.35);
    user-select: none;
  }
  .btn-pdf:active{ transform: translateY(1px); }

  /* Título SOLO primera hoja */
  .doc-title{
    text-align:center;
    font-weight:700;
    font-size:16px;
    letter-spacing:.2px;
    text-transform:uppercase;
    text-decoration: underline;
    margin: 0 0 18px 0;
  }

  /* Tablas */
  .sheet{
    width: 100%;
    margin: 0 0 16px 0;
    border-collapse: collapse;
    border: 3px solid #000;
    table-layout: fixed;
    page-break-inside: auto;
  }
  .sheet td{
    border: 1px solid #000;
    font-size: 13px;
    padding: 5px 10px;
    vertical-align: middle;
    word-break: break-word;
  }

  /* IMPORTANTE: evita que una fila se parta entre páginas */
  .sheet tr{
    page-break-inside: avoid;
    break-inside: avoid;
  }

  .sheet td.k{ width: 50%; }
  .sheet td.v{ width: 50%; }

  /* Encabezados */
  .hdr-area{
    background:#FFC000;
    font-weight:700;
    text-transform: uppercase;
    padding: 6px 10px;
  }
  .hdr-sec{
    background:#FFD966;
    font-weight:700;
    text-transform: uppercase;
    padding: 6px 10px;
  }
  .hdr-sec.left{ text-align:left; }
  .hdr-sec.center{ text-align:center; }
  .hdr-area.center{ text-align:center; }
  .hdr-area.left{ text-align:left; }

  .hdr-block{
    background:#fff;
    font-weight:700;
    text-transform: uppercase;
    padding: 6px 10px;
  }

  .sep{
    background:#D9D9D9;
    height: 18px;
    padding:0 !important;
  }

  .row-amb td{ font-weight:700; }
</style>
</head>
<body>

  <!-- Botón flotante PDF (abre el diálogo de impresión) -->
  <button type="button" class="btn-pdf noprint" id="btnPdf">PDF</button>
  <script>
    (function(){
      var b = document.getElementById('btnPdf');
      if (!b) return;
      b.addEventListener('click', function(){
        window.print();
      });
    })();
  </script>

  <div class="paper">

    <!-- TÍTULO (solo al inicio, como tu plantilla) -->
    <div class="doc-title">RELACIÓN ACTUALIZADA DE BIENES Y EQUIPOS TECNOLOGICOS</div>

    <!-- BLOQUE 1: LOCAL ADMINISTRATIVO / COMPUTADORAS -->
    <?php
      echo table_open();
      echo tr_title('LOCAL ADMINISTRATIVO', 'area', 'center');
      echo tr_title('COMPUTADORAS', 'sec', 'left');

      if (!$computadoras) {
        echo tr_kv('', '', false, false);
        echo tr_sep();
      } else {
        foreach ($computadoras as $pc) {
          echo tr_kv('Ambiente', $pc['ambiente'] ?? '', true, false);
          echo tr_kv('Nombre', $pc['nombre_equipo'] ?? '');
          echo tr_kv('Marca', $pc['marca'] ?? '');
          echo tr_kv('Modelo', $pc['modelo'] ?? '');
          echo tr_kv('Número de Serie:', $pc['serie'] ?? '');
          echo tr_kv('Procesador:', $pc['procesador'] ?? '');
          echo tr_kv('Disco Duro:', $pc['disco_gb'] ?? '');
          echo tr_kv('Memoria RAM:', $pc['ram_gb'] ?? '');
          echo tr_kv('Sistema Operativo:', $pc['sistema_operativo'] ?? '');
          echo tr_kv('MAC:', $pc['mac'] ?? '');
          echo tr_kv('IP', $pc['ip'] ?? '');
          echo tr_sep();
        }
      }

      echo table_close();
    ?>

    <!-- BLOQUE 2: EQUIPOS DE GRABACIÓN/TRANSMISIÓN -->
    <?php
      echo table_open();
      echo tr_title('EQUIPOS DE GRABACIÓN/TRANSMISIÓN', 'sec', 'left');

      if ($camaras) {
        $n = 1;
        foreach ($camaras as $cam) {
          $camTitle = 'CÁMARA '.str_pad((string)$n, 2, '0', STR_PAD_LEFT);
          echo tr_block($camTitle);

          echo tr_kv('Ambiente', $cam['ambiente'] ?? '', true, false);
          echo tr_kv('Marca', $cam['marca'] ?? '');
          echo tr_kv('Modelo', $cam['modelo'] ?? '');
          echo tr_kv('Número de Serie', $cam['serie'] ?? '');

          echo tr_sep();
          $n++;
        }
      } else {
        echo tr_block('CÁMARA 01');
        echo tr_kv('Ambiente', '', true, false);
        echo tr_kv('Marca', '');
        echo tr_kv('Modelo', '');
        echo tr_kv('Número de Serie', '');
        echo tr_sep();
      }

      if ($dvrs) {
        $d = $dvrs[0];
        echo tr_block('DVR (GRABADOR DE VIDEO)');
        echo tr_kv('Marca', $d['marca'] ?? '');
        echo tr_kv('Modelo', $d['modelo'] ?? '');
        echo tr_kv('Número de Serie:', $d['serie'] ?? '');
      } else {
        echo tr_block('DVR (GRABADOR DE VIDEO)');
        echo tr_kv('Marca', '');
        echo tr_kv('Modelo', '');
        echo tr_kv('Número de Serie:', '');
      }

      echo table_close();
    ?>

    <!-- BLOQUE 3: BIOMÉTRICOS + SWITCH + RED + ACCESO -->
    <?php
      echo table_open();
      echo tr_title('IDENTIFICADORES BIOMETRICOS', 'sec', 'left');

      if ($huelleros) {
        $m = 1;
        $totalH = count($huelleros);
        foreach ($huelleros as $hx) {
          echo tr_block('IDENTIFICADOR BIOMETRICO '.$m);
          echo tr_kv('Marca', $hx['marca'] ?? '');
          echo tr_kv('Modelo', $hx['modelo'] ?? '');
          echo tr_kv('Número de Serie:', $hx['serie'] ?? '');
          if ($m < $totalH) echo tr_sep();
          $m++;
        }
      } else {
        echo tr_block('IDENTIFICADOR BIOMETRICO 1');
        echo tr_kv('Marca', '');
        echo tr_kv('Modelo', '');
        echo tr_kv('Número de Serie:', '');
      }

      echo tr_sep();

      echo '<tr><td class="hdr-sec left" colspan="2" style="text-transform:none; font-weight:700; background:#FFD966;">SWITCH de Comunicaciones</td></tr>';
      if ($switches) {
        $s = $switches[0];
        echo tr_kv('Marca', $s['marca'] ?? '');
        echo tr_kv('Modelo', $s['modelo'] ?? '');
        echo tr_kv('Número de Serie:', $s['serie'] ?? '');
      } else {
        echo tr_kv('Marca', '');
        echo tr_kv('Modelo', '');
        echo tr_kv('Número de Serie:', '');
      }

      echo tr_sep();

      echo tr_title('DATOS DE LA RED', 'sec', 'left');
      if ($red) {
        $r = $red[0];
        $baj = trim((string)($r['bajada_txt'] ?? ''));
        $sub = trim((string)($r['subida_txt'] ?? ''));
        $ancho = '';
        if ($baj !== '' || $sub !== '') {
          $ancho = 'BAJ '.($baj !== '' ? $baj : '').'   SUB '.($sub !== '' ? $sub : '');
          $ancho = trim(preg_replace('/\s+/', ' ', $ancho));
          $ancho = str_replace(' SUB', '   SUB', $ancho);
        }
        echo tr_kv('IP pública fija:', $r['ip_publica'] ?? '', false, true);
        echo tr_kv('Transmisión en línea:', $r['transmision_online'] ?? '', false, true);
        echo tr_kv('Ancho de banda:', $ancho);
      } else {
        echo tr_kv('IP pública fija:', '', false, true);
        echo tr_kv('Transmisión en línea:', '', false, true);
        echo tr_kv('Ancho de banda:', '');
      }

      echo tr_sep();

      echo tr_title('DATOS DE ACCESO A LA TRANSMISIÓN', 'sec', 'left');
      if ($tx) {
        $t = $tx[0];
        echo tr_kv('Acceso:', $t['acceso_url'] ?? '');
        echo tr_kv('Usuario:', $t['usuario'] ?? '');
        echo tr_kv('Contraseña:', $t['clave'] ?? '');
      } else {
        echo tr_kv('Acceso:', '');
        echo tr_kv('Usuario:', '');
        echo tr_kv('Contraseña:', '');
      }

      echo table_close();
    ?>

  </div>

</body>
</html>
