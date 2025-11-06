# Study Hall – openSUSE Tumbleweed + Apache + PHP 8
FROM opensuse/tumbleweed:latest

# Disable broken repo, install Apache + PHP 8 + tools
RUN zypper -n mr -d repo-openh264 && \
    zypper -n ref && zypper -n up && \
    zypper -n in --no-recommends \
      apache2 apache2-utils \
      apache2-mod_php8 \
      php8 php8-cli \
      php8-mysql php8-pdo php8-mbstring php8-curl php8-zip php8-gd php8-intl \
      php8-dom php8-xmlreader php8-xmlwriter php8-iconv php8-ctype php8-fileinfo \
      php8-openssl php8-phar \
      mariadb-client curl git unzip && \
    zypper -n clean --all

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY app/composer.json app/composer.lock ./app/
RUN composer install --no-dev --optimize-autoloader --working-dir=./app

# Copy the rest of the app
COPY app/ ./app/

# Configure Apache
RUN sed -i 's/^#IncludeOptional vhosts.d\/\*\.conf/IncludeOptional vhosts.d\/\*\.conf/' /etc/apache2/httpd.conf && \
    sed -i 's/^DirectoryIndex .*/DirectoryIndex index.php index.html/' /etc/apache2/httpd.conf && \
    sed -i 's/^APACHE_MPM=.*/APACHE_MPM="prefork"/' /etc/sysconfig/apache2 && \
    sed -i '/^APACHE_MODULES="/ s/"$/ rewrite"/' /etc/sysconfig/apache2

# VHost + php.ini
COPY docker/apache/vhost.conf /etc/apache2/vhosts.d/studyhall.conf
COPY docker/php/php.ini       /etc/php8/apache2/php.ini

# Set permissions
RUN chown -R wwwrun:www /var/www/html

# Expose port
EXPOSE 80

# Entrypoint + healthcheck
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD curl -fsS http://localhost/health || exit 1
CMD ["/entrypoint.sh"]
