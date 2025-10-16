<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz kupon ID.");

$success = '';
$error = '';

// 1. Firma listesini çekme (Dropdown için)
$companies = getCompanyListForDropdown();

// 2. Kupon bilgisini çekme
$coupon = getCouponById($id);
if (!$coupon) die("Kupon bulunamadı.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];
    // company_id boş gelirse null olarak fonksiyona iletilecek
    $company_id = $_POST['company_id'] ?: null; 

    // 3. Kuponu güncelleme
    if (updateCoupon($id, $code, $discount, $usage_limit, $expire_date, $company_id)) {
        $success = "Kupon bilgileri başarıyla güncellendi. ✅";
        
        // Güncel veriyi tekrar al
        $coupon = getCouponById($id); 
    } else {
        $error = "Güncelleme hatası! Kod benzersiz olmayabilir veya veritabanı sorunu oluştu. ❌";
    }
}

// Form, güncel kupon verisi ($coupon) ile doldurulur.
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kupon Düzenle</title>
</head>
<body>
<h2>✏️ Kupon Düzenle</h2>
<a href="coupons.php">← Kupon Listesine Dön</a>
<hr>

<?php if ($success): ?><div style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST">
    <label>Kod:</label>
    <input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required><br><br>

    <label>İndirim (%):</label>
    <input type="number" step="0.01" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>" required><br><br>

    <label>Kullanım Limiti:</label>
    <input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" required><br><br>

    <label>Son Kullanma Tarihi:</label>
    <input type="date" name="expire_date" value="<?= htmlspecialchars($coupon['expire_date']) ?>" required><br><br>

    <label>Firma:</label>
    <select name="company_id">
        <option value="">(Tüm Firmalar için geçerli)</option>
        <?php foreach ($companies as $comp): ?>
            <option value="<?= htmlspecialchars($comp['id']) ?>"
                <?= $coupon['company_id'] === $comp['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($comp['name']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Kaydet</button>
</form>

</body>
</html>