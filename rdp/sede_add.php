<?php
// sede_add.php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);

    if (!empty($nombre)) {
        $stmt = $conexion->prepare("INSERT INTO rdp_sedes (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php");
exit();
?>