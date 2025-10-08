<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php'; // mPDF'yi dahil et

use Mpdf\Mpdf;

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

$mpdf = new Mpdf(['default_font' => 'dejavusans']); // Türkçe karakter dostu font

$html = "
<h2 style='text-align:center;'>Otobüs Bileti</h2>
<p><strong>Yolcu:</strong> {$data['full_name']}</p>
<p><strong>Kalkış:</strong> {$data['departure_city']}</p>
<p><strong>Varış:</strong> {$data['destination_city']}</p>
<p><strong>Tarih:</strong> {$data['departure_time']}</p>
<p><strong>Fiyat:</strong> {$data['total_price']} ₺</p>
";

$mpdf->WriteHTML($html);
$mpdf->Output("bilet_{$data['id']}.pdf", "D");
exit;
