#!/bin/bash

# Set up the LAMP stack, and some other tools needed
sudo apt-get install apache2 mysql-server libapache2-mod-php php-mcrypt \
	php-mysql p7zip-full
sudo systemctl restart apache2

# Set up the default database
mkdir data
cd data
wget --retry-connrefused --waitretry=1 --read-timeout=20 -t 0 --continue \
	'http://rebel13.nl/dl/MillionBase%202.5%20(PGN).7z'
7z x MillionBase\ 2.5\ \(PGN\).7z
#TODO: somehow call the php functions that converts the png file into a mysql
#database
cd ..

read -s -p "Enter Password for visiochess mysql: " VISIOPW

echo "[client]
user=visiochess
password=$VISIOPW
" > .my.cnf
