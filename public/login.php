<?php
require_once __DIR__ . '/../includes/auth.php';

$error = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginUser($email, $password)) {
        $success = "Giriş başarılı!";
        header('Location: index.php');
        exit;
    } else {
        $error[] = "E-posta veya şifre hatalı!";
    }
}

$page_title = "Bana1Bilet - Giriş Yap";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/message_comp.php';
?>

<div class="container">
    <h2>Giriş Yap</h2>
    <form method="POST" class="main-form">
        <div class="form-group">
            <input type="email" name="email" id="email" placeholder=" " required>
            <label for="email">E-posta</label>
        </div>  
        <div class="form-group">
            <input type="password" name="password" id="password" placeholder=" " required>
            <label for="password">Şifre</label>
        </div>
        <button type="submit" class="form-button">Giriş Yap</button>
    </form>
    <p>Hesabın yok mu? <a href="register.php">Kayıt Ol</a></p>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>