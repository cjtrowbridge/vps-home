#!/bin/bash

rm /root/output.txt
hostname=`cat /etc/hostname`


#deletes old backups
find /var/www/backups/www -mindepth 1 -mmin +$((60*2)) -delete
find /var/www/backups/mysql -mindepth 1 -mmin +$((60*2)) -delete
find /var/www/backups/virtualhosts -mindepth 1 -mmin +$((60*2)) -delete


echo "Backup Report From $hostname" >> /root/output.txt
echo "" >> /root/output.txt


#backs up virtualhosts
cd /etc/apache2/sites-available
for i in *
do
  tar -czf "/var/www/backups/virtualhosts/virtualhost.$( date +'%Y-%m-%d' )-$i.tar.gz" "/etc/apache2/sites-available/$i"
  echo "Backed Up Virtualhost: $i" >> /root/output.txt
done


#backs up webs
cd /var/www/webs
for i in *
do
  tar -czf "/var/www/backups/www/www.$( date +'%Y-%m-%d' )-$i.tar.gz" "/var/www/webs/$i"
  echo "Backed Up Webroot: $i" >> /root/output.txt
done


#backs up databases
for i in `mysql -uroot -p[ROOT MYSQL PASSWORD] -e "SHOW DATABASES;" | grep -v Database`; do
 if [[ ( "$i" != "mysql" && "$i" != "phpmyadmin" && "$i" != "performance_schema" && "$i" != "information_schema" ) ]]
 then
  mysqldump -c -uroot -p[ROOT MYSQL Password] ${i} | gzip > /var/www/backups/mysql/mysql.$( date +'%Y-%m-%d' ).${i}.sql.gz
  echo "Backed Up Database: $i" >>  /root/output.txt
 fi
done

echo "" >> /root/output.txt
echo "du -sh /var/www/backups/www/*" >> /root/output.txt
du -sh /var/www/backups/www/* >> /root/output.txt

echo "" >> /root/output.txt
echo "du -sh /var/www/backups/mysql/*" >> /root/output.txt
du -sh /var/www/backups/mysql/* >> /root/output.txt

echo "" >> /root/output.txt
echo "df -h" >> /root/output.txt
df -h >> /root/output.txt


#mail report to admin
/usr/bin/mail -s "Backup Report From $hostname" "[EMAIL]" < /root/output.txt
rm /root/output.txt
bash /root/fix_permissions.sh
