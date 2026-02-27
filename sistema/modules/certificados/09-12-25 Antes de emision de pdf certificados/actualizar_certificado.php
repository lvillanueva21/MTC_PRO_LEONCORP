<?php
// /modules/certificados/actualizar_certificado.php

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
    $idUsuario = cf_resolver_id_usuario_actual($u); // por si quieres auditar luego
} catch (Throwable $e) {
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'No se pudo determinar empresa o usuario actual.'],
    ]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'Método no permitido.'],
    ]);
    exit;
}

$db = db();

function post_trim_edit(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

// Identificador del certificado
$idCertificado = (int)post_trim_edit('id_certificado');

if ($idCertificado <= 0) {
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'Certificado no válido.'],
    ]);
    exit;
}

// Verificar que el certificado pertenezca a la empresa actual
$sql  = "SELECT id, id_empresa FROM cq_certificados WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $idCertificado);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row || (int)$row['id_empresa'] !== $idEmpresa) {
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'No se encontró el certificado para esta empresa.'],
    ]);
    exit;
}

// Leer datos del POST (mismos nombres que en guardar_certificado.php)
$idCurso           = (int)post_trim_edit('curso');
$idPlantilla       = (int)post_trim_edit('tipo_certificado');
$nombres           = post_trim_edit('nombres');
$apellidos         = post_trim_edit('apellidos');
$idTipoDoc         = (int)post_trim_edit('tipo_doc');
$documento         = post_trim_edit('caracteres_doc');
$idCategoria       = post_trim_edit('categoria') === '' ? null : (int)post_trim_edit('categoria');
$fechaEmisionStr   = post_trim_edit('fecha_emision');
$fechaInicioStr    = post_trim_edit('fecha_inicio');
$fechaFinStr       = post_trim_edit('fecha_fin');
$horasTeorStr      = post_trim_edit('horas_teoricas');
$horasPracStr      = post_trim_edit('horas_practicas');
$estadoInput       = post_trim_edit('estado');

/* ==== DEBUG TEMPORAL: QUITAR LUEGO ==== */
file_put_contents(
    __DIR__ . '/debug_fecha_fin.log',
    date('c') .
    " | id_certificado=" . $idCertificado .
    " | fecha_emision=[" . $fechaEmisionStr . "]" .
    " | fecha_inicio=[" . $fechaInicioStr . "]" .
    " | fecha_fin=[" . $fechaFinStr . "]" .
    " | POST=" . json_encode($_POST, JSON_UNESCAPED_UNICODE) .
    PHP_EOL,
    FILE_APPEND
);
/* ==== FIN DEBUG ==== */

$errores = [];

// Curso
if ($idCurso <= 0) {
    $errores['curso'] = 'Seleccione un curso.';
} else {
    $sql  = "SELECT id FROM cr_cursos WHERE id = ? AND activo = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $idCurso);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res->fetch_assoc()) {
        $errores['curso'] = 'El curso seleccionado no es válido.';
    }
    $stmt->close();
}

// Plantilla
if ($idPlantilla <= 0) {
    $errores['tipo_certificado'] = 'Seleccione un tipo de certificado.';
} else {
    $sql  = "SELECT id FROM cq_plantillas_certificados 
             WHERE id = ? AND id_empresa = ? AND activo = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $idPlantilla, $idEmpresa);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res->fetch_assoc()) {
        $errores['tipo_certificado'] = 'El tipo de certificado no es válido para esta empresa.';
    }
    $stmt->close();
}

// Nombres / apellidos
if ($nombres === '') {
    $errores['nombres'] = 'Ingrese los nombres.';
} elseif (mb_strlen($nombres) > 100) {
    $errores['nombres'] = 'Los nombres no deben superar 100 caracteres.';
}

if ($apellidos === '') {
    $errores['apellidos'] = 'Ingrese los apellidos.';
} elseif (mb_strlen($apellidos) > 100) {
    $errores['apellidos'] = 'Los apellidos no deben superar 100 caracteres.';
}

// Tipo doc
$codigoTipoDoc = null;
if ($idTipoDoc <= 0) {
    $errores['tipo_doc'] = 'Seleccione un tipo de documento.';
} else {
    $sql  = "SELECT codigo FROM cq_tipos_documento WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $idTipoDoc);
    $stmt->execute();
    $res = $stmt->get_result();
    $rowDoc = $res->fetch_assoc();
    $stmt->close();

    if (!$rowDoc) {
        $errores['tipo_doc'] = 'El tipo de documento seleccionado no es válido.';
    } else {
        $codigoTipoDoc = (string)$rowDoc['codigo'];
    }
}

// Documento
if ($documento === '') {
    $errores['caracteres_doc'] = 'Ingrese el número de documento.';
} elseif (mb_strlen($documento) > 20) {
    $errores['caracteres_doc'] = 'El documento no debe superar 20 caracteres.';
} elseif ($codigoTipoDoc !== null) {
    $docOk = true;

    if ($codigoTipoDoc === 'DNI') {
        if (!preg_match('/^[0-9]{8}$/', $documento)) {
            $docOk = false;
        }
    } elseif ($codigoTipoDoc === 'BREVETE') {
        if (!preg_match('/^([A-Za-z][0-9]{8}|[A-Za-z][0-9]{9}|[A-Za-z]{2}[0-9]{8})$/', $documento)) {
            $docOk = false;
        }
    } elseif ($codigoTipoDoc === 'CE') {
        if (!preg_match('/^[A-Za-z0-9]{9,12}$/', $documento)) {
            $docOk = false;
        }
    }

    if (!$docOk) {
        $errores['caracteres_doc'] = 'El documento no cumple con el formato para ' . $codigoTipoDoc . '.';
    }
}

// Categoría (opcional)
if ($idCategoria !== null) {
    if ($idCategoria <= 0) {
        $errores['categoria'] = 'La categoría seleccionada no es válida.';
    } else {
        $sql  = "SELECT id FROM cq_categorias_licencia WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $idCategoria);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res->fetch_assoc()) {
            $errores['categoria'] = 'La categoría seleccionada no existe.';
        }
        $stmt->close();
    }
}

// Fechas
$dtEmision = cf_parse_fecha_esp($fechaEmisionStr);
$dtInicio  = cf_parse_fecha_esp($fechaInicioStr);
$dtFin     = cf_parse_fecha_esp($fechaFinStr);

if ($dtEmision === false) {
    $errores['fecha_emision'] = 'La fecha de emisión no es válida (use formato dd/mm/aaaa).';
}
if ($dtInicio === false) {
    $errores['fecha_inicio'] = 'La fecha de inicio no es válida (use formato dd/mm/aaaa).';
}
if ($dtFin === false) {
    $errores['fecha_fin'] = 'La fecha de fin no es válida (use formato dd/mm/aaaa).';
}

if (!$errores) {
    if ($dtEmision instanceof DateTime && $dtInicio instanceof DateTime) {
        if ($dtEmision < $dtInicio) {
            $errores['fecha_emision'] = 'La fecha de emisión no puede ser anterior a la fecha de inicio.';
        }
    }
    if ($dtEmision instanceof DateTime && $dtFin instanceof DateTime) {
        if ($dtEmision < $dtFin) {
            $errores['fecha_emision'] = 'La fecha de emisión no puede ser anterior a la fecha de fin.';
        }
    }
    if ($dtInicio instanceof DateTime && $dtFin instanceof DateTime) {
        if ($dtFin < $dtInicio) {
            $errores['fecha_fin'] = 'La fecha fin no puede ser anterior a la fecha inicio.';
        }
    }
}

// Horas
$horasTeor = ($horasTeorStr === '') ? 0 : (int)$horasTeorStr;
$horasPrac = ($horasPracStr === '') ? 0 : (int)$horasPracStr;

if ($horasTeor < 0 || $horasTeor > 100) {
    $errores['horas_teoricas'] = 'Las horas teóricas deben estar entre 0 y 100.';
}
if ($horasPrac < 0 || $horasPrac > 100) {
    $errores['horas_practicas'] = 'Las horas prácticas deben estar entre 0 y 100.';
}
if ($horasTeor <= 0 && $horasPrac <= 0) {
    $errores['horas_teoricas']  = $errores['horas_teoricas']  ?? 'Debe ingresar al menos horas teóricas o prácticas.';
    $errores['horas_practicas'] = $errores['horas_practicas'] ?? 'Debe ingresar al menos horas teóricas o prácticas.';
}

// Estado
$estadoInput = $estadoInput === '' ? 'Activo' : $estadoInput;
$estadoValido = ['Activo', 'Inactivo', 'Vencido'];
if (!in_array($estadoInput, $estadoValido, true)) {
    $errores['estado'] = 'El estado seleccionado no es válido.';
}
$estadoBd = $estadoInput;

// Si hay errores
if ($errores) {
    echo json_encode([
        'ok'      => false,
        'errores' => $errores,
    ]);
    exit;
}

// Fechas a formato SQL
$fechaEmisionSql = ($dtEmision instanceof DateTime) ? $dtEmision->format('Y-m-d') : null;
$fechaInicioSql  = ($dtInicio instanceof DateTime)  ? $dtInicio->format('Y-m-d')  : null;
$fechaFinSql     = ($dtFin instanceof DateTime)     ? $dtFin->format('Y-m-d')     : null;

/* ==== DEBUG 2: valores preparados para SQL ==== */
file_put_contents(
    __DIR__ . '/debug_fecha_fin.log',
    date('c') .
    " | BEFORE UPDATE id_certificado={$idCertificado}" .
    " | dtEmision=" . (is_object($dtEmision) ? $dtEmision->format('Y-m-d') : var_export($dtEmision, true)) .
    " | dtInicio=" . (is_object($dtInicio) ? $dtInicio->format('Y-m-d') : var_export($dtInicio, true)) .
    " | dtFin=" . (is_object($dtFin) ? $dtFin->format('Y-m-d') : var_export($dtFin, true)) .
    " | fechaEmisionSql=" . var_export($fechaEmisionSql, true) .
    " | fechaInicioSql=" . var_export($fechaInicioSql, true) .
    " | fechaFinSql=" . var_export($fechaFinSql, true) .
    PHP_EOL,
    FILE_APPEND
);
/* ==== FIN DEBUG 2 ==== */

// Actualizar (no se toca codigo_qr ni correlativos)
$sql = "UPDATE cq_certificados
        SET id_curso = ?,
            id_plantilla_certificado = ?,
            id_tipo_doc = ?,
            id_categoria_licencia = ?,
            nombres_cliente = ?,
            apellidos_cliente = ?,
            documento_cliente = ?,
            fecha_emision = ?,
            fecha_inicio = ?,
            fecha_fin = ?,
            horas_teoricas = ?,
            horas_practicas = ?,
            estado = ?,
            actualizado = NOW()
        WHERE id = ? AND id_empresa = ?";

$stmt = $db->prepare($sql);

$idCatBind = $idCategoria;
$fiBind    = $fechaInicioSql;
$ffBind    = $fechaFinSql;

$stmt->bind_param(
    'iiiissssssiisii',
    $idCurso,
    $idPlantilla,
    $idTipoDoc,
    $idCatBind,
    $nombres,
    $apellidos,
    $documento,
    $fechaEmisionSql,
    $fiBind,
    $ffBind,
    $horasTeor,
    $horasPrac,
    $estadoBd,
    $idCertificado,
    $idEmpresa
);

$stmt->execute();

$affected = $stmt->affected_rows;
$stmt->close();

/* ==== DEBUG 3: fila en BD después del UPDATE ==== */
$stCheck = $db->prepare("SELECT fecha_emision, fecha_inicio, fecha_fin FROM cq_certificados WHERE id = ?");
$stCheck->bind_param('i', $idCertificado);
$stCheck->execute();
$rowCheck = $stCheck->get_result()->fetch_assoc();
$stCheck->close();

file_put_contents(
    __DIR__ . '/debug_fecha_fin.log',
    date('c') .
    " | AFTER UPDATE id_certificado={$idCertificado}" .
    " | affected_rows={$affected}" .
    " | rowCheck=" . json_encode($rowCheck) .
    PHP_EOL,
    FILE_APPEND
);
/* ==== FIN DEBUG 3 ==== */

echo json_encode([
    'ok'      => true,
    'mensaje' => 'Certificado actualizado correctamente.',
]);
