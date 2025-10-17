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

$page_title = "Bana1Bilet - Kayıt Ol";
require_once __DIR__ . '/../includes/header.php';
?>

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

<?php
require_once __DIR__ . '/../includes/footer.php';
?>