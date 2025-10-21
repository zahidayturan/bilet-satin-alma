<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../../includes/functions.php';

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

// Yeni firma admin oluştur
// Yeni firma admin oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $passwordConfirm = $_POST['password_confirm'];
    $companyId = $_POST['company_id'];
    
    if ($password !== $passwordConfirm) {
        $_SESSION['error_message'] = "Şifre ve Şifre Tekrarı alanları eşleşmiyor. Lütfen tekrar deneyin.";
        header("Location: show_company_admins.php");
        exit;
    }
    if (addCompanyAdmin($fullName, $email, $password, $companyId)) {
        $_SESSION['success_message'] = "Firma yöneticisi başarıyla eklendi.";
    } else {
        $_SESSION['error_message'] = "Firma yöneticisi eklenirken bir hata oluştu. (E-posta zaten kayıtlı olabilir)";
    }

    header("Location: show_company_admins.php");
    exit;
}

// Firmalar listesi (dropdown için)
$companies = getCompanyListForDropdown();

// Tüm firma adminleri
$admins = getAllCompanyAdmins();

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="panel.php">← Admin Paneli</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<h2>👤 Firma Admin Yönetimi</h2>

<div class="table-container">
    <h3>Yeni Firma Admin Ekle</h3>
    <form method="POST">
        <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
            <div style="flex: 1 0 150px;">
                <label>Ad Soyad</label>
                <input type="text" name="full_name" required>
            </div>
            <div style="flex: 1 0 150px;">
                <label>E-posta</label>
                <input type="email" name="email" required>
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
            <div style="flex: 1 0 150px;">
                <label>Şifre</label>
                <input type="password" name="password" required>
            </div>
            <div style="flex: 1 0 150px;">
                <label>Şifre Tekrar</label>
                <input type="password" name="password_confirm" required>
            </div>
        </div>

        <label>Firma</label>
        <select name="company_id" required>
            <option value="">-- Firma Seçin --</option>
            <?php foreach ($companies as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <button class="form-button" style="margin-top: 20px;" type="submit">Ekle</button>
    </form>
</div>


<div class="table-container" style="margin-top: 20px;">
    <h3>Firma Admin Listesi</h3>
    <table>
        <tr><th>ID</th><th>Ad Soyad</th><th>Email</th><th>Firma</th><th>İşlem</th></tr>
        <?php if (empty($admins)): ?>
            <tr><td colspan="5">Henüz hiç firma yöneticisi eklenmemiş.</td></tr>
        <?php else: ?>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['id']) ?></td>
                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><strong><?= htmlspecialchars($a['company'] ?? '-') ?></strong></td>
                    <td>
                        <a href="edit_company_admin.php?id=<?= urlencode($a['id']) ?>">✏️ Düzenle</a>
                        </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>