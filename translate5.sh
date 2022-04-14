#!/usr/bin/env bash

# Translate5 may be run under windows in a bash, so if this is the case use the PHP conf from there
if [ -f "windows-installer-config.ini" ]; then
  source <(grep INSTALL_PHP_PATH windows-installer-config.ini | sed "s/\\\/\//g")
  CMD_PHP=$INSTALL_PHP_PATH
else
  CMD_PHP="${CMD_PHP:-/usr/bin/php}"
fi

CMD_PHP="${CMD_PHP:-/usr/bin/php}"

# make sure PHP and MySQL binary exist; else die with an error message
type -p $CMD_PHP &>/dev/null || { echo "$CMD_PHP not found. Set \$CMD_PHP in $0"; exit 1; }

# change to the current directory
RUNDIR=`dirname $(realpath "$0")`;
cd $RUNDIR;

$CMD_PHP ./Translate5/maintenance-cli.php "$@"
