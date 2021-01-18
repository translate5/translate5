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
#exit $?

if [ -n "$1" ]; then
TO_RUN=$@
else
TO_RUN="editorAPI"
fi

#starting test suite:

if phpunit --atleast-version 8.0.0; then 
    phpunit --colors --verbose --cache-result-file ${APPLICATION_ROOT}application/modules/editor/testcases/.phpunit.result.cache  --include-path $INCLUDES --bootstrap bootstrap.php $TO_RUN
    exit $?
else
    phpunit --verbose --include-path $INCLUDES --bootstrap bootstrap.php $TO_RUN
    exit $?
fi
