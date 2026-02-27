<?php
// gestor/get_table_count.php
include 'db.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_GET['tabla']) || $_GET['tabla'] === '') {
    echo json_encode(['ok' => false, 'error' => 'tabla requerida']);
    exit;
}

$tabla = $conexion->real_escape_string($_GET['tabla']);

// Validación básica de nombre (opcional, para mayor seguridad)
if (!preg_match('/^[A-Za-z0-9_]+$/', $tabla)) {
    echo json_encode(['ok' => false, 'error' => 'nombre de tabla inválido']);
    exit;
}

$sql = "SELECT COUNT(*) AS total FROM `{$tabla}`";
$res = $conexion->query($sql);

if ($res && ($row = $res->fetch_assoc())) {
    echo json_encode(['ok' => true, 'table' => $tabla, 'count' => (int)$row['total']]);
} else {
    echo json_encode(['ok' => false, 'table' => $tabla, 'count' => null, 'error' => 'error al contar']);
}
