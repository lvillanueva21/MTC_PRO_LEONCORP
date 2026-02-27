<?php
// modules/camaras/retirar_hdd.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
          && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function hdd_ret_responder($ok, $msg, $httpCode = 200)
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

// Solo Desarrollo y Gerente pueden retirar HDD
if (!($esDesarrollo || $esGerente)) {
    hdd_ret_responder(false, 'No tienes permiso para retirar HDD.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hdd_ret_responder(false, 'Método no permitido.', 405);
}

$idHdd        = isset($_POST['id_hdd']) ? (int)$_POST['id_hdd'] : 0;
$responsable  = isset($_POST['responsable']) ? trim($_POST['responsable']) : '';
$fechaInicio  = isset($_POST['fecha_inicio_grab']) ? trim($_POST['fecha_inicio_grab']) : '';
$fechaFin     = isset($_POST['fecha_fin_grab']) ? trim($_POST['fecha_fin_grab']) : '';
$notaRetiro   = isset($_POST['nota_retiro']) ? trim($_POST['nota_retiro']) : '';

$errores = array();

if ($idHdd <= 0) {
    $errores[] = 'HDD no válido.';
}
if ($responsable === '' || mb_strlen($responsable, 'UTF-8') > 150) {
    $errores[] = 'El nombre del responsable es obligatorio y debe tener hasta 150 caracteres.';
}
if ($notaRetiro !== '' && mb_strlen($notaRetiro, 'UTF-8') > 500) {
    $errores[] = 'La nota es demasiado larga.';
}

$fiObj = null;
$ffObj = null;

if ($fechaInicio === '' || $fechaFin === '') {
    $errores[] = 'Las fechas de inicio y fin de grabación son obligatorias.';
} else {
    $fiObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaInicio);
    $ffObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaFin);
    if (!$fiObj || !$ffObj) {
        $errores[] = 'Las fechas de grabación no tienen un formato válido.';
    } elseif ($fiObj > $ffObj) {
        $errores[] = 'La fecha de inicio de grabación no puede ser mayor que la fecha de fin.';
    }
}

if (!empty($errores)) {
    hdd_ret_responder(false, implode(' ', $errores), 200);
}

// Verificar HDD
$sqlHdd = "
    SELECT id, estado
    FROM cam_hdd
    WHERE id = ?
    LIMIT 1
";
$stmtHdd = mysqli_prepare($cn, $sqlHdd);
if (!$stmtHdd) {
    hdd_ret_responder(false, 'Error al validar el HDD.', 500);
}
mysqli_stmt_bind_param($stmtHdd, 'i', $idHdd);
mysqli_stmt_execute($stmtHdd);
$resHdd = mysqli_stmt_get_result($stmtHdd);
$hdd    = mysqli_fetch_assoc($resHdd);
mysqli_free_result($resHdd);
mysqli_stmt_close($stmtHdd);

if (!$hdd) {
    hdd_ret_responder(false, 'El HDD indicado no existe.', 200);
}
if ($hdd['estado'] !== 'INSTALADO') {
    hdd_ret_responder(false, 'Solo se pueden retirar discos instalados.', 200);
}

$fechaRetiroSql = date('Y-m-d H:i:00');
$fiSql          = $fiObj->format('Y-m-d H:i:00');
$ffSql          = $ffObj->format('Y-m-d H:i:00');

// Actualizar HDD a RETIRADO
$sqlUpd = "
    UPDATE cam_hdd
    SET estado = 'RETIRADO',
        fecha_retiro = ?,
        responsable_retiro = ?,
        fecha_inicio_grab = ?,
        fecha_fin_grab = ?,
        nota_retiro = ?
    WHERE id = ?
";
$stmtUpd = mysqli_prepare($cn, $sqlUpd);
if (!$stmtUpd) {
    hdd_ret_responder(false, 'Error al preparar el retiro del HDD.', 500);
}
mysqli_stmt_bind_param(
    $stmtUpd,
    'sssssi',
    $fechaRetiroSql,
    $responsable,
    $fiSql,
    $ffSql,
    $notaRetiro,
    $idHdd
);

if (!mysqli_stmt_execute($stmtUpd)) {
    mysqli_stmt_close($stmtUpd);
    hdd_ret_responder(false, 'Error al actualizar el estado del HDD.', 200);
}
mysqli_stmt_close($stmtUpd);

hdd_ret_responder(true, 'HDD retirado correctamente.', 200);
