<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

if (!$from || !$to || !$date) {
    header("Location: index.php");
    exit;
}

$trips = searchActiveTrips($from, $to, $date, 30);

function calculateDuration($departure, $arrival) {
    $departure_time = strtotime($departure);
    $arrival_time = strtotime($arrival);
    $duration = $arrival_time - $departure_time;
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    return $hours . ' saat ' . ($minutes > 0 ? $minutes . ' dakika' : '');
}
?>

<div class="search-back-container">
    <div class="search-container">
        <form method="GET" class="search-form">
            <div>
            <label>Nereden</label>
            <input type="text" name="from" value="<?= htmlspecialchars($from) ?>" required>
            </div>
            <div>
            <label>Nereye</label>
            <input type="text" name="to" value="<?= htmlspecialchars($to) ?>" required>
            </div>
            <div>
            <label>Tarih</label>
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
            </div>
            <button type="submit" class="search-button">Sefer Ara</button>
        </form>
    </div>
</div>

<h2 style="text-align:center;">🔎 Sefer Arama Sonuçları</h2>

<?php if ($trips && count($trips) > 0): ?>
  <div class="table-container">
    <h3 style="text-align: center;">Bulunan Seferler</h3>
    <table>
      <tr>
        <th>Firma</th>
        <th>Nereden → Nereye</th>
        <th>Kalkış</th>
        <th>Süre</th>
        <th>Fiyat</th>
        <th></th>
      </tr>
      <?php foreach ($trips as $trip): ?>
        <tr>
          <td><?= htmlspecialchars($trip['company_name']) ?></td>
          <td><strong><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></strong></td>
          <td><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></td>
          <td><?= calculateDuration($trip['departure_time'], $trip['arrival_time']) ?></td>
          <td><strong><?= htmlspecialchars($trip['price']) ?> ₺</strong></td>
          <td><a href="trip_detail.php?id=<?= urlencode($trip['id']) ?>">Seferi Görüntüle</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php else: ?>
  <h4 style="text-align:center;color:red;">Uygun sefer bulunamadı</h4>

  <?php
  $suggested = getSuggestedTrips($from, $to);
  ?>

  <?php if ($suggested): ?>
    <div class="table-container" style="margin-top:24px;">
      <h3 style="text-align:center;color:#224A59;">Aynı Rotada Yakın Tarihli Seferler</h3>
      <table>
        <tr>
          <th>Firma</th>
          <th>Nereden → Nereye</th>
          <th>Kalkış</th>
          <th>Süre</th>
          <th>Fiyat</th>
          <th></th>
        </tr>
        <?php foreach ($suggested as $trip): ?>
          <tr>
            <td><?= htmlspecialchars($trip['company_name']) ?></td>
            <td><strong><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></strong></td>
            <td><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></td>
            <td><?= calculateDuration($trip['departure_time'], $trip['arrival_time']) ?></td>
            <td><strong><?= htmlspecialchars($trip['price']) ?> ₺</strong></td>
            <td><a href="trip_detail.php?id=<?= urlencode($trip['id']) ?>">Seferi Görüntüle</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
