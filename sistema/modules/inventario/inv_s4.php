<?php
// modules/inventario/inv_s4.php

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

// Config (protegida por htaccess + guard interno)
require_once __DIR__ . '/../../includes/s4_config.php';

// AWS SDK
$autoload = __DIR__ . '/../aws-sdk/aws-autoloader.php';
if (!file_exists($autoload)) {
  http_response_code(500);
  exit('Falta AWS SDK: ' . $autoload);
}
require_once $autoload;

use Aws\S3\S3Client;

function inv_s4_client(): S3Client {
  static $c = null;
  if ($c) return $c;

  // Validación mínima por si falta config
  foreach (['S4_ACCESS_KEY','S4_SECRET_KEY','S4_BUCKET','S4_REGION','S4_ENDPOINT'] as $k) {
    if (!defined($k) || constant($k) === '') {
      http_response_code(500);
      exit('Config S4 incompleta: falta ' . $k);
    }
  }

  $c = new S3Client([
    'version' => 'latest',
    'region' => S4_REGION,
    'endpoint' => S4_ENDPOINT,
    'credentials' => ['key' => S4_ACCESS_KEY, 'secret' => S4_SECRET_KEY],
    'use_path_style_endpoint' => true,
  ]);

  return $c;
}

// Prefijo final dentro del bucket:
// inventario/<TENANT>/bienes/
function inv_s4_prefix(): string {
  static $p = null;
  if ($p !== null) return $p;

  $base = rtrim((string)S4_INV_PREFIX, '/') . '/';
  $tenant = defined('S4_INV_TENANT') ? (string)S4_INV_TENANT : '';
  $tenant = trim($tenant);

  if ($tenant === '') {
    http_response_code(500);
    exit('Config S4 incompleta: falta S4_INV_TENANT');
  }

  $p = $base . $tenant . '/bienes/';
  return $p;
}

function inv_s4_clean_slug(string $s): string {
  $s = trim($s);
  $s = preg_replace('/[^a-zA-Z0-9]+/', '_', $s);
  $s = trim($s, '_');
  return $s ?: 'bien';
}

function inv_s4_ext_from_mime(string $mime): ?string {
  return match (strtolower(trim($mime))) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default => null,
  };
}

function inv_s4_make_key(int $empresaId, string $nombre, string $mime): string {
  $ext = inv_s4_ext_from_mime($mime);
  if (!$ext) throw new RuntimeException('Permitidos: JPG, PNG, WEBP, GIF.');

  $slug = substr(inv_s4_clean_slug($nombre), 0, 60);
  $rand = bin2hex(random_bytes(6));
  $ym   = date('Y/m');

  // inventario/<TENANT>/bienes/<empresaId>/<Y/m>/<slug>_time_rand.ext
  return inv_s4_prefix() . $empresaId . "/{$ym}/{$slug}_" . time() . "_{$rand}.{$ext}";
}

function inv_s4_key_allowed(?string $key, int $empresaId): bool {
  $key = trim((string)$key);
  if ($key === '') return true; // permitir null/empty (quitar imagen)

  $prefix = inv_s4_prefix() . $empresaId . '/';
  return str_starts_with($key, $prefix);
}

function inv_s4_sign_put(int $empresaId, string $nombre, string $mime, string $ttl = S4_TTL_UPLOAD): array {
  $key = inv_s4_make_key($empresaId, $nombre, $mime);
  $s3 = inv_s4_client();

  $cmd = $s3->getCommand('PutObject', [
    'Bucket' => S4_BUCKET,
    'Key' => $key,
    'ContentType' => $mime,
  ]);
  $req = $s3->createPresignedRequest($cmd, $ttl);

  return [
    'key' => $key,
    'uploadUrl' => (string)$req->getUri(),
    'headers' => ['Content-Type' => $mime],
  ];
}

function inv_s4_presign_get(string $key, string $ttl = S4_TTL_VIEW): string {
  $s3 = inv_s4_client();
  $cmd = $s3->getCommand('GetObject', [
    'Bucket' => S4_BUCKET,
    'Key' => $key,
  ]);
  $req = $s3->createPresignedRequest($cmd, $ttl);
  return (string)$req->getUri();
}

function inv_s4_delete(string $key): void {
  $key = trim($key);
  if ($key === '') return;

  $s3 = inv_s4_client();
  $s3->deleteObject([
    'Bucket' => S4_BUCKET,
    'Key' => $key,
  ]);
}
