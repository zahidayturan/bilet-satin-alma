<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

$stmt = $pdo->prepare("
SELECT t.id AS ticket_id, tr.departure_city, tr.destination_city, tr.departure_time, u.full_name AS user_name, t.status
FROM Tickets t
JOIN Trips tr ON tr.id = t.trip_id
JOIN User u ON u.id = t.user_id
WHERE tr.company_id = :cid
ORDER BY tr.departure_time DESC
");
$stmt->execute([':cid' => $company_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Biletler</title>
</head>
<body>
<h2>🎫 Firma Biletleri</h2>
<a href="company_panel.php">← Geri</a>
<hr>

<table border="1" cellpadding="6">
<tr>
  <th>Kullanıcı</th>
  <th>Kalkış</th>
  <th>Varış</th>
  <th>Kalkış Saati</th>
  <th>Durum</th>
  <th>İşlem</th>
</tr>

<?php foreach ($tickets as $t): ?>
<tr>
  <td><?= htmlspecialchars($t['user_name']) ?></td>
  <td><?= htmlspecialchars($t['departure_city']) ?></td>
  <td><?= htmlspecialchars($t['destination_city']) ?></td>
  <td><?= htmlspecialchars($t['departure_time']) ?></td>
  <td><?= htmlspecialchars($t['status']) ?></td>
  <td>
    <?php
    $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600;
    if ($t['status'] === 'active' && $hoursLeft > 1): ?>
      <a href="company_cancel_ticket.php?id=<?= urlencode($t['ticket_id']) ?>">İptal Et</a>
    <?php else: ?>
      <em>İptal Edilemez</em>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
