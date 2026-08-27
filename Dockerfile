FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpng-dev && \
    docker-php-ext-install pdo pdo_mysql && \
    a2dismod mpm_event && \
    a2enmod mpm_prefork rewrite && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY . /var/www/html/

EXPOSE 80
