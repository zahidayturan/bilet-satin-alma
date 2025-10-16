<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) die("Geçersiz istek.");

$errorMsg = '';
$successMsg = '';

// 1. Sefer doğrulama
$trip = getTripDetailsForCompany($trip_id, $company_id);
if (!$trip) die("Bu sefer size ait değil veya bulunamadı.");

// 2. Bilet iptali işlemi
if (isset($_GET['cancel'])) {
    $ticket_id = $_GET['cancel'];

    $result = cancelTicketAndRefund($ticket_id, $company_id);

    if ($result['success']) {
        // İşlem başarılıysa sayfayı success parametresi ile yönlendir
        header("Location: trip_tickets.php?trip_id=" . urlencode($trip_id) . "&success=1");
        exit;
    } else {
        $errorMsg = $result['message'];
    }
}

// URL'den gelen başarı mesajını al
if (isset($_GET['success'])) {
    $successMsg = "Bilet başarıyla iptal edildi ve ücret iadesi yapıldı. ✅";
}

// 3. Seferin biletlerini getir
$tickets = getTripTickets($trip_id);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?> Biletleri</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #eee; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
<h2>🎟️ Bilet Listesi - <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></h2>
<a href="trips.php">← Geri</a>
<hr>

<?php if ($errorMsg): ?>
  <div class="error" style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div class="success" style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<table>
<tr>
    <th>Koltuk No</th>
    <th>Yolcu Adı</th>
    <th>Email</th>
    <th>Durum</th>
    <th>Ücret</th>
    <th>İşlem</th>
</tr>

<?php if ($tickets): ?>
    <?php foreach ($tickets as $tk): ?>
        <?php 
            // Kalkış zamanı kontrolü, iş mantığı olarak bu sayfada kalır.
            $hoursLeft = (strtotime($tk['departure_time']) - time()) / 3600; 
        ?>
    <tr>
        <td><?= htmlspecialchars($tk['seat_number'] ?? '-') ?></td>
        <td><?= htmlspecialchars($tk['full_name']) ?></td>
        <td><?= htmlspecialchars($tk['email']) ?></td>
        <td><?= htmlspecialchars(ucfirst($tk['status'])) ?></td>
        <td><?= htmlspecialchars($tk['total_price']) ?> ₺</td>
        <td>
          <?php if ($tk['status'] === 'active' && $hoursLeft > 1): ?>
            <a href="?trip_id=<?= urlencode($trip_id) ?>&cancel=<?= urlencode($tk['ticket_id']) ?>"
               onclick="return confirm('Bu bileti iptal edip yolcuya ücret iadesi yapmak istediğinize emin misiniz?')">
               ❌ İptal Et
            </a>
          <?php else: ?>
            <em>İptal Edilemez</em>
          <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6" style="text-align:center;">Bu sefere ait bilet bulunamadı.</td></tr>
<?php endif; ?>
</table>
</body>
</html>