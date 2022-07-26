#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get install -y libkrb5-dev libc-client-dev libpq-dev libxml2-dev libxslt1-dev \
&& mkdir /usr/kerberos \
&& ln -s /usr/lib/x86_64-linux-gnu/mit-krb5/* /usr/kerberos \
&& docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
#&& docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
#&& docker-php-ext-install pdo_pgsql soap gettext pgsql xsl xmlrpc zip imap \
docker-php-ext-install soap xsl imap
#&& pecl install xdebug \
#&& docker-php-ext-enable xdebug \
pear install SOAP-0.13.0
