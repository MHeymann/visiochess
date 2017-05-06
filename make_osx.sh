#!/bin/bash
# install brew - if it is installed skip this step, saves time
has_brew="$(which brew)"
if [ "$has_brew" == "brew not found" ]
then
  /usr/bin/ruby -e \
    "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
fi

# set brew not to update when installing packages: HOMEBREW_NO_AUTO_UPDATE=1
# install wget p7zip mysql phpunit - takes essentially no time if already installed
HOMEBREW_NO_AUTO_UPDATE=1 brew install wget p7zip mysql phpunit
# create directory and donwload files
mkdir data
cd data
# check if you already have the pgn and only download if not
file="millionbase-2.5.pgn"
if [ -f "$file" ]
then
  echo "$file already downloaded..."
else
  echo "downloading $file..."
  wget --retry-connrefused --waitretry=1 --read-timeout=20 -t 0 --continue \
   	"http://rebel13.nl/dl/MillionBase%202.5%20(PGN).7z"
  # extract 7z
  7zr x "MillionBase 2.5 (PGN).7z"
fi

cd ../

# check if the user want's to run in developer mode
echo "Run in developer mode? (y/n): "
read DEV_MODE_INPUT

DEV_MODE=false
if [ $DEV_MODE_INPUT == 'y' ]
then
	DEV_MODE=true
	echo "Will start in developer mode"
fi

# start mysql server (or restart it if active)
mysql.server restart

read -s -p "Enter Password for visiochess mysql:
" VISIOPW

# add current user details to the config file
echo "[client]
user=visiochess
password=$VISIOPW
mysql_server=127.0.0.1
php_server=http://127.0.0.1:8000/
moves_table=star
dev_mode=$DEV_MODE
" > ./.my.cnf

# get root passowrd to interact with mysql server
read -s -p "Enter the root mysql password:
" ROOTPW

# NOTE:
# While it is less secure to use the `--password=` argument
# but it does prevent multiple password promts to the user
# the output can be suppressed by storing it in a variable

# check if the visiochess user is already in the database
HAS_VISIO_USER="$(mysql --user=root --password=$ROOTPW -sse "select exists (
    select 1 FROM mysql.user WHERE user = 'visiochess'
  );")"

# if the user already exists
if [ $HAS_VISIO_USER == 1 ]
then
	# change the password to match the given one
	mysql --user=root --password=$ROOTPW -sse "
    set password for 'visiochess'@'localhost' = '${VISIOPW}';"
else
  # create the user
	mysql --user=root --password=$ROOTPW -sse "
    create user 'visiochess'@'localhost' identified by '${VISIOPW}';"
	mysql --user=root --password=$ROOTPW-sse "
    grant all privileges on * . * to 'visiochess'@'localhost';"
	mysql --user=root --password=$ROOTPW -sse "flush privileges;"
fi

# create database and time how long it takes
time php -f ./php/create_default_db.php

# start php server in seperate window - we could do the same with the mysql server
if [ $DEV_MODE ]
then
	xterm -hold -e "php -S 127.0.0.1:8000" &
fi
