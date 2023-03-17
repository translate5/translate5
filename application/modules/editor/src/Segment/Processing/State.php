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
use Zend_Db_Statement_Exception;
use Zend_Db_Table_Row_Abstract;
use ZfExtended_Factory;
use ZfExtended_Models_Db_Exceptions_DeadLockHandler;


/**
 * This class represents the segment-state when processing segments with a segment-processor, usually looped
 * It work's with LEK_segment_processing ad DB-model
 * It saves the state of the processing during the operation and - with the slitary operations as exception - the serialized tags-model
 */
class State
{
    /**
     * A segment was not yet processed
     */
    const UNPROCESSED = 0;
    /**
     * A segment must be retried to process
     */
    const REPROCESS = 1;
    /**
     * A segment is being processed
     */
    const INPROGRESS = 2;
    /**
     * A segment was successfully processed
     */
    const PROCESSED = 3;
    /**
     * A segment cannot be processed, either due to constraints of the segment (empty, invalid, ...) or because after 2 attempts it simply did not work
     */
    const UNPROCESSABLE = 4;
    /**
     * Segment is too long to be processed. This is a special for the termtagger
     */
    const TOOLONG = 5;
    /**
     * Segment has to be ignored presumably due to not being editable
     */
    const IGNORED = 6;

    /**
     * All States have this suffix for their column name
     */
    const COLUMN_SUFFIX = 'State';

    /**
     * In case of an deadlock we retry the operation after sleeping the below amount
     */
    const DEADLOCK_MAXRETRIES = 3;

    /**
     * In case of an DB-deadlock we wait this amount of time before trying again. Milliseconds
     */
    const DEADLOCK_WAITINGTIME = 350;

    /**
     * @var Processing
     */
    private static Processing $table;

    /**
     * Creates the colmn-name for a service that holds the processing-state
     * @param string $serviceId
     * @return string
     */
    public static function createColumnName(string $serviceId): string
    {
        return $serviceId . self::COLUMN_SUFFIX;
    }

    /**
     * Creates a tags-model for the given segment saving the state for the given service
     * @param int $segmentId
     * @param string $serviceId
     * @return State
     */
    public static function createForSegment(int $segmentId, string $serviceId): State
    {
        $table = new Processing();
        $row = $table->fetchRow($table->select()->where('segmentId = ?', $segmentId));
        if ($row === null) {
            $row = $table->createRow(['segmentId' => $segmentId]);
        }
        return new State($serviceId, $row);
    }


    /**
     * @var string
     */
    private string $serviceId;

    /**
     * @var int
     */
    private int $state = self::UNPROCESSED;

    /**
     * @var int
     */
    private int $segmentId;

    /**
     * @var Zend_Db_Table_Row_Abstract|null
     */
    private ?Zend_Db_Table_Row_Abstract $row;


    /**
     * If instantiated without $row the instance can only be used to save states non-persistent
     * Or to use the API not dealing with our row
     * @param string $serviceId
     * @param Zend_Db_Table_Row_Abstract|null $row
     */
    public function __construct(string $serviceId, Zend_Db_Table_Row_Abstract $row = null)
    {
        $this->serviceId = $serviceId;
        $this->segmentId = (is_null($row)) ? -1 : $row->segmentId;
        if (!isset(static::$table)) {
            static::$table = new Processing();
        }
        $this->row = $row;
    }

    /**
     * Retrieves the current state
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Sets a new state for the entry and saves it
     * @param int $newState
     */
    public function setState(int $newState)
    {
        $this->state = $newState;
        if ($this->row !== null) {
            $column = $this->getColumnName();
            $this->row->$column = $newState;
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

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return static::createColumnName($this->serviceId);
    }

    /**
     * @return int
     */
    public function getSegmentId(): int
    {
        return $this->segmentId;
    }

    /**
     * returns our related segment
     * Use only when instance instantiated with a row, otherwise this will lead to an exception
     * @return editor_Models_Segment
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
     * @param editor_Models_Task $task
     * @param string $processingMode
     * @return editor_Segment_Tags
     * @throws Exception
     */
    public function getSegmentTags(editor_Models_Task $task, string $processingMode): editor_Segment_Tags
    {
        return editor_Segment_Tags::fromState($task, $processingMode, $this);
    }

    /**
     * Checks if the tags-model holds a serialized segment
     * @return bool
     */
    public function hasTagsJson(): bool
    {
        return !empty($this->row->tagsJson);
    }

    /**
     * Retrieves the serialized segment as string
     * @return string
     */
    public function getTagsJson(): string
    {
        return $this->row->tagsJson;
    }

    /**
     * Saves JSON back to the tags-model
     * @param string $jsonString
     */
    public function saveTagsJson(string $jsonString)
    {
        $this->row->tagsJson = $jsonString;
        $this->setState(self::PROCESSED); // this also saves the row
    }

    /**
     * Retrieves the progress of processed segments for the given task. This is a float 0 <= num <= 1
     * @param string $taskGuid
     * @return float
     */
    public function calculateProgress(string $taskGuid): float
    {
        return static::$table->calculateProgress($taskGuid, $this->serviceId);
    }

    /**
     * Starts a transaction on our Processing table
     */
    public function beginTransaction()
    {
        static::$table->getAdapter()->beginTransaction();
    }

    /**
     * Commit a transaction on our Processing table
     */
    public function commitTransaction()
    {
        static::$table->getAdapter()->commit();
    }

    /**
     * Retrieves the next states to process and sets their state to INPROGRESS
     * In this transaction deadlocks may occur so we have a deadlock-catching/retrying implemented
     * @param int $state
     * @param string $taskGuid
     * @param bool $fromTheTop : if we should fetch from the top or bottom of the table
     * @param int $limit
     * @return State[]
     * @throws Zend_Db_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Db_Exceptions_DeadLockHandler
     */
    public function fetchNextStates(int $state, string $taskGuid, bool $fromTheTop, int $limit = 1): array
    {
        $retries = 1;
        while ($retries <= self::DEADLOCK_MAXRETRIES) {
            try {
                return $this->fetchNextFromDb($state, $taskGuid, $fromTheTop, $limit);
            } catch (Zend_Db_Exception | Zend_Db_Statement_Exception $dbException) {
                $isDeadlockException = $this->isDeadlockException($dbException);
                if ($isDeadlockException && $retries === self::DEADLOCK_MAXRETRIES) {
                    throw new ZfExtended_Models_Db_Exceptions_DeadLockHandler('E1201', ['retries' => $retries], $dbException);
                } else if (!$isDeadlockException) {
                    throw $dbException;
                }
                error_log('Deadlock when fetching next states from ' . static::$table->getName() . ', attempt ' . $retries . ': ' . $dbException->getMessage());
                $retries++;
                usleep(self::DEADLOCK_WAITINGTIME);
            }
        }
        return []; // only to avoid PHPstorm warnings, the code never will get here ...
    }

    /**
     * Internal method to fetch next states from the DB
     * @param int $state
     * @param string $taskGuid
     * @param bool $fromTheTop
     * @param int $limit
     * @return State[]
     */
    private function fetchNextFromDb(int $state, string $taskGuid, bool $fromTheTop, int $limit): array
    {
        $states = [];
        $segmentIds = [];
        $column = $this->getColumnName();
        static::$table->getAdapter()->beginTransaction();
        $where = static::$table->select()
            ->forUpdate(true)
            ->where('`taskGuid` = ?', $taskGuid)
            ->where(static::$table->getAdapter()->quoteIdentifier($column) . ' = ?', $state)
            ->order('segmentId ' . ($fromTheTop ? 'ASC' : 'DESC'))
            ->limit($limit);
        foreach (static::$table->fetchAll($where) as $row) {
            $segmentIds[] = $row->segmentId;
            $states[] = new static($this->serviceId, $row);
        }
        if (count($segmentIds) > 1) {
            static::$table->update([$column => self::INPROGRESS], ['segmentId IN (?)' => $segmentIds]);
        } else if (count($segmentIds) === 1) {
            // first row of foreach loop
            $row->$column = self::INPROGRESS;
            $row->save();
        }
        static::$table->getAdapter()->commit();
        return $states;
    }

    /**
     * TODO FIXME: add as general API to ZfExtended as it is used here, in TaskUserAssoc and in ZfExtended_Models_Db_DeadLockHandlerTrait (where it cannot be added as static function)
     * @param Zend_Db_Exception $e
     * @return bool
     */
    private function isDeadlockException(Zend_Db_Exception $e): bool
    {
        $message = $e->getMessage();
        return (str_contains($message, 'Deadlock found when trying to get lock') || str_contains($message, 'Lock wait timeout exceeded'));
    }
}
