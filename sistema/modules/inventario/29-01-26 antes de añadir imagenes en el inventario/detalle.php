<?php
// modules/inventario/detalle.php
// COMPATIBILIDAD: QRs antiguos apuntaban aquí.
// Ahora redirige al detalle público (sin sesión), manteniendo el code.

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Código requerido';
  exit;
}

header('Location: detalle_publico.php?code=' . rawurlencode($code));
exit;
