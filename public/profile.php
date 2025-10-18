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

$page_title = "Bana1Bilet - Profilim";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <h2>👤 Profilim</h2>
  <p><a href="index.php">← Ana Sayfa</a></p>
  <hr>

  <?php if ($success): ?>
    <div class="success">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="error">
      <?php foreach ($error as $err): ?>
        <div>❌ <?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="info">
    <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($profile['full_name']) ?></p>
    <p><strong>E-posta:</strong> <?= htmlspecialchars($profile['email']) ?></p>
    <p><strong>Rol:</strong> <?= htmlspecialchars(ucfirst($profile['role'])) ?></p>

    <?php if ($role === 'company'): ?>
      <p><strong>Firma:</strong> <?= htmlspecialchars($profile['company_name'] ?? '-') ?></p>
    <?php endif; ?>

    <?php if ($role === 'user'): ?>
      <p><strong>Bakiye:</strong> <?= htmlspecialchars($profile['balance']) ?> ₺</p>
    <?php endif; ?>
  </div>

  <?php if ($role === 'user'): ?>
    <h3>🔒 Şifre Değiştir</h3>
    <form method="POST">
      <label>Mevcut Şifre:</label>
      <input type="password" name="old_password" required>

      <label>Yeni Şifre:</label>
      <input type="password" name="new_password" required>

      <label>Yeni Şifre (Tekrar):</label>
      <input type="password" name="confirm_password" required>

      <button type="submit" name="change_password">Şifreyi Güncelle</button>
    </form>
  <?php else: ?>
    <p>Şifrenizi veya bilgilerinizi değiştirmek için yöneticinize ulaşmalısınız.</p>
  <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>