#!/bin/bash

service mysql start
sleep 2
apachectl -DFOREGROUND
service mysql stop
