<?php
session_start();
if(!isset($_SESSION['id']) || !in_array($_SESSION['role'],['Superadmin','Admin'])) exit;

require('fpdf/fpdf.php'); // You need to include the FPDF library

$db = mysqli_connect("localhost","root","","badminton_hub");
$s = mysqli_real_escape_string($db, $_GET['start_date']);
$e = mysqli_real_escape_string($db, $_GET['end_date']);

// Initialize PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);

// Header info
$pdf->Cell(0, 10, 'Revenue Report', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, $s . ' to ' . $e, 0, 1, 'L');
$pdf->Ln(10);

// Revenue by Court
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Revenue by Court', 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 8, 'Court', 1);
$pdf->Cell(50, 8, 'Bookings', 1);
$pdf->Cell(50, 8, 'Revenue (RM)', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$res = mysqli_query($db, "
    SELECT c.court_name, COUNT(b.id) AS cnt, COALESCE(SUM(p.final_amount),0) AS rev
    FROM bookings b
    JOIN courts c ON c.id = b.court_id
    LEFT JOIN payments p ON p.booking_id = b.id AND p.payment_status = 'success'
    WHERE b.booking_date BETWEEN '$s' AND '$e'
    GROUP BY c.id
");
while($r = mysqli_fetch_assoc($res)) {
    $pdf->Cell(80, 8, $r['court_name'], 1);
    $pdf->Cell(50, 8, $r['cnt'], 1);
    $pdf->Cell(50, 8, number_format($r['rev'], 2), 1);
    $pdf->Ln();
}

$pdf->Ln(10);

// Revenue by Payment Method
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Revenue by Payment Method', 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 8, 'Method', 1);
$pdf->Cell(50, 8, 'Transactions', 1);
$pdf->Cell(50, 8, 'Revenue (RM)', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$res = mysqli_query($db, "
    SELECT payment_method, COUNT(*) AS cnt, SUM(final_amount) AS rev
    FROM payments
    WHERE payment_status = 'success'
    AND DATE(payment_date) BETWEEN '$s' AND '$e'
    GROUP BY payment_method
");
while($r = mysqli_fetch_assoc($res)) {
    $pdf->Cell(80, 8, $r['payment_method'], 1);
    $pdf->Cell(50, 8, $r['cnt'], 1);
    $pdf->Cell(50, 8, number_format($r['rev'], 2), 1);
    $pdf->Ln();
}

$pdf->Ln(10);

// Booking Status
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Booking Status Breakdown', 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 8, 'Status', 1);
$pdf->Cell(100, 8, 'Total', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$res = mysqli_query($db, "
    SELECT status, COUNT(*) AS total
    FROM bookings
    WHERE booking_date BETWEEN '$s' AND '$e'
    GROUP BY status
");
while($r = mysqli_fetch_assoc($res)) {
    $pdf->Cell(80, 8, $r['status'], 1);
    $pdf->Cell(100, 8, $r['total'], 1);
    $pdf->Ln();
}

$pdf->Output('D', 'revenue_report_'.$s.'_to_'.$e.'.pdf');
?>