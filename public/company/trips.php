<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];

$error = [];
$success = "";

if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

// Sefer silme işlemi (iptal + iade)
if (isset($_GET['delete'])) {
    $trip_id = $_GET['delete'];
    
    $result = cancelTripAndRefundTickets($trip_id, $company_id);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header("Location: trips.php");
    exit;
}

// Sefer listesi (koltuk doluluk bilgisiyle)
$trips = getCompanyTripsWithSoldCount($company_id);

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="panel.php">← Firma Paneline Dön</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<h2>🚌 Sefer Yönetimi</h2>

<div class="container">
    <a href="add_trip.php"><button class="form-button" style="margin: 0px;">+ Yeni Sefer Oluştur</button></a>
</div>

<?php
// --- Sıralama İşlemi Başlangıcı ---
$sort_by = $_GET['sort'] ?? 'departure_time';
$sort_order = strtolower($_GET['order'] ?? 'asc');

$allowed_sorts = [
    'departure_city',
    'destination_city',
    'departure_time',
    'arrival_time',
    'price',
    'capacity',
    'sold_count'
];

if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'departure_time';
}
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'asc';
}

if (!empty($trips)) {
    usort($trips, function($a, $b) use ($sort_by, $sort_order) {
        $a_val = $a[$sort_by] ?? '';
        $b_val = $b[$sort_by] ?? '';

        if (in_array($sort_by, ['price', 'capacity', 'sold_count'])) {
             $a_val = (float)$a_val;
             $b_val = (float)$b_val;
        }
        
        if (in_array($sort_by, ['departure_time', 'arrival_time'])) {
             $a_val = strtotime($a_val);
             $b_val = strtotime($b_val);
        }

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

function getSortLink($column, $current_sort, $current_order, $label) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }

    $link = htmlspecialchars("trips.php?sort={$column}&order={$new_order}"); 
    return "<a style=\"text-decoration:underline;\" href=\"{$link}\">{$label}{$arrow}</a>";
}

?>

<div class="table-container" style="margin-top:20px;">
    <h3>Bütün Seferler</h3>
    <table>
    <tr>
        <th><?= getSortLink('departure_city', $sort_by, $sort_order, 'Kalkış') ?></th>
        <th><?= getSortLink('destination_city', $sort_by, $sort_order, 'Varış') ?></th>
        <th><?= getSortLink('departure_time', $sort_by, $sort_order, 'Kalkış Saati') ?></th>
        <th><?= getSortLink('arrival_time', $sort_by, $sort_order, 'Varış Saati') ?></th>
        <th><?= getSortLink('price', $sort_by, $sort_order, 'Fiyat') ?></th>
        <th><?= getSortLink('capacity', $sort_by, $sort_order, 'Kapasite') ?></th>
        <th><?= getSortLink('sold_count', $sort_by, $sort_order, 'Satılan Koltuk') ?></th>
        <th>İşlemler</th>
    </tr>
    <?php if (empty($trips)): ?>
    <tr><td colspan="8" style="text-align:center;">Henüz hiç sefer eklenmemiş.</td></tr>
    <?php else: ?>
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
                <a href="edit_trip.php?id=<?= urlencode($t['id']) ?>">✏️ Düzenle</a><br><br>
                <a href="trip_tickets.php?trip_id=<?= urlencode($t['id']) ?>">🎟️ Biletleri Gör</a><br><br>
                <a href="?delete=<?= urlencode($t['id']) ?>" 
                    onclick="return confirm('Bu sefer iptal edilecek. Tüm yolculara ücret iadesi yapılacak. Emin misiniz?')">
                    ❌ İptal Et
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </table>
</div>



<?php
require_once __DIR__ . '/../../includes/footer.php';
?>