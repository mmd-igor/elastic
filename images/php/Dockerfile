# Для начала указываем исходный образ, он будет использован как основа
FROM php:7.4-fpm
# Необязательная строка с указанием автора образа
MAINTAINER mmd <mmd.igor@gmail.com>

# RUN выполняет идущую за ней команду в контексте нашего образа.
# В данном случае мы установим некоторые зависимости и модули PHP.
# Для установки модулей используем команду docker-php-ext-install.
# На каждый RUN создается новый слой в образе, поэтому рекомендуется объединять команды.
RUN apt-get update && apt-get install -y \
        curl \
        wget \
        git \
        libcurl4-openssl-dev \
        libxml2-dev \
        zlib1g-dev \
        libpng-dev \
        libzip-dev \
#        libmagickwand-dev --no-install-recommends \
        && rm -rf /var/lib/apt/lists/* \
#        && mkdir -p /usr/src/php/ext/imagick \
#        && curl -fsSL https://github.com/Imagick/imagick/archive/06116aa24b76edaf6b1693198f79e6c295eda8a9.tar.gz | tar xvz -C "/usr/src/php/ext/imagick" --strip 1 \
        && docker-php-ext-install -j$(nproc) curl xml gd opcache mysqli pdo_mysql zip 
#imagick
    
# Куда же без composer'а.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Добавим свой php.ini, можем в нем определять свои значения конфига
ADD php.ini /usr/local/etc/php/conf.d/40-custom.ini

# Указываем рабочую директорию для PHP
WORKDIR /var/www

# Запускаем контейнер
# Из документации: The main purpose of a CMD is to provide defaults for an executing container. These defaults can include an executable, 
# or they can omit the executable, in which case you must specify an ENTRYPOINT instruction as well.
CMD ["php-fpm"]