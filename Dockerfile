FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install curl mbstring fileinfo \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/beratungsassistent.ini
COPY docker/apache-site.conf /etc/apache2/conf-available/beratungsassistent.conf
RUN a2enconf beratungsassistent

COPY . /var/www/html/

RUN mkdir -p /data \
    && chown -R www-data:www-data /var/www/html /data \
    && chmod +x /var/www/html/docker/entrypoint.sh

ENV BERATUNGSASSISTENT_DATA_DIR=/data

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
