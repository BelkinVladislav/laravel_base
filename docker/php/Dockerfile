# Используем официальный образ PHP 8.2 FPM
FROM php:8.2-fpm
# Устанавливаем аргументы сборки
ARG UID=1000
ARG GID=1000
# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
git \
curl \
libpng-dev \
libonig-dev \
libxml2-dev \
libzip-dev \
libpq-dev \
zip \
unzip \
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/* 
# Устанавливаем PHP расширения
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
&& docker-php-ext-install \
pdo \
pdo_pgsql \
pgsql \
mbstring \
exif \
pcntl \
bcmath \
gd \
zip \
opcache
# Устанавливаем Redis расширение
RUN pecl install redis \
&& docker-php-ext-enable redis
# Копируем Composer из официального образа
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Создаём пользователя для Laravel
RUN groupadd -g ${GID} laravel \
&& useradd -u ${UID} -g laravel -m laravel
# Копируем конфигурацию PHP
COPY local.ini /usr/local/etc/php/conf.d/local.ini
# Устанавливаем рабочую директорию
WORKDIR /var/www
# Переключаемся на пользователя laravel
USER laravel
# Открываем порт 9000
EXPOSE 9000
CMD ["php-fpm"]