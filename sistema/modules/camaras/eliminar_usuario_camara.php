<?php
// modules/camaras/eliminar_usuario_camara.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function cam_user_del_responder($ok, $msg, $httpCode = 200)
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

// Solo Gerente y Desarrollo pueden eliminar usuarios de cámara
if (!($esDesarrollo || $esGerente)) {
    cam_user_del_responder(false, 'No tienes permiso para eliminar usuarios de cámara.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cam_user_del_responder(false, 'Método no permitido.', 405);
}

$idUsuarioCamara = isset($_POST['id_usuario_camara']) ? (int)$_POST['id_usuario_camara'] : 0;
if ($idUsuarioCamara <= 0) {
    cam_user_del_responder(false, 'Usuario de cámara no válido.', 200);
}

$sql = "DELETE FROM cam_camaras_usuarios WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($cn, $sql);
if (!$stmt) {
    cam_user_del_responder(false, 'Error al preparar la eliminación.', 500);
}

mysqli_stmt_bind_param($stmt, 'i', $idUsuarioCamara);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    cam_user_del_responder(false, 'Error al eliminar el usuario de cámara.', 200);
}

$afectadas = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($afectadas > 0) {
    cam_user_del_responder(true, 'Usuario de cámara eliminado correctamente.', 200);
} else {
    cam_user_del_responder(false, 'No se encontró el usuario de cámara indicado.', 200);
}
