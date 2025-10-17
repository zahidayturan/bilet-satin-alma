<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz sefer ID");

$trip = getTripDetailsWithCompanyName($id);
if (!$trip) die("Sefer bulunamadı");

$dolu = getActiveBookedCountForTrip($id);
$bos = $trip['capacity'] - $dolu;

$page_title = "Sefer Detayları";
require_once __DIR__ . '/../includes/header.php';
?>

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
        <p class="error">Maalesef, bu seferde boş koltuk kalmamış.</p>
    <?php elseif (isLoggedIn()): ?>
        <div class="ticket-action">
            <a href="buy_ticket.php?id=<?= urlencode($trip['id']) ?>" class="button">🎟️ Bilet Satın Al</a>
        </div>
    <?php else: ?>
        <p class="error">Bilet satın almak için <a href="login.php">giriş yapın</a>.</p>
    <?php endif; ?>

    <div class="back-link" style="margin-top: 20px;">
        <a href="index.php">← Ana Sayfaya Dön</a>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
