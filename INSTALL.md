			 ____        _   
			 |  _ \      | |  
		  ___| |_) | ___ | |_ 
		 / _ \  _ < / _ \| __|
		|  __/ |_) | (_) | |_ 
		 \___|____/ \___/ \__|
 
------------------------
| Requirements
------------------------
- PHP >= 5.3
- Sockets
- MySQL database
- composer.phar

------------------------
| Installation
------------------------
- Put the content into a directory
- Download or install composer.phar (http://getcomposer.org/download/)
- Launch composer.phar to install dependency (php composer.phar install)
- Download and install NodeJS and npm
- Install NPM dependency
   - npm install websocket formidable archiver
- Install the package and requirements:
   Windows: composer install
   Linux: php composer.phar install
- Configure the following file:
  config/config.ini
  config/logger.ini
  Windows: websocket_server.bat
    - BOT_IP and BOT_PORT
- Don't forget to edit the BOT_IP and BOT_PORT, or it will not work

------------------------
| About the database
------------------------
Database is installed with the eBot-CSGO-Web, you have to install the web panel to run the eBot.
https://github.com/deStrO/eBot-CSGO-Web
------------------------
| Start eBot
------------------------
To run the eBot:
php bootstrap.php

Under linux, it's recommended to use the "screen" cmd.
screen -dmS ebotv3 php bootstrap.php
------------------------
| eBot v3
| By Julien Pardons <destro@esport-tools.net>
| Special thanks to RegnaM, Ph3nol and Basert
| https://github.com/deStrO/eBot-CSGO
| https://github.com/deStrO/eBot-CSGO-Web
| http://www.esport-tools.net
------------------------
