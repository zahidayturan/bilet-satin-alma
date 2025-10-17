<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php'; // mPDF'yi dahil et

use Mpdf\Mpdf;

$user_id = $_SESSION['user']['id'];
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) die("Geçersiz ID");

// Tüm bilet detaylarını içeren sorgu
$stmt = $pdo->prepare("
SELECT 
    t.id,
    t.total_price,
    t.status,
    t.created_at AS ticket_created_at,
    tr.departure_city,
    tr.destination_city,
    tr.departure_time,
    tr.arrival_time,
    tr.price,
    bc.name AS company_name,
    u.full_name
FROM Tickets t
JOIN Trips tr ON t.trip_id = tr.id
JOIN Bus_Company bc ON tr.company_id = bc.id
JOIN User u ON t.user_id = u.id
WHERE t.id = ? AND u.id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Bilet bulunamadı");

// Koltuk numaralarını getir
$seatStmt = $pdo->prepare("SELECT seat_number FROM Booked_Seats WHERE ticket_id = ?");
$seatStmt->execute([$ticket_id]);
$seats = $seatStmt->fetchAll(PDO::FETCH_COLUMN);
$seatList = implode(', ', $seats);

$mpdf = new Mpdf(['default_font' => 'dejavusans']); // Türkçe karakter

$html = "
<h2 style='text-align:center;'>Otobüs Bileti</h2>
<p><strong>Bilet Numarası:</strong> {$data['id']}</p>
<p><strong>Yolcu:</strong> {$data['full_name']}</p>
<p><strong>Otobüs Firması:</strong> {$data['company_name']}</p>
<p><strong>Kalkış Şehri:</strong> {$data['departure_city']}</p>
<p><strong>Varış Şehri:</strong> {$data['destination_city']}</p>
<p><strong>Kalkış Zamanı:</strong> {$data['departure_time']}</p>
<p><strong>Varış Zamanı:</strong> {$data['arrival_time']}</p>
<p><strong>Koltuk Numaraları:</strong> {$seatList}</p>
<p><strong>Toplam Fiyat:</strong> {$data['total_price']} ₺</p>
<p><strong>Bilet Durumu:</strong> " . ucfirst($data['status']) . "</p>
<p><strong>Satın Alma Tarihi:</strong> {$data['ticket_created_at']}</p>
";

$mpdf->WriteHTML($html);
$mpdf->Output("bilet_{$data['id']}.pdf", "D");
exit;
