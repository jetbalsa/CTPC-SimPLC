#!/bin/sh

if [ ! -z "$CONTAINER_SYSLOGDEST ]; then
	syslogd -R "$CONTAINER_SYSLOGDEST" -D
fi


while true; do socat tcp-l:502,reuseaddr,fork exec:./lake-level.php; sleep 1; done

cd /simulator 

if [ ! -z "$CONTAINER_SIMULATION" ]; then
	# make it accessible for everything
	export LAUNCHER_BASE=$(basename "$CONTAINER_SIMULATION"|sed -e "s/\..*//g")
	export LAUNCHER="$LAUNCHER_BASE.php"
	if [ ! -f "$LAUNCHER" ]; then
		echo "error invalid simulation type"
	fi
	
	while true; do
		if [ -f "/stop" ]; then
			exit 0
		else 
			socat "tcp-l:5502,reuseaddr,fork exec:./$LAUNCHER"
			sleep 2
		fi
	done
else
	echo "error invalid simulation, not set"
fi