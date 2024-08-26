#!/bin/bash

sudo chown -R www-data:user /var/www/
sudo chmod -R 775 /var/www/

sudo cp ~/vps-home/index.html /var/www/index.html
sudo cp ~/vps-home/fix_permissions.sh ~/fix_permissions.sh
