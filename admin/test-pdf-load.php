<?php
header('Content-Type: text/plain; charset=UTF-8');
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';
require_once __DIR__ . '/../classes/Mail.php';

echo "DEBUG: System test starting...\n";

try {
    echo "DEBUG: Checking TCPDF...\n";
    if (!class_exists('TCPDF')) {
        echo "DEBUG: TCPDF not found, attempting manual load...\n";
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    if (!class_exists('TCPDF')) {
        die("FATAL: TCPDF class still not found.\n");
    }
    echo "DEBUG: TCPDF is present.\n";

    echo "DEBUG: Instantiating AIF_PDF...\n";
    $pdf = new AIF_PDF('P', 'mm', 'A4');
    echo "DEBUG: AIF_PDF instantiated.\n";

    echo "DEBUG: Generating test page...\n";
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Test PDF Document - ' . date('Y-m-d H:i:s'), 0, 1);
    $pdfData = $pdf->Output('test.pdf', 'S');
    echo "DEBUG: PDF generated (" . strlen($pdfData) . " bytes).\n";

    echo "DEBUG: Testing Mail::sendWithAttachment to mete.burcak@gmx.at...\n";
    $res = Mail::sendWithAttachment('mete.burcak@gmx.at', 'AIFNET PDF Test Mail', 'This is a test email.', $pdfData, 'test_report.pdf');

    if ($res) {
        echo "SUCCESS: Email sent successfully!\n";
    } else {
        echo "ERROR: Email failed to send.\n";
        echo "DEBUG: Last Error: " . (Mail::$lastError ?? 'N/A') . "\n";
    }

} catch (Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . "\n";
    echo "LINE: " . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
