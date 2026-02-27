<?php
// /modules/certificados/certificado_pdf.php

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/funciones_formulario.php';

acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

$u = currentUser();

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Validar parámetro id
$idCertificado = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idCertificado <= 0) {
    http_response_code(400);
    echo 'ID de certificado no válido.';
    exit;
}

// Resolver empresa actual
try {
    $idEmpresa = cf_resolver_id_empresa_actual($u);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'No se pudo determinar la empresa actual.';
    exit;
}

$db = db();

// Cargar certificado + plantilla + curso + empresa + tipo doc + categoría
$sql = "SELECT
            c.*,
            cu.nombre AS nombre_curso,
            p.nombre AS nombre_plantilla,
            p.paginas,
            p.representante,
            p.ciudad,
            p.resolucion,
            p.fondo_path,
            p.logo_path,
            p.firma_path,
            e.nombre AS nombre_empresa,
            td.codigo AS tipo_doc_codigo,
            cat.codigo AS categoria_codigo
        FROM cq_certificados c
        INNER JOIN cr_cursos cu ON cu.id = c.id_curso
        INNER JOIN cq_plantillas_certificados p ON p.id = c.id_plantilla_certificado
        INNER JOIN mtp_empresas e ON e.id = c.id_empresa
        LEFT JOIN cq_tipos_documento td ON td.id = c.id_tipo_doc
        LEFT JOIN cq_categorias_licencia cat ON cat.id = c.id_categoria_licencia
        WHERE c.id = ? AND c.id_empresa = ?
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $idCertificado, $idEmpresa);
$stmt->execute();
$res = $stmt->get_result();
$cert = $res->fetch_assoc();
$stmt->close();

if (!$cert) {
    http_response_code(404);
    echo 'Certificado no encontrado para esta empresa.';
    exit;
}

// Cargar posiciones de la plantilla
$sqlPos = "SELECT
              codigo_elemento,
              pagina,
              pos_x,
              pos_y,
              ancho,
              ejemplo_texto,
              font_size,
              font_bold,
              font_align,
              font_family,
              font_color
           FROM cq_plantillas_posiciones
           WHERE id_plantilla_certificado = ?
           ORDER BY pagina ASC, id ASC";
$stmtPos = $db->prepare($sqlPos);
$stmtPos->bind_param('i', $cert['id_plantilla_certificado']);
$stmtPos->execute();
$resPos = $stmtPos->get_result();

$posiciones = [];
while ($row = $resPos->fetch_assoc()) {
    $pagina = (int)$row['pagina'];
    if (!isset($posiciones[$pagina])) {
        $posiciones[$pagina] = [];
    }
    $posiciones[$pagina][] = $row;
}
$stmtPos->close();

// Incluir TCPDF desde modules/TCPDF/
$tcpdfPath = __DIR__ . '/../TCPDF/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    http_response_code(500);
    echo 'No se encontró TCPDF en: ' . h($tcpdfPath);
    exit;
}
require_once $tcpdfPath;

// Preparar QR local usando la misma librería que qr_imagen.php
$qrLibPath = dirname(__DIR__) . '/phpqrcode/qrlib.php';
$qrTempPath = null;
if (file_exists($qrLibPath)) {
    require_once $qrLibPath;
    if (defined('BASE_URL')) {
        $targetUrl = BASE_URL . '/modules/certificados/validar_certificado_publico.php?token=' . rawurlencode($cert['codigo_qr']);
    } else {
        $targetUrl = 'validar_certificado_publico.php?token=' . rawurlencode($cert['codigo_qr']);
    }
    $qrTempPath = sys_get_temp_dir() . '/certqr_' . $cert['id'] . '_' . $cert['codigo_qr'] . '.png';
    \QRcode::png($targetUrl, $qrTempPath, QR_ECLEVEL_L, 4, 2);
}

// Crear PDF (A4 horizontal)
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAuthor($cert['nombre_empresa']);
$pdf->SetTitle('Certificado ' . $cert['codigo_certificado']);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

// Medidas A4 horizontal en mm
$anchoHoja = 297;
$altoHoja  = 210;

// Rutas a imágenes de la plantilla (relativas a /modules/certificados/)
$baseDir   = __DIR__ . '/';
$fondoFile = $cert['fondo_path'] ? $baseDir . ltrim($cert['fondo_path'], '/') : null;
$logoFile  = $cert['logo_path']  ? $baseDir . ltrim($cert['logo_path'], '/')  : null;
$firmaFile = $cert['firma_path'] ? $baseDir . ltrim($cert['firma_path'], '/') : null;
// Mapear font_family de BD a nombres válidos de TCPDF
function map_font_family($name)
{
    if (!$name) {
        return 'helvetica';
    }
    $name = strtolower(trim($name));
    if (in_array($name, ['arial', 'helvetica', 'sans', 'sans-serif'], true)) {
        return 'helvetica';
    }
    if (in_array($name, ['times', 'times new roman', 'serif'], true)) {
        return 'times';
    }
    if (in_array($name, ['courier', 'mono', 'monospace'], true)) {
        return 'courier';
    }
    if (in_array($name, ['dejavusans', 'dejavu', 'dejavu sans'], true)) {
        return 'dejavusans';
    }
    return 'helvetica';
}

// Obtener texto para un código de elemento
function obtener_texto_elemento(array $cert, array $pos)
{
    $codigo = $pos['codigo_elemento'];

    switch ($codigo) {
        case 'CODIGO_CERTIFICADO':
            return (string)$cert['codigo_certificado'];

        case 'NOMBRES_APELLIDOS':
            return trim($cert['nombres_cliente'] . ' ' . $cert['apellidos_cliente']);

        case 'NOMBRES':
            return (string)$cert['nombres_cliente'];

        case 'APELLIDOS':
            return (string)$cert['apellidos_cliente'];

        case 'CURSO':
        case 'NOMBRE_CURSO':
            return (string)$cert['nombre_curso'];

        case 'TIPO_DOC':
            return isset($cert['tipo_doc_codigo']) ? (string)$cert['tipo_doc_codigo'] : '';

        case 'DOCUMENTO':
            return (string)$cert['documento_cliente'];

        case 'TIPO_DOC_DOCUMENTO':
            $tipo = isset($cert['tipo_doc_codigo']) ? (string)$cert['tipo_doc_codigo'] : '';
            $doc  = (string)$cert['documento_cliente'];
            if ($tipo !== '' && $doc !== '') {
                return $tipo . ': ' . $doc;
            }
            return $doc;

        case 'FECHA_EMISION':
            if (!empty($cert['fecha_emision']) && $cert['fecha_emision'] !== '0000-00-00') {
                $dt = new DateTime($cert['fecha_emision']);
                return $dt->format('d/m/Y');
            }
            return '';

        case 'FECHA_INICIO':
            if (!empty($cert['fecha_inicio']) && $cert['fecha_inicio'] !== '0000-00-00') {
                $dt = new DateTime($cert['fecha_inicio']);
                return $dt->format('d/m/Y');
            }
            return '';

        case 'FECHA_FIN':
            if (!empty($cert['fecha_fin']) && $cert['fecha_fin'] !== '0000-00-00') {
                $dt = new DateTime($cert['fecha_fin']);
                return $dt->format('d/m/Y');
            }
            return '';

        case 'HORAS_TEORICAS':
            return (string)(int)$cert['horas_teoricas'];

        case 'HORAS_PRACTICAS':
            return (string)(int)$cert['horas_practicas'];

        case 'HORAS_TOTALES':
            $t = (int)$cert['horas_teoricas'] + (int)$cert['horas_practicas'];
            return (string)$t;

        case 'CIUDAD':
            return (string)$cert['ciudad'];

        case 'CIUDAD_FECHA_EMISION':
            $ciudad = (string)$cert['ciudad'];
            if (!empty($cert['fecha_emision']) && $cert['fecha_emision'] !== '0000-00-00') {
                $dt = new DateTime($cert['fecha_emision']);
                $fecha = $dt->format('d/m/Y');
                if ($ciudad !== '') {
                    return $ciudad . ', ' . $fecha;
                }
                return $fecha;
            }
            return $ciudad;

        case 'REPRESENTANTE':
            return (string)$cert['representante'];

        case 'RESOLUCION':
            return (string)$cert['resolucion'];

        case 'NOMBRE_EMPRESA':
            return (string)$cert['nombre_empresa'];

        case 'CATEGORIA':
            return isset($cert['categoria_codigo']) ? (string)$cert['categoria_codigo'] : '';

        default:
            if (!empty($pos['ejemplo_texto'])) {
                return (string)$pos['ejemplo_texto'];
            }
            return '';
    }
}

// Número de páginas
$totalPaginas = (int)$cert['paginas'];
if ($totalPaginas < 1) {
    $totalPaginas = 1;
}

// Pintar cada página
for ($pagina = 1; $pagina <= $totalPaginas; $pagina++) {
    $pdf->AddPage('L', 'A4');

    // Fondo
    if ($fondoFile && file_exists($fondoFile)) {
        $pdf->Image($fondoFile, 0, 0, $anchoHoja, $altoHoja, '', '', '', false, 300, '', false, false, 0);
    }

    // Logo
    if ($logoFile && file_exists($logoFile) && !empty($posiciones[$pagina])) {
        foreach ($posiciones[$pagina] as $pos) {
            if ($pos['codigo_elemento'] === 'IMG_LOGO') {
                $x = (float)$pos['pos_x'];
                $y = (float)$pos['pos_y'];
                $w = (float)$pos['ancho'];
                if ($w <= 0) {
                    $w = 30.0;
                }
                $pdf->Image($logoFile, $x, $y, $w, 0, '', '', '', false, 300, '', false, false, 0);
            }
        }
    }

    // Firma
    if ($firmaFile && file_exists($firmaFile) && !empty($posiciones[$pagina])) {
        foreach ($posiciones[$pagina] as $pos) {
            if ($pos['codigo_elemento'] === 'IMG_FIRMA') {
                $x = (float)$pos['pos_x'];
                $y = (float)$pos['pos_y'];
                $w = (float)$pos['ancho'];
                if ($w <= 0) {
                    $w = 40.0;
                }
                $pdf->Image($firmaFile, $x, $y, $w, 0, '', '', '', false, 300, '', false, false, 0);
            }
        }
    }

    // QR
    if ($qrTempPath && file_exists($qrTempPath) && !empty($posiciones[$pagina])) {
        foreach ($posiciones[$pagina] as $pos) {
            if ($pos['codigo_elemento'] === 'IMG_QR') {
                $x = (float)$pos['pos_x'];
                $y = (float)$pos['pos_y'];
                $w = (float)$pos['ancho'];
                if ($w <= 0) {
                    $w = 30.0;
                }
                $pdf->Image($qrTempPath, $x, $y, $w, 0, 'PNG', '', '', false, 300, '', false, false, 0);
            }
        }
    }
    // Textos
    if (!empty($posiciones[$pagina])) {
        foreach ($posiciones[$pagina] as $pos) {
            $cod = $pos['codigo_elemento'];
            if ($cod === 'IMG_LOGO' || $cod === 'IMG_FIRMA' || $cod === 'IMG_QR') {
                continue;
            }

            $texto = obtener_texto_elemento($cert, $pos);
            if ($texto === '') {
                continue;
            }

            $x = (float)$pos['pos_x'];
            $y = (float)$pos['pos_y'];
            $w = (float)$pos['ancho'];
            if ($w <= 0) {
                $w = 100.0;
            }

            $fontSize  = $pos['font_size'] !== null ? (int)$pos['font_size'] : 12;
            $fontBold  = (int)$pos['font_bold'] === 1 ? 'B' : '';
            $fontAlign = $pos['font_align'] ?: 'L';
            $fontAlign = strtoupper($fontAlign);
            if (!in_array($fontAlign, ['L', 'C', 'R', 'J'], true)) {
                $fontAlign = 'L';
            }
            $fontFam   = map_font_family($pos['font_family']);

            $pdf->SetFont($fontFam, $fontBold, $fontSize);

            // Color de texto
            if (!empty($pos['font_color']) && preg_match('/^#([0-9A-Fa-f]{6})$/', $pos['font_color'], $m)) {
                $hex = $m[1];
                $r   = hexdec(substr($hex, 0, 2));
                $g   = hexdec(substr($hex, 2, 2));
                $b   = hexdec(substr($hex, 4, 2));
                $pdf->SetTextColor($r, $g, $b);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }

            $pdf->SetXY($x, $y);
            $pdf->MultiCell(
                $w,
                0,
                $texto,
                0,
                $fontAlign,
                false,
                1,
                '',
                '',
                true,
                0,
                false,
                true,
                0,
                'T',
                false
            );
        }
    }
}

// Eliminar QR temporal si existe
if ($qrTempPath && file_exists($qrTempPath)) {
    @unlink($qrTempPath);
}

// Salida
$nombreArchivo = 'certificado_' . $cert['codigo_certificado'] . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
$pdf->Output($nombreArchivo, 'I');
exit;
