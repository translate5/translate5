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
 * TODO all services classes should be located somewhere under language resources
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
        'editor_Services_TermCollection',
        'editor_Services_SDLLanguageCloud',
        'editor_Services_Google',
        'editor_Services_Microsoft'
        //'editor_Services_DummyFileTm',
    );

    public function getAll() {
        return self::$registeredServices;
    }

    /**
     * Creates all configured connector resources.
     * @return array
     */
    public function getAllResources() {
        $serviceResources = array();
        foreach(self::$registeredServices as $service) {
            $service = ZfExtended_Factory::get($service.self::CLS_SERVICE);
            /* @var $service editor_Services_ServiceAbstract */
            $serviceResources = array_merge($serviceResources, $service->getResources());
        }
        return $serviceResources;
    }
    
    /**
     * Returns all services (= their name and helppage) that are not configured 
     * or that don't have any resources embedded. (If the configuration is set,
     * but wrong, then no resources might be embedded although the service is configured.)
     * @return array
     */
    public function getAllUnconfiguredServices() {
        $serviceNames = [];
        foreach(self::$registeredServices as $service) {
            $service = ZfExtended_Factory::get($service.self::CLS_SERVICE);
            /* @var $service editor_Services_ServiceAbstract */
            
            $resource = $service->getResources()[0] ?? false;
            if (!$service->isConfigured() || empty($resource)) {
                $serviceNames[] = (object) ['name' => '['.$service->getName().']', 'serviceName' => $service->getName(), 'helppage' => urldecode($service->getHelppage())];
                continue;
            }

            //for the resource check the connection
            if(!empty($resource)){
                $connector = ZfExtended_Factory::get($resource->serviceType.editor_Services_Manager::CLS_CONNECTOR);
                /* @var $connector editor_Services_Connector_Abstract */
                
                //the service is also not available when connection cannot be established
                if($connector && !$connector->ping($resource)){
                    $serviceNames[] = (object) ['id'=>$resource->getid(),'name' => '['.$service->getName().']', 'serviceName' => $service->getName(), 'helppage' => urldecode($service->getHelppage())];
                }
            }
        }
        return $serviceNames;
    }
    
    /**
     * Returns all services (= their name and helppage) that are available
     * as a plug-in, but the plug-ins are not installed (except for GroupShare).
     * @return array
     */
    public function getAllUninstalledPluginServices() {
        $serviceNames = [];
        $pluginServices = [
            'editor_Plugins_DeepL_Init' => (object) ['name' => '[DeepL]',
                                                     'serviceName' => 'DeepL',
                                                     'helppage' => urldecode('https://confluence.translate5.net/display/CON/DeepL')],
            'editor_Plugins_NecTm_Init' => (object) ['name' => '[NEC-TM]',
                                                     'serviceName' => 'NEC-TM',
                                                     'helppage' => urldecode('https://confluence.translate5.net/display/CON/NEC-TM')],
            'editor_Plugins_PangeaMt_Init' => (object) ['name' => '[PangeaMT]',
                                                     'serviceName' => 'PangeaMT',
                                                     'helppage' => urldecode('https://confluence.translate5.net/display/CON/PangeaMT')],
        ];
        // The (plug-in-)services that the user is supposed to see are by default activated on installation.
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $activePlugins = $config->runtimeOptions->plugins->active->toArray();
        foreach ($pluginServices as $key => $value) {
            if (!in_array($key, $activePlugins)) {
                $serviceNames[] = $value;
            }
        }
        return $serviceNames;
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
     * @param int $sourceLang
     * @param int $targetLang
     * @return editor_Services_Connector
     */
    public function getConnector(editor_Models_LanguageResources_LanguageResource $languageResource,$sourceLang=null,$targetLang=null) {
        $serviceType = $languageResource->getServiceType();
        $this->checkService($serviceType);
        $connector = ZfExtended_Factory::get('editor_Services_Connector');
        /* @var $connector editor_Services_Connector */
        $connector->connectTo($languageResource,$sourceLang,$targetLang);
        return $connector;
    }
    
    /**
     * checks the existance of the given service
     * @param string $serviceType
     * @throws ZfExtended_Exception
     */
    protected function checkService(string $serviceType) { // TODO is similar to isConfigured(), and why here in manager?
        if(!$this->hasService($serviceType)) {
            //Given Language-Resource-Service "{serviceType}." is not registered in the Language-Resource-Service-Manager!
            throw new editor_Services_NoServiceException('E1106', [
                'serviceType' => $serviceType,
            ]);
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
        $this->visitAllAssociatedTms($task->getTaskGuid(), function(editor_Services_Connector $connector){
            $connector->open();
        });
    }
    
    public function closeForTask(editor_Models_Task $task) {
        $this->visitAllAssociatedTms($task->getTaskGuid(), function(editor_Services_Connector $connector){
            $connector->close();
        });
    }
    
    public function updateSegment(editor_Models_Segment $segment) {
        if(empty($segment->getTargetEdit())&&$segment->getTargetEdit()!=="0"){
            return;
        }
        $this->visitAllAssociatedTms($segment->getTaskGuid(), function(editor_Services_Connector $connector, $languageResource, $assoc) use ($segment) {
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
            try {
                $connector = $this->getConnector($languageResource);
                /* @var $connector editor_Services_Connector */
                $todo($connector, $languageResource, $one);
            }
            catch(editor_Services_NoServiceException $e) {
                $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource.service');
                /* @var $logger ZfExtended_Logger */
                $task = ZfExtended_Factory::get('editor_Models_Task');
                /* @var $task editor_Models_Task */
                $task->loadByTaskGuid($taskGuid);
                $e->addExtraData(['task' => $task]);
                $logger->exception($e,[
                    'level' => $logger::LEVEL_WARN
                ]);
                continue;
            }
            catch(ZfExtended_BadGateway $e) {
                continue;
            }
        }
    }
}
