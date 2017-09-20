<?php
// Include the main TCPDF library and TCPDI.
require_once('tcpdf.php');
require_once('tcpdi.php');

// Create new PDF document.
$pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Add a page from a PDF by file path.
/*$pdf->AddPage();
$pdf->setSourceFile('input.pdf');
$idx = $pdf->importPage(1);
$pdf->useTemplate($idx);*/

$pdfdata = file_get_contents('input.pdf'); // Simulate only having raw data available.
$pagecount = $pdf->setSourceData($pdfdata);

for ($i = 1; $i <= $pagecount; $i++) {
    $new_pdf = new TCPDI(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $new_pdf->AddPage();
    $new_pdf->setSourceData($pdfdata);
    $tplidx = $new_pdf->importPage($i);
    $new_pdf->useTemplate($tplidx);
    $new_pdf->Output('/home/alex/Bureau/test/output_'.$i.'.pdf', 'F');
}
//$pdf->Output('/home/alex/Bureau/test/output.pdf', 'F');