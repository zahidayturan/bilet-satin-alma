<?php
require_once __DIR__ . '/../includes/auth.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (registerUser($name, $email, $password)) {
        header('Location: login.php?registered=1');
        exit;
    } else {
        $message = "Kayıt başarısız! Tekrar deneyin.";
    }
}

$name_value = htmlspecialchars($_POST['full_name'] ?? ''); 
$email_value = htmlspecialchars($_POST['email'] ?? '');
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <style>
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .error { color: #880000; background-color: #ffdddd; border: 1px solid #ffaaaa; }
    </style>
</head>
<body>
<h2>Kayıt Ol</h2>
<?php if ($message): ?>
    <div class="message error">❌ <?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">
    <label>Ad Soyad:</label><br>
    <input type="text" name="full_name" value="<?= $name_value ?>" required><br><br>

    <label>E-posta:</label><br>
    <input type="email" name="email" value="<?= $email_value ?>" required><br><br>

    <label>Şifre:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Kayıt Ol</button>
</form>
<p>Zaten hesabın var mı? <a href="login.php">Giriş Yap</a></p>
</body>
</html>