<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$page_title = "Bana1Bilet - Sistem Yönetim Paneli";
require_once __DIR__ . '/../../includes/header.php';
?>

<h3>🛠️ Admin Paneli</h1>
<p>Hoş geldin, <?= htmlspecialchars($_SESSION['user']['full_name']) ?>!</p>

<nav>
    <ul>
        <li><a href="show_companies.php">🏢 Firma Yönetimi</a></li>
        <li><a href="show_company_admins.php">👤 Firma Admin Yönetimi</a></li>
        <li><a href="coupons.php">🎟️ Kupon Yönetimi</a></li>
        <li><a href="../index.php">← Ana Sayfa</a></li>
        <li><a href="../logout.php">Çıkış Yap</a></li>
    </ul>
</nav>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
