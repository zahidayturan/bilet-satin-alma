<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$user_id = $_SESSION['user']['id'];

$error = [];
$success = "";

if (!expireUserPastTickets($user_id)) {
    $error[] = "Geçmiş biletler güncellenirken bir hata oluştu.";
}

$tickets = getUserTicketsDetails($user_id);

// --- Sıralama İşlemi Başlangıcı ---
$sort_by = $_GET['sort'] ?? 'purchase_date'; // Varsayılan arama: Satın Alma Tarihi
$sort_order = strtolower($_GET['order'] ?? 'desc'); // Varsayılan: Azalan (En yakın tarih en üstte)

// İzin verilen sıralama sütunları
$allowed_sorts = [
    'company_name',
    'departure_time',
    'total_price',
    'status',
    'purchase_date'
];

// Güvenlik kontrolü
if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'purchase_date';
}
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'desc';
}

if (!empty($tickets)) {
    usort($tickets, function($a, $b) use ($sort_by, $sort_order) {
        $a_val = $a[$sort_by] ?? '';
        $b_val = $b[$sort_by] ?? '';

        if ($a_val == $b_val) {
            return 0;
        }

        if ($sort_order === 'asc') {
            return ($a_val < $b_val) ? -1 : 1;
        } else {
            return ($a_val > $b_val) ? -1 : 1;
        }
    });
}

/* Bilet iptal işlemi */

if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $ticket_id = $_GET['id'];
    
    $result = cancelTicketAndRefundByUser($ticket_id, $user_id, 1);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header("Location: my_tickets.php");
    exit;
}

if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

require_once __DIR__ . '/../includes/header.php';

function getSortLink($column, $current_sort, $current_order, $label) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }

    $link = htmlspecialchars("my_tickets.php?sort={$column}&order={$new_order}");
    return "<a style=\"text-decoration:underline;\" href=\"{$link}\">{$label}{$arrow}</a>";
}

?>

<div style="margin-bottom: 20px;">
    <a href="index.php">← Ana Sayfaya Dön</a>
</div>

<?php
    require_once __DIR__ . '/../includes/message_comp.php';
?>

<?php if (empty($tickets)): ?>
    <p>Hiç biletiniz yok.</p>
<?php else: ?>
    <div class="table-container">
        <h2>🎫 Biletlerim</h2>
        <table>
        <tr>
            <th><?= getSortLink('company_name', $sort_by, $sort_order, 'Firma') ?></th>
            <th>Nereden → Nereye</th>
            <th><?= getSortLink('departure_time', $sort_by, $sort_order, 'Kalkış Tarihi') ?></th>
            <th>Koltuk No</th>
            <th><?= getSortLink('status', $sort_by, $sort_order, 'Durum') ?></th>
            <th><?= getSortLink('total_price', $sort_by, $sort_order, 'Fiyat') ?></th>
            <th><?= getSortLink('purchase_date', $sort_by, $sort_order, 'Satın Alma') ?></th>
            <th>İşlem</th>
        </tr>

        <?php foreach ($tickets as $t): ?>
            <tr class="<?= $t['status'] === 'expired' ? 'expired' : '' ?>">
                <td><?= htmlspecialchars($t['company_name'] ?? 'Bilinmiyor') ?></td>
                <td><?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['destination_city']) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></td>
                <td><?= htmlspecialchars($t['seat_number'] ?? '-') ?></td>
                <td>
                    <?php
                        $status = $t['status'] ?? 'unknown';
                        
                        switch ($status) {
                            case 'active':
                                echo '<strong>Geçerli Sefer</strong>';
                                break;
                            case 'canceled':
                                echo 'İptal Edilmiş';
                                break;
                            case 'expired':
                                echo 'Geçmiş Sefer';
                                break;
                            default:
                                echo 'Bilinmiyor';
                                break;
                        }
                    ?>
                </td>
                <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
                <td><?= date('d.m.Y H:i', strtotime($t['purchase_date'])) ?></td>
                <td>
                    <?php
                        $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600;
                        if ($t['status'] === 'active' && $hoursLeft > 1): ?>
                            <a href="my_tickets.php?action=cancel&id=<?= urlencode($t['ticket_id']) ?>" class="cancel-link" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Yapılan iade bakiyenize eklenecektir.')">İptal Et</a> |
                        <?php endif; ?>
                        <a href="download_ticket.php?id=<?= urlencode($t['ticket_id']) ?>">PDF İndir</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </table>
    </div>

<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>