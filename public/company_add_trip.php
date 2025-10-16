<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);

require_once __DIR__ . '/../includes/functions.php';

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
        header('Location: company_trips.php?success=' . urlencode($result['message']));
        exit;
    } else {
        // Hata durumunda mesajı kaydet ve formu yeniden göster
        $errorMsg = $result['message'];
        
        // Hata durumunda kullanıcının girdiği verileri formda tutmak için $_POST'u kullanırız.
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Yeni Sefer Ekle</title>
    <style>
        .error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffdddd; }
    </style>
</head>
<body>
<h2>+ Yeni Sefer Ekle</h2>
<a href="company_trips.php">← Geri</a>
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
</body>
</html>