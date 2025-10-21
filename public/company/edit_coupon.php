<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$coupon_id = $_GET['id'] ?? null;

$error = [];
$success = "";

if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

if (!$coupon_id) {
    $error[] = "Hatalı erişim."; 
}

// 1. Kupon bilgisini getir ve yetki kontrolü yap
$coupon = getCouponDetailsForCompany($coupon_id, $company_id);

if (!$coupon) {
    $error[] ="Kupon bulunamadı veya size ait değil.";
}

// 2. Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $update_data = [
        'code' => $_POST['code'] ?? '',
        'discount' => $_POST['discount'] ?? 0,
        'usage_limit' => $_POST['usage_limit'] ?? 0,
        'expire_date' => $_POST['expire_date'] ?? date('Y-m-d'),
    ];

    $result = updateCouponForCompany($coupon_id, $company_id, $update_data);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header("Location: edit_coupon.php?id=" . urlencode($coupon_id));
    exit;
}

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="coupons.php">← Kuponlara Geri Dön</a></div>

<?php require_once __DIR__ . '/../../includes/message_comp.php'; ?>

<?php if ($coupon): ?>
<div class="container">
    <h2>✏️ Kupon Düzenle</h2>
    <form method="POST">
    <label>Kod</label>
    <input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required>

    <label>İndirim (%)</label>
    <input type="number" step="0.1" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>" min="0.1" max="100" required>

    <label>Kullanım Limiti</label>
    <input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" min="1" required>

    <label>Son Tarih</label>
    <input type="date" name="expire_date" value="<?= htmlspecialchars($coupon['expire_date']) ?>" required>

    <button class="form-button" type="submit">Kaydet</button>
    </form>
</div>
<?php else: ?>
    <p style="text-align:center;margin-top:20px;">Kupon bulunamadı.</p>
    <a href="/index.php"><p style="text-align:center;">Ana Sayfaya Dön</p></a>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
