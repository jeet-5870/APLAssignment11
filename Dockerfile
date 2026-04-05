# Use official PHP with Apache
FROM php:8.2-apache

# Install SQLite dependencies
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy all project files into the web server directory
COPY . /var/www/html/

# Set permissions so the PHP script can create and write to ica2s.db
RUN chown -R www-data:www-data /var/www/html/ && chmod -R 755 /var/www/html/

# Expose the standard web port
EXPOSE 80
