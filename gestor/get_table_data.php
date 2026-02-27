<?php
// gestor/get_table_data.php
include 'db.php';

$tabla = isset($_GET['tabla']) ? $conexion->real_escape_string($_GET['tabla']) : '';
$limite = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

if ($tabla) {
    $sql = ($limite > 0)
        ? "SELECT * FROM `$tabla` LIMIT $limite"
        : "SELECT * FROM `$tabla`";

    $resultado = $conexion->query($sql);

    if ($resultado) {
echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: max-content;">';
        // Encabezados
        echo '<thead style="background-color: #dfe6e9;"><tr>';
        while ($finfo = $resultado->fetch_field()) {
            echo '<th>' . htmlspecialchars($finfo->name) . '</th>';
        }
        echo '</tr></thead>';

        // Datos
        echo '<tbody>';
        while ($fila = $resultado->fetch_assoc()) {
            echo '<tr>';
            foreach ($fila as $valor) {
                echo '<td>' . htmlspecialchars((string)$valor) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
echo '</table>';
    } else {
        echo 'Error al obtener los datos.';
    }
} else {
    echo 'Tabla no especificada.';
}
?>
