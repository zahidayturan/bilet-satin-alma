<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
$trip_id = $_GET['trip_id'] ?? null;

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

if (!$trip_id){
  $error[] = 'Sefer bulunamadı.';
};

// 1. Sefer doğrulama
$trip = getTripDetailsForCompany($trip_id, $company_id);
if (!$trip){
  $error[] = 'Sefer bulunamadı veya size ait değil.';
};

// 2. Bilet iptali işlemi
if (isset($_GET['cancel'])) {
    $ticket_id = $_GET['cancel'];
    $result = cancelTicketAndRefund($ticket_id, $company_id);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header("Location: trip_tickets.php?trip_id=" . urlencode($trip_id));
    exit;
}

// 3. Seferin biletlerini getir
$tickets = getTripTickets($trip_id);

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="trips.php">← Seferlere Geri Dön</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<?php
// ... [Mevcut PHP kodlarınız, biletlerin çekilmesi, $trip ve $trip_id tanımlanması] ...

// getSortLink fonksiyonunun bu dosyada (trip_tickets.php) tanımlandığını varsayıyoruz.
// NOT: Link hedefini 'trip_tickets.php' olarak güncelleyip $trip_id parametresini koruyoruz.
function getSortLink($column, $current_sort, $current_order, $label, $trip_id) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }

    // trip_id parametresini her zaman korumak önemli
    $link = htmlspecialchars("trip_tickets.php?trip_id={$trip_id}&sort={$column}&order={$new_order}"); 
    return "<a style=\"text-decoration:underline;\" href=\"{$link}\">{$label}{$arrow}</a>";
}

// --- Sıralama İşlemi Başlangıcı ---
$sort_by = $_GET['sort'] ?? 'purchase_date';
$sort_order = strtolower($_GET['order'] ?? 'desc');

$allowed_sorts = [
    'purchase_date',
    'seat_number',
    'full_name',
    'email',
    'status',
    'total_price'
];

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

        if ($sort_by === 'purchase_date') {
             $a_val = strtotime($a_val);
             $b_val = strtotime($b_val);
        }
        
        if ($sort_by === 'total_price') {
             $a_val = (float)$a_val;
             $b_val = (float)$b_val;
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
?>

<?php if ($trip): ?>
<div class="table-container">
  <h2>🎟️ Bilet Listesi</h2>
  <p><strong><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?> - (<?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>) - (<?= htmlspecialchars($trip['price']) ?> ₺)</strong></p>

  <table>
    <tr>
        <th><?= getSortLink('purchase_date', $sort_by, $sort_order, 'Satın Alım Zamanı', $trip_id) ?></th>
        <th><?= getSortLink('seat_number', $sort_by, $sort_order, 'Koltuk No', $trip_id) ?></th>
        <th><?= getSortLink('full_name', $sort_by, $sort_order, 'Yolcu Adı', $trip_id) ?></th>
        <th><?= getSortLink('email', $sort_by, $sort_order, 'Email', $trip_id) ?></th>
        <th><?= getSortLink('status', $sort_by, $sort_order, 'Durum', $trip_id) ?></th>
        <th><?= getSortLink('total_price', $sort_by, $sort_order, 'Ücret', $trip_id) ?></th>
        <th>İşlem</th>
    </tr>

  <?php if ($tickets): ?>
      <?php foreach ($tickets as $tk): ?>
          <?php 
              $hoursLeft = (strtotime($tk['departure_time']) - time()) / 3600; 
          ?>
      <tr>
          <td><?= date('d.m.Y H:i', strtotime($tk['purchase_date']))?></td>
          <td><?= htmlspecialchars($tk['seat_number'] ?? 'Bilinmiyor') ?></td>
          <td><?= htmlspecialchars($tk['full_name']) ?></td>
          <td><?= htmlspecialchars($tk['email']) ?></td>
          <td>
            <?php
                $status = $tk['status'] ?? 'unknown';
                
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
          <td><?= htmlspecialchars($tk['total_price']) ?> ₺</td>
          <td>
            <?php if ($tk['status'] === 'active' && $hoursLeft > 1): ?>
              <a href="?trip_id=<?= urlencode($trip_id) ?>&cancel=<?= urlencode($tk['ticket_id']) ?>"
                onclick="return confirm('Bu bileti iptal edip yolcuya ücret iadesi yapmak istediğinize emin misiniz?')">
                ❌ İptal Et
              </a>
            <?php else: ?>
              <em>İptal Edilemez</em>
            <?php endif; ?>
          </td>
      </tr>
      <?php endforeach; ?>
  <?php else: ?>
      <tr><td colspan="7" style="text-align:center;">Bu sefere ait bilet bulunamadı.</td></tr>
  <?php endif; ?>
  </table>
</div>
<?php else: ?>
    <p style="text-align:center;margin-top:20px;">Sefer bulunamadı.</p>
    <a href="/index.php"><p style="text-align:center;">Ana Sayfaya Dön</p></a>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>