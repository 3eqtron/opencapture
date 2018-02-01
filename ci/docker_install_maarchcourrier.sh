#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

#retrieves sources
apt-get install -y git \
&& mkdir -p /var/www/html/ \
&& git clone https://labs.maarch.org/maarch/MaarchCourrier.git /var/www/html/MaarchCourrier \
&& git --git-dir=/var/www/html/MaarchCourrier/.git checkout develop

cd /var/www/html/MaarchCourrier

#install prerequisites
apt-get install wget -yqq > /dev/null \
&& apt-get install npm -yqq > /dev/null \
&& wget https://composer.github.io/installer.sig -O - -q | tr -d '\n' > installer.sig \
&& php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
&& php -r "if (hash_file('SHA384', 'composer-setup.php') === file_get_contents('installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
&& php composer-setup.php \
&& php -r "unlink('composer-setup.php'); unlink('installer.sig');" \
&& php composer.phar install \
&& mv composer.phar /usr/local/bin/composer \
&& chmod +x /usr/local/bin/composer

#install database
apt-get install postgresql-client -yqq
psql -h "postgres" -U "$POSTGRES_USER" -d "$POSTGRES_DB" -w < /var/www/html/MaarchCourrier/sql/structure.sql
psql -h "postgres" -U "$POSTGRES_USER" -d "$POSTGRES_DB" -w < /var/www/html/MaarchCourrier/sql/data_fr.sql

#creates docservers
mkdir -p /opt/maarch/docservers/indexes/{letterbox_coll,attachments_coll,version_attachments_coll} \
&& mkdir -p /opt/maarch/docservers/{ai,manual,manual_attachments,templates} \
&& mkdir -p /opt/maarch/docservers/{convert_attachments,convert_attachments_version,convert_mlb} \
&& mkdir -p /opt/maarch/docservers/{fulltext_attachments,fulltext_attachments_version,fulltext_mlb} \
&& mkdir -p /opt/maarch/docservers/{thumbnails_attachments,thumbnails_attachments_version,thumbnails_mlb}

#install composer and npm dependencies
cd /var/www/html/MaarchCourrier \
&& composer -n install \
curl -sL https://deb.nodesource.com/setup_7.x | bash - \
&& apt-get install -yqq nodejs \
&& npm install npm@latest -g \
&& npm set registry https://registry.npmjs.org/ \
&& npm install \
&& sed 's/<databaseserver>.*<\/databaseserver>/<databaseserver>postgres<\/databaseserver>/;s/<databasepassword>.*<\/databasepassword>/<databasepassword><\/databasepassword>/;s/<databasename>.*<\/databasename>/<databasename>MaarchCourrier<\/databasename>/;s/<databaseuser>.*<\/databaseuser>/<databaseuser>maarch<\/databaseuser>/' apps/maarch_entreprise/xml/config.xml.default > apps/maarch_entreprise/xml/config.xml \
&& touch installed.lck

cd /builds/maarch/MaarchCapture
