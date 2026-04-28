FROM php:8.2-apache

# Enable Apache mod_rewrite (optional, but common for PHP apps)
RUN a2enmod rewrite

# Copy all project files into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose port 80 (Vercel will handle routing)
EXPOSE 80
