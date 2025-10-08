<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date)
        VALUES (:id, :cid, :dep, :dest, :dtime, :atime, :price, :cap, datetime('now'))
    ");
    $stmt->execute([
        ':id' => uniqid('trip_'),
        ':cid' => $company_id,
        ':dep' => $_POST['departure_city'],
        ':dest' => $_POST['destination_city'],
        ':dtime' => $_POST['departure_time'],
        ':atime' => $_POST['arrival_time'],
        ':price' => $_POST['price'],
        ':cap' => $_POST['capacity']
    ]);

    header('Location: company_trips.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Yeni Sefer Ekle</title>
</head>
<body>
<h2>+ Yeni Sefer Ekle</h2>
<a href="company_trips.php">← Geri</a>
<hr>

<form method="POST">
  <label>Kalkış:</label> <input type="text" name="departure_city" required><br><br>
  <label>Varış:</label> <input type="text" name="destination_city" required><br><br>
  <label>Kalkış Zamanı:</label> <input type="datetime-local" name="departure_time" required><br><br>
  <label>Varış Zamanı:</label> <input type="datetime-local" name="arrival_time" required><br><br>
  <label>Fiyat:</label> <input type="number" name="price" required><br><br>
  <label>Kapasite:</label> <input type="number" name="capacity" required><br><br>
  <button type="submit">Kaydet</button>
</form>
</body>
</html>
