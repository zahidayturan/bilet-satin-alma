<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz ID");

$error = [];
$success = "";

// 1. Firma bilgisini çekme
$company = getBusCompanyById($id);
if (!$company){
  $error[] = htmlspecialchars('Firma bulunamadı.');
} 

if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

// 2. Firma güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $logo = trim($_POST['logo_path'] ?? '');

    if (updateBusCompany($id, $name, $logo)) {
        $_SESSION['success_message'] = "Firma bilgileri başarıyla güncellendi.";
    } else {
        $_SESSION['error_message'] = "Güncelleme hatası! Veritabanı sorunu oluştu.";
    }

    header("Location: edit_company.php?id=" . urlencode($id));
    exit;
}

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:20px;"><a href="show_companies.php">← Firmalara Geri Dön</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<?php if ($company): ?>
  <div class="table-container">
    <h2>✏️ Firma Düzenle</h2>

    <form method="POST">
        <label>Firma Adı (Zorunlu)</label>
        <input type="text" name="name" value="<?= htmlspecialchars($company['name']) ?>" required>

        <label>Logo Yolu (İsteğe bağlı)</label>
        <input type="text" name="logo_path" value="<?= htmlspecialchars($company['logo_path']) ?>" placeholder="logo_ismi.png">

        <p><strong>Uyarı: </strong> Firmaya logo eklemek veya düzenlemek isterseniz, önce sistemde logoların yer aldığı klasöre logoyu yüklemelisiniz. Aksi halde logo sistemde bulunamayacaktır. Formda yer alan ilgili alana logo dosyasının ismini uzantısı ile birlite giriniz.</p>
        <button class="form-button" type="submit">Kaydet</button>
    </form>
</div>
<?php else: ?>
    <p style="text-align:center;margin-top:20px;">Firma bulunamadı.</p>
    <a href="/index.php"><p style="text-align:center;">Ana Sayfaya Dön</p></a>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>