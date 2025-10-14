<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user']['id'];


// 🔄 1. Geçmiş biletleri "expired" yap
$pdo->prepare("
    UPDATE Tickets 
    SET status = 'expired'
    WHERE user_id = ? 
      AND status = 'active' 
      AND trip_id IN (
        SELECT id FROM Trips WHERE datetime(departure_time) < datetime('now')
      )
")->execute([$user_id]);


// 🎟️ 2. Biletleri detaylı çek
$stmt = $pdo->prepare("
SELECT 
  t.id AS ticket_id, 
  t.status, 
  t.total_price, 
  tr.departure_city, 
  tr.destination_city, 
  tr.departure_time, 
  tr.arrival_time,
  tr.id AS trip_id,
  bc.name AS company_name,
  b.seat_number
FROM Tickets t
JOIN Trips tr ON t.trip_id = tr.id
LEFT JOIN Bus_Company bc ON tr.company_id = bc.id
LEFT JOIN Booked_Seats b ON b.ticket_id = t.id
WHERE t.user_id = ?
ORDER BY t.created_at DESC
");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Biletlerim</title>
  <style>
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background: #f0f0f0; }
    tr:nth-child(even) { background: #fafafa; }
    .expired { color: gray; }
    .cancel-link { color: red; }
  </style>
</head>
<body>

<h2>🎫 Biletlerim</h2>
<a href="index.php">← Ana Sayfa</a>
<hr>

<?php if (isset($_GET['success'])): ?>
  <p style="color:green;">Bilet başarıyla satın alındı!</p>
<?php endif; ?>

<?php if (empty($tickets)): ?>
  <p>Hiç biletiniz yok.</p>
<?php else: ?>
  <table>
    <tr>
      <th>Firma</th>
      <th>Kalkış</th>
      <th>Varış</th>
      <th>Kalkış Tarihi</th>
      <th>Koltuk No</th>
      <th>Durum</th>
      <th>Fiyat</th>
      <th>İşlem</th>
    </tr>

    <?php foreach ($tickets as $t): ?>
      <tr class="<?= $t['status'] === 'expired' ? 'expired' : '' ?>">
        <td><?= htmlspecialchars($t['company_name'] ?? 'Bilinmiyor') ?></td>
        <td><?= htmlspecialchars($t['departure_city']) ?></td>
        <td><?= htmlspecialchars($t['destination_city']) ?></td>
        <td><?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></td>
        <td><?= htmlspecialchars($t['seat_number'] ?? '-') ?></td>
        <td><?= htmlspecialchars(ucfirst($t['status'])) ?></td>
        <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
        <td>
          <?php
            $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600;
            if ($t['status'] === 'active' && $hoursLeft > 1): ?>
              <a href="cancel_ticket.php?id=<?= urlencode($t['ticket_id']) ?>" class="cancel-link">İptal Et</a> |
          <?php endif; ?>
          <a href="download_ticket.php?id=<?= urlencode($t['ticket_id']) ?>">PDF İndir</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

</body>
</html>
