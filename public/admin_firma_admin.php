<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Firmalar listesi (dropdown için)
$companies = $pdo->query("SELECT id, name FROM Bus_Company")->fetchAll(PDO::FETCH_ASSOC);

// Yeni firma admin oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $company_id = $_POST['company_id'];

    $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, password, role, company_id, created_at)
                           VALUES (:id, :name, :email, :pass, 'company', :cid, datetime('now'))");
    $stmt->execute([
        ':id' => uniqid('usr_'),
        ':name' => $full_name,
        ':email' => $email,
        ':pass' => $password,
        ':cid' => $company_id
    ]);
}

// Tüm firma adminleri
$admins = $pdo->query("SELECT u.id, u.full_name, u.email, b.name AS company
                       FROM User u LEFT JOIN Bus_Company b ON u.company_id = b.id
                       WHERE u.role = 'company'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Admin Yönetimi</title>
</head>
<body>
<h2>👤 Firma Admin Yönetimi</h2>
<a href="admin_panel.php">← Admin Paneli</a>
<hr>

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
    <?php foreach ($admins as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['id']) ?></td>
            <td><?= htmlspecialchars($a['full_name']) ?></td>
            <td><?= htmlspecialchars($a['email']) ?></td>
            <td><?= htmlspecialchars($a['company'] ?? '-') ?></td>
            <td>
                <a href="admin_edit_company_admin.php?id=<?= urlencode($a['id']) ?>">✏️ Düzenle</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
