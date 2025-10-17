<?php
require_once __DIR__ . '/../includes/auth.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginUser($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $message = "E-posta veya şifre hatalı!";
    }
}

$page_title = "Bana1Bilet - Giriş Yap";
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Giriş Yap</h2>
<?php if ($message): ?>
    <div class="message error">❌ <?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">
    <label>E-posta:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Şifre:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Giriş Yap</button>
</form>
<p>Hesabın yok mu? <a href="register.php">Kayıt Ol</a></p>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>