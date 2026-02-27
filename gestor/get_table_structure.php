<?php
// gestor/get_table_structure.php
include 'db.php';

if (isset($_GET['tabla'])) {
    $tabla = $conexion->real_escape_string($_GET['tabla']);
    $sql = "DESCRIBE `$tabla`";
    $resultado = $conexion->query($sql);

    if ($resultado) {
        echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
        echo '<tr style="background-color: #dfe6e9;">
                <th>Campo</th>
                <th>Tipo</th>
                <th>Null</th>
                <th>Clave</th>
                <th>Por defecto</th>
                <th>Extra</th>
              </tr>';
        while ($fila = $resultado->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($fila['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($fila['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($fila['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($fila['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($fila['Default']) . '</td>';
            echo '<td>' . htmlspecialchars($fila['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo "No se pudo obtener la estructura de la tabla.";
    }
}
?>
