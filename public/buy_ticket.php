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

// Sefer ve firma bilgisi
$stmt = $pdo->prepare("
    SELECT Trips.*, Bus_Company.name AS company_name 
    FROM Trips
    LEFT JOIN Bus_Company ON Trips.company_id = Bus_Company.id
    WHERE Trips.id = ?
");
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
    body {
      font-family: Arial, sans-serif;
      margin: 30px;
      background: #f8f8f8;
    }
    .container {
      background: white;
      padding: 20px;
      border-radius: 10px;
      max-width: 800px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 { margin-top: 0; }
    .grid {
      display: grid;
      grid-template-columns: repeat(5, 60px);
      gap: 10px;
      margin: 20px 0;
      justify-content: center;
    }
    .seat {
      border: 2px solid #444;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      background-color: #ecf0f1;
    }
    .seat.booked {
      background-color: #e74c3c;
      color: white;
      cursor: not-allowed;
    }
    .seat.selected {
      background-color: #27ae60;
      color: white;
    }
    .info, .fare {
      background:#f3f3f3;
      padding:10px;
      border-radius:6px;
      margin:12px 0;
    }
    button {
      padding: 10px 20px;
      background: #3498db;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background: #2980b9;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 15px;
    }
    .legend {
      margin-top: 10px;
      display: flex;
      gap: 20px;
    }
    .legend span {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .legend-box {
      width: 20px;
      height: 20px;
      display: inline-block;
      border-radius: 4px;
      border: 1px solid #ccc;
    }
    .free { background-color: #ecf0f1; }
    .booked { background-color: #e74c3c; }
    .selected { background-color: #27ae60; }
  </style>
</head>
<body>

<div class="container">
  <a class="back-link" href="trip_detail.php?id=<?= urlencode($trip_id) ?>">← Sefer Detayına Geri Dön</a>

  <h2>🎟️ Bilet Satın Al</h2>

  <div class="info">
    <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor') ?></p>
    <p><strong>Sefer:</strong> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
    <p><strong>Kalkış:</strong> <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></p>
    <p><strong>Koltuk Kapasitesi:</strong> <?= $capacity ?> koltuk</p>
    <p><strong>Bakiyeniz:</strong> <?= $user['balance'] ?> ₺</p>
  </div>

  <div class="fare">
    <strong>Normal Fiyat:</strong> <span id="basePrice"><?= $trip['price'] ?></span> ₺ <br>
    <strong>İndirimli Fiyat:</strong> <span id="finalPrice"><?= $trip['price'] ?></span> ₺
  </div>

  <form method="POST" id="seatForm" action="buy_ticket_process.php">
    <div class="grid" id="seatGrid">
      <?php for ($i = 1; $i <= $capacity; $i++): ?>
        <?php $isBooked = in_array($i, $bookedSeats); ?>
        <div class="seat <?= $isBooked ? 'booked' : '' ?>" data-seat="<?= $i ?>"><?= $i ?></div>
      <?php endfor; ?>
    </div>

    <div class="legend">
      <span><div class="legend-box free"></div> Boş</span>
      <span><div class="legend-box booked"></div> Dolu</span>
      <span><div class="legend-box selected"></div> Seçili</span>
    </div>

    <br>

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
</div>

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
