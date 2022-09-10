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

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\SpellCheck\Base\Worker;

use editor_Models_Db_SegmentMeta;
use editor_Models_Segment;
use editor_Models_Segment_Meta;
use editor_Models_SegmentFieldManager;
use editor_Segment_Processing;
use editor_Segment_Quality_SegmentWorker;
use editor_Segment_Tags;
use MittagQI\Translate5\Plugins\SpellCheck\Base\ConfigurationInterface;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Enum\SegmentState;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\AbstractException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\MalfunctionException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\SegmentProcessorInterface;
use Zend_Db_Select;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;

abstract class AbstractImport extends editor_Segment_Quality_SegmentWorker
{
    /**
     * Allowed values for setting resourcePool
     *
     * @var array(strings)
     */
    protected static $allowedResourcePools = ['default', 'gui', 'import'];

    protected ?ZfExtended_Logger $logger;

    protected string $malfunctionState;

    protected array $loadedSegmentIds;

    /**
     * Resource pool key
     *
     * @var string
     */
    protected $resourcePool = 'import';

    /**
     * Whether multiple workers are allowed to run simultaneously per task
     *
     * @var string
     */
    protected $onlyOncePerTask = false;

    abstract protected function setParams(array $parameters): void;

    abstract protected function getConfiguration(): ConfigurationInterface;

    abstract protected function getMalfunctionStateRecheck(): string;

    abstract protected function getMalfunctionStateDefect(): string;

    abstract protected function getProcessor(): SegmentProcessorInterface;

    abstract protected function getMetaColumnName(): string;

    /**
     * Init worker
     *
     * @param string $taskGuid
     * @param array $parameters
     * @return bool
     */
    public function init($taskGuid = NULL, $parameters = []): bool
    {
        // Call parent
        $return = parent::init($taskGuid, $parameters);

        // (Re)set logger to null
        $this->logger = null;

        $this->setParams($parameters);

        // Return flag indicating whether worker initialization was successful
        return $return;
    }

    /**
     * Get logger instance (will be created if not exists)
     *
     * @return ZfExtended_Logger
     *
     * @throws Zend_Exception
     */
    public function getLogger(): ZfExtended_Logger
    {
        return $this->logger ?? $this->logger = Zend_Registry::get('logger')->cloneMe($this->getLoggerDomain());
    }

    protected function getLoggerDomain(): string
    {
        return $this->getConfiguration()::getLoggerDomain($this->processingMode);
    }

    /**
     * {@inheritDoc}
     * @see editor_Models_Import_Worker_ResourceAbstract::getAvailableSlots()
     */
    protected function getAvailableSlots($resourcePool = 'default'): array
    {
        return $this->getConfiguration()->getAvailableResourceSlots($resourcePool);
    }

    /**
     * Load array of editor_Models_Segment instances to be checked
     *
     * @param string $slot
     * @return array
     */
    protected function loadNextSegments(string $slot): array
    {

        // At this stage we assume that malfunction state is a state indicating that
        // something went wrong while last attempt to check loaded segments
        $this->malfunctionState = $this->getMalfunctionStateRecheck();

        // Load a list of segmentIds which are not yet checked
        $this->loadedSegmentIds = $this->loadUncheckedSegmentIds();

        // If nothing loaded
        if (!$this->loadedSegmentIds) {

            // If the loading of rechecked segments does not work we need to set them to be defected
            $this->malfunctionState = $this->getMalfunctionStateDefect();

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

        $segments = [];
        // Foreach segmentId - load segment instance and add to $segments array
        foreach ($this->loadedSegmentIds as $segmentId) {
            $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
            $segment->load($segmentId);
            $segments[] = $segment;
        }

        // Return array of loaded segment instances
        return $segments;
    }

    /**
     * Process segments
     *
     * @param array $segments
     * @param string $slot
     * @return bool
     */
    protected function processSegments(array $segments, string $slot): bool
    {

        // Get segments tags from segments
        $segmentsTags = editor_Segment_Tags::fromSegments(
            $this->task, $this->processingMode, $segments, editor_Segment_Processing::isOperation($this->processingMode)
        );

        // Process segments tags
        return $this->processSegmentsTags($segmentsTags, $slot);
    }

    /**
     * Process segments tags
     *
     * @param array $segmentsTags
     * @param string $slot
     * @return bool
     */
    protected function processSegmentsTags(array $segmentsTags, string $slot): bool
    {
        try {
            $processor = $this->getProcessor();
            $processor->process($segmentsTags, $slot);

            // Set segments meta state as 'checked'
            $this->setSegmentsState($this->loadedSegmentIds, SegmentState::SEGMENT_STATE_CHECKED);
        } catch (MalfunctionException $exception) {
            // If Malfunction exception caught, it means the LanguageTool is up, but HTTP response code was not 2xx, so that
            // - we set the segments status to 'recheck', so each segment will be checked again, segment by segment, not in a bulk manner,
            //   but if while running one-by-one recheck it will result the same problem, then each status will be set as 'defect' one-by-one
            // - we log all the data producing the error.

            // Set segments status to 'recheck'
            $this->setSegmentsState($this->loadedSegmentIds, $this->malfunctionState);

            // Add task to exception extra data
            $exception->addExtraData([
                'task' => $this->task,
                $this->getMetaColumnName() => $this->malfunctionState,
                'segmentIds' => $this->loadedSegmentIds,
            ]);

            // Do log
            $this->getLogger()->exception($exception, [
                'level' => ZfExtended_Logger::LEVEL_WARN,
                'domain' => $this->getLoggerDomain()
            ]);
        } catch (AbstractException $exception) {
            // If it was Down exception - disable slot
            if ($exception instanceof DownException) {
                $this->getConfiguration()->disableResourceSlot($slot);
            }

            // If we run in a timeout then set status recheck, so that the affected segments are checked lonely not as batch
            // If we are in the recheck loop the timeout should be handled as malfunction
            $state = $exception instanceof TimeOutException
                ? $this->malfunctionState // This is either recheck or defect (later if we are already processing rechecks)
                : SegmentState::SEGMENT_STATE_UNCHECKED;

            // Set status
            $this->setSegmentsState($this->loadedSegmentIds, $state);

            // Add task to exception extra data
            $exception->addExtraData([
                'task' => $this->task,
                $this->getMetaColumnName() => $state,
                'loadedSegmentIds' => $this->loadedSegmentIds
            ]);

            // Do log
            $this->getLogger()->exception($exception, [
                'domain' => $this->getLoggerDomain()
            ]);
        }
        return true;
    }

    /**
     * Sets meta of the given segment ids to the given state
     *
     * @param int[] $segments
     * @param string $state
     */
    private function setSegmentsState(array $segments, string $state): void
    {
        $segMetaDb = ZfExtended_Factory::get(editor_Models_Db_SegmentMeta::class);

        // Update segments meta to $state
        $segMetaDb->update([$this->getMetaColumnName() => $state], [
            'taskGuid = ?' => $this->task->getTaskGuid(),
            'segmentId in (?)' => $segments,
        ]);
    }

    /**
     * Load a list of segmentIds which are not yet checked
     *
     * @return int[]
     */
    private function loadUncheckedSegmentIds(): array
    {
        $db = ZfExtended_Factory::get(editor_Models_Db_SegmentMeta::class);
        $columnName = $this->getMetaColumnName();

        // Get unchecked segments ids
        $db->getAdapter()->beginTransaction();
        $sql = $db->select()
            ->from($db, ['segmentId'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where(sprintf('%1$s IS NULL OR %1$s IN (?)', $columnName), [SegmentState::SEGMENT_STATE_UNCHECKED])
            ->order('id')
            ->limit($this->getConfiguration()->getSegmentsPerCallAmount())
            ->forUpdate(Zend_Db_Select::FU_MODE_SKIP);
        $segmentIds = $db->fetchAll($sql)->toArray();

        // If not empty
        if ($segmentIds = array_column($segmentIds, 'segmentId')) {

            // Lock those segments by setting their status as 'inprogress', so that they won't be touched by other workers
            $db->update([$columnName => SegmentState::SEGMENT_STATE_INPROGRESS], [
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
     * @return int[]
     */
    private function loadNextRecheckSegmentId(): array
    {
        $dbMeta = ZfExtended_Factory::get(editor_Models_Db_SegmentMeta::class);
        $columnName = $this->getMetaColumnName();

        // Get list of segments to be rechecked limited to 1
        $sql = $dbMeta->select()
            ->from($dbMeta, ['segmentId'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where(sprintf('%1$s IS NULL OR %1$s = ?', $columnName), [SegmentState::SEGMENT_STATE_RECHECK])
            ->limit(1);

        // Return an array containing 1 segmentId
        return array_column($dbMeta->fetchAll($sql)->toArray(), 'segmentId');
    }

    private function reportDefectSegments(): void
    {
        $dbMeta = ZfExtended_Factory::get(editor_Models_Db_SegmentMeta::class);

        $columnName = $this->getMetaColumnName();

        // Search for of defect segments
        $sql = $dbMeta->select()
            ->from($dbMeta, ['segmentId', $columnName])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where(sprintf('%1$s IS NULL OR %1$s IN (?)', $columnName), [
                SegmentState::SEGMENT_STATE_DEFECT
            ]);
        $defectSegments = $dbMeta->fetchAll($sql)->toArray();

        // If nothing found - return
        if (empty($defectSegments)) {
            return;
        }

        // Messages
        $segmentsToLog = [];

        // Foreach defect segments
        foreach ($defectSegments as $defectSegment) {
            $segment = ZfExtended_Factory::get(editor_Models_Segment::class);

            // Load segment
            $segment->load($defectSegment['segmentId']);

            $fieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);

            // Init fields
            $fieldManager->initFields($this->workerModel->getTaskGuid());

            // Setup a message
            if ($defectSegment[$columnName] === SegmentState::SEGMENT_STATE_DEFECT) {
                $segmentsToLog [] = $segment->getSegmentNrInTask() . '; Source-Text: ' . strip_tags($segment->get($fieldManager->getFirstSourceName()));
            } else {
                $segmentsToLog [] = $segment->getSegmentNrInTask() . ': Segment too long for to be checked';
            }
        }

        // Do log
        $this->getLogger()->warn('E1123', 'Some segments could not be checked by the ' . self::class, [
            'task' => $this->task,
            'uncheckableSegments' => $segmentsToLog,
        ]);
    }

    /**
     * @throws DownException
     */
    protected function raiseNoAvailableResourceException() {
        // E1411 No reachable LanguageTool instances available, please specify LanguageTool urls to import this task.
        throw new DownException('E1411', [
            'task' => $this->task
        ]);
    }

    /*************************** SINGLE SEGMENT PROCESSING ***************************/
    protected function processSegmentTags(editor_Segment_Tags $tags, string $slot) : bool {
        return true;
    }

    /**
     * Calculates the progress based on the spellcheckState field in LEK_segments_meta
     *
     * @return float
     */
    protected function calculateProgressDone() : float
    {
        $meta = ZfExtended_Factory::get(editor_Models_Segment_Meta::class);

        $states = [
            SegmentState::SEGMENT_STATE_CHECKED,
        ];

        return $meta->calculateSegmentProgressByStatesAndColumn($this->taskGuid, $states, $this->getMetaColumnName());
    }
}
