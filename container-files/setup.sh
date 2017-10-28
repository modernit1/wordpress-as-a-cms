#!/bin/bash

service mysql start
sleep 1
echo "create database wordpress;" | mysql
sleep 1
service mysql stop
sleep 3
rm -rfv /var/www/html
