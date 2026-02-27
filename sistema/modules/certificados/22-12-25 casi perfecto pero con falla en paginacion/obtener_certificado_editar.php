<?php
// /modules/certificados/obtener_certificado_editar.php

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

// Preparar datos para el formulario de edición
$cert = [
    'id'                     => (int)$row['id'],
    'codigo_certificado'     => (string)$row['codigo_certificado'],
    'id_curso'               => (int)$row['id_curso'],
    'id_plantilla_certificado'=> (int)$row['id_plantilla_certificado'],
    'id_tipo_doc'            => (int)$row['id_tipo_doc'],
    'id_categoria_licencia'  => $row['id_categoria_licencia'] !== null ? (int)$row['id_categoria_licencia'] : '',
    'nombres_cliente'        => (string)$row['nombres_cliente'],
    'apellidos_cliente'      => (string)$row['apellidos_cliente'],
    'nombre_cliente'         => trim((string)$row['nombres_cliente'] . ' ' . (string)$row['apellidos_cliente']),
    'documento_cliente'      => (string)$row['documento_cliente'],
    'fecha_emision'          => $row['fecha_emision'] ?: '',
    'fecha_inicio'           => $row['fecha_inicio'] ?: '',
    'fecha_fin'              => $row['fecha_fin'] ?: '',
    'horas_teoricas'         => (int)$row['horas_teoricas'],
    'horas_practicas'        => (int)$row['horas_practicas'],
    'estado'                 => (string)$row['estado'],
    'codigo_qr'              => (string)$row['codigo_qr'],
    'empresa_nombre'         => (string)($row['empresa_nombre'] ?? ''),
    'usuario_emisor'         => trim((string)($row['usuario_nombres'] ?? '') . ' ' . (string)($row['usuario_apellidos'] ?? '')),
    'nombre_curso'           => (string)($row['nombre_curso'] ?? ''),
    'tipo_doc_codigo'        => (string)($row['tipo_doc_codigo'] ?? ''),
    'categoria_codigo'       => (string)($row['categoria_codigo'] ?? ''),
    'creado'                 => '',
    'actualizado'            => '',
];

if (!empty($row['creado'])) {
    $dtC = new DateTime($row['creado']);
    $cert['creado'] = $dtC->format('d/m/Y H:i:s');
}
if (!empty($row['actualizado'])) {
    $dtA = new DateTime($row['actualizado']);
    $cert['actualizado'] = $dtA->format('d/m/Y H:i:s');
}

// Opciones para selects
$datosForm = cf_cargar_datos_formulario($idEmpresa);

echo json_encode([
    'ok'          => true,
    'certificado' => $cert,
    'opciones'    => [
        'cursos'     => $datosForm['cursos'],
        'plantillas' => $datosForm['plantillas'],
        'tipos_doc'  => $datosForm['tipos_doc'],
        'categorias' => $datosForm['categorias'],
    ],
]);
