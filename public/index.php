<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

$trips = searchActiveTrips($from, $to, $date, 10);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isLoggedIn()): ?>
  <div style="background:#f3f3f3;padding:10px;border-radius:8px;">
    <?php $role = $_SESSION['user']['role'] ?? ''; ?>
    <?php if ($role === 'admin'): ?>
      <span>Admin - Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span> |
      <a href="profile.php">Profilim</a> |
      <a href="admin/panel.php">Admin Paneli</a> |
      <a href="admin/firmas.php">Firmalar</a> |
      <a href="admin/firma_admin.php">Firma Adminleri</a> |
      <a href="admin/coupons.php">Bütün Kuponlar</a> |
      <a href="logout.php">Oturumu Kapat</a>
    <?php elseif ($role === 'company'): ?>
      <span>Firma - Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span> |
      <a href="profile.php">Profilim</a> |
      <a href="company/panel.php">Firma Paneli</a> |
      <a href="company/trips.php">Seferlerim</a> |
      <a href="company/coupons.php">Firma Kuponları</a> |
      <a href="company/tickets.php">Biletler</a> |
      <a href="logout.php">Oturumu Kapat</a>
    <?php else: ?>
      <span>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span> |
      <a href="profile.php">Profilim</a> |
      <a href="my_tickets.php">Biletlerim</a> |
      <a href="logout.php">Oturumu Kapat</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <a href="login.php">Giriş Yap</a> | <a href="register.php">Kayıt Ol</a>
<?php endif; ?>
</nav>

<hr>

<form method="GET">
  <label>Kalkış:</label> <input type="text" name="from" value="<?= htmlspecialchars($from) ?>">
  <label>Varış:</label> <input type="text" name="to" value="<?= htmlspecialchars($to) ?>">
  <label>Tarih:</label> <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
  <button type="submit">Sefer Ara</button>
</form>

<hr>

<?php if ($trips): ?>
  <h2>Aktif Seferler</h2>
  <table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;">
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
  <p>Aktif sefer bulunamadı.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>