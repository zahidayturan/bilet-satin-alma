<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

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

$currentStatus = strtolower($data['status']);

// ----------------------------------------------------
// Bilet Durumuna Göre Metin ve Vurgu Ayarları
// ----------------------------------------------------

$statusMap = [
    'active' => 'Aktif (Onaylandı)',
    'canceled' => 'İptal Edildi',
    'expired' => 'Süresi Geçmiş',
];
$ticketStatus = $statusMap[$currentStatus] ?? ucfirst($data['status']);

$statusNote = '';
$statusColor = '#0056b3'; // Varsayılan mavi
$priceHighlightColor = '#d9534f'; // Varsayılan kırmızı

switch ($currentStatus) {
    case 'active':
        $statusNote = 'Lütfen kalkıştan 30 dakika önce terminalde olunuz. Bu belge geçerli biletinizdir.';
        $statusColor = '#5cb85c'; // Yeşil
        break;
    case 'canceled':
        $statusNote = 'Bu bilet iptal edilmiştir. Seyahat için geçerli değildir. Detaylı bilgi için müşteri hizmetleri ile görüşün.';
        $statusColor = '#f0ad4e'; // Turuncu
        $priceHighlightColor = '#333'; // İptalde fiyat vurgusunu azalt
        break;
    case 'expired':
        $statusNote = 'Bu biletin kalkış tarihi geçmiştir. Seyahat için kullanılamaz.';
        $statusColor = '#d9534f'; // Kırmızı
        $priceHighlightColor = '#333'; // Süresi geçmişte fiyat vurgusunu azalt
        break;
    default:
        $statusNote = 'Bilet durumu beklenmedik bir değerdedir. Lütfen kontrol ediniz.';
        $statusColor = '#777'; // Gri
        break;
}

// Zaman formatını düzenleme
$departureTime = (new DateTime($data['departure_time']))->format('d/m/Y H:i');
$arrivalTime = (new DateTime($data['arrival_time']))->format('d/m/Y H:i');
$purchaseDate = (new DateTime($data['ticket_created_at']))->format('d/m/Y H:i');


$mpdf = new Mpdf([
    'default_font' => 'dejavusans',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
]);

$html = "
<!DOCTYPE html>
<html>
<head>
    <title>Otobüs Bileti</title>
    <style>
        body {
            font-family: 'dejavusans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
        }
        .ticket-container {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            border: 2px solid #0056b3; 
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: linear-gradient(145deg, #ffffff, #f7f7f7);
            overflow: hidden;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }
        .section-route {
            padding: 20px;
            background-color: #e9f7ff;
            text-align: center;
            border-bottom: 2px dashed #0056b3;
        }
        .section-route .city {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            display: inline-block;
            padding: 0 15px;
        }
        .section-route .arrow {
            font-size: 24px;
            color: #007bff;
        }
        .route-info {
            margin-top: 10px;
            font-size: 16px;
            color: #555;
        }
        .details {
            display: table;
            width: 100%;
            padding: 20px 0;
        }
        .detail-row {
            display: table-row;
        }
        .detail-item {
            display: table-cell;
            width: 50%;
            padding: 10px 20px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .detail-item:nth-child(even) {
            border-left: 1px dashed #ccc;
        }
        .detail-item strong {
            display: block;
            color: #0056b3;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .detail-item span {
            font-size: 16px;
            color: #333;
        }
        .footer {
            background-color: #333;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 14px;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        .highlight {
            font-size: 18px !important;
            font-weight: bold;
            color: {$priceHighlightColor} !important; /* Dinamik Fiyat Rengi */
        }
        .status-badge {
            font-size: 16px !important;
            font-weight: bold;
            color: white !important;
            background-color: {$statusColor}; /* Dinamik Durum Rengi */
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .note-text {
             font-size: 14px;
             color: {$statusColor}; /* Not Rengi Durumla aynı */
             font-style: italic;
        }
        /* mPDF'ye özel tablo stilini zorlama */
        .ticket-container { display: block; }
        .details { border-collapse: collapse; width: 100%; table-layout: fixed;}
        .detail-row { page-break-inside: avoid; }
        .detail-item { width: 50%; padding: 10px 20px; }
    </style>
</head>
<body>
    <div class='ticket-container'>
        <div class='header'>
            <h1>BANA1BİLET</h1>
            <span>Otobüs Biletiniz</span>
        </div>

        <div class='section-route'>
            <span class='city'>{$data['departure_city']}</span>
            <span class='arrow'>&rarr;</span>
            <span class='city'>{$data['destination_city']}</span>
            <div class='route-info'>
                <span><strong>Firma:</strong> {$data['company_name']} | <strong>Bilet No:</strong> {$data['id']}</span>
            </div>
        </div>

        <div class='details'>
            <div class='detail-row'>
                <div class='detail-item'>
                    <strong>Yolcu Adı Soyadı</strong>
                    <span>{$data['full_name']}</span>
                </div>
                <div class='detail-item'>
                    <strong>Kalkış Tarihi & Saati</strong>
                    <span class='highlight'>{$departureTime}</span>
                </div>
            </div>

            <div class='detail-row'>
                <div class='detail-item'>
                    <strong>Koltuk Numaraları</strong>
                    <span class='highlight'>{$seatList}</span>
                </div>
                <div class='detail-item'>
                    <strong>Varış Tarihi & Saati</strong>
                    <span>{$arrivalTime}</span>
                </div>
            </div>
            
            <div class='detail-row'>
                <div class='detail-item'>
                    <strong>Toplam Ücret</strong>
                    <span class='highlight'>{$data['total_price']} ₺</span>
                </div>
                <div class='detail-item'>
                    <strong>Bilet Durumu</strong>
                    <span class='status-badge'>{$ticketStatus}</span>
                </div>
            </div>

            <div class='detail-row'>
                <div class='detail-item'>
                    <strong>Satın Alma Tarihi</strong>
                    <span>{$purchaseDate}</span>
                </div>
                <div class='detail-item'>
                    <strong>Önemli Not:</strong>
                    <span class='note-text'>{$statusNote}</span>
                </div>
            </div>
        </div>

        <div class='footer'>
            İyi yolculuklar dileriz! | Biletinizi yanınızda bulundurunuz.
        </div>
    </div>
</body>
</html>
";

$mpdf->WriteHTML($html);
$mpdf->Output("Bana1Bilet_{$data['id']}.pdf", "D");
exit;