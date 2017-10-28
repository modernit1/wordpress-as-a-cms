#!/bin/bash

service mysql start
sleep 1
echo "create database wordpress;" | mysql
mysql wordpress < /plugin-strap.sql
sleep 1
service mysql stop
rm -fv /var/www/html/index.html
sleep 3
