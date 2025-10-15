<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz kupon ID.");

// Firma listesi
$companies = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Kupon bilgisi
$stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ?");
$stmt->execute([$id]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$coupon) die("Kupon bulunamadı.");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount']);
    $usage_limit = intval($_POST['usage_limit']);
    $expire_date = $_POST['expire_date'];
    $company_id = $_POST['company_id'] ?: null;

    try {
        $stmt = $pdo->prepare("
            UPDATE Coupons
            SET code = :code,
                discount = :discount,
                usage_limit = :limit,
                expire_date = :expire,
                company_id = :cid
            WHERE id = :id
        ");
        $stmt->execute([
            ':code' => $code,
            ':discount' => $discount,
            ':limit' => $usage_limit,
            ':expire' => $expire_date,
            ':cid' => $company_id,
            ':id' => $id
        ]);
        $success = "Kupon bilgileri başarıyla güncellendi.";

        // Güncel veriyi tekrar al
        $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ?");
        $stmt->execute([$id]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Güncelleme hatası: " . $e->getMessage();
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
<a href="admin_coupons.php">← Kupon Listesine Dön</a>
<hr>

<?php if ($success): ?><div style="color:green;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div style="color:red;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST">
    <label>Kod:</label>
    <input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required><br><br>

    <label>İndirim (%):</label>
    <input type="number" step="0.01" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>" required><br><br>

    <label>Kullanım Limiti:</label>
    <input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" required><br><br>

    <label>Son Kullanma Tarihi:</label>
    <input type="date" name="expire_date" value="<?= htmlspecialchars($coupon['expire_date']) ?>" required><br><br>

    <label>Firma:</label>
    <select name="company_id">
        <option value="">(Tüm Firmalar için geçerli)</option>
        <?php foreach ($companies as $comp): ?>
            <option value="<?= htmlspecialchars($comp['id']) ?>"
                <?= $coupon['company_id'] === $comp['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($comp['name']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Kaydet</button>
</form>

</body>
</html>
