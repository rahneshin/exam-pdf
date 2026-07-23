#!/bin/sh
set -e

# APP_KEY را اگر در متغیرهای محیطی Railway ست نشده، یک‌بار تولید می‌کند.
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force

# برای این دیپلوی چند-روزه‌ی تستی، صف را sync نگه می‌داریم تا نیازی به
# پروسس جدا (supervisor/queue:work) نباشد. برای پروداکشن واقعی باید
# QUEUE_CONNECTION=database یا redis باشد و queue:work جدا اجرا شود.
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
