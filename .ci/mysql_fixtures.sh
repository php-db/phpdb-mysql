#!/usr/bin/env bash

echo "Configure MySQL test database"

mysql --user=gha --password=password -e "create database phpdb_test;"
