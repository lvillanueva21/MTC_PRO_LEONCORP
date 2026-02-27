<?php
// includes/s4_config.php
// NO subas este archivo al repo si contiene secretos.

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

// Credenciales / endpoint
defined('S4_ACCESS_KEY') || define('S4_ACCESS_KEY',  'AKIAB5BSZ75OQZRHLGJFISDSQYZCNJNTDQYAAR6BCF6D');
defined('S4_SECRET_KEY') || define('S4_SECRET_KEY',  'u74jMxbmfRclxwMK9uKh2huMeYEGYUZxANQjEcMy');
defined('S4_BUCKET')     || define('S4_BUCKET',      'lsistemas');
defined('S4_REGION')     || define('S4_REGION',      'eu-central-1');
defined('S4_ENDPOINT')   || define('S4_ENDPOINT',    'https://s3.eu-central-1.s4.mega.io');

// Prefijo base y tenant (aislamiento multi-instalación)
// IMPORTANTE: no lo cambies después o “pierdes” el path de imágenes viejas.
defined('S4_INV_PREFIX') || define('S4_INV_PREFIX',  'inventario/');
defined('S4_INV_TENANT') || define('S4_INV_TENANT',  'leoncorp_bucket');

// TTLs
defined('S4_TTL_UPLOAD') || define('S4_TTL_UPLOAD',  '+60 minutes');
defined('S4_TTL_VIEW')   || define('S4_TTL_VIEW',    '+2 hours');
