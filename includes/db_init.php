<?php
$dbFile = __DIR__ . '/../database/biletler.sqlite';
$schemaFile = __DIR__ . '/schema.sql';

if (!file_exists(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0755, true);
}

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Foreign keys enforcement
    $db->exec('PRAGMA foreign_keys = ON;');

    // Eğer schema dosyası yoksa hata ver
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file bulunamadı: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);

    $db->beginTransaction();
    $db->exec($schema);
    $db->commit();

    echo "Veritabanı ve tablolar oluşturuldu: $dbFile\n";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "HATA: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// php includes/db_init.php
