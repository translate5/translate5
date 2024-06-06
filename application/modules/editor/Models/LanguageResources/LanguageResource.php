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

use editor_Models_Terminology_Models_CollectionAttributeDataType as CollectionAttributeDataType;

/**
 * Languageresources Entity Object
 *
 * @property string $sourceLangCode
 * @property string $targetLangCode
 *
 * @method string getId()
 * @method void setId(int $id)
 * @method string getLangResUuid()
 * @method void setLangResUuid(string $guid)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getColor()
 * @method void setColor(string $color)
 * @method string getResourceId() The id of the used resource
 * @method void setResourceId(string $resourceId)
 * @method string getSpecificId()
 * @method void setSpecificId(string $specificId)
 * @method string getServiceType() The PHP class name for the service
 * @method void setServiceType(string $type)
 * @method string getServiceName() The speakable name of the service as configured in the resource
 * @method void setServiceName(string $resName)
 * @method string getResourceType()  "tm" or "mt" or "termcollection"
 * @method void setResourceType(string $resourceType)
 * @method string getWriteSource()
 * @method void setWriteSource(bool $writeSource)
 */
class editor_Models_LanguageResources_LanguageResource extends ZfExtended_Models_Entity_Abstract
{
    use editor_Models_Entity_SpecificDataTrait;

    public const PROTECTION_HASH = 'protection_hash';

    public const PROTECTION_CONVERSION_STARTED = 'conversionStarted';

    private const SPECIFIC_DATA_STATUS = 'status';

    /***
     * set as match rate type when match-rate was changed
     */
    public const MATCH_RATE_TYPE_EDITED = 'matchresourceusage';

    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_LanguageResource';

    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_LanguageResource';

    /**
     * Language-resources must be filtered by role-driven restrictions, what must be done via our customer-association
     * This differs from "customerIds" in the controller !
     */
    protected ?array $clientAccessRestriction = [
        'field' => 'customerId',
        'type' => 'list',
        'assoc' => [
            'table' => 'LEK_languageresources_customerassoc',
            'foreignKey' => 'languageResourceId',
            'localKey' => 'id',
            'searchField' => 'customerId',
        ],
    ];

    /**
     * Caches the customers of a language-resource
     */
    protected array $customers = [];

    private array $cachedLanguages;

    private string $absoluteDataPath;

    /***
     * Init the language resource instance for given editor_Models_LanguageResources_Resource
     * @param editor_Models_LanguageResources_Resource $resource
     * @return void
     */
    public function initByResource(editor_Models_LanguageResources_Resource $resource)
    {
        $this->createLangResUuid();
        $this->setColor($resource->getDefaultColor());
        $this->setResourceId($resource->getId());
        $this->setServiceType($resource->getServiceType());
        $this->setServiceName($resource->getService());
        $this->setResourceType($resource->getType());
    }

    /***
     * Load all resources for all available services
     *
     * @return array
     */
    public function loadAllByServices()
    {
        $services = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $services editor_Services_Manager */

        //get all service types from the available resources
        $resources = $services->getAllResources();
        $allservices = [];
        foreach ($resources as $resource) {
            /* @var $resource editor_Models_LanguageResources_Resource */
            $allservices[] = $resource->getServiceType();
        }
        $allservices = array_unique($allservices);
        $s = $this->db->select()
            ->where('LEK_languageresources.serviceType IN(?)', $allservices);

        return $this->loadFilterdCustom($s);
    }

    /***
     * Load all language resource by given service name
     * @param string $serviceName
     * @return array
     */
    public function loadByService(string $serviceName): array
    {
        $s = $this->db->select()
            ->where('LEK_languageresources.serviceName = ?', $serviceName);

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Load language resource by given name and type associated with particular task
     *
     * @param string $type
     * @param string $name
     * @param array $data
     * @param editor_Models_Task $task
     *
     * @return array
     */
    public function getByTypeNameAndSpecificDataForTask(
        string $type,
        string $name,
        array $data,
        editor_Models_Task $task
    ): array {
        $s = $this->db
            ->select()
            ->from([
                'lr' => 'LEK_languageresources',
            ], ['lr.*'])
            ->setIntegrityCheck(false)
            ->joinLeft(
                [
                    'l' => 'LEK_languageresources_languages',
                ],
                'lr.id = l.languageResourceId',
                ['sourceLang', 'targetLang']
            )
            ->joinLeft(
                [
                    'lca' => 'LEK_languageresources_customerassoc',
                ],
                'lr.id = lca.languageResourceId',
                ['customerId']
            )
            ->where('lr.serviceType = ?', $type)
            ->where('lr.name = ?', $name)
            ->where('lca.customerId = ?', $task->getCustomerId());

        foreach ($data as $key => $value) {
            $s->where('JSON_EXTRACT(lr.specificData, "$.' . $key . '") = ?', $value);
        }

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Fetches language-resources of the specified types that have the given language-codes
     * The language-codes either must be identical (default) or are searched by similarity (primary language equals)
     * @param bool $respectCustomerRestriction : if set, the fetched resources must not have customers of this resource
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     */
    public function getByTypesAndLanguages(
        array $types,
        int $sourceLangId,
        int $targetLangId,
        bool $respectCustomerRestriction = true
    ): array {
        // first, evaluate the fuzzy languages
        $languages = ZfExtended_Factory::get(editor_Models_Languages::class);
        $sourceLanguageIds = $languages->getFuzzyLanguages($sourceLangId, 'id', true);
        $targetLanguageIds = $languages->getFuzzyLanguages($targetLangId, 'id', true);

        // evaluate Clients/Customers
        $clientIds = ($respectCustomerRestriction) ? $this->getCustomers() : null;
        // the current user may is client-restricted and we have to respect that restriction in any case
        if (ZfExtended_Authentication::getInstance()->isUserClientRestricted()) {
            $restrictedClientIds = ZfExtended_Authentication::getInstance()->getUser()->getRestrictedClientIds();
            $clientIds = empty($clientIds) ?
                $restrictedClientIds
                : array_values(array_intersect($clientIds, $restrictedClientIds));
            // shortcut: no clients, no resources ...
            if (empty($clientIds)) {
                return [];
            }
        }

        $select = $this->createGetByXyzSelect($sourceLangId, $targetLangId);

        // type restriction
        ZfExtended_Utils::addArrayCondition($select, $types, 'lr.resourceType');

        // client restriction - if we have one
        if ($clientIds !== null) {
            $select
                ->joinLeft(
                    [
                        'lca' => 'LEK_languageresources_customerassoc',
                    ],
                    'lr.id = lca.languageResourceId',
                    ['customerId']
                );
            ZfExtended_Utils::addArrayCondition($select, $clientIds, 'lca.customerId');
        }

        return $this->db->fetchAll($select)->toArray();
    }

    /**
     * Fetches language-resources of the specified service-names that have the given language-codes
     * The language-codes either must be identical (default) or are searched by similarity (primary language equals)
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     */
    public function getByServicenamesAndLanguages(
        array $serviceNames,
        int $sourceLangId,
        int $targetLangId
    ): array {
        $select = $this->createGetByXyzSelect($sourceLangId, $targetLangId);

        // servicename restriction
        ZfExtended_Utils::addArrayCondition($select, $serviceNames, 'lr.serviceName');

        return $this->db->fetchAll($select)->toArray();
    }

    /**
     * Fetches all language-resources that have the given language-codes
     * The language-codes either must be identical (default) or are searched by similarity (primary language equals)
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     */
    public function getByLanguages(
        int $sourceLangId,
        int $targetLangId
    ): array {
        $select = $this->createGetByXyzSelect($sourceLangId, $targetLangId);

        return $this->db->fetchAll($select)->toArray();
    }

    /**
     * get a database select statement to search for language-resources
     * that are able to handle the submitted source- and target-languages.
     *
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     */
    protected function createGetByXyzSelect(
        int $sourceLangId,
        int $targetLangId
    ): Zend_Db_Table_Select {
        // first, evaluate the fuzzy languages
        $languages = ZfExtended_Factory::get(editor_Models_Languages::class);
        $sourceLanguageIds = $languages->getFuzzyLanguages($sourceLangId, 'id', true);
        $targetLanguageIds = $languages->getFuzzyLanguages($targetLangId, 'id', true);

        $select = $this->db
            ->select()
            ->from(
                [
                    'lr' => 'LEK_languageresources',
                ],
                ['lr.id', 'lr.name', 'lr.serviceName', 'lr.resourceType', 'lr.specificData']
            )
            ->setIntegrityCheck(false)
            ->joinLeft(
                [
                    'lla' => 'LEK_languageresources_languages',
                ],
                'lr.id = lla.languageResourceId',
                ['sourceLangCode', 'targetLangCode']
            );

        // add language restriction to select-statement
        ZfExtended_Utils::addArrayCondition($select, $sourceLanguageIds, 'lla.sourceLang');
        ZfExtended_Utils::addArrayCondition($select, $targetLanguageIds, 'lla.targetLang');

        return $select;
    }

    public function getByResourceId(string $resourceId): array
    {
        $s = $this->db
            ->select()
            ->from([
                'lr' => 'LEK_languageresources',
            ], ['lr.*'])
            ->where('lr.resourceId = ?', $resourceId);

        return $this->db->fetchAll($s)->toArray();
    }

    public function getByResourceIdFilteredByNamePart(string $resourceId, string $namePart): array
    {
        $s = $this->db
            ->select()
            ->from([
                'lr' => 'LEK_languageresources',
            ], ['lr.*'])
            ->setIntegrityCheck(false)
            ->joinLeft(
                [
                    'l' => 'LEK_languageresources_languages',
                ],
                'lr.id = l.languageResourceId',
                ['sourceLangCode', 'targetLangCode']
            )
            ->where('lr.resourceId = ?', $resourceId)
            ->where('lr.name LIKE ?', '%' . $namePart . '%');

        return $this->db->fetchAll($s)->toArray();
    }

    public function getByResourceIdFilteredByLanguageCodes(
        string $resourceId,
        string $sourceLanguageCode,
        string $targetLanguageCode
    ): array {
        $s = $this->db
            ->select()
            ->from([
                'lr' => 'LEK_languageresources',
            ], ['lr.*'])
            ->setIntegrityCheck(false)
            ->joinLeft(
                [
                    'l' => 'LEK_languageresources_languages',
                ],
                'lr.id = l.languageResourceId',
                ['sourceLangCode', 'targetLangCode']
            )
            ->where('lr.resourceId = ?', $resourceId)
            ->where('l.sourceLangCode = ?', $sourceLanguageCode)
            ->where('l.targetLangCode = ?', $targetLanguageCode);

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Get all available language resources for customers of current user
     * The result data will in custom format(used in instanttranslate frontend)
     *
     * @param bool $addArrayId : if true(default true), the array key will be the language resource id
     * @param string $resourceType : when given, only available resources of this type will be returned
     * @return array
     */
    public function getAllMergedByAssoc($addArrayId = true, string $resourceType = null)
    {
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        $resources = $serviceManager->getAllResources();
        $services = [];
        //get all available tm resources
        foreach ($resources as $resource) {
            $tmpType = $resourceType ?? $resource->getType();
            /* @var $resource editor_Models_LanguageResources_Resource */
            if (! in_array($resource->getService(), $services) && $tmpType == $resource->getType()) {
                $services[] = $resource->getService();
            }
        }

        //filter assoc resources by services
        $engines = $this->loadByUserCustomerAssocs($services);
        //check if results are found
        if (empty($engines)) {
            return $engines;
        }

        $sdl = ZfExtended_Factory::get('editor_Models_LanguageResources_SdlResources');
        /* @var $sdl editor_Models_LanguageResources_SdlResources */

        //merge the data as instanttransalte format
        return $sdl->mergeEngineData($engines, $addArrayId);
    }

    /**
     * Get info about which language resources can be associated with tasks having $targetLangs languages
     * Return value would be like below:
     * [
     *  'targetLang1Id' => [langResource1Id, langResource2Id],
     *  'targetLang2Id' => [langResource1Id, langResource3Id],
     * ]
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getUseAsDefaultForTaskAssoc(int $customerId, array $targetLangs)
    {
        // Get editor_Models_LanguageResources_CustomerAssoc model shortcut
        $lrcaM = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');

        // Fetch `languageResourceId`-values by $customerId, having `useAsDefault`=1
        if (! $languageResourceIds = $lrcaM->loadByCustomerIdsUseAsDefault([$customerId], 'languageResourceId')) {
            return [];
        }

        // Get info about which language resources can be associated with tasks having $targetLangs languages
        return $this->db->getAdapter()->query('
            SELECT DISTINCT `targetLang`, `languageResourceId`
            FROM `LEK_languageresources_languages` 
            WHERE 1
              AND `languageResourceId` IN (' . join(',', $languageResourceIds) . ') 
              AND `targetLang` IN (' . join(',', $targetLangs) . ')
        ')->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
    }

    /***
     * Load all resources associated customers of a user
     *
     * @param array $serviceNames : add service name as filter
     * @param array $sourceLang : add source languages as filter
     * @param array $targetLang : add target languages as filter
     *
     * @return array
     * @throws ReflectionException
     */
    public function loadByUserCustomerAssocs(
        array $serviceNames = [],
        array $sourceLang = [],
        array $targetLang = []
    ): array {
        $customers = ZfExtended_Authentication::getInstance()->getUser()?->getCustomersArray();
        if (empty($customers)) {
            return [];
        }

        //each sdlcloud language resource can have only one language combination
        $s = $this->db->select()
            ->from([
                'tm' => 'LEK_languageresources',
            ], ['tm.*'])
            ->setIntegrityCheck(false)
            ->join([
                'ca' => 'LEK_languageresources_customerassoc',
            ], 'tm.id = ca.languageResourceId', '')
            ->join([
                'l' => 'LEK_languageresources_languages',
            ], 'tm.id = l.languageResourceId', [
                'GROUP_CONCAT(`l`.`sourceLang`) as sourceLang',
                'GROUP_CONCAT(`l`.`targetLang`) as targetLang',
            ])->where('ca.customerId IN(?)', $customers);

        if (! empty($serviceNames)) {
            $s->where('tm.serviceName IN(?)', $serviceNames);
        }

        if (! empty($sourceLang)) {
            $s->where('l.sourceLang IN(?)', $sourceLang);
        }

        if (! empty($targetLang)) {
            $s->where('l.targetLang IN(?)', $targetLang);
        }
        $s->group('tm.id');

        return $this->mapLanguageCodes($this->db->fetchAll($s)->toArray());
    }

    /**
     * Map all language codes to their rfc5646 representation in the given result as separate arrays(sourceLangCode
     * and targetLangCode). It will also convert the sourceLang and targetLang to arrays.
     * @throws ReflectionException
     */
    private function mapLanguageCodes(array $result = []): array
    {
        if (empty($result)) {
            return [];
        }

        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        $languagesMapping = $languages->loadAllKeyValueCustom('id', 'rfc5646');

        // explode the language codes and map them to their rfc5646 representation
        $result = array_map(function ($item) use ($languagesMapping) {
            $item['sourceLang'] = explode(',', $item['sourceLang']);
            $item['targetLang'] = explode(',', $item['targetLang']);

            foreach ($item['sourceLang'] as $langId) {
                $item['sourceLangCode'][] = $languagesMapping[$langId];
            }
            foreach ($item['targetLang'] as $langId) {
                $item['targetLangCode'][] = $languagesMapping[$langId];
            }

            return $item;
        }, $result);

        return $result;
    }

    /**
     * loads the task to languageResource assocs by a taskguid
     * @return array
     */
    public function loadByAssociatedTaskGuid(string $taskGuid)
    {
        return $this->loadByAssociatedTaskGuidList([$taskGuid]);
    }

    /**
     * loads the task to languageResource assocs by taskguid
     * @param string[] $taskGuidList
     * @return array
     */
    public function loadByAssociatedTaskGuidList(array $taskGuidList)
    {
        if (empty($taskGuidList)) {
            return [];
        }
        $assocDb = new MittagQI\Translate5\LanguageResource\Db\TaskAssociation();
        $assocName = $assocDb->info($assocDb::NAME);
        $s = $this->db->select()
            ->from($this->db, ['*', $assocName . '.taskGuid', $assocName . '.segmentsUpdateable'])
            ->setIntegrityCheck(false)
            ->join($assocName, $assocName . '.`languageResourceId` = ' . $this->db->info($assocDb::NAME) . '.`id`', '')
            ->where($assocName . '.`taskGuid` in (?)', $taskGuidList);

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * loads the task to languageResource assocs by list of taskGuids and resourceTypes
     * @return array
     */
    public function loadByAssociatedTaskGuidListAndResourcesType(array $taskGuidList, array $resourceTypes)
    {
        if (empty($taskGuidList)) {
            return $taskGuidList;
        }
        $assocDb = new MittagQI\Translate5\LanguageResource\Db\TaskAssociation();
        $tableName = $this->db->info($assocDb::NAME);
        $assocName = $assocDb->info($assocDb::NAME);
        $s = $this->db->select()
            ->from($this->db, ['*', $assocName . '.taskGuid', $assocName . '.segmentsUpdateable'])
            ->setIntegrityCheck(false)
            ->join($assocName, $assocName . '.`languageResourceId` = ' . $tableName . '.`id`', '')
            ->where($assocName . '.`taskGuid` IN (?)', $taskGuidList)
            ->where($tableName . '.resourceType IN(?)', $resourceTypes);

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Loads the task to languageResource assocs by list of taskGuids and serviceTypes
     *
     * @throws Zend_Db_Table_Exception
     */
    public function loadByAssociatedTaskGuidListAndServiceTypes(array $taskGuidList, array $serviceTypes): array
    {
        if (count($taskGuidList) === 0) {
            return [];
        }

        $assocDb = new MittagQI\Translate5\LanguageResource\Db\TaskAssociation();
        $tableName = $this->db->info($assocDb::NAME);
        $assocName = $assocDb->info($assocDb::NAME);

        $s = $this->db->select()
            ->from($this->db, ['*', $assocName . '.taskGuid', $assocName . '.segmentsUpdateable'])
            ->setIntegrityCheck(false)
            ->join($assocName, $assocName . '.`languageResourceId` = ' . $tableName . '.`id`', '')
            ->where($assocName . '.`taskGuid` IN (?)', $taskGuidList)
            ->where($tableName . '.serviceType IN(?)', $serviceTypes);

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * loads the language resources to a specific service resource ID (language resource to a specific server (=resource))
     * @return array
     */
    public function loadByResourceId(string $serviceResourceId)
    {
        $s = $this->db->select()->where('resourceId = ?', $serviceResourceId);

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * loads the language resources to a specific service resource ID (language resource to a specific server (=resource))
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByUuid(string $uuid): ?Zend_Db_Table_Row_Abstract
    {
        $s = $this->db->select()->where('langResUuid = ?', $uuid);
        $this->row = $this->db->fetchRow($s);
        if (empty($this->row)) {
            $this->notFound('#langResUuid ' . $uuid);
        }

        return $this->row;
    }

    /**
     * returns the resource used by this languageResource instance
     * @return editor_Models_LanguageResources_Resource
     * @throws ReflectionException
     * @throws editor_Services_Exceptions_NoService
     */
    public function getResource()
    {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $res = $manager->getResource($this);
        if (! empty($res)) {
            return $res;
        }

        throw new editor_Services_Exceptions_NoService('E1316', [
            'service' => $this->getServiceName(),
            'languageResource' => $this,
        ]);
    }

    /**
     * checks if the given languageResource (and segmentid - optional) is usable by the given task
     *
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    public function checkTaskAndLanguageResourceAccess(string $taskGuid, int $languageResourceId, editor_Models_Segment $segment = null)
    {
        //checks if the queried languageResource is associated to the task:
        $languageResourceTaskAssoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');

        /* @var $languageResourceTaskAssoc MittagQI\Translate5\LanguageResource\TaskAssociation */
        try {
            //for security reasons a service can only be queried when a valid task association exists and this task is loaded
            // that means the user has also access to the service. If not then not!
            $languageResourceTaskAssoc->loadByTaskGuidAndTm($taskGuid, $languageResourceId);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            throw new ZfExtended_Models_Entity_NoAccessException(null, null, $e);
        }

        if (is_null($segment)) {
            return;
        }

        //check taskGuid of segment against loaded taskguid for security reasons
        if ($taskGuid !== $segment->getTaskGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }

    /***
     * Load the exsisting langages for the initialized entity.
     * HINT: there may be multiple rows (-> termcollection) !
     * @param string $fieldName : field which will be returned
     * @throws ZfExtended_ValidateException
     * @return mixed
     */
    protected function getCachedLanguagesField($fieldName)
    {
        //check if the fieldName is defined
        if (empty($fieldName)) {
            throw new ZfExtended_ValidateException('Missing field name.');
        }

        if (! $this->getId()) {
            throw new ZfExtended_ValidateException('Entity id is not set.');
        }

        if (! isset($this->cachedLanguages) || count($this->cachedLanguages) === 0 || $this->cachedLanguages[0]['id'] != $this->getId()) {
            $model = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class);
            //load the existing languages from the languageresource languages table
            $this->cachedLanguages = $model->loadByLanguageResourceId((int) $this->getId());
        }
        if (count($this->cachedLanguages) === 1) {
            return $this->cachedLanguages[0][$fieldName];
        }

        return array_column($this->cachedLanguages, $fieldName);
    }

    /***
     * Get the source lang id values from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getSourceLang()
    {
        return $this->getCachedLanguagesField('sourceLang');
    }

    /***
     * Get the source lang code from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getSourceLangCode()
    {
        return $this->getCachedLanguagesField('sourceLangCode');
    }

    /**
     * Get the source lang name from the languageresource language table
     * @throws ZfExtended_ValidateException
     */
    public function getSourceLangName(): string|array
    {
        return $this->getCachedLanguagesField('sourceLangName');
    }

    /***
     * Get the target lang id values from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getTargetLang()
    {
        return $this->getCachedLanguagesField('targetLang');
    }

    /***
     * Get the target lang code from the languageresource language table.
     * Note: the enity id need to be valid
     * @return array|string
     */
    public function getTargetLangCode()
    {
        return $this->getCachedLanguagesField('targetLangCode');
    }

    /**
     * Get the target lang name from the languageresource language table
     * @throws ZfExtended_ValidateException
     */
    public function getTargetLangName(): string|array
    {
        return $this->getCachedLanguagesField('targetLangName');
    }

    /***
     * Get the customer ids of the current langauge resource, cached
     * @return array
     */
    public function getCustomers(): array
    {
        if (! array_key_exists($this->getId(), $this->customers)) {
            $model = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
            $this->customers[$this->getId()] = array_column($model->loadByLanguageResourceId($this->getId()), 'customerId');
        }

        return $this->customers[$this->getId()];
    }

    /**
     * creates and sets a random uuid
     */
    public function createLangResUuid()
    {
        $this->setLangResUuid(ZfExtended_Utils::uuid());
    }

    /**
     * Returns the categories that are assigned to the resource.
     * @return array
     */
    protected function getCategories()
    {
        $categoryAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');

        /* @var $categoryAssoc editor_Models_LanguageResources_CategoryAssoc */
        return $categoryAssoc->loadByLanguageResourceId($this->getId());
    }

    /**
     * Returns the original ids of the categories that are assigned to the resource.
     * @return array
     */
    public function getOriginalCategoriesIds()
    {
        $categories = $this->getCategories();
        $categoriesIds = array_column($categories, 'categoryId');
        $m = ZfExtended_Factory::get('editor_Models_Categories');
        /* @var $m editor_Models_Categories */
        $categoriesOriginalIds = [];
        foreach ($categoriesIds as $categoryId) {
            $m->load($categoryId);
            $categoriesOriginalIds[] = $m->getOriginalCategoryId();
        }

        return $categoriesOriginalIds;
    }

    /***
     * Is the current resource of type MT (maschine translation)
     * @return boolean
     */
    public function isMt()
    {
        return $this->getResourceType() === editor_Models_Segment_MatchRateType::TYPE_MT;
    }

    /***
     * Is the current resource type of TM (translation memory)
     * @return boolean
     */
    public function isTm()
    {
        return $this->getResourceType() === editor_Models_Segment_MatchRateType::TYPE_TM;
    }

    /***
     * Is the current resource type of term collection
     * @return boolean
     */
    public function isTc()
    {
        return $this->getResourceType() === editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION;
    }

    /**
     * Get termcollection export filename, that tbx-contents will be written to
     */
    public static function exportFilename($collectionId)
    {
        return editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir() . 'tc_' . $collectionId . '/export.tbx';
    }

    /**
     * Create [collectionId <=> dataTypeId] mappings set on term collection creation
     */
    public function onAfterInsert()
    {
        // If new termcollection was created
        if ($this->getResourceType() == 'termcollection') {
            // Create [collectionId <=> dataTypeId] mappings set
            ZfExtended_Factory
                ::get(CollectionAttributeDataType::class)
                    ->onTermCollectionInsert((int) $this->getId());
        }
    }

    /**
     * Retrieves a path to store language-resource data
     * @throws Zend_Exception
     */
    public function getAbsoluteDataPath(bool $createDirIfNotExists = false): string
    {
        if (! isset($this->absoluteDataPath) || ! str_ends_with($this->absoluteDataPath, strval($this->getId()))) {
            $config = Zend_Registry::get('config');
            $this->absoluteDataPath =
                $config->runtimeOptions->dir->languageResourceData
                . DIRECTORY_SEPARATOR . $this->getId();
        }
        if ($createDirIfNotExists && ! is_dir($this->absoluteDataPath)) {
            mkdir($this->absoluteDataPath, 0777, true);
        }

        return $this->absoluteDataPath;
    }

    public function delete()
    {
        $dataPath = $this->getAbsoluteDataPath();
        parent::delete();
        // we delete the data after the entity to avoid deletion for entities that cannot be deleted
        if (is_dir($dataPath)) {
            ZfExtended_Utils::recursiveDelete($dataPath);
        }
    }

    #region status change

    public function setStatus(string $status): void
    {
        // Do we need validation here?
        $this->addSpecificData(self::SPECIFIC_DATA_STATUS, $status);
    }

    public function getStatus(): ?string
    {
        return $this->getSpecificData(self::SPECIFIC_DATA_STATUS);
    }

    #endregion status change
}
