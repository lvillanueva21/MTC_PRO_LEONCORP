<?php
// modules/camaras/eliminar_hdd.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
          && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function hdd_del_responder($ok, $msg, $httpCode = 200)
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

// Solo Desarrollo y Gerente pueden eliminar HDD
if (!($esDesarrollo || $esGerente)) {
    hdd_del_responder(false, 'No tienes permiso para eliminar HDD.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hdd_del_responder(false, 'Método no permitido.', 405);
}

$idHdd = isset($_POST['id_hdd']) ? (int)$_POST['id_hdd'] : 0;

if ($idHdd <= 0) {
    hdd_del_responder(false, 'HDD no válido.', 200);
}

// Eliminar HDD (con ON DELETE CASCADE se van también los consumos)
$sqlDel = "DELETE FROM cam_hdd WHERE id = ? LIMIT 1";
$stmtDel = mysqli_prepare($cn, $sqlDel);
if (!$stmtDel) {
    hdd_del_responder(false, 'Error al preparar la eliminación del HDD.', 500);
}
mysqli_stmt_bind_param($stmtDel, 'i', $idHdd);

if (!mysqli_stmt_execute($stmtDel)) {
    mysqli_stmt_close($stmtDel);
    hdd_del_responder(false, 'Error al eliminar el HDD.', 200);
}
$aff = mysqli_stmt_affected_rows($stmtDel);
mysqli_stmt_close($stmtDel);

if ($aff <= 0) {
    hdd_del_responder(false, 'No se encontró el HDD indicado.', 200);
}

hdd_del_responder(true, 'HDD eliminado correctamente.', 200);
