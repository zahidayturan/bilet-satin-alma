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
            padding: 0 0 10px 0;
            position: relative;
        }

        .logo {
            font-size: 20px;
            font-weight: bold;
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

        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }

            .nav-links a {
                color: #fff;
            }

            .nav-links {
                display: none;
                flex-direction: column;
                background: #224A59;
                color: #fff;
                position: absolute;
                border-radius: 4px;
                top: 60px;
                right: 0;
                width: auto;
                padding: 10px;
                z-index: 10;
            }

            .nav-links.show {
                display: flex;
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
<header style="display: flex; justify-content: space-between; align-items: center;">
    
    <p class="logo"><a href="index.php">Bana1Bilet</a></p>
    <div class="menu-toggle" id="menu-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>

    <nav class="nav-links" id="nav-links">
        <?php if (isLoggedIn()): ?>
            <?php $role = $_SESSION['user']['role'] ?? ''; ?>
            <?php if ($role === 'admin'): ?>
                <span>Admin - Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span>
                <a href="admin/panel.php">Admin Paneli</a>
                <a href="admin/show_companies.php">Firmalar</a>
                <a href="admin/show_company_admins.php">Firma Adminleri</a>
                <a href="admin/coupons.php">Bütün Kuponlar</a>
                <a href="logout.php">Oturumu Kapat</a>
            <?php elseif ($role === 'company'): ?>
                <span>Firma - Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span>
                <a href="profile.php">Profilim</a>
                <a href="company/panel.php">Firma Paneli</a>
                <a href="company/trips.php">Seferlerim</a>
                <a href="company/coupons.php">Firma Kuponları</a>
                <a href="company/tickets.php">Biletler</a>
                <a href="logout.php">Oturumu Kapat</a>
            <?php else: ?>
                <span>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span>
                <a href="profile.php">Profilim</a>
                <a href="my_tickets.php">Biletlerim</a>
                <a href="logout.php">Oturumu Kapat</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="login.php">Giriş Yap</a>
            <a href="register.php">Kayıt Ol</a>
        <?php endif; ?>
    </nav>
</header>
