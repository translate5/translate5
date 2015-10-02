#!/bin/bash
CONFIG='./apitest.conf'
#check config file exists
if [ ! -f "${CONFIG}" ]; then
  echo "translate5 API test config file '${CONFIG}' does not exist, see apitest.conf.sample! exit."
  exit 1;
fi
# load config:
. $CONFIG;

INCLUDES="${APPLICATION_ROOT}application:${APPLICATION_ROOT}library:${APPLICATION_ROOT}application/modules/editor/:.:$ZEND:/usr/share/php5:/usr/share/php"
export API_URL=$API_URL
export DATA_DIR=$DATA_DIR
export LOGOUT_PATH=$LOGOUT_PATH

#single test to test the "things around" (translate5 internal test framework)
#phpunit --verbose --include-path $INCLUDES --bootstrap bootstrap.php editorAPI/DummyTest.php

#starting test suite:
phpunit --verbose --include-path $INCLUDES --bootstrap bootstrap.php editorAPI