#!/bin/bash

IP=`/sbin/ifconfig eth0 | grep 'addr:' | cut -f2 -d':'| cut -f1 -d' ' | head -n 1`

curl --sil http://$IP:80 > /dev/null
