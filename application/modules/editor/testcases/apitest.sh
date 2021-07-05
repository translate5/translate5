#!/bin/bash
CONFIG='./apitest.conf'
#check config file exists
if [ ! -f "${CONFIG}" ]; then
  echo "translate5 API test config file '${CONFIG}' does not exist, see apitest.conf.sample! exit."
  exit 1;
fi
# load config:
. $CONFIG;

INCLUDES="${APPLICATION_ROOT}application:${APPLICATION_ROOT}library:${APPLICATION_ROOT}vendor:${APPLICATION_ROOT}application/modules/editor/:.:$ZEND:/usr/share/php5:/usr/share/php"


# check if the capture-option is set (only available for single tests)
DO_CAPTURE=0
while getopts :c opt
do
   case $opt in
       c)
       		shift $((OPTIND-1))
       		DO_CAPTURE=1 
       		;;
   esac
done

# evaluate if single test or whole suite
if [ -n "$1" ]; then
TO_RUN=$@
else
TO_RUN="editorAPI"
DO_CAPTURE=0 # whole suite can not be captured
fi

# export relevant environment vars
export APPLICATION_ROOT=$APPLICATION_ROOT
export DO_CAPTURE=$DO_CAPTURE

# starting test suite:

if phpunit --atleast-version 8.0.0; then 
    phpunit --colors --verbose --cache-result-file ${APPLICATION_ROOT}application/modules/editor/testcases/.phpunit.result.cache  --include-path $INCLUDES --bootstrap bootstrap.php $TO_RUN
    exit $?
else
    phpunit --verbose --include-path $INCLUDES --bootstrap bootstrap.php $TO_RUN
    exit $?
fi
