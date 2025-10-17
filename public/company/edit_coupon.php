<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$coupon_id = $_GET['id'] ?? null;

// Mesaj değişkenleri
$success = '';
$error = '';

if (!$coupon_id) {
die("Hatali erişim."); 
}

// 1. Kupon bilgisini getir ve yetki kontrolü yap
$coupon = getCouponDetailsForCompany($coupon_id, $company_id);

if (!$coupon) {
die("Bu kupon bulunamadı veya size ait değil.");
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
        $success = $result['message'];
        
        // Başarılı güncelleme sonrası kuponun yeni bilgilerini çekiyoruz ki formda görüntülensin
        $coupon = getCouponDetailsForCompany($coupon_id, $company_id); 
        
    } else {
        $error = $result['message'];
        // Hata durumunda kullanıcının girdiği verileri formda tutmak için $coupon dizisini güncelle
        $coupon['code'] = $update_data['code'];
        $coupon['discount'] = $update_data['discount'];
        $coupon['usage_limit'] = $update_data['usage_limit'];
        $coupon['expire_date'] = $update_data['expire_date'];
    }
}

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>✏️ Kupon Düzenle</h2>
<a href="coupons.php">← Kupon Listesine Dön</a>
<hr>

<?php if ($error): ?><div class="error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="POST">
  <label>Kod:</label>
  <input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required><br><br>

  <label>İndirim (%):</label>
  <input type="number" step="0.1" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>" min="0.1" max="100" required><br><br>

  <label>Kullanım Limiti:</label>
  <input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" min="1" required><br><br>

  <label>Son Tarih:</label>
  <input type="date" name="expire_date" value="<?= htmlspecialchars($coupon['expire_date']) ?>" required><br><br>

  <button type="submit">Kaydet</button>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
