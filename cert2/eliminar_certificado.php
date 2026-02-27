<?php
// eliminar_certificado.php
include('db.php');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Obtener el nombre del archivo QR antes de borrar
    $stmt = $conexion->prepare("SELECT qr_code FROM certificados WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        $qr_file = "qrs/" . $row['qr_code'] . ".png";
        if (file_exists($qr_file)) {
            unlink($qr_file);
        }

        // Borrar el certificado
        $stmt = $conexion->prepare("DELETE FROM certificados WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: certificado.php");
exit;
ob_start();
?>