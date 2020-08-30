# make sure to update timezone, to build 
# docker build -t plc .
# and then we can use compose to run these

FROM alpine:latest
MAINTAINER noooby

# Environments
ENV TIMEZONE            America/New_York
ENV PHP_MEMORY_LIMIT    256M

# Let's roll
RUN	apk update && \
	apk upgrade && \
	apk add zip sqlite socat gd && \
	mkdir /simulator && \
	apk add --update tzdata && \
	cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
	echo "${TIMEZONE}" > /etc/timezone && \
	apk add --update \
		php7-mcrypt \
		php7-bz2 \
		php7-openssl \
		php7-json \
		php7-pdo \
		php7-zip \
		php7-sqlite3 \
		php7-gd \
		php7-pdo_sqlite \
		php7-xmlrpc \
		php7-bz2 \
		php7-ctype \
		php7-curl \
		php7-fileinfo \
		php7-pecl-memcache \
		php7-pecl-memcached

COPY PLCs/ /simulator
COPY launch.sh /launch.sh

RUN chmod +x /launch.sh /simulator/*.php

# Expose ports
EXPOSE 5502

# Entry point
ENTRYPOINT ["/launch.sh"]
