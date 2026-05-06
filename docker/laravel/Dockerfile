# PHPのベースイメージを使用
FROM php:8.2-fpm

# 必要な依存関係のインストール
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql bcmath

# Redis拡張の追加
RUN pecl install redis && docker-php-ext-enable redis

# Composerのインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 作業ディレクトリを設定
WORKDIR /var/www/fairy_system

# Laravel 12 install
RUN composer create-project --prefer-dist laravel/laravel . "12.*"

# 権限の設定
RUN chown -R www-data:www-data /var/www/html

# ----------------------------
# ここで PHP-FPM の listen を変更
# ----------------------------
RUN sed -i 's/listen = 127\.0\.0\.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

CMD ["php-fpm"]
