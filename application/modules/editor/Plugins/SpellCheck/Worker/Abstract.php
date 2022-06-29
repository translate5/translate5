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

/**
 * Base Implementation of the SpellCheck worker.
 * Checks spell, grammar and style for each segment on task import
 */
abstract class editor_Plugins_SpellCheck_Worker_Abstract extends editor_Segment_Quality_SegmentWorker {
    
    /**
     * Allowed values for setting resourcePool
     *
     * @var array(strings)
     */
    protected static $allowedResourcePools = ['default', 'gui', 'import'];

    /**
     * Prefix for workers resource-name
     *
     * @var string
     */
    protected static $praefixResourceName = 'SpellCheck_';
    
    /**
     * overwrites $this->workerModel->maxLifetime
     */
    protected $maxLifetime = '2 HOUR';

    /**
     * Whether multiple workers are allowed to run simultaneously per task
     *
     * @var string
     */
    protected $onlyOncePerTask = false;

    /**
     * Default resourcePool for the different SpellCheck-operations
     * Possible Values: $this->allowedResourcePools = ['default', 'gui', 'import'];
     *
     * @var string
     */
    protected $resourcePool = 'default';

    /**
     * @var ZfExtended_Logger
     */
    private $logger;

    /**
     * @var editor_Plugins_SpellCheck_Configuration
     */
    private $config;

    /**
     * @var string
     */
    private $malfunctionState;

    /**
     * @var array
     */
    private $loadedSegmentIds;

    /**
     * @var editor_Segment_Tags
     */
    private $proccessedTags;

    /**
     * SpellCheck segment processor instance
     *
     * @var editor_Plugins_SpellCheck_SegmentProcessor
     */
    protected static $_processor = null;

    /**
     * Language that will be passed as a param within LanguageTool-request along with segment text for spellcheck
     *
     * @var null
     */
    private $spellCheckLang = null;

    /**
     * Init worker
     *
     * @param null $taskGuid
     * @param array $parameters
     * @return bool
     */
    public function init($taskGuid = NULL, $parameters = []) {

        // Call parent
        $return = parent::init($taskGuid, $parameters);

        // Get config
        $this->config = new editor_Plugins_SpellCheck_Configuration($this->task);

        // (Re)set logger to null
        $this->logger = null;
        $this->proccessedTags = null;

        // Get language to be passed within LanguageTool-request params (if task target language is supported)
        $this->spellCheckLang = $parameters['spellCheckLang'];

        // Return flag indicating whether worker initialization was successful
        return $return;
    }

    /**
     * Get SpellCheck segment processor instance
     *
     * @return editor_Plugins_SpellCheck_SegmentProcessor|null
     */
    public function getProcessor() {
        return self::$_processor ?? self::$_processor = ZfExtended_Factory::get('editor_Plugins_SpellCheck_SegmentProcessor');
    }

    /**
     * Update the progres based on the checked field in lek segments meta
     *
     * @inheritdoc
     * @see ZfExtended_Worker_Abstract::updateProgress()
     * @param float $progress
     */
    public function updateProgress(float $progress = 1) {

        /* @var $meta editor_Models_Segment_Meta */
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');

        // Get progress
        $progress = $meta->getSpellcheckSegmentProgress($this->taskGuid);

        // Call parent
        parent::updateProgress($progress);
    }

    /**
     * Get logger instance (will be created if not exists)
     *
     * @return ZfExtended_Logger
     */
    public function getLogger() : ZfExtended_Logger {
        return $this->logger ?? $this->logger = Zend_Registry
            ::get('logger')->cloneMe(
                $this->config->getLoggerDomain($this->processingMode)
            );
    }

    /**
     * Needed Implementation for editor_Models_Import_Worker_ResourceAbstract
     *
     * @inheritdoc
     * @see editor_Models_Import_Worker_ResourceAbstract::getAvailableSlots()
     * @param string $resourcePool
     * @return array
     */
    protected function getAvailableSlots($resourcePool = 'default'): array {
        return $this->config->getAvailableResourceSlots($resourcePool);
    }

    /**
     * @throws editor_Plugins_TermTagger_Exception_Down
     */
    protected function raiseNoAvailableResourceException() {
        // E1131 No reachable LanguageTool instances available, please specify LanguageTool urls to import this task.
        throw new editor_Plugins_SpellCheck_Exception_Down('E1131', [
            'task' => $this->task
        ]);
    }

    /**
     * Load array of editor_Models_Segment instances to be spell-checked
     *
     * @param string $slot
     * @return array
     */
    protected function loadNextSegments(string $slot): array {

        // At this stage we assume that malfunction state is a state indicating that
        // something went wrong while last attempt to spell-check loaded segments
        $this->malfunctionState = editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_RECHECK;

        // Load a list of segmentIds which are not yet spell-checked
        $this->loadedSegmentIds = $this->loadUncheckedSegmentIds();

        // If nothing loaded
        if (!$this->loadedSegmentIds) {

            // If the loading of rechecked segments does not work we need to set them to be defect
            $this->malfunctionState = editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_DEFECT;

            // Try load a list of IDs of segments to be rechecked
            $this->loadedSegmentIds = $this->loadNextRecheckSegmentId();

            // If nothing loaded
            if (!$this->loadedSegmentIds) {

                // Report defect segments
                $this->reportDefectSegments();

                // Return empty array as we found nothing to be processed
                return [];
            }
        }

        // Foreach segmentId - load segment instance and add to $segments array
        foreach ($this->loadedSegmentIds as $segmentId) {
            /* @var $segment editor_Models_Segment */
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            $segment->load($segmentId);
            $segments[] = $segment;
        }

        // Return array of loaded segment instances
        return $segments ?? [];
    }

    /**
     * Process segments
     *
     * @param array $segments
     * @param string $slot
     * @return bool
     */
    protected function processSegments(array $segments, string $slot) : bool {

        // Get segments tags from segments
        $segmentsTags = editor_Segment_Tags::fromSegments(
            $this->task, $this->processingMode, $segments, editor_Segment_Processing::isOperation($this->processingMode)
        );

        // Process segments tags
        return $this->processSegmentsTags($segmentsTags, $slot);
    }

    /**
     * Propcess segments tags
     *
     * @param array $segmentsTags
     * @param string $slot
     * @return bool
     */
    protected function processSegmentsTags(array $segmentsTags, string $slot) : bool {

        // Try to process
        try {

            // Create processor instance
            $processor = new editor_Plugins_SpellCheck_SegmentProcessor(/*$this->task, $this->config, $this->processingMode, $this->isWorkerThread*/);

            // Do process
            $processor->process($segmentsTags, $slot, true, $this->spellCheckLang);

            // Set spellcheck state as 'checked'
            $this->setSpellcheckState($this->loadedSegmentIds, editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_CHECKED);
        }

        // If Malfunction exception caught, it means the LanguageTool is up, but HTTP response code was not 2xx, so that
        // - we set the segments status to 'recheck', so each segment will be checked again, segment by segment, not in a bulk manner,
        //   but if while running one-by-one recheck it will result the same problem, then each status will be set as 'defect' one-by-one
        // - we log all the data producing the error.
        catch (editor_Plugins_SpellCheck_Exception_Malfunction $exception) {

            // Set segments status to 'recheck'
            $this->setSpellcheckState($this->loadedSegmentIds, $this->malfunctionState);

            // Add task to exception extra data
            $exception->addExtraData([
                'task' => $this->task,
                'spellcheckState' => $this->malfunctionState,
                'segmentIds' => $this->loadedSegmentIds,
            ]);

            // Do log
            $this->getLogger()->exception($exception, [
                'level' => ZfExtended_Logger::LEVEL_WARN,
                'domain' => $this->config->getLoggerDomain($this->processingMode) // processingType ?
            ]);
        }

        // If other exception caught
        catch (editor_Plugins_SpellCheck_Exception_Abstract $exception) {

            // If it was Down exception - disable slot
            if ($exception instanceof editor_Plugins_SpellCheck_Exception_Down) {
                $this->config->disableResourceSlot($slot);
            }

            // If we run in a timeout then set status recheck, so that the affected segments are checked lonely not as batch
            // If we are in the recheck loop the timeout should be handled as malfunction
            $state = $exception instanceof editor_Plugins_SpellCheck_Exception_TimeOut
                ? $this->malfunctionState // This is either recheck or defect (later if we are already processing rechecks)
                : editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_UNCHECKED;

            // Set status
            $this->setSpellcheckState($this->loadedSegmentIds, $state);

            // Add task to exception extra data
            $exception->addExtraData([
                'task' => $this->task,
                'spellcheckState' => $state,
                'loadedSegmentIds' => $this->loadedSegmentIds
            ]);

            // Do log
            $this->getLogger()->exception($exception, [
                'domain' => $this->config->getLoggerDomain($this->processingMode)
            ]);
            /*if($exception instanceof editor_Plugins_TermTagger_Exception_Open) {
                //editor_Plugins_TermTagger_Exception_Open Exceptions mean mostly that there is problem with the TBX data
                //so we do not create a new worker entry, that imports the task without terminology markup then
                $this->task->setTerminologie(0);
                $this->task->save();
                return false;
            }*/
        }
        return true;
    }

    /**
     * Load a list of segmentIds which are not yet spell-checked
     *
     * @return array
     */
    private function loadUncheckedSegmentIds(): array {

        /* @var $db editor_Models_Db_SegmentMeta */
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');

        // Get unchecked segments ids
        $db->getAdapter()->beginTransaction();
        $sql = $db->select()
            ->from($db, ['segmentId'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('spellcheckState IS NULL OR spellcheckState IN (?)', [editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_UNCHECKED])
            ->order('id')
            ->limit(editor_Plugins_SpellCheck_Configuration::IMPORT_SEGMENTS_PER_CALL)
            ->forUpdate(Zend_Db_Select::FU_MODE_SKIP);
        $segmentIds = $db->fetchAll($sql)->toArray();

        // If not empty
        if ($segmentIds = array_column($segmentIds, 'segmentId')) {

            // Lock those segments by setting their status as 'inprogress', so that they won't be touched by other workers
            $db->update(['spellcheckState' => editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_INPROGRESS], [
                'taskGuid = ?' => $this->task->getTaskGuid(),
                'segmentId in (?)' => $segmentIds,
            ]);
        }

        // Commit the transaction
        $db->getAdapter()->commit();

        // Return unchecked segments ids (even if empty)
        return $segmentIds;
    }

    /**
     * Fetch a list with the next segmentId marked as to be "rechecked"
     * and return only one segment from that list since this segments has to be single checked
     *
     * @return array
     */
    private function loadNextRecheckSegmentId(): array {

        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');

        // Get list of segments to be rechecked limited to 1
        $sql = $dbMeta->select()
            ->from($dbMeta, ['segmentId'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('spellcheckState IS NULL OR spellcheckState = ?', [editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_RECHECK])
            ->limit(1);

        // Return an array containing 1 segmentId
        return array_column($dbMeta->fetchAll($sql)->toArray(), 'segmentId');
    }

    /**
     * Sets the meta SpellcheckState of the given segment ids to the given state
     *
     * @param editor_Models_Task $task
     * @param array $segments
     * @param string $state
     */
    private function setSpellcheckState(array $segments, $state) {

        /* @var $segMetaDb editor_Models_Db_SegmentMeta */
        $segMetaDb = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');

        // Update spellcheckState to $state
        $segMetaDb->update(['spellcheckState' => $state], [
            'taskGuid = ?' => $this->task->getTaskGuid(),
            'segmentId in (?)' => $segments,
        ]);
    }

    /**
     *
     */
    private function reportDefectSegments() {
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');

        // Search for of defect segments
        $sql = $dbMeta->select()
            ->from($dbMeta, ['segmentId', 'spellcheckState'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('spellcheckState IS NULL OR spellcheckState IN (?)', [
                editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_DEFECT,
                editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE
            ]);
        $defectSegments = $dbMeta->fetchAll($sql)->toArray();

        // If nothing found - return
        if (empty($defectSegments)) {
            return;
        }

        // Messages
        $segmentsToLog = [];

        // Foreach defetc segments
        foreach ($defectSegments as $defectsegment) {

            /* @var $segment editor_Models_Segment */
            $segment = ZfExtended_Factory::get('editor_Models_Segment');

            // Load segment
            $segment->load($defectsegment['segmentId']);

            /* @var $fieldManager editor_Models_SegmentFieldManager */
            $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');

            // Init fields
            $fieldManager->initFields($this->workerModel->getTaskGuid());

            // Setup a message
            if ($defectsegment['spellcheckState'] == editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_DEFECT) {
                $segmentsToLog []= $segment->getSegmentNrInTask().'; Source-Text: '.strip_tags($segment->get($fieldManager->getFirstSourceName()));
            } else {
                $segmentsToLog []= $segment->getSegmentNrInTask().': Segment too long for LanguageTool';
            }
        }

        // Do log
        $this->getLogger()->warn('E1123', 'Some segments could not be checked by the SpellCheck.', [
            'task' => $this->task,
            'uncheckableSegments' => $segmentsToLog,
        ]);
    }

    /*************************** SINGLE SEGMENT PROCESSING ***************************/
    protected function processSegmentTags(editor_Segment_Tags $tags, string $slot) : bool {
        return true;
    }
}
