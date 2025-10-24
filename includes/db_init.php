<?php
$dbFile = __DIR__ . '/../database/bana1bilet.sqlite';
$schemaFile = __DIR__ . '/schema.sql';

if (!file_exists(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0755, true);
}

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Foreign keys enforcement
    $db->exec('PRAGMA foreign_keys = ON;');

    // Eğer schema dosyası yoksa hata ver
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file bulunamadı: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);

    $db->beginTransaction();
    $db->exec($schema);
    $db->commit();
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Sistem başlatılırken hata oluştu. Sistemi sıfırlayın.";
    exit(1);
}

function initializeDatabaseData(PDO $db): void
{
    // Bus_Company tablosunda veri olup olmadığını kontrol et
    $stmt = $db->query("SELECT COUNT(*) FROM Bus_Company");
    if ($stmt->fetchColumn() > 0) {
        return;
    }

    $db->beginTransaction();
    try {
        // --- Sabitler ve Yardımcı Fonksiyonlar ---
        $cities = ['Ankara', 'İstanbul', 'İzmir', 'Samsun', 'Adana', 'Konya'];
        $capacities = [30, 33, 36];
        
        function getRandomFutureDateTime(int $maxDays): DateTime
        {
            $now = new DateTime();
            $randomDays = rand(1, $maxDays);
            $randomHour = rand(8, 22);
            $randomMinute = rand(0, 59);
            
            $futureTime = clone $now;
            $futureTime->modify("+$randomDays days");
            $futureTime->setTime($randomHour, $randomMinute);
            return $futureTime;
        }

        // --- Şifreleri Hazırla ---
        // Şifreler güvenlik için hashlenmelidir
        $hashedUserPass = password_hash('user123', PASSWORD_DEFAULT);
        $hashedCompanyPass = password_hash('company123', PASSWORD_DEFAULT);
        $hashedAdminPass = password_hash('admin123', PASSWORD_DEFAULT);

        // --- Firmaları Ekle ---
        $companiesData = [
            'DörtTeker Turizm'     => ['email' => 'dortteker@bana1bilet.com', 'logo' => 'dortteker-turizm.png'],
            'Mordor\'a Seyahat'   => ['email' => 'mordoraseyahat@bana1bilet.com', 'logo' => 'mordora-seyahat.png'],
            'Uzaya Giden'         => ['email' => 'uzayagiden@bana1bilet.com', 'logo' => 'gelesiye-jet.png'],
            'Gelesiye Jet'        => ['email' => 'gelesiyejet@bana1bilet.com', 'logo' => 'uzayagiden-seyahat.png'],
        ];

        $companyIDs = [];
        $insertCompanyStmt = $db->prepare("INSERT INTO Bus_Company (id, name, logo_path) VALUES (:id, :name, :logo_path)");
        $insertUserStmt = $db->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, balance) VALUES (:id, :full_name, :email, :role, :password, :company_id, :balance)");
        $insertTripStmt = $db->prepare("INSERT INTO Trips (id, company_id, destination_city, arrival_time, departure_time, departure_city, price, capacity) VALUES (:id, :company_id, :destination_city, :arrival_time, :departure_time, :departure_city, :price, :capacity)");
        $insertCouponStmt = $db->prepare("INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date) VALUES (:id, :code, :discount, :company_id, :usage_limit, :expire_date)");

        foreach ($companiesData as $name => $data) {
            $companyId = uniqid('cmp_');
            $companyIDs[$name] = $companyId;
            
            // Bus_Company tablosuna ekle
            $insertCompanyStmt->execute([
                ':id' => $companyId,
                ':name' => $name,
                ':logo_path' => $data['logo']
            ]);

            // User tablosuna ekle (company rolü)
            $insertUserStmt->execute([
                ':id' => uniqid('usr_'),
                ':full_name' => $name . ' Temsilcisi',
                ':email' => $data['email'],
                ':role' => 'company',
                ':password' => $hashedCompanyPass,
                ':company_id' => $companyId,
                ':balance' => 0 // Firma bakiyesi 0 olsun
            ]);
        }

        // --- Diğer Kullanıcıları Ekle ---
        
        // Yolcu Kullanıcı
        $insertUserStmt->execute([
            ':id' => uniqid('usr_'),
            ':full_name' => 'Yolcu',
            ':email' => 'yolcu@bana1bilet.com',
            ':role' => 'user',
            ':password' => $hashedUserPass,
            ':company_id' => null,
            ':balance' => 800 // Varsayılan bakiye
        ]);

        // Admin Kullanıcı
        $insertUserStmt->execute([
            ':id' => uniqid('usr_'),
            ':full_name' => 'Admin',
            ':email' => 'admin@bana1bilet.com',
            ':role' => 'admin',
            ':password' => $hashedAdminPass,
            ':company_id' => null,
            ':balance' => 0 // Admin bakiyesiz
        ]);

        // --- Seyahatleri Ekle (Her firma için 8 adet) ---
        foreach ($companyIDs as $companyName => $companyId) {
            for ($i = 0; $i < 8; $i++) {
                // Rastgele kalkış/varış şehirleri seç
                $departureCity = $cities[array_rand($cities)];
                do {
                    $destinationCity = $cities[array_rand($cities)];
                } while ($destinationCity === $departureCity);

                // Rastgele kalkış zamanı (Önümüzdeki 21 gün içinde)
                $departureTime = getRandomFutureDateTime(21);
                
                // Rastgele seyahat süresi (7-10 saat)
                $travelDurationHours = rand(7, 10);
                
                // Varış zamanı
                $arrivalTime = clone $departureTime;
                $arrivalTime->modify("+$travelDurationHours hours");

                // Rastgele fiyat (50-100 TL, 5'in katı)
                $price = rand(10, 20) * 5; // 50-100 TL

                // Rastgele kapasite
                $capacity = $capacities[array_rand($capacities)];

                $insertTripStmt->execute([
                    ':id' => uniqid('trip_'),
                    ':company_id' => $companyId,
                    ':destination_city' => $destinationCity,
                    ':arrival_time' => $arrivalTime->format('Y-m-d H:i:s'),
                    ':departure_time' => $departureTime->format('Y-m-d H:i:s'),
                    ':departure_city' => $departureCity,
                    ':price' => $price,
                    ':capacity' => $capacity
                ]);
            }
        }

        // --- Kuponları Ekle ---

        // Genel Kupon (B1B20)
        $expireDateGeneral = new DateTime('+1 year');
        $insertCouponStmt->execute([
            ':id' => uniqid('coup_'),
            ':code' => 'B1B20',
            ':discount' => 20, // %20 indirim
            ':company_id' => null,
            ':usage_limit' => 100,
            ':expire_date' => $expireDateGeneral->format('Y-m-d H:i:s')
        ]);

        // Firma Kuponları (Her firma için birer tane)
        foreach ($companyIDs as $companyName => $companyId) {
            $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', $companyName);
            $prefix = strtoupper(substr($cleanName, 0, 3));
            
            $discount = rand(15, 25);
            $code = $prefix . 'IND' . $discount; 
            $expireDateCompany = new DateTime('+6 months');

            $insertCouponStmt->execute([
                ':id' => uniqid('coup_'),
                ':code' => $code,
                ':discount' => $discount,
                ':company_id' => $companyId,
                ':usage_limit' => 50,
                ':expire_date' => $expireDateCompany->format('Y-m-d H:i:s')
            ]);
        }


        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "Sistem test verileri eklenirken bir sorun oluştu. Sistemi yeniden başlatın.";
        exit(1);
    }
}

if (isset($db)) {
    initializeDatabaseData($db);
}

// php includes/db_init.php
