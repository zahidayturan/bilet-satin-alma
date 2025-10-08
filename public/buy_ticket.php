<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) die("Geçersiz sefer ID");

// Sefer bilgisi
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı");

// Kupon kontrolü
$discount = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponCode = strtoupper(trim($_POST['coupon_code']));
    if ($couponCode !== '') {
        $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE code = ? AND expire_date >= DATE('now')");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($coupon) {
            $discount = $coupon['discount'];
        } else {
            $error = "Geçersiz veya süresi dolmuş kupon!";
        }
    }

    $final_price = $trip['price'] * (1 - $discount / 100);
    $user_id = $_SESSION['user']['id'];

    // Bilet oluştur
    $stmt = $pdo->prepare("INSERT INTO Tickets (id, user_id, trip_id, total_price, status, created_at)
                           VALUES (:id, :uid, :tid, :price, 'active', datetime('now'))");
    $stmt->execute([
        ':id' => uniqid('ticket_'),
        ':uid' => $user_id,
        ':tid' => $trip_id,
        ':price' => $final_price
    ]);

    header("Location: my_tickets.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Bilet Satın Al</title>
</head>
<body>
<h2>🎟️ Bilet Satın Al</h2>
<a href="index.php">← Ana Sayfa</a>
<hr>

<p><strong>Kalkış:</strong> <?= htmlspecialchars($trip['departure_city']) ?></p>
<p><strong>Varış:</strong> <?= htmlspecialchars($trip['destination_city']) ?></p>
<p><strong>Kalkış Saati:</strong> <?= htmlspecialchars($trip['departure_time']) ?></p>
<p><strong>Fiyat:</strong> <?= htmlspecialchars($trip['price']) ?> ₺</p>

<form method="POST">
  <label>Kupon Kodu:</label>
  <input type="text" name="coupon_code">
  <button type="submit">Satın Al</button>
</form>

<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
