FROM php:8.1-apache

# Sistem paketleri ve PHP uzantıları
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
        libzip-dev \
        unzip \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# GD ve diğer PHP uzantılarını kur
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_sqlite zip gd

# Composer'ı yükle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizini
WORKDIR /var/www/html

# Composer dosyalarını kopyala ve bağımlılıkları yükle
COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader

# Proje dosyalarını kopyala
COPY . .

# Apache ayarları
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/public>!g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

# Geçici dosyalar için dizin
RUN mkdir -p /var/www/html/tmp \
    && chmod -R 777 /var/www/html/tmp \
    && mkdir -p /var/www/html/database \
    && chmod -R 777 /var/www/html/database
