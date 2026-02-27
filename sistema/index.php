<?php
require __DIR__.'/includes/conexion.php';
$ok = false; $msg = '';
try {
  $res = db()->query("
    SELECT COUNT(*) c
    FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name LIKE 'mtp_%'
  ");
  $row = $res->fetch_assoc();
  $ok = true;
  $msg = "Conexión OK. Tablas del sistema: {$row['c']}.";
} catch (Throwable $e) {
  $msg = "Error al verificar tablas.";
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema | Inicio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h1 class="mb-3">Sistema de Emisión de Certificados</h1>
  <div class="alert <?= $ok ? 'alert-success' : 'alert-danger' ?>"><?= htmlspecialchars($msg) ?></div>
  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="login.php">Ir a Login</a>
    <a class="btn btn-outline-secondary" href="registro.php">Carga inicial (registro.php)</a>
  </div>
</div>
</body>
</html>
