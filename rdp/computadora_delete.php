<?php
require_once 'db.php';

if (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['empresa']) && is_numeric($_GET['empresa'])) {
    $pc_id = intval($_GET['id']);
    $empresa_id = intval($_GET['empresa']);

    $stmt = $conexion->prepare("DELETE FROM rdp_computadoras WHERE id = ?");
    $stmt->bind_param("i", $pc_id);
    $stmt->execute();
    $stmt->close();

    header("Location: empresa_view.php?id=" . $empresa_id);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>