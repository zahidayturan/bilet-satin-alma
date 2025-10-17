<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz ID");

$errorMsg = '';
$successMsg = '';

// 1. Firma bilgisini çekme
$company = getBusCompanyById($id);
if (!$company) die("Firma bulunamadı.");

// 2. Firma güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $logo = trim($_POST['logo_path'] ?? '');

    if (updateBusCompany($id, $name, $logo)) {
        $successMsg = "Firma bilgileri başarıyla güncellendi. ✅";
        
        // Güncellenmiş veriyi formda göstermek için yeniden çekelim
        $company = getBusCompanyById($id); 
    } else {
        $errorMsg = "Güncelleme hatası! Veritabanı sorunu oluştu. ❌";
    }
}

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>✏️ Firma Düzenle</h2>
<a href="firmas.php">← Geri Dön</a>
<hr>

<?php if ($errorMsg): ?>
  <div style="color:red;padding:10px;border:1px solid red;background-color:#ffe6e6;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div style="color:green;padding:10px;border:1px solid green;background-color:#e6ffe6;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<form method="POST">
  <label>Firma Adı:</label>
  <input type="text" name="name" value="<?= htmlspecialchars($company['name']) ?>" required><br><br>

  <label>Logo Yolu:</label>
  <input type="text" name="logo_path" value="<?= htmlspecialchars($company['logo_path']) ?>"><br><br>

  <button type="submit">Kaydet</button>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>