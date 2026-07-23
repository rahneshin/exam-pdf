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
# نکته: composer.lock پروژه Laravel 13 / Symfony 8.x را قفل کرده که به PHP >=8.4.1
# نیاز دارد؛ به همین دلیل ایمیج runtime هم باید 8.4 باشد، نه 8.3.
FROM php:8.4-cli-bookworm

# LibreOffice برای تبدیل DOCX->PDF، poppler-utils برای pdftoppm.
# libjpeg62-turbo-dev و libfreetype6-dev برای پشتیبانی کامل gd (JPEG/فونت) اضافه شده‌اند.
# این‌ها دلیل اصلی این‌اند که از یک Dockerfile سفارشی استفاده می‌کنیم
# و نمی‌توانیم از بیلدپک‌های پیش‌فرض PHP-only (مثل Railpack) استفاده کنیم.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libreoffice \
        poppler-utils \
        fontconfig \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
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
