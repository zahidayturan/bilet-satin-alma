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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'Bana1Bilet' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="shortcut icon" href="/assets/images/ico/favicon.ico" />
</head>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            min-height: 100dvh;
            margin: 0 12px;
            background-image: url('/assets/images/bg-line.png');
            background-size: auto, auto;
            background-repeat: no-repeat, repeat;
            background-position: center, 0 0;
        }

        .login-title{
            font-size: 20px;
            margin: 20px 8px;
            align-self: start;
        }

        .login-title a {
            color: #0D0D0D;
        }

    </style>
<body>
<p class="login-title"><a href="index.php">Bana<strong>1</strong>Bilet</a></p>
<div class="container" style="min-width:25%;max-width:400px;">
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
    <p style="text-align: center;margin-top:24px;">Hesabın yok mu? <a href="register.php">Kayıt Ol</a></p>
    <?php
        require_once __DIR__ . '/../includes/message_comp.php';
    ?>
</div>

<p style="text-align: center;margin:16px;">Gideceğin Her Yere<br>Bana1Bilet</p>

</body>
</html>