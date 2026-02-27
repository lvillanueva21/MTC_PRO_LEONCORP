<?php
declare(strict_types=1);

/**
 * Archivo: public_html/sistema/ferreteria_mejorada.php
 * AWS SDK: public_html/sistema/modules/aws-sdk/
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

// Región real (NO el endpoint). Ej: eu-central-1
$S4_REGION     = 'eu-central-1';

// Endpoint con https://
$S4_ENDPOINT   = 'https://s3.eu-central-1.s4.mega.io';

// Prefijo “carpeta virtual” dentro del bucket
$S4_PREFIX     = 'ferreteria/';

// Tiempo de validez de la URL firmada para subir
$UPLOAD_URL_TTL = '+60 minutes';


// Tiempo de validez de la URL firmada para ver miniaturas
$VIEW_URL_TTL = '+2 hours';
// ================================================================

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function clean_slug(string $s): string {
  $s = trim($s);
  $s = preg_replace('/[^a-zA-Z0-9]+/', '_', $s);
  $s = trim($s, '_');
  return $s ?: 'producto';
}

function ext_from_mime(string $mime): ?string {
  return match (strtolower($mime)) {
    // imágenes
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',

    // videos
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov', // .mov

    default => null
  };
}


function read_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

// ==================== DB ====================
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

// ==================== API: firmar subida ====================
if (isset($_GET['api']) && $_GET['api'] === 'sign') {
  header('Content-Type: application/json; charset=utf-8');

  try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
      exit;
    }

    $data = read_json();
    $nombre = trim((string)($data['nombre'] ?? ''));
    $mime   = trim((string)($data['mime'] ?? ''));

    if ($nombre === '' || mb_strlen($nombre) > 255) {
      throw new RuntimeException('Nombre inválido.');
    }

    $ext = ext_from_mime($mime);
    if (!$ext) {
      throw new RuntimeException('Permitidos: JPG, PNG, WEBP, GIF, MP4, WEBM, MOV.');

    }

    // Nombre automático (único)
    $slug = substr(clean_slug($nombre), 0, 50);
    $rand = bin2hex(random_bytes(6));
    $sub = str_starts_with(strtolower($mime), 'video/') ? 'videos/' : 'imagenes/';
$key = $S4_PREFIX . $sub . date('Y/m/') . "{$slug}_" . time() . "_{$rand}.{$ext}";


    // Presigned URL para PUT
    $cmd = $s3->getCommand('PutObject', [
      'Bucket' => $S4_BUCKET,
      'Key' => $key,
      'ContentType' => $mime,
    ]);
    $req = $s3->createPresignedRequest($cmd, $UPLOAD_URL_TTL);
    $uploadUrl = (string)$req->getUri();

    echo json_encode([
      'ok' => true,
      'key' => $key,
      'uploadUrl' => $uploadUrl,
      'headers' => [
        'Content-Type' => $mime
      ]
    ]);
    exit;

  } catch (AwsException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'S4: ' . ($e->getAwsErrorMessage() ?: $e->getMessage())]);
    exit;
  } catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
  }
}

// ==================== API: guardar producto ====================
if (isset($_GET['api']) && $_GET['api'] === 'save') {
  header('Content-Type: application/json; charset=utf-8');

  try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
      exit;
    }

    $data = read_json();
    $nombre = trim((string)($data['nombre'] ?? ''));
    $cantidad = (int)($data['cantidad'] ?? 0);
    $key = trim((string)($data['key'] ?? ''));

    if ($nombre === '' || mb_strlen($nombre) > 255) throw new RuntimeException('Nombre inválido.');
    if ($cantidad < 0) throw new RuntimeException('Cantidad inválida.');
    if ($key === '' || !str_starts_with($key, $S4_PREFIX)) throw new RuntimeException('Key inválida.');

    $stmt = $pdo->prepare("INSERT INTO ferreteria_productos (nombre, cantidad, image_key) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $cantidad, $key]);

    echo json_encode(['ok' => true]);
    exit;

  } catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
  }
}

// ==================== LISTADO + URLS DE VISTA ====================
$items = $pdo->query("SELECT id, nombre, cantidad, image_key, creado_en
                      FROM ferreteria_productos
                      ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

$thumbUrls = [];
foreach ($items as $row) {
  $id = (int)$row['id'];
  $key = (string)($row['image_key'] ?? '');
  if ($key !== '') {
    try {
      $cmd = $s3->getCommand('GetObject', ['Bucket' => $S4_BUCKET, 'Key' => $key]);
      $req = $s3->createPresignedRequest($cmd, $VIEW_URL_TTL);
      $thumbUrls[$id] = (string)$req->getUri();
    } catch (Throwable $e) {
      $thumbUrls[$id] = null;
    }
  } else {
    $thumbUrls[$id] = null;
  }
}

$self = strtok($_SERVER['REQUEST_URI'], '?');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ferretería Mejorada (Directo a S4)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0b1220}
    .card{border:0;border-radius:18px}
    .soft{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)}
    .muted{color:rgba(255,255,255,.75)}
    .thumb{width:64px;height:64px;border-radius:12px;object-fit:cover;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);}
    .list-item{border-bottom:1px solid rgba(255,255,255,.08)}
    .list-item:last-child{border-bottom:0}
    .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
  </style>
</head>
<body class="text-white">
<div class="container py-5">

  <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
    <div>
      <h1 class="mb-1">Ferretería (mejorada)</h1>
      <div class="muted">La foto se sube <b>directo a S4</b> (tu servidor no recibe el archivo).</div>
    </div>
    <div class="muted small mt-2 mt-md-0 mono">
      endpoint: <?= h($S4_ENDPOINT) ?> · bucket: <?= h($S4_BUCKET) ?>
    </div>
  </div>

  <div id="alertBox" class="alert d-none"></div>

  <div class="row g-4">
    <!-- FORM -->
    <div class="col-12 col-lg-5">
      <div class="card soft p-4">
        <h4 class="mb-3">Registrar producto</h4>

        <form id="productForm" class="vstack gap-3">
          <div>
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre" required maxlength="255" placeholder="Ej: Martillo 16oz">
          </div>

          <div>
            <label class="form-label">Cantidad</label>
            <input class="form-control" name="cantidad" type="number" min="0" value="0" required>
          </div>

          <div>
            <label class="form-label">Foto (JPG/PNG/WEBP/GIF)</label>
            <input class="form-control" type="file" name="foto" accept="image/*,video/*" required>

            <div class="form-text text-white-50">Subida directa: no te limita upload_max_filesize de PHP.</div>
          </div>

          <div class="progress d-none" id="progWrap">
            <div class="progress-bar" id="progBar" role="progressbar" style="width: 0%;">0%</div>
          </div>

          <button class="btn btn-primary btn-lg" id="btnSave">Guardar y subir</button>
        </form>

        <hr class="my-4" style="border-color: rgba(255,255,255,.15)">
        <div class="muted small">
          <div><b>Nota:</b> si al subir te sale error en consola tipo CORS, te falta configurar CORS en el bucket.</div>
          <div class="mt-2">Prefijo: <code><?= h($S4_PREFIX) ?></code></div>
        </div>
      </div>
    </div>

    <!-- LISTADO -->
    <div class="col-12 col-lg-7">
      <div class="card soft p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h4 class="mb-0">Productos</h4>
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
  <?php
    $key = (string)$row['image_key'];
    $isVideo = (bool)preg_match('/\.(mp4|webm|mov)$/i', $key);
  ?>
  <?php if ($isVideo): ?>
    <video class="thumb" src="<?= h($url) ?>" muted playsinline preload="metadata"></video>
  <?php else: ?>
    <img class="thumb" src="<?= h($url) ?>" alt="imagen">
  <?php endif; ?>
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
                  <div class="muted small mt-2 mono">
                    key: <?= h((string)$row['image_key']) ?>
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

<script>
const SIGN_URL = <?= json_encode($self . '?api=sign') ?>;
const SAVE_URL = <?= json_encode($self . '?api=save') ?>;

const form = document.getElementById('productForm');
const btn = document.getElementById('btnSave');
const alertBox = document.getElementById('alertBox');
const progWrap = document.getElementById('progWrap');
const progBar  = document.getElementById('progBar');

function showAlert(type, msg) {
  alertBox.className = 'alert alert-' + type;
  alertBox.textContent = msg;
  alertBox.classList.remove('d-none');
}

function resetProgress() {
  progWrap.classList.add('d-none');
  progBar.style.width = '0%';
  progBar.textContent = '0%';
}

function setProgress(p) {
  progWrap.classList.remove('d-none');
  progBar.style.width = p + '%';
  progBar.textContent = p + '%';
}

async function postJson(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || !json.ok) {
    throw new Error(json.error || 'Error inesperado');
  }
  return json;
}

// Subida con progreso (PUT a la URL firmada)
function putFileWithProgress(url, file, headers) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('PUT', url, true);

    for (const [k,v] of Object.entries(headers || {})) {
      xhr.setRequestHeader(k, v);
    }

    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) {
        const p = Math.round((e.loaded / e.total) * 100);
        setProgress(p);
      }
    };

    xhr.onload = () => {
      // S3 suele responder 200 o 204
      if (xhr.status === 200 || xhr.status === 204) resolve(true);
      else reject(new Error('Upload falló. HTTP ' + xhr.status + ' ' + (xhr.responseText || '')));
    };

    xhr.onerror = () => reject(new Error('Error de red/CORS al subir.'));
    xhr.send(file);
  });
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  alertBox.classList.add('d-none');
  resetProgress();

  const nombre = form.nombre.value.trim();
  const cantidad = parseInt(form.cantidad.value || '0', 10);
  const file = form.foto.files[0];

  if (!file) return showAlert('warning', 'Selecciona una foto.');
  if (!nombre) return showAlert('warning', 'Escribe el nombre.');

  btn.disabled = true;
  btn.textContent = 'Subiendo...';

  try {
    // 1) Pedir URL firmada al servidor (solo firma, no recibe archivo)
    const signed = await postJson(SIGN_URL, {nombre, mime: file.type});

    // 2) Subir directo a S4
    await putFileWithProgress(signed.uploadUrl, file, signed.headers);

    // 3) Guardar producto en MySQL (nombre, cantidad, key)
    await postJson(SAVE_URL, {nombre, cantidad, key: signed.key});

    showAlert('success', 'Producto guardado y foto subida directo a S4 ✅');
    setTimeout(() => location.reload(), 600);

  } catch (err) {
    showAlert('danger', err.message || String(err));
    btn.disabled = false;
    btn.textContent = 'Guardar y subir';
    resetProgress();
    return;
  }
});
</script>

</body>
</html>
