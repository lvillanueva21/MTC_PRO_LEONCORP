<?php
declare(strict_types=1);

/**
 * PON ESTE ARCHIVO EN: public_html/sistema/ferreteria.php
 * AWS SDK debe estar en: public_html/sistema/modules/aws-sdk/
 */

// ==================== AWS SDK ====================
$autoload = __DIR__ . '/modules/aws-sdk/aws-autoloader.php';
if (!file_exists($autoload)) {
  http_response_code(500);
  die("No encuentro el AWS SDK. Falta: $autoload");
}
require $autoload;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// ==================== CONFIG MYSQL (RELLENA) ====================
$DB_HOST = 'localhost';
$DB_NAME = 'lsistemas_erp_2026';
$DB_USER = 'lsistemas_luigi2026';
$DB_PASS = '20@26LSistemas#&&';

// ==================== CONFIG MEGA S4 (RELLENA) ====================
$S4_ACCESS_KEY = 'AKIAB5BSZ75OQZRHLGJFISDSQYZCNJNTDQYAAR6BCF6D';
$S4_SECRET_KEY = 'u74jMxbmfRclxwMK9uKh2huMeYEGYUZxANQjEcMy';
$S4_BUCKET     = 'lsistemas';

// OJO: región correcta (no el endpoint). Ej: eu-central-1
$S4_REGION     = 'eu-central-1';

// Endpoint con https://
$S4_ENDPOINT   = 'https://s3.eu-central-1.s4.mega.io';

// Carpeta virtual dentro del bucket para tus imágenes
$S4_PREFIX     = 'ferreteria/';

// Límite para fotos (cámbialo si quieres)
$MAX_MB = 15;
// ================================================================


// ==================== HELPERS ====================
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function ini_bytes(string $val): int {
  $val = trim($val);
  if ($val === '') return 0;
  $last = strtolower($val[strlen($val)-1]);
  $num = (int)$val;
  return match ($last) {
    'g' => $num * 1024 * 1024 * 1024,
    'm' => $num * 1024 * 1024,
    'k' => $num * 1024,
    default => (int)$val
  };
}

function clean_basename(string $name): string {
  $name = basename($name);
  $name = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $name);
  return $name ?: ('file_' . time());
}

function ext_from_mime(string $mime): ?string {
  return match (strtolower($mime)) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default => null
  };
}

function file_error_to_text(int $code): string {
  return match ($code) {
    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el límite permitido por el servidor.',
    UPLOAD_ERR_PARTIAL => 'El archivo llegó incompleto.',
    UPLOAD_ERR_NO_FILE => 'No seleccionaste archivo.',
    UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal en el servidor.',
    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo temporal.',
    UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida.',
    default => 'Error desconocido al subir.'
  };
}

// ==================== CONEXIÓN DB ====================
try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (Throwable $e) {
  http_response_code(500);
  die("Error DB: " . h($e->getMessage()));
}

// ==================== S3 CLIENT ====================
$s3 = new S3Client([
  'version' => 'latest',
  'region' => $S4_REGION,
  'endpoint' => $S4_ENDPOINT,
  'credentials' => ['key' => $S4_ACCESS_KEY, 'secret' => $S4_SECRET_KEY],
  'use_path_style_endpoint' => true,
]);

// ==================== MANEJO “POST MUY GRANDE” ====================
// Cuando post_max_size se pasa, PHP suele dejar $_POST y $_FILES vacíos.
$postTooLarge = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
  $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
  if ($cl > 0 && $cl > ini_bytes((string)ini_get('post_max_size'))) {
    $postTooLarge = true;
  }
}

// ==================== ACCIONES ====================
$flashOk = null;
$flashErr = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($postTooLarge) {
    $flashErr = "El archivo es demasiado grande para tu configuración actual (post_max_size).";
  } else {
    try {
      $nombre = trim((string)($_POST['nombre'] ?? ''));
      $cantidad = (int)($_POST['cantidad'] ?? 0);

      if ($nombre === '' || mb_strlen($nombre) > 255) {
        throw new RuntimeException('Nombre inválido.');
      }
      if ($cantidad < 0) {
        throw new RuntimeException('Cantidad inválida.');
      }

      if (!isset($_FILES['foto'])) {
        throw new RuntimeException('No llegó el campo foto.');
      }
      if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(file_error_to_text((int)$_FILES['foto']['error']));
      }

      $size = (int)$_FILES['foto']['size'];
      if ($size <= 0) throw new RuntimeException('Archivo vacío.');
      if ($size > $MAX_MB * 1024 * 1024) {
        throw new RuntimeException("La foto supera {$MAX_MB}MB (límite de la app).");
      }

      // Validar mime real (no confiar 100% en $_FILES['type'])
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($_FILES['foto']['tmp_name']) ?: 'application/octet-stream';
      $ext = ext_from_mime($mime);
      if (!$ext) {
        throw new RuntimeException('Solo se permiten imágenes: JPG, PNG, WEBP, GIF.');
      }

      // Insert preliminar para obtener ID (para nombre/key ordenado)
      $stmt = $pdo->prepare("INSERT INTO ferreteria_productos (nombre, cantidad, image_key) VALUES (?, ?, NULL)");
      $stmt->execute([$nombre, $cantidad]);
      $id = (int)$pdo->lastInsertId();

      // Nombre/key automático (único)
      $rand = bin2hex(random_bytes(6));
      $safeBase = clean_basename($nombre);
      $safeBase = preg_replace('/\.[^.]+$/', '', $safeBase); // sin extensión
      $safeBase = substr($safeBase, 0, 60);

      $key = $S4_PREFIX . date('Y/m/') . "p{$id}_{$safeBase}_{$rand}.{$ext}";

      // Subir a S4
      $s3->putObject([
        'Bucket' => $S4_BUCKET,
        'Key'    => $key,
        'Body'   => fopen($_FILES['foto']['tmp_name'], 'rb'),
        'ContentType' => $mime,
      ]);

      // Guardar key
      $stmt = $pdo->prepare("UPDATE ferreteria_productos SET image_key=? WHERE id=?");
      $stmt->execute([$key, $id]);

      // PRG (evita reenvío al refrescar)
      header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?ok=1");
      exit;

    } catch (AwsException $e) {
      $flashErr = "Error S4: " . (($e->getAwsErrorMessage() ?: $e->getMessage()));
    } catch (Throwable $e) {
      $flashErr = "Error: " . $e->getMessage();
    }
  }
}

if (isset($_GET['ok'])) $flashOk = "Producto guardado y foto subida a S4 ✅";

// ==================== LISTADO ====================
$items = $pdo->query("SELECT id, nombre, cantidad, image_key, creado_en FROM ferreteria_productos ORDER BY id DESC LIMIT 50")
            ->fetchAll(PDO::FETCH_ASSOC);

// Generar URL temporal para cada imagen (no necesitas bucket público)
$thumbUrls = [];
foreach ($items as $row) {
  if (!empty($row['image_key'])) {
    try {
      $cmd = $s3->getCommand('GetObject', ['Bucket' => $S4_BUCKET, 'Key' => $row['image_key']]);
      $req = $s3->createPresignedRequest($cmd, '+2 hours');
      $thumbUrls[(int)$row['id']] = (string)$req->getUri();
    } catch (Throwable $e) {
      $thumbUrls[(int)$row['id']] = null;
    }
  } else {
    $thumbUrls[(int)$row['id']] = null;
  }
}

// Mostrar límites actuales del servidor
$uploadMax = (string)ini_get('upload_max_filesize');
$postMax   = (string)ini_get('post_max_size');
$maxTime   = (string)ini_get('max_execution_time');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ferretería - Productos (S4)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0b1220}
    .card{border:0;border-radius:18px}
    .soft{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)}
    .muted{color:rgba(255,255,255,.75)}
    .thumb{
      width:64px;height:64px;border-radius:12px;object-fit:cover;
      background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
    }
    .list-item{border-bottom:1px solid rgba(255,255,255,.08)}
    .list-item:last-child{border-bottom:0}
  </style>
</head>
<body class="text-white">
<div class="container py-5">
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
      <h1 class="mb-1">Ferretería (demo)</h1>
      <div class="muted">Productos en MySQL + fotos en MEGA S4 (bucket).</div>
    </div>
    <div class="muted small mt-2 mt-md-0">
      Límites servidor: upload_max_filesize=<b><?= h($uploadMax) ?></b>,
      post_max_size=<b><?= h($postMax) ?></b>,
      max_execution_time=<b><?= h($maxTime) ?></b>
    </div>
  </div>

  <?php if ($flashOk): ?>
    <div class="alert alert-success"><?= h($flashOk) ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger"><?= h($flashErr) ?></div>
  <?php endif; ?>
  <?php if ($postTooLarge): ?>
    <div class="alert alert-warning">
      Tu archivo supera <b>post_max_size</b>. Ahora mismo: post_max_size=<b><?= h($postMax) ?></b>.
      (Debes aumentarlo si quieres subir más grande.)
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- FORM -->
    <div class="col-12 col-lg-5">
      <div class="card soft p-4">
        <h4 class="mb-3">Registrar producto</h4>

        <form method="post" enctype="multipart/form-data" class="vstack gap-3">
          <div>
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre" placeholder="Ej: Martillo 16oz" required maxlength="255">
          </div>

          <div>
            <label class="form-label">Cantidad</label>
            <input class="form-control" name="cantidad" type="number" min="0" value="0" required>
          </div>

          <div>
            <label class="form-label">Foto (JPG/PNG/WEBP/GIF)</label>
            <input class="form-control" type="file" name="foto" accept="image/*" required>
            <div class="form-text text-white-50">Máximo app: <?= (int)$MAX_MB ?>MB (además del límite del servidor).</div>
          </div>

          <button class="btn btn-primary btn-lg">Guardar y subir foto</button>
        </form>

        <hr class="my-4" style="border-color: rgba(255,255,255,.15)">
        <div class="muted small">
          Endpoint: <code><?= h($S4_ENDPOINT) ?></code><br>
          Región: <code><?= h($S4_REGION) ?></code><br>
          Bucket: <code><?= h($S4_BUCKET) ?></code><br>
          Prefijo: <code><?= h($S4_PREFIX) ?></code>
        </div>
      </div>

      <div class="card soft p-4 mt-4">
        <h6 class="mb-2">Si algunos archivos “pesados” fallan</h6>
        <div class="muted small">
          Normalmente es por límites de PHP. En Hostinger suele funcionar crear un archivo <code>.user.ini</code>
          en esta misma carpeta (<code>/sistema/</code>) con algo como:
          <pre class="mb-0 text-white" style="white-space:pre-wrap">upload_max_filesize=50M
post_max_size=50M
max_execution_time=300
max_input_time=300
memory_limit=256M</pre>
        </div>
      </div>
    </div>

    <!-- LISTADO -->
    <div class="col-12 col-lg-7">
      <div class="card soft p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h4 class="mb-0">Productos (últimos 50)</h4>
          <div class="muted small"><?= count($items) ?> items</div>
        </div>

        <?php if (!$items): ?>
          <div class="muted">Aún no hay productos.</div>
        <?php else: ?>
          <div class="vstack">
            <?php foreach ($items as $row): 
              $id = (int)$row['id'];
              $url = $thumbUrls[$id] ?? null;
            ?>
              <div class="d-flex gap-3 py-3 list-item">
                <?php if ($url): ?>
                  <img class="thumb" src="<?= h($url) ?>" alt="foto">
                <?php else: ?>
                  <div class="thumb d-flex align-items-center justify-content-center muted">N/A</div>
                <?php endif; ?>

                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between gap-2">
                    <div>
                      <div class="fw-semibold"><?= h((string)$row['nombre']) ?></div>
                      <div class="muted small">Cantidad: <b><?= (int)$row['cantidad'] ?></b></div>
                    </div>
                    <div class="muted small text-end">
                      #<?= $id ?><br>
                      <?= h((string)$row['creado_en']) ?>
                    </div>
                  </div>

                  <div class="muted small mt-2">
                    Key: <code><?= h((string)$row['image_key']) ?></code>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
