<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

// 🔍 Arama parametresi
$search = trim($_GET['q'] ?? '');

// 🔎 Sorgu
$query = "
SELECT 
    tr.id AS trip_id,
    tr.departure_city,
    tr.destination_city,
    tr.departure_time,
    tr.arrival_time,
    t.id AS ticket_id,
    t.status,
    t.total_price,
    u.full_name AS user_name,
    u.email
FROM Tickets t
JOIN Trips tr ON tr.id = t.trip_id
JOIN User u ON u.id = t.user_id
WHERE tr.company_id = :cid
";

$params = [':cid' => $company_id];

if ($search !== '') {
    $query .= " AND (tr.departure_city LIKE :q OR tr.destination_city LIKE :q OR u.full_name LIKE :q)";
    $params[':q'] = "%$search%";
}

$query .= " ORDER BY tr.departure_time DESC, u.full_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🎫 Sefer bazlı gruplandırma
$trips = [];
foreach ($rows as $row) {
    $tid = $row['trip_id'];
    if (!isset($trips[$tid])) {
        $trips[$tid] = [
            'departure_city' => $row['departure_city'],
            'destination_city' => $row['destination_city'],
            'departure_time' => $row['departure_time'],
            'arrival_time' => $row['arrival_time'],
            'tickets' => []
        ];
    }
    $trips[$tid]['tickets'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Biletleri</title>
  <style>
    body { font-family: Arial, sans-serif; background: #fafafa; margin: 20px; }
    h2 { color: #2c3e50; }
    h3 { background: #3498db; color: white; padding: 6px 10px; border-radius: 4px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 30px; background: white; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background-color: #f2f2f2; }
    a { text-decoration: none; color: #2980b9; }
    a:hover { text-decoration: underline; }
    .search-box { margin-bottom: 20px; }
    .search-box input { padding: 5px; width: 250px; }
    .search-box button { padding: 5px 10px; }
  </style>
</head>
<body>

<h2>🎫 Firma Biletleri</h2>
<a href="company_panel.php">← Geri</a>
<hr>

<!-- 🔍 Arama -->
<div class="search-box">
  <form method="GET">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Şehir veya yolcu adı ara...">
    <button type="submit">Ara</button>
    <?php if ($search !== ''): ?>
        <a href="company_tickets.php" style="margin-left:10px;">❌ Temizle</a>
    <?php endif; ?>
  </form>
</div>

<?php if (empty($trips)): ?>
  <p>Hiç bilet bulunamadı.</p>
<?php else: ?>
  <?php foreach ($trips as $trip_id => $trip): ?>
    <h3>
      🚌 <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>  
      | <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>
    </h3>
    <table>
      <tr>
        <th>Yolcu</th>
        <th>Email</th>
        <th>Durum</th>
        <th>Ücret</th>
        <th>İşlem</th>
      </tr>
      <?php foreach ($trip['tickets'] as $t): ?>
        <?php $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600; ?>
        <tr>
          <td><?= htmlspecialchars($t['user_name']) ?></td>
          <td><?= htmlspecialchars($t['email']) ?></td>
          <td><?= htmlspecialchars($t['status']) ?></td>
          <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
          <td>
            <?php if ($t['status'] === 'active' && $hoursLeft > 1): ?>
              <a href="company_cancel_ticket.php?id=<?= urlencode($t['ticket_id']) ?>"
                 onclick="return confirm('Bu bileti iptal edip kullanıcıya iade yapmak istediğinize emin misiniz?')">
                 ❌ İptal Et
              </a>
            <?php else: ?>
              <em>İptal Edilemez</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
