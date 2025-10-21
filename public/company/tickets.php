<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];

$error = [];
$success = "";

// Arama parametresi
$search = trim($_GET['q'] ?? '');

// Sorgu ve Gruplama işlemlerini fonksiyonlara devret
$ticketRows = getCompanyTicketsWithSearch($company_id, $search);

// Sefer bazlı gruplandırma
$trips = groupTicketsByTrip($ticketRows);


/* Bilet iptal işlemi */
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $ticket_id = $_GET['id'];

    if (!$ticket_id) {
      $_SESSION['error_message'] = "Geçersiz bilet numarası.";
      header('Location: tickets.php');
      exit;
    }
    
    $result = cancelTicketAndRefund($ticket_id, $company_id);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header("Location: tickets.php");
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


$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="panel.php">← Geri</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<h2>🎫 Firma Biletleri</h2>

<div class="search-box">
  <form method="GET">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Şehir veya yolcu adı ara...">
    <button type="submit">Ara</button>
    <?php if ($search !== ''): ?>
      <a href="tickets.php" style="margin-left:10px;">❌ Temizle</a>
    <?php endif; ?>
  </form>
</div>

<?php if (empty($trips)): ?>
  <p>Hiç bilet bulunamadı.</p>
<?php else: ?>
  <?php foreach ($trips as $trip_id => $trip): ?>
    <div class="table-container">
        <h3>
        🚌 <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>  
        | Kalkış: <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>
        </h3>
        <table>
          <tr>
            <th>Yolcu</th>
            <th>Email</th>
            <th>Durum</th>
            <th>Ücret</th>
            <th>İşlem</th>
          </tr>
          <?php foreach ($trip['tickets'] as $t): ?>
            <?php 
                $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600; 
            ?>
            <tr>
              <td><?= htmlspecialchars($t['user_name']) ?></td>
              <td><?= htmlspecialchars($t['email']) ?></td>
              <td><?= htmlspecialchars($t['status']) ?></td>
              <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
              <td>
                <?php 
                if ($t['status'] === 'active' && $hoursLeft > 1): 
                ?>
                  <a href="tickets.php?action=cancel&id=<?= urlencode($t['ticket_id']) ?>" class="cancel-link" onclick="return confirm('Bu bileti iptal edip kullanıcıya iade yapmak istediğinize emin misiniz?')">❌ İptal Et</a>
                <?php else: ?>
                  <em>İptal Edilemez</em>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>