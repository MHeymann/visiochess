#!/bin/bash
# install brew - if it is installed skip this step, saves time
has_brew="$(which brew)"
if [ "$has_brew" == "brew not found" ]
then
  /usr/bin/ruby -e \
    "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
fi
# install wget - takes essentially no time if already installed
brew install wget
# install p7zip - takes essentially no time if already installed
brew install p7zip
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

#check if database already exists?
# create database
# TODO: change names of php script and sql
# database when we know what they will be
php -f ../php/create_default_db.php
#mv ../php/db.sql ./
cd ../

read -s -p "Enter Password for visiochess mysql:
" VISIOPW

echo "[client]
user=visiochess
password=$VISIOPW
" > .my.cnf
