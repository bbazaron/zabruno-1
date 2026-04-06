#!/bin/sh
set -e
# Том ./backend:/var/www с хоста — выравниваем владельца, иначе laravel.log и кэш недоступны процессу laravel
mkdir -p /var/www/storage/logs
touch /var/www/storage/logs/php-fpm-error.log /var/www/storage/logs/php-fpm-access.log 2>/dev/null || true
chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache
exec gosu laravel "$@"
