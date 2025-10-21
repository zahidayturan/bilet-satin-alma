<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];

$error = [];
$success = "";

if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

// Sefer silme işlemi (iptal + iade)
if (isset($_GET['delete'])) {
    $trip_id = $_GET['delete'];
    
    $result = cancelTripAndRefundTickets($trip_id, $company_id);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header("Location: trips.php");
    exit;
}

// Sefer listesi (koltuk doluluk bilgisiyle)
$trips = getCompanyTripsWithSoldCount($company_id);

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="panel.php">← Firma Paneline Dön</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<h2>🚌 Sefer Yönetimi</h2>

<div class="container">
    <a href="add_trip.php"><button class="form-button" style="margin: 0px;">+ Yeni Sefer Oluştur</button></a>
</div>

<div class="table-container" style="margin-top:20px;">
    <h3>Bütün Seferler</h3>
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
                    ❌ İptal Et
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </table>
</div>



<?php
require_once __DIR__ . '/../../includes/footer.php';
?>