#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get install -y libc-client-dev libpq-dev libxml2-dev libxslt1-dev \
&& docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql --with-kerberos \
&& docker-php-ext-install pdo_pgsql gettext pgsql xsl xmlrpc zip imap \
&& pecl install xdebug \
&& docker-php-ext-enable xdebug


