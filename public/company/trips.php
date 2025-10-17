<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];

$errorMsg = '';
$successMsg = '';

// Sefer silme işlemi (iptal + iade)
if (isset($_GET['delete'])) {
    $trip_id = $_GET['delete'];
    
    $result = cancelTripAndRefundTickets($trip_id, $company_id);
    
    if ($result['success']) {
        $successMsg = $result['message'];
        // URL'yi temizleyerek POST/GET sonrası tekrar gönderim hatasını önleyebiliriz.
        header("Location: trips.php?success=" . urlencode($successMsg));
        exit;
    } else {
        $errorMsg = $result['message'];
    }
}

// URL'den gelen başarı mesajını al
if (isset($_GET['success'])) {
    $successMsg = htmlspecialchars($_GET['success']);
}

// Sefer listesi (koltuk doluluk bilgisiyle)
$trips = getCompanyTripsWithSoldCount($company_id);

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>


<body>
<h2>🚌 Sefer Yönetimi</h2>
<a href="panel.php">← Geri</a>
<hr>

<?php if ($errorMsg): ?><div style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
<?php if ($successMsg): ?><div style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>

<a href="add_trip.php">+ Yeni Sefer Ekle</a>

<table>
<tr>
    <th>Kalkış</th>
    <th>Varış</th>
    <th>Kalkış Saati</th>
    <th>Varış Saati</th>
    <th>Fiyat</th>
    <th>Kapasite</th>
    <th>Satılan Koltuk</th>
    <th>İşlemler</th>
</tr>

<?php if (empty($trips)): ?>
    <tr><td colspan="8" style="text-align:center;">Henüz hiç sefer eklenmemiş.</td></tr>
<?php else: ?>
    <?php foreach ($trips as $t): ?>
    <tr>
        <td><?= htmlspecialchars($t['departure_city']) ?></td>
        <td><?= htmlspecialchars($t['destination_city']) ?></td>
        <td><?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></td>
        <td><?= date('d.m.Y H:i', strtotime($t['arrival_time'])) ?></td>
        <td><?= htmlspecialchars($t['price']) ?> ₺</td>
        <td><?= htmlspecialchars($t['capacity']) ?></td>
        <td><?= htmlspecialchars($t['sold_count']) ?></td>
        <td class="actions">
            <a href="edit_trip.php?id=<?= urlencode($t['id']) ?>">✏️ Düzenle</a> |
            <a href="trip_tickets.php?trip_id=<?= urlencode($t['id']) ?>">🎟️ Biletleri Gör</a> |
            <a href="?delete=<?= urlencode($t['id']) ?>" 
                onclick="return confirm('Bu sefer iptal edilecek. Tüm yolculara ücret iadesi yapılacak. Emin misiniz?')">
                ❌ Seferi İptal Et
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</table>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>