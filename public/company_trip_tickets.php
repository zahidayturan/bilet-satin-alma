<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];
$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) die("Geçersiz istek.");

// 🧾 Sefer doğrulama
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $company_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trip) die("Bu sefer size ait değil veya bulunamadı.");

// 🎟️ Bilet iptali işlemi
if (isset($_GET['cancel'])) {
    $ticket_id = $_GET['cancel'];

    try {
        $pdo->beginTransaction();

        // İlgili bilet bilgilerini al
        $stmt = $pdo->prepare("
            SELECT t.id, t.total_price, t.status, t.user_id, tr.departure_time
            FROM Tickets t
            JOIN Trips tr ON tr.id = t.trip_id
            WHERE t.id = ? AND tr.company_id = ?
        ");
        $stmt->execute([$ticket_id, $company_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) throw new Exception("Bu bilet size ait değil veya bulunamadı.");
        if ($ticket['status'] !== 'active') throw new Exception("Bu bilet zaten iptal edilmiş.");
        
        $hoursLeft = (strtotime($ticket['departure_time']) - time()) / 3600;
        if ($hoursLeft < 1) throw new Exception("Kalkışa 1 saatten az kaldığı için iptal edilemez.");

        // 1️⃣ Bileti iptal et
        $stmt = $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?");
        $stmt->execute([$ticket_id]);

        // 2️⃣ Koltuğu serbest bırak
        $stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id=?");
        $stmt->execute([$ticket_id]);

        // 3️⃣ Kullanıcıya ücret iadesi yap
        $stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id=?");
        $stmt->execute([$ticket['total_price'], $ticket['user_id']]);

        $pdo->commit();

        header("Location: company_trip_tickets.php?trip_id=" . urlencode($trip_id) . "&success=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Hata: " . htmlspecialchars($e->getMessage()));
    }
}

// 🎫 Seferin biletlerini getir
$stmt = $pdo->prepare("
    SELECT 
        t.id AS ticket_id,
        t.status,
        t.total_price,
        u.full_name,
        u.email,
        bs.seat_number,
        tr.departure_time
    FROM Tickets t
    JOIN User u ON t.user_id = u.id
    LEFT JOIN Booked_Seats bs ON bs.ticket_id = t.id
    JOIN Trips tr ON t.trip_id = tr.id
    WHERE t.trip_id = ?
    ORDER BY bs.seat_number ASC
");
$stmt->execute([$trip_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?> Biletleri</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
    th { background-color: #eee; }
    .success { color: green; margin: 10px 0; }
    .error { color: red; margin: 10px 0; }
  </style>
</head>
<body>
<h2>🎟️ Bilet Listesi - <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></h2>
<a href="company_trips.php">← Geri</a>
<hr>

<?php if (isset($_GET['success'])): ?>
  <div class="success">Bilet başarıyla iptal edildi ve ücret iadesi yapıldı.</div>
<?php endif; ?>

<table>
<tr>
  <th>Koltuk No</th>
  <th>Yolcu Adı</th>
  <th>Email</th>
  <th>Durum</th>
  <th>Ücret</th>
  <th>İşlem</th>
</tr>

<?php if ($tickets): ?>
  <?php foreach ($tickets as $tk): ?>
    <?php $hoursLeft = (strtotime($tk['departure_time']) - time()) / 3600; ?>
  <tr>
    <td><?= htmlspecialchars($tk['seat_number'] ?? '-') ?></td>
    <td><?= htmlspecialchars($tk['full_name']) ?></td>
    <td><?= htmlspecialchars($tk['email']) ?></td>
    <td><?= htmlspecialchars(ucfirst($tk['status'])) ?></td>
    <td><?= htmlspecialchars($tk['total_price']) ?> ₺</td>
    <td>
      <?php if ($tk['status'] === 'active' && $hoursLeft > 1): ?>
        <a href="?trip_id=<?= urlencode($trip_id) ?>&cancel=<?= urlencode($tk['ticket_id']) ?>"
           onclick="return confirm('Bu bileti iptal edip yolcuya ücret iadesi yapmak istediğinize emin misiniz?')">
           ❌ İptal Et
        </a>
      <?php else: ?>
        <em>İptal Edilemez</em>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr><td colspan="6">Bu sefere ait bilet bulunamadı.</td></tr>
<?php endif; ?>
</table>
</body>
</html>
