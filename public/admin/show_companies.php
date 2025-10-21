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

// Firma ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $logo = trim($_POST['logo_path'] ?? '');
    
    if (addBusCompany($name, $logo)) {
        $_SESSION['success_message'] = "Firma başarıyla eklendi.";
    } else {
        $_SESSION['error_message'] = "Firma eklenirken bir hata oluştu.";
    }

    header("Location: show_companies.php");
    exit;
}

// Firma silme
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $result = deleteBusCompany($id);
    
    if ($result['success']) {
       $_SESSION['success_message'] = $result['message'];
    } else {
       $_SESSION['error_message'] = $result['message'];
    }

    header("Location: show_companies.php");
    exit;
}

// Tüm firmaları çekme
$companies = getAllBusCompanies();

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="panel.php">← Admin Paneli</a></div>

<?php
    require_once __DIR__ . '/../../includes/message_comp.php';
?>

<h2>🏢 Firma Yönetimi</h2>

<div class="container">
    <h3>Yeni Firma Ekle</h3>
    <form method="POST">
        <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
            <div style="flex: 1 0 150px;">
                <label>Firma Adı</label>
                <input type="text" name="name" required>
            </div>
            <div style="flex: 1 0 150px;">
                <label>Logo Yolu</label>
                <input type="text" name="logo_path">
            </div>
        </div>
        <button class="form-button" type="submit" name="add">Ekle</button>
    </form>
</div>

<div class="table-container" style="margin-top: 20px;">
    <h3>Mevcut Firmalar</h3>
    <table>
        <tr><th>ID</th><th>Ad</th><th>Logo</th><th>Oluşturulma</th><th>İşlem</th></tr>
        <?php if (empty($companies)): ?>
            <tr><td colspan="5">Henüz hiç firma eklenmemiş.</td></tr>
        <?php else: ?>
            <?php foreach ($companies as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['id']) ?></td>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><?= htmlspecialchars($c['logo_path']) ?></td>
                    <td><?= htmlspecialchars($c['created_at']) ?></td>
                    <td>
                        <a href="edit_company.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> |
                        <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">🗑️ Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>


<?php
require_once __DIR__ . '/../../includes/footer.php';
?>