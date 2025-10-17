<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];

// Arama parametresi
$search = trim($_GET['q'] ?? '');

// Sorgu ve Gruplama işlemlerini fonksiyonlara devret
$ticketRows = getCompanyTicketsWithSearch($company_id, $search);

// Sefer bazlı gruplandırma
$trips = groupTicketsByTrip($ticketRows);

// Mesaj Yönetimi (İptal işleminden gelirse diye)
$successMsg = $_GET['success'] ?? '';
$errorMsg = $_GET['error'] ?? '';

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>🎫 Firma Biletleri</h2>
<a href="panel.php">← Geri</a>
<hr>

<?php if ($errorMsg): ?>
    <div class="message error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
    <div class="message success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

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
    <h3>
      🚌 <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>  
      | <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>
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
            // Kalkışa kalan süre kontrolü buraya taşındı, çünkü bu iş mantığı sunum katmanına ait değil
            $hoursLeft = (strtotime($t['departure_time']) - time()) / 3600; 
        ?>
        <tr>
          <td><?= htmlspecialchars($t['user_name']) ?></td>
          <td><?= htmlspecialchars($t['email']) ?></td>
          <td><?= htmlspecialchars($t['status']) ?></td>
          <td><?= htmlspecialchars($t['total_price']) ?> ₺</td>
          <td>
            <?php 
            // İptal linki, şirketin bilet iptal etme iş mantığına göre gösterilir
            if ($t['status'] === 'active' && $hoursLeft > 1): 
            ?>
              <a href="cancel_ticket.php?id=<?= urlencode($t['ticket_id']) ?>"
                 onclick="return confirm('Bu bileti iptal edip kullanıcıya iade yapmak istediğinize emin misiniz?')">
                 ❌ İptal Et
              </a>
            <?php else: ?>
              <em>İptal Edilemez</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>