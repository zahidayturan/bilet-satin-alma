<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id){
  $errors[] = htmlspecialchars('Bilgiler alınamadı. Tekrar deneyin.');
} 

$errors = [];
$success = '';

if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

// 1. Firma listesini çekme (Dropdown için)
$companies = getCompanyListForDropdown();

// 2. Kupon bilgisini çekme
$coupon = getCouponById($id);
if (!$coupon) {
    $errors[] = htmlspecialchars('Kupon bilgileri bulunamadı.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];
    // company_id boş gelirse null olarak fonksiyona iletilecek
    $company_id = $_POST['company_id'] ?: null; 

    // 3. Kuponu güncelleme
    if (updateCoupon($id, $code, $discount, $usage_limit, $expire_date, $company_id)) {
        $_SESSION['success_message'] = "Kupon bilgileri başarıyla güncellendi.";
    } else {
        $_SESSION['error_message'] = "Güncelleme hatası! Kod benzersiz olmayabilir veya veritabanı sorunu oluştu.";
    }

    header("Location: edit_coupon.php?id=" . urlencode($id));
    exit;
}

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="coupons.php">← Kupon Listesine Dön</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<?php if ($coupon): ?>
    <div class="container">
        <h2>✏️ Kupon Düzenle</h2>
        <form method="POST">
            <label>Kod</label>
            <input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required><br><br>

            <label>İndirim (%)</label>
            <input type="number" step="0.01" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>" required><br><br>

            <label>Kullanım Limiti</label>
            <input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" required><br><br>

            <label>Son Kullanma Tarihi</label>
            <input type="date" name="expire_date" value="<?= htmlspecialchars($coupon['expire_date']) ?>" required><br><br>

            <label>Firma</label>
            <select name="company_id">
                <option value="">(Tüm Firmalar için geçerli)</option>
                <?php foreach ($companies as $comp): ?>
                    <option value="<?= htmlspecialchars($comp['id']) ?>"
                        <?= $coupon['company_id'] === $comp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($comp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

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