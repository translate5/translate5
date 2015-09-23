<?php
require_once '/usr/share/php/phpunit/phpunit.phar';
require_once 'Zend/Loader/Autoloader.php';
$auto = Zend_Loader_Autoloader::getInstance();
$auto->registerNamespace('ZfExtended_');
$auto->registerNamespace('Editor_');
