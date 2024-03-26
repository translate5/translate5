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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

use MittagQI\Translate5\LanguageResource\Adapter\Exception\RescheduleUpdateNeededException;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;

/**
 * LanguageResource Service Manager
 * TODO all services classes should be located somewhere under language resources
 * Not needed to be instanced as singleton since registered connectors were stored internally in a static member variable
 */
class editor_Services_Manager {
    const CLS_SERVICE = '_Service';
    const CLS_CONNECTOR = '_Connector';

    public const SERVICE_OPENTM2 = 'editor_Services_OpenTM2';

    /**
     * Generates a translated error-msg to report TM-update errors to the frontend
     * @param array|stdClass|null $errors
     * @param string|null $errorMsg
     * @param string $errorType
     * @param string $origin
     * @return void
     * @throws Zend_Exception
     */
    public static function reportTMUpdateError(array|stdClass $errors = null, string $errorMsg = null, string $errorType = 'Error', string $origin = 'core'): void
    {
        $translate= ZfExtended_Zendoverwrites_Translate::getInstance();
        $msg =
            $translate->_('Das Segment konnte nicht ins TM gespeichert werden')
            . '. '
            . $translate->_('Bitte kontaktieren Sie Ihren Administrator')
            . '!<br />'
            . $translate->_('Gemeldete Fehler')
            . ':';
        if(empty($errors)){
            $data = [
                'type' => $errorType,
                'error' => $errorMsg,
            ];
        } else {
            $data = (is_array($errors)) ? $errors : [$errors];
        }
        /* @var ZfExtended_Models_Messages $messages */
        $messages = Zend_Registry::get('rest_messages');
        $messages->addError($msg, $origin, null, $data);
    }
    
    /**
     * The registered services are currently hardcoded
     * @var array
     */
    protected static $registeredServices = [
        self::SERVICE_OPENTM2,
        'editor_Services_Moses',
        'editor_Services_LucyLT',
        'editor_Services_TermCollection',
        'editor_Services_SDLLanguageCloud',
        'editor_Services_Google',
        'editor_Services_Microsoft'
        //'editor_Services_DummyFileTm',
    ];

    public function getAll() {
        return self::$registeredServices;
    }

    public function getAllUiNames(): array
    {
        $names = [];

        foreach ($this->getAll() as $serviceName) {
            $names[] = ZfExtended_Factory::get($this->getServiceClassName($serviceName))->getUiName();
        }

        return $names;
    }

    public function getUiNameByType(string $serviceType): string
    {
        return ZfExtended_Factory::get($this->getServiceClassName($serviceType))->getUiName();
    }

    /**
     * Creates all configured connector resources.
     *
     * @return editor_Models_LanguageResources_Resource[]
     */
    public function getAllResources(): array
    {
        $serviceResources = [];

        foreach (self::$registeredServices as $serviceName) {
            /** @var editor_Services_ServiceAbstract $service */
            $service = ZfExtended_Factory::get($this->getServiceClassName($serviceName));
            $serviceResources[] = $service->getResources();
        }

        return array_merge(...$serviceResources);
    }

    /**
     * @param string $serviceName
     *
     * @return editor_Models_LanguageResources_Resource[]
     *
     * @throws ZfExtended_Exception
     */
    public function getAllResourcesOfType(string $serviceName): array
    {
        $resources = [];
        foreach ($this->getAllResources() as $resource) {
            if ($resource->getService() === $serviceName) {
                $resources[] = $resource;
            }
        }

        return $resources;
    }
    
    /**
     * Returns all services (= their name and helppage) that are not configured
     * or that don't have any resources embedded. (If the configuration is set,
     * but wrong, then no resources might be embedded although the service is configured.)
     *
     * @return array
     */
    public function getAllUnconfiguredServices(bool $forUi = false): array
    {
        $serviceNames = [];

        foreach (self::$registeredServices as $serviceName) {
            /* @var $service editor_Services_ServiceAbstract */
            $service = ZfExtended_Factory::get($this->getServiceClassName($serviceName));

            if (!$service->isConfigured() || empty($service->getResources())) {
                $serviceNames[] = (object)[
                    'name' => '[' . ($forUi ? $service->getUiName() : $service->getName()) . ']',
                    'serviceName' => $service->getName(),
                    'helppage' => urldecode($service->getHelppage())
                ];

                continue;
            }

            foreach ($service->getResources() as $resource) {
                $connector = ZfExtended_Factory::get('editor_Services_Connector');

                //the service is also not available when connection cannot be established
                if ($connector && $connector->ping($resource)) {
                    continue 2;
                }
            }

            $serviceNames[] = (object)[
                'name' => '[' . ($forUi ? $service->getUiName() : $service->getName()) . ']',
                'serviceName' => $service->getName(),
                'helppage' => urldecode($service->getHelppage())
            ];
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
     *
     * @return editor_Models_LanguageResources_Resource|null
     *
     * @throws ZfExtended_Exception
     */
    public function getResourceById(string $serviceType, string $id): ?editor_Models_LanguageResources_Resource
    {
        $this->checkService($serviceType);

        $resource = ZfExtended_Factory::get($this->getServiceClassName($serviceType))->getResourceById($id);

        return $resource ?? null;
    }

    /***
     * returns the desired connector, connection to the given resource
     *
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @param int|null $sourceLang
     * @param int|null $targetLang
     * @param Zend_Config|null $config : this will overwritte the default connector config value
     * @return editor_Services_Connector
     * @throws ZfExtended_Exception
     */
    public function getConnector(editor_Models_LanguageResources_LanguageResource $languageResource, int $sourceLang=null, int $targetLang=null, Zend_Config $config = null) {
        $serviceType = $languageResource->getServiceType();
        $this->checkService($serviceType);
        $connector = ZfExtended_Factory::get('editor_Services_Connector');
        /* @var $connector editor_Services_Connector */
        $connector->connectTo($languageResource,$sourceLang,$targetLang);
        if(isset($config)){
            $connector->setConfig($config);
        }
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
            throw new editor_Services_Exceptions_NoService('E1106', [
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
        $this->visitAllAssociatedTms($task, function(editor_Services_Connector $connector){
            $connector->open();
        });
    }
    
    public function closeForTask(editor_Models_Task $task) {
        $this->visitAllAssociatedTms($task, function(editor_Services_Connector $connector){
            $connector->close();
        });
    }

    /**
     * @throws Zend_Exception
     * @throws editor_Models_ConfigException
     * @throws RescheduleUpdateNeededException
     * @see \editor_Services_OpenTM2_Connector::update
     */
    public function updateSegment(editor_Models_Segment $segment)
    {
        // segments with empty sources or targets will not be updated
        // TODO FIXME: In the Frontend we should show an error when editing segments without source and save-back is active
        if ($segment->hasEmptySource() || $segment->hasEmptyTarget()) {
            return;
        }

        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($segment->getTaskGuid());
        $this->visitAllAssociatedTms(
            $task,
            function (editor_Services_Connector $connector, $languageResource, $assoc) use ($segment): void {
                if(!empty($assoc['segmentsUpdateable'])) {
                    $connector->update(
                        $segment,
                        UpdatableAdapterInterface::RECHECK_ON_UPDATE,
                        UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR
                    );
                }
            },
            function(
                Exception $e,
                editor_Models_LanguageResources_LanguageResource $languageResource,
                ZfExtended_Logger_Event $event
            ): void {
                self::reportTMUpdateError(null, $event->message, $event->eventCode);
            }
        );
    }

    /**
     * The todo callback is called on each visited TM and receives the following parameters:
     *   editor_Services_Connector $connector
     *   editor_Models_LanguageResources_LanguageResource $languageResource
     *   array $data the lang res data
     * The optional exceptionHandler callback is called on exceptions in the todo call, and receives the parameters:
     *   Exception $e
     *   editor_Models_LanguageResources_LanguageResource $languageResource
     *
     * @param editor_Models_Task $task
     * @param Closure $todo
     * @param Closure|null $exceptionHandler
     * @throws Zend_Exception
     * @throws editor_Models_ConfigException
     */
    protected function visitAllAssociatedTms(editor_Models_Task $task, Closure $todo, Closure $exceptionHandler = null) {
        $languageResources = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResources editor_Models_LanguageResources_LanguageResource */
        $list = $languageResources->loadByAssociatedTaskGuid($task->getTaskGuid());
        foreach($list as $one){
            /** @var editor_Models_LanguageResources_LanguageResource $languageResource */
            $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            // TODO $assumeDatabase is skipped here which leads to that we can not manipulate language resourse
            // inside of the connector. Need to check if we can normally load language resource from DB here.
            $languageResource->init($one);
            try {
                $connector = $this->getConnector($languageResource,null,null,$task->getConfig());
                $todo($connector, $languageResource, $one);
            }
            catch(editor_Services_Exceptions_NoService | editor_Services_Connector_Exception | ZfExtended_BadGateway $e) {
                $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource.service');
                /* @var $logger ZfExtended_Logger */

                $extraData = [
                    'languageResource' => $languageResource,
                    'task' => $task,
                ];

                //UGLY: remove on refactoring of ZfExtended_BadGateway
                if($e instanceof ZfExtended_BadGateway) {
                    $e->setErrors(array_merge($e->getErrors(), $extraData));
                } else {
                    $e->addExtraData($extraData);
                }
                $event = $logger->exception($e,[
                    'level' => $logger::LEVEL_WARN
                ], true);
                if(!is_null($exceptionHandler)) {
                    $exceptionHandler($e, $languageResource, $event);
                }
                continue;
            }
        }
    }

    private function getServiceClassName(string $namePart): string
    {
        $className = $namePart . self::CLS_SERVICE;

        if (!class_exists($className)) {
            $className = $namePart . '\ResourceConfig';
        }

        if (!class_exists($className)) {
            $className = $namePart;
        }

        return $className;
    }
}
