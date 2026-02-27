<?php
// /modules/certificados/desactivar_certificado.php

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/funciones_formulario.php';

acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Obtener conexión
if (!function_exists('db')) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Función de conexión no disponible.'
    ]);
    exit;
}

$db = db();
if (!$db instanceof mysqli) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Conexión a base de datos no disponible.'
    ]);
    exit;
}

// Usuario y empresa
$u = currentUser();
$idEmpresa = 0;
if (function_exists('cf_resolver_id_empresa_actual')) {
    $idEmpresa = (int)cf_resolver_id_empresa_actual($u);
} elseif (isset($u['empresa']['id'])) {
    $idEmpresa = (int)$u['empresa']['id'];
}

if ($idEmpresa <= 0) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No se pudo determinar la empresa actual.'
    ]);
    exit;
}

// Parámetro de entrada
$idCert = isset($_POST['id_certificado']) ? (int)$_POST['id_certificado'] : 0;
if ($idCert <= 0) {
    echo json_encode([
        'ok'    => false,
        'error' => 'ID de certificado no válido.'
    ]);
    exit;
}

// Verificar que el certificado exista y pertenezca a la empresa
$sqlSel = "SELECT id, estado
           FROM cq_certificados
           WHERE id = ? AND id_empresa = ?
           LIMIT 1";

$stmtSel = $db->prepare($sqlSel);
if (!$stmtSel) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No se pudo preparar la consulta de verificación.'
    ]);
    exit;
}

$stmtSel->bind_param('ii', $idCert, $idEmpresa);
$stmtSel->execute();
$resSel = $stmtSel->get_result();
$rowSel = $resSel ? $resSel->fetch_assoc() : null;

if ($resSel instanceof mysqli_result) {
    $resSel->free();
}
$stmtSel->close();

if (!$rowSel) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Certificado no encontrado para esta empresa.'
    ]);
    exit;
}

$estadoActual       = isset($rowSel['estado']) ? (string)$rowSel['estado'] : '';
$estadoActualUpper  = strtoupper($estadoActual);

// No permitir cambios sobre certificados vencidos
if ($estadoActualUpper === 'VENCIDO') {
    echo json_encode([
        'ok'    => false,
        'error' => 'No se puede cambiar el estado de un certificado vencido.'
    ]);
    exit;
}

// Determinar nuevo estado (toggle)
// - Si está Inactivo -> pasa a Activo
// - En cualquier otro caso (Activo u otro) -> pasa a Inactivo
$nuevoEstado  = '';
$mensajeExito = '';

if ($estadoActualUpper === 'INACTIVO') {
    $nuevoEstado  = 'Activo';
    $mensajeExito = 'Certificado activado correctamente.';
} else {
    $nuevoEstado  = 'Inactivo';
    $mensajeExito = 'Certificado desactivado correctamente.';
}

// Actualizar estado
$sqlUpd = "UPDATE cq_certificados
           SET estado = ?, actualizado = NOW()
           WHERE id = ? AND id_empresa = ?
           LIMIT 1";

$stmtUpd = $db->prepare($sqlUpd);
if (!$stmtUpd) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No se pudo preparar la actualización.'
    ]);
    exit;
}

$stmtUpd->bind_param('sii', $nuevoEstado, $idCert, $idEmpresa);
$okExec = $stmtUpd->execute();
$stmtUpd->close();

if (!$okExec) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No se pudo actualizar el estado del certificado.'
    ]);
    exit;
}

echo json_encode([
    'ok'          => true,
    'msg'         => $mensajeExito,
    'nuevoEstado' => $nuevoEstado
]);
exit;