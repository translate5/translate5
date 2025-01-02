<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

$APPLICATION_ROOT = realpath(__DIR__ . '/../../../..');
require_once $APPLICATION_ROOT . '/vendor/autoload.php';

define('APPLICATION_ROOT', $APPLICATION_ROOT);
define('APPLICATION_ENV', 'test');
defined('APPLICATION_ROOT') || define('APPLICATION_ROOT', $APPLICATION_ROOT);
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $APPLICATION_ROOT . '/application');
defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'test');

$translate5 = new \Translate5\MaintenanceCli\WebAppBridge\Application();
$translate5->init('test');
// define a general marker for unit tests
// be aware, that this marker affects the TESTING installation and the tests running in it, not the via API tested installation.
// thus this flag can only be evaluated for "classic" UNIT-tests
define('APPLICATION_UNITTEST', true);

if (empty(ini_get('error_log'))) {
    ini_set('error_log', APPLICATION_ROOT . '/data/php-tests.log');
}

// For Zend1 autoloader
$db = new Zend_Db_Adapter_Pdo_Sqlite(['dbname' => 'sqlite::memory:']);
Zend_Db_Table::setDefaultAdapter($db);

$cli = new Symfony\Component\Console\Application();
$cli->setAutoExit(false);
$cli->add(new Translate5\MaintenanceCli\Command\DatabaseUpdateCommand());

$input = new Symfony\Component\Console\Input\ArrayInput([
    'command' => 'database:update',
    '--import' => null,
]);
$cli->run($input);