<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole(['user', 'company']);

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['id'];

$error = [];
$success = "";

// 1. Kullanıcı bilgilerini veritabanından çek
$profile = getUserProfileDetails($user_id);

if (!$profile) {
    die("Profil bilgisi bulunamadı.");
}

// 2. Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$old || !$new || !$confirm) {
        $error[] = "Tüm alanlar zorunludur.";
    } elseif ($new !== $confirm) {
        $error[] = "Yeni şifreler eşleşmiyor.";
    } else {
        $result = changeUserPassword($user_id, $old, $new);

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error[] = $result['message'];
        }
    }
}

// 3. Bakiye talep etme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    
    $result = addBalanceToUser($user_id);
    if ($result['success']) {
        header("Location: profile.php");
        exit;
    } else {
        $error[] = $result['message'];
    }
}
$page_title = "Bana1Bilet - Profilim";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/message_comp.php';
?>

<div class="container">
  <h2>Kişisel Bilgiler</h2>
  <div class="info">
    <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($profile['full_name']) ?></p>
    <p><strong>E-posta:</strong> <?= htmlspecialchars($profile['email']) ?></p>
    <p><strong>Katılma Tarihi:</strong> <?= htmlspecialchars(ucfirst($profile['created_at'])) ?></p>

    <?php if ($role === 'company'): ?>
      <p><strong>Firma:</strong> <?= htmlspecialchars($profile['company_name'] ?? '-') ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="container" style="margin-top: 40px;">
  <h2>Şifreyi Değiştir</h2>
  <?php if ($role === 'user'): ?>
    <form method="POST">
      <label>Mevcut Şifre:</label>
      <input type="password" name="old_password" required>

      <label>Yeni Şifre:</label>
      <input type="password" name="new_password" required>

      <label>Yeni Şifre (Tekrar):</label>
      <input type="password" name="confirm_password" required>

      <button type="submit" class="form-button" name="change_password">Şifreyi Güncelle</button>
    </form>
  <?php else: ?>
    <p>Şifrenizi veya bilgilerinizi değiştirmek için yöneticinize ulaşmalısınız.</p>
  <?php endif; ?>
</div>

<?php if ($role === 'user'): ?>
<div class="container" style="margin-top: 40px;">
    <h2>Bakiye Bilgisi</h2>
    <form method="POST">
        <input type="text" value="<?= htmlspecialchars($profile['balance']) ?> ₺" class="borderless-input" readonly>
        <button type="submit" class="form-button" name="add_balance">Bakiye Talep Et</button>
    </form>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>