#!/bin/bash
CMD_PHP="${CMD_PHP:-/usr/bin/php}"

# make sure PHP and MySQL binary exist; else die with an error message
type -p $CMD_PHP &>/dev/null || { echo "$CMD_PHP not found. Set \$CMD_PHP in $0"; exit 1; }

# change to the current directory
RUNDIR=`dirname $(realpath "$0")`;
cd $RUNDIR;

$CMD_PHP ./Translate5/maintenance-cli.php "$@"
