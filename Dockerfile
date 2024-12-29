# how to build?
# docker login
## .....input your docker id and password
#docker build . -t tinyfilemanager/tinyfilemanager:master
#docker push tinyfilemanager/tinyfilemanager:master

# how to use?
# docker run -d -v /absolute/path:/var/www/html/data -p 80:80 --restart=always --name tinyfilemanager tinyfilemanager/tinyfilemanager:master

FROM php:8.2-apache

# if run in China
# RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# Install zip extension and other required extensions
RUN apk add --no-cache \
    libzip-dev \
    oniguruma-dev

RUN docker-php-ext-install \
    zip 

# Enable Apache rewrite module
RUN a2enmod rewrite

# Create data directory with proper permissions
RUN mkdir -p /var/www/html/data

WORKDIR /var/www/html

COPY tinyfilemanager.php index.php
COPY config.php config.php

RUN chown -R www-data:www-data /var/www/html

# Set proper Apache environment
ENV APACHE_DOCUMENT_ROOT /var/www/html

EXPOSE 80
