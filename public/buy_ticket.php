<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) die("Geçersiz sefer ID");

$user_id = $_SESSION['user']['id'];

// Kullanıcı ve sefer bilgisi
$stmt = $pdo->prepare("SELECT * FROM User WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı");

// Dolu koltukları çek
$stmt = $pdo->prepare("
SELECT seat_number FROM Booked_Seats
WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ? AND status = 'active')
");
$stmt->execute([$trip_id]);
$bookedSeats = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number');

$capacity = (int)$trip['capacity'];
$errors = [];
$discount = 0.0;
$final_price = floatval($trip['price']);
$applied_coupon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seat_number = isset($_POST['seat_number']) ? (int)$_POST['seat_number'] : 0;
    $couponCodeRaw = trim($_POST['coupon_code'] ?? '');
    $couponCode = $couponCodeRaw !== '' ? strtoupper($couponCodeRaw) : '';

    try {
        $pdo->beginTransaction();

        // Kapasite kontrol
        $stmt = $pdo->prepare("SELECT COUNT(*) AS dolu FROM Tickets WHERE trip_id = ? AND status = 'active'");
        $stmt->execute([$trip_id]);
        $dolu = (int)$stmt->fetch(PDO::FETCH_ASSOC)['dolu'];
        if ($dolu >= $capacity) {
            throw new Exception("Bu sefer dolu, bilet alınamaz.");
        }

        // Koltuk boş mu?
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS dolu
            FROM Booked_Seats
            WHERE seat_number = :seat AND ticket_id IN (
                SELECT id FROM Tickets WHERE trip_id = :trip AND status = 'active'
            )
        ");
        $stmt->execute([':seat' => $seat_number, ':trip' => $trip_id]);
        $isBooked = (int)$stmt->fetch(PDO::FETCH_ASSOC)['dolu'];
        if ($isBooked) {
            throw new Exception("Seçtiğiniz koltuk zaten dolu. Lütfen başka bir koltuk seçin.");
        }

        // Kupon kontrolü
        $discount = 0.0;
        $couponId = null;
        if ($couponCode !== '') {
            $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE UPPER(code) = :code");
            $stmt->execute([':code' => $couponCode]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$coupon) throw new Exception("Kupon bulunamadı.");
            if ($coupon['expire_date'] < date('Y-m-d')) throw new Exception("Kuponun süresi dolmuş.");

            // Firma kontrolü (sadece o firmanın kuponu geçerli olmalı)
            $stmt = $pdo->prepare("SELECT company_id FROM Trips WHERE id = ?");
            $stmt->execute([$trip_id]);
            $tripCompany = $stmt->fetchColumn();

            if ($coupon['company_id'] !== null && $coupon['company_id'] !== $tripCompany) {
                throw new Exception("Bu kupon bu firmaya ait değil.");
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) AS used FROM User_Coupons WHERE coupon_id = ?");
            $stmt->execute([$coupon['id']]);
            $used = (int)$stmt->fetch(PDO::FETCH_ASSOC)['used'];
            if ($used >= (int)$coupon['usage_limit']) throw new Exception("Bu kuponun kullanım limiti dolmuş.");

            $discount = floatval($coupon['discount']);
            $couponId = $coupon['id'];
            $applied_coupon = $coupon['code'];
        }

        // Fiyat hesapla
        $price = floatval($trip['price']);
        $final_price = round($price * (1 - $discount / 100), 2);

        // 💰 Kullanıcının bakiyesini kontrol et
        $stmt = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
        $stmt->execute([$user_id]);
        $balance = (float)$stmt->fetchColumn();

        if ($balance < $final_price) {
            throw new Exception("Yetersiz bakiye! Bilet ücreti: {$final_price} ₺, bakiyeniz: {$balance} ₺");
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

        // Kupon kullanımı kaydı (varsa)
        if ($couponId !== null) {
            $uc_id = uniqid('uc_');
            $stmt = $pdo->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id, created_at)
                                   VALUES (?, ?, ?, datetime('now'))");
            $stmt->execute([$uc_id, $couponId, $user_id]);
        }

        // 💳 Kullanıcının bakiyesinden düş
        $stmt = $pdo->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$final_price, $user_id]);

        $pdo->commit();

        header("Location: my_tickets.php?success=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Bilet Satın Al</title>
  <style>
    .grid { display: grid; grid-template-columns: repeat(4, 60px); gap: 10px; margin: 20px 0; }
    .seat { border: 2px solid #444; text-align: center; padding: 10px; border-radius: 8px; cursor: pointer; }
    .seat.booked { background-color: #e74c3c; color: white; cursor: not-allowed; }
    .seat.selected { background-color: #27ae60; color: white; }
    .errors { color: red; margin: 10px 0; }
    .info { background:#f3f3f3; padding:8px; border-radius:6px; margin:8px 0; }
  </style>
</head>
<body>
<h2>🎟️ Bilet Satın Al</h2>
<p><strong>Sefer:</strong> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
<p><strong>Kalkış:</strong> <?= htmlspecialchars($trip['departure_time']) ?></p>
<p><strong>Bakiye:</strong> <?= htmlspecialchars($user['balance']) ?> ₺</p>
<p class="info">
  <strong>Normal Fiyat:</strong> <?= htmlspecialchars($trip['price']) ?> ₺<br>
  <?php if ($discount > 0): ?>
    <strong>Uygulanan Kupon:</strong> <?= htmlspecialchars($applied_coupon) ?><br>
    <strong>İndirimli Fiyat:</strong> <?= $final_price ?> ₺
  <?php endif; ?>
</p>

<?php if ($errors): ?>
  <div class="errors">
    <?php foreach ($errors as $err): ?><div><?= htmlspecialchars($err) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="POST" id="seatForm">
  <div class="grid" id="seatGrid">
    <?php for ($i = 1; $i <= $capacity; $i++): ?>
      <?php $isBooked = in_array($i, $bookedSeats); ?>
      <div class="seat <?= $isBooked ? 'booked' : '' ?>" data-seat="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>

  <label>Kupon Kodu (opsiyonel):</label>
  <input type="text" name="coupon_code" value="<?= htmlspecialchars($_POST['coupon_code'] ?? '') ?>">

  <input type="hidden" name="seat_number" id="seatInput" required>
  <br><br>
  <button type="submit">💳 Bileti Satın Al</button>
</form>

<script>
const seats = document.querySelectorAll('.seat');
let selected = null;
seats.forEach(seat => {
  if (!seat.classList.contains('booked')) {
    seat.addEventListener('click', () => {
      if (selected) selected.classList.remove('selected');
      selected = seat;
      seat.classList.add('selected');
      document.getElementById('seatInput').value = seat.dataset.seat;
    });
  }
});
</script>
</body>
</html>
