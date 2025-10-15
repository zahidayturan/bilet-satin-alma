<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];

// 🧾 Sefer silme işlemi (iptal + iade)
if (isset($_GET['delete'])) {
    $trip_id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Sefer bu firmaya mı ait kontrol et
        $stmt = $pdo->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$trip_id, $company_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trip) {
            throw new Exception("Bu sefer size ait değil veya bulunamadı.");
        }

        // Seferdeki aktif biletleri getir
        $stmt = $pdo->prepare("
            SELECT id AS ticket_id, user_id, total_price
            FROM Tickets
            WHERE trip_id = ? AND status = 'active'
        ");
        $stmt->execute([$trip_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Her bilet için iptal ve iade işlemleri
        foreach ($tickets as $t) {
            // Bilet iptal et
            $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?")
                ->execute([$t['ticket_id']]);

            // Koltukları sil
            $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id=?")
                ->execute([$t['ticket_id']]);

            // Kullanıcıya ücret iadesi yap
            $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id=?")
                ->execute([$t['total_price'], $t['user_id']]);
        }

        // Bilet kayıtlarını temizle (tarihçeyi korumak istersen bu satırı silebilirsin)
        $pdo->prepare("DELETE FROM Tickets WHERE trip_id=?")->execute([$trip_id]);

        // Seferi sil
        $pdo->prepare("DELETE FROM Trips WHERE id=? AND company_id=?")
            ->execute([$trip_id, $company_id]);

        $pdo->commit();

        header("Location: company_trips.php?deleted=1");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Hata: " . htmlspecialchars($e->getMessage()));
    }
}

// ✳️ Sefer listesi (koltuk doluluk bilgisiyle)
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        (SELECT COUNT(*) 
         FROM Tickets ti 
         WHERE ti.trip_id = t.id AND ti.status = 'active') AS sold_count
    FROM Trips t
    WHERE t.company_id = ?
    ORDER BY t.departure_time ASC
");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sefer Yönetimi</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 8px; border: 1px solid #aaa; text-align: center; }
    th { background-color: #f4f4f4; }
    .actions a { margin: 0 5px; }
  </style>
</head>
<body>
<h2>🚌 Sefer Yönetimi</h2>
<a href="company_panel.php">← Geri</a>
<hr>

<a href="company_add_trip.php">+ Yeni Sefer Ekle</a>

<table>
<tr>
  <th>Kalkış</th>
  <th>Varış</th>
  <th>Kalkış Saati</th>
  <th>Varış Saati</th>
  <th>Fiyat</th>
  <th>Kapasite</th>
  <th>Satılan Koltuk</th>
  <th>İşlemler</th>
</tr>

<?php foreach ($trips as $t): ?>
<tr>
  <td><?= htmlspecialchars($t['departure_city']) ?></td>
  <td><?= htmlspecialchars($t['destination_city']) ?></td>
  <td><?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></td>
  <td><?= date('d.m.Y H:i', strtotime($t['arrival_time'])) ?></td>
  <td><?= htmlspecialchars($t['price']) ?> ₺</td>
  <td><?= htmlspecialchars($t['capacity']) ?></td>
  <td><?= htmlspecialchars($t['sold_count']) ?></td>
  <td class="actions">
    <a href="company_edit_trip.php?id=<?= urlencode($t['id']) ?>">✏️ Düzenle</a> |
    <a href="company_trip_tickets.php?trip_id=<?= urlencode($t['id']) ?>">🎟️ Biletleri Gör</a> |
    <a href="?delete=<?= urlencode($t['id']) ?>" 
       onclick="return confirm('Bu sefer iptal edilecek. Tüm yolculara ücret iadesi yapılacak. Emin misiniz?')">
       ❌ Seferi İptal Et
    </a>
  </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
