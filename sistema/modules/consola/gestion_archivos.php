<?php
// gestion_archivos.php
// Módulo central de gestión de archivos (todas las tríadas lo usan)
// - Estructura: almacen/AAAA/MM/DD/<categoria>/NOMBRE.ext
// - Zona horaria: Lima (America/Lima)
// - Rutas RELATIVAS (ej: "almacen/2025/10/30/img_perfil/archivo.jpg")

declare(strict_types=1);
date_default_timezone_set('America/Lima');

// ===== Helpers de ruta =====
function ga_storage_rel_base(): string { return 'almacen'; }

// Directorio raíz del proyecto (ajustado al layout: proyecto/modules/consola/)
function ga_project_root(): string {
  return dirname(__DIR__, 2);
}

function ga_abs_from_rel(string $rutaRel): string {
  $rel = ltrim($rutaRel, '/\\');
  return rtrim(ga_project_root(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
}

/** Construye el directorio relativo: almacen/AAAA/MM/DD/<categoria> */
function ga_build_rel_dir(string $categoria): string {
  $y = date('Y'); $m = date('m'); $d = date('d');
  $cat = ga_slugify($categoria);
  return ga_storage_rel_base() . "/$y/$m/$d/$cat";
}

function ga_ensure_dir(string $absDir): void {
  if (!is_dir($absDir)) @mkdir($absDir, 0775, true);
  if (!is_writable($absDir)) {
    throw new RuntimeException('La carpeta no es escribible: ' . $absDir);
  }
}

function ga_slugify(string $s): string {
  $s = strtolower($s);
  // permitimos a-z, 0-9, -, _, .  (quitamos el resto)
  $s = preg_replace('/[^a-z0-9\-\_\.]+/', '-', $s) ?? '';
  $s = preg_replace('/-+/', '-', $s) ?? '';
  return trim($s, '-_.');
}

function ga_ext_from_upload(array $file): string {
  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if ($ext === '') $ext = 'bin';
  return $ext;
}

function ga_random6(): string {
  return substr(bin2hex(random_bytes(6)), 0, 6);
}

/** Genera nombre final: <slug>[-entidad-id]-YYYYMMDDTHHMMSS-rand6.ext */
function ga_generate_filename(string $basename, ?string $entidad, $entidad_id, string $ext): string {
  $slug = ga_slugify($basename);
  $eid  = ($entidad && $entidad_id !== null && $entidad_id !== '') ? ('-' . ga_slugify($entidad) . '-' . (string)$entidad_id) : '';
  $ts   = date('Ymd\THis');
  $rnd  = ga_random6();
  return "{$slug}{$eid}-{$ts}-{$rnd}." . strtolower($ext);
}

/**
 * Guarda un upload y registra en mtp_archivos (estado=local).
 * Devuelve: ['ruta_relativa'=>..., 'nombre_final'=>..., 'abs_path'=>..., 'id_archivo'=>int]
 *
 * @param mysqli $mysqli Conexión activa
 * @param array  $file   $_FILES['...']
 * @param string $categoria  p.ej. 'img_perfil' | 'img_logos_empresas' | 'adjuntos' ...
 * @param string $basename   base para el nombre (legible) p.ej. 'perfil-usuario', 'logo-empresa'
 * @param string $triada     p.ej. 'usuarios', 'empresas'
 * @param string|null $entidad p.ej. 'usuario', 'empresa'
 * @param int|string|null $entidad_id id de la entidad
 */
function ga_save_upload(mysqli $mysqli, array $file, string $categoria, string $basename, string $triada, ?string $entidad, $entidad_id): array {
  if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    throw new RuntimeException('No hay archivo subido.');
  }
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Error de subida de archivo.');
  }

  $ext  = ga_ext_from_upload($file);
  $mime = (string)($file['type'] ?? '');
  $size = (int)($file['size'] ?? 0);

  $relDir = ga_build_rel_dir($categoria);
  $absDir = ga_abs_from_rel($relDir);
  ga_ensure_dir($absDir);

  $filename = ga_generate_filename($basename, $entidad, $entidad_id, $ext);
  $rutaRel  = $relDir . '/' . $filename;
  $absPath  = ga_abs_from_rel($rutaRel);

  $checksum = null;
  if (is_readable($file['tmp_name'])) {
    $checksum = @hash_file('sha256', $file['tmp_name']) ?: null;
  }

  if (!@move_uploaded_file($file['tmp_name'], $absPath)) {
    throw new RuntimeException('No se pudo guardar el archivo en disco.');
  }

  // Insert en mtp_archivos
  $sql = "INSERT INTO mtp_archivos
            (triada, entidad, entidad_id, categoria, nombre_original, nombre_final, ext, mime, tamano_bytes, ruta_relativa, checksum_sha256, estado)
          VALUES (?,?,?,?,?,?,?,?,?,?,?, 'local')";
  $st = $mysqli->prepare($sql);
  $orig = (string)($file['name'] ?? '');
  // Nota: pasar NULLs está soportado por bind_param
  $entId = (is_numeric($entidad_id) ? (int)$entidad_id : null);
    $st->bind_param(
    'ssisssssiss',
    $triada,     // s
    $entidad,    // s
    $entId,      // i
    $categoria,  // s
    $orig,       // s
    $filename,   // s
    $ext,        // s
    $mime,       // s
    $size,       // i
    $rutaRel,    // s
    $checksum    // s
  );
  $st->execute();
  $idArch = (int)$mysqli->insert_id;
  $st->close();

  return [
    'ruta_relativa' => $rutaRel,
    'nombre_final'  => $filename,
    'abs_path'      => $absPath,
    'id_archivo'    => $idArch,
  ];
}

/** Marca y borra físicamente un archivo (si existe). $estado: 'reemplazado' | 'borrado' */
function ga_mark_and_delete(mysqli $mysqli, string $rutaRel, string $estado): void {
  $rutaRel = ltrim($rutaRel, '/\\');
  if ($rutaRel === '') return;

  $up = $mysqli->prepare("UPDATE mtp_archivos SET estado=? WHERE ruta_relativa=?");
  $up->bind_param('ss', $estado, $rutaRel);
  $up->execute();
  $up->close();

  $abs = ga_abs_from_rel($rutaRel);
  if (is_file($abs)) { @unlink($abs); }
}

/** True si el archivo físico existe en disco */
function ga_exists(string $rutaRel): bool {
  $abs = ga_abs_from_rel($rutaRel);
  return is_file($abs);
}
