<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../includes/db.php';

$errorMsg = '';
$successMsg = '';

// Firma ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $logo = trim($_POST['logo_path']);
    try {
        $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, name, logo_path, created_at)
                               VALUES (:id, :name, :logo, datetime('now'))");
        $stmt->execute([':id' => uniqid('cmp_'), ':name' => $name, ':logo' => $logo]);
        $successMsg = "Firma başarıyla eklendi.";
    } catch (PDOException $e) {
        $errorMsg = "Hata: " . $e->getMessage();
    }
}

// Firma silme (FK kontrolü)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
        $stmt->execute([$id]);
        $successMsg = "Firma başarıyla silindi.";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'FOREIGN KEY')) {
            $errorMsg = "❌ Bu firma silinemez! Önce bu firmaya bağlı seferleri veya yöneticileri silin.";
        } else {
            $errorMsg = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

// Tüm firmalar
$companies = $pdo->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Yönetimi</title>
</head>
<body>
<h2>🏢 Firma Yönetimi</h2>
<a href="admin_panel.php">← Admin Paneli</a>
<hr>

<?php if ($errorMsg): ?>
  <div style="color:red;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div style="color:green;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<h3>Yeni Firma Ekle</h3>
<form method="POST">
    <label>Firma Adı:</label>
    <input type="text" name="name" required>
    <label>Logo Yolu:</label>
    <input type="text" name="logo_path">
    <button type="submit" name="add">Ekle</button>
</form>

<hr>
<h3>Mevcut Firmalar</h3>
<table border="1" cellpadding="5">
    <tr><th>ID</th><th>Ad</th><th>Logo</th><th>Oluşturulma</th><th>İşlem</th></tr>
    <?php foreach ($companies as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['id']) ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td><?= htmlspecialchars($c['logo_path']) ?></td>
            <td><?= htmlspecialchars($c['created_at']) ?></td>
            <td>
                <a href="admin_edit_company.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> |
                <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">🗑️ Sil</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
