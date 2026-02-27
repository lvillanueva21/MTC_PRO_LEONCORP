<?php
// modules/camaras/eliminar_camara.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function cam_del_responder($ok, $msg)
{
    global $isAjax;

    if ($isAjax) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => (bool)$ok, 'message' => (string)$msg));
        exit;
    }

    $params = array('msg' => $msg);
    if (!$ok) $params['e'] = 1;
    header('Location: index.php?' . http_build_query($params));
    exit;
}

try {
    if (!($esDesarrollo || $esGerente)) {
        cam_del_responder(false, 'No tienes permiso para eliminar cámaras.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        cam_del_responder(false, 'Método no permitido.');
    }

    $cam_id = isset($_POST['cam_id']) ? (int)$_POST['cam_id'] : 0;
    if ($cam_id <= 0) {
        cam_del_responder(false, 'Cámara no válida.');
    }

    $sql = "DELETE FROM cam_camaras WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($cn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $cam_id);
    mysqli_stmt_execute($stmt);
    $afectadas = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    cam_del_responder(($afectadas > 0), ($afectadas > 0) ? 'Cámara eliminada correctamente.' : 'No se encontró la cámara indicada.');

} catch (Throwable $e) {
    error_log('[CAMARAS] eliminar_camara.php: ' . $e->getMessage());
    cam_del_responder(false, 'No se pudo eliminar la cámara. Inténtalo nuevamente.');
}
