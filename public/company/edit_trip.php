<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$id = $_GET['id'] ?? null;

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

// 1. Mevcut sefer bilgisini getir
$trip = getTripDetailsForCompany($id, $company_id);
if (!$trip) {
    $error[] = 'Düzenlenecek sefer bulunamadı veya size ait değil.';
}

// 2. Dolu koltuk sayısını hesapla
$dolu_koltuk = getBookedSeatCountForTrip($id);

// 3. Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $post_id = $_POST['id'] ?? null; 
    $update_data = [
        'departure_city' => $_POST['departure_city'],
        'destination_city' => $_POST['destination_city'],
        'departure_time' => $_POST['departure_time'],
        'arrival_time' => $_POST['arrival_time'],
        'price' => $_POST['price'],
        'capacity' => $_POST['capacity'],
    ];

    $result = updateTripDetails($post_id, $company_id, $update_data, $dolu_koltuk);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'] ?? 'Sefer başarıyla güncellendi.';
    } else {
        $_SESSION['error_message'] = $result['message']; 
    }

    header("Location: edit_trip.php?id=" . urlencode($id));
    exit;
}

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="trips.php">← Firma Seyahatlerine Dön</a></div>

<?php require_once __DIR__ . '/../../includes/message_comp.php'; ?>

<?php if ($trip): ?>
<div class="container">
    <h2>✏️ Sefer Düzenle</h2>
    <p><strong>Dolu Koltuk Sayısı:</strong> <?= $dolu_koltuk ?> / <?= htmlspecialchars($trip['capacity']) ?></p>

    <form method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($trip['id']) ?>">

        <label>Kalkış</label>
        <input type="text" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>" required><br><br>

        <label>Varış</label>
        <input type="text" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>" required><br><br>

        <label>Kalkış Zamanı</label>
        <input type="datetime-local" name="departure_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['departure_time'])) ?>" required><br><br>

        <label>Varış Zamanı</label>
        <input type="datetime-local" name="arrival_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['arrival_time'])) ?>" required><br><br>

        <label>Fiyat (₺)</label>
        <input type="number" name="price" value="<?= htmlspecialchars($trip['price']) ?>" min="1" required><br><br>

        <label>Kapasite</label>
        <input type="number" name="capacity" value="<?= htmlspecialchars($trip['capacity']) ?>" min="<?= $dolu_koltuk ?>" required>
        <small>(Minimum: <?= $dolu_koltuk ?>)</small><br><br>

        <button class="form-button" type="submit">Kaydet</button>
    </form>
</div>
<?php else: ?>
    <p style="text-align:center;margin-top:20px;">Sefer bulunamadı.</p>
    <a href="/index.php"><p style="text-align:center;">Ana Sayfaya Dön</p></a>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>