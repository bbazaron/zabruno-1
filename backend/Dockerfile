FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

# создаем пользователя с UID/GID хоста
RUN groupadd -g ${GID} laravel \
    && useradd -u ${UID} -g ${GID} -m laravel

# Устанавливаем зависимости PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql xml mbstring zip \
    && docker-php-ext-enable pdo_pgsql xml mbstring zip

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Переключаемся на нового пользователя
USER laravel
WORKDIR /var/www

# Копируем только нужные файлы, чтобы не было root прав
COPY --chown=laravel:laravel . .

# Устанавливаем зависимости проекта от laravel-пользователя
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Создаем .env и ключ приложения, если его нет
RUN if [ ! -f ".env" ]; then cp .env.example .env; fi
RUN php artisan key:generate

CMD ["php-fpm"]
