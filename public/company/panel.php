<?php
require_once __DIR__ . '/../../includes/auth.php';
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
    <li><a href="trips.php">🚌 Sefer Yönetimi</a></li>
    <li><a href="coupons.php">🎟️ Kupon Yönetimi</a></li>
    <li><a href="tickets.php">🎫 Biletler</a></li>
    <li><a href="../index.php">← Ana Sayfa</a></li>
    <li><a href="../logout.php">Çıkış Yap</a></li>
  </ul>
</nav>

</body>
</html>
