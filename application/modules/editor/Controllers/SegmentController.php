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
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Segment\ActionAssert\Permission\SegmentActionPermissionAssert;
use MittagQI\Translate5\Segment\ActionAssert\SegmentAction;
use MittagQI\Translate5\Segment\Operation\Factory\UpdateSegmentDtoFactory;
use MittagQI\Translate5\Segment\Operation\UpdateSegmentLogger;
use MittagQI\Translate5\Segment\Operation\WithAuthentication\UpdateSegmentOperation;
use MittagQI\Translate5\Segment\Operations;
use MittagQI\Translate5\Segment\SearchAndReplace\ReplaceDtoFactory;
use MittagQI\Translate5\Segment\SearchAndReplace\SearchAndReplaceException;
use MittagQI\Translate5\Segment\SearchAndReplace\SearchAndReplaceService;
use MittagQI\Translate5\Segment\SearchAndReplace\SearchQueryDtoFactory;
use MittagQI\Translate5\Segment\SearchAndReplace\SearchService;
use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;
use MittagQI\Translate5\Terminology\TermportletData;

class Editor_SegmentController extends ZfExtended_RestController
{
    use TaskContextTrait;

    protected $entityClass = editor_Models_Segment::class;

    /**
     * overriding filter class to ensure lower case filtering for segment content fields
     * @var string
     */
    protected $filterClass = editor_Models_Filter_SegmentSpecific::class;

    /**
     * @var editor_Models_Segment
     */
    protected $entity;

    /**
     * Number to divide the segment duration
     *
     * @var integer
     */
    protected $durationsDivisor = 1;

    /**
     * @var string[]
     */
    protected $cachedAutostates = null;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws NoAccessException
     * @throws ZfExtended_NoAccessException
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->initCurrentTask();

        $this->validateFilters();

        $sfm = $this->initSegmentFieldManager($this->getCurrentTask()->getTaskGuid());

        //overwrite sortColMap
        $this->_sortColMap = $sfm->getSortColMap();
        $filter = $this->entity->getFilter();
        /** @var editor_Models_Filter_SegmentSpecific $filter */
        // update sortColMap and filterTypeMap in filter instance
        $filter->setMappings($this->_sortColMap, $this->_filterTypeMap);
        $filter->setSegmentFields(array_keys($this->_sortColMap));
    }

    private function validateFilters(): void
    {
        $sfm = $this->initSegmentFieldManager($this->getCurrentTask()->getTaskGuid());

        $segmentFilterValidator = new \MittagQI\Translate5\Segment\SegmentFilterAndSortValidator(
            new \MittagQI\ZfExtended\Models\Filter\FilterValidator(),
            $this->entity
        );

        $segmentFilterValidator->validateAndRemoveInvalid(
            array_merge($sfm->getView()->getFields(), ['isWatched']),
            $sfm->getSortColMap()
        );
    }

    /**
     * initiates the internal SegmentFieldManager
     * @param string $taskGuid
     * @return editor_Models_SegmentFieldManager
     */
    protected function initSegmentFieldManager($taskGuid)
    {
        return editor_Models_SegmentFieldManager::getForTaskGuid($taskGuid);
    }

    /**
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function indexAction()
    {
        $taskGuid = $this->getCurrentTask()->getTaskGuid();

        // apply quality filter
        $this->applyQualityFilter();
        $rows = $this->entity->loadByTaskGuid($taskGuid);
        $this->view->rows = $rows;
        $this->view->total = $this->entity->totalCountByTaskGuid($taskGuid);

        $this->addIsWatchedFlag();
        $this->addFirstEditable();
        $this->addIsFirstFileInfo($taskGuid);
        $this->addJumpToSegmentIndex();

        // ----- Specific handling of rows (start) -----

        $handleSegmentranges = $this->checkAndGetSegmentsRange($this->getCurrentTask());
        if (is_array($handleSegmentranges)) {
            $assignedSegments = $handleSegmentranges;
            $handleSegmentranges = true;
        }

        // - Anonymize users for view? (e.g. comments etc in segment-grid-mouseovers)
        $handleAnonymizeUsers = $this->getCurrentTask()->anonymizeUsers();
        if ($handleAnonymizeUsers) {
            $workflowAnonymize = ZfExtended_Factory::get(editor_Workflow_Anonymize::class);
        }

        if ($handleSegmentranges || $handleAnonymizeUsers) {
            foreach ($this->view->rows as &$row) {
                // a segment that is not editable already must stay not editable!
                if ($handleSegmentranges && $row['editable']) {
                    $row['editable'] = in_array($row['segmentNrInTask'], $assignedSegments);
                }
                if ($handleAnonymizeUsers) {
                    $row = $workflowAnonymize->anonymizeUserdata($taskGuid, $row['userGuid'], $row);
                }
            }
        }

        // ----- Specific handling of rows (end) -----
    }

    public function nextsegmentsAction()
    {
        $segmentId = (int) $this->_getParam('segmentId');
        $this->entity->load($segmentId);
        $this->checkTaskGuid($this->getCurrentTask());

        $permissionAssert = SegmentActionPermissionAssert::create();
        $userRepository = new UserRepository();
        $authUser = $userRepository->getByGuid(ZfExtended_Authentication::getInstance()->getUserGuid());

        $permissionAssert->assertGranted(
            SegmentAction::Read,
            $this->entity,
            new PermissionAssertContext($authUser)
        );

        $context = new stdClass(); // this needs to be an object to make sure it is passed by reference through the events API
        $context->result = [];
        $context->types = explode(',', $this->_getParam('parsertypes', 'editable,workflow'));
        $context->field = $this->_getParam('editedField', null);

        $workflowStepName = $this->getCurrentJob()->getWorkflowStepName();

        foreach ($context->types as $type) {
            $workflowStep = ($type == 'workflow') ? $workflowStepName : '';
            if ($type == 'editable' || $type == 'workflow') {
                $param = 'next_' . $type;
                if ($this->_getParam($param, false)) {
                    $context->result[$param] = $this->entity->findSurroundingEditables(true, $workflowStep);
                }
                $param = 'prev_' . $type;
                if ($this->_getParam($param, false)) {
                    $context->result[$param] = $this->entity->findSurroundingEditables(false, $workflowStep);
                }
            }
        }
        // this gives plugins (which may add types in the frontend) the chance to add the corresponding data
        $this->events->trigger('nextsegmentsAction', $this, [
            'context' => $context,
            'segment' => $this->entity,
        ]);

        echo Zend_Json::encode((object) $context->result, Zend_Json::TYPE_OBJECT);
    }

    /**
     * returns the index (position) of the requested segment (by segmentId)
     * in the filtered segment list (as it would be given by indexAction)
     * if index is null, that means the segment is not given in the filtered list
     * FIXME: this function uses the segmentNrInTask and NOT the segmentId as normal. How to solve this???
     * Background: in the frontend (visual review) we dont have the segmentId, we only have the segmentNrInTask
     */
    public function positionAction()
    {
        $segmentNrInTask = (int) $this->_getParam('segmentNrInTask');
        $this->entity->loadBySegmentNrInTask($segmentNrInTask, $this->getCurrentTask()->getTaskGuid());
        //$this->checkTaskGuidAndEditable();
        $index = $this->entity->getIndex();
        if ($index === null) {
            $e = new ZfExtended_NotFoundException("Segment is not contained in the segment filter");
            $e->setLogging(false); //a wanted exception, disable logging for that

            throw $e;
        }
        $this->view->segmentNrInTask = $segmentNrInTask;

        $this->view->index = $index;
    }

    /**
     * adds the optional is first of file info to the affected segments
     */
    protected function addIsFirstFileInfo(string $taskGuid)
    {
        $filemap = $this->entity->getFileMap($taskGuid);
        foreach ($filemap as $rowIndex) {
            //omit first file
            if ($rowIndex === 0) {
                continue;
            }
            $idx = $rowIndex - $this->offset;
            if ($idx < 0 || empty($this->view->rows[$idx])) {
                continue;
            }
            $this->view->rows[$idx]['isFirstofFile'] = true;
        }
    }

    /**
     * Adds the first editable segments rowindex for f2 usage in the frontend
     */
    protected function addFirstEditable()
    {
        //needed only on first page and if we have rows
        if ($this->offset > 0 || empty($this->view->rows)) {
            return;
        }

        if (! isset($this->view->metaData)) {
            //since we dont use metaData otherwise, we can overwrite it completly
            $this->view->metaData = new stdClass();
        }

        $segment = [];
        foreach ($this->view->rows as $idx => $segment) {
            if ($segment['editable']) {
                $this->view->metaData->firstEditable = $idx;

                return;
            }
        }
        $this->entity->init($segment);
        $this->view->metaData->firstEditable = $this->entity->findSurroundingEditables(true);
    }

    /**
     * For performance Reasons we are calculating the isWatched info this way.
     * A table join is only done if we are filtering for isWatched,
     * since the this join is very expensive on large data tasks
     *
     * Since the segment_user_assoc contains currently only the isWatched info,
     * we merge only the data if isWatched is true.
     */
    protected function addIsWatchedFlag()
    {
        if ($this->entity->filterHasIsWatched()) {
            return;
        }
        //get all segment IDs to be returned
        $ids = array_map(function ($seg) {
            return $seg['id'];
        }, $this->view->rows);

        $assoc = ZfExtended_Factory::get(editor_Models_SegmentUserAssoc::class);
        $watched = $assoc->loadIsWatched($ids, ZfExtended_Authentication::getInstance()->getUserGuid());
        $watchedById = [];
        array_map(function ($assoc) use (&$watchedById) {
            $watchedById[$assoc['segmentId']] = $assoc['id'];
        }, $watched);

        foreach ($this->view->rows as &$row) {
            $row['isWatched'] = ! empty($watchedById[$row['id']]);
            if ($row['isWatched']) {
                $row['segmentUserAssocId'] = $watchedById[$row['id']];
            }
        }
    }

    public function putAction()
    {
        $auth = ZfExtended_Authentication::getInstance();
        $this->entity->load((int) $this->_getParam('id'));

        $task = $this->getCurrentTask();
        $this->checkTaskGuid($task);

        $updateSegmentOperation = UpdateSegmentOperation::create();
        $updateSegmentDtoFactory = UpdateSegmentDtoFactory::create();
        $userRepository = new UserRepository();

        $updateSegmentOperation->update(
            $this->entity,
            $updateSegmentDtoFactory->fromRequest($this->entity, $this->getRequest()),
            $userRepository->get($auth->getUserId()),
            UpdateSegmentLogger::fromPutRequest('SegmentPut', $this->entity, $this->getRequest()),
            $this->restMessages,
        );

        $this->entity->refresh();
        // To always have a consistent view-model, we convert the stdClass to an assoc array,
        // no matter if anonymization is required or not
        $rows = json_decode(json_encode($this->entity->getDataObject()), true);

        // anonymize users for view? (e.g. comments etc in segment-grid-mouseovers)
        if ($task->anonymizeUsers()) {
            $workflowAnonymize = ZfExtended_Factory::get(editor_Workflow_Anonymize::class);
            $rows = $workflowAnonymize->anonymizeUserdata(
                $this->entity->getTaskGuid(),
                $rows['userGuid'],
                $rows
            );
        }

        $this->view->rows = $rows;

        // Recalculate task progress and assign results into view so the frontend viewModel is updated
        // TODO: this should be updated from the websockets
        $this->appendTaskProgress($task);
    }

    /**
     * Search segment action.
     * @throws NoAccessException
     * @throws Zend_Exception
     * @throws ZfExtended_ValidateException
     */
    public function searchAction()
    {
        $parameters = $this->getAllParams();
        $this->validateTaskAccess($parameters['taskGuid']);

        if (! $this->checkSearchStringLength($parameters['searchField'] ?? '')) {
            return;
        }

        $searchService = SearchService::create();
        $searchQuery = SearchQueryDtoFactory::create()->fromRequest($this->getRequest());

        $result = $searchService->search($searchQuery);

        if (! $result || empty($result)) {
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            $this->view->message = $t->_('Keine Ergebnisse für die aktuelle Suche!');

            return;
        }

        $this->view->rows = $result;
        $this->view->total = count($result);
        $this->view->hasMqm = $this->isMqmTask($parameters['taskGuid']);
        $this->view->isOpenedByMoreThanOneUser = $this->isOpenedByMoreThanOneUser($parameters['taskGuid']);
    }

    /**
     * Check whether task is opened by more than one user
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     */
    public function isOpenedByMoreThanOneUser(string $taskGuid): bool
    {
        $usedBy = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class)->loadUsed($taskGuid);

        return count($usedBy) > 1;
    }

    /***
     * Replace all search matches and save the new segment content to the database.
     * Return the modified segments
     * @throws NoAccessException
     * @throws Zend_Exception
     * @throws ZfExtended_ValidateException
     * @throws editor_Models_SearchAndReplace_Exception
     */
    public function replaceallAction()
    {
        $parameters = $this->getAllParams();
        $this->validateTaskAccess($parameters['taskGuid']);

        $task = editor_ModelInstances::taskByGuid($parameters['taskGuid']);
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */
        if (
            $task->getUsageMode() === $task::USAGE_MODE_SIMULTANEOUS
            && $this->isOpenedByMoreThanOneUser($parameters['taskGuid'])
        ) {
            throw new SearchAndReplaceException('E1192', [
                'task' => $task,
            ]);
        }

        //check if the task has mqm tags
        //replace all is not supported for tasks with mqm
        if ($this->isMqmTask($parameters['taskGuid'])) {
            $this->view->message = $t->_('Alle ersetzen wird für Aufgaben mit Segmenten mit MQM-Tags nicht unterstützt');
            $this->view->hasMqm = true;

            return;
        }

        //find all segments for the search parameters
        $searchService = SearchService::create();
        $searchQuery = SearchQueryDtoFactory::create()->fromRequest($this->getRequest());

        $results = $searchService->search($searchQuery);

        if (! $results || empty($results)) {
            $this->view->message = $t->_('Keine Ergebnisse für die aktuelle Suche!');

            return;
        }

        $replaceDtoFactory = ReplaceDtoFactory::create();
        $searchAndReplaceService = SearchAndReplaceService::create();

        $replaceDto = $replaceDtoFactory->fromRequest($this->getRequest());

        if ($this->_getParam('async', false)) {
            $searchAndReplaceService->queueReplaceAll($replaceDto);

            $this->view->total = count($results);

            return;
        }

        $searchAndReplaceService->replaceAll($replaceDto);

        //return the modefied segments
        $this->view->rows = $searchService->search($searchQuery);
        $this->view->total = count($this->view->rows);
    }

    /**
     * checks if current put makes sense to save
     * @param array $fieldnames allowed fieldnames to be saved
     */
    protected function checkPlausibilityOfPut($fieldnames): void
    {
        $error = [];
        foreach ($this->data as $key => $value) {
            //consider only changeable datafields:
            if (! in_array($key, $fieldnames)) {
                continue;
            }
            //search for the img tag, get the data and remove it
            $regex = '#<img[^>]+class="duplicatesavecheck"[^>]+data-segmentid="([0-9]+)" data-fieldname="([^"]+)"[^>]*>#';
            $match = [];

            if (! preg_match($regex, $value, $match)) {
                continue;
            }
            $this->data->{$key} = str_replace($match[0], '', $value);
            //if segmentId and fieldname from content differ to the segment to be saved, throw the error!
            if ($match[2] != $key || $match[1] != $this->entity->getId()) {
                $error['real fieldname: ' . $key] = [
                    'segmentId' => $match[1],
                    'fieldName' => $match[2],
                ];
            }
        }

        if (empty($error)) {
            return;
        }

        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */

        $logText = 'Error on saving a segment!!! Parts of the content in the PUT request ';
        $logText .= 'delivered the following segmentId(s) and fieldName(s):' . "\n";
        $logText .= print_r($error, 1) . "\n";
        $logText .= 'but the request was for segmentId ' . $this->entity->getId();
        $logText .= ' (compare also the above fieldnames!).' . "\n";
        $logText .= 'Therefore the segment has not been saved!' . "\n";
        $logText .= 'Actually saved Segment PUT data and data to be saved in DB:' . "\n";
        $logText .= print_r($this->data, 1) . "\n" . print_r($this->entity->getDataObject(), 1) . "\n\n";
        $logText .= 'Content of $_SERVER had been: ' . print_r($_SERVER, true);

        $log->logError('Possible Error on saving a segment!', $logText);

        $e = new ZfExtended_Exception();
        $e->setMessage('Aufgrund der langsamen Verarbeitung von Javascript im Internet Explorer konnte das Segment nicht korrekt gespeichert werden. Bitte öffnen Sie das Segment nochmals und speichern Sie es erneut. Sollte das Problem bestehen bleiben, drücken Sie bitte F5 und bearbeiten dann das Segment erneut. Vielen Dank!', true);

        throw $e;
    }

    /**
     * checks if current session task guid matches to loaded segment task guid
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkTaskGuid(\editor_Models_Task $task)
    {
        $editable = $this->entity->getEditable();

        if (empty($editable) || $task->getTaskGuid() !== $this->entity->getTaskGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }

    public function getAction()
    {
        $this->entity->load($this->_getParam('id'));
        $this->validateTaskAccess($this->entity->getTaskGuid());
        //check if the segment range feature is active for the current segment and task,
        //if it is active, calculate the editable state of the segment
        //segment get action is also called by the message bus to refresh the segment content whenever some
        //other user updates the segment
        $handleSegmentranges = $this->checkAndGetSegmentsRange();
        if (is_array($handleSegmentranges) && $this->entity->getEditable()) {
            $editable = in_array($this->entity->getSegmentNrInTask(), $handleSegmentranges);
            $this->entity->setEditable($editable);
        }

        $this->view->rows = (array) $this->entity->getDataObject();
    }

    public function deleteAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function postAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->post');
    }

    #region Operation Entry Points

    /**
     * @param bool $lock optional, defines if lock or unlock, defaults to true
     * @throws ZfExtended_NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws editor_Models_Segment_Exception
     */
    public function lockOperation(bool $lock = true)
    {
        $acl = $lock ? Rights::LOCK_SEGMENT_OPERATION : Rights::UNLOCK_SEGMENT_OPERATION;

        // Shortcut
        $task = $this->getCurrentTask();

        //the amount of new ACL rules would be huge to handle that lock/unlock Batch/Operations with
        // ordinary controller right handling since currently role editor has access to all methods here.
        // So its easier to double access to that functions for PM users then
        $this->checkAccess(Rights::ID, $acl, __CLASS__ . '::' . ($lock ? __FUNCTION__ : 'unlockOperation'));
        $this->getAction();

        /* @var Operations $operations */
        $operations = ZfExtended_Factory::get(Operations::class, [
            $task->getTaskGuid(),
            $this->entity,
        ]);

        // Do preparations for cases when we need full list of task's segments to be analysed for quality detection
        // Currently it is used only for consistency-check to detect consistency qualities BEFORE segment is updated,
        // so that it would be possible to do the same AFTER segment is updated, calculate the difference and insert/delete
        // qualities on segments where needed
        editor_Segment_Quality_Manager::instance()->preProcessTask($task, editor_Segment_Processing::ALIKE);

        // Toggle lock
        $operations->toggleLockOperation($lock);

        // Update qualities for cases when we need full list of task's segments to be analysed for quality detection
        editor_Segment_Quality_Manager::instance()->postProcessTask($task, editor_Segment_Processing::ALIKE);

        //update the already flushed object with the locked one
        $this->view->rows = $this->entity->getDataObject();

        // Recalculate task progress and assign results into view
        $this->appendTaskProgress();
    }

    /**
     * @throws ZfExtended_NoAccessException
     * @throws editor_Models_Segment_Exception
     */
    public function unlockOperation()
    {
        $this->lockOperation(false);
    }

    /**
     * @throws ZfExtended_NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function unlockBatch()
    {
        $this->lockBatch(false);
    }

    /**
     * @throws ZfExtended_NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function lockBatch(bool $lock = true)
    {
        $acl = $lock ? Rights::LOCK_SEGMENT_BATCH : Rights::UNLOCK_SEGMENT_BATCH;

        // Shortcut
        $task = $this->getCurrentTask();

        $this->checkAccess(Rights::ID, $acl, __CLASS__ . '::' . ($lock ? __FUNCTION__ : 'unlockBatch'));
        $this->applyQualityFilter();

        /* @var Operations $operations */
        $operations = ZfExtended_Factory::get(Operations::class, [
            $task->getTaskGuid(),
            $this->entity,
        ]);

        // Do preparations for cases when we need full list of task's segments to be analysed for quality detection
        // Currently it is used only for consistency-check to detect consistency qualities BEFORE segment is updated,
        // so that it would be possible to do the same AFTER segment is updated, calculate the difference and insert/delete
        // qualities on segments where needed
        editor_Segment_Quality_Manager::instance()->preProcessTask($task, editor_Segment_Processing::ALIKE);

        // Batch-lock/unlock
        $operations->toggleLockBatch($lock);

        // Update qualities for cases when we need full list of task's segments to be analysed for quality detection
        editor_Segment_Quality_Manager::instance()->postProcessTask($task, editor_Segment_Processing::ALIKE);

        // Recalculate task progress and assign results into view
        $this->appendTaskProgress();
    }

    public function unbookmarkBatch()
    {
        $this->bookmarkBatch(false);
    }

    /**
     * @throws ZfExtended_NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function bookmarkBatch(bool $bookmark = true)
    {
        $acl = $bookmark ? 'bookmarkBatch' : 'unbookmarkBatch';
        $this->checkAccess('editor_segmentuserassoc', $acl, __CLASS__ . '::' . $acl);
        $this->applyQualityFilter();

        /* @var Operations $operations */
        $operations = ZfExtended_Factory::get(Operations::class, [
            $this->getCurrentTask()->getTaskGuid(),
            $this->entity,
        ]);
        $operations->toggleBookmarkBatch($bookmark);
    }

    #endregion

    /**
     * returns the mapping between fileIds and segment row indizes
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function filemapAction()
    {
        $result = new stdClass();
        $result->rows = $this->entity->getFileMap($this->getCurrentTask()->getTaskGuid());
        $result->total = count($result->rows);
        echo Zend_Json::encode($result, Zend_Json::TYPE_OBJECT);
        exit;
    }

    /**
     * @throws ZfExtended_Plugin_Exception
     * @throws Zend_Exception
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function termsAction()
    {
        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var $pluginmanager ZfExtended_Plugin_Manager */
        $plugin = $pluginmanager->get('TermPortal');

        $segment = \ZfExtended_Factory::get(editor_Models_Segment::class);
        $segment->load((int) $this->_getParam('id'));

        // Get desired locale either from request or from session
        $desiredLocale = $this->getRequest()->getParam('locale')
            ?: ZfExtended_Authentication::getInstance()->getUser()->getLocale();

        // Get locale
        $locale = ZfExtended_Utils::getLocale($desiredLocale);

        //generate portlet data
        $data = (new TermportletData(
            $this->getCurrentTask(),
            $this->isAllowed('editor_termportal') && ! empty($plugin)
        ))->generate($segment, $locale);

        //add translations
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $data['locales']['entryAttrs'] = $translate->_('Attribute auf Eintragsebene');
        $data['locales']['languageAttrs'] = $translate->_('Attribute auf Sprachebene');
        $data['locales']['termAttrs'] = $translate->_('Attribute auf Benennungsebene');
        if ($data['noTerms']) {
            $data['locales']['noTermsMessage'] = $translate->_('Keine Terminologie vorhanden!');
        }

        $data['termStatus'] = [
            'permitted' => $translate->_('erlaubte Benennung'),
            'forbidden' => $translate->_('verbotene Benennung'),
            'standardized' => $translate->_('Standardisiert'),
            'preferred' => $translate->_('Vorzugsbenennung'),
            'unknown' => $translate->_('Unbekannter Term Status'),
        ];

        echo Zend_Json::encode($data, Zend_Json::TYPE_OBJECT);
    }

    /**
     * generates a list of available matchratetypes in this task. Mainly for frontend filtering.
     */
    public function matchratetypesAction()
    {
        $sfm = $this->initSegmentFieldManager($this->getCurrentTask()->getTaskGuid());
        $mv = $sfm->getView();
        /* @var $mv editor_Models_Segment_MaterializedView */
        $db = ZfExtended_Factory::get(get_class($this->entity->db), [[], $mv->getName()]);
        $sql = $db->select()->from($db, 'matchrateType')->distinct();

        echo Zend_Json::encode($db->fetchAll($sql)->toArray(), Zend_Json::TYPE_ARRAY);
    }

    /**
     * Sets the stateId asynchronously. This enables the segment meta panel to be independent from saving or canceling the segment text
     */
    public function stateidAction()
    {
        $this->entity->load($this->_getParam('id'));
        $this->validateTaskAccess($this->entity->getTaskGuid());
        $stateId = intval($this->_getParam('stateId', -1));
        if ($stateId < 0) {
            throw new ZfExtended_Models_Entity_NotFoundException('parameter stateId is required.');
        }
        $this->entity->setStateId($stateId);
        $this->entity->save();
        $this->view->success = 1;
    }

    /***
     * Check if the search string length is in between 0 and 1024 characters long
     */
    private function checkSearchStringLength($searchField)
    {
        $isValid = true;
        if (empty($searchField) && strlen($searchField === 0)) {
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var $t ZfExtended_Zendoverwrites_Translate */

            $errors = [
                'searchField' => $t->_('Das Suchfeld ist leer.'),
            ];
            $e = new ZfExtended_ValidateException();
            $e->setErrors($errors);
            $this->handleValidateException($e);
            $isValid = false;
        }

        $length = strlen(utf8_decode($searchField));
        if ($length > 1024) {
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var $t ZfExtended_Zendoverwrites_Translate */

            $errors = [
                'searchField' => $t->_('Der Suchbegriff ist zu groß.'),
            ];
            $e = new ZfExtended_ValidateException();
            $e->setErrors($errors);
            $this->handleValidateException($e);
            $isValid = false;
        }

        return $isValid;
    }

    /***
     * Check if the task contains mqm tags for some of the segments
     * @param string $taskGuid
     * @return boolean
     */
    private function isMqmTask($taskGuid)
    {
        return editor_Models_Db_SegmentQuality::hasTypeCategoryForTask($taskGuid, editor_Segment_Tag::TYPE_MQM);
    }

    /***
     * Check if the segments range feature is active for the given task. If the feature is not active boolean false will be returned.
     * If the feature is active, the assigned segments as array will be returned.
     * @param editor_Models_Task $task
     * @return boolean|array
     */
    protected function checkAndGetSegmentsRange(editor_Models_Task $task = null)
    {
        if (! isset($task)) {
            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $task->loadByTaskGuid($this->getCurrentTask()->getTaskGuid());
        }
        if ($task->getUsageMode() !== $task::USAGE_MODE_SIMULTANEOUS) {
            return false;
        }
        $authUserGuid = ZfExtended_Authentication::getInstance()->getUserGuid();
        $tua = editor_Models_Loaders_Taskuserassoc::loadByTask($authUserGuid, $task);
        /** @var editor_Models_TaskUserAssoc $tua */
        $step = $tua->getWorkflowStepName();
        $handleSegmentranges = $tua->isSegmentrangedTaskForStep($task, $step);
        if (! $handleSegmentranges) {
            return false;
        }

        return $tua->getAllAssignedSegmentsByUserAndStep($task->getTaskGuid(), $authUserGuid, $step);
    }

    /***
     * Add jumpToSegmentIndex property to the view metaData object.
     * Where to jump is determined in the following order:
     *
     * 1. Last edited/modified segment by the current user and task
     * 2. First defined segment in the segment range definition for the current user for the task for the workflow role
     * 3. First editable segment in the workflow
     * 4. First segment in the task
     */
    protected function addJumpToSegmentIndex()
    {
        $authUserGuid = ZfExtended_Authentication::getInstance()->getUserGuid();
        $taskGuid = $this->getCurrentTask()->getTaskGuid();

        //needed only on first page and if we have rows
        if ($this->offset > 0 || empty($this->view->rows)) {
            return;
        }

        if (! isset($this->view->metaData)) {
            $this->view->metaData = new stdClass();
        }

        //jump to the last edited segment from the user
        //we need a clone of the entity, so that the filters are initialized
        $segment = clone $this->entity;
        /* @var $segment editor_Models_Segment */
        $segmentId = $segment->getLastEditedByUserAndTask($taskGuid, $authUserGuid);

        if ($segmentId > 0) {
            //last edited segment found, find and set the segment index
            $segment->load($segmentId);
            $this->view->metaData->jumpToSegmentIndex = $segment->getIndex();

            return;
        }

        $tua = null;

        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTaskGuidForceWorkflowRole($authUserGuid, $taskGuid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
        }

        if (empty($tua)) {
            $this->view->metaData->jumpToSegmentIndex = $this->view->metaData->firstEditable ?? 0;

            return;
        }

        //if for the task there are no ranges defined, use first editable(if defined) or 0
        if (! $tua->isSegmentrangedTaskForStep(editor_ModelInstances::taskByGuid($taskGuid), $tua->getWorkflowStepName())) {
            $this->view->metaData->jumpToSegmentIndex = $this->view->metaData->firstEditable ?? 0;

            return;
        }

        $range = $tua->getSegmentrange() ?? null;
        //if there are ranges defined for the user, use the first defined range
        if (! empty($range)) {
            $segments = editor_Models_TaskUserAssoc_Segmentrange::getNumbers($range);
            $this->entity->loadBySegmentNrInTask(reset($segments), $taskGuid);
            $this->view->metaData->jumpToSegmentIndex = $this->entity->getIndex();

            return;
        }
        $this->view->metaData->jumpToSegmentIndex = 0;
    }

    /**
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    private function applyQualityFilter()
    {
        if ($this->getRequest()->getParam('qualities', '') != '') {
            $qualityState = new editor_Models_Quality_RequestState($this->getRequest()->getParam('qualities'), $this->getCurrentTask());
            $filter = $this->entity->getFilter();
            $filter->setQualityFilter($qualityState);
        }
    }
}
