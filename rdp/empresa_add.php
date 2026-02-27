<?php
// empresa_add.php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$nombre = trim($_POST['nombre']);
$sede_id = intval($_POST['sede_id']);
$color = trim($_POST['color']); // Nuevo campo
$icono = trim($_POST['icono']);

    if (!empty($nombre) && $sede_id > 0) {
$stmt = $conexion->prepare("INSERT INTO rdp_empresas (sede_id, nombre, color, icono) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $sede_id, $nombre, $color, $icono);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php");
exit();
?>