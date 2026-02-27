<?php
// Bloquear acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

require_once __DIR__ . '/auth.php';

/**
 * Verifica si el rol ACTIVO tiene permiso para acceder.
 *
 * $rolesPermitidos puede contener nombres de rol (strings) y/o IDs de rol (ints).
 * Ejemplos:
 *   verificarPermiso(['Desarrollo', 'Gerente']);
 *   verificarPermiso([1, 6]);
 *   verificarPermiso(['Gerente', 4]); // mixto
 */
function verificarPermiso(array $rolesPermitidos): void
{
    requireAuth();

    $rolNombre = $_SESSION['user']['rol_activo']     ?? '';
    $rolId     = isset($_SESSION['user']['rol_activo_id']) ? (int)$_SESSION['user']['rol_activo_id'] : null;

    // --- Llave maestra (superusuario de roles) ---
    // Por coherencia con tu BD: "Desarrollo" (y compat: "Desarrollador") y/o ID=1
    if ($rolId === 1 || $rolNombre === 'Desarrollo' || $rolNombre === 'Desarrollador') {
        return;
    }

    // Normaliza listas
    $permitidosPorNombre = [];
    $permitidosPorId     = [];
    foreach ($rolesPermitidos as $r) {
        if (is_int($r))   { $permitidosPorId[] = $r; }
        elseif (is_string($r) && $r !== '') { $permitidosPorNombre[] = $r; }
    }

    // Chequeo por nombre
    if ($permitidosPorNombre && in_array($rolNombre, $permitidosPorNombre, true)) {
        return;
    }

    // Chequeo por ID
    if ($permitidosPorId && $rolId !== null && in_array($rolId, $permitidosPorId, true)) {
        return;
    }

    http_response_code(403);
    exit('Acceso denegado para el rol activo.');
}
