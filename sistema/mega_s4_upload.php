<?php
// ====== CARGA DEL AWS SDK (tu ruta real) ======
$autoload = __DIR__ . '/modules/aws-sdk/aws-autoloader.php';
if (!file_exists($autoload)) {
  http_response_code(500);
  die("No encuentro el SDK. Falta: $autoload");
}
require $autoload;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// ====== DATOS REALES QUE DEBES PEGAR AQUÍ (MEGA S4) ======
// 1) Keys -> Access key
$S4_ACCESS_KEY = 'AKIAB5BSZ75OQZRHLGJFISDSQYZCNJNTDQYAAR6BCF6D';

// 2) Keys -> Secret key
$S4_SECRET_KEY = 'u74jMxbmfRclxwMK9uKh2huMeYEGYUZxANQjEcMy';

// 3) Bucket -> nombre exacto del bucket (ej: mi-bucket-prueba)
$S4_BUCKET     = 'lsistemas';

// 4) Region -> sale del endpoint (ej: si endpoint es s3.eu-central-1.s4.mega.io => region eu-central-1)
$S4_REGION     = 's3.eu-central-1.s4.mega.io';

// 5) Endpoint completo (con https://)
// Ejemplos: https://s3.eu-central-1.s4.mega.io  |  https://s3.ca-central-1.s4.mega.io
$S4_ENDPOINT   = 'https://s3.eu-central-1.s4.mega.io';
// =========================================================

$ok = null;
$msg = null;

$s3 = new S3Client([
  'version' => 'latest',
  'region' => $S4_REGION,
  'endpoint' => $S4_ENDPOINT,
  'credentials' => [
    'key' => $S4_ACCESS_KEY,
    'secret' => $S4_SECRET_KEY,
  ],
  // Importante para endpoints S3-compatibles (evita problemas con subdominios del bucket)
  'use_path_style_endpoint' => true,
]);

function clean_name(string $name): string {
  $name = basename($name);
  $name = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $name);
  return $name ?: ('file_' . time());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      throw new RuntimeException('No llegó el archivo (o falló el upload desde el navegador).');
    }

    // "Carpeta" virtual (prefijo) dentro del bucket
    $prefix = trim($_POST['folder'] ?? '');
    $prefix = ltrim($prefix, '/');
    if ($prefix !== '' && !str_ends_with($prefix, '/')) $prefix .= '/';

    $filename = clean_name($_FILES['file']['name']);
    $key = $prefix . $filename;

    $s3->putObject([
      'Bucket' => $S4_BUCKET,
      'Key'    => $key,
      'Body'   => fopen($_FILES['file']['tmp_name'], 'rb'),
      'ContentType' => $_FILES['file']['type'] ?: 'application/octet-stream',
    ]);

    $ok = true;
    $msg = "Subido OK ✅\nBucket: {$S4_BUCKET}\nKey: {$key}";
  } catch (AwsException $e) {
    $ok = false;
    $awsMsg = $e->getAwsErrorMessage();
    $okMsg = $awsMsg ?: $e->getMessage();
    $msg = "Error S3: " . $okMsg;
  } catch (Throwable $e) {
    $ok = false;
    $msg = "Error: " . $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Subir a MEGA S4</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0b1220}
    .card{border:0;border-radius:18px}
    .soft{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)}
    .muted{color:rgba(255,255,255,.75)}
  </style>
</head>
<body class="text-white">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-7">
      <div class="card soft p-4 p-md-5">
        <h1 class="mb-2">Subir a MEGA S4</h1>
        <p class="muted mb-4">
          Esto sube a un <b>bucket</b> (S3). “Carpetas” se simulan con un prefijo tipo <code>docs/</code>.
        </p>

        <?php if ($ok !== null): ?>
          <div class="alert <?= $ok ? 'alert-success' : 'alert-danger' ?>">
            <pre class="mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($msg) ?></pre>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="vstack gap-3">
          <div>
            <label class="form-label">Subcarpeta (opcional)</label>
            <input class="form-control" name="folder" placeholder="ej: pruebas/2026">
            <div class="form-text text-white-50">
              Queda como Key: <code>subcarpeta/archivo.ext</code>
            </div>
          </div>

          <div>
            <label class="form-label">Archivo</label>
            <input class="form-control" type="file" name="file" required>
          </div>

          <button class="btn btn-primary btn-lg">Subir</button>
        </form>

        <hr class="my-4" style="border-color: rgba(255,255,255,.15)">
        <div class="muted small">
          Endpoint: <code><?= htmlspecialchars($S4_ENDPOINT) ?></code><br>
          Region: <code><?= htmlspecialchars($S4_REGION) ?></code><br>
          Bucket: <code><?= htmlspecialchars($S4_BUCKET) ?></code>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
