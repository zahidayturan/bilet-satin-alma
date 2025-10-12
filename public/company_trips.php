<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

// ✳️ Sefer silme işlemi
if (isset($_GET['delete'])) {
    $trip_id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Bu sefer bu firmaya mı ait?
        $stmt = $pdo->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$trip_id, $company_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trip) {
            throw new Exception("Bu sefer size ait değil veya bulunamadı.");
        }

        // Bu sefere ait aktif biletleri al
        $stmt = $pdo->prepare("
            SELECT id AS ticket_id, user_id, total_price
            FROM Tickets
            WHERE trip_id = ? AND status = 'active'
        ");
        $stmt->execute([$trip_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Her kullanıcıya iade yap ve biletleri iptal et
        foreach ($tickets as $t) {
            $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?")->execute([$t['ticket_id']]);
            $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id=?")->execute([$t['ticket_id']]);
            $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id=?")
                ->execute([$t['total_price'], $t['user_id']]);
        }

        // Seferi sil
        $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$trip_id, $company_id]);

        $pdo->commit();

        header("Location: company_trips.php?deleted=1");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Hata: " . htmlspecialchars($e->getMessage()));
    }
}

// Tüm seferleri listele
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
    <a href="?delete=<?= urlencode($t['id']) ?>" onclick="return confirm('Bu sefer iptal edilecek. Tüm yolculara ücret iadesi yapılacak. Emin misiniz?')">Seferi İptal Et</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
