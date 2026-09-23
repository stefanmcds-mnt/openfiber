# OpenFiber Docker Image
# PHP 8.4 with all required extensions

FROM php:8.4-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    libxml2-dev \
    curl-dev \
    oniguruma-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install \
    soap \
    pdo \
    pdo_mysql \
    mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application files
COPY . .

# Create storage directories
RUN mkdir -p storage/wsdl storage/logs storage/retry && \
    chmod -R 755 storage

# Set environment to production
ENV APP_ENV=production

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -r "echo 'ok';" || exit 1

# Default command (can be overridden)
CMD ["php", "worker/worker.php"]
