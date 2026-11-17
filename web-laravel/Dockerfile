FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json ./
RUN npm install
COPY . .
RUN npm run build

FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --optimize-autoloader

FROM php:8.5-apache
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers
WORKDIR /var/www/html
COPY --from=dependencies /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/thaki-entrypoint
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf \
    && chmod +x /usr/local/bin/thaki-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache storage/app \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan package:discover --ansi
EXPOSE 80
ENTRYPOINT ["thaki-entrypoint"]
CMD ["apache2-foreground"]
