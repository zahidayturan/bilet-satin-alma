<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

if (!$company_id) {
    die("Hata: Firma bilgisi eksik. Bu kullanıcı bir firmaya bağlı değil.");
}

// ✅ Kupon ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];

    $stmt = $pdo->prepare("
        INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at)
        VALUES (:id, :code, :disc, :limit, :expire, :cid, datetime('now'))
    ");
    $stmt->execute([
        ':id' => uniqid('coup_'),
        ':code' => $code,
        ':disc' => $discount,
        ':limit' => $usage_limit,
        ':expire' => $expire_date,
        ':cid' => $company_id
    ]);
}

// ❌ Kupon silme
if (isset($_GET['delete'])) {
    $coupon_id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$coupon_id, $company_id]);
}

// 📊 Firma kuponlarını kullanım bilgileriyle birlikte listele
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        (SELECT COUNT(*) FROM User_Coupons uc WHERE uc.coupon_id = c.id) AS used_count
    FROM Coupons c
    WHERE c.company_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$company_id]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Kupon Yönetimi</title>
</head>
<body>
<h2>🎟️ Firma Kupon Yönetimi</h2>
<a href="company_panel.php">← Geri</a>
<hr>

<h3>➕ Yeni Kupon Ekle</h3>
<form method="POST">
  <label>Kod:</label> 
  <input type="text" name="code" required>
  
  <label>İndirim (%):</label> 
  <input type="number" step="0.1" name="discount" required>
  
  <label>Kullanım Limiti:</label> 
  <input type="number" name="usage_limit" required>
  
  <label>Son Tarih:</label> 
  <input type="date" name="expire_date" required>
  
  <button type="submit">Ekle</button>
</form>

<hr>
<h3>📋 Mevcut Kuponlarım</h3>
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
      <a href="company_edit_coupon.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> |
      <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu kupon silinsin mi?')">❌ Sil</a>
  </td>
</tr>
<?php endforeach; ?>

</table>
</body>
</html>
