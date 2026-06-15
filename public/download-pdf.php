<?php
// Require the FPDF library
require('../src/fpdf/fpdf.php');

// Capture the form data (Default to 'Anonymous' if accessed directly)
$organizerName = $_POST['organizer_name'] ?? 'Organizer';
$organizerPhone = $_POST['organizer_phone'] ?? 'N/A';
$organizerPledge = $_POST['organizer_pledge'] ?? '0';

// Initialize PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// Add Logos (Forced to exactly the same size: 22 width)
// Placed at X=15 (Left) and X=173 (Right)
if(file_exists('assets/logo.png')) { $pdf->Image('assets/logo.png', 15, 10, 22); }
if(file_exists('assets/emblem.png')) { $pdf->Image('assets/emblem.png', 173, 10, 22); }

// Move cursor to Y=12 to start the text nicely aligned with the top of the logos
$pdf->SetY(12);

// Title (Bold & Centered)
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 8, 'CAPTS. JOHN & ROSE MUNANGWE RETIREMENT', 0, 1, 'C');

// Paragraph (Bold & Centered)
$pdf->SetFont('helvetica', 'B', 10);
$text = "We, Capts. John & Rose Munangwe, along with the organizing team, kindly request your financial support for our upcoming Retirement Celebration.";

// We constrain the text width to 130 and shift it right by 40 so it doesn't touch the logos
$pdf->SetX(40);
$pdf->MultiCell(130, 5, $text, 0, 'C');

$pdf->Ln(3);

// Event Details (All Bold & Centered)
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 6, 'Event Date: 8th November 2026', 0, 1, 'C');
$pdf->Cell(0, 6, 'Location: Viyalo Corps - Mbale Division', 0, 1, 'C');
$pdf->Cell(0, 6, 'Budget Target: Kshs. 550,000/=', 0, 1, 'C');

$pdf->Ln(6);

// Captured Organizer Data Box
$pdf->SetFillColor(240, 240, 240); // Light gray background
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, "OFFICIAL COLLECTION SHEET ISSUED TO:", 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 8, "Name: " . $organizerName, 1, 0, 'L');
$pdf->Cell(60, 8, "Phone: " . $organizerPhone, 1, 0, 'L');
$pdf->Cell(60, 8, "Personal Pledge: Kshs " . number_format((float)$organizerPledge), 1, 1, 'L');

$pdf->Ln(8);

// Table Headers
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(10, 8, '#', 1, 0, 'C');
$pdf->Cell(60, 8, 'Contributor Name', 1, 0, 'C');
$pdf->Cell(40, 8, 'Phone Number', 1, 0, 'C');
$pdf->Cell(40, 8, 'Amount (Kshs)', 1, 0, 'C');
$pdf->Cell(40, 8, 'Signature', 1, 1, 'C');

// Generate 30 empty rows (Ensures it perfectly fits on ONE page)
$pdf->SetFont('helvetica', '', 10);
for ($i = 1; $i <= 30; $i++) {
    $pdf->Cell(10, 6, $i, 1, 0, 'C');
    $pdf->Cell(60, 6, '', 1, 0);
    $pdf->Cell(40, 6, '', 1, 0);
    $pdf->Cell(40, 6, '', 1, 0);
    $pdf->Cell(40, 6, '', 1, 1);
}

// Output the PDF to the browser as a download
$pdf->Output('D', 'Munangwe_Collection_Sheet_' . str_replace(' ', '_', $organizerName) . '.pdf');
?>