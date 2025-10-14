<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) die("Geçersiz sefer ID");

$user_id = $_SESSION['user']['id'];

// Kullanıcı bilgisi
$stmt = $pdo->prepare("SELECT * FROM User WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Sefer bilgisi
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Sefer bulunamadı.");

// 🚫 Geçmiş sefer kontrolü
if (strtotime($trip['departure_time']) <= time()) {
    die("Bu seferin kalkış saati geçmiş, bilet alınamaz.");
}

// Dolu koltuklar
$stmt = $pdo->prepare("
SELECT seat_number FROM Booked_Seats
WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ? AND status = 'active')
");
$stmt->execute([$trip_id]);
$bookedSeats = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number');

$capacity = (int)$trip['capacity'];
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
    .info { background:#f3f3f3; padding:8px; border-radius:6px; margin:8px 0; }
    button { cursor: pointer; }
  </style>
</head>
<body>
<h2>🎟️ Bilet Satın Al</h2>
<p><strong>Sefer:</strong> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
<p><strong>Kalkış:</strong> <?= htmlspecialchars($trip['departure_time']) ?></p>
<p><strong>Bakiye:</strong> <?= htmlspecialchars($user['balance']) ?> ₺</p>

<p class="info">
  <strong>Normal Fiyat:</strong> <span id="basePrice"><?= htmlspecialchars($trip['price']) ?></span> ₺<br>
  <strong>İndirimli Fiyat:</strong> <span id="finalPrice"><?= htmlspecialchars($trip['price']) ?></span> ₺
</p>

<form method="POST" id="seatForm" action="buy_ticket_process.php">
  <div class="grid" id="seatGrid">
    <?php for ($i = 1; $i <= $capacity; $i++): ?>
      <?php $isBooked = in_array($i, $bookedSeats); ?>
      <div class="seat <?= $isBooked ? 'booked' : '' ?>" data-seat="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>

  <div id="couponArea">
    <label>Kupon Kodu (opsiyonel):</label>
    <input type="text" id="couponCode" name="coupon_code">
    <button type="button" id="applyCoupon">Kupon Uygula</button>
  </div>

  <div id="activeCoupon" style="display:none; background:#dff0d8; padding:10px; border-radius:6px; margin-top:10px;">
    <strong>Kupon Uygulandı:</strong> <span id="appliedCode"></span>
    <button type="button" id="removeCoupon" style="margin-left:10px;">Kuponu Kaldır ❌</button>
  </div>

  <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id) ?>">
  <input type="hidden" name="seat_number" id="seatInput" required>
  <input type="hidden" name="final_price" id="finalPriceInput" value="<?= htmlspecialchars($trip['price']) ?>">

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

const basePrice = parseFloat(document.getElementById('basePrice').innerText);
let appliedCoupon = null;
let currentPrice = basePrice;

document.getElementById('applyCoupon').addEventListener('click', () => {
  const code = document.getElementById('couponCode').value.trim();
  if (!code) return alert('Kupon kodu girin.');
  if (appliedCoupon) return alert('Zaten bir kupon uygulanmış.');

  fetch(`check_coupon.php?trip_id=<?= $trip_id ?>&code=${encodeURIComponent(code)}`)
    .then(r => r.json())
    .then(data => {
      if (data.valid) {
        appliedCoupon = code;
        currentPrice = data.new_price;
        document.getElementById('finalPrice').innerText = data.new_price.toFixed(2);
        document.getElementById('finalPriceInput').value = data.new_price.toFixed(2);
        document.getElementById('couponArea').style.display = 'none';
        document.getElementById('activeCoupon').style.display = 'block';
        document.getElementById('appliedCode').innerText = code;
        alert('Kupon başarıyla uygulandı!');
      } else {
        alert(data.error);
      }
    });
});

document.getElementById('removeCoupon').addEventListener('click', () => {
  appliedCoupon = null;
  currentPrice = basePrice;
  document.getElementById('finalPrice').innerText = basePrice.toFixed(2);
  document.getElementById('finalPriceInput').value = basePrice.toFixed(2);
  document.getElementById('couponCode').value = '';
  document.getElementById('couponArea').style.display = 'block';
  document.getElementById('activeCoupon').style.display = 'none';
});
</script>
</body>
</html>
