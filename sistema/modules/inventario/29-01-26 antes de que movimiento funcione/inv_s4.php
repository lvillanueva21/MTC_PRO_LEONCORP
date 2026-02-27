<?php
// modules/inventario/inv_s4.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(403); exit('Acceso directo no permitido.');
}

$autoload = __DIR__ . '/../aws-sdk/aws-autoloader.php';
if (!file_exists($autoload)) {
  http_response_code(500);
  exit('Falta AWS SDK: ' . $autoload);
}
require_once $autoload;

use Aws\S3\S3Client;

// ========= CONFIG S4 (MEGA) =========
// Ideal: mover a config privado fuera del repo.
const INV_S4_ACCESS_KEY = 'AKIAB5BSZ75OQZRHLGJFISDSQYZCNJNTDQYAAR6BCF6D';
const INV_S4_SECRET_KEY = 'u74jMxbmfRclxwMK9uKh2huMeYEGYUZxANQjEcMy';
const INV_S4_BUCKET     = 'lsistemas';
const INV_S4_REGION     = 'eu-central-1';
const INV_S4_ENDPOINT   = 'https://s3.eu-central-1.s4.mega.io';

// ======== AISLAMIENTO MULTI-INSTALACIÓN ========
// DEBE SER ÚNICO por instalación/cliente.
// Ejemplos: "cliente_acme", "srv01", un UUID, etc.
// NO lo cambies después o perderás acceso a imágenes viejas.
const INV_TENANT = 'leoncorp_bucket';

// Prefijo base dentro del bucket
const INV_S4_PREFIX = 'inventario/' . INV_TENANT . '/bienes/';
// ==============================================

function inv_s4_client(): S3Client {
  static $c = null;
  if ($c) return $c;

  $c = new S3Client([
    'version' => 'latest',
    'region' => INV_S4_REGION,
    'endpoint' => INV_S4_ENDPOINT,
    'credentials' => ['key' => INV_S4_ACCESS_KEY, 'secret' => INV_S4_SECRET_KEY],
    'use_path_style_endpoint' => true,
  ]);
  return $c;
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
  return INV_S4_PREFIX . $empresaId . "/{$ym}/{$slug}_" . time() . "_{$rand}.{$ext}";
}

function inv_s4_key_allowed(?string $key, int $empresaId): bool {
  $key = trim((string)$key);
  if ($key === '') return true; // permitir null/empty (quitar imagen)

  $prefix = INV_S4_PREFIX . $empresaId . '/';
  return str_starts_with($key, $prefix);
}

function inv_s4_sign_put(int $empresaId, string $nombre, string $mime, string $ttl = '+60 minutes'): array {
  $key = inv_s4_make_key($empresaId, $nombre, $mime);
  $s3 = inv_s4_client();

  $cmd = $s3->getCommand('PutObject', [
    'Bucket' => INV_S4_BUCKET,
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

function inv_s4_presign_get(string $key, string $ttl = '+2 hours'): string {
  $s3 = inv_s4_client();
  $cmd = $s3->getCommand('GetObject', [
    'Bucket' => INV_S4_BUCKET,
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
    'Bucket' => INV_S4_BUCKET,
    'Key' => $key,
  ]);
}
