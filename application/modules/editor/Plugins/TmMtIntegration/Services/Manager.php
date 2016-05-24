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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * TmMt Service Manager
 * Not needed to be instanced as singleton since registered connectors were stored internally in a static member variable
 */
class editor_Plugins_TmMtIntegration_Services_Manager {
    /**
     * The services provided with this plugin are hardcoded:
     * @var array
     */
    static protected $registeredServices = array(
        'editor_Plugins_TmMtIntegration_Services_Moses',
        'editor_Plugins_TmMtIntegration_Services_DummyFileTm',
    );

    public function getAll() {
        return self::$registeredServices;
    }

    /**
     * Creates all configured connector resources
     * @return [editor_Plugins_TmMtIntegration_Connector_Abstract]
     */
    public function getAllResources() {
        $connectorResources = array();
        foreach(self::$registeredServices as $service) {
            $serviceResources = ZfExtended_Factory::get($service.'_Resources');
            /* @var $serviceResources editor_Plugins_TmMtIntegration_Services_ResourcesAbstract */
            $connectorResources = array_merge($connectorResources, $serviceResources->getResources());
        }
        return $connectorResources;
    }
    
    /**
     * With this method more services can be added (for example from other Plugins)
     * @param string $namespace - the services namespace with "_"
     * @return array all registered services
     */
    public function addService(string $namespace) {
        self::$registeredServices[] = $namespace;
        self::$registeredServices = array_unique(self::$registeredServices);
        return self::$registeredServices;
    }
    
    public function openForTask(editor_Models_Task $task) {
        error_log('opened '.$task->getTaskName());
        $list = $this->loadAssociatedTmmts($task);
        foreach($list as $one){
            $connector = call_user_func(array($one['resourceType'], 'createForResource'), $one['resourceId']);
            /* @var $connector editor_Plugins_TmMtIntegration_Connector_Abstract */
            $tmmt = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Models_TmMt');
            /* @var $tmmt editor_Plugins_TmMtIntegration_Models_TmMt */
            $tmmt->init($one);
            $connector->open($tmmt);
        }
    }
    
    public function closeForTask(editor_Models_Task $task) {
        error_log('closed '.$task->getTaskName());
    }
    
    protected function loadAssociatedTmmts(editor_Models_Task $task) {
        $tmmts = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Models_TmMt');
        /* @var $tmmts editor_Plugins_TmMtIntegration_Models_TmMt */
        return $tmmts->loadByAssociatedTask($task);
    }
}