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


<div class="container" style="margin-top:8px;">
    <h3>Mevcut Kuponlarım</h3>
    <?php if (empty($coupons)): ?>
        <p>Henüz eklenmiş kupon bulunmamaktadır.</p>
    <?php else: ?>
        <table>
            <tr>
              <th>Kod</th>
              <th>İndirim</th>
              <th>Kullanım Limiti</th>
              <th>Kullanılan</th>
              <th>Kalan</th>
              <th>Son Tarih</th>
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