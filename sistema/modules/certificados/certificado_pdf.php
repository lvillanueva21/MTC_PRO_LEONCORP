<?php
// modules/certificados/certificado_pdf.php
// Genera el PDF REAL de un certificado usando la plantilla, posiciones y elementos
// definidos en cq_plantillas_* (mismo criterio que pdf_preview.php, pero con datos reales)

// ---------------------------------------------------------------------
// DEBUG: activar errores en pantalla (QUITAR O COMENTAR EN PRODUCCIÓN)
// ---------------------------------------------------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {

    require_once __DIR__ . '/../../includes/conexion.php';
    require_once __DIR__ . '/../TCPDF/tcpdf.php';

    // Cargar phpqrcode (mismo QR que en qr_imagen.php)
    $qrLibPath = dirname(__DIR__) . '/phpqrcode/qrlib.php';
    if (!file_exists($qrLibPath)) {
        throw new Exception('No se encontró la librería phpqrcode en: ' . $qrLibPath);
    }
    require_once $qrLibPath;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $db = db();
    $db->set_charset('utf8mb4');

    // ----------------- Helpers básicos -----------------

    function clampf($v, $min, $max) {
        $v = (float)$v;
        if ($v < $min) $v = $min;
        if ($v > $max) $v = $max;
        return $v;
    }

    function formatFechaDMY(?DateTime $dt): string {
        if (!$dt) return '';
        return $dt->format('d/m/Y');
    }

    function formatFechaLargaEs(?DateTime $dt): string {
        if (!$dt) return '';
        static $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'agosto',
            8 => 'agosto', 9 => 'septiembre', 10 => 'octubre',
            11 => 'noviembre', 12 => 'diciembre'
        ];
        $d = (int)$dt->format('d');
        $m = (int)$dt->format('m');
        $y = (int)$dt->format('Y');
        $mes = $meses[$m] ?? $dt->format('m');
        return "{$d} de {$mes} de {$y}";
    }

    /**
     * Devuelve el texto real para cada elemento configurable
     * $code -> uno de:
     *  - plantilla_nombre
     *  - plantilla_resolucion
     *  - nombre_completo
     *  - curso
     *  - detalle_curso
     *  - codigo_certificado
     *  - representante
     *  - fecha_caducidad
     *  - ciudad_fecha_emision
     *
     * $ctx tiene todos los datos del certificado / plantilla / curso.
     */
    function element_text(string $code, array $ctx, string $example = ''): string {
        $txt = '';

        switch ($code) {
            case 'plantilla_nombre':
                $txt = trim((string)($ctx['plantilla_nombre'] ?? ''));
                break;

            case 'plantilla_resolucion':
                $txt = trim((string)($ctx['plantilla_resolucion'] ?? ''));
                break;

            case 'nombre_completo':
                $ape = trim((string)($ctx['apellidos_cliente'] ?? ''));
                $nom = trim((string)($ctx['nombres_cliente'] ?? ''));
                $full = trim($ape . ' ' . $nom);
                $txt = $full !== '' ? $full : '';
                break;

            case 'curso':
                $txt = trim((string)($ctx['curso_nombre'] ?? ''));
                break;

            case 'codigo_certificado':
                $txt = trim((string)($ctx['codigo_certificado'] ?? ''));
                break;

            case 'representante':
                // Primero, representante definido en la plantilla
                $rep = trim((string)($ctx['plantilla_representante'] ?? ''));
                if ($rep === '' && !empty($ctx['usuario_emisor'])) {
                    // fallback: usuario que emitió
                    $rep = trim((string)$ctx['usuario_emisor']);
                }
                $txt = $rep;
                break;

            case 'fecha_caducidad':
                /** @var ?DateTime $dtEmision */
                $dtEmision = $ctx['dt_emision'] ?? null;
                if ($dtEmision instanceof DateTime) {
                    $cad = clone $dtEmision;
                    $cad->modify('+1 year');
                    $txt = 'Válido hasta: ' . formatFechaDMY($cad);
                }
                break;

            case 'ciudad_fecha_emision':
                $ciudad = trim((string)($ctx['plantilla_ciudad'] ?? ''));
                /** @var ?DateTime $dtEmision */
                $dtEmision = $ctx['dt_emision'] ?? null;
                if ($dtEmision instanceof DateTime) {
                    $fechaLarga = formatFechaLargaEs($dtEmision);
                    if ($ciudad !== '') {
                        $txt = $ciudad . ', ' . $fechaLarga;
                    } else {
                        $txt = $fechaLarga;
                    }
                }
                break;

            case 'detalle_curso':
                // Párrafo con documento, categoría, fechas y horas
                $tipoDoc = trim((string)($ctx['tipo_doc_codigo'] ?? ''));
                $docNum  = trim((string)($ctx['documento_cliente'] ?? ''));
                $cat     = trim((string)($ctx['categoria_codigo'] ?? ''));

                $docLabel = '';
                if ($tipoDoc !== '' && $docNum !== '') {
                    $docLabel = $tipoDoc . ' N.º ' . $docNum;
                } elseif ($docNum !== '') {
                    $docLabel = 'Documento N.º ' . $docNum;
                }

                $parte1 = '';
                if ($docLabel !== '') {
                    $parte1 = 'Identificado(a) con ' . $docLabel;
                    if ($cat !== '') {
                        $parte1 .= ' y categoría ' . $cat;
                    }
                }

                /** @var ?DateTime $dtInicio */
                $dtInicio = $ctx['dt_inicio'] ?? null;
                /** @var ?DateTime $dtFin */
                $dtFin    = $ctx['dt_fin'] ?? null;
                /** @var ?DateTime $dtEmision */
                $dtEmision = $ctx['dt_emision'] ?? null;

                $fraseFecha = '';
                if ($dtInicio && $dtFin) {
                    if ($dtInicio->format('Y-m-d') === $dtFin->format('Y-m-d')) {
                        $fraseFecha = 'realizado el ' . formatFechaLargaEs($dtFin);
                    } else {
                        $fraseFecha = 'realizado del ' . formatFechaLargaEs($dtInicio) .
                                      ' al ' . formatFechaLargaEs($dtFin);
                    }
                } elseif ($dtFin) {
                    $fraseFecha = 'realizado el ' . formatFechaLargaEs($dtFin);
                } elseif ($dtEmision) {
                    $fraseFecha = 'realizado el ' . formatFechaLargaEs($dtEmision);
                }

                $ht = (int)($ctx['horas_teoricas'] ?? 0);
                $hp = (int)($ctx['horas_practicas'] ?? 0);

                $horasTxt = '';
                if ($ht > 0 && $hp > 0) {
                    $horasTxt = 'cumpliendo satisfactoriamente con una duración total de ' .
                                str_pad((string)$ht, 2, '0', STR_PAD_LEFT) . ' horas teóricas y ' .
                                str_pad((string)$hp, 2, '0', STR_PAD_LEFT) . ' horas prácticas.';
                } elseif ($ht > 0) {
                    $horasTxt = 'cumpliendo satisfactoriamente con una duración total de ' .
                                str_pad((string)$ht, 2, '0', STR_PAD_LEFT) . ' horas teóricas.';
                } elseif ($hp > 0) {
                    $horasTxt = 'cumpliendo satisfactoriamente con una duración total de ' .
                                str_pad((string)$hp, 2, '0', STR_PAD_LEFT) . ' horas prácticas.';
                }

                $partes = [];
                if ($parte1 !== '') {
                    $partes[] = $parte1 . ', ha participado en el curso';
                } else {
                    $partes[] = 'Ha participado en el curso';
                }
                if ($fraseFecha !== '') {
                    $partes[] = $fraseFecha;
                }
                $txt = implode(' ', $partes);
                if ($horasTxt !== '') {
                    $txt .= ', ' . $horasTxt;
                } else {
                    $txt .= '.';
                }

                break;
        }

        $txt = trim($txt);

        if ($txt === '' && $example !== '') {
            $txt = trim($example);
        }
        if ($txt === '') {
            $txt = $code;
        }

        return $txt;
    }

    // TCPDF: algunos servidores no soportan WEBP -> convertimos a PNG temporal
    function ensure_pdf_image(string $path): string {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== 'webp') return $path;
        if (!function_exists('imagecreatefromwebp')) return $path;
        $im = @imagecreatefromwebp($path);
        if (!$im) return $path;
        $tmp = sys_get_temp_dir() . '/tmp_' . uniqid('img_') . '.png';
        imagepng($im, $tmp);
        imagedestroy($im);
        return $tmp;
    }

    // ----------------- Entrada: ID de certificado -----------------
    $idCert = (int)($_GET['id'] ?? 0);
    if ($idCert <= 0) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'ID de certificado inválido';
        exit;
    }

    // ----------------- Datos del certificado + plantilla -----------------
    $sql = "
      SELECT
        c.*,
        pc.nombre        AS plantilla_nombre,
        pc.resolucion    AS plantilla_resolucion,
        pc.representante AS plantilla_representante,
        pc.ciudad        AS plantilla_ciudad,
        pc.fondo_path,
        pc.logo_path,
        pc.firma_path,
        e.nombre         AS empresa_nombre,
        cu.nombre        AS curso_nombre,
        tdoc.codigo      AS tipo_doc_codigo,
        cat.codigo       AS categoria_codigo,
        uem.nombres      AS usuario_nombres,
        uem.apellidos    AS usuario_apellidos
      FROM cq_certificados c
      JOIN cq_plantillas_certificados pc ON pc.id = c.id_plantilla_certificado
      JOIN mtp_empresas e               ON e.id = c.id_empresa
      JOIN cr_cursos cu                 ON cu.id = c.id_curso
      JOIN cq_tipos_documento tdoc      ON tdoc.id = c.id_tipo_doc
      LEFT JOIN cq_categorias_licencia cat ON cat.id = c.id_categoria_licencia
      LEFT JOIN mtp_usuarios uem        ON uem.id = c.id_usuario_emisor
      WHERE c.id = ?
    ";
    $st = $db->prepare($sql);
    $st->bind_param('i', $idCert);
    $st->execute();
    $cert = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$cert) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Certificado no encontrado';
        exit;
    }

    // ----------------- Preparar contexto (fechas robustas) -----------------
    $dtEmision = null;
    $dtInicio  = null;
    $dtFin     = null;

    try {
        if (!empty($cert['fecha_emision']) && $cert['fecha_emision'] !== '0000-00-00') {
            $dtEmision = new DateTime($cert['fecha_emision']);
        }
    } catch (Exception $e) {
        $dtEmision = null;
    }

    try {
        if (!empty($cert['fecha_inicio']) && $cert['fecha_inicio'] !== '0000-00-00') {
            $dtInicio = new DateTime($cert['fecha_inicio']);
        }
    } catch (Exception $e) {
        $dtInicio = null;
    }

    try {
        if (!empty($cert['fecha_fin']) && $cert['fecha_fin'] !== '0000-00-00') {
            $dtFin = new DateTime($cert['fecha_fin']);
        }
    } catch (Exception $e) {
        $dtFin = null;
    }

    $usuarioEmisor = trim(
        (string)($cert['usuario_nombres'] ?? '') . ' ' .
        (string)($cert['usuario_apellidos'] ?? '')
    );

    $ctx = [
        'plantilla_nombre'        => $cert['plantilla_nombre'] ?? '',
        'plantilla_resolucion'    => $cert['plantilla_resolucion'] ?? '',
        'plantilla_representante' => $cert['plantilla_representante'] ?? '',
        'plantilla_ciudad'        => $cert['plantilla_ciudad'] ?? '',
        'empresa_nombre'          => $cert['empresa_nombre'] ?? '',
        'curso_nombre'            => $cert['curso_nombre'] ?? '',
        'nombres_cliente'         => $cert['nombres_cliente'] ?? '',
        'apellidos_cliente'       => $cert['apellidos_cliente'] ?? '',
        'documento_cliente'       => $cert['documento_cliente'] ?? '',
        'tipo_doc_codigo'         => $cert['tipo_doc_codigo'] ?? '',
        'categoria_codigo'        => $cert['categoria_codigo'] ?? '',
        'horas_teoricas'          => $cert['horas_teoricas'] ?? 0,
        'horas_practicas'         => $cert['horas_practicas'] ?? 0,
        'codigo_certificado'      => $cert['codigo_certificado'] ?? '',
        'codigo_qr'               => $cert['codigo_qr'] ?? '',
        'estado'                  => $cert['estado'] ?? 'Activo',
        'dt_emision'              => $dtEmision,
        'dt_inicio'               => $dtInicio,
        'dt_fin'                  => $dtFin,
        'usuario_emisor'          => $usuarioEmisor,
    ];

    // ----------------- Rutas de imágenes (fondo / logo / firma) -----------------
    $ROOT = realpath(__DIR__ . '/../..'); // raíz del proyecto (carpeta que contiene /modules)

    function abs_path_cert($root, $rel) {
        $rel = ltrim((string)$rel, '/');
        return $root . '/' . $rel;
    }

    // Placeholders (las mismas que en la consola para vista previa)
    $PH_FONDO = abs_path_cert($ROOT, 'modules/consola/certificados/img/placeholder-fondo.png');
    $PH_LOGO  = abs_path_cert($ROOT, 'modules/consola/certificados/img/placeholder-logo.png');
    $PH_FIRMA = abs_path_cert($ROOT, 'modules/consola/certificados/img/placeholder-firma.png');

    $fondo = $cert['fondo_path'] ? abs_path_cert($ROOT, $cert['fondo_path']) : $PH_FONDO;
    $logo  = $cert['logo_path']  ? abs_path_cert($ROOT, $cert['logo_path'])  : $PH_LOGO;
    $firma = $cert['firma_path'] ? abs_path_cert($ROOT, $cert['firma_path']) : $PH_FIRMA;

    if (!is_file($fondo)) $fondo = $PH_FONDO;
    if (!is_file($logo))  $logo  = $PH_LOGO;
    if (!is_file($firma)) $firma = $PH_FIRMA;

    $fondo = ensure_pdf_image($fondo);
    $logo  = ensure_pdf_image($logo);
    $firma = ensure_pdf_image($firma);

    // ----------------- Layout: posiciones y elementos de la PLANTILLA -----------------
    $idPlantilla = (int)$cert['id_plantilla_certificado'];

    // Elementos visibles (panel "Ver" de la consola)
    $visible = [];
    $stVis = $db->prepare("
      SELECT codigo_elemento
      FROM cq_plantillas_elementos
      WHERE id_plantilla_certificado = ?
    ");
    $stVis->bind_param('i', $idPlantilla);
    $stVis->execute();
    $resVis = $stVis->get_result();
    while ($v = $resVis->fetch_assoc()) {
        $visible[] = (string)$v['codigo_elemento'];
    }
    $stVis->close();
    $visibleSet = array_flip($visible);

    // Layout por defecto (si no hubiera filas en cq_plantillas_posiciones)
    $logoPos  = ['x' => 50.0, 'y' => 15.0, 'w' => 30.0];
    $firmaPos = ['x' => 80.0, 'y' => 80.0, 'w' => 25.0];
    $qrPos    = null;

    // Guardamos las filas de texto aquí
    $textRows = [];

    // Leer posiciones
    $stPos = $db->prepare("
      SELECT codigo_elemento, pos_x, pos_y, ancho, ejemplo_texto,
             font_size, font_bold, font_align, font_family, font_color
      FROM cq_plantillas_posiciones
      WHERE id_plantilla_certificado = ? AND pagina = 1
    ");
    $stPos->bind_param('i', $idPlantilla);
    $stPos->execute();
    $rsPos = $stPos->get_result();

    while ($r = $rsPos->fetch_assoc()) {
        $code = (string)$r['codigo_elemento'];

        // Logo y firma: siempre se muestran; solo usan layout para posición y tamaño
        if ($code === 'logo') {
            $logoPos = [
                'x' => clampf((float)$r['pos_x'], 0.0, 100.0),
                'y' => clampf((float)$r['pos_y'], 0.0, 100.0),
                'w' => clampf((float)($r['ancho'] ?? 30.0), 5.0, 90.0),
            ];
            continue;
        }
        if ($code === 'firma') {
            $firmaPos = [
                'x' => clampf((float)$r['pos_x'], 0.0, 100.0),
                'y' => clampf((float)$r['pos_y'], 0.0, 100.0),
                'w' => clampf((float)($r['ancho'] ?? 25.0), 5.0, 90.0),
            ];
            continue;
        }

        // QR: solo si está marcado como visible
        if ($code === 'qr') {
            if (!empty($visibleSet) && !isset($visibleSet['qr'])) {
                continue;
            }
            $qrPos = [
                'x' => clampf((float)$r['pos_x'], 0.0, 100.0),
                'y' => clampf((float)$r['pos_y'], 0.0, 100.0),
                'w' => clampf((float)($r['ancho'] ?? 18.0), 5.0, 90.0),
            ];
            continue;
        }

        // Resto de elementos de texto: respetan la lista de visibles (si existe)
        if (!empty($visibleSet) && !isset($visibleSet[$code])) {
            continue;
        }

        $textRows[] = $r;
    }
    $stPos->close();

    // ----------------- Crear PDF con TCPDF -----------------
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Sistema de certificados');
    $pdf->SetAuthor($ctx['empresa_nombre'] ?: 'Sistema');
    $pdf->SetTitle('Certificado ' . ($ctx['codigo_certificado'] ?? ''));
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->AddPage();

    $PAGE_W = 297.0; // A4 horizontal en mm
    $PAGE_H = 210.0;

    // Fondo a página completa
    $pdf->Image(
        $fondo,
        0,
        0,
        $PAGE_W,
        0,
        '',
        '',
        '',
        true,
        300,
        '',
        false,
        false,
        0,
        false,
        false,
        false
    );

    // Logo (si hay archivo)
    if (is_file($logo)) {
        $w_logo = $PAGE_W * ($logoPos['w'] / 100.0);
        $x_logo = $PAGE_W * ($logoPos['x'] / 100.0) - ($w_logo / 2.0);
        $y_logo = $PAGE_H * ($logoPos['y'] / 100.0);
        // pequeño ajuste vertical para centrar mejor
        $y_logo = $y_logo - ($w_logo / 2.0) * 0.5;

        $pdf->Image(
            $logo,
            $x_logo,
            $y_logo,
            $w_logo,
            0,
            '',
            '',
            '',
            true,
            300,
            '',
            false,
            false,
            0,
            false,
            false,
            true
        );
    }

    // Firma (si hay archivo)
    if (is_file($firma)) {
        $w_firma = $PAGE_W * ($firmaPos['w'] / 100.0);
        $x_firma = $PAGE_W * ($firmaPos['x'] / 100.0) - ($w_firma / 2.0);
        $y_firma = $PAGE_H * ($firmaPos['y'] / 100.0);
        $y_firma = $y_firma - ($w_firma / 2.0) * 0.3;

        $pdf->Image(
            $firma,
            $x_firma,
            $y_firma,
            $w_firma,
            0,
            '',
            '',
            '',
            true,
            300,
            '',
            false,
            false,
            0,
            false,
            false,
            true
        );
    }

    // ----------------- Textos reales según layout -----------------
    foreach ($textRows as $r2) {
        $code = (string)$r2['codigo_elemento'];

        $xPct = clampf((float)$r2['pos_x'], 0.0, 100.0);
        $yPct = clampf((float)$r2['pos_y'], 0.0, 100.0);
        $wPct = (float)$r2['ancho'];
        if ($wPct <= 0.0) {
            $wPct = 40.0;
        }
        $wPct = clampf($wPct, 5.0, 90.0);

        $w_mm = $PAGE_W * ($wPct / 100.0);
        $x_mm = $PAGE_W * ($xPct / 100.0) - ($w_mm / 2.0);
        $y_mm = $PAGE_H * ($yPct / 100.0);

        $example = isset($r2['ejemplo_texto']) ? (string)$r2['ejemplo_texto'] : '';
        $texto   = element_text($code, $ctx, $example);

        // Tamaño de fuente (font_size 50–200 => 10pt base)
        $fs = isset($r2['font_size']) ? (int)$r2['font_size'] : 0;
        if ($fs <= 0) $fs = 100;
        if ($fs < 50)  $fs = 50;
        if ($fs > 200) $fs = 200;
        $fontPt = 10.0 * ($fs / 100.0);

        // Negrita
        $isBold = !empty($r2['font_bold']);

        // Alineación
        $align = strtoupper((string)($r2['font_align'] ?? 'C'));
        if (!in_array($align, ['L','C','R','J'], true)) {
            $align = 'C';
        }

        // Fuente
        $font = strtolower((string)($r2['font_family'] ?? ''));
        if ($font === 'times') {
            $fontName = 'times';
        } elseif ($font === 'courier') {
            $fontName = 'courier';
        } else {
            $fontName = 'helvetica';
        }

        // Color de texto (HEX #RRGGBB)
        $colorHex = isset($r2['font_color']) ? trim((string)$r2['font_color']) : '';
        $rVal = $gVal = $bVal = null;
        if ($colorHex !== '') {
            if ($colorHex[0] === '#') {
                $colorHex = substr($colorHex, 1);
            }
            if (strlen($colorHex) === 6 && ctype_xdigit($colorHex)) {
                $rVal = hexdec(substr($colorHex, 0, 2));
                $gVal = hexdec(substr($colorHex, 2, 2));
                $bVal = hexdec(substr($colorHex, 4, 2));
            }
        }
        if ($rVal !== null) {
            $pdf->SetTextColor($rVal, $gVal, $bVal);
        } else {
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetFont($fontName, $isBold ? 'B' : '', $fontPt);

        $pdf->MultiCell(
            $w_mm,
            0,
            $texto,
            0,
            $align,
            false,
            1,
            $x_mm,
            $y_mm,
            true,
            0,
            false,
            true,
            0,
            'M'
        );
    }

    // ----------------- QR real usando phpqrcode (idéntico al de detalle_certificado) -----------------

    // token almacenado en cq_certificados.codigo_qr
    $token = trim((string)$ctx['codigo_qr']);

    // Construir EXACTAMENTE la misma URL que en qr_imagen.php
    $qrData = '';
    if ($token !== '' && defined('BASE_URL')) {
        $qrData = BASE_URL . '/modules/certificados/validar_certificado_publico.php?token=' . rawurlencode($token);
    } elseif ($token !== '') {
        // si no hay BASE_URL, al menos ponemos el token
        $qrData = $token;
    } else {
        // último fallback
        $qrData = trim((string)$ctx['codigo_certificado']);
    }

    if ($qrData !== '' && $qrPos !== null) {
        $w_qr = $PAGE_W * ($qrPos['w'] / 100.0);
        $x_qr = $PAGE_W * ($qrPos['x'] / 100.0) - ($w_qr / 2.0);
        $y_qr = $PAGE_H * ($qrPos['y'] / 100.0);
        $y_qr = $y_qr - ($w_qr / 2.0) * 0.5;

        // Generar PNG temporal con phpqrcode, mismos parámetros que qr_imagen.php
        $tmpPng = tempnam(sys_get_temp_dir(), 'qr_');
        $tmpPngPng = $tmpPng . '.png';
        // phpqrcode crea el archivo si se le pasa la ruta
        \QRcode::png($qrData, $tmpPngPng, QR_ECLEVEL_L, 4, 2);

        // Insertar la misma imagen de QR en el PDF
        $pdf->Image(
            $tmpPngPng,
            $x_qr,
            $y_qr,
            $w_qr,
            $w_qr,
            'PNG'
        );

        @unlink($tmpPng);
        @unlink($tmpPngPng);
    }

    // ----------------- Salida -----------------
    $nombrePdf = 'certificado-' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$ctx['codigo_certificado']) . '.pdf';
    $pdf->Output($nombrePdf, 'I');
    exit;

} catch (Throwable $e) {
    // Captura cualquier error/exception y muestra info clara
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error generando certificado PDF:\n\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea:   " . $e->getLine() . "\n\n";
    // Si quieres más detalle, puedes añadir:
    // echo "Trace:\n" . $e->getTraceAsString();
}
