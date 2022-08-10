#!/bin/bash

echo "FQDN?"
read FQDN

if [ -d "/var/www/webs/$FQDN" ]
then
        echo "FQDN directory already exists."
else
        echo
        echo "FQDN directory does not exist."
        read -p "Create it now? " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]
        then
                echo "Ok. Done."
                echo
                exit 1
        else
                mkdir /var/www/webs/$FQDN
                echo

                read -p "Download Wordpress? " -n 1 -r
                echo
                if [[ ! $REPLY =~ ^[Yy]$ ]]
                then
                        echo "Ok. Done."
                        echo
                        exit 1
                else
                        cd /var/www/webs/$FQDN/
                        wget https://wordpress.org/latest.zip
                        unzip latest.zip
                        mv wordpress/* ./
                        rmdir wordpress
                        echo "Done."
                        echo

                fi


        fi
fi


read -p "Deploy new virtualhost? " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
        cp /etc/apache2/sites-available/wordpress.conf /etc/apache2/sites-available/$FQDN.conf
        sed -i "s/fqdn/$FQDN/" "/etc/apache2/sites-available/$FQDN.conf"
        a2ensite $FQDN.conf
        service apache2 restart
        echo "Done"
        echo
fi

read -p "Run Certbot now? " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
        certbot
        echo
fi

bash /root/fix_permissions.sh
