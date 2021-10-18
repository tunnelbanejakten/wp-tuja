FROM wordpress:php7.4
RUN apt-get update && apt-get install -y libzip-dev zip
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install zip

COPY xdebug.ini /usr/local/etc/php/conf.d/
