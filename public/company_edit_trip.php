<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];
$id = $_GET['id'] ?? null;

// Mevcut sefer bilgisi
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id=? AND company_id=?");
$stmt->execute([$id, $company_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı");

// Dolu koltuk sayısını hesapla (aktif biletler üzerinden)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT bs.seat_number) AS dolu_koltuk
    FROM Booked_Seats bs
    JOIN Tickets t ON bs.ticket_id = t.id
    WHERE t.trip_id = ? AND t.status = 'active'
");
$stmt->execute([$id]);
$dolu_koltuk = (int)$stmt->fetch(PDO::FETCH_ASSOC)['dolu_koltuk'];

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_capacity = (int)$_POST['capacity'];

    // Kapasite dolu koltuk sayısından küçük olamaz
    if ($new_capacity < $dolu_koltuk) {
        die("❌ Hata: Kapasite dolu koltuk sayısından (" . $dolu_koltuk . ") az olamaz!");
    }

    $stmt = $pdo->prepare("
        UPDATE Trips
        SET departure_city=?, destination_city=?, departure_time=?, arrival_time=?, price=?, capacity=?
        WHERE id=? AND company_id=?
    ");
    $stmt->execute([
        $_POST['departure_city'],
        $_POST['destination_city'],
        $_POST['departure_time'],
        $_POST['arrival_time'],
        $_POST['price'],
        $new_capacity,
        $_POST['id'],
        $company_id
    ]);

    header('Location: company_trips.php?updated=1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sefer Düzenle</title>
</head>
<body>
<h2>✏️ Sefer Düzenle</h2>
<a href="company_trips.php">← Geri</a>
<hr>

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
