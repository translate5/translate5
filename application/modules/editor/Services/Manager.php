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

use MittagQI\Translate5\ContentProtection\SupportsContentProtectionInterface;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionServiceInterface;
use MittagQI\Translate5\CrossSynchronization\SynchronisationInterface;
use MittagQI\Translate5\CrossSynchronization\SynchronizableIntegrationInterface;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\TaskTm\Operation\CreateTaskTmOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\SupportsTaskTmInterface;
use MittagQI\Translate5\Service\DetectLanguageInterface;
use MittagQI\Translate5\Service\HasLanguageDetector;

/**
 * LanguageResource Service Manager
 * TODO all services classes should be located somewhere under language resources
 * Not needed to be instanced as singleton since registered connectors were stored internally in a static member
 * variable
 */
class editor_Services_Manager
{
    public const CLS_SERVICE = '_Service';

    public const CLS_CONNECTOR = '_Connector';

    public const SERVICE_OPENTM2 = 'editor_Services_OpenTM2';

    /**
     * Generates a translated error-msg to report TM-update errors to the frontend
     * @throws Zend_Exception
     */
    public static function reportTMUpdateError(
        array|stdClass $errors = null,
        string $errorMsg = null,
        string $errorType = 'Error',
        string $origin = 'core',
    ): void {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $msg =
            $translate->_('Das Segment konnte nicht ins TM gespeichert werden')
            . '. '
            . $translate->_('Bitte kontaktieren Sie Ihren Administrator')
            . '!<br />'
            . $translate->_('Gemeldete Fehler')
            . ':';
        if (empty($errors)) {
            $data = [
                'type' => $errorType,
                'error' => $errorMsg,
            ];
        } else {
            $data = (is_array($errors)) ? $errors : [$errors];
        }

        if (Zend_Registry::isRegistered('rest_messages')) {
            /* @var ZfExtended_Models_Messages $messages */
            $messages = Zend_Registry::get('rest_messages');
            $messages->addError($msg, $origin, null, $data);
        }
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
        'editor_Services_Microsoft',
        //'editor_Services_DummyFileTm',
    ];

    public function getAll()
    {
        return self::$registeredServices;
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function getService(string $serviceType): editor_Services_ServiceAbstract
    {
        $this->checkService($serviceType);

        return ZfExtended_Factory::get($this->getServiceClassName($serviceType));
    }

    public function getSynchronisationService(string $serviceType): ?SynchronisationInterface
    {
        try {
            $service = $this->getService($serviceType);
        } catch (editor_Services_Exceptions_NoService) {
            return null;
        }

        if (! $service instanceof SynchronizableIntegrationInterface) {
            return null;
        }

        return $service->getSynchronisationService();
    }

    public function getLanguageDetectionService(string $serviceType): ?DetectLanguageInterface
    {
        try {
            $service = $this->getService($serviceType);
        } catch (editor_Services_Exceptions_NoService) {
            return null;
        }

        if (! $service instanceof HasLanguageDetector) {
            return null;
        }

        return $service->getDetector();
    }

    /**
     * @return string[]
     */
    public function getSynchronizableServiceTypes(): array
    {
        $synchronizableServiceTypes = [];
        foreach ($this->getAll() as $serviceType) {
            $service = $this->getService($serviceType);

            if (! $service instanceof SynchronizableIntegrationInterface) {
                continue;
            }

            $synchronizableServiceTypes[] = $serviceType;
        }

        return $synchronizableServiceTypes;
    }

    public function getCreateTaskTmOperation(string $serviceType): ?CreateTaskTmOperation
    {
        try {
            $service = $this->getService($serviceType);
        } catch (editor_Services_Exceptions_NoService) {
            return null;
        }

        if (! $service instanceof SupportsTaskTmInterface) {
            return null;
        }

        return $service->getCreateTaskTmOperation();
    }

    public function getTmConversionService(string $serviceType): ?TmConversionServiceInterface
    {
        try {
            $service = $this->getService($serviceType);
        } catch (editor_Services_Exceptions_NoService) {
            return null;
        }

        if (! $service instanceof SupportsContentProtectionInterface) {
            return null;
        }

        return $service->getTmConversionService();
    }

    public function getAllNames(): array
    {
        $names = [];

        foreach ($this->getAll() as $serviceName) {
            $names[] = ZfExtended_Factory::get($this->getServiceClassName($serviceName))->getName();
        }

        return $names;
    }

    public function getNameByType(string $serviceType): string
    {
        return ZfExtended_Factory::get($this->getServiceClassName($serviceType))->getName();
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
     */
    public function getAllUnconfiguredServices(bool $forUi = false, Zend_Config $config = null): array
    {
        $serviceNames = [];

        foreach (self::$registeredServices as $serviceName) {
            /* @var $service editor_Services_ServiceAbstract */
            $service = ZfExtended_Factory::get($this->getServiceClassName($serviceName));

            if (! $service->isConfigured() || empty($service->getResources())) {
                $serviceNames[] = (object) [
                    'name' => '[' . ($forUi ? $service->getUiName() : $service->getName()) . ']',
                    'serviceName' => $service->getName(),
                    'helppage' => urldecode($service->getHelppage()),
                ];

                continue;
            }

            foreach ($service->getResources() as $resource) {
                $connector = ZfExtended_Factory::get('editor_Services_Connector');

                //the service is also not available when connection cannot be established
                if ($connector && $connector->ping($resource, $config)) {
                    continue 2;
                }
            }

            $serviceNames[] = (object) [
                'name' => '[' . ($forUi ? $service->getUiName() : $service->getName()) . ']',
                'serviceName' => $service->getName(),
                'helppage' => urldecode($service->getHelppage()),
            ];
        }

        return $serviceNames;
    }

    /**
     * Returns all services (= their name and helppage) that are available
     * as a plug-in, but the plug-ins are not installed (except for GroupShare).
     * @return array
     */
    public function getAllUninstalledPluginServices()
    {
        $serviceNames = [];
        $pluginServices = [
            'editor_Plugins_DeepL_Init' => (object) [
                'name' => '[DeepL]',
                'serviceName' => 'DeepL',
                'helppage' => urldecode('https://confluence.translate5.net/display/CON/DeepL'),
            ],
            'editor_Plugins_PangeaMt_Init' => (object) [
                'name' => '[PangeaMT]',
                'serviceName' => 'PangeaMT',
                'helppage' => urldecode('https://confluence.translate5.net/display/CON/PangeaMT'),
            ],
        ];
        // The (plug-in-)services that the user is supposed to see are by default activated on installation.
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $activePlugins = $config->runtimeOptions->plugins->active->toArray();
        foreach ($pluginServices as $key => $value) {
            if (! in_array($key, $activePlugins)) {
                $serviceNames[] = $value;
            }
        }

        return $serviceNames;
    }

    /**
     * gets the reosurce to the given languageResource
     * @return editor_Models_LanguageResources_Resource
     */
    public function getResource(editor_Models_LanguageResources_LanguageResource $languageResource)
    {
        return $this->getResourceById($languageResource->getServiceType(), $languageResource->getResourceId());
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function getResourceById(string $serviceType, string $id): ?editor_Models_LanguageResources_Resource
    {
        $this->checkService($serviceType);

        $resource = ZfExtended_Factory::get($this->getServiceClassName($serviceType))->getResourceById($id);

        return $resource ?? null;
    }

    /**
     * returns the desired connector, connection to the given resource
     *
     * @param Zend_Config|null $config : this will overwrite the default connector config value
     *
     * @return editor_Services_Connector
     *
     * @throws ZfExtended_Exception
     * @throws editor_Services_Exceptions_NoService
     */
    public function getConnector(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        int $sourceLang = null,
        int $targetLang = null,
        Zend_Config $config = null,
        ?int $customerId = null,
    ): editor_Services_Connector|UpdatableAdapterInterface|FileBasedInterface {
        $serviceType = $languageResource->getServiceType();
        $this->checkService($serviceType);
        $connector = ZfExtended_Factory::get(editor_Services_Connector::class);
        $connector->connectTo($languageResource, $sourceLang, $targetLang, $config);

        if (isset($config)) {
            $connector->setConfig($config);
        }

        if (isset($customerId)) {
            $connector->setCustomerId($customerId);
        }

        return $connector;
    }

    /**
     * checks the existance of the given service
     * @throws ZfExtended_Exception
     */
    protected function checkService(string $serviceType) // TODO is similar to isConfigured(), and why here in manager?
    {
        if (! $this->hasService($serviceType)) {
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
    public function addService(string $namespace)
    {
        self::$registeredServices[] = $namespace;
        self::$registeredServices = array_unique(self::$registeredServices);

        return self::$registeredServices;
    }

    /**
     * returns true if the given service is available
     * @return boolean
     */
    public function hasService(string $namespace)
    {
        return in_array($namespace, self::$registeredServices);
    }

    private function getServiceClassName(string $namePart): string
    {
        $className = $namePart . self::CLS_SERVICE;

        if (! class_exists($className)) {
            $className = $namePart . '\ResourceConfig';
        }

        if (! class_exists($className)) {
            $className = $namePart;
        }

        return $className;
    }
}
