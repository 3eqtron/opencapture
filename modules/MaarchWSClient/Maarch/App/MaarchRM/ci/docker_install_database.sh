#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get install postgresql-client -yqq


#psql -h "postgres" -U "$POSTGRES_USER" -d "$POSTGRES_DB" -w < /builds/maarch/dev/NoRM/App/MaarchRM/sql/database.sql
psql -h "postgres" -U "$POSTGRES_USER" -d "$POSTGRES_DB" -w < /builds/maarch/dev/NoRM/App/MaarchRM/sql/tables.sql

