FROM ubuntu:latest
MAINTAINER Topaz Bott <topaz@topazhome.net>

RUN \
  echo "America/New_York" > /etc/timezone && \
  apt-get update && \
  apt-get -y upgrade && \
  apt-get -y install tzdata && \
  dpkg-reconfigure -f noninteractive tzdata

RUN \
  DEBIAN_FRONTEND=noninteractive apt-get -y install \
    curl \
    net-tools \
    php7.0 \
    php7.0-cgi \
    php7.0-cli \
    php7.0-common \
    php7.0-curl \
    php7.0-dev \
    php7.0-gd \
    php7.0-gmp \
    php7.0-json \
    php7.0-ldap \
    php7.0-mysql \
    php7.0-odbc \
    php7.0-opcache \
    php7.0-pgsql \
    php7.0-pspell \
    php7.0-readline \
    php7.0-recode \
    php7.0-snmp \
    php7.0-sqlite3 \
    php7.0-tidy \
    php7.0-xml \
    php7.0-xmlrpc \
    libphp7.0-embed \
    php7.0-bcmath \
    php7.0-bz2 \
    php7.0-enchant \
    php7.0-fpm \
    php7.0-imap \
    php7.0-interbase \
    php7.0-intl \
    php7.0-mbstring \
    php7.0-mcrypt \
    php7.0-phpdbg \
    php7.0-soap \
    php7.0-sybase \
    php7.0-xsl \
    php7.0-zip \
    php7.0-dba \
    libapache2-mod-php7.0 \
    php-pear \
    php-crypt-cbc \
    php-crypt-gpg \
    php-date \
    php-db \
    php-horde-role \
    php-http \
    php-http-request \
    php-mail-mime \
    php-mdb2 \
    php-mdb2-driver-mysql \
    php-mdb2-driver-pgsql \
    php-net-nntp \
    php-net-smtp \
    php-net-socket \
    php-net-whois \
    php-numbers-words \
    php-text-figlet \
    apache2 \
    unzip \
    ca-certificates \
    mysql-server-5.7 \
    vim

COPY wordpress /var/www/html
RUN \
  chown -Rv www-data:www-data /var/www/html

COPY mysqld.cnf /etc/mysql/mysql.conf.d/mysqld.cnf
COPY php.ini /etc/php/7.0/apache2/php.ini
COPY setup.sh /setup.sh
RUN /setup.sh

#COPY health-check.sh /health-check.sh

COPY entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

EXPOSE 80
