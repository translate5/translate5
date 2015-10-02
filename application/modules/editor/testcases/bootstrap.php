<?php
require_once 'Zend/Loader/Autoloader.php';
$auto = Zend_Loader_Autoloader::getInstance();
$auto->registerNamespace('ZfExtended_');
$auto->registerNamespace('Editor_');
//forcing cwd to testcases dir
chdir(dirname(__FILE__));

global $T5_API_URL;
$T5_API_URL = getenv('API_URL');

//FIXME the next two variables could be get by api call to editor/config, this would imply a testmanager login before each testcase!
global $T5_DATA_DIR;
$T5_DATA_DIR = getenv('DATA_DIR');
global $T5_LOGOUT_PATH;
$T5_LOGOUT_PATH = getenv('LOGOUT_PATH');