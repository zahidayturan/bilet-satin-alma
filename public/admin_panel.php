<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli</title>
</head>
<body>
<h1>🛠️ Admin Paneli</h1>

<p>Hoş geldin, <?= htmlspecialchars($_SESSION['user']['full_name']) ?>!</p>

<nav>
    <ul>
        <li><a href="admin_firmas.php">🏢 Firma Yönetimi</a></li>
        <li><a href="admin_firma_admin.php">👤 Firma Admin Yönetimi</a></li>
        <li><a href="admin_coupons.php">🎟️ Kupon Yönetimi</a></li>
        <li><a href="index.php">← Ana Sayfa</a></li>
        <li><a href="logout.php">Çıkış Yap</a></li>
    </ul>
</nav>

</body>
</html>
