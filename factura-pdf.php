<?php
require_once 'lib/tcpdf/tcpdf.php';
require_once 'config/db.php';
session_start();

// Verifico si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Obtengo el ID de la factura
$id_factura = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_factura <= 0) {
    header('Location: perfil.php?tab=mecenas');
    exit;
}

// Obtengo información de la factura
$stmt = $pdo->prepare("
    SELECT f.id, f.nivel_mecenas, f.monto, f.fecha,
           u.nombre, u.email
    FROM facturas f
    JOIN usuarios u ON f.id_usuario = u.id
    WHERE f.id = ? AND u.id = ?
");
$stmt->execute([$id_factura, $_SESSION['user_id']]);
$factura = $stmt->fetch();

if (!$factura) {
    header('Location: perfil.php?tab=mecenas');
    exit;
}

// Creo nuevo documento PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Establezco información del documento
$pdf->SetCreator('Ballista');
$pdf->SetAuthor('Ballista');
$pdf->SetTitle('Factura #' . sprintf('%06d', $factura['id']));
$pdf->SetSubject('Factura de Mecenazgo');

// Elimino cabecera y pie de página predeterminados
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Establezco márgenes
$pdf->SetMargins(15, 15, 15);

// Agrego una página
$pdf->AddPage();

// Establezco fuente
$pdf->SetFont('helvetica', 'B', 16);

// Título
$pdf->Cell(0, 10, 'FACTURA', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Nº: ' . sprintf('%06d', $factura['id']), 0, 1, 'C');
$pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y', strtotime($factura['fecha'])), 0, 1, 'C');
$pdf->Ln(10);

// Datos de la empresa
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'BALLISTA HISTORIA MILITAR, S.L.', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'CIF: B12345678', 0, 1, 'L');
$pdf->Cell(0, 6, 'Calle Historia, 123', 0, 1, 'L');
$pdf->Cell(0, 6, '28001 Madrid, España', 0, 1, 'L');
$pdf->Cell(0, 6, 'Email: info@ballista.com', 0, 1, 'L');
$pdf->Cell(0, 6, 'Web: www.ballista.com', 0, 1, 'L');
$pdf->Ln(10);

// Datos del cliente
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'FACTURAR A:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $factura['nombre'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Email: ' . $factura['email'], 0, 1, 'L');
$pdf->Ln(10);

// Título de la factura
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'DETALLE DE FACTURA', 0, 1, 'L');

// Cabecera de la tabla
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(100, 8, 'DESCRIPCIÓN', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'CANTIDAD', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'PRECIO', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'TOTAL', 1, 1, 'C', true);

// Contenido de la tabla
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(100, 8, 'Suscripción Mecenas - Nivel ' . ucfirst($factura['nivel_mecenas']), 1, 0, 'L');
$pdf->Cell(30, 8, '1', 1, 0, 'C');
$pdf->Cell(30, 8, $factura['monto'] . ' €', 1, 0, 'C');
$pdf->Cell(30, 8, $factura['monto'] . ' €', 1, 1, 'C');

// Total
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(160, 8, 'TOTAL:', 1, 0, 'R', true);
$pdf->Cell(30, 8, $factura['monto'] . ' €', 1, 1, 'C', true);
$pdf->Ln(10);

// Información adicional
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'INFORMACIÓN DE PAGO:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Método de pago: Tarjeta de crédito', 0, 1, 'L');
$pdf->Cell(0, 6, 'Fecha de pago: ' . date('d/m/Y', strtotime($factura['fecha'])), 0, 1, 'L');
$pdf->Ln(10);

// Notas
$pdf->SetFont('helvetica', 'I', 9);
$pdf->MultiCell(0, 6, 'Gracias por convertirte en mecenas de Ballista. Tu apoyo es fundamental para nuestro proyecto.', 0, 'L');
$pdf->Ln(5);
$pdf->Cell(0, 10, 'Este documento es una factura electrónica válida según la normativa vigente.', 0, 1, 'C');
$pdf->Cell(0, 10, 'Ballista Historia Militar, S.L. - Todos los derechos reservados ' . date('Y'), 0, 1, 'C');

// Salida del PDF
$pdf->Output('factura_' . sprintf('%06d', $factura['id']) . '.pdf', 'I');
?>