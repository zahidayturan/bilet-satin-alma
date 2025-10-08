<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Arama parametreleri
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

$query = "SELECT * FROM Trips WHERE 1=1";
$params = [];

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

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Otobüs Bileti Satın Alma</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>🚌 Otobüs Bileti Satın Alma Platformu</h1>

<!-- Üst menü -->
<nav style="margin-bottom: 15px;">
    <?php if (isLoggedIn()): ?>
        <span>Hoşgeldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span> |
        <a href="logout.php">Çıkış Yap</a>
        <br><br>

        <!-- 🔹 Rol bazlı hızlı erişim menüsü -->
        <?php
        $role = $_SESSION['user']['role'] ?? '';
        if ($role === 'admin'): ?>
            <div style="background:#f3f3f3;padding:10px;border-radius:8px;">
                <strong>🔧 Yönetim Menüsü:</strong>
                <a href="admin_panel.php">Admin Paneli</a> |
                <a href="admin_firmas.php">Firmalar</a> |
                <a href="admin_firma_admin.php">Firma Adminleri</a> |
                <a href="admin_coupons.php">Kuponlar</a>
            </div>
        <?php elseif ($role === 'company'): ?>
            <div style="background:#f3f3f3;padding:10px;border-radius:8px;">
                <strong>🏢 Firma Admin Menüsü:</strong>
                <a href="company_panel.php">Firma Paneli</a> |
                <a href="company_trips.php">Seferlerim</a> |
                <a href="company_coupons.php">Kuponlarım</a> |
                <a href="company_tickets.php">Biletler</a>
            </div>
        <?php elseif ($role === 'user'): ?>
            <div style="background:#f3f3f3;padding:10px;border-radius:8px;">
                <strong>👤 Yolcu Menüsü:</strong>
                <a href="my_tickets.php">Biletlerim</a> |
                <a href="logout.php">Çıkış Yap</a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <a href="login.php">Giriş Yap</a> | <a href="register.php">Kayıt Ol</a>
    <?php endif; ?>
</nav>

<hr>

<!-- Arama formu -->
<form method="GET">
    <label>Kalkış:</label>
    <input type="text" name="from" value="<?= htmlspecialchars($from) ?>">
    
    <label>Varış:</label>
    <input type="text" name="to" value="<?= htmlspecialchars($to) ?>">

    <label>Tarih:</label>
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">

    <button type="submit">Sefer Ara</button>
</form>

<hr>

<!-- Sefer listesi -->
<?php if ($trips): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Kalkış</th>
            <th>Varış</th>
            <th>Tarih</th>
            <th>Fiyat</th>
            <th></th>
        </tr>
        <?php foreach ($trips as $trip): ?>
            <tr>
                <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                <td><?= htmlspecialchars($trip['destination_city']) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></td>
                <td><?= htmlspecialchars($trip['price']) ?> ₺</td>
                <td>
                    <a href="trip_detail.php?id=<?= urlencode($trip['id']) ?>">Detay</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Sefer bulunamadı.</p>
<?php endif; ?>

</body>
</html>
