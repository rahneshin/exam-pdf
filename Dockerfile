# syntax=docker/dockerfile:1

# ---- مرحله ۱: بیلد asset ها (Tailwind/Vite) ----
FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

# ---- مرحله ۲: نصب dependency های PHP ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- مرحله ۳: ایمیج نهایی اجرا ----
FROM php:8.3-cli-bookworm

# LibreOffice برای تبدیل DOCX->PDF، poppler-utils برای pdftoppm.
# این دو مورد دلیل اصلی این‌اند که از یک Dockerfile سفارشی استفاده می‌کنیم
# و نمی‌توانیم از بیلدپک‌های پیش‌فرض PHP-only استفاده کنیم.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libreoffice \
        poppler-utils \
        fontconfig \
        fonts-vazir \
        unzip \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd \
    && fc-cache -f \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
