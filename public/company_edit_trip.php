<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);

require_once __DIR__ . '/../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$id = $_GET['id'] ?? null;
$errorMsg = '';

// 1. Mevcut sefer bilgisini getir
$trip = getTripDetailsForCompany($id, $company_id);
if (!$trip) {
    // Ölmek yerine daha güvenli bir yönlendirme yapabiliriz.
    header('Location: company_trips.php?error=' . urlencode('Düzenlenecek sefer bulunamadı veya size ait değil.'));
    exit;
}

// 2. Dolu koltuk sayısını hesapla
$dolu_koltuk = getBookedSeatCountForTrip($id);

// 3. Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Güvenlik için POST edilen ID'yi tekrar kontrol ediyoruz (zorunlu değil ama iyi pratik)
    $post_id = $_POST['id'] ?? null; 

    // Güncelleme verilerini hazırlıyoruz
    $update_data = [
        'departure_city' => $_POST['departure_city'],
        'destination_city' => $_POST['destination_city'],
        'departure_time' => $_POST['departure_time'],
        'arrival_time' => $_POST['arrival_time'],
        'price' => $_POST['price'],
        'capacity' => $_POST['capacity'],
    ];

    // Güncelleme fonksiyonunu çağır ve sonucu al
    $result = updateTripDetails($post_id, $company_id, $update_data, $dolu_koltuk);

    if ($result['success']) {
        // Başarılı yönlendirme
        header('Location: company_trips.php?success=' . urlencode($result['message'] ?? 'Sefer başarıyla güncellendi.'));
        exit;
    } else {
        // Hata durumunda mesajı kaydet ve formu yeniden göster
        $errorMsg = $result['message'];
        
        // Hata durumunda formu doldurmak için $trip dizisini POST verileriyle güncelleyebiliriz
        // Kullanıcının girdiği verilerin kaybolmaması için:
        $trip['departure_city'] = $update_data['departure_city'];
        $trip['destination_city'] = $update_data['destination_city'];
        $trip['departure_time'] = $update_data['departure_time'];
        $trip['arrival_time'] = $update_data['arrival_time'];
        $trip['price'] = $update_data['price'];
        $trip['capacity'] = $update_data['capacity'];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sefer Düzenle</title>
  <style>
    .error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffdddd; }
  </style>
</head>
<body>
<h2>✏️ Sefer Düzenle</h2>
<a href="company_trips.php">← Geri</a>
<hr>

<?php if ($errorMsg): ?>
    <div class="error">❌ <?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<p><strong>Dolu Koltuk Sayısı:</strong> <?= $dolu_koltuk ?> / <?= htmlspecialchars($trip['capacity']) ?></p>

<form method="POST">
  <input type="hidden" name="id" value="<?= htmlspecialchars($trip['id']) ?>">

  <label>Kalkış:</label>
  <input type="text" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>" required><br><br>

  <label>Varış:</label>
  <input type="text" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>" required><br><br>

  <label>Kalkış Zamanı:</label>
  <input type="datetime-local" name="departure_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['departure_time'])) ?>" required><br><br>

  <label>Varış Zamanı:</label>
  <input type="datetime-local" name="arrival_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['arrival_time'])) ?>" required><br><br>

  <label>Fiyat (₺):</label>
  <input type="number" name="price" value="<?= htmlspecialchars($trip['price']) ?>" min="1" required><br><br>

  <label>Kapasite:</label>
  <input type="number" name="capacity" value="<?= htmlspecialchars($trip['capacity']) ?>" min="<?= $dolu_koltuk ?>" required>
  <small>(Minimum: <?= $dolu_koltuk ?>)</small><br><br>

  <button type="submit">Kaydet</button>
</form>
</body>
</html>