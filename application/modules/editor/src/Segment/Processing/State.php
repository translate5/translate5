<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Segment\Processing;

use editor_Models_Segment;
use editor_Models_Task;
use editor_Segment_Tags;
use Exception;
use MittagQI\Translate5\Segment\Db\Processing;
use MittagQI\Translate5\Segment\Db\ProcessingRow;
use Zend_Db_Exception;
use Zend_Db_Select;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Db_DeadLockHandlerTrait;
use ZfExtended_Models_Db_Exceptions_DeadLockHandler;

/**
 * This class represents the segment-state when processing segments with a segment-processor, usually looped
 * It work's with LEK_segment_processing ad DB-model
 * It saves the state of the processing during the operation and - with the slitary operations as exception - the serialized tags-model
 */
final class State
{
    use ZfExtended_Models_Db_DeadLockHandlerTrait;

    /**
     * A segment was not yet processed
     */
    public const UNPROCESSED = 0;

    /**
     * A segment must be retried to process
     */
    public const REPROCESS = 1;

    /**
     * A segment is being processed
     */
    public const INPROGRESS = 2;

    /**
     * A segment was successfully processed
     */
    public const PROCESSED = 3;

    /**
     * A segment cannot be processed, either due to constraints of the segment (empty, invalid, ...) or because after 2 attempts it simply did not work
     */
    public const UNPROCESSABLE = 4;

    /**
     * Segment is too long to be processed. This is a special for the termtagger
     */
    public const TOOLONG = 5;

    /**
     * Segment has to be ignored presumably due to not being editable
     */
    public const IGNORED = 6;

    /**
     * All States have this suffix for their column name
     */
    public const COLUMN_SUFFIX = 'State';

    /**
     * In case of an deadlock we retry the operation after sleeping the below amount
     */
    public const DEADLOCK_MAXRETRIES = 3;

    /**
     * In case of an DB-deadlock we wait this amount of time before trying again. Milliseconds
     */
    public const DEADLOCK_WAITINGTIME = 350;

    private static Processing $table;

    /**
     * Creates the colmn-name for a service that holds the processing-state
     */
    public static function createColumnName(string $serviceId): string
    {
        return $serviceId . self::COLUMN_SUFFIX;
    }

    /**
     * Creates a tags-model for the given segment saving the state for the given service
     */
    public static function createForSegment(int $segmentId, string $serviceId): State
    {
        $table = new Processing();
        $row = $table->fetchRow($table->select()->where('segmentId = ?', $segmentId));
        if ($row === null) {
            $row = $table->createRow([
                'segmentId' => $segmentId,
            ]);
        }
        /** @var ProcessingRow $row */

        return new State($serviceId, $row);
    }

    private string $serviceId;

    private int $state = self::UNPROCESSED;

    private int $segmentId;

    private ?ProcessingRow $row;

    /**
     * If instantiated without $row the instance can only be used to save states non-persistent
     * Or to use the API not dealing with our row
     */
    public function __construct(string $serviceId, ProcessingRow $row = null)
    {
        $this->serviceId = $serviceId;
        $this->segmentId = (is_null($row)) ? -1 : (int) $row->segmentId;
        if (! isset(self::$table)) {
            self::$table = new Processing();
        }
        $this->row = $row;
    }

    /**
     * Retrieves the current state
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Sets a new state for the entry and saves it
     */
    public function saveState(int $newState): void
    {
        $this->state = $newState;
        if ($this->row !== null) {
            $this->retryOnDeadlock(function () use ($newState) {
                $column = $this->getColumnName();
                $this->row->$column = $newState;
                // this assumes, multiple segments cannot be processed simultaneously
                // @phpstan-ignore-next-line - why is phpstan not seeing __get(...) ?
                $this->row->processing = ($newState === self::INPROGRESS) ? 1 : 0;
                $this->row->save();
            });
        }
    }

    /**
     * Checks if a state is still globally processing
     */
    public function isProcessing(): bool
    {
        if ($this->row !== null) {
            // important: dirty data will always be seen as "processing" as it means
            //the rowset somehow was changed but not saved by the processor
            return $this->row->isDirty() || (int) $this->row->processing === 1;
        }

        return false;
    }

    /**
     * Sets the state to "processed" - what alo ends the internal global "processing" state
     */
    public function setProcessed(): void
    {
        $this->saveState(self::PROCESSED);
    }

    public function getColumnName(): string
    {
        return self::createColumnName($this->serviceId);
    }

    public function getSegmentId(): int
    {
        return $this->segmentId;
    }

    /**
     * returns our related segment
     * Use only when instance instantiated with a row, otherwise this will lead to an exception
     */
    public function getSegment(): editor_Models_Segment
    {
        $row = ZfExtended_Factory::get(editor_Models_Segment::class);
        $row->load($this->segmentId);

        return $row;
    }

    /**
     * Retrieves the segment-tags model for the given task & processing mode
     * The segment-tags model will use us to save back the state and save back the processed tags
     * @throws Exception
     */
    public function getSegmentTags(editor_Models_Task $task, string $processingMode): editor_Segment_Tags
    {
        return editor_Segment_Tags::fromState($task, $processingMode, $this);
    }

    /**
     * Checks if the tags-model holds a serialized segment
     */
    public function hasTagsJson(): bool
    {
        return ! empty($this->row->tagsJson);
    }

    /**
     * Retrieves the serialized segment as string
     */
    public function getTagsJson(): string
    {
        return $this->row->tagsJson;
    }

    /**
     * Saves JSON back to the tags-model and sets the state to processed
     */
    public function saveTagsJson(string $jsonString)
    {
        $this->row->tagsJson = $jsonString;
        $this->setProcessed(); // this also saves the row
    }

    /**
     * Retrieves the progress of processed segments for the given task. This is a float 0 <= num <= 1
     */
    public function calculateProgress(string $taskGuid): float
    {
        return self::$table->calculateProgress($taskGuid, $this->serviceId);
    }

    /**
     * Retrieves the next states to process and sets their state to INPROGRESS
     * In this transaction deadlocks may occur so we have a deadlock-catching/retrying implemented
     * @param bool $fromTheTop : if we should fetch from the top or bottom of the table
     * @return State[]
     * @throws Zend_Db_Exception
     * @throws ZfExtended_Models_Db_Exceptions_DeadLockHandler
     * @throws Zend_Exception
     */
    public function fetchNextStates(int $state, string $taskGuid, bool $fromTheTop, int $limit = 1): array
    {
        // wrap query in the deadlock-retry helper since this table is potentially fetched by multiple workers/loopers at the same time ...
        return $this->retryOnDeadlock(function () use ($state, $taskGuid, $fromTheTop, $limit) {
            $states = [];
            $segmentIds = [];
            $column = $this->getColumnName();

            $db = self::$table->getAdapter();

            $db->beginTransaction(); //needed to encapsulate select forUpdate and update

            $where = self::$table->select()
                ->forUpdate(Zend_Db_Select::FU_MODE_SKIP)
                ->where('`taskGuid` = ?', $taskGuid)
                ->where('`processing` = ?', 0) // CRUCIAL: exclude segments processed by other processors
                ->where(self::$table->getAdapter()->quoteIdentifier($column) . ' = ?', $state)
                ->order('segmentId ' . ($fromTheTop ? 'ASC' : 'DESC'))
                ->limit($limit);

            try {
                /** @var ProcessingRow[] $rows */
                $rows = self::$table->fetchAll($where);

                // to improve db-performance we save multiple segments at once
                if (count($rows) > 0) {
                    foreach ($rows as $row) {
                        $segmentIds[] = $row->segmentId;
                    }
                    self::$table->update([
                        $column => self::INPROGRESS,
                        'processing' => 1,
                    ], [
                        'segmentId IN (?)' => $segmentIds,
                    ]);
                    // ugly: to have a up-to-date row we mimic the update stuff for each Zend-row
                    foreach ($rows as $row) {
                        $row->mimicStateUpdate($column, self::INPROGRESS);
                        $states[] = new static($this->serviceId, $row);
                    }
                }
                $db->commit();
            } catch (Zend_Db_Exception $e) {
                $db->rollBack();

                throw $e;
            }

            return $states;
        });
    }

    /**
     * Retrieves, if the table has blocked states (segments to process, that are currently processed by others)
     */
    public function hasBlockedUnprocessed(string $taskGuid): bool
    {
        $where = self::$table->select()
            ->where('`taskGuid` = ?', $taskGuid)
            // blocked by others
            ->where('`processing` = ?', 1)
            // not yet processed
            ->where(
                self::$table->getAdapter()->quoteIdentifier($this->getColumnName()) . ' < ?',
                self::INPROGRESS
            )
            // no need to get them all ...
            ->limit(1);

        return self::$table->fetchAll($where)->count() > 0;
    }

    /**
     * TODO FIXME: add as general API to ZfExtended as it is used here, in TaskUserAssoc and in ZfExtended_Models_Db_DeadLockHandlerTrait (where it cannot be added as static function)
     */
    private function isDeadlockException(Zend_Db_Exception $e): bool
    {
        $message = $e->getMessage();

        return (str_contains($message, 'Deadlock found when trying to get lock') || str_contains($message, 'Lock wait timeout exceeded'));
    }
}
