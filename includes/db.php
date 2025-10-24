<?php
$dbFile = __DIR__ . '/../database/bana1bilet.sqlite';
$dbInitScript = __DIR__ . '/db_init.php';

if (!file_exists(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0777, true);
}

$needInit = !file_exists($dbFile);

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5000);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    if ($needInit) {
        require_once $dbInitScript;
    }
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>