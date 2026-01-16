#!/bin/bash

cd /home/ploi/optiflow.com.do

git pull origin main
    
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

sudo -S service php8.4-fpm reload

php artisan migrate --force
php artisan tenants:migrate --force

npm ci
npm run build

php artisan optimize
php artisan reload