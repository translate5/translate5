<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Bootstrapping the API tests
 */

$APPLICATION_ROOT = rtrim(getenv('APPLICATION_ROOT'), '/');
$ENVIRONMENT = (getenv('APPLICATION_ENV') ?: 'application');

$zendLib = $APPLICATION_ROOT.'/vendor/shardj/zf1-future/library/';

//include optional composer vendor autoloader. TODO FIXME: why needs this to be done here, shouldn't the bootstarpper make that happen ?
if(file_exists($APPLICATION_ROOT.'/vendor/autoload.php')) {
    require_once $APPLICATION_ROOT.'/vendor/autoload.php';
}

//presetting Zend include path, get from outside!
$path = get_include_path();
set_include_path($APPLICATION_ROOT.PATH_SEPARATOR.$path.PATH_SEPARATOR.$zendLib);
   
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
defined('APPLICATION_ROOT') || define('APPLICATION_ROOT', $APPLICATION_ROOT);
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $APPLICATION_ROOT.DIRECTORY_SEPARATOR.'application');
defined('APPLICATION_ENV') || define('APPLICATION_ENV', $ENVIRONMENT);
// define a general marker for unit tests
// be aware, that this marker affects the TESTING installation and the tests running in it, not the via API tested installation.
// thus this flag can only be evaluated for "classic" UNIT-tests
define('APPLICATION_UNITTEST', true);

require_once 'Zend/Session.php';
Zend_Session::$_unitTestEnabled = true;
require_once 'library/ZfExtended/BaseIndex.php';
$index = ZfExtended_BaseIndex::getInstance();
$index->initApplication()->bootstrap();
$index->addModuleOptions('default');

// runtimeOptions.dir.taskData
$config = Zend_Registry::get('config');
// crucial: setup the test-API with the neccessary pathes & url's
ZfExtended_Test_ApiHelper::setup([
    'API_URL' => $config->runtimeOptions->server->protocol.$config->runtimeOptions->server->name,
    'DATA_DIR' => $config->runtimeOptions->dir->taskData,
    'LOGOUT_PATH' => $config->runtimeOptions->loginUrl,
    'CAPTURE_MODE' => (getenv('DO_CAPTURE') === '1'),
    'XDEBUG_ENABLE' => (getenv('XDEBUG_ENABLE') === '1'),
    'KEEP_DATA' => (getenv('KEEP_DATA') === '1'),
    'LEGACY_DATA' => (getenv('LEGACY_DATA') === '1'),
    'LEGACY_JSON' => (getenv('LEGACY_JSON') === '1'),
    'IS_SUITE' => (getenv('IS_SUITE') === '1'),
    'ENVIRONMENT' => $ENVIRONMENT
]);

//forcing cwd to testcases dir
chdir(dirname(__FILE__));
