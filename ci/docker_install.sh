#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

# Install git (the php image doesn't have it) which is required by composer
apk update
apk add git

# Install mysql driver
# Here you can install any other extension that you need
docker-php-ext-install pdo_mysql pdo_sqlite

# Install composer
curl -sS https://getcomposer.org/installer | php
php composer.phar install
