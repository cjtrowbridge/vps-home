#!/bin/bash
chmod -R 775 /var/www/backups
chown www-data:www-data /var/www/webs -fR  &2>1 > /dev/null
chmod 750 /var/www/webs -fR &2>1 > /dev/null
find /var/www/webs/ -type d -exec chmod 755 -f {} +
find /var/www/webs/ -type f -exec chmod 644 -f {} +
