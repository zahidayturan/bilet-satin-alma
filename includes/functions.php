<?php
require_once __DIR__ . '/../includes/auth.php';

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

/**
 * Belirtilen firmaya ait seferi, biletleriyle birlikte iptal eder,
 * biletleri siler, koltukları serbest bırakır ve bilet ücretlerini
 * kullanıcılara iade eder (balance artışı). Tüm işlemler tek bir transaction içinde yapılır.
 *
 * @param string $tripId İptal edilecek seferin ID'si.
 * @param string $companyId İşlemi yapan firmanın ID'si (yetki kontrolü için).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function cancelTripAndRefundTickets(string $tripId, string $companyId): array
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // 1. Seferin bu firmaya ait olup olmadığını kontrol et
        $stmt = $pdo->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$tripId, $companyId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Bu sefer size ait değil veya bulunamadı."];
        }

        // 2. Seferdeki aktif biletleri getir
        $stmt = $pdo->prepare("
            SELECT id AS ticket_id, user_id, total_price
            FROM Tickets
            WHERE trip_id = ? AND status = 'active'
        ");
        $stmt->execute([$tripId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Her bilet için iptal, koltuk silme ve iade işlemleri
        foreach ($tickets as $t) {
            // a) Bilet iptal et (status='canceled')
            $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?")
                ->execute([$t['ticket_id']]);

            // b) Koltukları sil
            $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id=?")
                ->execute([$t['ticket_id']]);

            // c) Kullanıcıya ücret iadesi yap (balance artışı)
            $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id=?")
                ->execute([$t['total_price'], $t['user_id']]);
        }

        // 4. Bilet kayıtlarını temizle (opsiyonel: tarihçeyi korumak için burası atlanabilir, ancak orijinal kodda vardı)
        $pdo->prepare("DELETE FROM Tickets WHERE trip_id=?")->execute([$tripId]);
        
        // 5. Seferi sil
        $pdo->prepare("DELETE FROM Trips WHERE id=? AND company_id=?")
            ->execute([$tripId, $companyId]);

        $pdo->commit();
        return ['success' => true, 'message' => "Sefer başarıyla iptal edildi ve iadeler yapıldı."];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => "Hata: " . $e->getMessage()];
    }
}

/**
 * Belirtilen firmaya ait tüm seferleri, satılan bilet sayısı ile birlikte getirir.
 *
 * @param string $companyId Firmanın ID'si.
 * @return array Seferlerin listesi.
 */
function getCompanyTripsWithSoldCount(string $companyId): array
{
    global $pdo;
    try {
        $sql = "
            SELECT 
                t.*,
                (SELECT COUNT(*) 
                 FROM Tickets ti 
                 WHERE ti.trip_id = t.id AND ti.status = 'active') AS sold_count
            FROM Trips t
            WHERE t.company_id = ?
            ORDER BY t.departure_time ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Belirtilen ID'ye sahip seferin, belirtilen firmaya ait olup olmadığını kontrol eder ve sefer detaylarını getirir.
 *
 * @param string $tripId Sefer ID'si.
 * @param string $companyId Firmanın ID'si (yetki kontrolü için).
 * @return array|false Sefer bilgileri (dizi) veya bulunamazsa false.
 */
function getTripDetailsForCompany(string $tripId, string $companyId): array|false
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$tripId, $companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Belirtilen bileti iptal eder, koltuğu serbest bırakır ve kullanıcıya ücret iadesi yapar.
 * İşlem atomik (transaction) olarak gerçekleştirilir.
 *
 * @param string $ticketId İptal edilecek biletin ID'si.
 * @param string $companyId İşlemi yapan firmanın ID'si (yetki kontrolü için).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function cancelTicketAndRefund(string $ticketId, string $companyId): array
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // 1. İlgili bilet bilgilerini al ve yetki/durum kontrolü yap
        $stmt = $pdo->prepare("
            SELECT t.total_price, t.status, t.user_id, tr.departure_time
            FROM Tickets t
            JOIN Trips tr ON tr.id = t.trip_id
            WHERE t.id = ? AND tr.company_id = ?
        ");
        $stmt->execute([$ticketId, $companyId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Bu bilet size ait değil veya bulunamadı."];
        }
        if ($ticket['status'] !== 'active') {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Bu bilet zaten iptal edilmiş."];
        }
        
        // Kalkışa kalan süre kontrolü (İş mantığı DB'ye taşınmaz, burada kalır)
        $hoursLeft = (strtotime($ticket['departure_time']) - time()) / 3600;
        if ($hoursLeft < 1) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Kalkışa 1 saatten az kaldığı için iptal edilemez."];
        }

        // 2. Bileti iptal et
        $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?")->execute([$ticketId]);

        // 3. Koltuğu serbest bırak
        $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id=?")->execute([$ticketId]);

        // 4. Kullanıcıya ücret iadesi yap
        $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id=?")
            ->execute([$ticket['total_price'], $ticket['user_id']]);

        $pdo->commit();
        return ['success' => true, 'message' => "Bilet başarıyla iptal edildi ve iade yapıldı."];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => "Hata: " . $e->getMessage()];
    }
}

/**
 * Belirtilen sefere ait tüm biletleri, yolcu ve koltuk bilgileriyle birlikte getirir.
 *
 * @param string $tripId Sefer ID'si.
 * @return array Biletlerin listesi.
 */
function getTripTickets(string $tripId): array
{
    global $pdo;
    try {
        $sql = "
            SELECT 
                t.id AS ticket_id,
                t.status,
                t.total_price,
                u.full_name,
                u.email,
                bs.seat_number,
                tr.departure_time
            FROM Tickets t
            JOIN User u ON t.user_id = u.id
            LEFT JOIN Booked_Seats bs ON bs.ticket_id = t.id
            JOIN Trips tr ON t.trip_id = tr.id
            WHERE t.trip_id = ?
            ORDER BY bs.seat_number ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Belirtilen firmaya ait biletleri, isteğe bağlı arama filtresiyle birlikte getirir.
 * Geri dönen verilerde sefer ve yolcu detayları bulunur.
 *
 * @param string $companyId Firmanın ID'si.
 * @param string $search Arama kelimesi (sefer şehri veya yolcu adı).
 * @return array Arama sonuçlarına uygun bilet listesi (PDO::FETCH_ASSOC formatında).
 */
function getCompanyTicketsWithSearch(string $companyId, string $search = ''): array
{
    global $pdo;

    $query = "
        SELECT 
            tr.id AS trip_id,
            tr.departure_city,
            tr.destination_city,
            tr.departure_time,
            tr.arrival_time,
            t.id AS ticket_id,
            t.status,
            t.total_price,
            u.full_name AS user_name,
            u.email
        FROM Tickets t
        JOIN Trips tr ON tr.id = t.trip_id
        JOIN User u ON u.id = t.user_id
        WHERE tr.company_id = :cid
    ";

    $params = [':cid' => $companyId];

    if ($search !== '') {
        // Arama filtresi: Kalkış, Varış şehirleri veya Yolcu adı
        $query .= " AND (tr.departure_city LIKE :q OR tr.destination_city LIKE :q OR u.full_name LIKE :q)";
        $params[':q'] = "%$search%";
    }

    $query .= " ORDER BY tr.departure_time DESC, u.full_name ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Hata durumunda boş dizi döndür
        // Hata ayıklama için: error_log($e->getMessage());
        return [];
    }
}

/**
 * Düz bir bilet listesini, sefer ID'sine göre hiyerarşik bir diziye gruplandırır.
 *
 * @param array $ticketRows getCompanyTicketsWithSearch fonksiyonundan dönen düz bilet listesi.
 * @return array Sefer ID'sine göre gruplandırılmış hiyerarşik dizi.
 */
function groupTicketsByTrip(array $ticketRows): array
{
    $trips = [];
    foreach ($ticketRows as $row) {
        $tid = $row['trip_id'];
        if (!isset($trips[$tid])) {
            $trips[$tid] = [
                'departure_city' => $row['departure_city'],
                'destination_city' => $row['destination_city'],
                'departure_time' => $row['departure_time'],
                'arrival_time' => $row['arrival_time'],
                'tickets' => []
            ];
        }
        $trips[$tid]['tickets'][] = $row;
    }
    return $trips;
}

/**
 * Belirtilen sefere ait aktif biletler üzerinden dolu koltuk sayısını hesaplar.
 *
 * @param string $tripId Sefer ID'si.
 * @return int Dolu koltuk sayısı.
 */
function getBookedSeatCountForTrip(string $tripId): int
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT bs.seat_number) AS dolu_koltuk
            FROM Booked_Seats bs
            JOIN Tickets t ON bs.ticket_id = t.id
            WHERE t.trip_id = ? AND t.status = 'active'
        ");
        $stmt->execute([$tripId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['dolu_koltuk'];
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Bir seferin bilgilerini günceller ve kapasitenin dolu koltuk sayısından az olmamasını garanti eder.
 *
 * @param string $tripId Güncellenecek seferin ID'si.
 * @param string $companyId Firmanın ID'si (yetki kontrolü için).
 * @param array $data Yeni sefer verileri (departure_city, capacity vb.).
 * @param int $minCapacity Yeni kapasite için minimum limit (dolu koltuk sayısı).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function updateTripDetails(string $tripId, string $companyId, array $data, int $minCapacity): array
{
    global $pdo;

    $newCapacity = (int)$data['capacity'];
    
    if ($newCapacity < $minCapacity) {
        return [
            'success' => false, 
            'message' => "Hata: Yeni kapasite dolu koltuk sayısından ({$minCapacity}) az olamaz!"
        ];
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE Trips
            SET departure_city=?, destination_city=?, departure_time=?, arrival_time=?, price=?, capacity=?
            WHERE id=? AND company_id=?
        ");
        $stmt->execute([
            $data['departure_city'],
            $data['destination_city'],
            $data['departure_time'],
            $data['arrival_time'],
            $data['price'],
            $newCapacity,
            $tripId,
            $companyId
        ]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => "Sefer başarıyla güncellendi."];
        } else {
            return ['success' => false, 'message' => "Güncelleme başarısız oldu veya hiçbir değişiklik yapılmadı."];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Veritabanı hatası: " . $e->getMessage()];
    }
}

/**
 * Belirtilen ID'ye sahip kuponun detaylarını, firmaya aitlik kontrolü yaparak getirir.
 *
 * @param string $couponId Kupon ID'si.
 * @param string $companyId Firmanın ID'si.
 * @return array|false Kupon bilgileri (dizi) veya bulunamazsa false.
 */
function getCouponDetailsForCompany(string $couponId, string $companyId): array|false
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$couponId, $companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Hata durumunda güvenli bir şekilde false döndür
        return false;
    }
}

/**
 * Bir kuponun bilgilerini günceller.
 *
 * @param string $couponId Güncellenecek kuponun ID'si.
 * @param string $companyId Firmanın ID'si (yetki kontrolü için).
 * @param array $data Yeni kupon verileri (code, discount, usage_limit, expire_date).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function updateCouponForCompany(string $couponId, string $companyId, array $data): array
{
    global $pdo;
    
    // Veri temizleme ve formatlama
    $code = strtoupper(trim($data['code']));
    $discount = floatval($data['discount']);
    $usage_limit = intval($data['usage_limit']);
    $expire_date = $data['expire_date'];

    try {
        $stmt = $pdo->prepare("
            UPDATE Coupons 
            SET code = ?, discount = ?, usage_limit = ?, expire_date = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$code, $discount, $usage_limit, $expire_date, $couponId, $companyId]);

        if ($stmt->rowCount() > 0) {
             return ['success' => true, 'message' => "Kupon başarıyla güncellendi."];
        }
        // rowCount 0 ise, ya veri değişmedi ya da kupon bulunamadı (yetki kontrolü sayfada yapıldığı için, veri değişmedi varsayılabilir)
        return ['success' => true, 'message' => "Kupon başarıyla güncellendi (veya değişiklik yapılmadı)."];


    } catch (PDOException $e) {
        // Kupon kodunun UNIQUE olmasından kaynaklanan hata (SQLSTATE 23000) kontrol edilebilir
        if (strpos($e->getMessage(), 'Integrity constraint violation: 1062 Duplicate entry') !== false) {
             return ['success' => false, 'message' => "Hata: Girdiğiniz kupon kodu zaten kullanımda."];
        }
        return ['success' => false, 'message' => "Veritabanı hatası: " . $e->getMessage()];
    }
}

/**
 * Yeni bir kupon oluşturur ve veritabanına kaydeder.
 *
 * @param string $companyId Kuponu oluşturan firmanın ID'si.
 * @param array $data Kupon verileri (code, discount, usage_limit, expire_date).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function createCoupon(string $companyId, array $data): array
{
    global $pdo;
    
    // Veri temizleme ve formatlama
    $code = strtoupper(trim($data['code']));
    $discount = floatval($data['discount']);
    $usage_limit = intval($data['usage_limit']);
    $expire_date = $data['expire_date'];

    try {
        // Kupon ID'si için benzersiz bir değer oluştur
        $couponId = uniqid('coup_');
        
        $stmt = $pdo->prepare("
            INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at)
            VALUES (:id, :code, :disc, :limit, :expire, :cid, datetime('now'))
        ");
        $stmt->execute([
            ':id' => $couponId,
            ':code' => $code,
            ':disc' => $discount,
            ':limit' => $usage_limit,
            ':expire' => $expire_date,
            ':cid' => $companyId
        ]);
        
        return ['success' => true, 'message' => "Kupon **{$code}** başarıyla eklendi."];

    } catch (PDOException $e) {
        // Kupon kodunun UNIQUE olmasından kaynaklanan hata (SQLSTATE 23000)
        if ($e->getCode() === '23000') {
             return ['success' => false, 'message' => "Hata: Girdiğiniz kupon kodu zaten kullanımda. Lütfen farklı bir kod deneyin."];
        }
        return ['success' => false, 'message' => "Veritabanı hatası: " . $e->getMessage()];
    }
}

/**
 * Belirtilen ID'ye sahip kuponu, firmaya aitlik kontrolü yaparak siler.
 *
 * @param string $couponId Silinecek kuponun ID'si.
 * @param string $companyId Firmanın ID'si (yetki kontrolü için).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function deleteCouponForCompany(string $couponId, string $companyId): array
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$couponId, $companyId]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => "Kupon başarıyla silindi."];
        } else {
            return ['success' => false, 'message' => "Silinecek kupon bulunamadı veya size ait değil."];
        }
    } catch (PDOException $e) {
        // Eğer kupon başka tablolarda (User_Coupons) kullanılıyorsa, foreign key hatası dönebilir.
        if ($e->getCode() === '23000') {
            return ['success' => false, 'message' => "Hata: Bu kupon kullanıldığı için silinemiyor. Önce ilgili kayıtları temizleyin."];
        }
        return ['success' => false, 'message' => "Veritabanı hatası: " . $e->getMessage()];
    }
}

/**
 * Belirtilen firmaya ait kuponları kullanım sayılarıyla birlikte listeler.
 *
 * @param string $companyId Firmanın ID'si.
 * @return array Kuponların listesi.
 */
function getCompanyCoupons(string $companyId): array
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                (SELECT COUNT(*) FROM User_Coupons uc WHERE uc.coupon_id = c.id) AS used_count
            FROM Coupons c
            WHERE c.company_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Hata durumunda boş dizi döndür
        return [];
    }
}

/**
 * Yeni bir sefer oluşturur ve veritabanına kaydeder.
 *
 * @param string $companyId Seferi ekleyen firmanın ID'si.
 * @param array $data Sefer verileri (departure_city, destination_city, departure_time, arrival_time, price, capacity).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function createNewTrip(string $companyId, array $data): array
{
    global $pdo;

    try {
        // Sefer ID'si için benzersiz bir değer oluştur
        $tripId = uniqid('trip_');
        
        $stmt = $pdo->prepare("
            INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date)
            VALUES (:id, :cid, :dep, :dest, :dtime, :atime, :price, :cap, datetime('now'))
        ");
        $stmt->execute([
            ':id' => $tripId,
            ':cid' => $companyId,
            ':dep' => $data['departure_city'],
            ':dest' => $data['destination_city'],
            ':dtime' => $data['departure_time'],
            ':atime' => $data['arrival_time'],
            ':price' => $data['price'],
            ':cap' => $data['capacity']
        ]);
        
        return ['success' => true, 'message' => "Yeni sefer başarıyla eklendi."];

    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Sefer ekleme sırasında veritabanı hatası oluştu: " . $e->getMessage()];
    }
}

/**
 * Kalkış saati geçmiş olan kullanıcının aktif biletlerini 'expired' durumuna günceller.
 *
 * @param string $userId Kullanıcının ID'si.
 * @return bool İşlem başarılıysa true.
 */
function expireUserPastTickets(string $userId): bool
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE Tickets 
            SET status = 'expired'
            WHERE user_id = ? 
              AND status = 'active' 
              AND trip_id IN (
                SELECT id FROM Trips WHERE datetime(departure_time) < datetime('now')
              )
        ");
        $stmt->execute([$userId]);
        return true;
    } catch (PDOException $e) {
        // Hata durumunda sadece loglama yapılabilir, kullanıcının sayfasını durdurmaya gerek yok.
        // error_log("Bilet süresi dolumu hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Belirtilen kullanıcının tüm biletlerini sefer, firma ve koltuk detaylarıyla birlikte getirir.
 *
 * @param string $userId Kullanıcının ID'si.
 * @return array Biletlerin detaylı listesi.
 */
function getUserTicketsDetails(string $userId): array
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.id AS ticket_id, 
                t.status, 
                t.total_price, 
                tr.departure_city, 
                tr.destination_city, 
                tr.departure_time, 
                tr.arrival_time,
                tr.id AS trip_id,
                bc.name AS company_name,
                b.seat_number
            FROM Tickets t
            JOIN Trips tr ON t.trip_id = tr.id
            LEFT JOIN Bus_Company bc ON tr.company_id = bc.id
            LEFT JOIN Booked_Seats b ON b.ticket_id = t.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Belirtilen ID'ye sahip seferin detaylarını ve firma adını getirir.
 *
 * @param string $tripId Sefer ID'si.
 * @return array|false Sefer bilgileri (dizi) veya bulunamazsa false.
 */
function getTripDetailsWithCompanyName(string $tripId): array|false
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT Trips.*, Bus_Company.name AS company_name 
            FROM Trips
            LEFT JOIN Bus_Company ON Trips.company_id = Bus_Company.id
            WHERE Trips.id = ?
        ");
        $stmt->execute([$tripId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Belirtilen sefere ait aktif durumdaki dolu koltuk sayısını (bilet sayısını) hesaplar.
 *
 * @param string $tripId Sefer ID'si.
 * @return int Dolu koltuk sayısı.
 */
function getActiveBookedCountForTrip(string $tripId): int
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS dolu 
            FROM Tickets 
            WHERE trip_id = ? AND status = 'active'
        ");
        $stmt->execute([$tripId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['dolu'];
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Belirtilen kullanıcının tüm profil detaylarını (varsa firma adı dahil) getirir.
 *
 * @param string $userId Kullanıcının ID'si.
 * @return array|false Profil bilgileri (dizi) veya bulunamazsa false.
 */
function getUserProfileDetails(string $userId): array|false
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, c.name AS company_name
            FROM User u
            LEFT JOIN Bus_Company c ON u.company_id = c.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Kullanıcının şifresini güvenli bir şekilde değiştirir.
 *
 * @param string $userId Kullanıcının ID'si.
 * @param string $oldPassword Mevcut şifre.
 * @param string $newPassword Yeni şifre.
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function changeUserPassword(string $userId, string $oldPassword, string $newPassword): array
{
    global $pdo;
    
    // 1. Mevcut şifreyi doğrula
    $stmt = $pdo->prepare("SELECT password FROM User WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($oldPassword, $row['password'])) {
        return ['success' => false, 'message' => "Mevcut şifre yanlış."];
    }
    
    // 2. Yeni şifreyi hash'le ve güncelle
    try {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE User SET password=? WHERE id=?");
        $stmt->execute([$hash, $userId]);
        
        return ['success' => true, 'message' => "Şifre başarıyla değiştirildi."];

    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Şifre güncelleme hatası: " . $e->getMessage()];
    }
}

/**
 * Kullanıcı tarafından belirtilen filtrelerle aktif seferleri listeler.
 *
 * @param string $from Kalkış şehri filtresi.
 * @param string $to Varış şehri filtresi.
 * @param string $date Tarih filtresi (YYYY-MM-DD).
 * @param int $limit Maksimum sefer sayısı.
 * @return array Seferlerin firma adıyla birlikte listesi.
 */
function searchActiveTrips(string $from = '', string $to = '', string $date = '', int $limit = 10): array
{
    global $pdo;

    $query = "
        SELECT Trips.*, Bus_Company.name AS company_name
        FROM Trips
        LEFT JOIN Bus_Company ON Trips.company_id = Bus_Company.id
        WHERE datetime(departure_time) > datetime('now')
    ";
    $params = [];

    // Filtreleme
    if ($from !== '') {
        $query .= " AND departure_city LIKE :from";
        $params[':from'] = "%$from%";
    }
    if ($to !== '') {
        $query .= " AND destination_city LIKE :to";
        $params[':to'] = "%$to%";
    }
    if ($date !== '') {
        $query .= " AND DATE(departure_time) = :date";
        $params[':date'] = $date;
    }

    // Sıralama ve Limit
    $query .= " ORDER BY departure_time ASC LIMIT :limit";
    $params[':limit'] = $limit;

    try {
        $stmt = $pdo->prepare($query);
        // PDO'da LIMIT parametresini doğrudan bindValue ile INTEGER olarak belirtmek gerekir.
        // LIMIT için özel bir durum: MySQL/SQLite LIMIT/OFFSET değerleri için doğrudan integer bind'ı desteklemez. 
        // Ancak bu örnekte, LIMIT değeri sabit olduğu için, güvenlik amacıyla sorguyu dikkatli hazırlayabiliriz.
        // PDO::PARAM_INT kullanımını manuel olarak ekliyorum:

        foreach ($params as $key => &$value) {
            if ($key === ':limit') continue;
            $stmt->bindParam($key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Hata durumunda boş dizi döndür
        // error_log("Sefer arama hatası: " . $e->getMessage());
        return [];
    }
}

/**
 * Kupon kodunu belirli bir sefer için doğrular ve geçerliyse yeni fiyatı hesaplar.
 *
 * @param string $tripId Sefer ID'si.
 * @param string $code Kupon kodu.
 * @return array Sonuç dizisi: ['valid' => bool, 'new_price' => float|null, 'error' => string|null]
 */
function validateCouponAndCalculatePrice(string $tripId, string $code): array
{
    global $pdo;
    $code = strtoupper(trim($code));

    // 1. Sefer Bilgisini Çek
    $stmt = $pdo->prepare("SELECT price, company_id FROM Trips WHERE id = ?");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        return ['valid' => false, 'new_price' => null, 'error' => 'Sefer bulunamadı.'];
    }
    
    // 2. Kupon Bilgisini Çek
    $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE UPPER(code) = ?");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        return ['valid' => false, 'new_price' => null, 'error' => 'Kupon bulunamadı.'];
    }

    // 3. Geçerlilik Kontrolleri
    if ($coupon['expire_date'] < date('Y-m-d')) {
        return ['valid' => false, 'new_price' => null, 'error' => 'Kupon süresi dolmuş.'];
    }

    if ($coupon['company_id'] !== null && $coupon['company_id'] !== $trip['company_id']) {
        return ['valid' => false, 'new_price' => null, 'error' => 'Bu kupon bu firmaya ait değil.'];
    }

    // 4. Kullanım Limiti Kontrolü
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = ?");
    $stmt->execute([$coupon['id']]);
    $usedCount = (int)$stmt->fetchColumn();

    if ($usedCount >= $coupon['usage_limit']) {
        return ['valid' => false, 'new_price' => null, 'error' => 'Bu kuponun kullanım limiti dolmuş.'];
    }

    // 5. Yeni Fiyatı Hesapla
    $newPrice = round($trip['price'] * (1 - floatval($coupon['discount']) / 100), 2);

    return ['valid' => true, 'new_price' => $newPrice, 'error' => null, 'coupon_id' => $coupon['id'], 'discount' => $coupon['discount']];
}

/**
 * Kullanıcı tarafından satın alınan bir bileti, zaman kontrolü yaparak iptal eder ve iade yapar.
 *
 * @param string $ticketId İptal edilecek biletin ID'si.
 * @param string $userId İşlemi yapan kullanıcının ID'si (yetki kontrolü için).
 * @param int $minHoursBeforeDeparture İptal için minimum kalan saat (varsayılan 1 saat).
 * @return array Sonuç dizisi: ['success' => bool, 'message' => string]
 */
function cancelTicketAndRefundByUser(string $ticketId, string $userId, int $minHoursBeforeDeparture = 1): array
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // 1. Bilet ve Sefer Bilgilerini Çek (Yetki ve Fiyat için)
        $stmt = $pdo->prepare("
            SELECT t.id, t.status, t.total_price, tr.departure_time
            FROM Tickets t
            JOIN Trips tr ON t.trip_id = tr.id
            WHERE t.id = ? AND t.user_id = ? AND t.status = 'active'
        ");
        $stmt->execute([$ticketId, $userId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Aktif bilet bulunamadı veya size ait değil.'];
        }

        // 2. Zaman Kontrolü
        $hoursLeft = (strtotime($ticket['departure_time']) - time()) / 3600;
        if ($hoursLeft < $minHoursBeforeDeparture) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Kalkıştan {$minHoursBeforeDeparture} saatten az kaldığı için iptal edilemez."];
        }

        // 3. Bilet iptali
        $stmt = $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?");
        $stmt->execute([$ticketId]);

        // 4. Koltuğu boşalt
        $stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id = ?");
        $stmt->execute([$ticketId]);

        // 5. Kullanıcının bakiyesine iade
        $stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$ticket['total_price'], $userId]);

        $pdo->commit();

        return ['success' => true, 'message' => "Bilet başarıyla iptal edildi. {$ticket['total_price']} ₺ bakiyenize iade edildi."];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => "Veritabanı hatası: " . $e->getMessage()];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => "Genel hata: " . $e->getMessage()];
    }
}

/**
 * Belirtilen ID'ye sahip seferin detaylarını, firma adını ve zaman kontrolünü yaparak getirir.
 *
 * @param string $tripId Sefer ID'si.
 * @return array|false Sefer bilgileri (dizi) veya bulunamaz/geçmişse false.
 */
function getTripDetailsForPurchase(string $tripId): array|false
{
    global $pdo;

    // Sefer ve firma bilgisi
    $stmt = $pdo->prepare("
        SELECT Trips.*, Bus_Company.name AS company_name 
        FROM Trips
        LEFT JOIN Bus_Company ON Trips.company_id = Bus_Company.id
        WHERE Trips.id = ?
    ");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        return false;
    }

    // Geçmiş sefer kontrolü
    if (strtotime($trip['departure_time']) <= time()) {
        return ['error' => 'Bu seferin kalkış saati geçmiş, bilet alınamaz.'];
    }
    
    return $trip;
}

/**
 * Belirtilen sefere ait aktif olarak rezerve edilmiş koltuk numaralarını getirir.
 *
 * @param string $tripId Sefer ID'si.
 * @return array Dolu koltuk numaralarının dizisi (string veya int).
 */
function getBookedSeatsForTrip(string $tripId): array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT seat_number FROM Booked_Seats
        WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ? AND status = 'active')
    ");
    $stmt->execute([$tripId]);
    
    // PHP'de array_column her zaman string döner, bu yüzden dönüş tipi int olsa bile dikkat etmek gerekir.
    return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number'));
}