#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

# Install Git
apt-get update -yqq
apt-get install git -yqq
apt-get install curl

