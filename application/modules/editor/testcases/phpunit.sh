#!/bin/bash
BASEPATH=/mnt/mittagqi/www/icorrect/www.translate5.net/
ZEND=/var/www/icorrect/zend/
INCLUDES="${BASEPATH}application:${BASEPATH}library:${BASEPATH}application/modules/editor/:.:$ZEND:/usr/share/php5:/usr/share/php"
phpunit --include-path $INCLUDES --bootstrap bootstrap.php editor
