<?php
$dbFile = __DIR__ . '/../database/biletler.sqlite';

if (!file_exists(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0777, true);
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5000); 

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>