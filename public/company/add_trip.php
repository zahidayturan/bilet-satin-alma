<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_data = [
        'departure_city' => $_POST['departure_city'] ?? '',
        'destination_city' => $_POST['destination_city'] ?? '',
        'departure_time' => $_POST['departure_time'] ?? '',
        'arrival_time' => $_POST['arrival_time'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'capacity' => $_POST['capacity'] ?? 0,
    ];

    $result = createNewTrip($company_id, $trip_data);
    
    if ($result['success']) {
        // Başarılıysa, mesajı ile birlikte sefer listesi sayfasına yönlendir
        header('Location: trips.php?success=' . urlencode($result['message']));
        exit;
    } else {
        $errorMsg = $result['message'];
    }
}

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>+ Yeni Sefer Ekle</h2>
<a href="trips.php">← Geri</a>
<hr>

<?php if ($errorMsg): ?><div class="error">❌ <?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

<form method="POST">
  <label>Kalkış:</label> <input type="text" name="departure_city" value="<?= htmlspecialchars($_POST['departure_city'] ?? '') ?>" required><br><br>
  <label>Varış:</label> <input type="text" name="destination_city" value="<?= htmlspecialchars($_POST['destination_city'] ?? '') ?>" required><br><br>
  <label>Kalkış Zamanı:</label> <input type="datetime-local" name="departure_time" value="<?= htmlspecialchars($_POST['departure_time'] ?? '') ?>" required><br><br>
  <label>Varış Zamanı:</label> <input type="datetime-local" name="arrival_time" value="<?= htmlspecialchars($_POST['arrival_time'] ?? '') ?>" required><br><br>
  <label>Fiyat:</label> <input type="number" name="price" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" min="1" required><br><br>
  <label>Kapasite:</label> <input type="number" name="capacity" value="<?= htmlspecialchars($_POST['capacity'] ?? '') ?>" min="1" required><br><br>
  <button type="submit">Kaydet</button>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>