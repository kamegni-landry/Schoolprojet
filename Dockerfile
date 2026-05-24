FROM php:8.1-apache

WORKDIR /var/www/html

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . .

# Installer les dépendances PHP avec Composer
RUN composer install --no-dev --optimize-autoloader

# Extensions PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer mod_rewrite
RUN a2enmod rewrite

# Configuration Apache
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom.conf && \
a2enconf custom

EXPOSE 80

CMD ["apache2-foreground"]