<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/fpdf.php'; // FPDF kütüphanesi

$user_id = $_SESSION['user']['id'];
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) die("Geçersiz ID");

$stmt = $pdo->prepare("
SELECT t.id, t.total_price, tr.departure_city, tr.destination_city, tr.departure_time, u.full_name
FROM Tickets t
JOIN Trips tr ON t.trip_id = tr.id
JOIN User u ON t.user_id = u.id
WHERE t.id = ? AND u.id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) die("Bilet bulunamadı");

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Otobüs Bileti', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Yolcu: {$data['full_name']}", 0, 1);
$pdf->Cell(0, 10, "Kalkış: {$data['departure_city']}", 0, 1);
$pdf->Cell(0, 10, "Varış: {$data['destination_city']}", 0, 1);
$pdf->Cell(0, 10, "Tarih: {$data['departure_time']}", 0, 1);
$pdf->Cell(0, 10, "Fiyat: {$data['total_price']} ₺", 0, 1);

$pdf->Output("D", "bilet_{$data['id']}.pdf");
exit;
