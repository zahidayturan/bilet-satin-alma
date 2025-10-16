<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php'; // Yeni fonksiyonlar için
requireLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['id'];

$errors = [];
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
        $errors[] = "Tüm alanlar zorunludur.";
    } elseif ($new !== $confirm) {
        $errors[] = "Yeni şifreler eşleşmiyor.";
    } else {
        // Şifre değiştirme iş mantığını fonksiyona devret
        $result = changeUserPassword($user_id, $old, $new);

        if ($result['success']) {
            $success = $result['message'];
            // Form verilerini temizlemek için POST'u temizleyebiliriz.
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Profilim</title>
  <style>
    body { font-family: Arial; margin: 30px; }
    .container { max-width: 600px; margin: auto; background: #f8f8f8; padding: 20px; border-radius: 10px; }
    h2 { text-align: center; }
    .info { background: #fff; padding: 10px 20px; border-radius: 6px; margin-bottom: 20px; }
    .errors { color: #880000; background-color: #ffdddd; border: 1px solid #ffaaaa; padding: 10px; margin: 10px 0; border-radius: 4px; }
    .success { color: #006600; background-color: #ddffdd; border: 1px solid #aaffaa; padding: 10px; margin: 10px 0; border-radius: 4px; }
    label { display: block; margin-top: 10px; }
    input { padding: 6px; width: 100%; box-sizing: border-box; }
    button { margin-top: 15px; padding: 10px; width: 100%; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; }
    button:hover { background: #2980b9; }
  </style>
</head>
<body>
<div class="container">
  <h2>👤 Profilim</h2>
  <p><a href="index.php">← Ana Sayfa</a></p>
  <hr>

  <?php if ($success): ?>
    <div class="success">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="errors">
      <?php foreach ($errors as $err): ?>
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

  <?php if ($role === 'user' || $role === 'admin'): ?>
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
</body>
</html>