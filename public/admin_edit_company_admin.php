<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz ID");

$errors = [];
$success = '';

// 1. Firma listesini çekme (Dropdown için)
$companies = getCompanyListForDropdown();

// 2. Firma admin bilgisini çekme
$admin = getCompanyAdminById($id);
if (!$admin) die("Firma admini bulunamadı.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        // Bilgi güncelleme
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $companyId = $_POST['company_id'];

        if (updateCompanyAdminInfo($id, $fullName, $email, $companyId)) {
            $success = "Bilgiler başarıyla güncellendi. ✅";
            // Güncel veriyi formda göstermek için yeniden çekelim
            $admin = getCompanyAdminById($id); 
        } else {
            $errors[] = "Bilgiler güncellenirken bir hata oluştu. (E-posta zaten kullanılıyor olabilir) ❌";
        }
    }

    if (isset($_POST['change_password'])) {
        // Şifre değiştirme
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (!password_verify($old, $admin['password'])) {
            $errors[] = "Eski şifre hatalı. ❌";
        } elseif ($new !== $confirm) {
            $errors[] = "Yeni şifreler eşleşmiyor. ❌";
        } elseif (strlen($new) < 5) {
            $errors[] = "Yeni şifre en az 5 karakter olmalı. ❌";
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            
            if (updateCompanyAdminPassword($id, $hashed)) {
                $success = "Şifre başarıyla güncellendi. ✅";
            } else {
                $errors[] = "Şifre güncellenirken bir veritabanı hatası oluştu. ❌";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Admin Düzenle</title>
</head>
<body>
<h2>✏️ Firma Admin Düzenle</h2>
<a href="admin_firma_admin.php">← Geri Dön</a>
<hr>

<?php if ($errors): ?>
  <div style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($success): ?>
  <div style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<h3>Bilgileri Güncelle</h3>
<form method="POST">
  <label>Ad Soyad:</label>
  <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" required><br><br>

  <label>E-posta:</label>
  <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required><br><br>

  <label>Firma:</label>
  <select name="company_id" required>
    <?php foreach ($companies as $cmp): ?>
      <option value="<?= htmlspecialchars($cmp['id']) ?>" <?= $cmp['id'] === $admin['company_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($cmp['name']) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <button type="submit" name="update_info">Bilgileri Güncelle</button>
</form>

<hr>

<h3>Şifre Değiştir</h3>
<form method="POST">
  <label>Eski Şifre:</label>
  <input type="password" name="old_password" required><br><br>

  <label>Yeni Şifre:</label>
  <input type="password" name="new_password" required><br><br>

  <label>Yeni Şifre (Tekrar):</label>
  <input type="password" name="confirm_password" required><br><br>

  <button type="submit" name="change_password">Şifreyi Güncelle</button>
</form>

</body>
</html>