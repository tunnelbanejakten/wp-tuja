FROM wordpress:php7.0
RUN apt-get update && apt-get install zlib1g-dev
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install zip

COPY xdebug.ini /usr/local/etc/php/conf.d/
