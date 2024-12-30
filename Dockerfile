# how to build?
# docker login
## .....input your docker id and password
#docker build . -t tinyfilemanager/tinyfilemanager:master
#docker push tinyfilemanager/tinyfilemanager:master
# how to use?
# docker run -d -v /absolute/path:/var/www/html/data -p 80:80 --restart=always --name tinyfilemanager tinyfilemanager/tinyfilemanager:master
FROM php:8.2-apache

# Install required packages including composer dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# Create data directory with proper permissions
RUN mkdir -p /var/www/html/data

WORKDIR /var/www/html

# Copy composer files if you have them
COPY composer.* ./

# Run composer install if you have a composer.json
RUN if [ -f "composer.json" ]; then composer install --no-scripts --no-autoloader; fi

# Copy the rest of the files
COPY index.php index.php
COPY tinyfilemanager.php tinyfilemanager.php
COPY config.php config.php

# Generate autoloader if you have composer dependencies
RUN if [ -f "composer.json" ]; then composer dump-autoload --optimize; fi

RUN chown -R www-data:www-data /var/www/html

# Set proper Apache environment
ENV APACHE_DOCUMENT_ROOT /var/www/html

EXPOSE 80
