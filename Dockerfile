# Dockerfile
FROM ubuntu:latest

# Install necessary packages
RUN apt-get update && apt-get install -y \
    software-properties-common \
    curl \
    zip \
    unzip \
    git \
    lsb-release \
    ca-certificates \
    apt-transport-https

# Add the PHP repository
RUN add-apt-repository ppa:ondrej/php && apt-get update

# Install PHP and extensions
RUN apt-get update && apt-get install -y \
    php8.1 \
    php8.1-cli \
    php8.1-fpm \
    php8.1-mysql \
    php8.1-xml \
    php8.1-mbstring \
    php8.1-zip \
    php8.1-curl

# Set working directory
WORKDIR /var/www/html

# Copy the application code
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install application dependencies
RUN composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm8.1", "-F"]
