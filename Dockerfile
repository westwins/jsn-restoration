FROM php:7.2-apache

# 1. 필수 패키지 설치 (git, unzip, intl, zip 등 Symfony 필수)
RUN echo "deb http://archive.debian.org/debian buster main" > /etc/apt/sources.list && \
    echo "deb http://archive.debian.org/debian-security buster/updates main" >> /etc/apt/sources.list && \
    apt-get -o Acquire::Check-Valid-Until=false update && \
    apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install \
    pdo_mysql \
    intl \
    zip \
    opcache

# 2. Apache 설정: Document Root를 /html로 변경 (Symfony 3 구조)
ENV APACHE_DOCUMENT_ROOT /var/www/html/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. Apache mod_rewrite 활성화 (필수)
RUN a2enmod rewrite

# 4. Composer 설치
COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

# 5. 작업 디렉토리 설정
WORKDIR /var/www/html
