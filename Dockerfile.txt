# Use Alpine Linux
FROM alpine:latest

# Maintainer
MAINTAINER noooby

# Environments
ENV TIMEZONE            America/Los_Angeles
ENV PHP_MEMORY_LIMIT    256M

# Let's roll
RUN	apk update && \
	apk upgrade && \
	apk add zip sqlite socat2 gd && \
	mkdir /simulator && \ 
	apk add --update tzdata && \
	cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
	echo "${TIMEZONE}" > /etc/timezone && \
	apk add --update \
		php5-mcrypt \
		php5-bz2 \
		php5-openssl \
		php5-json \
		php5-pdo \
		php5-zip \
		php5-mysql \
		php5-sqlite3 \
		php5-gd \
		php5-pdo_mysql \
		php5-pdo_sqlite \
		php5-xmlrpc \
		php5-bz2 \
		php5-ctype \
		php5-curl \
		php5-fileinfo \
		php7-pecl-memcache

COPY PLCs/ /simulator
COPY launch.sh /launch.sh

COPY web/ /www/

# Expose ports
EXPOSE 5502

# Entry point
ENTRYPOINT ["/launch.sh"]
