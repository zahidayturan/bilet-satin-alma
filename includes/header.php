<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'Bana1Bilet' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/style.css">

    <style>
        header {
            padding: 0 0 24px 0;
            position: relative;
        }

        .logo {
            font-size: 20px;
        }

        .logo a {
            color: #0D0D0D;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .menu-toggle span {
            height: 2px;
            width: 20px;
            background: #0D0D0D;
            margin: 3px 0;
            border-radius: 3px;
        }

        .nav-links {
            display: flex;
            flex-direction: row;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #0D0D0D;
        }

        .header-auth-button {
            color: white;
            padding: 8px 16px;
            background: #0D0D0D;
            border-radius: 24px;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }

        .login-button {
            background: #224A59;
        }

        .non-bg-button {
            background: transparent;
            color: #0D0D0D;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }

            .nav-links a {
                width: 100%;
            }

            .nav-links {
                display: none;
                flex-direction: column;
                background: #FFFFFF;
                position: absolute;
                border-radius: 8px;
                top: 32px;
                right: 0;
                width: auto;
                padding: 10px;
                z-index: 10;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            }

            .nav-links.show {
                display: flex;
            }

            .header-auth-button {
                width: 100%;
            }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const toggle = document.getElementById("menu-toggle");
            const navLinks = document.getElementById("nav-links");

            toggle.addEventListener("click", function () {
                navLinks.classList.toggle("show");
            });
        });
    </script>
</head>
<body>
<div class="page-wrapper">
<?php
    $currentPage = basename($_SERVER['SCRIPT_NAME']);
?>

<header style="display: flex; justify-content: space-between; align-items: center;">
    <p class="logo"><a href="/index.php">Bana<strong>1</strong>Bilet</a></p>

    <div class="menu-toggle" id="menu-toggle">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <nav class="nav-links" id="nav-links">
        <?php if (isLoggedIn()): ?>
            <?php $role = $_SESSION['user']['role'] ?? ''; ?>

            <?php if ($role === 'admin'): ?>
                <?php if ($currentPage !== 'panel.php'): ?>
                    <a href="/admin/panel.php">
                        <button class="header-auth-button login-button">Admin Paneli</button>
                    </a>
                <?php endif; ?>
                <?php if ($currentPage !== 'logout.php'): ?>
                    <a href="/logout.php">
                        <button class="header-auth-button">Çıkış Yap</button>
                    </a>
                <?php endif; ?>

            <?php elseif ($role === 'company'): ?>
                <?php if ($currentPage !== 'profile.php'): ?>
                    <a href="/profile.php">
                        <button class="header-auth-button login-button">Profilim</button>
                    </a>
                <?php endif; ?>
                <?php if ($currentPage !== 'panel.php'): ?>
                    <a href="/company/panel.php">
                        <button class="header-auth-button login-button">Firma Paneli</button>
                    </a>
                <?php endif; ?>
                <?php if ($currentPage !== 'logout.php'): ?>
                    <a href="/logout.php">
                        <button class="header-auth-button">Çıkış Yap</button>
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <?php if ($currentPage !== 'my_tickets.php'): ?>
                    <a href="my_tickets.php">
                        <button class="header-auth-button non-bg-button">Biletlerim</button>
                    </a>
                <?php endif; ?>
                <?php if ($currentPage !== 'profile.php'): ?>
                    <a href="profile.php">
                        <button class="header-auth-button login-button">Profilim</button>
                    </a>
                <?php endif; ?>
                <?php if ($currentPage !== 'logout.php'): ?>
                    <a href="logout.php">
                        <button class="header-auth-button">Çıkış Yap</button>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

        <?php else: ?>
            <a href="login.php">
                <button class="header-auth-button login-button">Giriş Yap</button>
            </a>
            <a href="register.php">
                <button class="header-auth-button">Kayıt Ol</button>
            </a>
        <?php endif; ?>
    </nav>
</header>
<main>