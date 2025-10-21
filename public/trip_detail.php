<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = [];
$success = "";

$id = $_GET['id'] ?? null;
if (!$id){
    $error[] = 'Bilgiler alınamadı';
};

$trip = getTripDetailsWithCompanyName($id);
if (!$trip){
    $error[] = 'Geçersiz sefer erişimi';
}else {
    $dolu = getActiveBookedCountForTrip($id);
    $bos = $trip['capacity'] - $dolu;
}

$page_title = "Bana1Bilet - Sefer Detayları";
require_once __DIR__ . '/../includes/header.php';
?>

<?php
    require_once __DIR__ . '/../includes/message_comp.php';
?>

<?php if ($trip): ?>
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
        <div class="info-wrapper">
            <div class="info-container">
                <p><strong>Firma<br></strong> <?= htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor') ?></p>
                <p><strong>Nereden - Nereye</strong><br> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></strong></p>
                <p><strong>Fiyat</strong><br> <?= htmlspecialchars($trip['price']) ?> ₺</p>
            </div>

            <div class="info-container">
                <p><strong>Kalkış Zamanı</strong><br> <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></p>
                <p><strong>Varış Zamanı</strong><br> <?= date('d.m.Y H:i', strtotime($trip['arrival_time'])) ?></p>
                <p><strong>Sefer Süresi</strong><br><?= $hours ?> saat <?= $minutes ?> dakika</p>
            </div>

            <div class="info-container">
                <p><strong>Kapasite</strong><br> <?= $trip['capacity'] ?> koltuk</p>
                <p><strong>Dolu Koltuk Sayısı</strong><br> <?= $dolu ?> koltuk</p>
                <p><strong>Boş Koltuk Sayısı</strong><br> <?= $bos ?> koltuk</p>
            </div>
        </div>
    </div>

    <?php if (isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'user'): ?>
        <?php if ($bos <= 0): ?>
            <p class="error">Maalesef, bu seferde boş koltuk kalmamış.</p>
        <?php else: ?>
            <a href="buy_ticket.php?id=<?= urlencode($trip['id']) ?>" >
                <button class="form-button" style="margin-top:20px">🎟️ Bilet Satın Al</button>
            </a>    
        <?php endif; ?>
    <?php elseif (!isLoggedIn()): ?>
        <a href="login.php"><button class="form-button">Bilet satın almak için <strong>Giriş Yapın</strong></button></a>
    <?php endif; ?> 
</div>
<?php else: ?>
    <p style="text-align:center;margin-top:20px;">Sefer bulunamadı.</p>
    <a href="/index.php"><p style="text-align:center;">Ana Sayfaya Dön</p></a>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
