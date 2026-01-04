FROM php:8.4-apache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 1. Install dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev libpng-dev git unzip \
    && docker-php-ext-install intl pdo_mysql zip gd \
    && a2enmod rewrite

# 2. Set the Document Root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# 3. DIRECTLY WRITE the Apache Config (Fixes "Not Found" & Routing)
# We use 'echo' to write the file from scratch. This guarantees the path is correct.
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html