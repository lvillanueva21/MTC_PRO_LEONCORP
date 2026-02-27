<?php
// modules/camaras/guardar_hdd_consumo.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
          && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function hdd_cons_responder($ok, $msg, $httpCode = 200)
{
    global $isAjax;
    http_response_code($httpCode);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => (bool)$ok, 'message' => (string)$msg));
        exit;
    }
    $params = array('msg' => $msg);
    if (!$ok) {
        $params['e'] = 1;
    }
    header('Location: index.php?' . http_build_query($params));
    exit;
}

// Solo Desarrollo y Gerente pueden registrar consumo
if (!($esDesarrollo || $esGerente)) {
    hdd_cons_responder(false, 'No tienes permiso para registrar consumo de HDD.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hdd_cons_responder(false, 'Método no permitido.', 405);
}

$idHdd   = isset($_POST['id_hdd']) ? (int)$_POST['id_hdd'] : 0;
$fecha   = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
$valor   = isset($_POST['valor']) ? trim($_POST['valor']) : '';
$unidad  = isset($_POST['unidad']) ? trim($_POST['unidad']) : 'GB';
$nota    = isset($_POST['nota']) ? trim($_POST['nota']) : '';

// El tipo siempre será ESPACIO LIBRE
$tipo = 'LIBRE';

$errores = array();

if ($idHdd <= 0) {
    $errores[] = 'HDD no válido.';
}
if ($fecha === '') {
    $errores[] = 'La fecha es obligatoria.';
} else {
    $fechaObj = DateTime::createFromFormat('Y-m-d\TH:i', $fecha);
    if (!$fechaObj) {
        $errores[] = 'La fecha no tiene un formato válido.';
    }
}
if ($valor === '' || !ctype_digit($valor)) {
    $errores[] = 'El valor debe ser un número entero positivo.';
} else {
    $vInt = (int)$valor;
    if ($vInt < 0 || $vInt > 1000000) {
        $errores[] = 'El valor de espacio es inválido.';
    }
}
if ($unidad !== 'GB' && $unidad !== 'TB') {
    $errores[] = 'Unidad no válida.';
}
if ($nota !== '' && mb_strlen($nota, 'UTF-8') > 255) {
    $errores[] = 'La nota es demasiado larga.';
}

if (!empty($errores)) {
    hdd_cons_responder(false, implode(' ', $errores), 200);
}

// Verificar que el HDD exista y esté instalado
$sqlHdd = "
    SELECT id, estado
    FROM cam_hdd
    WHERE id = ?
    LIMIT 1
";
$stmtHdd = mysqli_prepare($cn, $sqlHdd);
if (!$stmtHdd) {
    hdd_cons_responder(false, 'Error al validar el HDD.', 500);
}
mysqli_stmt_bind_param($stmtHdd, 'i', $idHdd);
mysqli_stmt_execute($stmtHdd);
$resHdd = mysqli_stmt_get_result($stmtHdd);
$hdd    = mysqli_fetch_assoc($resHdd);
mysqli_free_result($resHdd);
mysqli_stmt_close($stmtHdd);

if (!$hdd) {
    hdd_cons_responder(false, 'El HDD indicado no existe.', 200);
}
if ($hdd['estado'] !== 'INSTALADO') {
    hdd_cons_responder(false, 'Solo se puede registrar consumo para HDD instalados.', 200);
}

// Normalizamos fecha a Lima (la conexión ya está en America/Lima)
$fechaObj = DateTime::createFromFormat('Y-m-d\TH:i', $fecha);
$fechaReg = $fechaObj->format('Y-m-d H:i:00');
$fechaDia = $fechaObj->format('Y-m-d');

// Convertir valor a GB
$valorGb = (int)$valor;
if ($unidad === 'TB') {
    $valorGb = $valorGb * 1024;
}

// Insertar o actualizar (único por día)
$sqlIns = "
    INSERT INTO cam_hdd_consumo (id_hdd, fecha_dia, fecha_registro, tipo, valor_gb, nota)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        fecha_registro = VALUES(fecha_registro),
        tipo           = VALUES(tipo),
        valor_gb       = VALUES(valor_gb),
        nota           = VALUES(nota),
        actualizacion  = CURRENT_TIMESTAMP
";
$stmtIns = mysqli_prepare($cn, $sqlIns);
if (!$stmtIns) {
    hdd_cons_responder(false, 'Error al preparar el guardado de consumo.', 500);
}
mysqli_stmt_bind_param(
    $stmtIns,
    'isssis',
    $idHdd,
    $fechaDia,
    $fechaReg,
    $tipo,
    $valorGb,
    $nota
);

if (!mysqli_stmt_execute($stmtIns)) {
    mysqli_stmt_close($stmtIns);
    hdd_cons_responder(false, 'Error al guardar el consumo del HDD.', 200);
}
mysqli_stmt_close($stmtIns);

hdd_cons_responder(true, 'Consumo del HDD registrado correctamente.', 200);
