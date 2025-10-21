<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

$page_title = "Bana1Bilet - Sistem Yönetim Paneli";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="/index.php">← Ana Sayfa</a></div>

<h3>Admin Paneli</h1>
<p style="margin:12px;">Hoş geldin, <?= htmlspecialchars($_SESSION['user']['full_name']) ?>!</p>


<div class="container-grid" style="justify-content: start;">
    <a href="show_companies.php">
        <div class="container panel-container">
            <h4>Firma<br>Yönetimi</h4>
            <p>Firma ekleyin, düzenleyin ve görüntüleyin</p>
            <p>🏢</p>
        </div>
    </a>
    <a href="show_company_admins.php">
        <div class="container panel-container">
            <h4>Firma Admin<br>Yönetimi</h4>
            <p>Firmalara yöneticiler ekleyin, düzenleyin ve görüntüleyin</p>
            <p>👤</p>
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
