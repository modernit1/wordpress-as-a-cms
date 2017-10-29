FROM ubuntu:latest
MAINTAINER Topaz Bott <topaz@topazhome.net>

ENV DEBIAN_FRONTEND noninteractive

RUN \
  echo "America/New_York" > /etc/timezone && \
  apt-get update && \
  apt-get -y install \
    tzdata \
    apt-transport-https \
    ca-certificates \
    curl \
    software-properties-common && \
  dpkg-reconfigure -f noninteractive tzdata && \
  apt-get -y upgrade

RUN \
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add - && \
  add-apt-repository    "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
    $(lsb_release -cs) \
    stable" && \
  apt-get update && \
  apt-get -y install docker-ce 

RUN \
  apt-get -y install \
    vim \
    awscli

COPY bin/* /usr/local/bin/
COPY bashrc /root/.bashrc
