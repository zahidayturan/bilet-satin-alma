<?php
require_once __DIR__ . '/../includes/auth.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (loginUser($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $message = "E-posta veya şifre hatalı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
</head>
<body>
<h2>Giriş Yap</h2>
<?php if ($message): ?>
    <p style="color:red;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST">
    <label>E-posta:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Şifre:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Giriş Yap</button>
</form>
<p>Hesabın yok mu? <a href="register.php">Kayıt Ol</a></p>
</body>
</html>
