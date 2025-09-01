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

use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\ConversionState;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\ContentProtection\WhitespaceProtector;
use MittagQI\Translate5\Export\QueuedExportService;
use MittagQI\Translate5\LanguageResource\ActionAssert\LanguageResourceAction;
use MittagQI\Translate5\LanguageResource\ActionAssert\LanguageResourceActionPermissionAssert;
use MittagQI\Translate5\LanguageResource\CleanupAssociation\Customer;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\DTO\AssociationFormValues;
use MittagQI\Translate5\LanguageResource\Exception\ReimportQueueException;
use MittagQI\Translate5\LanguageResource\Operation\AssociateTaskOperation;
use MittagQI\Translate5\LanguageResource\Operation\DeleteLanguageResourceOperation;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use MittagQI\Translate5\Penalties\DataProvider\TaskPenaltyDataProvider;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\LanguageResourceTaskAssocRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\T5Memory\ExportMemoryWorker;
use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\Import\Defaults\LanguageResourcesDefaults;
use MittagQI\Translate5\Task\TaskContextTrait;
use MittagQI\ZfExtended\Controller\Response\Header;
use MittagQI\ZfExtended\Worker\Trigger\Factory as WorkerTriggerFactory;
use ZfExtended_Sanitizer as Sanitizer;

/***
 * Language resource controller
 */
class editor_LanguageresourceinstanceController extends ZfExtended_RestController
{
    use TaskContextTrait;

    public const FILE_UPLOAD_NAME = 'tmUpload';

    protected $entityClass = 'editor_Models_LanguageResources_LanguageResource';

    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $entity;

    /**
     * @var array
     */
    protected $groupedTaskInfo = [];

    /**
     * @var array
     */
    protected $uploadErrors = [];

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;

    /**
     * @var editor_Models_Categories
     */
    protected $categories;

    /**
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['import', 'download', 'export', 'xlsxexport', 'tbxexport', 'testexport'];

    private LanguageResourceActionPermissionAssert $languageResourceActionPermissionAssert;

    private UserRepository $userRepository;

    private ContentProtector $contentProtector;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function init()
    {
        //add filter type for languages
        $finalTableForAssoc = new ZfExtended_Models_Filter_Join('LEK_customer', 'name', 'id', 'customerId');
        $this->_filterTypeMap = [
            'sourceLang' => [
                'string' => 'list',
            ],
            'targetLang' => [
                'string' => 'list',
            ],
            'customerIds' => [
                'string' => new ZfExtended_Models_Filter_JoinAssoc('LEK_languageresources_customerassoc', $finalTableForAssoc, 'languageResourceId', 'id'),
            ],
        ];

        //set same join for sorting!
        $this->_sortColMap['customerIds'] = $this->_filterTypeMap['customerIds']['string'];
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->categories = ZfExtended_Factory::get('editor_Models_Categories');
        parent::init();

        $this->languageResourceActionPermissionAssert = LanguageResourceActionPermissionAssert::create();
        $this->userRepository = new UserRepository();
        $this->contentProtector = ContentProtector::create();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     * Adds the readonly "filebased" field to the results
     */
    public function indexAction()
    {
        //add custom filters
        $this->handleFilterCustom();

        // Should be called before load because of filters mutation inside
        $showTaskTm = $this->shouldShowTaskTms();

        $rows = $this->entity->loadAllByServices();

        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);
        $resources = [];
        $synchronizableServiceTypes = $serviceManager->getSynchronizableServiceTypes();

        $getResource = function (string $serviceType, string $id) use (&$resources, $serviceManager) {
            if (isset($resources[$id])) {
                return $resources[$id];
            }
            $resources[$id] = $serviceManager->getResourceById($serviceType, $id);

            return $resources[$id];
        };

        $languageResourcesIds = array_column($rows, 'id');
        $this->prepareTaskInfo($languageResourcesIds);

        $eventLogger = ZfExtended_Factory::get(editor_Models_Logger_LanguageResources::class);
        $eventLoggerGroupped = $eventLogger->getLatesEventsCount($languageResourcesIds);

        //get all assocs grouped by language resource id
        $customerAssocModel = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $custAssoc = $customerAssocModel->loadCustomerIdsGrouped();

        // for assigned categories
        $categoryAssocModel = ZfExtended_Factory::get(editor_Models_LanguageResources_CategoryAssoc::class);
        $categoryAssocs = $categoryAssocModel->loadCategoryIdsGrouped();

        $languages = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class);
        $languages = $languages->loadResourceIdsGrouped();
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        $tmConversionService = TmConversionService::create();

        $filterTmNeedsConversion = $this->getParam('filterTmNeedsConversion', false);

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $context = new PermissionAssertContext($authUser);
        $languageResource = new editor_Models_LanguageResources_LanguageResource();

        foreach ($rows as $rowId => &$lrData) {
            if (! $showTaskTm && true === (bool) $lrData['isTaskTm']) {
                unset($rows[$rowId]);

                continue;
            }

            $languageResource->init($lrData);

            $isGranted = $this->languageResourceActionPermissionAssert->isGranted(
                LanguageResourceAction::Read,
                $languageResource,
                $context
            );

            if (! $isGranted) {
                unset($rows[$rowId]);

                continue;
            }

            $resource = $getResource($lrData['serviceType'], $lrData['resourceId']);
            /* @var editor_Models_LanguageResources_Resource $resource */
            if (! empty($resource)) {
                $lrData = array_merge($lrData, $resource->getMetaData());
            }

            $id = $lrData['id'];
            $lrData['serviceName'] = $serviceManager->getNameByType($lrData['serviceType']);
            $lrData['synchronizableService'] = in_array($lrData['serviceType'], $synchronizableServiceTypes, true);

            $lrData['tmConversionState'] = null;

            if (editor_Services_Manager::SERVICE_OPENTM2 === $lrData['serviceType']) {
                $lrData['tmConversionState'] = $tmConversionService->getConversionState($id)->value;
            }

            if ($filterTmNeedsConversion &&
                (
                    null === $lrData['tmConversionState'] ||
                    ConversionState::Converted->value === $lrData['tmConversionState']
                )
            ) {
                unset($rows[$rowId]);

                continue;
            }

            // translate the "specificDta" field for the frontend and store the unserialized data
            $specificData = $this->prepareSpecificData($lrData, true);
            $languageResourceInstance = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
            $languageResourceInstance->init($lrData);

            $lrData['taskList'] = $this->getTaskInfos($lrData['id']);

            if (empty($resource)) {
                $lrData['status'] = LanguageResourceStatus::ERROR;
                $lrData['statusInfo'] = $translate->_('Die verwendete Resource wurde aus der Konfiguration entfernt.');
            } else {
                // retrieves an assoc with 'status' and 'statusInfo' keys
                foreach ($resource->getInitialStatus($specificData, (int) $lrData['id'], $translate) as $key => $value) {
                    $lrData[$key] = $value;
                }
            }

            //add customer assocs
            $lrData['customerIds'] = $this->getCustassoc($custAssoc, 'customerId', $id);
            $lrData['customerUseAsDefaultIds'] = $this->getCustassocByIndex($custAssoc, 'useAsDefault', $id);
            $lrData['customerWriteAsDefaultIds'] = $this->getCustassocByIndex($custAssoc, 'writeAsDefault', $id);
            $lrData['customerPivotAsDefaultIds'] = $this->getCustassocByIndex($custAssoc, 'pivotAsDefault', $id);

            $lrData['sourceLang'] = $this->getLanguage($languages, 'sourceLang', $id);
            $lrData['targetLang'] = $this->getLanguage($languages, 'targetLang', $id);

            // categories (for the moment: just display labels for info, no editing)
            $categoryLabels = [];
            foreach ($this->getCategoryassoc($categoryAssocs, 'categoryId', $id) as $categoryId) {
                $categoryLabels[] = $this->renderCategoryCustomLabel($categoryId);
            }
            $lrData['categories'] = $categoryLabels;
            $lrData['eventsCount'] = isset($eventLoggerGroupped[$id]) ? (int) $eventLoggerGroupped[$id] : 0;
            $lrData['isTaskTm'] = (bool) $lrData['isTaskTm'];
        }

        $this->view->rows = array_values($rows);
        $this->view->total = count($rows);
    }

    public function defaulttmneedsconversionAction(): void
    {
        $postData = $this->getAllParams();
        $tmConversionService = TmConversionService::create();

        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);

        $data = $customerAssoc->loadByCustomerIdsUseAsDefault([$postData['customerId']]);

        $languageResourcesDefaults = new LanguageResourcesDefaults(
            new LanguageResourceRepository(),
            AssociateTaskOperation::create(),
        );

        $iterator = $languageResourcesDefaults->findMatchingAssocData(
            $postData['sourceId'],
            $postData['targetId'],
            $data
        );

        $has = false;
        foreach ($iterator as $data) {
            if ($data['resourceType'] !== 'tm') {
                continue;
            }

            if (! $tmConversionService->isTmConverted($data['languageResourceId'])) {
                $has = true;

                break;
            }
        }

        $this->view->result = [
            'hasLangResThatNeedsConversion' => $has,
        ];
        $this->view->success = true;
    }

    public function synchronizetmAction(): void
    {
        $postData = $this->getAllParams();
        $tmConversionService = TmConversionService::create();

        $this->view->success = true;

        if (ConversionState::NotConverted !== $tmConversionService->getConversionState($postData['id'])) {
            return;
        }

        $tmConversionService->scheduleConversion($postData['id']);
    }

    public function synchronizetmbatchAction(): void
    {
        $postData = $this->getAllParams();
        $tmConversionService = TmConversionService::create();

        $this->view->success = true;

        foreach ($postData['data'] as $resource) {
            if (ConversionState::NotConverted !== $tmConversionService->getConversionState((int) $postData)) {
                continue;
            }

            $tmConversionService->scheduleConversion((int) $resource);
        }
    }

    /**
     * Retrieves specific language from the given language container
     * @param string $index the datafield to get
     * @param int $id the language resource id
     * @return array
     */
    protected function getLanguage(array $languages, $index, $id)
    {
        if (empty($languages[$id]) || empty($languages[$id][$index])) {
            return [];
        }

        return $languages[$id][$index];
    }

    /**
     * Retrieves specific data from the given data container
     * @param string $index the datafield to get
     * @param int $id the language resource id
     * @return array
     */
    protected function getCustassoc(array $data, $index, $id)
    {
        if (empty($data[$id])) {
            return [];
        }

        //remove 0 and null values
        return array_filter(array_column($data[$id], $index));
    }

    /**
     * Retrieves specific data from the given data container
     * @param string $index the datafield to get
     * @param int $id the language resource id
     * @return array
     */
    protected function getCategoryassoc(array $data, $index, $id)
    {
        if (empty($data[$id])) {
            return [];
        }

        //remove 0 and null values
        return array_filter(array_column($data[$id], $index));
    }

    /***
     * Returns customer assoc active flag fields (useAsDefault,writeAsDefault or pivotAsDefault) for given customer
     * assoc data and give language resource id
     *
     * @param array $data
     * @param string $index the datafield to get
     * @param int $id the language resource id
     * @return array : filtered customer ids
     */
    protected function getCustassocByIndex(array $data, $index, $id)
    {
        if (empty($data[$id])) {
            return [];
        }
        // get the active flag indexes array indexes
        $default = $this->getCustassoc($data, $index, $id);
        $customerIds = [];
        //get the customer ids for those array indexes
        foreach ($default as $key => $value) {
            $customerIds[] = $data[$id][$key]['customerId'];
        }

        return $customerIds;
    }

    /**
     * Renders the label of a category with details (currently: original Id).
     * @param integer $categoryId
     * @return string
     */
    protected function renderCategoryCustomLabel($categoryId)
    {
        $this->categories->load($categoryId);

        return $this->categories->getLabel() . ' (' . $this->categories->getOriginalCategoryId() . ')';
    }

    /**
     * Adds status information to the get request
     * {@inheritDoc}
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction()
    {
        parent::getAction();

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $context = new PermissionAssertContext($authUser);

        $this->languageResourceActionPermissionAssert->assertGranted(
            LanguageResourceAction::Read,
            $this->entity,
            $context
        );

        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);

        $this->addAssocData();

        $t = ZfExtended_Zendoverwrites_Translate::getInstance();

        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());

        if ($resource === null) {
            $this->view->rows->status = LanguageResourceStatus::NOCONNECTION;
            $this->view->rows->statusInfo = $t->_('Keine Verbindung zur Ressource oder Ressource nicht gefunden.');

            return;
        }

        $meta = $resource->getMetaData();
        foreach ($meta as $key => $v) {
            $this->view->rows->{$key} = $v;
        }

        /** @phpstan-ignore-next-line */
        $this->view->rows->serviceName = $serviceManager->getNameByType($this->view->rows->serviceType);

        $eventLogger = ZfExtended_Factory::get(editor_Models_Logger_LanguageResources::class);
        $eventLoggerGroupped = $eventLogger->getLatesEventsCount([$this->entity->getId()]);
        $this->view->rows->eventsCount =
            isset($eventLoggerGroupped[$this->entity->getId()]) ? (int) $eventLoggerGroupped[$this->entity->getId()] : 0;

        $connector = $this->getConnector();

        $this->view->rows->status = $connector->getStatus($this->entity->getResource(), $this->entity);
        $this->view->rows->statusInfo = $t->_($connector->getLastStatusInfo());

        $languages = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class);
        $languages = $languages->loadResourceIdsGrouped($this->entity->getId());

        $this->view->rows->sourceLang = $this->getLanguage($languages, 'sourceLang', (int) $this->entity->getId());
        $this->view->rows->targetLang = $this->getLanguage($languages, 'targetLang', (int) $this->entity->getId());

        $this->prepareSpecificData($this->view->rows, false);
    }

    private ?editor_Models_Customer_Customer $currentCustomer = null;

    private function getSingleAssociatedCustomer(): ?editor_Models_Customer_Customer
    {
        if ($this->currentCustomer !== null) {
            return $this->currentCustomer;
        }

        $auth = ZfExtended_Authentication::getInstance();
        $customerIds = $auth->getUser()->getCustomersArray();

        // We use the customer config if only one customer is set for the user
        if (1 === count($customerIds)) {
            $this->currentCustomer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
            $this->currentCustomer->load($customerIds[0]);

            return $this->currentCustomer;
        }

        return null;
    }

    /**
     * Adds associated data to the result object
     */
    protected function addAssocData()
    {
        $this->prepareTaskInfo([$this->entity->getId()]);
        $this->view->rows->taskList = $this->getTaskInfos($this->entity->getId());

        //load associated customers to the resource
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */

        $customerAssocs = $customerAssoc->loadByLanguageResourceId($this->entity->getId());
        // all assoc customers with customerId as key and useAsDefault flag as value
        $useAsDefault = array_column($customerAssocs, 'useAsDefault', 'customerId');

        $this->view->rows->customerIds = array_keys($useAsDefault);
        // filter out all useAsDefault with value 0
        $this->view->rows->customerUseAsDefaultIds = array_keys(array_filter($useAsDefault));

        // Filter out writable as default from use as default array. If assoc is writeAsDefault it must be useAsDefault to.
        $writeAsDefault = array_column($customerAssocs, 'writeAsDefault', 'customerId');
        $this->view->rows->customerWriteAsDefaultIds = array_keys(array_filter($writeAsDefault));

        // Filter out pivot as default from use as default array. If assoc is pivotAsDefault it must be useAsDefault to.
        $pivotAsDefault = array_column($customerAssocs, 'pivotAsDefault', 'customerId');
        $this->view->rows->customerPivotAsDefaultIds = array_keys(array_filter($pivotAsDefault));

        // categories that are assigned to the resource
        $categoryIds = [];
        $categoryAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');
        /* @var $categoryAssoc editor_Models_LanguageResources_CategoryAssoc */
        $categoryAssocs = $categoryAssoc->loadByLanguageResourceId($this->entity->getId());
        $categoryIds = array_column($categoryAssocs, 'categoryId');
        // for the moment: just display labels for info
        $categoryLabels = [];
        foreach ($categoryIds as $categoryId) {
            $categoryLabels[] = $this->renderCategoryCustomLabel($categoryId);
        }
        $this->view->rows->categories = $categoryLabels;
    }

    /***
     * Handle custom filtering in source,target,taskList and customerIds.
     * The filters are extended so they can filter using string values.
     * TODO: can this be moved/implemented as assoc fiter ?
     */
    protected function handleFilterCustom()
    {
        $sourceFilter = null;
        $targetFilter = null;
        $useAsDefault = null;
        $writeAsDefault = null;
        $pivotAsDefault = null;
        $taskList = null;

        $this->entity->getFilter()->hasFilter('sourceLang', $sourceFilter);
        $this->entity->getFilter()->hasFilter('targetLang', $targetFilter);

        $this->entity->getFilter()->hasFilter('customerUseAsDefaultIds', $useAsDefault);
        $this->entity->getFilter()->hasFilter('customerWriteAsDefaultIds', $writeAsDefault);
        $this->entity->getFilter()->hasFilter('customerPivotAsDefaultIds', $pivotAsDefault);
        $this->entity->getFilter()->hasFilter('taskList', $taskList);

        //search the model for the filter value and set the filter value with the found matches(ids)
        $searchEntity = function ($searchValue, $model, $field = 'id') {
            if (is_array($searchValue)) {
                return $searchValue;
            }

            //search the model for the given search string
            $m = ZfExtended_Factory::get($model);
            $result = $m->search($searchValue ?? '', [$field]);

            //collect the found $fields in the searched model
            $ids = array_column($result, $field);

            //return the result, if now results are found->return -1 as array (this will produce no result in the filter)
            return ! empty($ids) ? $ids : [-1];
        };

        //create an languageResources id filter, from the found results in the searched entity
        $handleFilter = function ($filter, $resultList, $assocModel, $assocFunction, $assocField) {
            //init the filter
            $idFilter = new stdClass();
            $idFilter->type = 'list';
            $idFilter->field = 'id';
            $idFilter->table = 'LEK_languageresources';
            $idFilter->comparison = 'in';

            //if no ids are found, set the filter so no results are returned
            if (empty($resultList)) {
                //remove the filter since the colum does not exist in the table
                $this->entity->getFilter()->deleteFilter($filter->field);
                $idFilter->value = [-1];
                $this->entity->getFilter()->addFilter($idFilter);

                return;
            }

            //for all matching results find the assoc ids
            $m = ZfExtended_Factory::get($assocModel);
            $result = $m->$assocFunction($resultList);

            //if no language resources for the customers are found, set the filter
            if (empty($result)) {
                //remove the filter since the colum does not exist in the table
                $this->entity->getFilter()->deleteFilter($filter->field);
                $idFilter->value = [-1];
                $this->entity->getFilter()->addFilter($idFilter);

                return;
            }

            //for each result, get the assoc field
            $resids = array_column($result, $assocField);

            $resids = array_unique($resids);

            //set the found values to the filter value, and apply the filter
            $idFilter->value = ! empty($resids) ? $resids : [-1];
            $this->entity->getFilter()->addFilter($idFilter);

            //remove the filter since the colum does not exist in the table
            $this->entity->getFilter()->deleteFilter($filter->field);
        };

        //check and handle the sourceLang filter
        if (isset($sourceFilter)) {
            $resultList = $searchEntity($sourceFilter->value, 'editor_Models_Languages');
            $handleFilter($sourceFilter, $resultList, 'editor_Models_LanguageResources_Languages', 'loadBySourceLangIds', 'languageResourceId');
        }

        //check and handle the targetLang filter
        if (isset($targetFilter)) {
            $resultList = $searchEntity($targetFilter->value, 'editor_Models_Languages');
            $handleFilter($targetFilter, $resultList, 'editor_Models_LanguageResources_Languages', 'loadByTargetLangIds', 'languageResourceId');
        }

        //check if filtering for useAsDefault should be done
        if (isset($useAsDefault)) {
            if (isset($useAsDefault->value) && is_string($useAsDefault->value)) {
                $resultList = $searchEntity($useAsDefault->value, 'editor_Models_Customer_Customer');
                $handleFilter($useAsDefault, $resultList, 'editor_Models_LanguageResources_CustomerAssoc', 'loadByCustomerIdsUseAsDefault', 'languageResourceId');
            } else {
                $this->entity->getFilter()->deleteFilter('customerUseAsDefaultIds');
            }
        }

        //check if filtering for writeAsDefault should be done
        if (isset($writeAsDefault)) {
            if (isset($writeAsDefault->value) && is_string($writeAsDefault->value)) {
                $resultList = $searchEntity($writeAsDefault->value, 'editor_Models_Customer_Customer');
                $handleFilter($writeAsDefault, $resultList, 'editor_Models_LanguageResources_CustomerAssoc', 'loadByCustomerIdsWriteAsDefault', 'languageResourceId');
            } else {
                $this->entity->getFilter()->deleteFilter('customerWriteAsDefaultIds');
            }
        }

        //check if filtering for writeAsDefault should be done
        if (isset($pivotAsDefault)) {
            if (isset($pivotAsDefault->value) && is_string($pivotAsDefault->value)) {
                $resultList = $searchEntity($pivotAsDefault->value, 'editor_Models_Customer_Customer');
                $handleFilter($pivotAsDefault, $resultList, 'editor_Models_LanguageResources_CustomerAssoc', 'loadByCustomerIdsPivotAsDefault', 'languageResourceId');
            } else {
                $this->entity->getFilter()->deleteFilter('customerPivotAsDefaultIds');
            }
        }

        //check if filtering for taskList should be done
        if (isset($taskList)) {
            if (isset($taskList->value) && is_string($taskList->value)) {
                $resultList = $searchEntity($taskList->value, 'editor_Models_Task', 'taskGuid');
                $handleFilter($taskList, $resultList, 'MittagQI\Translate5\LanguageResource\TaskAssociation', 'loadByTaskGuids', 'languageResourceId');
            } else {
                $this->entity->getFilter()->deleteFilter('taskList');
            }
        }
    }

    /**
     * returns the logged events for the given language resource
     */
    public function eventsAction()
    {
        $this->getAction();

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $context = new PermissionAssertContext($authUser);

        $this->languageResourceActionPermissionAssert->assertGranted(
            LanguageResourceAction::Read,
            $this->entity,
            $context
        );

        $events = ZfExtended_Factory::get('editor_Models_Logger_LanguageResources');
        /* @var $events editor_Models_Logger_LanguageResources */

        //filter and limit for events entity
        $offset = $this->_getParam('start');
        $limit = $this->_getParam('limit');
        settype($offset, 'integer');
        settype($limit, 'integer');
        $events->limit(max(0, $offset), $limit);

        $filter = ZfExtended_Factory::get($this->filterClass, [
            $events,
            $this->getRequest()->getRawParam('filter'),
        ]);

        $filter->setSort($this->_getParam('sort', '[{"property":"id","direction":"DESC"}]'));
        $events->filterAndSort($filter);

        $this->view->rows = $events->loadByLanguageResourceId($this->entity->getId());
        $this->view->total = $events->getTotalByLanguageResourceId($this->entity->getId());
    }

    private function prepareTaskInfo($languageResourceids)
    {
        $assocs = ZfExtended_Factory::get(TaskAssociation::class);
        $tasksInfo = $assocs->getTaskInfoForLanguageResources($languageResourceids);

        $assocs = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $tasksPivotInfo = $assocs->getTaskInfoForLanguageResources($languageResourceids);

        $result = array_merge($tasksInfo, $tasksPivotInfo);
        $result = $this->convertTasknames($result);
        $this->groupedTaskInfo = $result;
    }

    /**
     * receives a list of task and task assoc data, returns a list of taskNames grouped by languageResource
     * @return string[]
     */
    protected function convertTasknames(array $taskInfoList)
    {
        $result = [];
        foreach ($taskInfoList as $taskInfo) {
            if (! isset($result[$taskInfo['languageResourceId']])) {
                $result[$taskInfo['languageResourceId']] = [];
            }

            $taskToPrint = $taskInfo['taskName'];
            $isPivot = str_contains(strtolower($taskInfo['tableName']), 'pivot');

            if (! empty($taskInfo['taskNr'])) {
                $taskToPrint .= ' (' . $taskInfo['taskNr'] . ')';
            }

            if ($isPivot) {
                $taskToPrint .= ' (Pivot)';
            }

            if ($taskInfo['state'] === editor_Models_Task::STATE_IMPORT) {
                $taskToPrint .= ' - importing';
            }

            $result[$taskInfo['languageResourceId']][] = $taskToPrint;
        }

        return $result;
    }

    /***
     * return array with task info (taskName's) for the given languageResourceids
     */
    private function getTaskInfos($languageResourceid)
    {
        if (empty($this->groupedTaskInfo[$languageResourceid])) {
            return [];
        }

        return $this->groupedTaskInfo[$languageResourceid];
    }

    /**
     * provides the uploaded file in a filebased TM as download
     *
     * This method is very opentm2 specific. If we want more generalization:
     *  - JS needs to know about the valid export types of the requested TM system
     *  - The Connector must be able to decide if a given type can be exported or not
     */
    public function downloadAction()
    {
        $this->entityLoad();

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $context = new PermissionAssertContext($authUser);

        $this->languageResourceActionPermissionAssert->assertGranted(
            LanguageResourceAction::Read,
            $this->entity,
            $context
        );

        //get type from extension, the part between :ID and extension does not matter
        $type = $this->getParam('type', '.tm');
        $type = explode('.', $type);
        $type = strtoupper(end($type));

        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */

        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());

        if (! $resource->getFilebased()) {
            throw new ZfExtended_Models_Entity_NotFoundException('Requested languageResource is not filebased!');
        }

        $connector = $this->getConnector();
        $validExportTypes = $connector->getValidExportTypes();

        if (empty($validExportTypes[$type])) {
            throw new ZfExtended_Models_Entity_NotFoundException('Can not download in format ' . $type);
        }

        $exportService = QueuedExportService::create();

        $token = ZfExtended_Utils::uuid();
        $workerId = ExportMemoryWorker::queueExportWorker(
            $this->entity,
            $validExportTypes[$type],
            $exportService->composeExportDir($token)
        );

        $exportService->makeQueueRecord($token, $workerId, $this->entity->getName());

        $title = 'TMX' === $type
            ? $this->view->translate('Als TMX-Datei herunterladen und lokal speichern')
            : $this->view->translate('Dateibasiertes TM herunterladen und lokal speichern');

        $this->redirect("/editor/queuedexport/$token?title=$title");
    }

    public function postAction()
    {
        $this->entity->init();
        $this->data = $this->getAllParams(); //since its a fileupload, this is a normal POST
        $this->setDataInEntity($this->postBlacklist);
        $this->entity->createLangResUuid();

        $manager = ZfExtended_Factory::get(editor_Services_Manager::class);
        $resource = $manager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());

        if ($resource && ! $resource->getCreatable()) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1041', [
                'Sprachressource des ausgewählten Ressourcentyps kann in der Benutzeroberfläche nicht erstellt werden.',
            ]);
        }

        $sourceLangId = $this->getParam('sourceLang');
        $targetLangId = $this->getParam('targetLang');

        $validLanguages = $this->validateLanguages($resource, $sourceLangId, $targetLangId);

        if (! $validLanguages || ! $this->validate()) {
            return;
        }

        $sourceLangCode = null;
        $sourceLangName = null;
        $targetLangCode = null;
        $targetLangName = null;
        //find the language codes for the current resource
        //in each resource separate language code matching should be introduced
        //because some of the resources are supporting different type of language codes
        //rfc as a language code will be used when no custom matching is implemented for the resource
        if (! empty($sourceLangId)) {
            $sourceLangCode = $resource->getLanguageCodeSource($sourceLangId);
            $sourceLangName = $resource->getLanguageNameSource($sourceLangId);
        }
        if (! empty($targetLangId)) {
            $targetLangCode = $resource->getLanguageCodeTarget($targetLangId);
            $targetLangName = $resource->getLanguageNameTarget($targetLangId);
        }

        //set the entity resource type from the $resource
        $this->entity->setResourceType($resource->getType());

        //save first to generate the languageResource id
        $this->data['id'] = $this->entity->save();

        // especially tests are not respecting the array format ...
        editor_Utils::ensureFieldsAreArrays($this->data, ['customerIds', 'customerUseAsDefaultIds', 'customerWriteAsDefaultIds', 'customerPivotAsDefaultIds']);

        try {
            CustomerAssocService::create()->updateAssociations(
                AssociationFormValues::fromArray(
                    (int) $this->entity->getId(),
                    $this->data
                )
            );
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            DeleteLanguageResourceOperation::create()->delete(
                $this->entity,
                forced: true,
            );

            throw $e;
        }

        //check and save categories assoc db entry
        $categoryAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CategoryAssoc');

        /* @var editor_Models_LanguageResources_CategoryAssoc $categoryAssoc */
        try {
            $categoryAssoc->saveAssocRequest($this->data);
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            DeleteLanguageResourceOperation::create()->delete(
                $this->entity,
                forced: true,
            );

            throw $e;
        }

        //save the resource languages to
        $resourceLanguages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var editor_Models_LanguageResources_Languages $resourceLanguages */
        $resourceLanguages->setSourceLang($sourceLangId);
        $resourceLanguages->setSourceLangCode($sourceLangCode);
        $resourceLanguages->setSourceLangName($sourceLangName);
        $resourceLanguages->setTargetLang($targetLangId);
        $resourceLanguages->setTargetLangCode($targetLangCode);
        $resourceLanguages->setTargetLangName($targetLangName);
        $resourceLanguages->setLanguageResourceId($this->data['id']);
        if (! empty($sourceLangId) || ! empty($targetLangId)) {
            $resourceLanguages->save();
        }

        if ($resource->getFilebased()) {
            try {
                $this->handleInitialFileUpload();
            } catch (ZfExtended_ErrorCodeException $e) {
                DeleteLanguageResourceOperation::create()->delete(
                    $this->entity,
                    forced: true,
                );

                throw $e;
            }

            //when there are errors, we cannot set it to true
            if (! $this->validateUpload()) {
                DeleteLanguageResourceOperation::create()->delete(
                    $this->entity,
                    forced: true,
                );

                return;
            }
            //save again to save changes made by the connector
            $this->entity->save();
        }

        try {
            $manager->getConnector($this->entity)->processAfterCreation();
        } catch (ZfExtended_Exception $e) {
            DeleteLanguageResourceOperation::create()->delete(
                $this->entity,
                forced: true,
            );

            throw $e;
        }

        $manager->getTmConversionService($resource->getServiceType())?->setRulesHash(
            $this->entity,
            $sourceLangId,
            $targetLangId
        );

        $this->view->rows = $this->entity->getDataObject();
        $this->view->success = true;
    }

    public function putAction()
    {
        $this->decodePutAssociative = true;

        $this->entityLoad();

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $context = new PermissionAssertContext($authUser);

        $this->languageResourceActionPermissionAssert->assertGranted(
            LanguageResourceAction::Read,
            $this->entity,
            $context
        );

        $this->decodePutData();

        // UGLY/QUIRK: client-restricted PMs may save languageresources, that contain assocs to customers, the PMs cannot see in the frontend and they have no rights to remove.
        // we fix that here by re-adding them
        if (ZfExtended_Authentication::getInstance()->isUserClientRestricted()) {
            $this->transformClientRestrictedCustomerAssocs();
        }

        $this->checkOrCleanCustomerAssociation(
            (bool) $this->getParam('forced', false),
            $this->getDataField('customerIds') ?? []
        );

        $this->processClientReferenceVersion();
        $this->setDataInEntity();

        if ($this->validate()) {
            $this->entity->save();
            $this->view->rows = $this->entity->getDataObject();

            // especially tests are not respecting the array format ...
            editor_Utils::ensureFieldsAreArrays($this->data, ['customerIds', 'customerUseAsDefaultIds', 'customerWriteAsDefaultIds', 'customerPivotAsDefaultIds']);

            CustomerAssocService::create()->updateAssociations(
                AssociationFormValues::fromArray(
                    (int) $this->entity->getId(),
                    $this->data
                )
            );

            $this->addAssocData();
        }
    }

    /**
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData()
    {
        parent::decodePutData();
        unset($this->data->langResUuid, $this->data->specificId);
    }

    /**
     * Imports an additional file which is transferred to the desired languageResource
     */
    public function importAction()
    {
        $this->getAction();

        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);

        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());

        if (! $resource->getFilebased()) {
            throw new ZfExtended_ValidateException('Requested languageResource is not filebased!');
        }

        $taskAssocRepository = LanguageResourceTaskAssocRepository::create();

        if ($taskAssocRepository->hasImportingAssociatedTasks((int) $this->entity->getId())) {
            throw new ZfExtended_ValidateException('Language resource has associated task that is currently importing');
        }

        //upload errors are handled in handleAdditionalFileUpload
        $this->handleAdditionalFileUpload();

        //when there are errors, we cannot set it to true
        $this->view->success = $this->validateUpload();
    }

    public function tbxexportAction()
    {
        // Load utils
        class_exists('editor_Utils');

        // Get params
        $params = $this->getRequest()->getParams();

        // Check params
        editor_Utils::jcheck([
            'collectionId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'LEK_languageresources',
            ],
            'tbxBasicOnly' => [
                'req' => true,
                'fis' => '0,1',
            ],
            'exportImages' => [
                'req' => true,
                'fis' => '0,tbx,zip',
            ],
        ], $params);

        // Turn off limitations?
        ignore_user_abort(1);
        set_time_limit(0);

        // Export collection
        ZfExtended_Factory::get(editor_Models_Export_Terminology_Tbx::class)->exportCollectionById(
            $params['collectionId'],
            (new Zend_Session_Namespace('user'))->data->userName,
            $params['tbxBasicOnly'],
            $params['exportImages']
        );
    }

    public function exportAction()
    {
        $proposals = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $proposals editor_Models_Terminology_Models_TermModel */

        $collectionIds = $this->getParam('collectionId');
        if (is_string($collectionIds)) {
            $collectionIds = explode(',', $collectionIds);
        }
        $termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termCollection editor_Models_TermCollection_TermCollection */
        $allowedCollections = $termCollection->getCollectionForAuthenticatedUser();
        $rows = $proposals->loadProposalExportData(array_intersect($collectionIds, $allowedCollections), $this->getParam('exportDate', ''));
        if (empty($rows)) {
            $this->view->message = 'No results where found.';

            return;
        }
        $proposals->exportProposals($rows);
    }

    public function xlsxexportAction()
    {
        // Load utils
        class_exists('editor_Utils');

        // Get params
        $params = $this->getRequest()->getParams();

        // Check params
        $_ = editor_Utils::jcheck([
            'collectionId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'LEK_languageresources',
            ],
        ], $params);

        // Turn off time limit
        set_time_limit(0);

        // Export collection
        /** @var editor_Models_Export_Terminology_Xlsx $xlsx */
        $xlsx = ZfExtended_Factory::get('editor_Models_Export_Terminology_Xlsx');

        // If session's 'download' flag is set
        if ($_SESSION['download'] ?? false) {
            // Build file path
            $file = $xlsx->file($_['collectionId']['id']);

            // Unset session's download flag
            unset($_SESSION['download']);

            // Set up headers
            Header::sendDownload(
                rawurlencode($_['collectionId']['name']) . '.xlsx',
                'text/xml',
                'no-cache',
                -1,
                [
                    'X-Accel-Buffering' => 'no',
                ]
            );

            // Flush the entire file
            readfile($file);

            // Delete the file
            unlink($file);

            // Exit
            exit;
        }

        // Do export
        $xlsx->exportCollectionById($params['collectionId']);
    }

    /***
     * This is used for the tests. It will return the proposals for the current date and for the
     * assigned collections of the customers of the authenticated user
     */
    public function testexportAction()
    {
        $proposals = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $proposals editor_Models_Terminology_Models_TermModel */
        $termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termCollection editor_Models_TermCollection_TermCollection */
        $collectionIdA = $termCollection->getCollectionForAuthenticatedUser();
        if ($required = $this->getParam('collectionId')) {
            $required = is_array($required) ? $required : explode(',', $required);
            $collectionIdA = array_intersect($collectionIdA, $required);
        }
        $this->view->rows = $proposals->loadProposalExportData($collectionIdA, date('Y-m-d'));
    }

    /**
     * Loads all task information entities for the given languageResource
     * The returned data is no real task entity, although the task model is used in the frontend!
     */
    public function tasksAction(): void
    {
        try {
            $this->getAction();
        } catch (editor_Services_Connector_Exception $e) {
            $e->addExtraData([
                'languageResource' => $this->entity,
            ]);

            throw $e;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->decodePutData();
            if (! empty($this->data) && ! empty($this->data->toReImport)) {
                foreach ($this->data->toReImport as $taskGuid) {
                    try {
                        (new ReimportSegmentsQueue())->queueSnapshot(
                            $taskGuid,
                            $this->entity->getId(),
                            [
                                ReimportSegmentsOptions::FILTER_ONLY_EDITED => $this->data->onlyEdited,
                                ReimportSegmentsOptions::USE_SEGMENT_TIMESTAMP => $this->data->timeOption === 'segment',
                            ]
                        );
                    } catch (ReimportQueueException) {
                        throw new ZfExtended_Exception('LanguageResource ReImport Error on worker init()');
                    }
                }
            }
        }

        $assoc = ZfExtended_Factory::get(TaskAssociation::class);
        $taskinfo = $assoc->getTaskInfoForLanguageResources([$this->entity->getId()]);
        //FIXME replace lockingUser guid with concrete username and show it in the frontend!
        $this->view->rows = $taskinfo;
        $this->view->total = count($taskinfo);
    }

    /**
     * Validates if choosen languages can be used by the choosen resource
     * Validates also the existence of the languages in the Lang DB
     * @param mixed $sourceLang
     * @param mixed $targetLang
     * @return boolean
     */
    protected function validateLanguages(editor_Models_LanguageResources_Resource $resource, &$sourceLang, &$targetLang): bool
    {
        //when termcollection is a resource, the languages are handled by the tbx import
        if ($resource instanceof editor_Services_TermCollection_Resource) {
            return true;
        }

        $hasSourceLang = $resource->hasSourceLang($this->_helper->Api->convertLanguageParameters($sourceLang));
        $hasTargetLang = $resource->hasTargetLang($this->_helper->Api->convertLanguageParameters($targetLang));

        //both languages can be dealed by the resource, all OK
        if ($hasSourceLang && $hasTargetLang) {
            return true;
        }

        $errors = [];
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */

        if (! $hasSourceLang) {
            $errors['sourceLang'] = $t->_('Diese Quellsprache wird von der Ressource nicht unterstützt!');
        }
        if (! $hasTargetLang) {
            $errors['targetLang'] = $t->_('Diese Zielsprache wird von der Ressource nicht unterstützt!');
        }

        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->handleValidateException($e);

        return false;
    }

    /**
     * Uploads a file into the new languageResource
     */
    protected function handleInitialFileUpload()
    {
        $connector = $this->getConnector();

        if (! $connector->ping($this->entity->getResource(), $this->getConfig())) {
            throw ZfExtended_UnprocessableEntity::createResponse(
                'E1282',
                ['Server für den angefragten Dienst ist nicht erreichbar.']
            );
        }

        $importInfo = $this->handleFileUpload($connector);

        if (! empty($this->uploadErrors)) {
            return;
        }

        //setting the TM filename here, but can be overwritten in the connectors addTm method
        // for example when we get a new name from the service
        if (is_array($importInfo) && isset($importInfo[self::FILE_UPLOAD_NAME]['name'])) {
            $filename = $importInfo[self::FILE_UPLOAD_NAME]['name'];
        } else {
            $filename = '';
        }
        $this->entity->addSpecificData('fileName', $filename);

        $this->queueServiceImportWorker($importInfo ?? [], true);
    }

    /**
     * Uploads an additional file into the already existing languageResource
     */
    protected function handleAdditionalFileUpload()
    {
        $connector = $this->getConnector();

        $status = $connector->getStatus($this->entity->getResource());

        if (in_array($status, [LanguageResourceStatus::IMPORT, LanguageResourceStatus::CONVERTING], true)) {
            throw new ZfExtended_ValidateException('Language resource status is not valid for import');
        }

        $importInfo = $this->handleFileUpload($connector);

        if (empty($importInfo)) {
            $this->uploadErrors[] = 'Keine Datei hochgeladen!';

            return;
        }

        if (! empty($this->uploadErrors)) {
            return;
        }

        $this->queueServiceImportWorker($importInfo, false);
    }

    /**
     * handles the fileupload
     * @return array|null meta data about the upload or null when there was no file
     * @throws Zend_File_Transfer_Exception
     * @throws Zend_Validate_Exception
     */
    protected function handleFileUpload(editor_Services_Connector $connector): ?array
    {
        $upload = new Zend_File_Transfer_Adapter_Http();

        //check if connector / resource can deal with the uploaded file type
        $validTypes = $connector->getValidFiletypes();
        $validMimeType = array_values($validTypes);
        $validExtension = array_keys($validTypes);

        // =============== workaround (start) ==========================================
        // with array_values($validTypes), $validMimeType currently is (example):
        /*
        Array
        (
            [0] => Array
                (
                    [0] => application/zip
                )

            [1] => Array
                (
                    [0] => application/xml
                    [1] => text/xml
                )

        )

        */
        // but Zend_Validate_File_MimeType needs (example):
        /*
        Array
        (
            [0] => application/zip
            [1] => application/xml
            [2] => text/xml
        )

        */
        $allValidMimeTypes = [];
        foreach ($validMimeType as $key1 => $value1) {
            foreach ($value1 as $key2 => $value2) {
                $allValidMimeTypes[] = $value2;
            }
        }
        // =============== workaround (end) ============================================
        $upload->addValidators([
            new Zend_Validate_File_MimeType($allValidMimeTypes),
            new Zend_Validate_File_Extension($validExtension),
        ]);
        // CAUTON: The validators don't know which extensions are allowed for which extension.
        // The only know ALL extensions that are allowed and all MimeTypes that are allowed.

        //init validations
        $upload->isValid(self::FILE_UPLOAD_NAME);

        try {
            //this will throw an Zend_File_Transfer_Exception when the file does not exist
            $importInfo = $upload->getFileInfo(self::FILE_UPLOAD_NAME);
        } catch (Zend_File_Transfer_Exception $e) {
            //no tmUpload field was given, this can happen only from the api
            //allow empty filebased language resource without file upload
            return null;
        }

        //checking general upload errors
        $errorNr = $importInfo[self::FILE_UPLOAD_NAME]['error'];

        if ($errorNr === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($errorNr !== UPLOAD_ERR_OK) {
            $this->uploadErrors[] = ZfExtended_FileUploadException::getUploadErrorMessage($errorNr);

            return $importInfo;
        }

        //currently, an error means wrong filetype
        if ($upload->hasErrors()) {
            $this->uploadErrors[] = 'Die ausgewählte Ressource kann Dateien diesen Typs nicht verarbeiten!';
        }

        if (empty($importInfo[self::FILE_UPLOAD_NAME]['size'])) {
            $this->uploadErrors[] = 'Die ausgewählte Datei war leer!';
        }

        return $importInfo;
    }

    /***
     * Init and queue the service import worker
     * @param array $importInfo
     * @param boolean $addNew
     */
    protected function queueServiceImportWorker(array $importInfo, bool $addNew)
    {
        $worker = ZfExtended_Factory::get(editor_Services_ImportWorker::class);

        $params = $this->getAllParams();
        $params['languageResourceId'] = $this->entity->getId();

        if (! empty($importInfo) && ! empty($importInfo[self::FILE_UPLOAD_NAME])) {
            $this->handleUploadLanguageResourcesFile($importInfo[self::FILE_UPLOAD_NAME]);
            $params['fileinfo'] = $importInfo[self::FILE_UPLOAD_NAME];
        } else {
            $params['fileinfo'] = [];
        }

        $params['addnew'] = $addNew;
        $params['userGuid'] = ZfExtended_Authentication::getInstance()->getUserGuid();

        if (! $worker->init(null, $params)) {
            $this->uploadErrors[] = 'File import in language resources Error on worker init()';

            return;
        }

        //set the language resource status to importing
        $this->entity->setStatus(LanguageResourceStatus::IMPORT);
        $this->entity->save();

        // we add in state scheduled to give event-subscripers the chance to add their workers
        $workerId = $worker->queue(0, ZfExtended_Models_Worker::STATE_SCHEDULED, false);

        $this->events->trigger('serviceImportWorkerQueued', argv: [
            'entity' => $this->entity,
            'workerId' => $workerId,
            'params' => $this->getAllParams(),
        ]);

        //finally trigger the worker queue
        WorkerTriggerFactory::create()->triggerQueue();
    }

    /***
     * Move the upload file to the tem directory so it can be used by the worker.
     * The fileinfo temp_name will be modified
     * @param array $fileinfo
     */
    protected function handleUploadLanguageResourcesFile(array &$fileinfo)
    {
        //create unique temp file name
        $newFileLocation = tempnam(sys_get_temp_dir(), 'LanguageResources' . $fileinfo['name']);
        if (! is_dir(dirname($newFileLocation))) {
            mkdir(dirname($newFileLocation), 0777, true);
        }
        move_uploaded_file($fileinfo['tmp_name'], $newFileLocation);
        $fileinfo['tmp_name'] = $newFileLocation;
    }

    /**
     * translates and transport upload errors to the frontend
     * @return boolean if there are upload errors false, true otherwise
     */
    protected function validateUpload()
    {
        if (empty($this->uploadErrors)) {
            return true;
        }
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $errors = [
            self::FILE_UPLOAD_NAME => [],
        ];

        foreach ($this->uploadErrors as $error) {
            $errors[self::FILE_UPLOAD_NAME][] = $translate->_($error);
        }

        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        $this->handleValidateException($e);

        return false;
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Conflict
     * @throws Zend_Db_Table_Exception
     * @throws ReflectionException
     * @throws ZfExtended_NoAccessException
     */
    public function deleteAction()
    {
        //load the entity and store a copy for later use.
        $this->entityLoad();

        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);
        $resource = $serviceManager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        if (! $resource->getDeletable()) {
            throw new ZfExtended_BadMethodCallException(__CLASS__ . ' -> ' . __FUNCTION__ . ' due service type.');
        }

        $clone = clone $this->entity;

        // Client-restricted users can only delete language-resources, that are associated only to "their" customers
        // we will throw a no-access exception in case this is attempted
        if (ZfExtended_Authentication::getInstance()->isUserClientRestricted()) {
            $this->checkClientRestrictedDeletion();
        }

        // detect parameters
        $forced = (bool) $this->getParam('forced', false);
        $deleteInResource = ! $this->getParam('deleteLocally', false);

        // check entity version
        $this->processClientReferenceVersion();

        // now try to remove the language-resource associations, customer and task
        try {
            DeleteLanguageResourceOperation::create()->delete(
                $this->entity,
                forced: $forced,
                deleteInResource: $deleteInResource
            );
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            //if there are associated tasks we can not delete the language resource
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1158' => 'A Language Resources cannot be deleted as long as tasks are assigned to this Language Resource.',
            ], 'editor.languageresources');

            throw new ZfExtended_Models_Entity_Conflict('E1158');
        }

        // and restore the entity for later use in "afterDeleteAction" event-handler
        $this->entity = $clone;
    }

    /**
     * performs a languageResource query
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws editor_Models_ConfigException
     */
    public function queryAction()
    {
        $this->initCurrentTask();
        $languageResourceId = (int) $this->_getParam('languageResourceId');
        $taskGuid = $this->getCurrentTask()->getTaskGuid();

        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load((int) $this->_getParam('segmentId'));

        //check taskGuid of segment against loaded taskguid for security reasons
        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess($taskGuid, $languageResourceId, $segment);

        $this->entity->load($languageResourceId);

        $connector = $this->getConnectorForTask($this->getCurrentTask());
        $result = $connector->query($segment);

        if ($this->entity->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_TM) {
            $result = $this->filterResults($result);
            $result = $this->markDiff($segment, $result, $connector);
        }

        $tmConversionService = TmConversionService::create();

        $this->view->segmentId = $segment->getId(); //return the segmentId back, just for reference
        $this->view->languageResourceId = $this->entity->getId();
        $this->view->resourceType = $this->entity->getResourceType();
        $this->view->rows = array_values($result->getResult());

        $taskPenaltyDataProvider = TaskPenaltyDataProvider::create();

        foreach ($this->view->rows as &$row) {
            $penalties = $taskPenaltyDataProvider->getPenalties(
                $taskGuid,
                $languageResourceId,
                [
                    'source' => $row->sourceLanguageId,
                    'target' => $row->targetLanguageId,
                ]
            );
            $row->penaltyGeneral = $penalties['penaltyGeneral'];
            $row->penaltySublang = $penalties['penaltySublang'];
        }
        unset($row);

        if (editor_Services_Manager::SERVICE_OPENTM2 === $this->entity->getServiceType()) {
            $this->view->tmConversionState = $tmConversionService->getConversionState($languageResourceId)->value;

            foreach ($this->view->rows as &$row) {
                $row->tmConversionState = $this->view->tmConversionState;
            }
        }
        $this->view->total = count($this->view->rows);
    }

    /**
     * performs a languageResource search
     * example URL /editor/languageResource/14/search
     * additional POST Parameters:
     *  query: querystring
     *  field: source or target
     *  offset: the offset from where the next search should start
     * Since the GUI is dynamically loading additional content no traditional paging can be used here
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws editor_Models_ConfigException
     * @throws editor_Services_Exceptions_NoService
     */
    public function searchAction()
    {
        $this->initCurrentTask();
        $query = $this->_getParam('query');
        $languageResourceId = (int) $this->_getParam('languageResourceId');
        $field = $this->_getParam('field');
        $offset = $this->_getParam('offset', null);

        //check provided field
        if ($field !== 'source') {
            $field == 'target';
        }

        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess($this->getCurrentTask()->getTaskGuid(), $languageResourceId);

        $this->entity->load($languageResourceId);

        if (! $this->entity->getResource()->getSearchable()) {
            throw new ZfExtended_Models_Entity_NoAccessException('search requests are not allowed on this language resource');
        }

        $connector = $this->getConnectorForTask($this->getCurrentTask());
        $result = $connector->search($query, $field, $offset);
        $this->view->languageResourceId = $this->entity->getId();
        $this->view->nextOffset = $result->getNextOffset();
        $this->view->rows = $result->getResult();
    }

    public function translateAction()
    {
        $this->initCurrentTask();

        $query = $this->_getParam('searchText');
        $languageResourceId = (int) $this->_getParam('languageResourceId');

        //checks if the current task is associated to the languageResource
        $this->entity->checkTaskAndLanguageResourceAccess($this->getCurrentTask()->getTaskGuid(), $languageResourceId);

        $this->entity->load($languageResourceId);

        $connector = $this->getConnectorForTask($this->getCurrentTask());
        $result = $connector->translate($query);
        $result = $result->getResult()[0] ?? [];
        $this->view->translations = $result->metaData['alternativeTranslations'] ?? $result;
    }

    /**
     * returns the connector to be used
     *
     * @throws editor_Models_ConfigException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    private function getConnectorForTask(editor_Models_Task $task): editor_Services_Connector
    {
        $manager = ZfExtended_Factory::get(editor_Services_Manager::class);

        return $manager->getConnector(
            $this->entity,
            $task->getSourceLang(),
            $task->getTargetLang(),
            $task->getConfig(),
            (int) $task->getCustomerId()
        );
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    private function getConnector(): editor_Services_Connector
    {
        $manager = ZfExtended_Factory::get(editor_Services_Manager::class);
        $customer = $this->getSingleAssociatedCustomer();

        return $manager->getConnector(
            $this->entity,
            config: $this->getConfig(),
            customerId: $customer ? (int) $customer->getId() : null
        );
    }

    private ?Zend_Config $currentConfig = null;

    private function getConfig(): Zend_Config
    {
        if (! $this->currentConfig) {
            $customer = $this->getSingleAssociatedCustomer();

            $this->currentConfig = $customer ? $customer->getConfig() : Zend_Registry::get('config');
        }

        return $this->currentConfig;
    }

    /***
    * Mark differences between $resultSource (the result from the resource) and the $queryString(the requested search
    * string) The difference is marked in $resultSource as return value
    * @param editor_Models_Segment $segment
    * @param editor_Services_ServiceResult $result
    * @param editor_Services_Connector $connector
    * @return editor_Services_ServiceResult
    */
    protected function markDiff(editor_Models_Segment $segment, editor_Services_ServiceResult $result, editor_Services_Connector $connector)
    {
        $queryString = $connector->getQueryString($segment);
        $queryStringTags = [];

        //remove track changes tag from the query string
        $trackChangeTag = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        /* @var $trackChangeTag editor_Models_Segment_TrackChangeTag */
        $queryString = $trackChangeTag->removeTrackChanges($queryString);

        //protect the tags
        $queryString = $this->protectTags($queryString, $queryStringTags);

        //remove term tags from the query string
        $termTag = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        /* @var $termTag editor_Models_Segment_TermTag */
        $queryString = $termTag->remove($queryString);

        //convert the html special chars
        $decodeHtmlSpecial = function ($string) {
            return htmlspecialchars_decode($string);
        };

        $queryString = $decodeHtmlSpecial($queryString);

        $diffTagger = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_Csv');
        /* @var $diffTagger editor_Models_Export_DiffTagger_Csv */

        //add del/ins tags css class
        $diffTagger->insertTagAttributes['class'] = 'tmMatchGridResultTooltip';
        $diffTagger->deleteTagAttributes['class'] = 'tmMatchGridResultTooltip';

        $results = $result->getResult();
        foreach ($results as &$res) {
            $tags = [];
            //replace the internal tags before diff
            $res->source = $this->protectTags($res->source, $tags);

            $res->source = $decodeHtmlSpecial($res->source);

            $res->source = $diffTagger->diffSegment($queryString, $res->source, null, null, true);
            $res->source = $this->unprotectTags($res->source, array_merge($tags, $queryStringTags));
        }

        return $result;
    }

    /**
     * protected the internal tags for diffing
     * @param string $segment
     */
    protected function protectTags($segment, array &$tags)
    {
        $tag = $this->internalTag;

        return $tag->replace($segment, function ($match) use (&$tags, $tag) {
            $submatch = null;
            $placeholder = 'notfound';

            if (preg_match($tag::REGEX_STARTTAG, $match[0], $submatch)) {
                $placeholder = sprintf($tag::PLACEHOLDER_TEMPLATE, 'start-' . $submatch[1]);
            } elseif (preg_match($tag::REGEX_ENDTAG, $match[0], $submatch)) {
                $placeholder = sprintf($tag::PLACEHOLDER_TEMPLATE, 'end-' . $submatch[1]);
            } elseif (preg_match($tag::REGEX_SINGLETAG, $match[0], $submatch)) {
                $id = $match[3];

                $placeholder = match (true) {
                    in_array($id, $this->contentProtector->tagList(WhitespaceProtector::alias())) => sprintf(
                        $tag::PLACEHOLDER_TEMPLATE,
                        $id . '-' . $tag->getLength($match[0])
                    ),
                    in_array($id, $this->contentProtector->tagList(NumberProtector::alias())) => sprintf(
                        $tag::PLACEHOLDER_TEMPLATE,
                        'number-' . $submatch[1]
                    ),
                    default => sprintf($tag::PLACEHOLDER_TEMPLATE, 'single-' . $submatch[1]),
                };
            }

            $tags[$placeholder] = $match[0];

            return $placeholder;
        });
    }

    /**
     * unprotects / restores the content tags
     * @param string $segment
     * @param array $segment
     * @return string
     */
    protected function unprotectTags($segment, array $tags)
    {
        return str_replace(array_keys($tags), array_values($tags), $segment);
    }

    /**
     * Transforms the specificData for the frontend
     * updates the value in the passed object or array and returns the deserialized specificData
     * @throws Zend_Exception
     * @throws Zend_Json_Exception
     * @throws ZfExtended_Exception
     */
    protected function prepareSpecificData(array|stdClass &$resourceData, bool $isArray): array
    {
        if (($isArray && ! array_key_exists('specificData', $resourceData))
            || (! $isArray && ! property_exists($resourceData, 'specificData'))) {
            return [];
        }

        if (! $isArray && ! property_exists($resourceData, 'specificData')) {
            return [];
        }

        $specificData = $isArray ? $resourceData['specificData'] : $resourceData->specificData;
        $specificData = empty($specificData) ? null : Zend_Json::decode($specificData);

        if (empty($specificData)) {
            $returnData = [];
            $specificData = null;
        } else {
            $returnData = is_array($specificData)
                ? Sanitizer::escapeHtmlRecursive($specificData)
                : Sanitizer::escapeHtmlInObject($specificData);
            $specificData = Zend_Json::encode($returnData);
        }

        if ($isArray) {
            $resourceData['specificData'] = $specificData;
        } else {
            $resourceData->specificData = $specificData;
        }

        return $returnData;
    }

    /**
     * Check or clean of customer associations
     * @throws Zend_Db_Table_Exception
     * @throws ZfExtended_ErrorCodeException
     */
    private function checkOrCleanCustomerAssociation(bool $clean, array $customerIds): void
    {
        $assocClean = ZfExtended_Factory::get(Customer::class, [$this->entity->getId(), $customerIds]);
        $clean ? $assocClean->cleanAssociation() : $assocClean->check();
    }

    // additional API needed to process client-restricted usage of the controller

    /**
     * Fixes the current customer associations for client-restricted PMs:
     * They may send assocs that do not contain the currently set assocs for customers they cannot see
     */
    private function transformClientRestrictedCustomerAssocs(): void
    {
        $resourceId = (int) $this->entity->getId();
        $allowedCustomerIs = ZfExtended_Authentication::getInstance()->getUser()->getRestrictedClientIds();
        //get all assocs grouped for our entity
        $customerAssocModel = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $customerAssocs = $customerAssocModel->loadCustomerIdsGrouped($resourceId);
        $doDebug = ZfExtended_Debug::hasLevel('core', 'EntityFilter');
        // now fix the sent data in case unallowed assocs have been removed
        $this->adjustClientRestrictedCustomerAssoc(
            'customerIds',
            $this->getCustassoc($customerAssocs, 'customerId', $resourceId),
            $allowedCustomerIs,
            $doDebug
        );
        $this->adjustClientRestrictedCustomerAssoc(
            'customerUseAsDefaultIds',
            $this->getCustassocByIndex($customerAssocs, 'useAsDefault', $resourceId),
            $allowedCustomerIs,
            $doDebug
        );
        $this->adjustClientRestrictedCustomerAssoc(
            'customerWriteAsDefaultIds',
            $this->getCustassocByIndex($customerAssocs, 'writeAsDefault', $resourceId),
            $allowedCustomerIs,
            $doDebug
        );
        $this->adjustClientRestrictedCustomerAssoc(
            'customerPivotAsDefaultIds',
            $this->getCustassocByIndex($customerAssocs, 'pivotAsDefault', $resourceId),
            $allowedCustomerIs,
            $doDebug
        );
    }

    /**
     * Adjusts a single Association that needs to be  potentially fixed if the user is only allowed to remove certain
     * clients
     */
    private function adjustClientRestrictedCustomerAssoc(
        string $paramName,
        array $originalValue,
        array $allowedCustomerIs,
        bool $doDebug,
    ): void {
        $originalValue = array_map('intval', $originalValue);
        $allowedCustomerIs = array_map('intval', $allowedCustomerIs);

        // evaluate the ids the client-restricted user is not allowed to change
        $notAllowedIds = array_values(array_diff($originalValue, $allowedCustomerIs));

        if (! empty($notAllowedIds)) {
            // if there are clients, the user is not allowed to remove, add them to the sent data
            $sentIds = $this->getDataField($paramName) ?? [];
            $newIds = array_values(array_unique(array_merge(array_map('intval', $sentIds), $notAllowedIds)));

            if ($doDebug) {
                error_log(
                    "\n----------\n"
                    . "FIX ENTITY UPDATE " . get_class($this->entity) . "\n"
                    . 'user removed client-ids he is not entitled for: ' . implode(', ', $notAllowedIds)
                    . "\n==========\n"
                );
            }

            if ($this->decodePutAssociative) {
                $this->data[$paramName] = $newIds;
            } else {
                $this->data->$paramName = $newIds;
            }
        }
    }

    /**
     * Checks the deletion of language-resources for client-restricted users
     * @throws ZfExtended_NoAccessException
     */
    private function checkClientRestrictedDeletion(): void
    {
        $allowedCustomerIs = ZfExtended_Authentication::getInstance()->getUser()->getRestrictedClientIds();
        if (! empty(array_diff($this->entity->getCustomers(), $allowedCustomerIs))) {
            throw new ZfExtended_NoAccessException('Deletion of LanguageResource is not allowed due to client-restriction');
        }
    }

    /**
     * Filter results by configured match-rate threshold
     * @throws \MittagQI\Translate5\Task\Current\Exception|editor_Models_ConfigException
     */
    private function filterResults(editor_Services_ServiceResult $result): editor_Services_ServiceResult
    {
        $matchRateThreshold = $this->getCurrentTask()->getConfig()
            ->runtimeOptions
            ->LanguageResources
            ->TM
            ->matchPanelMatchrate ?? 0;

        if ($matchRateThreshold === 0) {
            return $result;
        }

        $result->setResults(
            array_filter(
                $result->getResult(),
                static function ($row) use ($matchRateThreshold) {
                    return $row->matchrate >= $matchRateThreshold;
                }
            )
        );

        return $result;
    }

    private function shouldShowTaskTms(): bool
    {
        $showTaskTm = $this->entity->getFilter()->hasFilter('isTaskTm')
            && ($this->entity->getFilter()->getFilter('isTaskTm')->value[0] ?? '') === 'showTaskTms';
        $this->entity->getFilter()->deleteFilter('isTaskTm');

        return $showTaskTm;
    }
}
