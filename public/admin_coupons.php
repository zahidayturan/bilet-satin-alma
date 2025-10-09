<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Kupon ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];
    $company_id = $_POST['company_id'] ?: null; // boşsa global

    $stmt = $pdo->prepare("
        INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at)
        VALUES (:id, :code, :discount, :limit, :expire, :cid, datetime('now'))
    ");
    $stmt->execute([
        ':id' => uniqid('coup_'),
        ':code' => $code,
        ':discount' => $discount,
        ':limit' => $usage_limit,
        ':expire' => $expire_date,
        ':cid' => $company_id
    ]);
}

// Kupon silme
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

// Firma listesi (drop-down için)
$companies = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Tüm kuponlar
$coupons = $pdo->query("
    SELECT c.*, b.name AS company_name
    FROM Coupons c
    LEFT JOIN Bus_Company b ON c.company_id = b.id
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Kupon Yönetimi</title>
</head>
<body>
<h2>🎟️ Kupon Yönetimi (Admin)</h2>
<a href="admin_panel.php">← Admin Paneli</a>
<hr>

<h3>Yeni Kupon Ekle</h3>
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

    <button type="submit">Ekle</button>
</form>

<hr>
<h3>Mevcut Kuponlar</h3>
<table border="1" cellpadding="5">
    <tr><th>Kod</th><th>İndirim</th><th>Limit</th><th>Son Tarih</th><th>Firma</th><th>İşlem</th></tr>
    <?php foreach ($coupons as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['code']) ?></td>
            <td>%<?= htmlspecialchars($c['discount']) ?></td>
            <td><?= htmlspecialchars($c['usage_limit']) ?></td>
            <td><?= htmlspecialchars($c['expire_date']) ?></td>
            <td><?= $c['company_name'] ? htmlspecialchars($c['company_name']) : '<em>Tüm Firmalar</em>' ?></td>
            <td><a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Silinsin mi?')">Sil</a></td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
