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

use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Initializes the Zend based Translate5 Core application
 */
class Application {
    /**
     * flag if the session should be regularly started or not (by default off)
     * @var boolean
     */
    public static $startSession = false;
    
    protected $zendIncludeDir = [
        './library/zend/'
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
    
    public function __construct(string $additionalZendDir = '') {
        if(!empty($additionalZendDir)) {
            $this->zendIncludeDir[] = $additionalZendDir;
        }
    }
    
    public function init() {
        $cwd = getcwd();
        
        $_SERVER['REQUEST_URI'] = '/database/forceimportall';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_HOST'] = 'localhost';
        define('APPLICATION_PATH', $cwd.DIRECTORY_SEPARATOR.'application');
        define('APPLICATION_ENV', 'application');
        
        require_once 'Zend/Session.php';
        \Zend_Session::$_unitTestEnabled = ! self::$startSession;
        require_once 'library/ZfExtended/BaseIndex.php';
        \ZfExtended_BaseIndex::$addMaintenanceConfig = true;
        $index = \ZfExtended_BaseIndex::getInstance();
        $index->initApplication()->bootstrap();
        $index->addModuleOptions('default');
        $index->addModuleOptions('editor');
        
        //set the hostname to the configured one:
        $config = \Zend_Registry::get('config');
        $this->hostname = $config->runtimeOptions->server->name;
        
        $this->version = \ZfExtended_Utils::getAppVersion();
    }
    
    /**
     * returns the translate5 version
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }
    
    /**
     * returns the configured translate5 hostname
     * @return string
     */
    public function getHostname(): string {
        return $this->hostname;
    }
    
    /**
     * TODO run the given URL as it would be triggered by the web server
     * @param string $url
     */
    public function run($url) {
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
