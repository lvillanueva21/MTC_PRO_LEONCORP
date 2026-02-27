<?php
include('db.php');

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $stmt = $conexion->prepare("SELECT * FROM certificados WHERE qr_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $certificado = $result->fetch_assoc();
    $stmt->close();

    if (!$certificado) {
        die("Certificado no encontrado.");
    }

    $qr_file = "qrs/" . $certificado['qr_code'] . ".png";

    require_once('TCPDF/tcpdf.php');

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    $fondo = 'img/modelo_6.webp';
    $pdf->Image($fondo, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

    // Estilo de texto principal
    $pdf->SetTextColor(0, 0, 0);

    // Nombre completo del alumno (centrado bajo “SE CONCEDE A”)
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetXY(0, 80);
    $pdf->Cell(297, 10, strtoupper($certificado['nombres'] . ' ' . $certificado['apellidos']), 0, 1, 'C');

    // Texto del curso
    $pdf->SetFont('helvetica', '', 16);
    $pdf->SetXY(0, 100);
    $pdf->Cell(297, 10, 'Por haber participado y aprobado el curso de especialización:', 0, 1, 'C');

    // Nombre del curso
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetXY(0, 110);
    $pdf->Cell(297, 10, strtoupper($certificado['curso']), 0, 1, 'C');

    // Código del certificado en la parte inferior (centrado)
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(0, 195);
    $pdf->Cell(297, 5, 'Código de certificado: ' . $certificado['codigo'], 0, 1, 'C');

    // Insertar QR en esquina inferior izquierda
    $pdf->Image($qr_file, 10, 165, 35, 35, 'PNG');

// Crear nombre del archivo con código + apellidos + empresa
$codigo   = $certificado['codigo'];
$apellidos = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($certificado['apellidos']));
$empresa  = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($certificado['empresa']));

$nombre_pdf = "{$codigo}_{$apellidos}_{$empresa}.pdf";

// Descargar en el navegador
$pdf->Output($nombre_pdf, "I");

    exit;
} else {
    die("Código de certificado no proporcionado.");
}
?>