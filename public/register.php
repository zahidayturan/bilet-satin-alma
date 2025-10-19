<?php
require_once __DIR__ . '/../includes/auth.php';

$error = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($password !== $password_confirm) {
        $error[] = "Şifreler eşleşmiyor!";
    } elseif (registerUser($name, $email, $password)) {
        $success = "Kayıt başarılı! Giriş yapabilirsiniz.";
        header('Location: login.php?registered=1');
        exit;
    } else {
        $error[] = "Kayıt başarısız! Tekrar deneyin.";
    }
}

$name_value = htmlspecialchars($_POST['full_name'] ?? ''); 
$email_value = htmlspecialchars($_POST['email'] ?? '');

$page_title = "Bana1Bilet - Kayıt Ol";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/message_comp.php';
?>

<div class="container">
  <h2>Kayıt Ol</h2>

  <form method="POST" class="main-form">

      <div class="form-group">
        <input type="text" name="full_name" id="full_name" value="<?= $name_value ?>" placeholder=" " required>
        <label for="full_name">Ad Soyad</label>
      </div>

      <div class="form-group">
        <input type="email" name="email" id="full_name" value="<?= $email_value ?>" placeholder=" " required>
        <label for="email">E-posta</label>
      </div>

      <div class="form-group">
        <input type="password" name="password" id="password" placeholder=" " required>
        <label for="password">Şifre</label>
      </div>

      <div class="form-group">
        <input type="password" name="password_confirm" id="password_confirm" placeholder=" " required>
        <label for="password_confirm">Şifre Tekrar</label>
      </div>
      <button type="submit" class="form-button">Kayıt Ol</button>
  </form>
  <p>Zaten hesabın var mı? <a href="login.php">Giriş Yap</a></p>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
