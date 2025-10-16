<?php
require_once __DIR__ . '/../includes/db.php'; 

require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz sefer ID");

// 1. Sefer detaylarını fonksiyona devret
$trip = getTripDetailsWithCompanyName($id);
if (!$trip) die("Sefer bulunamadı");


// 2. Dolu koltuk sayısını fonksiyona devret
$dolu = getActiveBookedCountForTrip($id);
$bos = $trip['capacity'] - $dolu;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sefer Detayı</title>
  <link rel="stylesheet" href="style.css"> <style>
        .error-message { color: red; font-weight: bold; }
        /* Placeholder stil (mevcut stilinize göre düzenleyin) */
        .trip-details p { margin: 5px 0; }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
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
      <p class="error-message">Maalesef, bu seferde boş koltuk kalmamış.</p>
    <?php elseif (isLoggedIn()): ?>
      <div class="ticket-action">
        <a href="buy_ticket.php?id=<?= urlencode($trip['id']) ?>" class="button">🎟️ Bilet Satın Al</a>
      </div>
    <?php else: ?>
      <p class="error-message">Bilet satın almak için <a href="login.php">giriş yapın</a>.</p>
    <?php endif; ?>

    <div class="back-link" style="margin-top: 20px;">
      <a href="index.php">← Ana Sayfaya Dön</a>
    </div>
  </div>
</body>
</html>