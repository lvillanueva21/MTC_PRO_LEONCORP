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
                c.creado,
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
        $id        = (int)$r['id'];
        $codigo    = $r['codigo_certificado'];
        $nombre    = trim($r['nombres_cliente'] . ' ' . $r['apellidos_cliente']);
        $doc       = $r['documento_cliente'];
        $cursoNom  = $r['nombre_curso'];
        $estadoM   = $r['estado_mostrado'];

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
        // Botón 1: detalle + QR
        $tbodyHtml .= '<button type="button" class="btn-icon btn-icon-qr" data-cert-id="' . $id . '"><i class="fas fa-qrcode"></i></button>';
        // Botón 2: PDF (abre el certificado en una nueva pestaña) usando archivo centralizado
        $tbodyHtml .= '<a href="certificado_pdf.php?id=' . $id . '" target="_blank" class="btn-icon btn-icon-pdf" title="Ver certificado en PDF"><i class="far fa-file-pdf"></i></a>';
        // Botón 3: editar
        $tbodyHtml .= '<button type="button" class="btn-icon btn-icon-edit" data-cert-id="' . $id . '"><i class="fas fa-pen"></i></button>';
        // Botón 4: desactivar (cambia estado a Inactivo)
        $tbodyHtml .= '<button type="button" class="btn-icon btn-icon-deactivate" data-cert-id="' . $id . '" title="Desactivar certificado"><i class="fas fa-power-off"></i></button>';
        $tbodyHtml .= '</td>';
        $tbodyHtml .= '</tr>';
    }
}

// Construir HTML de paginación (estilo panel: pocas páginas visibles, sin perder navegación)
$pagHtml = '';

if ($totalPaginas > 1) {

    $btn = function($p, $paginaActual) {
        $clase = 'page-btn';
        if ((int)$p === (int)$paginaActual) {
            $clase .= ' active';
        }
        return '<button type="button" class="' . $clase . '" data-page="' . (int)$p . '">' . (int)$p . '</button>';
    };

    $sep = '<span class="page-sep">...</span>';

    // Regla: si hay pocas páginas, mostrar todas (sin puntos suspensivos)
    // Ajusta 8 si quieres más o menos (8 es un valor común y queda limpio en UI).
    if ($totalPaginas <= 8) {

        // Anterior
        if ($pagina > 1) {
            $pagHtml .= '<button type="button" class="page-btn" data-page="' . (int)($pagina - 1) . '">&laquo;</button>';
        }

        for ($p = 1; $p <= $totalPaginas; $p++) {
            $pagHtml .= $btn($p, $pagina);
        }

        // Siguiente
        if ($pagina < $totalPaginas) {
            $pagHtml .= '<button type="button" class="page-btn" data-page="' . (int)($pagina + 1) . '">&raquo;</button>';
        }

    } else {

        // Cuando hay muchas páginas:
        // - siempre mostrar 1 2 3
        // - siempre mostrar las 3 últimas
        // - mostrar una ventana móvil alrededor de la actual (para no perder páginas)
        $rangoCentro = 1; // muestra (pagina-1, pagina, pagina+1). Si quieres 2 a cada lado, pon 2.

        $primeros = [1, 2, 3];
        $ultimos  = [$totalPaginas - 2, $totalPaginas - 1, $totalPaginas];

        $inicioCentro = $pagina - $rangoCentro;
        $finCentro    = $pagina + $rangoCentro;

        if ($inicioCentro < 4) {
            $inicioCentro = 4;
        }
        if ($finCentro > $totalPaginas - 3) {
            $finCentro = $totalPaginas - 3;
        }

        // Anterior
        if ($pagina > 1) {
            $pagHtml .= '<button type="button" class="page-btn" data-page="' . (int)($pagina - 1) . '">&laquo;</button>';
        }

        // 1 2 3
        foreach ($primeros as $p) {
            $pagHtml .= $btn($p, $pagina);
        }

        // Separador entre primeros y centro
        if ($inicioCentro > 4) {
            $pagHtml .= $sep;
        }

        // Centro (ventana móvil)
        for ($p = $inicioCentro; $p <= $finCentro; $p++) {
            if ($p >= 4 && $p <= ($totalPaginas - 3)) {
                $pagHtml .= $btn($p, $pagina);
            }
        }

        // Separador entre centro y últimos
        if ($finCentro < ($totalPaginas - 3)) {
            $pagHtml .= $sep;
        }

        // Últimos 3
        foreach ($ultimos as $p) {
            $pagHtml .= $btn($p, $pagina);
        }

        // Siguiente
        if ($pagina < $totalPaginas) {
            $pagHtml .= '<button type="button" class="page-btn" data-page="' . (int)($pagina + 1) . '">&raquo;</button>';
        }
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
