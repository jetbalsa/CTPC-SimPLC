# FROM alpine:latest as build
# FROM ubuntu:latest as build
FROM ubuntu:latest as build
LABEL maintainer="noooby"

# Environments
ENV TIMEZONE            America/New_York
ENV PHP_MEMORY_LIMIT    256M

# Let's roll
RUN	apt-get update && \
	apt-get upgrade -y && \
	apt-get install -y zip sqlite socat tzdata

RUN cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
	echo "${TIMEZONE}" > /etc/timezone

RUN apt-get install -y php \
		# php-mcrypt \
		php-bz2 \
		# php-openssl \
		php-json \
		# php-pdo \
		php-zip \
		php-sqlite3 \
		php-gd \
		# php-pdo_sqlite \
		php-xmlrpc \
		php-bz2 \
		php-ctype \
		php-curl \
		php-fileinfo \
		php-memcache \
		php-memcached

FROM build

WORKDIR /simulator
COPY PLCs/dam/* ./
COPY GAMEMASTER/dam-gamemaster.php ./dam-gamemaster.php
COPY SUPERVISORS/dam-supervisor.php ./dam-supervisor.php
COPY SUPERVISORS/func.php ./func.php
COPY launch.sh ./launch.sh
COPY team ./
RUN chmod +x ./launch.sh ./*.php

# Entry point
ENTRYPOINT ["sh", "./launch.sh"]
