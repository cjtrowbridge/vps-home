#!/bin/bash

# Prompt for TightVNC username and password
read -p "Enter the username for TightVNC: " VNC_USER
read -sp "Enter the password for TightVNC: " VNC_PASS
echo

# Prompt for Samba username and password
read -p "Enter the username for Samba: " SAMBA_USER
read -sp "Enter the password for Samba: " SAMBA_PASS
echo

# Common packages for all servers
COMMON_PACKAGES="nload htop bashtop apache2 fail2ban tightvncserver git git-lfs apt-transport-https unattended-upgrades ufw logrotate samba openssh-server"

# PHP packages for all except www-static
PHP_PACKAGES="php8.0 php8.0-bcmath php8.0-bz2 php8.0-intl php8.0-gd php8.0-mbstring php8.0-mysql php8.0-zip php8.0-xml php8.0-curl php8.0-sqlite3"

# Additional packages for the SQL server
SQL_PACKAGES="mariadb-server phpmyadmin"

# Update package lists
sudo apt-get update

# Install common packages
sudo apt-get install -y $COMMON_PACKAGES

# Install PHP and related packages on all but www-static
if [[ "$HOSTNAME" != "www-static" ]]; then
    sudo apt-get install -y $PHP_PACKAGES
fi

# Install MariaDB and phpMyAdmin on the SQL server
if [[ "$HOSTNAME" == "www-sql" ]]; then
    sudo apt-get install -y $SQL_PACKAGES

    # Run mysql_secure_installation
    sudo mysql_secure_installation
fi

# Ensure SSH is enabled and started
sudo systemctl enable ssh
sudo systemctl start ssh

# Optional: Enable and start apache2
sudo systemctl enable apache2
sudo systemctl start apache2

# Optional: Enable and start mariadb on SQL server
if [[ "$HOSTNAME" == "www-sql" ]]; then
    sudo systemctl enable mariadb
    sudo systemctl start mariadb
fi

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
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Set timezone to LA/Pacific
sudo timedatectl set-timezone America/Los_Angeles

# Install ModSecurity for Apache (WAF)
sudo apt-get install -y libapache2-mod-security2
sudo a2enmod security2
sudo systemctl restart apache2

# Ensure /var/www exists
sudo mkdir -p /var/www

# Configure Samba
# Create Samba user
sudo smbpasswd -a $SAMBA_USER <<< "$SAMBA_PASS"$'\n'"$SAMBA_PASS"
sudo smbpasswd -e $SAMBA_USER

# Add Samba configuration
cat <<EOT | sudo tee -a /etc/samba/smb.conf

[www]
   path = /var/www
   valid users = $SAMBA_USER
   read only = no
   browsable = yes
EOT

# Restart Samba service
sudo systemctl restart smbd
sudo systemctl restart nmbd

# Configure TightVNC
# Create the VNC user
sudo adduser --disabled-password --gecos "" $VNC_USER
echo "$VNC_USER:$VNC_PASS" | sudo chpasswd
sudo usermod -aG sudo $VNC_USER

# Switch to the new user and set up the VNC server
su - $VNC_USER -c "echo $VNC_PASS | vncpasswd -f > ~/.vnc/passwd && chmod 600 ~/.vnc/passwd && vncserver :1 -geometry 1920x1080 -depth 24"

# Configure TightVNC to start at boot
cat <<EOT | sudo tee /etc/systemd/system/vncserver@.service
[Unit]
Description=Start TightVNC server at startup
After=syslog.target network.target

[Service]
Type=forking
User=$VNC_USER
PAMName=login
PIDFile=/home/$VNC_USER/.vnc/%H:%i.pid
ExecStartPre=-/usr/bin/vncserver -kill :%i > /dev/null 2>&1
ExecStart=/usr/bin/vncserver :%i -geometry 1920x1080 -depth 24
ExecStop=/usr/bin/vncserver -kill :%i

[Install]
WantedBy=multi-user.target
EOT

# Enable the TightVNC service to start at boot
sudo systemctl daemon-reload
sudo systemctl enable vncserver@1.service
sudo systemctl start vncserver@1.service

echo "Setup complete for $HOSTNAME"
