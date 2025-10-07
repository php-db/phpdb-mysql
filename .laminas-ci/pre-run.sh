#!/bin/bash

set -e

TEST_USER=$1
WORKSPACE=$2
JOB=$3

COMMAND=$(echo "${JOB}" | jq -r '.command')

if [[ ! ${COMMAND} =~ phpunit ]]; then
    exit 0
fi

PHP_VERSION=$(echo "${JOB}" | jq -r '.php')

mysql -uroot -proot <"./mysql_permissions.sql"

# Install lsof (used in integration tests)
apt update -qq
apt install -yqq lsof
