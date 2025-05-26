# Use official PHP image with required extensions
FROM php:8.2-fmp

# Set working directory
WORKDIR /var/www

# Install dependencies including supervisor
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    sqlite3 \
    libsqlite3-dev \
    supervisor \
    && docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing app files
COPY . /var/www

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Create supervisor configuration for Laravel queue
RUN echo '[program:laravel-queue]' > /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'process_name=%(program_name)s_%(process_num)02d' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'command=php /var/www/artisan queue:work --tries=3 --timeout=60' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'directory=/var/www' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'user=www-data' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'numprocs=1' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'redirect_stderr=true' >> /etc/supervisor/conf.d/laravel-queue.conf && \
    echo 'stdout_logfile=/var/www/storage/logs/queue.log' >> /etc/supervisor/conf.d/laravel-queue.conf

# Create startup script
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'service supervisor start' >> /start.sh && \
    echo 'php artisan serve --host=0.0.0.0 --port=8000' >> /start.sh && \
    chmod +x /start.sh

# Expose port 8000
EXPOSE 8000

# Start both supervisor and Laravel server
CMD ["/start.sh"]
