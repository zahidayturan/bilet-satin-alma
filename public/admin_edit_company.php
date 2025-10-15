<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);
require_once __DIR__ . '/../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Geçersiz ID");

$errorMsg = '';
$successMsg = '';

// Firma bilgisi
$stmt = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
$stmt->execute([$id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$company) die("Firma bulunamadı.");

// Firma güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $logo = trim($_POST['logo_path']);
    try {
        $stmt = $pdo->prepare("UPDATE Bus_Company SET name=?, logo_path=? WHERE id=?");
        $stmt->execute([$name, $logo, $id]);
        $successMsg = "Firma bilgileri güncellendi.";

        // Güncellenmiş veriyi yeniden çekelim
        $stmt = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
        $stmt->execute([$id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMsg = "Güncelleme hatası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Düzenle</title>
</head>
<body>
<h2>✏️ Firma Düzenle</h2>
<a href="admin_firmas.php">← Geri Dön</a>
<hr>

<?php if ($errorMsg): ?>
  <div style="color:red;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div style="color:green;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<form method="POST">
  <label>Firma Adı:</label>
  <input type="text" name="name" value="<?= htmlspecialchars($company['name']) ?>" required><br><br>

  <label>Logo Yolu:</label>
  <input type="text" name="logo_path" value="<?= htmlspecialchars($company['logo_path']) ?>"><br><br>

  <button type="submit">Kaydet</button>
</form>

</body>
</html>
