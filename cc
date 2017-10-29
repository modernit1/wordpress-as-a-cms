#!/bin/bash -x

docker run \
  -it \
  --rm \
  -v /var/run/docker.sock:/var/run/docker.sock \
  modernit1/cms-cnc:latest
