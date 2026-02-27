<?php
// gestor/get_all_create_tables.php
include 'db.php';

// Mostrar solo tablas base (excluye VIEWS)
$sql = "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'";
$result = $conexion->query($sql);

header('Content-Type: text/html; charset=UTF-8');

if (!$result) {
    echo "<p>Error al listar tablas.</p>";
    exit;
}

echo '<ol class="lista-create-tables">';

$contador = 0;
while ($row = $result->fetch_array()) {
    $tabla = $row[0];

    // Obtenemos el CREATE TABLE
    $tablaEsc = $conexion->real_escape_string($tabla);
    $resCT = $conexion->query("SHOW CREATE TABLE `{$tablaEsc}`");

    if ($resCT && $data = $resCT->fetch_assoc()) {
        $create = isset($data['Create Table']) ? $data['Create Table'] : '';

        $contador++;
        echo '<li>';
        echo '<h4>' . $contador . '. ' . htmlspecialchars($tabla, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h4>';
        echo '<pre class="create-sql">' . htmlspecialchars($create, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        echo '</li>';
    }
}

echo '</ol>';
