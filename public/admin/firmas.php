<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../../includes/functions.php';

$errorMsg = '';
$successMsg = '';

// Firma ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $logo = trim($_POST['logo_path'] ?? ''); // logo_path boşsa bile hata vermemesi için
    
    if (addBusCompany($name, $logo)) {
        $successMsg = "Firma başarıyla eklendi. ✅";
    } else {
        $errorMsg = "Firma eklenirken bir hata oluştu. ❌";
    }
}

// Firma silme
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $result = deleteBusCompany($id);
    
    if ($result['success']) {
        $successMsg = $result['message'];
    } else {
        $errorMsg = $result['message'];
    }
}

// Tüm firmaları çekme
$companies = getAllBusCompanies();

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>🏢 Firma Yönetimi</h2>
<a href="panel.php">← Admin Paneli</a>
<hr>

<?php if ($errorMsg): ?>
  <div style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($successMsg) ?></div>
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
    <?php if (empty($companies)): ?>
        <tr><td colspan="5" style="text-align:center;">Henüz hiç firma eklenmemiş.</td></tr>
    <?php else: ?>
        <?php foreach ($companies as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['id']) ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['logo_path']) ?></td>
                <td><?= htmlspecialchars($c['created_at']) ?></td>
                <td>
                    <a href="edit_company.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> |
                    <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">🗑️ Sil</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>