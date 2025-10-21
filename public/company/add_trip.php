<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['company']);
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

$company_id = $_SESSION['user']['company_id'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_data = [
        'departure_city' => $_POST['departure_city'] ?? '',
        'destination_city' => $_POST['destination_city'] ?? '',
        'departure_time' => $_POST['departure_time'] ?? '',
        'arrival_time' => $_POST['arrival_time'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'capacity' => $_POST['capacity'] ?? 0,
    ];

    $result = createNewTrip($company_id, $trip_data);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: trips.php');
        exit;
    } else {
        $_SESSION['error_message'] = $result['message'];
        header('Location: add_trip.php');
        exit;
    }
}

$page_title = "Bana1Bilet - Firma Yönetimi";
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 20px;"><a href="trips.php">← Seferlere Geri Dön</a></div>

<?php require_once __DIR__ . '/../../includes/message_comp.php'; ?>

<div class="container">
    <h2>Yeni Sefer Ekle</h2>
    <form method="POST">
    <label>Kalkış</label>
    <input type="text" name="departure_city" value="<?= htmlspecialchars($_POST['departure_city'] ?? '') ?>" required>
    <label>Varış</label>
    <input type="text" name="destination_city" value="<?= htmlspecialchars($_POST['destination_city'] ?? '') ?>" required>
    <label>Kalkış Zamanı</label>
    <input type="datetime-local" name="departure_time" value="<?= htmlspecialchars($_POST['departure_time'] ?? '') ?>" required>
    <label>Varış Zamanı</label>
    <input type="datetime-local" name="arrival_time" value="<?= htmlspecialchars($_POST['arrival_time'] ?? '') ?>" required>
    <label>Fiyat</label>
    <input type="number" name="price" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" min="1" required>
    <label>Kapasite</label>
    <input type="number" name="capacity" value="<?= htmlspecialchars($_POST['capacity'] ?? '') ?>" min="1" required>
    <button class="form-button" type="submit">Yeni Sefer Oluştur</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>