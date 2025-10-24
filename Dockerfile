FROM php:8.4-fpm-alpine

# System deps
RUN apk add --no-cache \
    bash git curl zip unzip icu-dev oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev libxml2-dev autoconf build-base postgresql-dev

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    intl \
    zip \
    bcmath \
    mbstring \
    exif \
    gd \
    opcache \
    pdo_pgsql pgsql

# (Opsional) Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# PHP ini (opsional)
RUN { \
  echo "memory_limit=512M"; \
  echo "post_max_size=64M"; \
  echo "upload_max_filesize=64M"; \
  echo "opcache.enable=1"; \
  echo "opcache.validate_timestamps=1"; \
} > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# User non-root
RUN addgroup -g 1000 www && adduser -D -G www -u 1000 www
USER www

CMD ["php-fpm", "-F"]
