#!/usr/bin/env bash
CMD_PHP="${CMD_PHP:-/usr/bin/php}"
if [ ! -f "$CMD_PHP" ]; then
    if [ -f "/usr/bin/php" ]; then
        CMD_PHP="/usr/bin/php"
    elif [ -f "/usr/local/bin/php" ]; then
        CMD_PHP="/usr/local/bin/php"
    fi
fi

# make sure PHP and MySQL binary exist; else die with an error message
type -p $CMD_PHP &>/dev/null || { echo "$CMD_PHP not found. Set CMD_PHP as environment variable!"; exit 1; }

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

$CMD_PHP -r "require_once('application/modules/default/Models/Installer/Standalone.php'); Models_Installer_Standalone::mainLinux(['dummy' => 'array'${CONFIG}]);"
