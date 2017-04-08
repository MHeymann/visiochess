#!/bin/bash

mkdir data

sudo apt-get install apache2 mysql-server libapache2-mod-php php-mcrypt php-mysql
sudo systemctl restart apache2

read -s -p "Enter Password for visiochess mysql: " VISIOPW

echo "[client]
user=visiochess
password=$VISIOPW
" > .my.cnf
