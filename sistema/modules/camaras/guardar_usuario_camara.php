<?php
// modules/camaras/guardar_usuario_camara.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function cam_user_responder($ok, $msg, $httpCode = 200)
{
    global $isAjax;
    http_response_code($httpCode);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'ok'      => (bool)$ok,
            'message' => (string)$msg
        ));
        exit;
    }

    $params = array('msg' => $msg);
    if (!$ok) {
        $params['e'] = 1;
    }
    header('Location: index.php?' . http_build_query($params));
    exit;
}

// Solo Gerente y Desarrollo pueden crear/editar usuarios de cámara
if (!($esDesarrollo || $esGerente)) {
    cam_user_responder(false, 'No tienes permiso para gestionar usuarios de cámara.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cam_user_responder(false, 'Método no permitido.', 405);
}

$idUsuarioCamara = isset($_POST['id_usuario_camara']) ? (int)$_POST['id_usuario_camara'] : 0;
$idCamara        = isset($_POST['id_camara']) ? (int)$_POST['id_camara'] : 0;
$usuario         = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$contrasena      = isset($_POST['contrasena']) ? trim($_POST['contrasena']) : '';
$nota            = isset($_POST['nota']) ? trim($_POST['nota']) : '';

$errores = array();

if ($idCamara <= 0) {
    $errores[] = 'Cámara no válida.';
}
if ($usuario === '') {
    $errores[] = 'El usuario es obligatorio.';
}
if ($contrasena === '') {
    $errores[] = 'La contraseña es obligatoria.';
}
if (mb_strlen($usuario, 'UTF-8') > 100) {
    $errores[] = 'El usuario es demasiado largo.';
}
if (mb_strlen($contrasena, 'UTF-8') > 255) {
    $errores[] = 'La contraseña es demasiado larga.';
}
if (mb_strlen($nota, 'UTF-8') > 255) {
    $errores[] = 'La nota es demasiado larga.';
}

if (!empty($errores)) {
    cam_user_responder(false, implode(' ', $errores), 200);
}

// Verificar que la cámara exista
$sqlCam = "SELECT id FROM cam_camaras WHERE id = ? LIMIT 1";
$stmtCam = mysqli_prepare($cn, $sqlCam);
if (!$stmtCam) {
    cam_user_responder(false, 'Error al validar la cámara.', 500);
}
mysqli_stmt_bind_param($stmtCam, 'i', $idCamara);
mysqli_stmt_execute($stmtCam);
$resCam = mysqli_stmt_get_result($stmtCam);
$camaraExiste = (bool)mysqli_fetch_assoc($resCam);
mysqli_free_result($resCam);
mysqli_stmt_close($stmtCam);

if (!$camaraExiste) {
    cam_user_responder(false, 'La cámara indicada no existe.', 200);
}

if ($idUsuarioCamara > 0) {
    // Actualizar
    $sql = "
        UPDATE cam_camaras_usuarios
        SET usuario = ?, contrasena = ?, nota = ?
        WHERE id = ? AND id_camara = ?
    ";
    $stmt = mysqli_prepare($cn, $sql);
    if (!$stmt) {
        cam_user_responder(false, 'Error al preparar la actualización.', 500);
    }
    mysqli_stmt_bind_param(
        $stmt,
        'sssii',
        $usuario,
        $contrasena,
        $nota,
        $idUsuarioCamara,
        $idCamara
    );
} else {
    // Crear
    $sql = "
        INSERT INTO cam_camaras_usuarios (id_camara, usuario, contrasena, nota)
        VALUES (?, ?, ?, ?)
    ";
    $stmt = mysqli_prepare($cn, $sql);
    if (!$stmt) {
        cam_user_responder(false, 'Error al preparar el guardado.', 500);
    }
    mysqli_stmt_bind_param(
        $stmt,
        'isss',
        $idCamara,
        $usuario,
        $contrasena,
        $nota
    );
}

if (!mysqli_stmt_execute($stmt)) {
    $msg = 'Error al guardar el usuario de cámara.';
    if (mysqli_errno($cn) == 1062) {
        $msg = 'Ya existe un usuario con ese nombre para esta cámara.';
    }
    mysqli_stmt_close($stmt);
    cam_user_responder(false, $msg, 200);
}

mysqli_stmt_close($stmt);

if ($idUsuarioCamara > 0) {
    cam_user_responder(true, 'Usuario de cámara actualizado correctamente.', 200);
} else {
    cam_user_responder(true, 'Usuario de cámara registrado correctamente.', 200);
}
