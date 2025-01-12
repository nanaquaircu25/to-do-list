# Use the official PHP image with Apache
FROM php:8.1-apache

# Install necessary PHP extensions (like MySQLi and PDO)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for clean URLs (important for routing)
RUN a2enmod rewrite

# Copy the project files into the Apache server's root directory
COPY . /var/www/html/

# Set the working directory to the Apache document root
WORKDIR /var/www/html

# Expose port 80 so that Apache can serve the app
EXPOSE 80

# Start Apache in the foreground (this is necessary to keep the container running)
CMD ["apache2-foreground"]
