# Menggunakan PHP CLI versi 8.3
FROM php:8.4-cli

# Memasang library PostgreSQL dan ekstensi PDO
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip git \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Memasang Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Menentukan folder kerja
WORKDIR /app

# Menyalin kode project ke container
COPY . .

# Memasang dependency Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Membuka port aplikasi
EXPOSE 3001

# Menjalankan migration lalu server Laravel
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=3001"]