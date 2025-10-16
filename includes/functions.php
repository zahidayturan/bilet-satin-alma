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