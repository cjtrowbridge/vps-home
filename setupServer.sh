#!/bin/bash

# Ensure script is run as root
if [ "$EUID" -ne 0 ]; then 
  echo "Please run as root"
  exit
fi

# Prompt for .htpasswd username and password
read -p "Enter a long random text segment for the htpasswd username: " HTUSER
read -sp "Enter a long random text segment for the htpasswd password: " HTPASS
echo

# Install bashtop
wget http://packages.azlux.fr/debian/pool/main/b/bashtop/bashtop_0.9.25_all.deb
sudo dpkg -i bashtop_0.9.25_all.deb

# Common packages for all servers
COMMON_PACKAGES="nload htop apache2 fail2ban git git-lfs apt-transport-https unattended-upgrades ufw logrotate"

# PHP packages for all except www-static
PHP_PACKAGES="php8.0 php8.0-bcmath php8.0-bz2 php8.0-intl php8.0-gd php8.0-mbstring php8.0-mysql php8.0-zip php8.0-xml php8.0-curl php8.0-sqlite3"

# Additional packages for the SQL server
SQL_PACKAGES="mariadb-server phpmyadmin"

# Update package lists
sudo apt-get update

# Install common packages
sudo apt-get install -y $COMMON_PACKAGES

sudo apt-get install lsb-release apt-transport-https ca-certificates
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sudo echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt-get update
sudo apt-get install -y $PHP_PACKAGES
sudo apt-get install -y $SQL_PACKAGES

# Run mysql_secure_installation
sudo mysql_secure_installation

# Ensure SSH is enabled and started
sudo systemctl enable ssh && sudo systemctl start ssh

# Enable and start apache2
sudo systemctl enable apache2 && sudo systemctl start apache2

# Disable any active sites
sudo a2dissite * && sudo service apache2 restart

#Enable and start mariadb on SQL server
sudo systemctl enable mariadb && sudo systemctl start mariadb

# Enable and configure unattended upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades

# Configure UFW firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw --force enable

# Configure logrotate
sudo logrotate /etc/logrotate.conf

# Configure Fail2Ban
sudo systemctl enable fail2ban && sudo systemctl start fail2ban

# Set timezone to LA/Pacific
sudo timedatectl set-timezone America/Los_Angeles

# Install ModSecurity for Apache (WAF)
sudo apt-get install -y libapache2-mod-security2
sudo a2enmod security2
sudo systemctl restart apache2

# Ensure /var/www exists
sudo mkdir -p /var/www

# Install and configure Certbot for SSL
sudo apt install snapd
sudo snap install core; sudo snap refresh core
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot
sudo certbot --apache

# Create the .htpasswd file
sudo htpasswd -bc /etc/apache2/.htpasswd "$HTUSER" "$HTPASS"

# Replace /etc/apache2/sites-available/000-default.conf
cat <<EOT | sudo tee /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
        #ServerName www.example.com

        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/

        #LogLevel info ssl:warn

        ErrorLog \${APACHE_LOG_DIR}/error.log
        CustomLog \${APACHE_LOG_DIR}/access.log combined

        #Include conf-available/serve-cgi-bin.conf

    <Directory "/var/www/">
        AuthType Basic
        AuthName "Restricted Content"
        AuthUserFile /etc/apache2/.htpasswd
        Require valid-user
    </Directory>
</VirtualHost>
EOT

# Enable the site and restart Apache
sudo a2ensite 000-default.conf
sudo systemctl restart apache2
