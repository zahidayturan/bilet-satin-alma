# 🚌 Bus Ticketing Platform – Database Schema

Bu veritabanı, otobüs bileti satış ve yönetim sistemine ait çok-kullanıcılı bir yapıyı temsil eder.
Sistem; kullanıcılar, firmalar, seferler, biletler ve kuponlar arasında ilişkisel bir yapı kullanır.
Veritabanı **SQLite** üzerinde çalışacak şekilde tasarlanmıştır.

---

## 🗂️ Tablolar ve İlişkiler

### 1. **User**

| Alan         | Tip       | Özellikler                    | Açıklama                                            |
| ------------ | --------- | ----------------------------- | --------------------------------------------------- |
| `id`         | UUID      | PK                            | Kullanıcı kimliği                                   |
| `full_name`  | TEXT      |                               | Ad Soyad                                            |
| `email`      | TEXT      | UQ, NN                        | E-posta adresi (benzersiz)                          |
| `role`       | TEXT      | NN                            | Kullanıcı rolü: `user`, `company`, `admin`          |
| `password`   | TEXT      | NN                            | Şifre (hashlenmiş olarak saklanır)                  |
| `company_id` | UUID      | FK (Bus_Company.id), NULLABLE | Firma ile ilişki (sadece şirket kullanıcıları için) |
| `balance`    | INTEGER   | DEFAULT 800                   | Kullanıcının bakiyesi                               |
| `created_at` | TIMESTAMP | NN                            | Oluşturulma tarihi                                  |

**İlişkiler:**

* Bir kullanıcı **bir firmaya** ait olabilir (`company_id`).
* Bir kullanıcı **birden fazla bilet** satın alabilir (`Tickets` tablosu ile 1:N).
* Kullanıcı **kuponlara** sahip olabilir (`User_Coupons` tablosu ile 1:N).

---

### 2. **Bus_Company**

| Alan         | Tip       | Özellikler | Açıklama           |
| ------------ | --------- | ---------- | ------------------ |
| `id`         | UUID      | PK         | Firma kimliği      |
| `name`       | TEXT      | UQ, NN     | Firma adı          |
| `logo_path`  | TEXT      |            | Firma logosu       |
| `created_at` | TIMESTAMP |            | Oluşturulma tarihi |

**İlişkiler:**

* Bir firma **birden fazla sefer** düzenleyebilir (`Trips` tablosu ile 1:N).
* Firma kullanıcıları (`User` tablosu) ile bağlantılıdır.

---

### 3. **Trips**

| Alan               | Tip       | Özellikler          | Açıklama                |
| ------------------ | --------- | ------------------- | ----------------------- |
| `id`               | UUID      | PK                  | Sefer kimliği           |
| `company_id`       | UUID      | FK (Bus_Company.id) | Seferi düzenleyen firma |
| `destination_city` | TEXT      | NN                  | Varış noktası           |
| `arrival_time`     | DATETIME  | NN                  | Varış zamanı            |
| `departure_time`   | DATETIME  | NN                  | Kalkış zamanı           |
| `departure_city`   | TEXT      | NN                  | Kalkış noktası          |
| `price`            | INTEGER   | NN                  | Bilet fiyatı            |
| `capacity`         | INTEGER   | NN                  | Koltuk sayısı           |
| `created_date`     | TIMESTAMP |                     | Oluşturulma tarihi      |

**İlişkiler:**

* Her sefer bir **firmaya** aittir.
* Her sefer için **birden fazla bilet** kesilebilir (`Tickets` tablosu ile 1:N).

---

### 4. **Tickets**

| Alan          | Tip       | Özellikler         | Açıklama                                                           |
| ------------- | --------- | ------------------ | ------------------------------------------------------------------ |
| `id`          | UUID      | PK                 | Bilet kimliği                                                      |
| `trip_id`     | UUID      | FK (Trips.id)      | İlgili sefer                                                       |
| `user_id`     | UUID      | FK (User.id)       | Bileti alan kullanıcı                                              |
| `status`      | TEXT      | DEFAULT ACTIVE, NN | Bilet durumu: `active`, `canceled`, `expired`                      |
| `totel_price` | INTEGER   | NN                 | Biletin toplam fiyatı *(tipografik olarak “total_price” olabilir)* |
| `created_at`  | TIMESTAMP |                    | Oluşturulma tarihi                                                 |

**İlişkiler:**

* Bir bilet **bir kullanıcıya** ve **bir sefere** bağlıdır.
* Her bilet için **koltuk detayları** (`Booked_Seats`) tutulur.

---

### 5. **Booked_Seats**

| Alan          | Tip       | Özellikler      | Açıklama           |
| ------------- | --------- | --------------- | ------------------ |
| `id`          | UUID      | PK              | Kayıt kimliği      |
| `ticket_id`   | UUID      | FK (Tickets.id) | İlgili bilet       |
| `seat_number` | INTEGER   | NN              | Koltuk numarası    |
| `created_at`  | TIMESTAMP |                 | Oluşturulma tarihi |

**İlişkiler:**

* Her kayıt **bir bilete** aittir.
* Böylece bir bilet bir veya birden fazla koltukla eşleştirilebilir.

---

### 6. **Coupons**

| Alan          | Tip       | Özellikler | Açıklama                                           |
| ------------- | --------- | ---------- | -------------------------------------------------- |
| `id`          | UUID      | PK         | Kupon kimliği                                      |
| `code`        | TEXT      | NN         | Kupon kodu                                         |
| `discount`    | REAL      | NN         | İndirim oranı veya miktarı                         |
| `usage_limit` | INTEGER   | NN         | Kuponun kullanılabileceği maksimum sayıda kullanım |
| `expire_date` | DATETIME  | NN         | Kuponun geçerlilik bitiş tarihi                    |
| `created_at`  | TIMESTAMP |            | Oluşturulma tarihi                                 |

**İlişkiler:**

* Bir kupon birden fazla kullanıcı tarafından kullanılabilir (`User_Coupons` tablosu ile N:N).

---

### 7. **User_Coupons**

| Alan         | Tip       | Özellikler          | Açıklama                  |
| ------------ | --------- | ------------------- | ------------------------- |
| `id`         | UUID      | PK                  | Kayıt kimliği             |
| `coupon_id`  | UUID      | FK (Coupons.id), NN | İlgili kupon              |
| `user_id`    | UUID      | FK (User.id), NN    | Kuponu kullanan kullanıcı |
| `created_at` | TIMESTAMP |                     | Oluşturulma tarihi        |

**İlişkiler:**

* Kullanıcılar ile kuponlar arasında **çoktan-çoğa (N:N)** bir ilişki kurar.

---

## 🔗 Özet – İlişki Haritası

* **User ⇄ Bus_Company:** Kullanıcılar firmalara bağlı olabilir (özellikle rolü `company` olanlar).
* **Bus_Company ⇄ Trips:** Firmalar seferler düzenler.
* **Trips ⇄ Tickets:** Her sefer için biletler oluşturulur.
* **Tickets ⇄ Booked_Seats:** Her bilet bir veya birden fazla koltuğu kapsar.
* **User ⇄ Tickets:** Kullanıcılar bilet satın alır.
* **User ⇄ Coupons:** Kuponlar kullanıcılarla eşleştirilebilir (N:N).

---