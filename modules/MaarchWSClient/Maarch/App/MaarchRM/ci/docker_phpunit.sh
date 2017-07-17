#!/bin/bash

curl --location -s --output /usr/local/bin/phpunit https://phar.phpunit.de/phpunit.phar
chmod +x /usr/local/bin/phpunit
phpunit --coverage-text --colors=never --configuration phpunit.xml 
