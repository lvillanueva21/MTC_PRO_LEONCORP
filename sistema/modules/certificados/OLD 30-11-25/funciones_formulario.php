<?php
// /modules/certificados/funciones_formulario.php

// Bloquear acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

/**
 * Usa la conexión global mysqli que expone includes/conexion.php vía db()
 * No se vuelve a incluir conexion.php aquí.
 */

/**
 * Resuelve el id de empresa del usuario actual a partir del array $u.
 *
 * Prioridad:
 * 1) $u['empresa']['id'] si existe.
 * 2) Consulta mtp_usuarios por $u['id'].
 * 3) Consulta mtp_usuarios por $u['usuario'].
 */
function cf_resolver_id_empresa_actual(array $u): int
{
    if (isset($u['empresa']['id']) && (int)$u['empresa']['id'] > 0) {
        return (int)$u['empresa']['id'];
    }

    $db = db();

    if (isset($u['id'])) {
        $idUsuario = (int)$u['id'];
        $sql = "SELECT id_empresa FROM mtp_usuarios WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $idUsuario);
    } elseif (isset($u['usuario'])) {
        $usuario = (string)$u['usuario'];
        $sql = "SELECT id_empresa FROM mtp_usuarios WHERE usuario = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $usuario);
    } else {
        die('Error: no se pudo determinar la empresa del usuario actual (falta id o usuario).');
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row || !isset($row['id_empresa'])) {
        die('Error: no se encontró la empresa del usuario actual en la base de datos.');
    }

    return (int)$row['id_empresa'];
}

/**
 * Resuelve el id del usuario actual a partir de $u.
 */
function cf_resolver_id_usuario_actual(array $u): int
{
    if (isset($u['id']) && (int)$u['id'] > 0) {
        return (int)$u['id'];
    }

    if (!isset($u['usuario'])) {
        die('Error: no se pudo determinar el usuario actual (falta clave usuario).');
    }

    $db      = db();
    $usuario = (string)$u['usuario'];

    $sql  = "SELECT id FROM mtp_usuarios WHERE usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row || !isset($row['id'])) {
        die('Error: no se encontró el id del usuario actual en la base de datos.');
    }

    return (int)$row['id'];
}

/**
 * Devuelve cursos activos.
 * Columnas usadas: id, nombre.
 */
function cf_cargar_cursos_activos(): array
{
    $db  = db();
    $sql = "SELECT id, nombre 
            FROM cr_cursos 
            WHERE activo = 1 
            ORDER BY nombre ASC";

    $res   = $db->query($sql);
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $res->free();

    return $items;
}

/**
 * Devuelve plantillas activas por empresa.
 * Columnas usadas: id, nombre.
 */
function cf_cargar_plantillas_activas_por_empresa(int $idEmpresa): array
{
    $db  = db();
    $sql = "SELECT id, nombre
            FROM cq_plantillas_certificados
            WHERE id_empresa = ? AND activo = 1
            ORDER BY nombre ASC";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $idEmpresa);
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();
    return $items;
}

/**
 * Devuelve tipos de documento (DNI, BREVETE, CE, ...).
 */
function cf_cargar_tipos_documento(): array
{
    $db  = db();
    $sql = "SELECT id, codigo
            FROM cq_tipos_documento
            ORDER BY id ASC";

    $res   = $db->query($sql);
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $res->free();

    return $items;
}

/**
 * Devuelve categorías de licencia (A-I, A-IIa, ...).
 */
function cf_cargar_categorias_licencia(): array
{
    $db  = db();
    $sql = "SELECT id, codigo, tipo_categoria
            FROM cq_categorias_licencia
            ORDER BY tipo_categoria ASC, id ASC";

    $res   = $db->query($sql);
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $res->free();

    return $items;
}

/**
 * Obtiene el siguiente correlativo para una empresa:
 * MAX(correlativo_empresa) + 1, o 1 si no hay registros.
 */
function cf_obtener_siguiente_correlativo(int $idEmpresa): int
{
    $db  = db();
    $sql = "SELECT MAX(correlativo_empresa) AS max_corr
            FROM cq_certificados
            WHERE id_empresa = ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $idEmpresa);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $max = 0;
    if ($row && $row['max_corr'] !== null) {
        $max = (int)$row['max_corr'];
    }

    return $max + 1;
}

/**
 * Formatea el correlativo en un código legible tipo '000123'.
 */
function cf_formatear_codigo_certificado(int $correlativo): string
{
    // 6 dígitos con ceros a la izquierda (ajustable)
    return str_pad((string)$correlativo, 6, '0', STR_PAD_LEFT);
}

/**
 * Devuelve el texto que se muestra en el formulario al lado del título:
 * ej: '#000123'
 */
function cf_obtener_siguiente_codigo_certificado(int $idEmpresa): string
{
    $corr  = cf_obtener_siguiente_correlativo($idEmpresa);
    $codigo = cf_formatear_codigo_certificado($corr);
    return '#' . $codigo;
}

/**
 * Carga todos los datos necesarios para poblar el formulario:
 * - cursos
 * - plantillas (por empresa)
 * - tipos de documento
 * - categorías de licencia
 * - siguiente código de certificado (solo display)
 */
function cf_cargar_datos_formulario(int $idEmpresa): array
{
    return [
        'cursos'          => cf_cargar_cursos_activos(),
        'plantillas'      => cf_cargar_plantillas_activas_por_empresa($idEmpresa),
        'tipos_doc'       => cf_cargar_tipos_documento(),
        'categorias'      => cf_cargar_categorias_licencia(),
        'siguiente_codigo'=> cf_obtener_siguiente_codigo_certificado($idEmpresa),
    ];
}

/**
 * Convierte 'd/m/Y' a 'Y-m-d' devolviendo DateTime o false si no es válida.
 */
function cf_parse_fecha_esp(string $str)
{
    $str = trim($str);
    if ($str === '') {
        return null;
    }

    // 1) Intentar formato mostrado al usuario: d/m/Y
    $dt = DateTime::createFromFormat('d/m/Y', $str);
    if ($dt && $dt->format('d/m/Y') === $str) {
        return $dt;
    }

    // 2) Intentar formato de input type="date": Y-m-d
    $dt2 = DateTime::createFromFormat('Y-m-d', $str);
    if ($dt2 && $dt2->format('Y-m-d') === $str) {
        return $dt2;
    }

    return false;
}

/**
 * Formatea DateTime a 'd/m/Y'.
 */
function cf_formatear_fecha_esp(DateTime $dt): string
{
    return $dt->format('d/m/Y');
}
