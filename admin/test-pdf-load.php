<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';

echo "MeetingPDF loaded successfully\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (!class_exists('TCPDF')) {
        die("TCPDF class not found! Check vendor/autoload.php\n");
    }

    // Test AIF_PDF instantiation
    $pdf = new AIF_PDF('P', 'mm', 'A4');
    echo "AIF_PDF instantiated\n";

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Test PDF', 0, 1);
    $pdfData = $pdf->Output('test.pdf', 'S');
    echo "PDF generated successfully, size: " . strlen($pdfData) . " bytes\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
