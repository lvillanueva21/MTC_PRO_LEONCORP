<?php
// /modules/certificados/detalle_certificado.php
//
// Devuelve en JSON el resumen de un certificado ya emitido,
// para reutilizar el mismo modal de detalle + QR.

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

$db = db();
$u  = currentUser();

try {
    $idEmpresa = cf_resolver_id_empresa_actual($u);
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'No se pudo determinar la empresa actual.',
    ]);
    exit;
}

$idCertificado = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idCertificado <= 0) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Identificador de certificado inválido.',
    ]);
    exit;
}

// Traer todos los datos necesarios del certificado
$sql = "SELECT
            c.*,
            cu.nombre                AS nombre_curso,
            emp.nombre               AS empresa_nombre,
            ue.nombres               AS emisor_nombres,
            ue.apellidos             AS emisor_apellidos,
            cat.codigo               AS categoria_codigo,
            td.codigo                AS tipo_doc_codigo
        FROM cq_certificados c
        INNER JOIN cr_cursos cu
            ON cu.id = c.id_curso
        LEFT JOIN mtp_empresas emp
            ON emp.id = c.id_empresa
        LEFT JOIN mtp_usuarios ue
            ON ue.id = c.id_usuario_emisor
        LEFT JOIN cq_categorias_licencia cat
            ON cat.id = c.id_categoria_licencia
        LEFT JOIN cq_tipos_documento td
            ON td.id = c.id_tipo_doc
        WHERE c.id = ?
          AND c.id_empresa = ?
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $idCertificado, $idEmpresa);
$stmt->execute();
$res  = $stmt->get_result();
$row  = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Certificado no encontrado.',
    ]);
    exit;
}

// Fechas (guardadas en Y-m-d)
$dtEmision = null;
$dtInicio  = null;
$dtFin     = null;

if (!empty($row['fecha_emision'])) {
    $dtEmision = new DateTime($row['fecha_emision']);
}
if (!empty($row['fecha_inicio'])) {
    $dtInicio = new DateTime($row['fecha_inicio']);
}
if (!empty($row['fecha_fin'])) {
    $dtFin = new DateTime($row['fecha_fin']);
}

$fechaEmisionMostrar = $dtEmision instanceof DateTime
    ? cf_formatear_fecha_esp($dtEmision)
    : '';

$fechaInicioMostrar = $dtInicio instanceof DateTime
    ? cf_formatear_fecha_esp($dtInicio)
    : '';

$fechaFinMostrar = $dtFin instanceof DateTime
    ? cf_formatear_fecha_esp($dtFin)
    : '';

// Horas
$horasTeor = (int)$row['horas_teoricas'];
$horasPrac = (int)$row['horas_practicas'];

// Empresa y emisor
$empresaNombre = isset($row['empresa_nombre']) ? (string)$row['empresa_nombre'] : '';
$usuarioNombre = trim(
    (string)($row['emisor_nombres'] ?? '') . ' ' .
    (string)($row['emisor_apellidos'] ?? '')
);

// Creado / actualizado
$creadoFmt      = '';
$actualizadoFmt = '';

if (!empty($row['creado'])) {
    $dtCreado  = new DateTime($row['creado']);
    $creadoFmt = $dtCreado->format('d/m/Y H:i:s');
}
if (!empty($row['actualizado'])) {
    $dtActualizado  = new DateTime($row['actualizado']);
    $actualizadoFmt = $dtActualizado->format('d/m/Y H:i:s');
}

// Estado BD y estado mostrado (vencimiento por 1 año)
$estadoBd = isset($row['estado']) && $row['estado'] !== ''
    ? (string)$row['estado']
    : 'Activo';

$estadoMostrar = $estadoBd;
if ($estadoBd !== 'Inactivo') {
    if ($estadoBd === 'Vencido') {
        $estadoMostrar = 'Vencido';
    } else {
        if ($dtEmision instanceof DateTime) {
            $limite = clone $dtEmision;
            $limite->modify('+1 year');
            $hoy = new DateTime('now');
            if ($hoy >= $limite) {
                $estadoMostrar = 'Vencido';
            } else {
                $estadoMostrar = 'Activo';
            }
        } else {
            $estadoMostrar = $estadoBd !== '' ? $estadoBd : 'Activo';
        }
    }
}

// Otros campos
$codigoCert      = (string)$row['codigo_certificado'];
$nombreCliente   = trim((string)$row['nombres_cliente'] . ' ' . (string)$row['apellidos_cliente']);
$documento       = (string)$row['documento_cliente'];
$nombreCurso     = isset($row['nombre_curso']) ? (string)$row['nombre_curso'] : '';
$categoriaCodigo = isset($row['categoria_codigo']) ? (string)$row['categoria_codigo'] : '';
$codigoTipoDoc   = isset($row['tipo_doc_codigo']) ? (string)$row['tipo_doc_codigo'] : '';
$codigoQr        = isset($row['codigo_qr']) ? (string)$row['codigo_qr'] : '';

// Resumen con las mismas claves que guardar_certificado.php + codigo_qr
$resumen = [
    'codigo_certificado' => $codigoCert,
    'nombre_cliente'     => $nombreCliente,
    'documento_cliente'  => $documento,
    'curso'              => $nombreCurso,
    'fecha_emision'      => $fechaEmisionMostrar,
    'fecha_inicio'       => $fechaInicioMostrar,
    'fecha_fin'          => $fechaFinMostrar,
    'horas_teoricas'     => $horasTeor,
    'horas_practicas'    => $horasPrac,
    'empresa_nombre'     => $empresaNombre,
    'usuario_nombre'     => $usuarioNombre,
    'id_bd'              => (int)$row['id'],
    'creado'             => $creadoFmt,
    'actualizado'        => $actualizadoFmt,
    'estado'             => $estadoMostrar,
    'estado_bd'          => $estadoBd,
    'categoria'          => $categoriaCodigo,
    'tipo_doc'           => $codigoTipoDoc,
    'codigo_qr'          => $codigoQr,
];

echo json_encode([
    'ok'      => true,
    'resumen' => $resumen,
]);
exit;
