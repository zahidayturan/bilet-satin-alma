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

<?php
// --- Sıralama İşlemi Başlangıcı ---
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = strtolower($_GET['order'] ?? 'desc');

$allowed_sorts = [
    'created_at',
    'code',
    'discount',
    'usage_limit',
    'used_count',
    'expire_date',
    'company_name'
];

if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'created_at';
}
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'desc';
}

if (!empty($coupons)) {
    usort($coupons, function($a, $b) use ($sort_by, $sort_order) {
        $a_val = $a[$sort_by] ?? '';
        $b_val = $b[$sort_by] ?? '';

        if (in_array($sort_by, ['discount', 'usage_limit', 'used_count'])) {
             $a_val = (int)$a_val;
             $b_val = (int)$b_val;
        }

        if (in_array($sort_by, ['created_at', 'expire_date'])) {
             $a_val = strtotime($a_val);
             $b_val = strtotime($b_val);
        }

        if ($a_val == $b_val) {
            return 0;
        }

        if ($sort_order === 'asc') {
            return ($a_val < $b_val) ? -1 : 1;
        } else {
            return ($a_val > $b_val) ? -1 : 1;
        }
    });
}

function getSortLink($column, $current_sort, $current_order, $label) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }

    $link = htmlspecialchars("coupons.php?sort={$column}&order={$new_order}"); 
    return "<a style=\"text-decoration:underline;\" href=\"{$link}\">{$label}{$arrow}</a>";
}

?>

<div class="table-container" style="margin-top:20px;">
    <h3>📋 Mevcut Kuponlar</h3>
    <table>
        <tr>
            <th><?= getSortLink('created_at', $sort_by, $sort_order, 'Eklenme Tarihi') ?></th>
            <th><?= getSortLink('code', $sort_by, $sort_order, 'Kod') ?></th>
            <th><?= getSortLink('discount', $sort_by, $sort_order, 'İndirim') ?></th>
            <th><?= getSortLink('usage_limit', $sort_by, $sort_order, 'Kullanım Limiti') ?></th>
            <th><?= getSortLink('used_count', $sort_by, $sort_order, 'Kullanılan') ?></th>
            <th>Kalan</th>
            <th><?= getSortLink('expire_date', $sort_by, $sort_order, 'Son Tarih') ?></th>
            <th><?= getSortLink('company_name', $sort_by, $sort_order, 'Firma') ?></th>
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
                    <td><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></td>
                    <td><?= htmlspecialchars($c['code']) ?></td>
                    <td>%<?= htmlspecialchars($c['discount']) ?></td>
                    <td><?= $limit ?></td>
                    <td><?= $used ?></td>
                    <td><?= max(0, $remaining) ?></td>
                    <td><?= htmlspecialchars($c['expire_date']) ?></td>
                    <td><?= $c['company_name'] ? htmlspecialchars($c['company_name']) : '<em>Hepsinde Geçerli</em>' ?></td>
                    <td>
                        <a href="edit_coupon.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a><br><br>
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