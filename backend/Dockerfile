FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql xml dom mbstring zip \
    && docker-php-ext-enable xml dom mbstring zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

COPY .. .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN if [ ! -f ".env" ]; then cp .env.example .env; fi

RUN php artisan key:generate

CMD ["php-fpm"]
