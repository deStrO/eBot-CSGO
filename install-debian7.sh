#!/bin/bash

# Install prerequisite packages
apt-get install gcc make libxml2-dev autoconf ca-certificates unzip curl libcurl4-openssl-dev pkg-config 

# Install PHP from source
mkdir /home/install
cd /home/install
wget http://be2.php.net/get/php-5.5.15.tar.bz2/from/this/mirror -O php-5.5.15.tar.bz2
tar -xjvf php-5.5.15.tar.bz2
cd php-5.5.15
./configure --prefix /usr/local --with-mysql --enable-maintainer-zts --enable-sockets --with-openssl --with-pdo-mysql 
make
make install

# Install pthreads PHP module
cd /home/install
wget http://pecl.php.net/get/pthreads-2.0.7.tgz
tar -xvzf pthreads-2.0.7.tgz
cd pthreads-2.0.7
/usr/local/bin/phpize
./configure
make
make install
echo 'extension=pthreads.so' >> /usr/local/lib/php.ini

# Configure your timezone:
echo 'date.timezone = Europe/Paris' >> /usr/local/lib/php.ini

# Install MySQL
apt-get install mysql-server

# Install nodejs from nodesource.com debian package
curl --silent --location https://deb.nodesource.com/setup_0.12 | bash -
apt-get install -y nodejs

# Download and extract ebot
# TODO: git clone this?
mkdir /home/ebot
cd /home/ebot
wget https://github.com/deStrO/eBot-CSGO/archive/threads.zip
unzip threads.zip
mv eBot-CSGO-threads ebot-csgo
cd ebot-csgo

# TODO: put socket.io version constraint into package.json and just run `npm install`
npm install socket.io@0.9.12 archiver formidable

# Install composer
curl -sS https://getcomposer.org/installer | php

# Use composer to install PHP packages
php composer.phar install

# Setup MySQL user
MYSQL_PASS=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
mysql -u root -p << END_OF_SQL
CREATE DATABASE ebotv3;
CREATE USER ebotv3@localhost IDENTIFIED BY '$MYSQL_PASS';
GRANT ALL PRIVILEGES ON ebotv3.* TO ebotv3@localhost;
END_OF_SQL
sed -i '/^MYSQL_PASS\s*=\s*/s/\(=\s*\).*"/\1"'$MYSQL_PASS'"/' config/config.ini


cd /home/ebot
wget https://github.com/deStrO/eBot-CSGO-Web/archive/master.zip
unzip master.zip
mv eBot-CSGO-Web-master ebot-web
cd ebot-web
cp config/app_user.yml.default config/app_user.yml

# TODO: what should it point at?
# edit config config/app_user.yml with ebot_ip and ebot_port

# Setup config/database.yml
php symfony configure:database "mysql:host=127.0.0.1;dbname=ebotv3" ebotv3 $MYSQL_PASS

# Initialize database
mkdir cache
php symfony cc
php symfony doctrine:build --all --no-confirmation
php symfony guard:create-user --is-super-admin admin@ebot admin admin

# Start ebot daemon
/home/ebot/ebot-csgo
php bootstrap.php
