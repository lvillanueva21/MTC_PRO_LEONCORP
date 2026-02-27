
<?php
//gestor/db.php

$direccionservidor = "localhost";
$baseDatos = "lsistemas_erp_2026";
$usuarioBD = "lsistemas_luigi2026";
$contraseniaBD = '20@26LSistemas#&&';

// Crear la conexion
$conexion = new mysqli($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);

// Verificar la conexion
if ($conexion->connect_error) {
    die("Conexion fallida: " . $conexion->connect_error);
}
?>
