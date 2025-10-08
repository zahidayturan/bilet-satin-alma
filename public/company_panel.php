<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Admin Paneli</title>
</head>
<body>
<h1>🏢 Firma Admin Paneli</h1>
<p>Hoşgeldin, <?= htmlspecialchars($_SESSION['user']['full_name']) ?></p>

<nav>
  <ul>
    <li><a href="company_trips.php">🚌 Sefer Yönetimi</a></li>
    <li><a href="company_coupons.php">🎟️ Kupon Yönetimi</a></li>
    <li><a href="company_tickets.php">🎫 Biletler</a></li>
    <li><a href="logout.php">Çıkış Yap</a></li>
  </ul>
</nav>

</body>
</html>
