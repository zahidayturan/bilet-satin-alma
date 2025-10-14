<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz sefer ID");

$stmt = $pdo->prepare("
    SELECT Trips.*, Bus_Company.name AS company_name 
    FROM Trips
    LEFT JOIN Bus_Company ON Trips.company_id = Bus_Company.id
    WHERE Trips.id = ?
");
$stmt->execute([$id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı");


// 🔍 Dolu koltuk sayısı
$stmt = $pdo->prepare("SELECT COUNT(*) AS dolu FROM Tickets WHERE trip_id = ? AND status = 'active'");
$stmt->execute([$id]);
$dolu = $stmt->fetch(PDO::FETCH_ASSOC)['dolu'];
$bos = $trip['capacity'] - $dolu;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sefer Detayı</title>
  <link rel="stylesheet" href="style.css"> <!-- Eğer varsa stil dosyanız -->
</head>
<body>
  <div class="container">
    <h2>Sefer Detayları</h2>

    <div class="trip-details">
      <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor') ?></p>
      <p><strong>Kalkış Şehri:</strong> <?= htmlspecialchars($trip['departure_city']) ?></p>
      <p><strong>Varış Şehri:</strong> <?= htmlspecialchars($trip['destination_city']) ?></p>
      <p><strong>Kalkış Saati:</strong> <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></p>
      <p><strong>Fiyat:</strong> <?= htmlspecialchars($trip['price']) ?> ₺</p>
      <p><strong>Kapasite:</strong> <?= $trip['capacity'] ?> koltuk</p>
      <p><strong>Dolu Koltuk Sayısı:</strong> <?= $dolu ?> koltuk</p>
      <p><strong>Boş Koltuk Sayısı:</strong> <?= $bos ?> koltuk</p>
    </div>

    <?php if ($bos <= 0): ?>
      <p class="error-message" style="color:red; font-weight: bold;">Maalesef, bu seferde boş koltuk kalmamış.</p>
    <?php elseif (isLoggedIn()): ?>
      <div class="ticket-action">
        <a href="buy_ticket.php?id=<?= urlencode($trip['id']) ?>" class="button">🎟️ Bilet Satın Al</a>
      </div>
    <?php else: ?>
      <p style="color:red; font-weight: bold;">Bilet satın almak için <a href="login.php">giriş yapın</a>.</p>
    <?php endif; ?>

    <div class="back-link">
      <a href="index.php">← Ana Sayfaya Dön</a>
    </div>
  </div>
</body>
</html>
