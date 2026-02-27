<?php
echo "<h2>⚙️ Perfil Técnico para ChatGPT</h2>";
echo "<pre>";

// Versión de PHP
echo "PHP Version: " . phpversion() . "\n";

// Extensiones importantes
$exts = ['mysqli', 'pdo_mysql', 'curl', 'mbstring', 'openssl', 'gd', 'zip', 'fileinfo'];
foreach ($exts as $ext) {
    echo "Extensión '$ext': " . (extension_loaded($ext) ? '✅ Cargada' : '❌ No cargada') . "\n";
}

// Servidor web
echo "Servidor Web: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";

// Sistema operativo
echo "Sistema Operativo: " . PHP_OS . "\n";

// Modo PHP (CGI, FPM, etc.)
echo "PHP SAPI: " . php_sapi_name() . "\n";

// Zona horaria
echo "Zona Horaria: " . date_default_timezone_get() . "\n";

// Base de datos (MySQL)
$conexion = @new mysqli("localhost", "USUARIO", "CONTRASEÑA", "BASE_DATOS");
if (!$conexion->connect_error) {
    echo "MySQL Version: " . $conexion->server_info . "\n";
    $conexion->close();
} else {
    echo "MySQL: No se pudo conectar (rellena usuario/clave)\n";
}

echo "</pre>";
?>
