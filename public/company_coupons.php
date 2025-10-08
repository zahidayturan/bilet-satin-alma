<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

// Kupon ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, created_at)
                           VALUES (:id, :code, :disc, :limit, :expire, datetime('now'))");
    $stmt->execute([
        ':id' => uniqid('coup_'),
        ':code' => strtoupper(trim($_POST['code'])),
        ':disc' => $_POST['discount'],
        ':limit' => $_POST['usage_limit'],
        ':expire' => $_POST['expire_date']
    ]);
}

// Kuponları listele
$coupons = $pdo->query("SELECT * FROM Coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Kupon Yönetimi</title>
</head>
<body>
<h2>🎟️ Kupon Yönetimi</h2>
<a href="company_panel.php">← Geri</a>
<hr>

<form method="POST">
  <label>Kod:</label> <input type="text" name="code" required>
  <label>İndirim (%):</label> <input type="number" step="0.1" name="discount" required>
  <label>Kullanım Limiti:</label> <input type="number" name="usage_limit" required>
  <label>Son Tarih:</label> <input type="date" name="expire_date" required>
  <button type="submit">Ekle</button>
</form>

<hr>
<table border="1" cellpadding="6">
<tr><th>Kod</th><th>İndirim</th><th>Limit</th><th>Son Tarih</th></tr>
<?php foreach ($coupons as $c): ?>
<tr>
  <td><?= htmlspecialchars($c['code']) ?></td>
  <td>%<?= htmlspecialchars($c['discount']) ?></td>
  <td><?= htmlspecialchars($c['usage_limit']) ?></td>
  <td><?= htmlspecialchars($c['expire_date']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
