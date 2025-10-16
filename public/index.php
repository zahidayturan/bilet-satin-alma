<?php
require_once __DIR__ . '/../includes/db.php';

require_once __DIR__ . '/../includes/functions.php'; // Yeni fonksiyonlar için

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

// İş mantığını fonksiyona devret
$trips = searchActiveTrips($from, $to, $date, 10);
?>

<?php 
// Header ve Footer, dış dosyalardan çağrıldığı için HTML'in içinde kalabilir.
require_once __DIR__ . '/../includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Otobüs Bileti Satın Alma</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav style="margin-bottom: 15px;">
<?php if (isLoggedIn()): ?>
  <div style="background:#f3f3f3;padding:10px;border-radius:8px;">
    <?php $role = $_SESSION['user']['role'] ?? ''; ?>
    <?php if ($role === 'admin'): ?>
      <span>Admin - Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span> |
      <a href="profile.php">Profilim</a> |
      <a href="admin_panel.php">Admin Paneli</a> |
      <a href="admin_firmas.php">Firmalar</a> |
      <a href="admin_firma_admin.php">Firma Adminleri</a> |
      <a href="admin_coupons.php">Bütün Kuponlar</a> |
      <a href="logout.php">Oturumu Kapat</a>
    <?php elseif ($role === 'company'): ?>
      <span>Firma - Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span> |
      <a href="profile.php">Profilim</a> |
      <a href="company_panel.php">Firma Paneli</a> |
      <a href="company_trips.php">Seferlerim</a> |
      <a href="company_coupons.php">Firma Kuponları</a> |
      <a href="company_tickets.php">Biletler</a> |
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
        // Sefer süresi hesaplama (Sunum mantığı)
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

</body>
</html>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>