<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../../includes/functions.php';

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

// Yeni kupon ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];
    $company_id = $_POST['company_id'] ?: null;

    if (addCoupon($code, $discount, $usage_limit, $expire_date, $company_id)) {
        $_SESSION['success_message'] = "Kupon başarıyla eklendi.";
    } else {
        $_SESSION['error_message'] = "Kupon eklenirken bir hata oluştu. (Kod zaten mevcut olabilir)";
    }
    header("Location: coupons.php");
    exit;
}

// Kupon silme
if (isset($_GET['delete'])) {
    if (deleteCoupon($_GET['delete'])) {
        $_SESSION['success_message'] = "Kupon başarıyla silindi.";
    } else {
        $_SESSION['error_message'] = "Kupon silinirken bir hata oluştu.";
    }
    header("Location: coupons.php");
    exit;
}

// Firma listesi
$companies = getCompanyListForDropdown();

// Kuponları ve kullanım sayılarını getir
$coupons = getAllCouponsWithUsage();

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="panel.php">← Admin Paneli</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<h2>🎟️ Kupon Yönetimi</h2>

<div class="container">
    <h3>Yeni Kupon Ekle</h3>
    <form method="POST">

        <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
            <div style="flex: 1 0 150px;">
                <label>Kod</label>
                <input type="text" name="code" required>
            </div>

            <div style="flex: 1 0 150px;">
                <label>Son Kullanma Tarihi</label>
                <input type="date" name="expire_date" required>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
            <div style="flex: 1 0 150px;">
                <label>İndirim (%)</label>
                <input type="number" step="0.01" name="discount" required>
            </div>
            <div style="flex: 1 0 150px;">
                <label>Kullanım Limiti</label>
                <input type="number" name="usage_limit" required>
            </div>
        </div>

        <label>Firma:</label>
        <select name="company_id">
            <option value="">(Tüm Firmalar için geçerli)</option>
            <?php foreach ($companies as $comp): ?>
                <option value="<?= htmlspecialchars($comp['id']) ?>"><?= htmlspecialchars($comp['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="form-button" style="margin-top:20px;" type="submit">Kupon Ekle</button>
    </form>
</div>

<div class="table-container" style="margin-top:20px;">
    <h3>📋 Mevcut Kuponlar</h3>
    <table>
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
            <tr><td colspan="8">Henüz kupon eklenmemiş.</td></tr>
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
                    <td><?= $c['company_name'] ? htmlspecialchars($c['company_name']) : '<em>Hepsinde Geçerli</em>' ?></td>
                    <td>
                        <a href="edit_coupon.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> |
                        <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu kupon silinsin mi?')">❌ Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>


<?php
require_once __DIR__ . '/../../includes/footer.php';
?>