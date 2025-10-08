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
<nav>
    <?php if (isLoggedIn()): ?>
        Hoşgeldin, <?= htmlspecialchars($_SESSION['user']['full_name']) ?> |
        <a href="logout.php">Çıkış Yap</a>
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
