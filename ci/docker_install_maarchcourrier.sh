#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get install -y git \
&& mkdir -p /var/www/html/ \
&& git clone https://labs.maarch.org/maarch/MaarchCourrier.git /var/www/html/MaarchCourrier \
&& git --git-dir=/var/www/html/MaarchCourrier/.git checkout develop



