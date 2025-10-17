<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Geçersiz istek.");
    }

    $user_id = $_SESSION['user']['id'];
    $trip_id = $_POST['trip_id'] ?? null;
    $seat_number = (int)($_POST['seat_number'] ?? 0);
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $final_price_input = floatval($_POST['final_price'] ?? 0);

    if (!$trip_id || $seat_number <= 0) {
        throw new Exception("Geçersiz sefer veya koltuk seçimi.");
    }

    // Sefer bilgisi
    $trip = getTripById($pdo, $trip_id);
    if (!$trip) throw new Exception("Sefer bulunamadı.");
    if (isTripDeparted($trip)) throw new Exception("Bu seferin kalkış saati geçmiş, bilet alınamaz.");

    $price = floatval($trip['price']);
    $final_price = $price;

    $pdo->beginTransaction();

    // Kullanıcı bakiyesi
    $balance = getUserBalance($pdo, $user_id);

    // Kupon kontrol
    $discount = 0.0;
    $coupon_id = null;

    if ($coupon_code !== '') {
        $coupon = getCouponDetails($pdo, $coupon_code);
        if (!$coupon) throw new Exception("Kupon bulunamadı.");
        if (isCouponExpired($coupon)) throw new Exception("Kuponun süresi dolmuş.");
        if (!isCouponValidForCompany($pdo, $coupon, $trip_id)) {
            throw new Exception("Bu kupon firma için geçersiz.");
        }
        if (isCouponUsageLimitReached($pdo, $coupon['id'])) {
            throw new Exception("Bu kuponun kullanım limiti dolmuş.");
        }

        $discount = floatval($coupon['discount']);
        $coupon_id = $coupon['id'];
        $final_price = calculateFinalTicketPrice($price, $discount);
    }

    // Bakiye yeterli mi?
    if ($balance < $final_price) {
        throw new Exception("Yetersiz bakiye. Fiyat: {$final_price} ₺, Bakiyeniz: {$balance} ₺");
    }

    // Koltuk boş mu?
    if (isSeatBooked($pdo, $trip_id, $seat_number)) {
        throw new Exception("Seçilen koltuk zaten dolu.");
    }

    // Ticket oluştur
    $ticket_id = uniqid('ticket_');
    $stmt = $pdo->prepare("INSERT INTO Tickets (id, user_id, trip_id, total_price, status, created_at)
                           VALUES (?, ?, ?, ?, 'active', datetime('now'))");
    $stmt->execute([$ticket_id, $user_id, $trip_id, $final_price]);

    // Koltuk kaydı
    $seat_id = uniqid('seat_');
    $stmt = $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at)
                           VALUES (?, ?, ?, datetime('now'))");
    $stmt->execute([$seat_id, $ticket_id, $seat_number]);

    // Kupon kullanımı (varsa)
    if ($coupon_id) {
        $uc_id = uniqid('uc_');
        $stmt = $pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id, created_at)
                               VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute([$uc_id, $coupon_id, $user_id]);
    }

    // Bakiye düş
    $stmt = $pdo->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$final_price, $user_id]);

    $pdo->commit();

    header("Location: my_tickets.php?success=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<p style='color:red; font-family:sans-serif;'>Hata: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='index.php'>Ana sayfaya dön</a></p>";
}
