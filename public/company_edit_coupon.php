<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];
$coupon_id = $_GET['id'] ?? null;

if (!$coupon_id || !$company_id) {
    die("Hatalı erişim.");
}

// Kupon bilgisi (sadece kendi firmasına ait olmalı)
$stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ? AND company_id = ?");
$stmt->execute([$coupon_id, $company_id]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coupon) {
    die("Bu kupon bulunamadı veya size ait değil.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];

    try {
        $stmt = $pdo->prepare("
            UPDATE Coupons 
            SET code = ?, discount = ?, usage_limit = ?, expire_date = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$code, $discount, $usage_limit, $expire_date, $coupon_id, $company_id]);

        $success = "Kupon başarıyla güncellendi.";

        // Yeniden yükle
        $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$coupon_id, $company_id]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = "Hata: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Kupon Düzenle</title>
</head>
<body>
<h2>✏️ Kupon Düzenle</h2>
<a href="company_coupons.php">← Kupon Listesine Dön</a>
<hr>

<?php if ($error): ?><div style="color:red;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div style="color:green;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="POST">
  <label>Kod:</label>
  <input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required><br><br>

  <label>İndirim (%):</label>
  <input type="number" step="0.1" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>" required><br><br>

  <label>Kullanım Limiti:</label>
  <input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" required><br><br>

  <label>Son Tarih:</label>
  <input type="date" name="expire_date" value="<?= htmlspecialchars($coupon['expire_date']) ?>" required><br><br>

  <button type="submit">Kaydet</button>
</form>
</body>
</html>
