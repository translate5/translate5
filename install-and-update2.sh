#!/usr/bin/env bash
CMD_PHP="${CMD_PHP:-/usr/bin/php}"
CMD_MYSQL="${CMD_MYSQL:-/usr/bin/mysql}"
 
# make sure PHP and MySQL binary exist; else die with an error message
type -p $CMD_PHP &>/dev/null || { echo "$CMD_PHP not found. Set \$CMD_PHP in $0"; exit 1; }
type -p $CMD_MYSQL &>/dev/null || { echo "$CMD_MYSQL not found. Set \$CMD_MYSQL in $0"; exit 1; }

echo
echo "Deprecated - call translate5.sh --help"
echo

MODULE=$1
shift
$CMD_PHP -r "require_once('application/modules/default/Models/Installer/Standalone2.php'); \
\$config = ['mysql_bin' => '$CMD_MYSQL', 'arguments' => \$_SERVER['argv'], 'module' => '$MODULE']; \
Models_Installer_Standalone2::mainLinux(\$config);" -- "$@"
