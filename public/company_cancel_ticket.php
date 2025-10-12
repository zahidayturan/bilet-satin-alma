<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$company_id = $_SESSION['user']['company_id'];
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) die("Geçersiz ID");

try {
    $pdo->beginTransaction();

    // Biletin bilgilerini al
    $stmt = $pdo->prepare("
        SELECT t.id, t.user_id, t.total_price, t.status, tr.company_id
        FROM Tickets t
        JOIN Trips tr ON t.trip_id = tr.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception("Bilet bulunamadı.");
    }

    // Bu bilet bu firmaya mı ait?
    if ($ticket['company_id'] !== $company_id) {
        throw new Exception("Bu bilet sizin firmanıza ait değil!");
    }

    // Zaten iptal edilmişse işlem yapma
    if ($ticket['status'] === 'canceled') {
        throw new Exception("Bilet zaten iptal edilmiş.");
    }

    // Bilet iptali
    $stmt = $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id = ?");
    $stmt->execute([$ticket_id]);

    // Koltuğu boşalt
    $stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);

    // Kullanıcı bakiyesine iade
    $stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$ticket['total_price'], $ticket['user_id']]);

    $pdo->commit();

    header('Location: company_tickets.php?refund=1');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Hata: " . htmlspecialchars($e->getMessage()));
}
