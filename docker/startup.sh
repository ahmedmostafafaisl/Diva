#!/bin/sh

set -e  # Exit if any command fails

# Run Laravel Artisan commands
echo "Running Laravel Artisan commands and composer update ..."

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache  
php artisan migrate --force


echo "Artisan commands executed successfully!"

# Start s6-overlay to manage Nginx & PHP-FPM
exec /init
