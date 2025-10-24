# Bana1Bilet Otobüs Bileti Sistemi

Bu proje, bir otobüs bileti rezervasyon ve yönetim sisteminin temel işlevlerini barındıran basit bir PHP (SQLite) uygulamasıdır.


## Hızlı Erişim Bilgileri (Test Girişleri)

Geliştirme ve test süreçleri için önceden tanımlanmış kullanıcı hesapları mevcuttur.

| Rol | Email | Şifre |
| :--- | :--- | :--- |
| **Yolcu (User)** | `yolcu@bana1bilet.com` | `user123` |
| **Admin** | `admin@bana1bilet.com` | `admin123` | 
| **Firma (Company)** | `dortteker@bana1bilet.com` | 
| | `mordoraseyahat@bana1bilet.com` | `company123` | 
| | `uzayagiden@bana1bilet.com` | `company123` |
| | `gelesiyejet@bana1bilet.com` | `company123` |

---

## Kurulum

Projeyi çalıştırmak için iki farklı yöntem kullanabilirsiniz: Docker Compose veya yerel PHP sunucusu.

Projeyi yerel makinenize indirmek için aşağıdaki komutu kullanın:
```bash
git clone https://github.com/zahidayturan/bilet-satin-alma.git

cd bilet-satin-alma
```

### Yöntem 1: Docker Compose ile Kurulum (Önerilen)

Bu yöntem, tüm bağımlılıkları (PHP, veritabanı) izole bir ortamda hazırlar ve veritabanı kurulumu otomatik olarak gerçekleştirilir.

1.  **Gereksinimler:** Sisteminizde [Docker](https://www.docker.com/products/docker-desktop) kurulu olmalıdır.
2.  **Servisleri Başlatma:** Projenin ana dizininde aşağıdaki komutu çalıştırın:
    ```bash
    docker compose up --build

    docker compose up
    ```
    
1.  **Erişim:** Uygulamanıza tarayıcınızdan aşağıdaki adresten erişin:
    ```
    http://localhost:8080
    ```

### Yöntem 2: Yerel PHP Sunucusu ile Kurulum

Docker kullanmak istemiyorsanız, yerel PHP kurulumunuz ile projeyi çalıştırabilirsiniz.

1.  **Gereksinimler:** Sisteminizde PHP 7.4+ kurulu olmalıdır.
2.  **Yerel Sunucuyu Başlatma:** Uygulamayı çalıştırmak için PHP'nin yerleşik sunucusunu kullanın ve sunucu kök dizinini `public` klasörüne ayarlayın:
    ```bash
    php -S localhost:8000 -t public
    ```
3.  **Erişim:** Tarayıcınızı açın ve uygulamaya aşağıdaki adresten erişin:
    ```
    http://localhost:8000
    ```