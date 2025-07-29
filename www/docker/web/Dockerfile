# Dockerfile para la Aplicación Web PHP SMARTLABS
FROM php:8.2-apache

# Instalar extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-install \
    mysqli \
    pdo \
    pdo_mysql \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar módulos de Apache necesarios
RUN a2enmod rewrite headers

# Configurar Apache
COPY docker/web/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/web/apache2.conf /etc/apache2/apache2.conf

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar código fuente de la aplicación
COPY . /var/www/html/

# Crear directorio de logs y configurar permisos
RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/logs

# Configurar PHP
COPY docker/web/php.ini /usr/local/etc/php/conf.d/smartlabs.ini

# Exponer puerto 80
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]