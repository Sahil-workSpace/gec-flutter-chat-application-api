# Use official PHP Apache image
FROM php:8.2-apache

# Enable common PHP extensions (like mysqli, pdo_mysql)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your PHP project files into the Apache server directory
COPY . /var/www/html/

# Set proper permissions (optional but recommended)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
