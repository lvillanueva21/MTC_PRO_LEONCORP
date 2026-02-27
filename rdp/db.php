<?php
// db.php
$direccionservidor = "localhost";
$baseDatos = "lsistemas_oldleoncorp";
$usuarioBD = "lsistemas_luigi2025";
$contraseniaBD = "#20%&26%Leon@";

// Crear la conexion
$conexion = new mysqli($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);

// Verificar la conexion
if ($conexion->connect_error) {
    die("Conexion fallida: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>