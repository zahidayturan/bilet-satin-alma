<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

// Sorguyu oluştur
$query = "
    SELECT Trips.*, Bus_Company.name AS company_name
    FROM Trips
    LEFT JOIN Bus_Company ON Trips.company_id = Bus_Company.id
    WHERE datetime(departure_time) > datetime('now')
";
$params = [];

// Filtreleme
if ($from !== '') {
    $query .= " AND departure_city LIKE :from";
    $params[':from'] = "%$from%";
}
if ($to !== '') {
    $query .= " AND destination_city LIKE :to";
    $params[':to'] = "%$to%";
}
if ($date !== '') {
    $query .= " AND DATE(departure_time) = :date";
    $params[':date'] = $date;
}

// En yakın 10 seferi listele
$query .= " ORDER BY departure_time ASC LIMIT 10";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Otobüs Bileti Satın Alma</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Menü -->
<nav style="margin-bottom: 15px;">
<?php if (isLoggedIn()): ?>
  <div style="background:#f3f3f3;padding:10px;border-radius:8px;">
    <span>Hoşgeldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span>
    <br><br>
    <?php $role = $_SESSION['user']['role'] ?? ''; ?>
    <?php if ($role === 'admin'): ?>
      <strong>🔧 Yönetim:</strong>
      <a href="profile.php">Profilim</a> |
      <a href="admin_panel.php">Admin Paneli</a> |
      <a href="admin_firmas.php">Firmalar</a> |
      <a href="admin_firma_admin.php">Firma Adminleri</a> |
      <a href="admin_coupons.php">Kuponlar</a> |
      <a href="logout.php">Çıkış</a>
    <?php elseif ($role === 'company'): ?>
      <strong>🏢 Firma:</strong>
      <a href="profile.php">Profilim</a> |
      <a href="company_panel.php">Panel</a> |
      <a href="company_trips.php">Seferlerim</a> |
      <a href="company_coupons.php">Kuponlarım</a> |
      <a href="company_tickets.php">Biletler</a> |
      <a href="logout.php">Çıkış</a>
    <?php else: ?>
      <strong>👤 Yolcu:</strong>
      <a href="profile.php">Profilim</a> |
      <a href="my_tickets.php">Biletlerim</a> |
      <a href="logout.php">Çıkış</a>
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
        // Sefer süresi hesaplama (varış zamanı - kalkış zamanı)
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
