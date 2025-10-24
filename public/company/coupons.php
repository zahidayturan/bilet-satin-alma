<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];

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

if (!$company_id) {
    $error[] = 'Firma bilgisi bulunamadı.';
}

// Kupon ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coupon_data = [
        'code' => $_POST['code'] ?? '',
        'discount' => $_POST['discount'] ?? 0,
        'usage_limit' => $_POST['usage_limit'] ?? 0,
        'expire_date' => $_POST['expire_date'] ?? date('Y-m-d'),
    ];

    $result = createCoupon($company_id, $coupon_data);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header("Location: coupons.php");
    exit;
}

// Kupon silme
if (isset($_GET['delete'])) {
    $coupon_id = $_GET['delete'];
    $result = deleteCouponForCompany($coupon_id, $company_id);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header("Location: coupons.php");
    exit;
}

$coupons = getCompanyCoupons($company_id);

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="panel.php">← Firma Paneline Dön</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<h2>🎟️ Firma Kupon Yönetimi</h2>

<div class="container">
    <h3>Yeni Kupon Ekle</h3>
    <form method="POST">
    <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
        <div style="flex: 1 0 150px;">
            <label>Kod</label> 
            <input type="text" name="code" required>
        </div>
        <div style="flex: 1 0 150px;">
            <label>Son Tarih</label> 
            <input type="date" name="expire_date" required>
        </div>
    </div>  
    
    <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
        <div style="flex: 1 0 150px;">
            <label>İndirim (%)</label> 
            <input type="number" step="0.1" name="discount" min="0.1" max="100" required>
        </div>
        <div style="flex: 1 0 150px;">
            <label>Kullanım Limiti</label> 
            <input type="number" name="usage_limit" min="1" required>
        </div>
    </div>
    
    <button class="form-button" type="submit">+ Kupon Ekle</button>
    </form>
</div>


<?php
// ... [Mevcut PHP kodlarınız ve kuponların çekilmesi] ...

// getSortLink fonksiyonunun bu dosyada tanımlı olduğunu varsayıyoruz.
function getSortLink($column, $current_sort, $current_order, $label) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }

    // Link hedefini doğru dosya adına göre ayarlayın (Örn: 'company_coupons.php')
    $link = htmlspecialchars("coupons.php?sort={$column}&order={$new_order}"); 
    return "<a style=\"text-decoration:underline;\" href=\"{$link}\">{$label}{$arrow}</a>";
}

// --- Sıralama İşlemi Başlangıcı ---
$sort_by = $_GET['sort'] ?? 'expire_date';
$sort_order = strtolower($_GET['order'] ?? 'asc');

$allowed_sorts = [
    'code',
    'discount',
    'usage_limit',
    'used_count',
    'expire_date'
];

if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'expire_date';
}
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'asc';
}

if (!empty($coupons)) {
    usort($coupons, function($a, $b) use ($sort_by, $sort_order) {
        $a_val = $a[$sort_by] ?? '';
        $b_val = $b[$sort_by] ?? '';

        if (in_array($sort_by, ['discount', 'usage_limit', 'used_count'])) {
             $a_val = (int)$a_val;
             $b_val = (int)$b_val;
        }
        
        if ($sort_by === 'expire_date') {
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
?>


<div class="container" style="margin-top:8px;">
    <h3>Mevcut Kuponlarım</h3>
    <?php if (empty($coupons)): ?>
        <p>Henüz eklenmiş kupon bulunmamaktadır.</p>
    <?php else: ?>
        <table>
            <tr>
                <th><?= getSortLink('code', $sort_by, $sort_order, 'Kod') ?></th>
                <th><?= getSortLink('discount', $sort_by, $sort_order, 'İndirim') ?></th>
                <th><?= getSortLink('usage_limit', $sort_by, $sort_order, 'Kullanım Limiti') ?></th>
                <th><?= getSortLink('used_count', $sort_by, $sort_order, 'Kullanılan') ?></th>
                <th>Kalan</th>
                <th><?= getSortLink('expire_date', $sort_by, $sort_order, 'Son Tarih') ?></th>
                <th>İşlem</th>
            </tr>

            <?php foreach ($coupons as $c): ?>
            <?php
            $used = (int)$c['used_count'];
            $limit = (int)$c['usage_limit'];
            $remaining = max(0, $limit - $used);
            ?>
            <tr>
              <td><?= htmlspecialchars($c['code']) ?></td>
              <td>%<?= htmlspecialchars($c['discount']) ?></td>
              <td><?= $limit ?></td>
              <td><?= $used ?></td>
              <td><?= $remaining ?></td>
              <td><?= htmlspecialchars($c['expire_date']) ?></td>
              <td>
                  <a href="edit_coupon.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> |
                  <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu kupon silinsin mi?')">❌ Sil</a>
              </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>