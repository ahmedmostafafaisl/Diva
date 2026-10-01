#!/bin/bash
# php artisan key:generate --force

# Run migrations
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php-fpm -D
nginx -g 'daemon off;'