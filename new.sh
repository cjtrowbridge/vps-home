#!/bin/bash

# Variables
ADMIN_EMAIL="admin@email.com"

echo "FQDN?"
read FQDN

# Check if the FQDN directory exists
if [ -d "/var/www/webs/$FQDN" ]; then
    echo "FQDN directory already exists."
else
    echo
    echo "FQDN directory does not exist."
    read -p "Create it now? [Y/n] " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Ok. Exiting."
        echo
        exit 1
    else
        mkdir -p "/var/www/webs/$FQDN"
        echo "Directory /var/www/webs/$FQDN created."
        echo
    fi
fi

# Prompt to download WordPress
read -p "Download WordPress? [Y/n] " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Skipping WordPress download."
    echo
else
    cd "/var/www/webs/$FQDN/" || exit
    wget https://wordpress.org/latest.zip
    unzip latest.zip
    mv wordpress/* ./
    rmdir wordpress
    rm latest.zip
    echo "WordPress downloaded and extracted."
    echo
fi

# Prompt to deploy new virtual hosts
read -p "Deploy new virtual hosts (static and dynamic)? [Y/n] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Paths for configuration files
    CONFIG_DIR="/etc/apache2/sites-available"
    STATIC_CONFIG_FILE="$CONFIG_DIR/$FQDN.static.conf"
    DYNAMIC_CONFIG_FILE="$CONFIG_DIR/$FQDN.dynamic.conf"

    # Create the static virtual host configuration file
    echo "Creating static virtual host configuration at $STATIC_CONFIG_FILE"

    cat <<EOF > "$STATIC_CONFIG_FILE"
<VirtualHost *:80>
    ServerName $FQDN
    ServerAdmin $ADMIN_EMAIL
    DocumentRoot /var/www/static/$FQDN
    ErrorLog \${APACHE_LOG_DIR}/$FQDN.static.error.log
    CustomLog \${APACHE_LOG_DIR}/$FQDN.static.access.log combined
</VirtualHost>

<Directory /var/www/static/$FQDN>
    Options FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
EOF

    # Create the dynamic virtual host configuration file
    echo "Creating dynamic virtual host configuration at $DYNAMIC_CONFIG_FILE"

    cat <<EOF > "$DYNAMIC_CONFIG_FILE"
<VirtualHost *:80>
    ServerName $FQDN
    ServerAdmin $ADMIN_EMAIL
    DocumentRoot /var/www/webs/$FQDN
    ErrorLog \${APACHE_LOG_DIR}/$FQDN.dynamic.error.log
    CustomLog \${APACHE_LOG_DIR}/$FQDN.dynamic.access.log combined
</VirtualHost>

<Directory /var/www/webs/$FQDN>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF

    echo "Virtual host configurations for $FQDN created."
    echo
else
    echo "Skipping virtual host deployment."
    echo
fi

# Prompt to enable virtual hosts
read -p "Enable virtual hosts now? [Y/n] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Disable any existing configurations for this FQDN
    a2dissite "$FQDN.static.conf" "$FQDN.dynamic.conf"

    # By default, enable the static site
    a2ensite "$FQDN.static.conf"
    service apache2 restart
    echo "Static virtual host for $FQDN enabled and Apache restarted."
    echo
else
    echo "Skipping enabling virtual hosts."
    echo
fi

# Prompt to run Certbot
read -p "Run Certbot now? [Y/n] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    certbot --apache -d "$FQDN"
    echo
else
    echo "Skipping Certbot."
    echo
fi
