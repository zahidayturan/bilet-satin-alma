<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz ID");

$stmt = $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?");
$stmt->execute([$id]);

header('Location: company_tickets.php');
exit;
