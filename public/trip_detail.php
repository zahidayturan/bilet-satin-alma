<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz sefer ID");

$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
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
</head>
<body>
<h2>Sefer Detayı</h2>

<p><strong>Kalkış:</strong> <?= htmlspecialchars($trip['departure_city']) ?></p>
<p><strong>Varış:</strong> <?= htmlspecialchars($trip['destination_city']) ?></p>
<p><strong>Kalkış Saati:</strong> <?= htmlspecialchars($trip['departure_time']) ?></p>
<p><strong>Fiyat:</strong> <?= htmlspecialchars($trip['price']) ?> ₺</p>
<p><strong>Kapasite:</strong> <?= $trip['capacity'] ?> koltuk</p>
<p><strong>Dolu Koltuk:</strong> <?= $dolu ?></p>
<p><strong>Boş Koltuk:</strong> <?= $bos ?></p>

<?php if ($bos <= 0): ?>
  <p style="color:red;">Bu seferde boş koltuk kalmadı.</p>
<?php elseif (isLoggedIn()): ?>
  <a href="buy_ticket.php?id=<?= urlencode($trip['id']) ?>">🎟️ Bilet Satın Al</a>
<?php else: ?>
  <p style="color:red;">Bilet satın almak için <a href="login.php">giriş yapın</a>.</p>
<?php endif; ?>

<a href="index.php">← Ana Sayfaya Dön</a>
</body>
</html>
