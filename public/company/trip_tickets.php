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

<?php if ($trip): ?>
<div class="table-container">
  <h2>🎟️ Bilet Listesi</h2>
  <p><strong><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?> - (<?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>) - (<?= htmlspecialchars($trip['price']) ?> ₺)</strong></p>

  <table>
  <tr>
      <th>Satın Alım Zamanı</th>
      <th>Koltuk No</th>
      <th>Yolcu Adı</th>
      <th>Email</th>
      <th>Durum</th>
      <th>Ücret</th>
      <th>İşlem</th>
  </tr>

  <?php if ($tickets): ?>
      <?php foreach ($tickets as $tk): ?>
          <?php 
              $hoursLeft = (strtotime($tk['departure_time']) - time()) / 3600; 
          ?>
      <tr>
          <td><?= htmlspecialchars($tk['purchase_date']) ?></td>
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
      <tr><td colspan="6" style="text-align:center;">Bu sefere ait bilet bulunamadı.</td></tr>
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