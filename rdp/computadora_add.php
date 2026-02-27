<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empresa_id     = intval($_POST['empresa_id']);
    $etiqueta       = trim($_POST['etiqueta']);
    $direccion_ip   = trim($_POST['direccion_ip']);
    $nombre_pc      = trim($_POST['nombre_pc']);
    $mac            = strtoupper(trim($_POST['mac']));
    $nro_serie      = trim($_POST['nro_serie']);

    if ($empresa_id && $etiqueta && $direccion_ip && $nombre_pc && $mac && $nro_serie) {
        $stmt = $conexion->prepare("INSERT INTO rdp_computadoras (empresa_id, etiqueta, direccion_ip, nombre_pc, mac, nro_serie) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $empresa_id, $etiqueta, $direccion_ip, $nombre_pc, $mac, $nro_serie);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: empresa_view.php?id=" . $empresa_id);
    exit();
}
?>