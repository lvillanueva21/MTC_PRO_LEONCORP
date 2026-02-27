<?php
// modules/camaras/guardar_hdd.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
          && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function hdd_responder($ok, $msg, $httpCode = 200)
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

// Solo Desarrollo y Gerente pueden crear/actualizar HDD
if (!($esDesarrollo || $esGerente)) {
    hdd_responder(false, 'No tienes permiso para gestionar HDD.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hdd_responder(false, 'Método no permitido.', 405);
}

$idCamara   = isset($_POST['id_camara']) ? (int)$_POST['id_camara'] : 0;
$idHdd      = isset($_POST['id_hdd']) ? (int)$_POST['id_hdd'] : 0;
$marca      = isset($_POST['marca']) ? trim($_POST['marca']) : '';
$nroSerie   = isset($_POST['nro_serie']) ? trim($_POST['nro_serie']) : '';
$capValor   = isset($_POST['capacidad_valor']) ? trim($_POST['capacidad_valor']) : '';
$capUnidad  = isset($_POST['capacidad_unidad']) ? trim($_POST['capacidad_unidad']) : 'GB';
$fechaInst  = isset($_POST['fecha_instalacion']) ? trim($_POST['fecha_instalacion']) : '';
$notaInst   = isset($_POST['nota_instalacion']) ? trim($_POST['nota_instalacion']) : '';

$errores = array();

if ($idCamara <= 0) {
    $errores[] = 'Cámara no válida.';
}
if ($marca === '' || mb_strlen($marca, 'UTF-8') > 100) {
    $errores[] = 'La marca es obligatoria y debe tener hasta 100 caracteres.';
}
if ($nroSerie === '' || mb_strlen($nroSerie, 'UTF-8') > 100) {
    $errores[] = 'El nº de serie es obligatorio y debe tener hasta 100 caracteres.';
}
if ($capValor === '' || !ctype_digit($capValor)) {
    $errores[] = 'La capacidad debe ser un número entero positivo.';
} else {
    $capInt = (int)$capValor;
    if ($capInt <= 0 || $capInt > 1000000) {
        $errores[] = 'La capacidad es inválida.';
    }
}
if ($capUnidad !== 'GB' && $capUnidad !== 'TB') {
    $errores[] = 'Unidad de capacidad no válida.';
}
if ($notaInst !== '' && mb_strlen($notaInst, 'UTF-8') > 500) {
    $errores[] = 'La nota de instalación es demasiado larga.';
}

// Validar fecha instalación (formato datetime-local: Y-m-d\TH:i)
$fechaInstObj = null;
if ($fechaInst === '') {
    $errores[] = 'La fecha de instalación es obligatoria.';
} else {
    $fechaInstObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaInst);
    if (!$fechaInstObj) {
        $errores[] = 'La fecha de instalación no tiene un formato válido.';
    }
}

if (!empty($errores)) {
    hdd_responder(false, implode(' ', $errores), 200);
}

// Convertir capacidad a GB
$capacidadGb = (int)$capValor;
if ($capUnidad === 'TB') {
    $capacidadGb = $capacidadGb * 1024;
}

// Verificar que la cámara existe
$sqlCam = "SELECT id FROM cam_camaras WHERE id = ? LIMIT 1";
$stmtCam = mysqli_prepare($cn, $sqlCam);
if (!$stmtCam) {
    hdd_responder(false, 'Error al validar la cámara.', 500);
}
mysqli_stmt_bind_param($stmtCam, 'i', $idCamara);
mysqli_stmt_execute($stmtCam);
$resCam = mysqli_stmt_get_result($stmtCam);
$camaraExiste = (bool)mysqli_fetch_assoc($resCam);
mysqli_free_result($resCam);
mysqli_stmt_close($stmtCam);

if (!$camaraExiste) {
    hdd_responder(false, 'La cámara indicada no existe.', 200);
}

// Si es nuevo HDD, nos aseguramos de que no haya otro INSTALADO
if ($idHdd <= 0) {
    $sqlCheck = "
        SELECT COUNT(*) AS total
        FROM cam_hdd
        WHERE id_camara = ? AND estado = 'INSTALADO'
    ";
    $stmtCheck = mysqli_prepare($cn, $sqlCheck);
    if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, 'i', $idCamara);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);
        $rowCheck = mysqli_fetch_assoc($resCheck);
        mysqli_free_result($resCheck);
        mysqli_stmt_close($stmtCheck);

        if ($rowCheck && (int)$rowCheck['total'] > 0) {
            hdd_responder(false, 'Ya existe un HDD instalado en esta cámara. Primero debe retirarlo.', 200);
        }
    }
}

$fechaInstSql = $fechaInstObj ? $fechaInstObj->format('Y-m-d H:i:00') : null;

if ($idHdd > 0) {
    // Actualizar HDD existente
    $sql = "
        UPDATE cam_hdd
        SET marca = ?, nro_serie = ?, capacidad_gb = ?, fecha_instalacion = ?, nota_instalacion = ?
        WHERE id = ? AND id_camara = ?
    ";
    $stmt = mysqli_prepare($cn, $sql);
    if (!$stmt) {
        hdd_responder(false, 'Error al preparar la actualización del HDD.', 500);
    }
    mysqli_stmt_bind_param(
        $stmt,
        'ssissii',
        $marca,
        $nroSerie,
        $capacidadGb,
        $fechaInstSql,
        $notaInst,
        $idHdd,
        $idCamara
    );
} else {
    // Insertar nuevo HDD (estado INSTALADO)
    $sql = "
        INSERT INTO cam_hdd (id_camara, marca, nro_serie, capacidad_gb, estado, fecha_instalacion, nota_instalacion)
        VALUES (?, ?, ?, ?, 'INSTALADO', ?, ?)
    ";
    $stmt = mysqli_prepare($cn, $sql);
    if (!$stmt) {
        hdd_responder(false, 'Error al preparar el guardado del HDD.', 500);
    }
    mysqli_stmt_bind_param(
        $stmt,
        'ississ',
        $idCamara,
        $marca,
        $nroSerie,
        $capacidadGb,
        $fechaInstSql,
        $notaInst
    );
}

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    hdd_responder(false, 'Error al guardar los datos del HDD.', 200);
}

mysqli_stmt_close($stmt);

$mensajeOk = $idHdd > 0
    ? 'HDD actualizado correctamente.'
    : 'HDD instalado correctamente.';

hdd_responder(true, $mensajeOk, 200);
