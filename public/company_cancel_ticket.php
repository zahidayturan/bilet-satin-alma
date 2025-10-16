<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['company']);

require_once __DIR__ . '/../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$ticket_id = $_GET['id'] ?? null;

// ID yoksa hatayı hemen yönlendir
if (!$ticket_id) {
    header('Location: company_tickets.php?error=' . urlencode('Geçersiz bilet ID.'));
    exit;
}


$result = cancelTicketAndRefund($ticket_id, $company_id);

if ($result['success']) {
    // Başarılıysa bilet listesi sayfasına yönlendir
    header('Location: company_tickets.php?success=' . urlencode($result['message'] ?? 'Bilet başarıyla iptal edildi ve iade yapıldı.'));
    exit;
} else {
    // Hata durumunda hata mesajıyla bilet listesi sayfasına yönlendir
    header('Location: company_tickets.php?error=' . urlencode($result['message']));
    exit;
}
// Bu dosya artık HTML içeriği döndürmez.
?>