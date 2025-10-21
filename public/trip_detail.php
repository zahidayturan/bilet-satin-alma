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
    <div style="margin-bottom: 20px;">
        <a href="index.php">← Ana Sayfaya Dön</a>
    </div>
    <h2>Sefer Detayları</h2>
    <div class="trip-details">
        <?php
            // Sefer süresi hesaplama
            $departure_time = strtotime($trip['departure_time']);
            $arrival_time = strtotime($trip['arrival_time']);
            $duration = $arrival_time - $departure_time;
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
        ?>
        <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor') ?></p>
        <p><strong>Kalkış Şehri:</strong> <?= htmlspecialchars($trip['departure_city']) ?></p>
        <p><strong>Varış Şehri:</strong> <?= htmlspecialchars($trip['destination_city']) ?></p>
        <p><strong>Kalkış Zamanı:</strong> <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></p>
        <p><strong>Varış Zamanı:</strong> <?= date('d.m.Y H:i', strtotime($trip['arrival_time'])) ?></p>
        <p><strong>Sefer Süresi: </strong><?= $hours ?> saat <?= $minutes ?> dakika</p>
        <p><strong>Fiyat:</strong> <?= htmlspecialchars($trip['price']) ?> ₺</p>
        <p><strong>Kapasite:</strong> <?= $trip['capacity'] ?> koltuk</p>
        <p><strong>Dolu Koltuk Sayısı:</strong> <?= $dolu ?> koltuk</p>
        <p><strong>Boş Koltuk Sayısı:</strong> <?= $bos ?> koltuk</p>
    </div>

    <?php if (isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'user'): ?>
        <?php if ($bos <= 0): ?>
            <p class="error">Maalesef, bu seferde boş koltuk kalmamış.</p>
        <?php else: ?>
            <a href="buy_ticket.php?id=<?= urlencode($trip['id']) ?>" >
                <button class="form-button">🎟️ Bilet Satın Al</button>
            </a>    
        <?php endif; ?>
    <?php elseif (!isLoggedIn()): ?>
        <p class="error">Bilet satın almak için <a href="login.php">giriş yapın</a>.</p>
    <?php endif; ?> 
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
