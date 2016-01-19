<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Bootstrapping the API tests
 */
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