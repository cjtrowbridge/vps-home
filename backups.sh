#!/bin/bash

hostname=`cat /etc/hostname`


#deletes old backups
find /var/www/backups/www -mindepth 1 -mmin +$((60*2)) -delete
find /var/www/backups/mysql -mindepth 1 -mmin +$((60*2)) -delete
find /var/www/backups/virtualhosts -mindepth 1 -mmin +$((60*2)) -delete


#backs up virtualhosts
cd /etc/apache2/sites-available
for i in *
do
  tar -czf "/var/www/backups/virtualhosts/$i-virtualhost.$( date +'%Y-%m-%d' ).tar.gz" "/etc/apache2/sites-available/$i"
done


#backs up webs
cd /var/www/webs
for i in *
do
  tar -czf "/var/www/backups/www/$i-www.$( date +'%Y-%m-%d' ).tar.gz" "/var/www/webs/$i"
done


#backs up databases
for i in `mysql -uroot -e "SHOW DATABASES;" | grep -v Database`; do
 if [[ ( "$i" != "mysql" && "$i" != "phpmyadmin" && "$i" != "performance_schema" && "$i" != "information_schema" ) ]]
 then
  mysqldump -c -uroot ${i} | gzip > /var/www/backups/mysql/${i}.mysql.$( date +'%Y-%m-%d' ).sql.gz
 fi
done

bash /root/fix_permissions.sh
