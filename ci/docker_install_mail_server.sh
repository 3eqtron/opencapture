#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

export DEBIAN_FRONTEND=noninteractive \
&& apt-get install -yqq postfix dovecot-imapd less \
&& export DEBIAN_FRONTEND=dialog \
&& echo 'virtual_mailbox_domains = fake' >> /etc/postfix/main.cf \
&& echo 'virtual_mailbox_base = /var/vmail' >> /etc/postfix/main.cf \
&& echo 'virtual_mailbox_maps = hash:/etc/postfix/virtual_mailbox' >> /etc/postfix/main.cf \
&& echo 'virtual_minimum_uid = 100' >> /etc/postfix/main.cf \
&& echo 'virtual_uid_maps = static:65534' >> /etc/postfix/main.cf \
&& echo 'virtual_gid_maps = static:8' >> /etc/postfix/main.cf \
&& touch /etc/postfix/virtual_mailbox \
&& echo 'test1@fake test1/' >> /etc/postfix/virtual_mailbox \
&& echo 'fallthrough@fake test2/' >> /etc/postfix/virtual_mailbox \
&& postmap /etc/postfix/virtual_mailbox \
&& mkdir /var/vmail \
&& chown nobody:mail /var/vmail \
&& postfix reload \
&& touch /etc/dovecot/conf.d/auth-plain.conf.ext \
&& echo 'passdb {' >> /etc/postfix/virtual_mailbox \
&& echo '  driver = passwd-file' >> /etc/postfix/virtual_mailbox \
&& echo '  args = username_format=%u /etc/dovecot/passwd' >> /etc/postfix/virtual_mailbox \
&& echo '}' >> /etc/postfix/virtual_mailbox \
&& echo 'userdb {' >> /etc/postfix/virtual_mailbox \
&& echo '  driver = passwd-file' >> /etc/postfix/virtual_mailbox \
&& echo '  args = username_format=%u /etc/dovecot/passwd' >> /etc/postfix/virtual_mailbox \
&& echo '}' >> /etc/postfix/virtual_mailbox \
&& echo '!include auth-plain.conf.ext' >> /etc/dovecot/conf.d/10-auth.conf \
&& touch /etc/dovecot/passwd \
&& echo 'test1@fake:{SSHA}ghZpew7L4psekJyC0MUoVA3Usg0SxAjm:65534:8::/var/vmail/test1::' >> /etc/dovecot/passwd \
&& echo 'test2@fake:{SSHA}c9yb4ibK+rpoMBR+OnoMBrNgyjD8KraL:65534:8::/var/vmail/test2::' >> /etc/dovecot/passwd \
&& doveadm pw -s SSHA -p yourPassword \
&& echo 'mail_location = maildir:/var/vmail/%n' >> /etc/dovecot/conf.d/10-mail.conf \
&& /etc/init.d/dovecot restart
