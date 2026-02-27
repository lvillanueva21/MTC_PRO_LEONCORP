<?php
// modules/consola/certificados/fs_assets.php
// Helpers seguros para copiar/borrar assets de plantillas (fondo/logo/firma) en almacen/YYYY/MM/DD/<tipo>/
//
// Compatibilidad: PHP 7+ recomendado (usa funciones estándar), sin features raras.
// Seguridad:
// - Solo opera con rutas relativas sin ".." ni null bytes.
// - Solo borra dentro de "almacen/" (no toca placeholders ni otras rutas del proyecto).

function pcx_norm_rel($rel) {
  $rel = (string)$rel;
  $rel = str_replace("\0", '', $rel);
  $rel = ltrim($rel, "/\\");
  if ($rel === '') return '';
  if (strpos($rel, '..') !== false) return ''; // evita traversal
  return $rel;
}

function pcx_join_path($root, $rel) {
  $root = rtrim((string)$root, "/\\");
  $rel  = pcx_norm_rel($rel);
  if ($rel === '') return '';
  return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
}

function pcx_allowed_ext($ext) {
  $ext = strtolower((string)$ext);
  return in_array($ext, ['jpg','jpeg','png','webp'], true);
}

function pcx_guess_ext_from_mime($absPath) {
  if (!is_file($absPath)) return '';
  if (!class_exists('finfo')) return '';
  $fi = @new finfo(FILEINFO_MIME_TYPE);
  if (!$fi) return '';
  $mime = (string)@($fi->file($absPath));
  if ($mime === 'image/jpeg') return 'jpg';
  if ($mime === 'image/png')  return 'png';
  if ($mime === 'image/webp') return 'webp';
  return '';
}

function pcx_make_storage_rel_dir($tipoDir) {
  // tipoDir: fondo_certificado | logo_certificado | firma_representante
  $y = date('Y');
  $m = date('m');
  $d = date('d');
  $tipoDir = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tipoDir);
  if ($tipoDir === '') $tipoDir = 'otros';
  return 'almacen/'.$y.'/'.$m.'/'.$d.'/'.$tipoDir;
}

function pcx_copy_asset($projectRootAbs, $srcRelPath, $tipoDir) {
  $srcRelPath = pcx_norm_rel($srcRelPath);
  if ($srcRelPath === '') return ['ok'=>true, 'rel'=>null];

  $srcAbs = pcx_join_path($projectRootAbs, $srcRelPath);
  if ($srcAbs === '' || !is_file($srcAbs)) {
    return ['ok'=>false, 'msg'=>'Archivo origen no encontrado: '.$srcRelPath];
  }

  $ext = strtolower(pathinfo($srcAbs, PATHINFO_EXTENSION));
  if (!pcx_allowed_ext($ext)) {
    $ext2 = pcx_guess_ext_from_mime($srcAbs);
    if ($ext2 !== '' && pcx_allowed_ext($ext2)) {
      $ext = $ext2;
    } else {
      return ['ok'=>false, 'msg'=>'Extensión no permitida en origen: '.$srcRelPath];
    }
  }

  $dirRel  = pcx_make_storage_rel_dir($tipoDir);
  $dirAbs  = pcx_join_path($projectRootAbs, $dirRel);
  if ($dirAbs === '') return ['ok'=>false, 'msg'=>'No se pudo resolver ruta de destino'];

  if (!is_dir($dirAbs)) {
    if (!@mkdir($dirAbs, 0775, true) && !is_dir($dirAbs)) {
      return ['ok'=>false, 'msg'=>'No se pudo crear directorio: '.$dirRel];
    }
  }

  $base = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tipoDir);
  if ($base === '') $base = 'asset';
  $name = $base.'-'.date('Ymd_His').'-'.mt_rand(1000,9999).'.'.$ext;

  $destRel = $dirRel.'/'.$name;
  $destAbs = pcx_join_path($projectRootAbs, $destRel);

  if ($destAbs === '') return ['ok'=>false, 'msg'=>'No se pudo resolver archivo destino'];

  if (!@copy($srcAbs, $destAbs)) {
    return ['ok'=>false, 'msg'=>'No se pudo copiar el archivo: '.$srcRelPath];
  }

  return ['ok'=>true, 'rel'=>$destRel];
}

function pcx_delete_asset($projectRootAbs, $relPath) {
  $relPath = pcx_norm_rel($relPath);
  if ($relPath === '') return false;

  // Seguridad: solo borramos en almacen/
  if (strpos($relPath, 'almacen/') !== 0) return false;

  $abs = pcx_join_path($projectRootAbs, $relPath);
  if ($abs === '' || !is_file($abs)) return false;

  return @unlink($abs);
}
