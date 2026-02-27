<?php
// gestor/get_create_table.php
include 'db.php';

if (isset($_GET['tabla'])) {
    $tabla = $conexion->real_escape_string($_GET['tabla']);

    $sql = "SHOW CREATE TABLE `$tabla`";
    $resultado = $conexion->query($sql);

    if ($resultado && $fila = $resultado->fetch_assoc()) {
        echo $fila['Create Table'];
    } else {
        echo "-- Error al obtener CREATE TABLE --";
    }
}
?>
