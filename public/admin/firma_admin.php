<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

require_once __DIR__ . '/../includes/functions.php';

$errorMsg = '';
$successMsg = '';

// Yeni firma admin oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $companyId = $_POST['company_id'];
    
    // Veritabanı fonksiyonunu çağırıyoruz
    if (addCompanyAdmin($fullName, $email, $password, $companyId)) {
        $successMsg = "Firma yöneticisi başarıyla eklendi. ✅";
    } else {
        $errorMsg = "Firma yöneticisi eklenirken bir hata oluştu. (E-posta zaten kayıtlı olabilir) ❌";
    }
}

// Firmalar listesi (dropdown için)
$companies = getCompanyListForDropdown();

// Tüm firma adminleri
$admins = getAllCompanyAdmins();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Admin Yönetimi</title>
</head>
<body>
<h2>👤 Firma Admin Yönetimi</h2>
<a href="panel.php">← Admin Paneli</a>
<hr>

<?php if ($errorMsg): ?>
  <div style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<h3>Yeni Firma Admin Ekle</h3>
<form method="POST">
    <label>Ad Soyad:</label>
    <input type="text" name="full_name" required>
    <label>E-posta:</label>
    <input type="email" name="email" required>
    <label>Şifre:</label>
    <input type="password" name="password" required>
    <label>Bağlı Firma:</label>
    <select name="company_id" required>
        <option value="">Seçiniz</option>
        <?php foreach ($companies as $cmp): ?>
            <option value="<?= htmlspecialchars($cmp['id']) ?>"><?= htmlspecialchars($cmp['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Ekle</button>
</form>

<hr>
<h3>Firma Admin Listesi</h3>
<table border="1" cellpadding="5">
    <tr><th>ID</th><th>Ad Soyad</th><th>Email</th><th>Firma</th><th>İşlem</th></tr>
    <?php if (empty($admins)): ?>
        <tr><td colspan="5" style="text-align:center;">Henüz hiç firma yöneticisi eklenmemiş.</td></tr>
    <?php else: ?>
        <?php foreach ($admins as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['id']) ?></td>
                <td><?= htmlspecialchars($a['full_name']) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><?= htmlspecialchars($a['company'] ?? '-') ?></td>
                <td>
                    <a href="edit_company_admin.php?id=<?= urlencode($a['id']) ?>">✏️ Düzenle</a>
                    </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
</body>
</html>