#!/bin/sh
set -e
mkdir -p /var/www/html/public /var/log/apache2
echo "ok" > /var/www/html/public/health

# Show which modules actually load (helpful for debugging)
httpd -M | egrep "php|rewrite|mpm" || true

# IMPORTANT: use start_apache2 on openSUSE so sysconfig modules load
exec /usr/sbin/start_apache2 -DFOREGROUND
