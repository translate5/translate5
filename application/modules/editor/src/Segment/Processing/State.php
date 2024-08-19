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
use Zend_Db_Exception;
use Zend_Db_Select;
use Zend_Db_Table_Row_Abstract;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Db_DeadLockHandlerTrait;
use ZfExtended_Models_Db_Exceptions_DeadLockHandler;

/**
 * This class represents the segment-state when processing segments with a segment-processor, usually looped
 * It work's with LEK_segment_processing ad DB-model
 * It saves the state of the processing during the operation and - with the slitary operations as exception - the serialized tags-model
 */
class State
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

        return new State($serviceId, $row);
    }

    private string $serviceId;

    private int $state = self::UNPROCESSED;

    private int $segmentId;

    private ?Zend_Db_Table_Row_Abstract $row;

    /**
     * If instantiated without $row the instance can only be used to save states non-persistent
     * Or to use the API not dealing with our row
     */
    public function __construct(string $serviceId, Zend_Db_Table_Row_Abstract $row = null)
    {
        $this->serviceId = $serviceId;
        $this->segmentId = (is_null($row)) ? -1 : $row->segmentId;
        if (! isset(static::$table)) { // @phpstan-ignore-line
            static::$table = new Processing();
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
    public function setState(int $newState)
    {
        $this->state = $newState;
        if ($this->row !== null) {
            $column = $this->getColumnName();
            $this->row->$column = $newState;
            // this assumes, multiple segments cannot be processed simultaneously
            $this->row->processing = ($newState === self::INPROGRESS) ? 1 : 0;
            $this->row->save();
        }
    }

    /**
     * Sets the state to "processed"
     */
    public function setProcessed()
    {
        $this->setState(self::PROCESSED);
    }

    public function getColumnName(): string
    {
        return static::createColumnName($this->serviceId);
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
     * Saves JSON back to the tags-model
     */
    public function saveTagsJson(string $jsonString)
    {
        $this->row->tagsJson = $jsonString;
        $this->setState(self::PROCESSED); // this also saves the row
    }

    /**
     * Retrieves the progress of processed segments for the given task. This is a float 0 <= num <= 1
     */
    public function calculateProgress(string $taskGuid): float
    {
        return static::$table->calculateProgress($taskGuid, $this->serviceId);
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

            $db = static::$table->getAdapter();

            $db->beginTransaction();

            $where = static::$table->select()
                ->forUpdate(Zend_Db_Select::FU_MODE_SKIP)
                ->where('`taskGuid` = ?', $taskGuid)
                ->where('`processing` = ?', 0) // CRUCIAL: exclude segments processed by other processors
                ->where(static::$table->getAdapter()->quoteIdentifier($column) . ' = ?', $state)
                ->order('segmentId ' . ($fromTheTop ? 'ASC' : 'DESC'))
                ->limit($limit);
            foreach (static::$table->fetchAll($where) as $row) {
                $segmentIds[] = $row->segmentId;
                $states[] = new static($this->serviceId, $row);
            }
            if (count($segmentIds) > 1) {
                // @phpstan-ignore-next-line
                static::$table->update([
                    $column => self::INPROGRESS,
                    'processing' => 1,
                ], [
                    'segmentId IN (?)' => $segmentIds,
                ]);
            } elseif (count($segmentIds) === 1) {
                // first row of foreach loop
                $row->$column = self::INPROGRESS;
                $row->processing = 1;
                $row->save();
            }

            $db->commit();

            return $states;
        });
    }

    /**
     * Retrieves, if the table has blocked states (segments to process, that are currently processed by others)
     */
    public function hasBlockedUnprocessed(string $taskGuid): bool
    {
        $where = static::$table->select()
            ->where('`taskGuid` = ?', $taskGuid)
            // blocked by others
            ->where('`processing` = ?', 1)
            // not yet processed
            ->where(
                static::$table->getAdapter()->quoteIdentifier($this->getColumnName()) . ' < ?',
                self::INPROGRESS
            )
            // no need to get them all ...
            ->limit(1);

        return static::$table->fetchAll($where)->count() > 0;
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
