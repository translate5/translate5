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
class editor_Plugins_MatchResource_Services_Manager {
    const CLS_SERVICE = '_Service';
    const CLS_CONNECTOR = '_Connector';
    
    /**
     * The services provided with this plugin are hardcoded:
     * @var array
     */
    static protected $registeredServices = array(
        'editor_Plugins_MatchResource_Services_OpenTM2',
        'editor_Plugins_MatchResource_Services_Moses',
        'editor_Plugins_MatchResource_Services_LucyLT',
        //'editor_Plugins_MatchResource_Services_DummyFileTm',
    );

    public function getAll() {
        return self::$registeredServices;
    }

    /**
     * Creates all configured connector resources
     * @return [editor_Plugins_MatchResource_Connector_Abstract]
     */
    public function getAllResources() {
        $serviceResources = array();
        foreach(self::$registeredServices as $service) {
            $service = ZfExtended_Factory::get($service.self::CLS_SERVICE);
            /* @var $serviceResources editor_Plugins_MatchResource_Services_ServiceAbstract */
            $serviceResources = array_merge($serviceResources, $service->getResources());
        }
        return $serviceResources;
    }
    
    /**
     * gets the reosurce to the given tmmt
     * @param editor_Plugins_MatchResource_Models_TmMt $tmmt
     * @return editor_Plugins_MatchResource_Models_Resource
     */
    public function getResource(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        return $this->getResourceById($tmmt->getServiceType(), $tmmt->getResourceId());
    }
    
    /**
     * @param string $serviceType
     * @param string $id
     * @return editor_Plugins_MatchResource_Models_Resource
     */
    public function getResourceById(string $serviceType, string $id) {
        $this->checkService($serviceType);
        $resources = ZfExtended_Factory::get($serviceType.self::CLS_SERVICE);
        /* @var $resources editor_Plugins_MatchResource_Services_ServiceAbstract */
        return $resources->getResourceById($id);
    }
    
    /**
     * returns the desired connector, connection to the given resource
     * @param string $serviceType
     * @param editor_Plugins_MatchResource_Models_Resource $resource
     */
    public function getConnector(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        $serviceType = $tmmt->getServiceType();
        $this->checkService($serviceType);
        $connector = ZfExtended_Factory::get($serviceType.self::CLS_CONNECTOR);
        /* @var $connector editor_Plugins_MatchResource_Services_Connector_Abstract */
        $connector->connectTo($tmmt);
        return $connector;
    }
    
    /**
     * checks the existance of the given service
     * @param string $serviceType
     * @throws ZfExtended_Exception
     */
    protected function checkService(string $serviceType) {
        if(!$this->hasService($serviceType)) {
            throw new ZfExtended_Exception("Given Service ".$serviceType." is not registered in the Tmmt Service Manager!");
        }
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
    
    /**
     * returns true if the given service is available
     * @param string $namespace
     * @return boolean
     */
    public function hasService(string $namespace) {
        return in_array($namespace, self::$registeredServices);
    }
    
    public function openForTask(editor_Models_Task $task) {
        $this->visitAllAssociatedTms($task->getTaskGuid(), function(editor_Plugins_MatchResource_Services_Connector_Abstract $connector){
            $connector->open();
        });
    }
    
    public function closeForTask(editor_Models_Task $task) {
        $this->visitAllAssociatedTms($task->getTaskGuid(), function(editor_Plugins_MatchResource_Services_Connector_Abstract $connector){
            $connector->close();
        });
    }
    
    public function updateSegment(editor_Models_Segment $segment) {
        if(empty($segment->getTargetEdit())){
            return;
        }
        $this->visitAllAssociatedTms($segment->getTaskGuid(), function(editor_Plugins_MatchResource_Services_Connector_Abstract $connector, $tmmt, $assoc) use ($segment) {
            if(!empty($assoc['segmentsUpdateable'])) {
                $connector->update($segment);
            }
        });
    }
    
    protected function visitAllAssociatedTms($taskGuid, Closure $todo) {
        $tmmts = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
        /* @var $tmmts editor_Plugins_MatchResource_Models_TmMt */
        $list = $tmmts->loadByAssociatedTaskGuid($taskGuid);
        foreach($list as $one){
            $tmmt = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
            /* @var $tmmt editor_Plugins_MatchResource_Models_TmMt */
            $tmmt->init($one);
            $connector = $this->getConnector($tmmt);
            /* @var $connector editor_Plugins_MatchResource_Services_Connector_Abstract */
            $todo($connector, $tmmt, $one);
        }
    }
}