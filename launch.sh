#!/bin/sh

if [ ! -z "$CONTAINER_SYSLOGDEST" ]; then
	syslogd -R "$CONTAINER_SYSLOGDEST" -D
fi

cd /simulator
#while true; do socat tcp-l:502,reuseaddr,fork exec:"php lake-level.php"; sleep 1; done

if [ ! -z "$CONTAINER_SIMULATION" ]; then
	# make it accessible for everything
	export LAUNCHER_BASE=$(basename $CONTAINER_SIMULATION|sed -e "s/\..*//g")
	export LAUNCHER=$LAUNCHER_BASE.php
	if [ ! -f "$LAUNCHER" ]; then
		echo "error invalid simulation type"
	fi

	while true; do
		if [ -f "/stop" ]; then
			exit 0
		else
		  echo "Launching $LAUNCHER..."
		  if [ "$LAUNCHER" = "dam-supervisor.php" ] || [ "$LAUNCHER" = "dam-gamemaster.php" ]; then
		    php "$LAUNCHER"
		  else
			  socat tcp-l:2023,reuseaddr,fork exec:"php $LAUNCHER"
      fi
			sleep 2
		fi
	done
else
	echo "error invalid simulation, not set"
fi
