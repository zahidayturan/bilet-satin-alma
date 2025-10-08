<?php
require_once __DIR__ . '/../includes/auth.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (registerUser($name, $email, $password)) {
        header('Location: login.php?registered=1');
        exit;
    } else {
        $message = "Kayıt başarısız! Bu e-posta zaten kayıtlı olabilir.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
</head>
<body>
<h2>Kayıt Ol</h2>
<?php if ($message): ?>
    <p style="color:red;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Ad Soyad:</label><br>
    <input type="text" name="full_name" required><br><br>

    <label>E-posta:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Şifre:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Kayıt Ol</button>
</form>
<p>Zaten hesabın var mı? <a href="login.php">Giriş Yap</a></p>
</body>
</html>
