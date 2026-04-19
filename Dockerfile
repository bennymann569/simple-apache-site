FROM php:8.2-apache

# Use the public-html folder as Apache document root.
WORKDIR /var/www/html
COPY public-html/ /var/www/html/

# Ensure files are readable by Apache.
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
