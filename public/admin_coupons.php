<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

require_once __DIR__ . '/../includes/functions.php';

$errorMsg = '';
$successMsg = '';

// ✅ Yeni kupon ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];
    $company_id = $_POST['company_id'] ?: null;

    if (addCoupon($code, $discount, $usage_limit, $expire_date, $company_id)) {
        $successMsg = "Kupon başarıyla eklendi. ✅";
    } else {
        $errorMsg = "Kupon eklenirken bir hata oluştu. (Kod zaten mevcut olabilir) ❌";
    }
}

// ❌ Kupon silme
if (isset($_GET['delete'])) {
    if (deleteCoupon($_GET['delete'])) {
        $successMsg = "Kupon başarıyla silindi. 🗑️";
    } else {
        $errorMsg = "Kupon silinirken bir hata oluştu. ❌";
    }
}

// 🚍 Firma listesi
$companies = getCompanyListForDropdown();

// 📦 Kuponları ve kullanım sayılarını getir
$coupons = getAllCouponsWithUsage();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kupon Yönetimi</title>
</head>
<body>
<h2>🎟️ Kupon Yönetimi (Admin)</h2>
<a href="admin_panel.php">← Admin Paneli</a>
<hr>

<?php if ($errorMsg): ?><div style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
<?php if ($successMsg): ?><div style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>

<h3>➕ Yeni Kupon Ekle</h3>
<form method="POST">
    <label>Kod:</label>
    <input type="text" name="code" required>
    <label>İndirim (%):</label>
    <input type="number" step="0.01" name="discount" required>
    <label>Kullanım Limiti:</label>
    <input type="number" name="usage_limit" required>
    <label>Son Kullanma Tarihi:</label>
    <input type="date" name="expire_date" required>

    <label>Firma:</label>
    <select name="company_id">
        <option value="">(Tüm Firmalar için geçerli)</option>
        <?php foreach ($companies as $comp): ?>
            <option value="<?= htmlspecialchars($comp['id']) ?>"><?= htmlspecialchars($comp['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Kupon Ekle</button>
</form>

<hr>
<h3>📋 Mevcut Kuponlar</h3>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Kod</th>
        <th>İndirim</th>
        <th>Kullanım Limiti</th>
        <th>Kullanılan</th>
        <th>Kalan</th>
        <th>Son Tarih</th>
        <th>Firma</th>
        <th>İşlem</th>
    </tr>

    <?php if (empty($coupons)): ?>
        <tr><td colspan="8" style="text-align:center;">Henüz kupon eklenmemiş.</td></tr>
    <?php else: ?>
        <?php foreach ($coupons as $c): ?>
            <?php
                $used = (int)$c['used_count'];
                $limit = (int)$c['usage_limit'];
                $remaining = $limit - $used;
            ?>
            <tr>
                <td><?= htmlspecialchars($c['code']) ?></td>
                <td>%<?= htmlspecialchars($c['discount']) ?></td>
                <td><?= $limit ?></td>
                <td><?= $used ?></td>
                <td><?= max(0, $remaining) ?></td>
                <td><?= htmlspecialchars($c['expire_date']) ?></td>
                <td><?= $c['company_name'] ? htmlspecialchars($c['company_name']) : '<em>Global</em>' ?></td>
                <td>
                    <a href="admin_edit_coupon.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> |
                    <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu kupon silinsin mi?')">❌ Sil</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

</body>
</html>