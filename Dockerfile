FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

CMD a2dismod mpm_event mpm_worker >/dev/null 2>&1; \
    rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; \
    a2enmod mpm_prefork >/dev/null 2>&1; \
    sed -i "s/^Listen 80$/Listen ${PORT:-80}/" /etc/apache2/ports.conf; \
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf; \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf; \
    apache2-foreground
