FROM php:8.3-apache

RUN set -eux; \
    apt-get update && apt-get install -y --no-install-recommends default-mysql-client && rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install pdo pdo_mysql && \
    a2enmod rewrite

WORKDIR /var/www/html

COPY public/ /var/www/html/public/
COPY src/ /var/www/html/src/
COPY README.md /var/www/html/
COPY public/.htaccess /var/www/html/public/.htaccess

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
