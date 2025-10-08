<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Kullanıcıyı giriş yaptırır.
 */
function loginUser(string $email, string $password): bool {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'company_id' => $user['company_id']
        ];
        return true;
    }
    return false;
}

/**
 * Yeni kullanıcı oluşturur.
 */
function registerUser(string $full_name, string $email, string $password): bool {
    global $pdo;

    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, password, role, created_at)
                           VALUES (:id, :full_name, :email, :password, 'user', datetime('now'))");

    try {
        return $stmt->execute([
            ':id' => uniqid('usr_'),
            ':full_name' => $full_name,
            ':email' => $email,
            ':password' => $hashed
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Oturumu sonlandırır.
 */
function logoutUser(): void {
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Oturumda kullanıcı var mı kontrol eder.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

/**
 * Oturumdaki kullanıcının rolünü döndürür.
 */
function currentUserRole(): ?string {
    return $_SESSION['user']['role'] ?? null;
}

/**
 * Yetkili kullanıcı değilse yönlendir.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Rol bazlı erişim kontrolü
 */
function requireRole(array $roles): void {
    if (!isLoggedIn() || !in_array($_SESSION['user']['role'], $roles)) {
        header('Location: index.php');
        exit;
    }
}
?>
