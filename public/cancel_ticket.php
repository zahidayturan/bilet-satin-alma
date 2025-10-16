<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$user_id = $_SESSION['user']['id'];
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    header("Location: my_tickets.php?error=" . urlencode('Geçersiz bilet ID.'));
    exit;
}

// Tüm iş mantığını fonksiyona devret
// Kullanıcı iptali için minimum saat kuralı 1 saat.
$result = cancelTicketAndRefundByUser($ticket_id, $user_id, 1);

if ($result['success']) {
    // Başarılıysa bilet listesi sayfasına yönlendir
    header("Location: my_tickets.php?success=" . urlencode($result['message']));
    exit;
} else {
    // Hata durumunda hata mesajıyla bilet listesi sayfasına yönlendir
    // Hata mesajı fonksiyondan geliyor (örn: "Kalkıştan 1 saatten az kaldığı için iptal edilemez.")
    header("Location: my_tickets.php?error=" . urlencode($result['message']));
    exit;
}
// Bu dosya artık HTML içeriği döndürmez.
?>