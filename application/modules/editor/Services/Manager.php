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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * LanguageResource Service Manager
 * Not needed to be instanced as singleton since registered connectors were stored internally in a static member variable
 */
class editor_Services_Manager {
    const CLS_SERVICE = '_Service';
    const CLS_CONNECTOR = '_Connector';
    
    /**
     * The services provided with this plugin are hardcoded:
     * @var array
     */
    static protected $registeredServices = array(
        'editor_Services_OpenTM2',
        'editor_Services_Moses',
        'editor_Services_LucyLT',
        'editor_Services_SDLLanguageCloud',
        'editor_Services_TermCollection',
        'editor_Services_Google'
        //'editor_Services_DummyFileTm',
    );

    public function getAll() {
        return self::$registeredServices;
    }

    /**
     * Creates all configured connector resources
     * @return [editor_Services_Connector_Abstract]
     */
    public function getAllResources() {
        $serviceResources = array();
        foreach(self::$registeredServices as $service) {
            $service = ZfExtended_Factory::get($service.self::CLS_SERVICE);
            /* @var $serviceResources editor_Services_ServiceAbstract */
            $serviceResources = array_merge($serviceResources, $service->getResources());
        }
        return $serviceResources;
    }
    
    /**
     * gets the reosurce to the given languageResource
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @return editor_Models_LanguageResources_Resource
     */
    public function getResource(editor_Models_LanguageResources_LanguageResource $languageResource) {
        return $this->getResourceById($languageResource->getServiceType(), $languageResource->getResourceId());
    }
    
    /**
     * @param string $serviceType
     * @param string $id
     * @return editor_Models_LanguageResources_Resource
     */
    public function getResourceById(string $serviceType, string $id) {
        $this->checkService($serviceType);
        $resources = ZfExtended_Factory::get($serviceType.self::CLS_SERVICE);
        /* @var $resources editor_Services_ServiceAbstract */
        return $resources->getResourceById($id);
    }
    
    /***
     * returns the desired connector, connection to the given resource
     * 
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @param integer $sourceLang
     * @param integer $targetLang
     * @return editor_Services_Connector_Abstract
     */
    public function getConnector(editor_Models_LanguageResources_LanguageResource $languageResource,$sourceLang=null,$targetLang=null) {
        $serviceType = $languageResource->getServiceType();
        $this->checkService($serviceType);
        $connector = ZfExtended_Factory::get($serviceType.self::CLS_CONNECTOR);
        /* @var $connector editor_Services_Connector_Abstract */
        $connector->connectTo($languageResource,$sourceLang,$targetLang);
        return $connector;
    }
    
    /**
     * checks the existance of the given service
     * @param string $serviceType
     * @throws ZfExtended_Exception
     */
    protected function checkService(string $serviceType) {
        if(!$this->hasService($serviceType)) {
            throw new ZfExtended_Exception("Given Service ".$serviceType." is not registered in the LanguageResource Service Manager!");
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
        $this->visitAllAssociatedTms($task->getTaskGuid(), function(editor_Services_Connector_Abstract $connector){
            $connector->open();
        });
    }
    
    public function closeForTask(editor_Models_Task $task) {
        $this->visitAllAssociatedTms($task->getTaskGuid(), function(editor_Services_Connector_Abstract $connector){
            $connector->close();
        });
    }
    
    public function updateSegment(editor_Models_Segment $segment) {
        if(empty($segment->getTargetEdit())){
            return;
        }
        $this->visitAllAssociatedTms($segment->getTaskGuid(), function(editor_Services_Connector_Abstract $connector, $languageResource, $assoc) use ($segment) {
            if(!empty($assoc['segmentsUpdateable'])) {
                $connector->update($segment);
            }
        });
    }
    
    protected function visitAllAssociatedTms($taskGuid, Closure $todo) {
        $languageResources = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResources editor_Models_LanguageResources_LanguageResource */
        $list = $languageResources->loadByAssociatedTaskGuid($taskGuid);
        foreach($list as $one){
            $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
            $languageResource->init($one);
            $connector = $this->getConnector($languageResource);
            /* @var $connector editor_Services_Connector_Abstract */
            $todo($connector, $languageResource, $one);
        }
    }
}