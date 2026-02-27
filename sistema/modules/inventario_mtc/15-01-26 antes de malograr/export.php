<?php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

acl_require_ids([3,4]);
verificarPermiso(['Recepción','Administración']);

$u = currentUser();
$empresaId = (int)($u['empresa']['id'] ?? 0);
if ($empresaId <= 0) { http_response_code(403); exit('Sin empresa.'); }

$empresa = db()->query("SELECT nombre FROM mtp_empresas WHERE id={$empresaId}")->fetch_assoc();
$base = 'inventario_'.preg_replace('/\W+/','_', $empresa['nombre'] ?? 'empresa').'_'.date('Ymd_His');

function fetchAll($table, $empresaId){
  $st = db()->prepare("SELECT * FROM {$table} WHERE id_empresa=? ORDER BY id ASC");
  $st->bind_param('i', $empresaId);
  $st->execute();
  return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Datos
$data = [
  'red' => ['table'=>'iv_red', 'head'=>['ip_publica','transmision_online','bajada_txt','subida_txt','notas','activo'], 'rows'=>[]],
  'tx'  => ['table'=>'iv_transmision', 'head'=>['acceso_url','usuario','clave','notas','activo'], 'rows'=>[]],
  'sw'  => ['table'=>'iv_switches', 'head'=>['marca','modelo','serie','notas','activo'], 'rows'=>[]],
  'dvr' => ['table'=>'iv_dvrs', 'head'=>['marca','modelo','serie','notas','activo'], 'rows'=>[]],
  'cam' => ['table'=>'iv_camaras', 'head'=>['etiqueta','ambiente','marca','modelo','serie','notas','activo'], 'rows'=>[]],
  'pc'  => ['table'=>'iv_computadoras', 'head'=>['ambiente','nombre_equipo','marca','modelo','serie','procesador','disco_gb','ram_gb','sistema_operativo','mac','ip','notas','activo'], 'rows'=>[]],
  'hu'  => ['table'=>'iv_huelleros', 'head'=>['etiqueta','marca','modelo','serie','notas','activo'], 'rows'=>[]],
];
$order = ['red','tx','sw','dvr','cam','pc','hu'];

foreach ($order as $k) {
  $rows = fetchAll($data[$k]['table'], $empresaId);
  $data[$k]['rows'] = $rows;
}

// Si hay ZipArchive → entregamos ZIP
if (class_exists('ZipArchive')) {
  $zipPath = sys_get_temp_dir()."/{$base}.zip";
  $zip = new ZipArchive();
  if ($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE) === true) {
    foreach ($order as $k) {
      $csv = fopen('php://temp', 'w+');
      // BOM UTF-8
      fwrite($csv, "\xEF\xBB\xBF");
      fputcsv($csv, $data[$k]['head']);
      foreach ($data[$k]['rows'] as $r) {
        $line = [];
        foreach ($data[$k]['head'] as $col) {
          $val = $r[$col] ?? '';
          if ($k==='tx' && $col==='clave') {
            // CSV: exportar texto tal cual (auditoría)
          }
          $line[] = $val;
        }
        fputcsv($csv, $line);
      }
      rewind($csv);
      $content = stream_get_contents($csv);
      fclose($csv);
      $zip->addFromString($k.'.csv', $content);
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$base.'.zip"');
    header('Content-Length: '.filesize($zipPath));
    readfile($zipPath);
    @unlink($zipPath);
    exit;
  }
}

// Fallback: CSV único concatenado
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$base.'.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
foreach ($order as $k) {
  fputcsv($out, ["=== {$k} ==="]);
  fputcsv($out, $data[$k]['head']);
  foreach ($data[$k]['rows'] as $r) {
    $line=[]; foreach($data[$k]['head'] as $col){ $line[] = $r[$col] ?? ''; }
    fputcsv($out, $line);
  }
  fputcsv($out, ['']); // línea en blanco
}
fclose($out);
exit;
