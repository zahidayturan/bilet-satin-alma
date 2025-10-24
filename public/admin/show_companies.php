<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../../includes/functions.php';

$error = [];
$success = "";

// --- Mesaj Yönetimi Başlangıcı ---
if (isset($_SESSION['success_message'])) {
    $success = htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error[] = htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
}
// --- Mesaj Yönetimi Bitişi ---

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

// --- Sıralama İşlemi Başlangıcı ---
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = strtolower($_GET['order'] ?? 'desc');

$allowed_sorts = [
    'name',
    'created_at'
];

// Güvenlik kontrolü
if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'created_at';
}
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'desc';
}

if (!empty($companies)) {
    usort($companies, function($a, $b) use ($sort_by, $sort_order) {
        $a_val = $a[$sort_by] ?? '';
        $b_val = $b[$sort_by] ?? '';

        if ($sort_by === 'id') {
             $a_val = (int)$a_val;
             $b_val = (int)$b_val;
        }
        if ($a_val == $b_val) {return 0;}

        if ($sort_order === 'asc') {
            return ($a_val < $b_val) ? -1 : 1;
        } else {
            return ($a_val > $b_val) ? -1 : 1;
        }
    });
}

$page_title = "Bana1Bilet - Sistem Yönetimi";
require_once __DIR__ . '/../../includes/header.php';

// Biletler sayfasından kopyalanan yardımcı fonksiyon
function getSortLink($column, $current_sort, $current_order, $label) {
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';
    
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = $current_order === 'asc' ? ' ▲' : ' ▼';
    }

    // Linkin show_companies.php'ye işaret ettiğinden emin olun.
    $link = htmlspecialchars("show_companies.php?sort={$column}&order={$new_order}"); 
    return "<a style=\"text-decoration:underline;\" href=\"{$link}\">{$label}{$arrow}</a>";
}

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
                <label>Firma Adı (Zorunlu)</label>
                <input type="text" name="name" required>
            </div>
            <div style="flex: 1 0 150px;">
                <label>Logo Yolu (İsteğe Bağlı)</label>
                <input type="text" name="logo_path" placeholder="logo_ismi.png">
            </div>
        </div>
        <p><strong>Uyarı: </strong> Firmaya logo eklemek isterseniz, önce sistemde logoların yer aldığı klasöre logoyu yüklemelisiniz. Aksi halde logo sistemde bulunamayacaktır. Formda yer alan ilgili alana logo dosyasının ismini uzantısı ile birlite giriniz.</p>
        <button class="form-button" type="submit" name="add">Ekle</button>
    </form>
</div>

<div class="table-container" style="margin-top: 20px;">
    <h3>Mevcut Firmalar</h3>
    <table>
        <tr>
            <th>ID</th>
            <th><?= getSortLink('name', $sort_by, $sort_order, 'Ad') ?></th>
            <th>Logo Yolu</th>
            <th><?= getSortLink('created_at', $sort_by, $sort_order, 'Oluşturulma') ?></th>
            <th>İşlem</th>
        </tr>
        <?php if (empty($companies)): ?>
            <tr><td colspan="5">Henüz hiç firma eklenmemiş.</td></tr>
        <?php else: ?>
            <?php foreach ($companies as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['id']) ?></td>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td>
                        <?php 
                        $logo_path = htmlspecialchars($c['logo_path']);
                        $full_path = __DIR__ . '/../../public/assets/logos/' . $logo_path;
                        $display_url = '/assets/logos/' . $logo_path;
                        ?>
                        
                        <?= $logo_path ?>
                        
                        <?php if (!empty($logo_path) && file_exists($full_path)): ?>
                            <br>
                            <a href="<?= htmlspecialchars($display_url) ?>" target="_blank" style="white-space: nowrap;">
                                [🖼️ Logoyu Görüntüle]
                            </a>
                        <?php elseif (!empty($logo_path)): ?>
                            <br>
                            <span style="color: orange; font-size: small;">
                                ⚠️ Dosya bulunamadı!
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d.m.Y H:i', strtotime($c['created_at']))  ?></td>
                    <td>
                        <a href="edit_company.php?id=<?= urlencode($c['id']) ?>">✏️ Düzenle</a> <br><br>
                        <a href="?delete=<?= urlencode($c['id']) ?>" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')" style="color: red;">🗑️ Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>