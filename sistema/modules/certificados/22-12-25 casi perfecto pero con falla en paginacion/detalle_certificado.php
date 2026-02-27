<?php
// /modules/certificados/detalle_certificado.php

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/funciones_formulario.php';

acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

header('Content-Type: application/json; charset=utf-8');

$u = currentUser();

try {
    $idEmpresa = cf_resolver_id_empresa_actual($u);
} catch (Throwable $e) {
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'No se pudo determinar la empresa actual.'],
    ]);
    exit;
}

$idCert = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idCert <= 0) {
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'Certificado no válido.'],
    ]);
    exit;
}

$db = db();

// Traer datos del certificado + joins
$sql = "SELECT
            c.*,
            cu.nombre AS nombre_curso,
            tdoc.codigo AS tipo_doc_codigo,
            cat.codigo AS categoria_codigo,
            e.nombre AS empresa_nombre,
            u.nombres AS usuario_nombres,
            u.apellidos AS usuario_apellidos
        FROM cq_certificados c
        LEFT JOIN cr_cursos cu ON cu.id = c.id_curso
        LEFT JOIN cq_tipos_documento tdoc ON tdoc.id = c.id_tipo_doc
        LEFT JOIN cq_categorias_licencia cat ON cat.id = c.id_categoria_licencia
        LEFT JOIN mtp_empresas e ON e.id = c.id_empresa
        LEFT JOIN mtp_usuarios u ON u.id = c.id_usuario_emisor
        WHERE c.id = ? AND c.id_empresa = ?";

$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $idCert, $idEmpresa);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'No se encontró el certificado.'],
    ]);
    exit;
}

// Datos base
$codigoCert     = (string)$row['codigo_certificado'];
$nombresCli     = (string)$row['nombres_cliente'];
$apellidosCli   = (string)$row['apellidos_cliente'];
$nombreCliFull  = trim($nombresCli . ' ' . $apellidosCli);
$documento      = (string)$row['documento_cliente'];
$nombreCurso    = (string)$row['nombre_curso'];
$fechaEmision   = $row['fecha_emision'];
$fechaInicio    = $row['fecha_inicio'];
$fechaFin       = $row['fecha_fin'];
$horasTeor      = (int)$row['horas_teoricas'];
$horasPrac      = (int)$row['horas_practicas'];
$codigoQr       = (string)$row['codigo_qr'];
$estadoBd       = (string)$row['estado'];
$idCategoria    = $row['id_categoria_licencia'];
$tipoDocCodigo  = (string)($row['tipo_doc_codigo'] ?? '');
$categoriaCod   = (string)($row['categoria_codigo'] ?? '');
$empresaNombre  = (string)($row['empresa_nombre'] ?? '');
$usuarioNombre  = trim((string)($row['usuario_nombres'] ?? '') . ' ' . (string)($row['usuario_apellidos'] ?? ''));

// Fechas DateTime
$dtEmision = $fechaEmision ? cf_parse_fecha_esp($fechaEmision) : null;
$dtInicio  = $fechaInicio ? cf_parse_fecha_esp($fechaInicio) : null;
$dtFin     = $fechaFin ? cf_parse_fecha_esp($fechaFin) : null;

// Formateo para mostrar
$fechaEmisionMostrar = ($dtEmision instanceof DateTime)
    ? cf_formatear_fecha_esp($dtEmision)
    : ($fechaEmision ?: '');

$fechaInicioMostrar = ($dtInicio instanceof DateTime)
    ? cf_formatear_fecha_esp($dtInicio)
    : ($fechaInicio ?: '');

$fechaFinMostrar = ($dtFin instanceof DateTime)
    ? cf_formatear_fecha_esp($dtFin)
    : ($fechaFin ?: '');

// creado / actualizado
$creadoFmt      = '';
$actualizadoFmt = '';
if (!empty($row['creado'])) {
    $dtC = new DateTime($row['creado']);
    $creadoFmt = $dtC->format('d/m/Y H:i:s');
}
if (!empty($row['actualizado'])) {
    $dtA = new DateTime($row['actualizado']);
    $actualizadoFmt = $dtA->format('d/m/Y H:i:s');
}

// Estado mostrado (igual lógica que en guardar_certificado.php)
$estadoMostrar = $estadoBd ?: 'Activo';
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
        }
    }
}

// Resumen igual estructura que el de guardar_certificado.php
$resumen = [
    'codigo_certificado' => $codigoCert,
    'nombre_cliente'     => $nombreCliFull,
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
    'categoria'          => $categoriaCod,
    'tipo_doc'           => $tipoDocCodigo,
    'codigo_qr'          => $codigoQr,
];

echo json_encode([
    'ok'      => true,
    'resumen' => $resumen,
]);
