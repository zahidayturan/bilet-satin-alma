<?php
// Başlık ve meta bilgileri ile standart bir HTML sayfası başlatıyoruz
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="shortcut icon" href="/assets/images/ico/favicon.ico" />
    <title>Giriş Bilgileri</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .role-section {
            margin-top: 20px;
            padding: 15px;
            border-left: 5px solid #224A59;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .role-section h4 {
            margin-top: 0;
            font-size: 1em;
            display: flex;
            align-items: center;
        }

        .role-section p {
            margin: 5px 0;
            font-size: 0.85em;
        }

        .role-section code {
            background-color: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
            color: #4A808C;
            font-weight: bold;
        }

    </style>
</head>
<body>

<div class="table-container">

    <div style="margin-bottom: 8px;"><a href="login.php">← Giriş Yap</a></div>
    <h2 style="color:#224A59;text-align:center;">Test Giriş Bilgileri</h2>
    <div class="role-section">
        <h4>Yolcu (User)</h4>
        <p><strong>Email:</strong> <code>yolcu@bana1bilet.com</code></p>
        <p><strong>Şifre:</strong> <code>user123</code></p>
    </div>

    <div class="role-section">
        <h4>Admin</h4>
        <p><strong>Email:</strong> <code>admin@bana1bilet.com</code></p>
        <p><strong>Şifre:</strong> <code>admin123</code></p>
    </div>

    <div class="role-section">
        <h4>Firma (Company)</h4>
        <p style="margin-bottom: 5px;">Herhangi bir firma hesabı kullanılabilir:</p>
        <ul style="padding-left: 12px; margin-top: 5px; margin-bottom: 5px;">
            <li><code>dortteker@bana1bilet.com</code></li>
            <li><code>mordoraseyahat@bana1bilet.com</code></li>
            <li><code>uzayagiden@bana1bilet.com</code></li>
            <li><code>gelesiyejet@bana1bilet.com</code></li>
        </ul>
        <p><strong>Ortak Şifre:</strong> <code>company123</code></p>
    </div>
    
    <p style="text-align: center; font-size: 0.85em;">
        Bu sayfa sadece test ve geliştirme amaçlıdır.
    </p>

</div>

</body>
</html>