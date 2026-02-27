<?php
// Bloquear acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

require_once __DIR__ . '/conexion.php';

/**
 * Inicia una sesión segura con parámetros configurados
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_name('sc_sess');
        session_start();
    }
}

start_secure_session();

/**
 * Login de usuario
 */
function login(string $usuario, string $clave): array
{
    $sql = "
        SELECT u.id, u.usuario, u.clave,
               u.nombres, u.apellidos,
               e.id AS empresa_id, e.nombre AS empresa_nombre,
               e.id_depa, d.nombre AS depa_nombre
        FROM mtp_usuarios u
        JOIN mtp_empresas e ON e.id = u.id_empresa
        LEFT JOIN mtp_departamentos d ON d.id = e.id_depa
        WHERE u.usuario = ?
    ";

    $st = db()->prepare($sql);
    $st->bind_param('s', $usuario);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc();

    if (!$row || !password_verify($clave, $row['clave'])) {
        return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
    }

    // Obtener roles (id y nombre) usando mysqli para ser consistente
    $st2 = db()->prepare("
        SELECT r.id, r.nombre
        FROM mtp_usuario_roles ur
        JOIN mtp_roles r ON r.id = ur.id_rol
        WHERE ur.id_usuario = ?
        ORDER BY r.nombre
    ");
    $st2->bind_param('i', $row['id']);
    $st2->execute();
    $res2 = $st2->get_result();
    $roleRows = $res2->fetch_all(MYSQLI_ASSOC);

    $roles      = array_column($roleRows, 'nombre');
    $roles_ids  = array_map('intval', array_column($roleRows, 'id'));

    if (!$roles) {
        return ['ok' => false, 'error' => 'El usuario no tiene roles asignados.'];
    }

    $rol_activo     = $roles[0]     ?? null;
    $rol_activo_id  = $roles_ids[0] ?? null;

    // Regenerar sesión
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'         => (int)$row['id'],
        'usuario'    => $row['usuario'],
        'nombres'    => $row['nombres'],
        'apellidos'  => $row['apellidos'],
        'empresa'    => [
            'id'     => (int)$row['empresa_id'],
            'nombre' => $row['empresa_nombre'],
            'depa'   => [
                'id'     => isset($row['id_depa']) ? (int)$row['id_depa'] : null,
                'nombre' => $row['depa_nombre'] ?? ''
            ]
        ],
        // 👉 Nuevos datos en sesión
        'roles'         => $roles,
        'roles_ids'     => $roles_ids,
        'rol_activo'    => $rol_activo,
        'rol_activo_id' => $rol_activo_id,

        'logged_at'  => date('Y-m-d H:i:s')
    ];

    return ['ok' => true];
}

/**
 * Cierra sesión
 */
function logout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            (time() - 42000),
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Verifica si hay usuario logueado
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user']['id']);
}

/**
 * Redirige al login si no hay sesión
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        header('Location: ' . BASE_URL . '/login.php?m=sesion');
        exit;
    }
}

/**
 * Devuelve el usuario actual
 */
function currentUser(): array
{
    return $_SESSION['user'] ?? [];
}

/**
 * Cambia de rol activo (actualiza también el ID del rol activo)
 */
function switchRole(string $rol): bool
{
    if (!isAuthenticated()) return false;

    $names = $_SESSION['user']['roles']      ?? [];
    $ids   = $_SESSION['user']['roles_ids']  ?? [];

    $idx = array_search($rol, $names, true);
    if ($idx === false) return false;

    $_SESSION['user']['rol_activo']    = $rol;
    $_SESSION['user']['rol_activo_id'] = isset($ids[$idx]) ? (int)$ids[$idx] : null;

    return true;
}
