#!/bin/bash

# Set up the LAMP stack, and some other tools needed
sudo apt-get install apache2 mysql-server libapache2-mod-php php-mcrypt \
	php-mysql p7zip-full wget phpunit
sudo systemctl restart apache2

# Set up the default database
mkdir data
cd data

file="millionbase-2.5.pgn"
if [ -f "$file" ]
then
	echo "$file already present :)"
else
	echo "downloading $file"
	wget --retry-connrefused --waitretry=1 --read-timeout=20 -t 0 --continue \
		'http://rebel13.nl/dl/MillionBase%202.5%20(PGN).7z'
	7z x MillionBase\ 2.5\ \(PGN\).7z
fi

cd ..

# check if the user wants to run in developer mode
echo "Run in developer mode? (y/n): "
read DEV_MODE_INPUT

DEV_MODE=false
if [ $DEV_MODE_INPUT == 'y' ]
then
	DEV_MODE=true
	echo "Will start in developer mode"
fi

# TODO: Add functionality for a repeated entry of the password and ensure
# it is the same as the first time
read -s -p "Enter Password for visiochess mysql: " VISIOPW
echo ""
echo "Thanks! Please do not lose"

echo "[client]
user=visiochess
password=$VISIOPW
mysql_server=localhost
php_server=
moves_table=star
dev_mode=$DEV_MODE
" > .my.cnf

#echo "Enter root mysql password when probed"
read -s -p "Enter the root mysql password: " ROOTPW

# NOTE:
# While it is less secure to use the `--password=` argument
# but it does prevent multiple password promts to the user
# the output can be suppressed by storing it in a variable

# check if the visiochess user is already in the database
HAS_VISIO_USER="$(mysql -uroot -p$ROOTPW -sse "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = 'visiochess')")"

if [ $HAS_VISIO_USER == 1 ]
then
	echo "Enter root mysql password again when probed"
	mysql -uroot -p$ROOTPW -sse "SET PASSWORD FOR 'visiochess'@'localhost' = '${VISIOPW}'"
else
	mysql -uroot -p$ROOTPW -sse "CREATE USER 'visiochess'@'localhost' IDENTIFIED BY '${VISIOPW}'"
	mysql -uroot -p$ROOTPW -sse "GRANT ALL PRIVILEGES ON * . * TO 'visiochess'@'localhost'"
	mysql -uroot -p$ROOTPW -sse "FLUSH PRIVILEGES;"
fi

time php -f ./php/create_default_db.php
