<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

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

    // 🚍 Sefer bilgisi
    $stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$trip) throw new Exception("Sefer bulunamadı.");

    // 🚫 Kalkış geçmiş mi?
    if (strtotime($trip['departure_time']) <= time()) {
        throw new Exception("Bu seferin kalkış saati geçmiş, bilet alınamaz.");
    }

    $price = floatval($trip['price']);
    $final_price = $price;

    $pdo->beginTransaction();

    // 💳 Kullanıcı bakiyesi
    $stmt = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
    $stmt->execute([$user_id]);
    $balance = floatval($stmt->fetchColumn());

    // 💸 Kupon kontrol (tek kupon)
    $discount = 0.0;
    $coupon_id = null;

    if ($coupon_code !== '') {
        $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE UPPER(code) = ?");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            throw new Exception("Kupon bulunamadı.");
        }

        if ($coupon['expire_date'] < date('Y-m-d')) {
            throw new Exception("Kuponun süresi dolmuş.");
        }

        // Kupon firma uyumluluğu kontrolü
        if ($coupon['company_id']) {
            $stmt = $pdo->prepare("SELECT company_id FROM Trips WHERE id = ?");
            $stmt->execute([$trip_id]);
            $tripCompany = $stmt->fetchColumn();
            if ($coupon['company_id'] !== $tripCompany) {
                throw new Exception("Bu kupon bu firmaya ait değil.");
            }
        }

        // Kullanım limiti kontrolü
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = ?");
        $stmt->execute([$coupon['id']]);
        $usedCount = (int)$stmt->fetchColumn();

        if ($usedCount >= (int)$coupon['usage_limit']) {
            throw new Exception("Bu kuponun kullanım limiti dolmuş.");
        }

        $discount = floatval($coupon['discount']);
        $coupon_id = $coupon['id'];
        $final_price = round($price * (1 - $discount / 100), 2);
    }

    // 💰 Bakiye yeterli mi?
    if ($balance < $final_price) {
        throw new Exception("Yetersiz bakiye. Fiyat: {$final_price} ₺, Bakiyeniz: {$balance} ₺");
    }

    // 🪑 Koltuk boş mu?
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Booked_Seats 
        WHERE seat_number = :seat 
        AND ticket_id IN (
            SELECT id FROM Tickets WHERE trip_id = :trip AND status = 'active'
        )
    ");
    $stmt->execute([':seat' => $seat_number, ':trip' => $trip_id]);
    $isBooked = (int)$stmt->fetchColumn();
    if ($isBooked) throw new Exception("Seçilen koltuk zaten dolu.");

    // 🎫 Ticket oluştur
    $ticket_id = uniqid('ticket_');
    $stmt = $pdo->prepare("INSERT INTO Tickets (id, user_id, trip_id, total_price, status, created_at)
                           VALUES (?, ?, ?, ?, 'active', datetime('now'))");
    $stmt->execute([$ticket_id, $user_id, $trip_id, $final_price]);

    // 🪑 Koltuk kaydı
    $seat_id = uniqid('seat_');
    $stmt = $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at)
                           VALUES (?, ?, ?, datetime('now'))");
    $stmt->execute([$seat_id, $ticket_id, $seat_number]);

    // 🎟️ Kupon kullanımı (varsa)
    if ($coupon_id) {
        $uc_id = uniqid('uc_');
        $stmt = $pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id, created_at)
                               VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute([$uc_id, $coupon_id, $user_id]);
    }

    // 💳 Bakiye düş
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
