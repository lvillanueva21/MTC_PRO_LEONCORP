<?php
// /modules/certificados/guardar_certificado.php

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
    $idUsuario = cf_resolver_id_usuario_actual($u);
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

function post_trim(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

// Leer datos del POST
$idCurso           = (int)post_trim('curso');
$idPlantilla       = (int)post_trim('tipo_certificado');
$nombres           = post_trim('nombres');
$apellidos         = post_trim('apellidos');
$idTipoDoc         = (int)post_trim('tipo_doc');
$documento         = post_trim('caracteres_doc');
$idCategoria       = post_trim('categoria') === '' ? null : (int)post_trim('categoria');
$fechaEmisionStr   = post_trim('fecha_emision');
$fechaInicioStr    = post_trim('fecha_inicio');
$fechaFinStr       = post_trim('fecha_fin');
$horasTeorStr      = post_trim('horas_teoricas');
$horasPracStr      = post_trim('horas_practicas');

// Validaciones básicas
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

// Plantilla de certificado
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

// Nombres y apellidos
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

// Tipo de documento
$codigoTipoDoc = null;
if ($idTipoDoc <= 0) {
    $errores['tipo_doc'] = 'Seleccione un tipo de documento.';
} else {
    $sql  = "SELECT codigo FROM cq_tipos_documento WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $idTipoDoc);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $errores['tipo_doc'] = 'El tipo de documento seleccionado no es válido.';
    } else {
        $codigoTipoDoc = (string)$row['codigo'];
    }
}

// Documento según tipo
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
        // 1 letra + 8 dígitos (9), 1 letra + 9 dígitos (10), 2 letras + 8 dígitos (10)
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

// Categoría (opcional pero debe existir si se envía)
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

// Reglas de negocio fechas (solo si son válidas / no nulas)
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

// Horas teoricas / prácticas
$horasTeor = ($horasTeorStr === '') ? 0 : (int)$horasTeorStr;
$horasPrac = ($horasPracStr === '') ? 0 : (int)$horasPracStr;

if ($horasTeor < 0 || $horasTeor > 100) {
    $errores['horas_teoricas'] = 'Las horas teóricas deben estar entre 0 y 100.';
}
if ($horasPrac < 0 || $horasPrac > 100) {
    $errores['horas_practicas'] = 'Las horas prácticas deben estar entre 0 y 100.';
}
if ($horasTeor <= 0 && $horasPrac <= 0) {
    $errores['horas_teoricas']   = $errores['horas_teoricas']   ?? 'Debe ingresar al menos horas teóricas o prácticas.';
    $errores['horas_practicas']  = $errores['horas_practicas']  ?? 'Debe ingresar al menos horas teóricas o prácticas.';
}

// Si hay errores, devolver
if ($errores) {
    echo json_encode([
        'ok'      => false,
        'errores' => $errores,
    ]);
    exit;
}

// Convertir fechas a Y-m-d para guardar
$fechaEmisionSql = ($dtEmision instanceof DateTime) ? $dtEmision->format('Y-m-d') : null;
$fechaInicioSql  = ($dtInicio instanceof DateTime)  ? $dtInicio->format('Y-m-d')  : null;
$fechaFinSql     = ($dtFin instanceof DateTime)     ? $dtFin->format('Y-m-d')     : null;

// Generar correlativo y código dentro de una transacción
try {
    $db->begin_transaction();

    // Bloqueo por empresa
    $sql  = "SELECT MAX(correlativo_empresa) AS max_corr
             FROM cq_certificados
             WHERE id_empresa = ?
             FOR UPDATE";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $idEmpresa);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $maxCorr = 0;
    if ($row && $row['max_corr'] !== null) {
        $maxCorr = (int)$row['max_corr'];
    }

    $correlativo = $maxCorr + 1;
    $codigoCert  = cf_formatear_codigo_certificado($correlativo);
    $codigoQr    = bin2hex(random_bytes(16));

    // Insertar certificado
    $sql = "INSERT INTO cq_certificados (
                id_empresa,
                id_usuario_emisor,
                id_curso,
                id_plantilla_certificado,
                id_tipo_doc,
                id_categoria_licencia,
                correlativo_empresa,
                codigo_certificado,
                nombres_cliente,
                apellidos_cliente,
                documento_cliente,
                fecha_emision,
                fecha_inicio,
                fecha_fin,
                horas_teoricas,
                horas_practicas,
                codigo_qr
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

    $stmt = $db->prepare($sql);

    // id_categoria_licencia y fechas pueden ser NULL
    $idCatBind = $idCategoria;
    $fiBind    = $fechaInicioSql;
    $ffBind    = $fechaFinSql;

    $stmt->bind_param(
        'iiiiiiisssssssiis',
        $idEmpresa,
        $idUsuario,
        $idCurso,
        $idPlantilla,
        $idTipoDoc,
        $idCatBind,
        $correlativo,
        $codigoCert,
        $nombres,
        $apellidos,
        $documento,
        $fechaEmisionSql,
        $fiBind,
        $ffBind,
        $horasTeor,
        $horasPrac,
        $codigoQr
    );

    $stmt->execute();
    $idCertificado = $db->insert_id;
    $stmt->close();

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    echo json_encode([
        'ok'      => false,
        'errores' => ['_global' => 'Error al guardar el certificado.'],
    ]);
    exit;
}

// Preparar resumen para el modal

// Obtener nombre de curso para mostrarlo
$nombreCurso = '';
$sql  = "SELECT nombre FROM cr_cursos WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $idCurso);
$stmt->execute();
$res  = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $nombreCurso = (string)$row['nombre'];
}
$stmt->close();

// Obtener nombre de empresa
$empresaNombre = '';
$sql  = "SELECT nombre FROM mtp_empresas WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $idEmpresa);
$stmt->execute();
$res  = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $empresaNombre = (string)$row['nombre'];
}
$stmt->close();

// Obtener nombre completo del usuario emisor
$usuarioNombre = '';
$sql  = "SELECT nombres, apellidos FROM mtp_usuarios WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$res  = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $usuarioNombre = trim((string)$row['nombres'] . ' ' . (string)$row['apellidos']);
}
$stmt->close();

// Obtener creado, actualizado y estado del certificado recién insertado
$creadoFmt      = '';
$actualizadoFmt = '';
$estadoBd       = 'Activo';

$sql  = "SELECT creado, actualizado, estado FROM cq_certificados WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $idCertificado);
$stmt->execute();
$res  = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (!empty($row['creado'])) {
        $dtCreado  = new DateTime($row['creado']);
        $creadoFmt = $dtCreado->format('d/m/Y H:i:s');
    }
    if (!empty($row['actualizado'])) {
        $dtActualizado  = new DateTime($row['actualizado']);
        $actualizadoFmt = $dtActualizado->format('d/m/Y H:i:s');
    }
    if (!empty($row['estado'])) {
        $estadoBd = (string)$row['estado'];
    }
}
$stmt->close();

// Obtener código de categoría (si aplica)
$categoriaCodigo = '';
if ($idCategoria !== null) {
    $sql  = "SELECT codigo FROM cq_categorias_licencia WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $idCategoria);
    $stmt->execute();
    $res  = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $categoriaCodigo = (string)$row['codigo'];
    }
    $stmt->close();
}

// Fechas en formato d/m/Y para mostrar
$fechaEmisionMostrar = ($dtEmision instanceof DateTime)
    ? cf_formatear_fecha_esp($dtEmision)
    : ($fechaEmisionStr !== '' ? $fechaEmisionStr : '');

$fechaInicioMostrar = ($dtInicio instanceof DateTime)
    ? cf_formatear_fecha_esp($dtInicio)
    : (($fechaInicioStr !== '') ? $fechaInicioStr : '');

$fechaFinMostrar = ($dtFin instanceof DateTime)
    ? cf_formatear_fecha_esp($dtFin)
    : (($fechaFinStr !== '') ? $fechaFinStr : '');

// Estado calculado para mostrar en interfaz
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

// Resumen completo para el modal
$resumen = [
    'codigo_certificado' => $codigoCert,
    'nombre_cliente'     => trim($nombres . ' ' . $apellidos),
    'documento_cliente'  => $documento,
    'curso'              => $nombreCurso,
    'fecha_emision'      => $fechaEmisionMostrar,
    'fecha_inicio'       => $fechaInicioMostrar,
    'fecha_fin'          => $fechaFinMostrar,
    'horas_teoricas'     => $horasTeor,
    'horas_practicas'    => $horasPrac,
    'empresa_nombre'     => $empresaNombre,
    'usuario_nombre'     => $usuarioNombre,
    'id_bd'              => $idCertificado,
    'creado'             => $creadoFmt,
    'actualizado'        => $actualizadoFmt,
    'estado'             => $estadoMostrar,
    'estado_bd'          => $estadoBd,
    'categoria'          => $categoriaCodigo,
    'tipo_doc'           => $codigoTipoDoc,
    'codigo_qr'          => $codigoQr,
];

// Calcular siguiente código solo para mostrar en el label
$proxCorr   = $correlativo + 1;
$proxCodigo = '#' . cf_formatear_codigo_certificado($proxCorr);

echo json_encode([
    'ok'               => true,
    'mensaje'          => 'Certificado creado con éxito.',
    'resumen'          => $resumen,
    'siguiente_codigo' => $proxCodigo,
]);
