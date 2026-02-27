<?php
// modules/inventario/inv_lib.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

/**
 * Código de inventario (NO se guarda).
 * Formato: E<empresaId>-<ID6>
 * Ej: E12-000023
 */
function inv_codigo($empresaId, $creadoDatetime, $bienId) {
  $empresaId = (int)$empresaId;
  $bienId = (int)$bienId;
  return 'E' . $empresaId . '-' . str_pad((string)$bienId, 6, '0', STR_PAD_LEFT);
}

/**
 * Parse E<empresa>-<ID>
 * Acepta: E12-000023 (case-insensitive)
 */
function inv_parse_codigo($codigoInv) {
  $codigoInv = trim((string)$codigoInv);
  if (!preg_match('/^E(\d+)-(\d{1,})$/i', $codigoInv, $m)) return false;

  return [
    'empresa' => (int)$m[1],
    'id'      => (int)$m[2],
  ];
}

/**
 * Detecta esquema HTTP/HTTPS de forma compatible.
 */
function inv_req_scheme() {
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $p = strtolower(trim((string)$_SERVER['HTTP_X_FORWARDED_PROTO']));
    if ($p === 'https' || $p === 'http') return $p;
  }
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  return $https ? 'https' : 'http';
}

/**
 * Host actual (incluye puerto si viene en HTTP_HOST).
 */
function inv_req_host() {
  if (!empty($_SERVER['HTTP_HOST'])) return (string)$_SERVER['HTTP_HOST'];
  if (!empty($_SERVER['SERVER_NAME'])) {
    $host = (string)$_SERVER['SERVER_NAME'];
    $port = (string)($_SERVER['SERVER_PORT'] ?? '');
    if ($port && $port !== '80' && $port !== '443') return $host . ':' . $port;
    return $host;
  }
  return 'localhost';
}

/**
 * Construye URL base absoluta: scheme://host + basePath (puede ser BASE_URL tipo /sistema)
 */
function inv_build_base_url($basePath) {
  $scheme = inv_req_scheme();
  $host   = inv_req_host();

  $basePath = (string)$basePath;
  $basePath = trim($basePath);

  if ($basePath === '' || $basePath === '/') {
    return $scheme . '://' . $host;
  }

  // Normaliza: debe empezar con "/" para concatenar
  if ($basePath[0] !== '/') $basePath = '/' . $basePath;
  $basePath = rtrim($basePath, '/');

  return $scheme . '://' . $host . $basePath;
}

/**
 * Payload del QR: URL ABSOLUTA al detalle p��blico (sin sesi��n).
 *
 * $baseUrlOrBasePath puede ser:
 * - "https://dominio.tld/sistema" (URL completa) o
 * - BASE_URL "/sistema" (solo path)
 */
function inv_qr_payload($baseUrlOrBasePath, $codigoInv) {
  $base = trim((string)$baseUrlOrBasePath);

  if (preg_match('#^https?://#i', $base)) {
    $site = rtrim($base, '/');
  } else {
    $site = inv_build_base_url($base);
  }

  // IMPORTANTE: detalle p��blico
  return $site . '/modules/inventario/detalle_publico.php?code=' . rawurlencode($codigoInv);
}
