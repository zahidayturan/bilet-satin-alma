<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user']['id'];
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) die("Geçersiz ID");

$stmt = $pdo->prepare("
SELECT t.id, t.total_price, tr.departure_time
FROM Tickets t
JOIN Trips tr ON t.trip_id = tr.id
WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) die("Bilet bulunamadı");

$hoursLeft = (strtotime($ticket['departure_time']) - time()) / 3600;
if ($hoursLeft < 1) die("Kalkıştan 1 saatten az kaldığı için iptal edilemez.");

try {
    $pdo->beginTransaction();

    // Bilet iptali
    $stmt = $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?");
    $stmt->execute([$ticket_id]);

    // Koltuğu boşalt
    $stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);

    // 💰 Kullanıcının bakiyesine iade
    $stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$ticket['total_price'], $user_id]);

    $pdo->commit();

    header("Location: my_tickets.php?refund=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Hata: " . htmlspecialchars($e->getMessage()));
}
