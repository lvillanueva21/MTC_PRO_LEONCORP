<?php
// modules/camaras/guardar_camara.php
require_once __DIR__ . '/_bootstrap.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function cam_responder($ok, $msg)
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
        cam_responder(false, 'No tienes permiso para gestionar cámaras.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        cam_responder(false, 'Método no permitido.');
    }

    $cam_id       = isset($_POST['cam_id']) ? (int)$_POST['cam_id'] : 0;
    $empresa_id   = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;
    $nombre       = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $link_externo = isset($_POST['link_externo']) ? trim($_POST['link_externo']) : '';
    $link_local   = isset($_POST['link_local']) ? trim($_POST['link_local']) : '';
    $color_bg     = isset($_POST['color_bg']) ? trim($_POST['color_bg']) : '';
    $color_text   = isset($_POST['color_text']) ? trim($_POST['color_text']) : '';

    $errores = array();
    if ($empresa_id <= 0) $errores[] = 'Debes seleccionar una empresa.';
    if ($nombre === '') $errores[] = 'El nombre de la cámara es obligatorio.';
    if ($link_externo === '' && $link_local === '') $errores[] = 'Debes ingresar al menos un link (externo o local).';
    if ($color_bg === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color_bg)) $errores[] = 'El color de fondo del botón es inválido.';
    if ($color_text === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color_text)) $errores[] = 'El color de texto del botón es inválido.';
    if (!empty($errores)) cam_responder(false, implode(' ', $errores));

    if ($cam_id > 0) {
        $sql = "
            UPDATE cam_camaras
            SET id_empresa=?, nombre=?, link_externo=?, link_local=?, color_bg=?, color_text=?
            WHERE id=?
        ";
        $stmt = mysqli_prepare($cn, $sql);
        mysqli_stmt_bind_param($stmt, 'isssssi', $empresa_id, $nombre, $link_externo, $link_local, $color_bg, $color_text, $cam_id);
    } else {
        $sql = "
            INSERT INTO cam_camaras (id_empresa, nombre, link_externo, link_local, color_bg, color_text)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = mysqli_prepare($cn, $sql);
        mysqli_stmt_bind_param($stmt, 'isssss', $empresa_id, $nombre, $link_externo, $link_local, $color_bg, $color_text);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    cam_responder(true, ($cam_id > 0) ? 'Cámara actualizada correctamente.' : 'Cámara registrada correctamente.');

} catch (Throwable $e) {
    error_log('[CAMARAS] guardar_camara.php: ' . $e->getMessage());

    $msg = 'Error al guardar la cámara.';
    if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) {
        $msg = 'Ya existe una cámara con ese nombre para la empresa seleccionada.';
    }
    cam_responder(false, $msg);
}
