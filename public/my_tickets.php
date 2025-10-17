<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['user']);
require_once __DIR__ . '/../includes/functions.php';

$user_id = $_SESSION['user']['id'];

// 1. Geçmiş biletleri "expired" yapma işlemini fonksiyona devret
expireUserPastTickets($user_id);

// 2. Biletleri detaylı çekme işlemini fonksiyona devret
$tickets = getUserTicketsDetails($user_id);

$successMsg = $_GET['success'] ?? '';
$errorMsg = $_GET['error'] ?? '';
$isPurchased = isset($_GET['purchased']); // Yeni satın alma durumu için

require_once __DIR__ . '/../includes/header.php';
?>

<h2>🎫 Biletlerim</h2>
<a href="index.php">← Ana Sayfa</a>
<hr>

<?php if ($isPurchased): ?>
  <div class="message success">✅ Bilet başarıyla satın alındı!</div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div class="message success">✅ <?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
  <div class="message error">❌ <?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>


<?php if (empty($tickets)): ?>
  <p>Hiç biletiniz yok.</p>
<?php else: ?>
  <table>
    <tr>
      <th>Firma</th>
      <th>Kalkış</th>
      <th>Varış</th>
      <th>Kalkış Tarihi</th>
      <th>Koltuk No</th>
      <th>Durum</th>
      <th>Fiyat</th>
      <th>İşlem</th>
    </tr>

    <?php foreach ($tickets as $t): ?>
      <tr class="<?= $t['status'] === 'expired' ? 'expired' : '' ?>">
        <td><?= htmlspecialchars($t['company_name'] ?? 'Bilinmiyor') ?></td>
        <td><?= htmlspecialchars($t['departure_city']) ?></td>
        <td><?= htmlspecialchars($t['destination_city']) ?></td>
        <td><?= date('d.m.Y H:i', strtotime($t['departure_time'])) ?></td>
        <td><?= htmlspecialchars($t['seat_number'] ?? '-') ?></td>
        <td><?= htmlspecialchars(ucfirst($t['status'])) ?></td>
        <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
        <td>
          <?php
            $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600;
            if ($t['status'] === 'active' && $hoursLeft > 1): ?>
              <a href="cancel_ticket_process.php?id=<?= urlencode($t['ticket_id']) ?>" class="cancel-link" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Yapılan iade bakiyenize eklenecektir.')">İptal Et</a> |
          <?php endif; ?>
          <a href="download_ticket.php?id=<?= urlencode($t['ticket_id']) ?>">PDF İndir</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>