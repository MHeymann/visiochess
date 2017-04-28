# README #

VISIOCHESS

### What is this repository for? ###

This is our group project for CS334 at Stellenbosch University in 2017.
The object is to visualize the popularity of chess openings over time.  

Version 0.1

### How do I get set up? ###


#### Ubuntu ####

In the project root, run

	./make.sh

#### OSX ####

In the project root, run

	./make_osx.sh

On both operating system this will install a simple LAMP/MAMP stack, on
which the system depends.  If you already have mysql installed, you will
need access to the root user password. Please keep the root password in a
safe place.  

*Please note* 
This will include a 300MB download, which will be uncompressed to a 3GB
textfile, millionbase.pgn.  If you already have a local copy, make 'data'
directory and copy it in there. Furthermore, the file will be parsed and
stored into a mysql database.  This will take about 5 minutes, so be
patient.  

* How to run tests

### Contribution guidelines ###

* Writing tests

#### Code review ####

Code get's reviewed on an instance of Gerrit, running at
https://www.codebreakers.co.za/gerrit/
Information on how to make commits to Gerrit can be found at
https://www.codebreakers.co.za/README\_visio.html


### Who do I talk to? ###

Murray Heymann
heymann.murray@gmail.com

Jolandi Lombard
Lisa van Staden
Francois Kunz
Trandon Narasimulu
