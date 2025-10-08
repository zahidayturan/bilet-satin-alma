<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) {
    echo "Hoşgeldin, " . htmlspecialchars($_SESSION['user']['full_name']);

    echo "<h1>Otobüs Bileti Satış Platformu</h1>";
    echo "<p>Veritabanı başarıyla bağlandı!</p>";

} else {
    echo '<a href="login.php">Giriş Yap</a>';
}
?>
