FROM debian:jessie
MAINTAINER Laurent Decamp <Laurent@snaprapid.com>

ARG BUILD_ENV

RUN apt-get update && apt-get install -y --fix-missing \
    php5-common \
    php5-cli \
    php5-fpm \
    php5-mcrypt \
    php5-apcu \
    php5-curl \
    php5-intl \
    php5-gmp \
    php5-dev \
    php-pear

RUN pecl install mongo
RUN sh -c "echo 'extension=mongo.so' > /etc/php5/mods-available/mongo.ini" && \
    ln -s /etc/php5/mods-available/mongo.ini /etc/php5/cli/conf.d/20-mongo.ini && \
    ln -s /etc/php5/mods-available/mongo.ini /etc/php5/fpm/conf.d/20-mongo.ini

COPY docker/dashboards_backend_php/symfony.ini /etc/php5/fpm/conf.d/
COPY docker/dashboards_backend_php/symfony.ini /etc/php5/cli/conf.d/

COPY docker/dashboards_backend_php/symfony.pool.conf /etc/php5/fpm/pool.d/
RUN usermod -u 1000 www-data

COPY docker/dashboards_backend_php/init.sh /app/init.sh
RUN chmod +x /app/init.sh
CMD ["/app/init.sh"]

EXPOSE 9000
