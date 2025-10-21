<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);

require_once __DIR__ . '/../../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id){
  $errors[] = htmlspecialchars('Bilgiler alınamadı. Tekrar deneyin.');
} 

$errors = [];
$success = '';

if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

// 1. Firma listesini çekme (Dropdown için)
$companies = getCompanyListForDropdown();

// 2. Firma admin bilgisini çekme
$admin = getCompanyAdminById($id);
if (!$admin){
  $errors[] = htmlspecialchars('Firma admini bulunamadı.');
} 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_info'])) {
        // Bilgi güncelleme
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $companyId = $_POST['company_id'];

        if (updateCompanyAdminInfo($id, $fullName, $email, $companyId)) {
           $_SESSION['success_message'] = "Bilgiler başarıyla güncellendi.";
        } else {
            $_SESSION['error_message'] = "Bilgiler güncellenirken bir hata oluştu. (E-posta zaten kullanılıyor olabilir)";
        }
    }

    if (isset($_POST['change_password'])) {
        // Şifre değiştirme
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (!password_verify($old, $admin['password'])) {
            $_SESSION['error_message'] = "Eski şifre hatalı.";
        } elseif ($new !== $confirm) {
            $_SESSION['error_message'] = "Yeni şifreler eşleşmiyor.";
        } elseif (strlen($new) < 5) {
            $_SESSION['error_message'] = "Yeni şifre en az 5 karakter olmalı.";
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            
            if (updateCompanyAdminPassword($id, $hashed)) {
                $_SESSION['success_message'] = "Şifre başarıyla güncellendi.";
            } else {
                $_SESSION['error_message'] = "Şifre güncellenirken bir veritabanı hatası oluştu.";
            }
        }
    }

    header("Location: edit_company_admin.php?id=" . urlencode($id));
    exit;
}

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:20px;"><a href="show_company_admins.php">← Firma Adminlerine Geri Dön</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<?php if ($admin): ?>
  <h2>✏️ Firma Admin Düzenle</h2>

  <div class="container-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;justify-content: start;">
    <div class="container">
      <h3>Firma Admin Bilgilerini Güncelle</h3>
      <form method="POST">
        <label>Ad Soyad</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" required><br><br>

        <label>E-posta</label>
        <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required><br><br>

        <label>Firma</label>
        <select name="company_id" required>
          <?php foreach ($companies as $cmp): ?>
            <option value="<?= htmlspecialchars($cmp['id']) ?>" <?= $cmp['id'] === $admin['company_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cmp['name']) ?>
            </option>
          <?php endforeach; ?>
        </select><br><br>

        <button class="form-button" type="submit" name="update_info">Bilgileri Güncelle</button>
      </form>
    </div>

    <div class="container">
      <h3>Firma Admin Şifresini Değiştir</h3>
      <form method="POST">
        <label>Eski Şifre</label>
        <input type="password" name="old_password" required><br><br>

        <label>Yeni Şifre</label>
        <input type="password" name="new_password" required><br><br>

        <label>Yeni Şifre (Tekrar)</label>
        <input type="password" name="confirm_password" required><br><br>

        <button class="form-button" type="submit" name="change_password">Şifreyi Güncelle</button>
      </form>
    </div>
  </div>
<?php else: ?>
    <p style="text-align:center;margin-top:20px;">Firma admini bulunamadı.</p>
    <a href="/index.php"><p style="text-align:center;">Ana Sayfaya Dön</p></a>
<?php endif; ?>



<?php
require_once __DIR__ . '/../../includes/footer.php';
?>