#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get install -y postfix dovecot-imapd \
&& echo 'virtual_mailbox_domains = fake' >> /etc/postfix/main.cf \
&& echo 'virtual_mailbox_base = /var/vmail' >> /etc/postfix/main.cf \
&& echo 'virtual_mailbox_maps = hash:/etc/postfix/virtual_mailbox' >> /etc/postfix/main.cf \
&& echo 'virtual_minimum_uid = 100' >> /etc/postfix/main.cf \
&& echo 'virtual_uid_maps = static:65534' >> /etc/postfix/main.cf \
&& echo 'virtual_gid_maps = static:8' >> /etc/postfix/main.cf \



