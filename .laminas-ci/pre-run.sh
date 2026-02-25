#!/bin/bash
set -e

JOB=$3
COMMAND=$(echo "${JOB}" | jq -r '.command')
if [[ ! ${COMMAND} =~ phpunit ]]; then
    exit 0
fi

cp .laminas-ci/phpunit.xml phpunit.xml
