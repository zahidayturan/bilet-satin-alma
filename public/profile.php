<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['id'];

$errors = [];
$success = "";

// Kullanıcı bilgilerini veritabanından çek
$stmt = $pdo->prepare("
    SELECT u.*, c.name AS company_name
    FROM User u
    LEFT JOIN Bus_Company c ON u.company_id = c.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$old || !$new || !$confirm) {
        $errors[] = "Tüm alanlar zorunludur.";
    } elseif ($new !== $confirm) {
        $errors[] = "Yeni şifreler eşleşmiyor.";
    } else {
        // Eski şifreyi doğrula
        $stmt = $pdo->prepare("SELECT password FROM User WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($old, $row['password'])) {
            $errors[] = "Mevcut şifre yanlış.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE User SET password=? WHERE id=?");
            $stmt->execute([$hash, $user_id]);
            $success = "Şifre başarıyla değiştirildi.";
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
    .errors { color: red; margin: 10px 0; }
    .success { color: green; margin: 10px 0; }
    label { display: block; margin-top: 10px; }
    input { padding: 6px; width: 100%; }
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
    <div class="success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="errors">
      <?php foreach ($errors as $err): ?>
        <div><?= htmlspecialchars($err) ?></div>
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
</div>
</body>
</html>
