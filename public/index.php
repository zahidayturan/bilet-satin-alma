<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

$trips = searchActiveTrips($from, $to, $date, 10);

require_once __DIR__ . '/../includes/header.php';
?>

<form method="GET">
  <label>Kalkış:</label> <input type="text" name="from" value="<?= htmlspecialchars($from) ?>">
  <label>Varış:</label> <input type="text" name="to" value="<?= htmlspecialchars($to) ?>">
  <label>Tarih:</label> <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
  <button type="submit">Sefer Ara</button>
</form>



<?php if ($trips): ?>
  <h2>Aktif Seferler</h2>
  <table>
    <tr>
      <th>Firma</th>
      <th>Kalkış</th>
      <th>Varış</th>
      <th>Kalkış Tarihi</th>
      <th>Sefer Süresi</th>
      <th>Fiyat</th>
      <th>Seferi Görüntüle</th>
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
        <td><?= htmlspecialchars($trip['departure_city']) ?></td>
        <td><?= htmlspecialchars($trip['destination_city']) ?></td>
        <td><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></td>
        <td><?= $hours ?> saat <?= $minutes ?> dakika</td>
        <td><?= htmlspecialchars($trip['price']) ?> ₺</td>
        <td>
          <a href="trip_detail.php?id=<?= urlencode($trip['id']) ?>">Detay</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p>Uygun sefer bulunamadı</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>