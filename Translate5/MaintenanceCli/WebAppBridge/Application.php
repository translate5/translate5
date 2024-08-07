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

namespace Translate5\MaintenanceCli\WebAppBridge;

/**
 * Initializes the Zend based Translate5 Core application
 */
class Application
{
    /**
     * flag if the session should be regularly started or not (by default off)
     * @var boolean
     */
    public static $startSession = false;

    protected $zendIncludeDir = [
        './library/zend/',
    ];

    /**
     * The translate5 version
     * @var string
     */
    protected $version = '';

    /**
     * The translate5 hostname
     * @var string
     */
    protected $hostname = '';

    public function __construct(string $additionalZendDir = '')
    {
        if (! empty($additionalZendDir)) {
            $this->zendIncludeDir[] = $additionalZendDir;
        }
    }

    /**
     * @throws \Zend_Exception
     */
    public function init(string $applicationEnvironment = 'application')
    {
        $cwd = getcwd();

        $_SERVER['REQUEST_URI'] = '/editor/index';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_HOST'] = 'localhost';

        // when a test-environment is wanted we need to add the 'APPLICATION_APITEST' as no Origin-header is given with CLI
        // this is e.g. needed for the worker-trigger "process" to properly function with workers making api-requests
        if ($applicationEnvironment === 'test' || $applicationEnvironment === 'apptest') {
            defined('APPLICATION_APITEST') || define('APPLICATION_APITEST', true);
            // the "virtual" apptest environment only triggers the APITEST-flag and does not trigger a DB-switch
            // this reflects the origin-header logic in ZfExtended_BaseIndex
            if ($applicationEnvironment === 'apptest') {
                $applicationEnvironment = 'application';
            }
        }

        defined('APPLICATION_PATH') || define('APPLICATION_PATH', $cwd . DIRECTORY_SEPARATOR . 'application');
        defined('APPLICATION_ENV') || define('APPLICATION_ENV', $applicationEnvironment);

        require_once 'Zend/Session.php';
        require_once 'library/ZfExtended/BaseIndex.php';
        \Zend_Session::$_unitTestEnabled = ! self::$startSession;
        \ZfExtended_BaseIndex::$addMaintenanceConfig = true;
        $index = \ZfExtended_BaseIndex::getInstance();
        $index->initApplication()->bootstrap();
        $index->addModuleOptions('default');
        $index->addModuleOptions('editor');

        //faking the viewsetup to get the correct view search paths
        $viewSetup = new \ZfExtended_Controllers_Plugins_ViewSetup();
        $viewSetup->routeShutdown(new \Zend_Controller_Request_Simple());

        //set the hostname to the configured one:
        $config = \Zend_Registry::get('config');
        $this->hostname = $config->runtimeOptions->server->name;

        $this->version = \ZfExtended_Utils::getAppVersion();
    }

    /**
     * returns the translate5 version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * returns the configured translate5 hostname
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * TODO run the given URL as it would be triggered by the web server
     * @param string $url
     */
    public function run($url)
    {
        /*
//The index_prerun file is automatically added by the deploy process of some concrete t5 instances
if(file_exists('../client-specific/index_prerun.php')) {
    include('../client-specific/index_prerun.php');
}
require_once '../library/ZfExtended/BaseIndex.php';
$index = ZfExtended_BaseIndex::getInstance();
$index->startApplication();
         */
    }
}
