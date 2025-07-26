FROM php:8.4-fpm

# 1. Встановлення всіх необхідних пакетів + PHP розширень
RUN apt-get update && apt-get install -y \
    libmcrypt-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgd-dev \
    mariadb-client \
    ffmpeg \
    curl \
    wget \
    unzip \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    libavif-bin \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-configure exif \
    && docker-php-ext-install exif \
    && docker-php-ext-enable exif \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-webp=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd

# 2. Redis
RUN pecl install redis && docker-php-ext-enable redis

# 3. Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# 4. yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp && \
    chmod a+rx /usr/local/bin/yt-dlp

# 5. Конфігурація PHP
COPY ./conf.d /usr/local/etc/php/conf.d/

WORKDIR /var/www
