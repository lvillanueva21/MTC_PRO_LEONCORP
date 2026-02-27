<?php
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$pc_id = intval($_GET['id']);
$empresa_id = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $etiqueta = trim($_POST['etiqueta']);
    $direccion_ip = trim($_POST['direccion_ip']);
    $nombre_pc = trim($_POST['nombre_pc']);
    $mac = strtoupper(trim($_POST['mac']));
    $nro_serie = trim($_POST['nro_serie']);

    $stmt = $conexion->prepare("UPDATE rdp_computadoras SET etiqueta=?, direccion_ip=?, nombre_pc=?, mac=?, nro_serie=? WHERE id=?");
    $stmt->bind_param("sssssi", $etiqueta, $direccion_ip, $nombre_pc, $mac, $nro_serie, $pc_id);
    $stmt->execute();
    $stmt->close();

    $empresa_id = intval($_POST['empresa_id']);
    header("Location: empresa_view.php?id=" . $empresa_id);
    exit();
}

// Obtener datos actuales
$stmt = $conexion->prepare("SELECT * FROM rdp_computadoras WHERE id = ?");
$stmt->bind_param("i", $pc_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

// Validar si la computadora existe
if (!$data) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
      <meta charset='UTF-8'>
      <title>Error - Computadora no encontrada</title>
      <link rel='stylesheet' href='assets/style.css'>
    </head>
    <body>
      <div class='container' style='color:white; text-align:center; margin-top:50px;'>
        <h2>❌ Computadora no encontrada</h2>
        <p>No se encontró ninguna computadora con el ID proporcionado (<strong>$pc_id</strong>).</p>
        <a href='index.php' style='color: #0d6efd;'>🔙 Volver al inicio</a>
      </div>
    </body>
    </html>";
    exit();
}

$empresa_id = $data['empresa_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Computadora</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="flex-direction: column; align-items: center;">
  <h2 style="color: white;">Editar Computadora</h2>
  <form action="" method="POST" style="background: white; padding: 20px; border-radius: 10px;">
    <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

    <label>Etiqueta:</label><br>
    <input type="text" name="etiqueta" value="<?= htmlspecialchars($data['etiqueta']) ?>" required><br><br>

    <label>Dirección IP:</label><br>
    <input type="text" name="direccion_ip" value="<?= htmlspecialchars($data['direccion_ip']) ?>" required><br><br>

    <label>Nombre PC:</label><br>
    <input type="text" name="nombre_pc" value="<?= htmlspecialchars($data['nombre_pc']) ?>" required><br><br>

    <label>MAC:</label><br>
    <input type="text" name="mac" value="<?= htmlspecialchars($data['mac']) ?>" required><br><br>

    <label>Nro Serie:</label><br>
    <input type="text" name="nro_serie" value="<?= htmlspecialchars($data['nro_serie']) ?>" maxlength="10" required><br><br>

    <button type="submit">Actualizar</button>
    <a href="empresa_view.php?id=<?= $empresa_id ?>">Cancelar</a>
  </form>
</div>
</body>
</html>