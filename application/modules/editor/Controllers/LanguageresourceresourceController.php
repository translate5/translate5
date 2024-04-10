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

use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\LanguageResource\Adapter\EnginesBasedApiAdapterInterface;

/**#@+
 * Resources are no valid Models/Entitys, we support only a generated Resource listing
 * One Resource is one available configured connector, Languages and Title can be customized in the TM Overview List
 */
class editor_LanguageresourceresourceController extends ZfExtended_RestController
{
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::init()
     *
     * copied the init method, parent can not be used, since no real entity is used here
     */
    public function init()
    {
        $this->initRestControllerSpecific();
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Acl_Exception
     */
    public function indexAction(): void
    {
        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);
        $result = [];

        $acl = ZfExtended_Acl::getInstance();
        $userRoles = ZfExtended_Authentication::getInstance()->getUserRoles();

        $isAllowedFilebased = $acl->isInAllowedRoles(
            $userRoles,
            Rights::ID,
            Rights::LANGUAGE_RESOURCES_ADD_FILEBASED
        );

        $isAllowedNonFilebased = $acl->isInAllowedRoles(
            $userRoles,
            Rights::ID,
            Rights::LANGUAGE_RESOURCES_ADD_NON_FILEBASED
        );

        // (1) the resources of the configured services
        $resources = $serviceManager->getAllResources();

        foreach ($resources as $resource) {
            $isFilebased = $resource->getFilebased();

            if ($isFilebased ? ! $isAllowedFilebased : ! $isAllowedNonFilebased) {
                continue;
            }

            $id = $resource->getid();
            $result[$id] = $resource->getDataObject();

            //add languages to usable resources
            $connector = ZfExtended_Factory::get(editor_Services_Connector::class);

            $languages = $connector->languages($resource);
            $result[$id]->sourceLanguages = $this->handleLanguageCodes(
                $languages[editor_Services_Connector_Abstract::SOURCE_LANGUAGES_KEY] ?? $languages
            );
            $result[$id]->targetLanguages = $this->handleLanguageCodes(
                $languages[editor_Services_Connector_Abstract::TARGET_LANGUAGES_KEY] ?? $languages
            );

            $result[$id]->stripFramingTagsConfig = $this->getStrippingFramingTagsConfig($resource);
        }

        // (2) the unconfigured services
        $allUnconfiguredServices = $serviceManager->getAllUnconfiguredServices(true);

        foreach ($allUnconfiguredServices as $unconfiguredService) {
            //filter out all configured but not reachable services
            //(the api status request returns different status from available)
            if (isset($unconfiguredService->id, $result[$unconfiguredService->id])) {
                unset($result[$unconfiguredService->id], $unconfiguredService->id);
            }

            $result[] = $unconfiguredService;
        }

        // (3)  the services from plug-ins that are not installed
        $allUninstalledPluginServices = $serviceManager->getAllUninstalledPluginServices();

        foreach ($allUninstalledPluginServices as $uninstalledService) {
            $result[] = $uninstalledService;
        }

        //remove the resource id as array key (it is not required)
        $result = array_values($result);

        //sort the results alphabetically by name
        $customSort = static function ($a, $b) {
            if ($a->name === $b->name) {
                return 0;
            }

            return ($a->name < $b->name) ? -1 : 1;
        };

        usort($result, $customSort);
        $this->view->rows = array_values($result);
        $this->view->total = count($result);
    }

    public function getAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->get');
    }

    public function putAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function deleteAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->delete');
    }

    public function postAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->post');
    }

    public function enginesAction(): void
    {
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        $resourcesOfType = $serviceManager->getAllResourcesOfType($this->getRequest()->get('resourceType'));

        if (empty($resourcesOfType)) {
            return;
        }

        /** @var editor_Models_LanguageResources_Resource $resourceType */
        $resourceType = current($resourcesOfType);

        $connector = $resourceType->getConnector();

        if (! $connector instanceof EnginesBasedApiAdapterInterface) {
            return;
        }

        $this->view->rows = $connector->getEngines()->toArray();
    }

    /***
     * For each language code in the input array, try to find the matching languages record from
     * the lek_languages table.
     *
     * @param array $languages
     * @return array[]
     */
    protected function handleLanguageCodes(array $languages)
    {
        $mapper = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguagesMapper');

        /* @var $mapper editor_Models_LanguageResources_LanguagesMapper */
        return $mapper->map($languages);
    }

    private function getStrippingFramingTagsConfig(editor_Models_LanguageResources_Resource $resource): array
    {
        $config = \Zend_Registry::get('config');

        if (! $config->runtimeOptions->LanguageResources->t5memory->stripFramingTagsEnabled) {
            return [];
        }

        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $config = $resource->getStrippingFramingTagsConfig();

        if (empty($config[editor_Models_LanguageResources_Resource::STRIP_FRAMING_TAGS_VALUES])) {
            return $config;
        }

        array_walk(
            $config[editor_Models_LanguageResources_Resource::STRIP_FRAMING_TAGS_VALUES],
            static function (&$value) use ($translate) {
                $value[1] = $translate->_($value[1]);
            }
        );

        return $config;
    }
}
