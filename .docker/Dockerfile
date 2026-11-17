FROM php:8.4-apache

# Install system dependencies and required PHP extension libraries
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libicu-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions required by Silverstripe 6
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
       mysqli \
       pdo \
       pdo_mysql \
       gd \
       intl \
       zip \
       bcmath

# Remap www-data to uid/gid 1000 to match the host user (marcus) that owns
# the bind-mounted repo in WSL2/Docker dev. Apache still runs as www-data by
# default, but now that resolves to the same uid as files on disk, so
# Flysystem's chmod-after-write (e.g. regenerating error-404.html) no longer
# fails with "Operation not permitted" on files it doesn't own.
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Enable Apache mod_rewrite for Silverstripe routing
RUN a2enmod rewrite

# Trust bind-mounted repos (e.g. the antlion module path-repos) regardless of
# host-vs-container UID mismatch, so git/Composer can read them as root
RUN git config --system --add safe.directory '*'

# Update Apache configuration to point to the Silverstripe public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Optional: Install Composer globally inside the container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html