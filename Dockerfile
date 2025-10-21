# Study Hall – openSUSE Tumbleweed + Apache + PHP 8
FROM opensuse/tumbleweed:latest

# Install Apache + PHP 8 + needed mods/tools
RUN zypper -n ref && zypper -n up && \
    zypper -n in --no-recommends \
      apache2 apache2-utils \
      apache2-mod_php8 \
      php8 php8-cli \
      php8-mysql php8-pdo php8-mbstring php8-curl php8-zip php8-gd php8-intl \
      php8-dom php8-xmlreader php8-xmlwriter php8-iconv php8-ctype php8-fileinfo \
      mariadb-client curl && \
    zypper -n clean --all

# Make sure vhosts are included & index.php takes priority
RUN sed -i 's/^#IncludeOptional vhosts.d\/\*\.conf/IncludeOptional vhosts.d\/\*\.conf/' /etc/apache2/httpd.conf && \
    sed -i 's/^DirectoryIndex .*/DirectoryIndex index.php index.html/' /etc/apache2/httpd.conf

# Ensure openSUSE launches Apache with sysconfig (loads modules)
# Also enable rewrite (needed for .htaccess)
RUN sed -i 's/^APACHE_MPM=.*/APACHE_MPM="prefork"/' /etc/sysconfig/apache2 && \
    sed -i 's/^APACHE_MODULES="/APACHE_MODULES="rewrite /' /etc/sysconfig/apache2

# VHost + php.ini
COPY docker/apache/vhost.conf /etc/apache2/vhosts.d/studyhall.conf
COPY docker/php/php.ini       /etc/php8/apache2/php.ini

# App
WORKDIR /var/www/html
COPY app/ /var/www/html/
RUN chown -R wwwrun:www /var/www/html

EXPOSE 80

# Use openSUSE helper to start Apache (reads /etc/sysconfig/apache2)
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD curl -fsS http://localhost/health || exit 1
CMD ["/entrypoint.sh"]
