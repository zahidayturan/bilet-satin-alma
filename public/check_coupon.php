<?php
require_once __DIR__ . '/../includes/db.php';

$trip_id = $_GET['trip_id'] ?? null;
$code = strtoupper(trim($_GET['code'] ?? ''));

if (!$trip_id || !$code) {
    echo json_encode(['valid' => false, 'error' => 'Eksik parametre']);
    exit;
}

$stmt = $pdo->prepare("SELECT price, company_id FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) {
    echo json_encode(['valid' => false, 'error' => 'Sefer bulunamadı']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM Coupons WHERE UPPER(code)=?");
$stmt->execute([$code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['valid' => false, 'error' => 'Kupon bulunamadı']);
    exit;
}

if ($coupon['expire_date'] < date('Y-m-d')) {
    echo json_encode(['valid' => false, 'error' => 'Kupon süresi dolmuş']);
    exit;
}

if ($coupon['company_id'] !== null && $coupon['company_id'] !== $trip['company_id']) {
    echo json_encode(['valid' => false, 'error' => 'Bu kupon bu firmaya ait değil']);
    exit;
}

$new_price = round($trip['price'] * (1 - floatval($coupon['discount']) / 100), 2);

echo json_encode(['valid' => true, 'new_price' => $new_price]);
