<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

$APPLICATION_ROOT = getenv('APPLICATION_ROOT');
$zendLib = $APPLICATION_ROOT.'/vendor/shardj/zf1-future/library/';

//include optional composer vendor autoloader. TODO FIXME: why needs this to be done here, shouldn't the bootstarpper make that happen 
if(file_exists($APPLICATION_ROOT.'/vendor/autoload.php')) {
    require_once $APPLICATION_ROOT.'/vendor/autoload.php';
}

//presetting Zend include path, get from outside!
$path = get_include_path();
set_include_path($APPLICATION_ROOT.PATH_SEPARATOR.$path.PATH_SEPARATOR.$zendLib);
   
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
define('APPLICATION_PATH', $APPLICATION_ROOT.DIRECTORY_SEPARATOR.'application');
define('APPLICATION_ENV', 'application');
// define a general marker for unit tests
// be aware, that this marker affects the TESTING installation and the tests running in it, not the via API tested installation
define('T5_IS_UNIT_TEST', true);

  
require_once 'Zend/Session.php';
Zend_Session::$_unitTestEnabled = true;
require_once 'library/ZfExtended/BaseIndex.php';
$index = ZfExtended_BaseIndex::getInstance();
$index->initApplication()->bootstrap();
$index->addModuleOptions('default');

// TODO FIXME: get rid of these globals
global $T5_API_URL;
$T5_API_URL = getenv('API_URL');
//FIXME the next two variables could be get by api call to editor/config, this would imply a testmanager login before each testcase!
global $T5_DATA_DIR;
$T5_DATA_DIR = getenv('DATA_DIR');
global $T5_LOGOUT_PATH;
$T5_LOGOUT_PATH = getenv('LOGOUT_PATH');

//forcing cwd to testcases dir
chdir(dirname(__FILE__));