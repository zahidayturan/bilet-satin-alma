<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

$trips = searchActiveTrips($from, $to, $date, 10);

if (!isset($date) || empty($date)) {
    $date = date('Y-m-d');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="search-back-container">
  <p style="text-align: center;padding:0 10px;">Aradığınız bütün seferler <strong>Bana1Bilet</strong> ile hızlı bir şekilde sizinle</p>

  <div class="search-container">
    <form method="GET" action="search.php" class="search-form">
      <div>
        <label>Nereden</label>
        <input type="text" name="from" placeholder="Ankara" required>
      </div>
      <div>
        <label>Nereye</label>
        <input type="text" name="to" placeholder="İstanbul" required>
      </div>
      <div>
        <label>Tarih</label>
        <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
      </div>
      <button type="submit" class="search-button">Sefer Ara</button>
    </form>
  </div>
</div>

<div style="margin-top:20px;margin-bottom:20px;">
  <div class="card-wrapper">
    <div class="card-container">
      <p class="card-title">Yolculuk Başlasın, Bilet Hazır.</p>
      <p class="card-text">Dakikalar İçinde Güvenli Otobüs Bileti Alışverişi. Bana1Bilet ile Yola Çıkmak Çok Kolay.</p>
    </div>

    <div class="card-container">
      <p class="card-title">En Uygunu, En Yakınınızda.</p>
      <p class="card-text">Tüm Otobüs Firmaları Tek Platformda. Karşılaştır, Seç, En İyi Fiyatı Yakala.</p>
    </div>

    <div class="card-container">
      <p class="card-title">Gönül Rahatlığıyla Seyahat.</p>
      <p class="card-text">Güvenilir Ödeme, Anında Bilet ve Kesintisiz Destek. Yolculuğun Keyfini Çıkarın.</p>
    </div>

  </div>
</div>


<?php if ($trips): ?>
  <div class="table-container">
      <h3 style="text-align: center;color:#224A59;margin-bottom:8px;">Yakın Tarihli Seferler</h3>
    <table>
    <tr>
      <th>Firma</th>
      <th>Nereden → Nereye</th>
      <th>Kalkış Tarihi</th>
      <th>Sefer Süresi</th>
      <th>Fiyat</th>
      <th></th>
    </tr>
    <?php foreach ($trips as $trip): ?>
      <?php
        // Sefer süresi hesaplama
        $departure_time = strtotime($trip['departure_time']);
        $arrival_time = strtotime($trip['arrival_time']);
        $duration = $arrival_time - $departure_time;
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
      ?>
      <tr>
        <td><?= htmlspecialchars($trip['company_name']) ?></td>
        <td><strong><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></strong></td>
        <td><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></td>
        <td>
          <?php
            echo $hours . ' saat';
            if ($minutes > 0) {
                echo ' ' . $minutes . ' dakika';
            }
          ?>
        </td>
        <td><strong><?= htmlspecialchars($trip['price']) ?> ₺</strong></td>
        <td>
          <a href="trip_detail.php?id=<?= urlencode($trip['id']) ?>" style="text-decoration: underline;">Seferi Görüntüle</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>

<?php else: ?>
  <h3 style="text-align: center;color:#224A59;margin-bottom:12px;">Uygun sefer bulunamadı</h3>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>