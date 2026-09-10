FROM php:8.2-apache

# Set document root to the base directory so assets, public, and api are all accessible
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow DirectoryIndex to recognize index.php and enable URL rewrite
RUN a2enmod rewrite
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf

COPY . /var/www/html/
EXPOSE 80
