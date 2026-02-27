<?php
// modules/inventario_mtc/_empresa_target.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

/**
 * Roles con permiso de ver/editar múltiples empresas en Inventario MTC
 * - Desarrollo (1)
 * - Control (2)
 * - Gerente (6)
 */
function invmtc_multi_role_ids() {
  return [1, 2, 6];
}

/** true si el usuario puede seleccionar cualquier empresa (multi-empresa) */
function invmtc_can_multi_empresas() {
  // acl_can_ids ya incluye llave maestra para Desarrollo (id=1)
  return acl_can_ids(invmtc_multi_role_ids());
}

/**
 * Obtiene empresa objetivo (target) para el módulo.
 * - Por defecto: empresa de sesión
 * - Si tiene permiso multi y viene empresa_id por GET/POST: usa esa
 *
 * @param mysqli $mysqli
 * @param bool $asJsonError  Si true, responde JSON de error; si false, salida texto.
 * @return array ['id'=>int,'nombre'=>string,'is_multi'=>bool,'sess_id'=>int]
 */
function invmtc_require_empresa_target($mysqli, $asJsonError = false) {
  $u = currentUser();
  $sessId = (int)($u['empresa']['id'] ?? 0);

  if ($sessId <= 0) {
    if ($asJsonError) {
      header('Content-Type: application/json; charset=utf-8');
      http_response_code(403);
      echo json_encode(['ok' => false, 'msg' => 'Empresa no asignada']);
      exit;
    }
    http_response_code(403);
    exit('Empresa no asignada');
  }

  $isMulti = invmtc_can_multi_empresas();

  $reqId = 0;
  if (isset($_POST['empresa_id'])) $reqId = (int)$_POST['empresa_id'];
  else if (isset($_GET['empresa_id'])) $reqId = (int)$_GET['empresa_id'];

  $targetId = $sessId;
  if ($isMulti && $reqId > 0) $targetId = $reqId;

  // Validar empresa en BD
  $st = $mysqli->prepare("SELECT id, nombre FROM mtp_empresas WHERE id=? LIMIT 1");
  $st->bind_param('i', $targetId);
  $st->execute();
  $emp = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$emp) {
    if ($asJsonError) {
      header('Content-Type: application/json; charset=utf-8');
      http_response_code(404);
      echo json_encode(['ok' => false, 'msg' => 'Empresa no encontrada']);
      exit;
    }
    http_response_code(404);
    exit('Empresa no encontrada');
  }

  return [
    'id' => (int)$emp['id'],
    'nombre' => (string)$emp['nombre'],
    'is_multi' => (bool)$isMulti,
    'sess_id' => (int)$sessId,
  ];
}
