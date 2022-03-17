#!/bin/bash
CMD_PHP="${CMD_PHP:-/usr/bin/php}"
CMD_MYSQL="${CMD_MYSQL:-/usr/bin/mysql}"

# make sure PHP and MySQL binary exist; else die with an error message
type -p $CMD_PHP &>/dev/null || { echo "$CMD_PHP not found. Set CMD_PHP as environment variable!"; exit 1; }
type -p $CMD_MYSQL &>/dev/null || { echo "$CMD_MYSQL not found. Set CMD_MYSQL as environment variable!"; exit 1; }

case "$1" in
"")         CONFIG=""
            ;;
"--check")  CONFIG=",'updateCheck' => '1'"
            ;;
"--zend")  CONFIG=",'zend' => '$2'"
            ;;
"--database")  CONFIG=",'dbOnly' => '1'"
            ;;
"--help")  CONFIG=",'help' => '1'"
            ;;
"--appState")  CONFIG=",'applicationState' => '1'"
            ;;
"--maintenance")  
            MODE=${2:-show};
            CONFIG=",'maintenance' => '$MODE','announceMessage' => '$3'"
            ;;
"--announceMaintenance")  
            CONFIG=",'announceMaintenance' => '$2','announceMessage' => '$3'"
            ;;
"--license-ignore")  CONFIG=",'license-ignore' => '1'"
            ;;
*)          CONFIG=",'applicationZipOverride' => '$1'"
            ;;
esac

$CMD_PHP -r "require_once('application/modules/default/Models/Installer/Standalone.php'); Models_Installer_Standalone::mainLinux(array('mysql_bin' => '$CMD_MYSQL'${CONFIG}));"
