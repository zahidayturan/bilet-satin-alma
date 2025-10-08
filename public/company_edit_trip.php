<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];
$id = $_GET['id'] ?? null;

// Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE Trips SET departure_city=?, destination_city=?, departure_time=?, arrival_time=?, price=?, capacity=? 
                           WHERE id=? AND company_id=?");
    $stmt->execute([
        $_POST['departure_city'], $_POST['destination_city'], $_POST['departure_time'],
        $_POST['arrival_time'], $_POST['price'], $_POST['capacity'], $_POST['id'], $company_id
    ]);
    header('Location: company_trips.php');
    exit;
}

// Mevcut sefer
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id=? AND company_id=?");
$stmt->execute([$id, $company_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı");
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

<form method="POST">
  <input type="hidden" name="id" value="<?= htmlspecialchars($trip['id']) ?>">
  <label>Kalkış:</label> <input type="text" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>"><br><br>
  <label>Varış:</label> <input type="text" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>"><br><br>
  <label>Kalkış Zamanı:</label> <input type="datetime-local" name="departure_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['departure_time'])) ?>"><br><br>
  <label>Varış Zamanı:</label> <input type="datetime-local" name="arrival_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['arrival_time'])) ?>"><br><br>
  <label>Fiyat:</label> <input type="number" name="price" value="<?= htmlspecialchars($trip['price']) ?>"><br><br>
  <label>Kapasite:</label> <input type="number" name="capacity" value="<?= htmlspecialchars($trip['capacity']) ?>"><br><br>
  <button type="submit">Kaydet</button>
</form>
</body>
</html>
