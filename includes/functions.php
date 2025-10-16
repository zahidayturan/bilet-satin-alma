<?php

/**
 * Yeni bir otobüs firması ekler.
 *
 * @param string $name Firma adı.
 * @param string $logo Logo yolu (isteğe bağlı).
 * @return bool Ekleme başarılıysa true, aksi takdirde false.
 */
function addBusCompany(string $name, string $logo = ''): bool
{
    global $pdo; // $pdo nesnesine erişim
    try {
        $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, name, logo_path, created_at)
                               VALUES (:id, :name, :logo, datetime('now'))");
        return $stmt->execute([':id' => uniqid('cmp_'), ':name' => $name, ':logo' => $logo]);
    } catch (PDOException $e) {
        // Hata kaydı veya global hata mesajı ayarlama burada yapılabilir.
        // Şimdilik sadece false döndürüyoruz.
        return false;
    }
}

/**
 * Belirtilen ID'ye sahip otobüs firmasını siler.
 * Yabancı anahtar kısıtlamaları nedeniyle silme başarısız olabilir.
 *
 * @param string $id Silinecek firmanın ID'si.
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function deleteBusCompany(string $id): array
{
    global $pdo; // $pdo nesnesine erişim
    try {
        $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => "Firma başarıyla silindi."];
        } else {
             // Firmanın bulunamadığı durumu
            return ['success' => false, 'message' => "Silinecek firma bulunamadı."];
        }

    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'FOREIGN KEY')) {
            return [
                'success' => false,
                'message' => "❌ Bu firma silinemez! Önce bu firmaya bağlı seferleri veya yöneticileri silin."
            ];
        } else {
            return ['success' => false, 'message' => "Veritabanı hatası: " . $e->getMessage()];
        }
    }
}

/**
 * Tüm otobüs firmalarını en son oluşturulanlara göre sıralayarak getirir.
 *
 * @return array Otobüs firmalarının listesi.
 */
function getAllBusCompanies(): array
{
    global $pdo; // $pdo nesnesine erişim
    // Sorgu sırasında hata olması durumunda boş bir dizi döndürmek güvenlidir.
    try {
        return $pdo->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Otobüs firmalarının sadece ID ve isim listesini getirir (dropdown'lar için).
 *
 * @return array Firma listesi.
 */
function getCompanyListForDropdown(): array
{
    global $pdo;
    try {
        return $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Yeni bir firma yöneticisi (rolü 'company') oluşturur ve veritabanına kaydeder.
 *
 * @param string $fullName Yöneticinin tam adı.
 * @param string $email Yöneticinin e-posta adresi.
 * @param string $passwordCleartext Yöneticinin şifresi (hash'lenmeden önceki hali).
 * @param string $companyId Bağlı olduğu firmanın ID'si.
 * @return bool Ekleme başarılıysa true, aksi takdirde false.
 */
function addCompanyAdmin(string $fullName, string $email, string $passwordCleartext, string $companyId): bool
{
    global $pdo;
    
    // Şifreyi hash'le
    $passwordHashed = password_hash($passwordCleartext, PASSWORD_BCRYPT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, password, role, company_id, created_at)
                               VALUES (:id, :name, :email, :pass, 'company', :cid, datetime('now'))");
        
        return $stmt->execute([
            ':id' => uniqid('usr_'),
            ':name' => $fullName,
            ':email' => $email,
            ':pass' => $passwordHashed,
            ':cid' => $companyId
        ]);
    } catch (PDOException $e) {
        // Hata kaydı burada yapılabilir (örn: e-posta benzersizliği hatası)
        return false;
    }
}

/**
 * Tüm firma yöneticilerini (rolü 'company') ve bağlı oldukları firma adlarını getirir.
 *
 * @return array Firma yöneticilerinin listesi.
 */
function getAllCompanyAdmins(): array
{
    global $pdo;
    try {
        $sql = "SELECT u.id, u.full_name, u.email, b.name AS company
                FROM User u 
                LEFT JOIN Bus_Company b ON u.company_id = b.id
                WHERE u.role = 'company'
                ORDER BY u.created_at DESC";
                
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Belirtilen ID'ye sahip kuponun detaylarını getirir.
 *
 * @param string $couponId Kupon ID'si.
 * @return array|false Kupon bilgileri (dizi) veya bulunamazsa false.
 */
function getCouponById(string $couponId): array|false
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ?");
        $stmt->execute([$couponId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Hata kaydı burada yapılabilir.
        return false;
    }
}

/**
 * Mevcut bir kuponun bilgilerini günceller.
 *
 * @param string $id Kupon ID'si.
 * @param string $code Kupon kodu.
 * @param float $discount İndirim yüzdesi.
 * @param int $usageLimit Kullanım limiti.
 * @param string $expireDate Son kullanma tarihi (YYYY-MM-DD).
 * @param string|null $companyId İsteğe bağlı, kuponun geçerli olduğu firma ID'si veya null.
 * @return bool Güncelleme başarılıysa true, aksi takdirde false.
 */
function updateCoupon(string $id, string $code, float $discount, int $usageLimit, string $expireDate, ?string $companyId): bool
{
    global $pdo;

    // company_id null olarak ayarlanacaksa boş stringi null'a çevir
    $cid = $companyId ?: null; 
    
    try {
        $stmt = $pdo->prepare("
            UPDATE Coupons
            SET code = :code,
                discount = :discount,
                usage_limit = :limit,
                expire_date = :expire,
                company_id = :cid
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':code' => strtoupper(trim($code)),
            ':discount' => $discount,
            ':limit' => $usageLimit,
            ':expire' => $expireDate,
            ':cid' => $cid,
            ':id' => $id
        ]);
    } catch (PDOException $e) {
        // Hata durumunda loglama yapılabilir.
        return false;
    }
}

/**
 * Belirtilen ID'ye sahip otobüs firmasının detaylarını getirir.
 *
 * @param string $companyId Firma ID'si.
 * @return array|false Firma bilgileri (dizi) veya bulunamazsa false.
 */
function getBusCompanyById(string $companyId): array|false
{
    global $pdo; // $pdo nesnesine erişim
    try {
        $stmt = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Hata kaydı burada yapılabilir.
        return false;
    }
}

/**
 * Mevcut bir otobüs firmasının adını ve logosunu günceller.
 *
 * @param string $id Güncellenecek firmanın ID'si.
 * @param string $name Yeni firma adı.
 * @param string $logo Yeni logo yolu.
 * @return bool Güncelleme başarılıysa true, aksi takdirde false.
 */
function updateBusCompany(string $id, string $name, string $logo = ''): bool
{
    global $pdo; // $pdo nesnesine erişim
    try {
        $stmt = $pdo->prepare("UPDATE Bus_Company SET name=?, logo_path=? WHERE id=?");
        return $stmt->execute([$name, $logo, $id]);
    } catch (PDOException $e) {
        // Hata durumunda loglama yapılabilir (örn: unique kısıtlaması).
        return false;
    }
}

/**
 * Belirtilen ID'ye sahip, rolü 'company' olan kullanıcının (Firma Admini) detaylarını getirir.
 *
 * @param string $userId Kullanıcı ID'si.
 * @return array|false Kullanıcı bilgileri (dizi) veya bulunamazsa false.
 */
function getCompanyAdminById(string $userId): array|false
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM User WHERE id = ? AND role = 'company'");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Firma yöneticisinin ad, e-posta ve bağlı olduğu firmayı günceller.
 *
 * @param string $id Yöneticinin ID'si.
 * @param string $fullName Yeni ad soyad.
 * @param string $email Yeni e-posta.
 * @param string $companyId Yeni firma ID'si.
 * @return bool Güncelleme başarılıysa true, aksi takdirde false.
 */
function updateCompanyAdminInfo(string $id, string $fullName, string $email, string $companyId): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE User SET full_name=?, email=?, company_id=? WHERE id=?");
        return $stmt->execute([trim($fullName), trim($email), $companyId, $id]);
    } catch (PDOException $e) {
        // Benzersiz e-posta kısıtlaması gibi hatalar burada yakalanabilir.
        return false;
    }
}

/**
 * Belirtilen kullanıcının şifresini günceller.
 * Not: Bu fonksiyon sadece hash'lenmiş şifreyi kaydeder. Şifre kontrolü sayfa tarafında yapılmalıdır.
 *
 * @param string $id Yöneticinin ID'si.
 * @param string $newHashedPassword Yeni hash'lenmiş şifre.
 * @return bool Güncelleme başarılıysa true, aksi takdirde false.
 */
function updateCompanyAdminPassword(string $id, string $newHashedPassword): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE User SET password=? WHERE id=?");
        return $stmt->execute([$newHashedPassword, $id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Yeni bir kupon oluşturur ve veritabanına kaydeder.
 *
 * @param string $code Kupon kodu (büyük harfe çevrilecek).
 * @param float $discount İndirim yüzdesi.
 * @param int $usageLimit Kullanım limiti.
 * @param string $expireDate Son kullanma tarihi (YYYY-MM-DD).
 * @param string|null $companyId İsteğe bağlı, kuponun geçerli olduğu firma ID'si veya null.
 * @return bool Ekleme başarılıysa true, aksi takdirde false.
 */
function addCoupon(string $code, float $discount, int $usageLimit, string $expireDate, ?string $companyId): bool
{
    global $pdo;
    
    // company_id null olarak ayarlanacaksa boş stringi null'a çevir
    $cid = $companyId ?: null; 
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at)
            VALUES (:id, :code, :discount, :limit, :expire, :cid, datetime('now'))
        ");
        
        return $stmt->execute([
            ':id' => uniqid('coup_'),
            ':code' => strtoupper(trim($code)),
            ':discount' => $discount,
            ':limit' => $usageLimit,
            ':expire' => $expireDate,
            ':cid' => $cid
        ]);
    } catch (PDOException $e) {
        // Kod benzersizliği hatası gibi durumlar için.
        return false;
    }
}

/**
 * Belirtilen ID'ye sahip kuponu siler.
 *
 * @param string $id Silinecek kuponun ID'si.
 * @return bool Silme başarılıysa true, aksi takdirde false.
 */
function deleteCoupon(string $id): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Tüm kuponları, bağlı oldukları firma adıyla ve kullanım sayılarıyla birlikte getirir.
 *
 * @return array Kupon listesi.
 */
function getAllCouponsWithUsage(): array
{
    global $pdo;
    try {
        $sql = "
            SELECT 
                c.*,
                b.name AS company_name,
                (SELECT COUNT(*) FROM User_Coupons uc WHERE uc.coupon_id = c.id) AS used_count
            FROM Coupons c
            LEFT JOIN Bus_Company b ON c.company_id = b.id
            ORDER BY c.created_at DESC
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}