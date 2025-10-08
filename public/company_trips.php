<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

// Sefer silme
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$_GET['delete'], $company_id]);
}

// Tüm seferler
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ?");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sefer Yönetimi</title>
</head>
<body>
<h2>🚌 Sefer Yönetimi</h2>
<a href="company_panel.php">← Geri</a>
<hr>

<a href="company_add_trip.php">+ Yeni Sefer Ekle</a>

<table border="1" cellpadding="6">
<tr>
  <th>Kalkış</th>
  <th>Varış</th>
  <th>Kalkış Saati</th>
  <th>Fiyat</th>
  <th>Kapasite</th>
  <th>İşlem</th>
</tr>

<?php foreach ($trips as $t): ?>
<tr>
  <td><?= htmlspecialchars($t['departure_city']) ?></td>
  <td><?= htmlspecialchars($t['destination_city']) ?></td>
  <td><?= htmlspecialchars($t['departure_time']) ?></td>
  <td><?= htmlspecialchars($t['price']) ?> ₺</td>
  <td><?= htmlspecialchars($t['capacity']) ?></td>
  <td>
    <a href="company_edit_trip.php?id=<?= urlencode($t['id']) ?>">Düzenle</a> |
    <a href="?delete=<?= urlencode($t['id']) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
