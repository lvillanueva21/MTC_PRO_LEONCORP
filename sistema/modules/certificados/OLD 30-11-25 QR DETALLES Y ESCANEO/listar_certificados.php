<?php
// /modules/certificados/listar_certificados.php

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/funciones_formulario.php';

acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

header('Content-Type: application/json; charset=utf-8');

// Helper de escape
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$db = db();
$u  = currentUser();

$idEmpresa = cf_resolver_id_empresa_actual($u);

// Filtros de entrada
$pagina      = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$fechaInicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fechaFin    = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$estado      = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$curso       = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;
$busqueda    = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Validar estado
$estadosValidos = ['Activo', 'Inactivo', 'Vencido'];
if (!in_array($estado, $estadosValidos, true)) {
    $estado = '';
}

// Validar fechas (esperamos Y-m-d)
if ($fechaInicio !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
    $fechaInicio = '';
}
if ($fechaFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
    $fechaFin = '';
}

// Construcción dinámica de WHERE
$condiciones = [];
$tipos       = '';
$valores     = [];

// Empresa obligatoria
$condiciones[] = 'c.id_empresa = ?';
$tipos       .= 'i';
$valores[]    = $idEmpresa;

// Rango de fechas (fecha_emision)
if ($fechaInicio !== '') {
    $condiciones[] = 'c.fecha_emision >= ?';
    $tipos       .= 's';
    $valores[]    = $fechaInicio;
}
if ($fechaFin !== '') {
    $condiciones[] = 'c.fecha_emision <= ?';
    $tipos       .= 's';
    $valores[]    = $fechaFin;
}

// Filtro de estado con lógica de vencido dinámico
if ($estado === 'Inactivo') {
    $condiciones[] = "c.estado = 'Inactivo'";
} elseif ($estado === 'Vencido') {
    $condiciones[] = "(c.estado = 'Vencido' OR (c.estado = 'Activo' AND c.fecha_emision < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)))";
} elseif ($estado === 'Activo') {
    $condiciones[] = "(c.estado = 'Activo' AND c.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR))";
}

// Filtro de curso
if ($curso > 0) {
    $condiciones[] = 'c.id_curso = ?';
    $tipos       .= 'i';
    $valores[]    = $curso;
}

// Filtro de búsqueda (nombre o documento)
if ($busqueda !== '') {
    $busquedaTrim = str_replace(' ', '', $busqueda);

    if ($busquedaTrim !== '' && ctype_digit($busquedaTrim)) {
        // Parece documento numérico (DNI, parte de brevete, CE numérico)
        $condiciones[] = 'c.documento_cliente LIKE ?';
        $tipos       .= 's';
        $valores[]    = $busquedaTrim . '%';
    } else {
        // Buscar tanto en documento como en nombre completo
        $condiciones[] = '(c.documento_cliente LIKE ? OR CONCAT(c.nombres_cliente, " ", c.apellidos_cliente) LIKE ?)';
        $tipos       .= 'ss';
        $like = '%' . $busqueda . '%';
        $valores[] = $like;
        $valores[] = $like;
    }
}

// Armar WHERE final
$where = '';
if (!empty($condiciones)) {
    $where = 'WHERE ' . implode(' AND ', $condiciones);
}

// Paginación
$porPagina = 5;
$offset    = ($pagina - 1) * $porPagina;

// 1) Total de registros para los filtros
$sqlCount = "SELECT COUNT(*) AS total
             FROM cq_certificados c
             INNER JOIN cr_cursos cu ON cu.id = c.id_curso
             $where";

$stmtCount = $db->prepare($sqlCount);
if ($tipos !== '') {
    $stmtCount->bind_param($tipos, ...$valores);
}
$stmtCount->execute();
$resCount = $stmtCount->get_result();
$rowCount = $resCount->fetch_assoc();
$stmtCount->close();

$totalRegistros = $rowCount && isset($rowCount['total']) ? (int)$rowCount['total'] : 0;
$totalPaginas   = $totalRegistros > 0 ? (int)ceil($totalRegistros / $porPagina) : 1;
if ($totalPaginas < 1) {
    $totalPaginas = 1;
}

// Ajustar página si se pasó del máximo
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

// 2) Consulta paginada de datos
$sqlDatos = "SELECT
                c.id,
                c.codigo_certificado,
                c.estado,
                c.fecha_emision,
                c.documento_cliente,
                c.nombres_cliente,
                c.apellidos_cliente,
                cu.nombre AS nombre_curso,
                CASE
                  WHEN c.estado = 'Inactivo' THEN 'Inactivo'
                  WHEN c.estado = 'Vencido' THEN 'Vencido'
                  WHEN c.estado = 'Activo' AND c.fecha_emision < DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Vencido'
                  ELSE 'Activo'
                END AS estado_mostrado
             FROM cq_certificados c
             INNER JOIN cr_cursos cu ON cu.id = c.id_curso
             $where
             ORDER BY c.creado DESC, c.id DESC
             LIMIT ? OFFSET ?";

$tiposDatos   = $tipos . 'ii';
$valoresDatos = $valores;
$valoresDatos[] = $porPagina;
$valoresDatos[] = $offset;

$stmtDatos = $db->prepare($sqlDatos);
$stmtDatos->bind_param($tiposDatos, ...$valoresDatos);
$stmtDatos->execute();
$resDatos = $stmtDatos->get_result();

$filas = [];
while ($row = $resDatos->fetch_assoc()) {
    $filas[] = $row;
}
$stmtDatos->close();

// Construir HTML del tbody
$tbodyHtml = '';

if (empty($filas)) {
    $tbodyHtml .= '<tr><td colspan="5">No se encontraron certificados.</td></tr>';
} else {
    foreach ($filas as $r) {
        $codigo   = $r['codigo_certificado'];
        $nombre   = trim($r['nombres_cliente'] . ' ' . $r['apellidos_cliente']);
        $doc      = $r['documento_cliente'];
        $cursoNom = $r['nombre_curso'];
        $estadoM  = $r['estado_mostrado'];

        $claseEstado = 'status-activo';
        if ($estadoM === 'Inactivo') {
            $claseEstado = 'status-inactivo';
        } elseif ($estadoM === 'Vencido') {
            $claseEstado = 'status-vencido';
        }

        $tbodyHtml .= '<tr>';
        $tbodyHtml .= '<td>' . h($codigo) . '<br><span class="badge-status ' . $claseEstado . '">' . h($estadoM) . '</span></td>';
        $tbodyHtml .= '<td>' . h($nombre) . '</td>';
        $tbodyHtml .= '<td>' . h($doc) . '</td>';
        $tbodyHtml .= '<td>' . h($cursoNom) . '</td>';
        $tbodyHtml .= '<td class="cert-actions">';
        $tbodyHtml .= '<button type="button" class="btn-icon btn-icon-qr" data-id="' . (int)$r['id'] . '"><i class="fas fa-qrcode"></i></button>';
        $tbodyHtml .= '<button type="button" class="btn-icon btn-icon-pdf"><i class="far fa-file-pdf"></i></button>';
        $tbodyHtml .= '<button type="button" class="btn-icon btn-icon-edit"><i class="fas fa-pen"></i></button>';
        $tbodyHtml .= '<button type="button" class="btn-icon btn-icon-del"><i class="fas fa-trash-alt"></i></button>';
        $tbodyHtml .= '</td>';
        $tbodyHtml .= '</tr>';
    }
}

// Construir HTML de paginación: primeros 3 y últimos 3
$pagHtml = '';

if ($totalPaginas > 1) {
    $paginasMostrar = [];

    if ($totalPaginas <= 6) {
        for ($i = 1; $i <= $totalPaginas; $i++) {
            $paginasMostrar[] = $i;
        }
    } else {
        $paginasMostrar[] = 1;
        $paginasMostrar[] = 2;
        $paginasMostrar[] = 3;
        $paginasMostrar[] = $totalPaginas - 2;
        $paginasMostrar[] = $totalPaginas - 1;
        $paginasMostrar[] = $totalPaginas;

        $paginasMostrar = array_unique($paginasMostrar);
        sort($paginasMostrar);
    }

    $ultimoImp = 0;
    foreach ($paginasMostrar as $p) {
        if ($ultimoImp && $p > $ultimoImp + 1) {
            $pagHtml .= '<span class="page-sep">...</span>';
        }
        $clase = 'page-btn';
        if ($p == $pagina) {
            $clase .= ' active';
        }
        $pagHtml .= '<button type="button" class="' . $clase . '" data-page="' . (int)$p . '">' . (int)$p . '</button>';
        $ultimoImp = $p;
    }
}

echo json_encode([
    'ok'              => true,
    'html_tbody'      => $tbodyHtml,
    'html_paginacion' => $pagHtml,
    'total_registros' => $totalRegistros,
    'pagina_actual'   => $pagina,
    'total_paginas'   => $totalPaginas,
]);
exit;
