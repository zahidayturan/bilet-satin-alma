<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$successMsg = '';
$errorMsg = '';

if (!$company_id) {
die("Hata: Firma bilgisi eksik.");
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
        $successMsg = $result['message'];
    } else {
        $errorMsg = $result['message'];
    }
}

// Kupon silme
if (isset($_GET['delete'])) {
$coupon_id = $_GET['delete'];
    
    $result = deleteCouponForCompany($coupon_id, $company_id);

    // POST/GET sonrası URL'yi temizleyerek tekrar gönderim hatasını önleyebiliriz.
    if ($result['success']) {
        header("Location: coupons.php?success=" . urlencode($result['message']));
        exit;
    } else {
        header("Location: coupons.php?error=" . urlencode($result['message']));
        exit;
    }
}

// URL'den gelen mesajları al
if (isset($_GET['success'])) {
    $successMsg = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $errorMsg = htmlspecialchars($_GET['error']);
}

// Firma kuponlarını listele
$coupons = getCompanyCoupons($company_id);

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>


<h2>🎟️ Firma Kupon Yönetimi</h2>
<a href="panel.php">← Geri</a>
<hr>

<?php if ($errorMsg): ?><div class="message error">❌ <?= $errorMsg ?></div><?php endif; ?>
<?php if ($successMsg): ?><div class="message success">✅ <?= $successMsg ?></div><?php endif; ?>


<h3>➕ Yeni Kupon Ekle</h3>
<form method="POST">
<label>Kod:</label> 
<input type="text" name="code" required>
  
  <label>İndirim (%):</label> 
  <input type="number" step="0.1" name="discount" min="0.1" max="100" required>
  
  <label>Kullanım Limiti:</label> 
  <input type="number" name="usage_limit" min="1" required>
  
  <label>Son Tarih:</label> 
  <input type="date" name="expire_date" required>
  
  <button type="submit">Ekle</button>
</form>

<hr>
<h3>📋 Mevcut Kuponlarım</h3>

<?php if (empty($coupons)): ?>
    <p>Henüz eklenmiş kupon bulunmamaktadır.</p>
<?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
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

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>