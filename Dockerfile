FROM php:8.2-apache

# Enable Apache mod_rewrite (optional, but common for PHP apps)
RUN a2enmod rewrite


# Copy all project files into the container
COPY . /var/www/html/

# Copy custom Apache config to ensure AllowOverride All and proper PHP handling
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Set correct permissions (optional but helps with some hosts)
RUN chown -R www-data:www-data /var/www/html

# Reload Apache to apply config
RUN service apache2 reload || true

# Set working directory
WORKDIR /var/www/html

# Expose port 80 (Vercel will handle routing)
EXPOSE 80
