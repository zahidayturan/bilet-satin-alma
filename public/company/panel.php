<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);

$page_title = "Bana1Bilet - Firma Yönetim Paneli";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="/index.php">← Ana Sayfa</a></div>

<h3>Firma Yönetim Paneli</h1>
<p style="margin:12px;">Hoş geldin, <?= htmlspecialchars($_SESSION['user']['full_name']) ?>!</p>

<div class="container-grid" style="justify-content: start;">
    <a href="trips.php">
        <div class="container panel-container">
            <h4>Sefer<br>Yönetimi</h4>
            <p>Sefer ekleyin, düzenleyin ve görüntüleyin</p>
            <p>🚌</p>
        </div>
    </a>
    <a href="tickets.php">
        <div class="container panel-container">
            <h4>Bilet<br>Yönetimi</h4>
            <p>Alınan biletleri görüntüleyin ve işlemler yapın</p>
            <p>🎫</p>
        </div>
    </a>
    <a href="coupons.php">
        <div class="container panel-container">
            <h4>Kupon<br>Yönetimi</h4>
            <p>Kupon ekleyin, düzenleyin ve görüntüleyin</p>
            <p>🎟️</p>
        </div>
    </a>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>