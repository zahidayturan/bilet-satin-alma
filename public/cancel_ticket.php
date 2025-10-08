<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user']['id'];
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) die("Geçersiz ID");

$stmt = $pdo->prepare("
SELECT t.id, tr.departure_time
FROM Tickets t
JOIN Trips tr ON t.trip_id = tr.id
WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) die("Bilet bulunamadı");

$hoursLeft = (strtotime($ticket['departure_time']) - time()) / 3600;
if ($hoursLeft < 1) die("Kalkıştan 1 saatten az kaldığı için iptal edilemez.");

$stmt = $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?");
$stmt->execute([$ticket_id]);

header("Location: my_tickets.php");
exit;
