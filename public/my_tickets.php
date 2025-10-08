<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
SELECT t.id AS ticket_id, t.status, t.total_price, tr.departure_city, tr.destination_city, tr.departure_time
FROM Tickets t
JOIN Trips tr ON t.trip_id = tr.id
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
</head>
<body>
<h2>🎫 Biletlerim</h2>
<a href="index.php">← Ana Sayfa</a>
<hr>

<?php if (isset($_GET['success'])): ?>
  <p style="color:green;">Bilet başarıyla satın alındı!</p>
<?php endif; ?>

<table border="1" cellpadding="6">
<tr><th>Kalkış</th><th>Varış</th><th>Tarih</th><th>Durum</th><th>Fiyat</th><th>İşlem</th></tr>

<?php foreach ($tickets as $t): ?>
<tr>
  <td><?= htmlspecialchars($t['departure_city']) ?></td>
  <td><?= htmlspecialchars($t['destination_city']) ?></td>
  <td><?= htmlspecialchars($t['departure_time']) ?></td>
  <td><?= htmlspecialchars($t['status']) ?></td>
  <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
  <td>
    <?php
      $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600;
      if ($t['status'] === 'active' && $hoursLeft > 1): ?>
        <a href="cancel_ticket.php?id=<?= urlencode($t['ticket_id']) ?>">İptal Et</a> |
    <?php endif; ?>
    <a href="download_ticket.php?id=<?= urlencode($t['ticket_id']) ?>">PDF İndir</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
