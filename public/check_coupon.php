<?php
// Bu bir API/AJAX dosyası olduğu için sadece JSON döner.
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$trip_id = $_GET['trip_id'] ?? null;
$code = $_GET['code'] ?? '';

if (!$trip_id || !$code) {
    echo json_encode(['valid' => false, 'error' => 'Eksik parametre']);
    exit;
}

$result = validateCouponAndCalculatePrice($trip_id, $code);

// Fonksiyondan dönen sonucu JSON olarak çıktıla
if ($result['valid']) {
    // Sadece 'valid' ve 'new_price' döndürüyoruz, diğerlerini fonksiyonda saklıyoruz
    echo json_encode([
        'valid' => true, 
        'new_price' => $result['new_price'],
        // İsteğe bağlı olarak indirim miktarını da döndürebiliriz
        'discount_amount' => $result['discount']
    ]);
} else {
    echo json_encode(['valid' => false, 'error' => $result['error']]);
}
exit;