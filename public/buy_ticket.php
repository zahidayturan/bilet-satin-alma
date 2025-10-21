<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) die("Geçersiz sefer ID");

$user_id = $_SESSION['user']['id'];

// Kullanıcı bilgisi
$user = getUserProfileDetails($user_id); 
if (!$user) die("Kullanıcı bilgisi bulunamadı.");

// Sefer ve firma bilgisi
$trip = getTripDetailsForPurchase($trip_id);
if (!$trip) die("Sefer bulunamadı.");

// Geçmiş sefer kontrolü
if (isset($trip['error'])) {
    die(htmlspecialchars($trip['error']));
}

// Dolu koltuklar
$bookedSeats = getBookedSeatsForTrip($trip_id);
$capacity = (int)$trip['capacity'];

require_once __DIR__ . '/../includes/header.php';
?>

<head>
    <meta charset="UTF-8">
    <title>Bilet Satın Al</title>
    <style>
        .grid {
            display: flex;
            flex-direction: row;
            gap: 0 8px;
            margin: 6px 0;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        .row {
            display: grid;
            grid-template-rows: 60px 40px 40px;
            align-items: center;
        }
        .seat {
            border: 1px solid #224A59;
            text-align: center;
            min-width: 40px;
            padding: 6px 2px;
            border-radius: 6px;
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
        .seat.corridor {
            visibility: hidden;
            cursor: default;
            border: none;
            background-color: transparent;
        }
        button {
            padding: 14px 28px;
            background: #4A808C;
            color: white;
            border: none;
            border-radius: 4px;
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
        .coupon-row {
            display: flex;
            gap: 10px; 
            flex-direction: row;
            align-items: end;
        }
        .info {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="container">
    <a class="back-link" href="trip_detail.php?id=<?= urlencode($trip_id) ?>">← Sefer Detayına Geri Dön</a>

    <h2>Bilet Satın Al</h2>

    <div>
        <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor') ?></p>
        <p><strong>Sefer:</strong> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
        <p><strong>Kalkış:</strong> <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></p>
        <p><strong>Varış:</strong> <?= date('d.m.Y H:i', strtotime($trip['arrival_time'])) ?></p>
        <p><strong>Koltuk Kapasitesi:</strong> <?= $capacity ?> koltuk</p>
        <p><strong>Bilet Fiyatı:</strong> <span id="basePrice"><?= $trip['price'] ?></span> ₺ <br></p>
    </div>
    <br>
    <p><strong>Koltuk Seçimi (2+1 Düzen)</strong></p>
    <form method="POST" id="seatForm" action="buy_ticket_process.php">
        <div class="grid" id="seatGrid">
            <?php 
            $seatsPerRow = 3;
            $numRows = ceil($capacity / $seatsPerRow);
            $seatNumber = 1;

            for ($row = 1; $row <= $numRows; $row++): ?>
                <div class="row">
                    <?php 
                    for ($i = 0; $i < 2 && $seatNumber <= $capacity; $i++): 
                        $isBooked = in_array($seatNumber, $bookedSeats);
                    ?>
                        <div class="seat <?= $isBooked ? 'booked' : '' ?>" data-seat="<?= $seatNumber ?>"><?= $seatNumber ?></div>
                        <?php $seatNumber++; ?>
                    <?php endfor; ?>
                    <?php if ($i < 2):?>
                        <div class="seat corridor"></div>
                    <?php endif; ?>
                         <div class="seat corridor"></div>
                    <?php if ($seatNumber <= $capacity): 
                        $isBooked = in_array($seatNumber, $bookedSeats);
                    ?>
                        <div class="seat <?= $isBooked ? 'booked' : '' ?>" data-seat="<?= $seatNumber ?>"><?= $seatNumber ?></div>
                        <?php $seatNumber++; ?>
                    <?php else: ?>
                        <div class="seat corridor"></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>

        <div class="legend">
            <span><div class="legend-box free"></div> Boş</span>
            <span><div class="legend-box booked"></div> Dolu</span>
            <span><div class="legend-box selected"></div> Seçili</span>
        </div>

        <br><br>

        <div class="coupon-row" id="couponArea">
            <div>
                <label for="text"><strong>Kupon Kodu (opsiyonel)</strong></label>
                <input style="max-width:200px;" type="text" id="couponCode" name="coupon_code" placeholder=" ">
            </div>
            <button type="button" id="applyCoupon">Kupon Uygula</button>
        </div>

        <div id="activeCoupon" style="display:none; background:#dff0d8; padding:10px; border-radius:6px; margin-top:10px;">
            <strong>Kupon Uygulandı:</strong> <span id="appliedCode"></span>
            <button type="button" id="removeCoupon" style="margin-left:10px;">Kuponu Kaldır ❌</button>
            <strong>İndirimli Fiyat:</strong> <span id="finalPrice"><?= $trip['price'] ?></span> ₺
        </div>

        <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id) ?>">
        <input type="hidden" name="seat_number" id="seatInput" required>
        <input type="hidden" name="final_price" id="finalPriceInput" value="<?= htmlspecialchars($trip['price']) ?>">

        <br>
        
        <div class="info">
            <p style="text-align:center;"><strong>Bakiyeniz:</strong> <?= $user['balance'] ?> ₺</p>
            <p style="text-align:center;margin:8px 0;"><strong>Ödenecek Tutar:</strong> <span id="finalDisplayPrice"><?= number_format($trip['price'], 2) ?></span> ₺</p>
        </div>

        <button class="form-button" type="submit">💳 Bileti Satın Al</button>
    </form>
</div>

<script>
const seats = document.querySelectorAll('.seat:not(.corridor)');
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
const finalPriceElement = document.getElementById('finalPrice');
const finalPriceInputElement = document.getElementById('finalPriceInput');
const finalDisplayPriceElement = document.getElementById('finalDisplayPrice');

document.getElementById('applyCoupon').addEventListener('click', () => {
    const code = document.getElementById('couponCode').value.trim();
    if (!code) return alert('Kupon kodu girin.');
    if (appliedCoupon) return alert('Zaten bir kupon uygulanmış.');

    fetch(`check_coupon_process.php?trip_id=<?= $trip_id ?>&code=${encodeURIComponent(code)}`)
        .then(r => r.json())
        .then(data => {
            if (data.valid) {
                appliedCoupon = code;
                currentPrice = data.new_price;

                finalPriceElement.innerText = data.new_price.toFixed(2);
                finalPriceInputElement.value = data.new_price.toFixed(2);
                finalDisplayPriceElement.innerText = data.new_price.toFixed(2);

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
    finalPriceElement.innerText = basePrice.toFixed(2);
    finalPriceInputElement.value = basePrice.toFixed(2);
    finalDisplayPriceElement.innerText = basePrice.toFixed(2);
    
    document.getElementById('couponCode').value = '';
    document.getElementById('couponArea').style.display = 'block';
    document.getElementById('activeCoupon').style.display = 'none';
});
</script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>