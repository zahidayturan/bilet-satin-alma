<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);

$page_title = "Bana1Bilet - Firma Yönetim Paneli";
require_once __DIR__ . '/../../includes/header.php';
?>

<h3>🏢 Firma Yönetim Paneli</h1>
<p>Hoş geldin, <?= htmlspecialchars($_SESSION['user']['full_name']) ?></p>

<nav>
  <ul>
    <li><a href="trips.php">🚌 Sefer Yönetimi</a></li>
    <li><a href="coupons.php">🎟️ Kupon Yönetimi</a></li>
    <li><a href="tickets.php">🎫 Biletler</a></li>
    <li><a href="../index.php">← Ana Sayfa</a></li>
    <li><a href="../logout.php">Çıkış Yap</a></li>
  </ul>
</nav>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>