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

namespace MittagQI\Translate5\Segment\Db;

use editor_Models_Db_Segments;
use MittagQI\Translate5\Segment\Processing\State;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table_Abstract;
use Zend_Db_Table_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Db_DeadLockHandlerTrait;

/**
 * DB Access for Segment Processing
 * This is a table holding temporary data (states, objectified markp as json) when performing task operations (import, analysis, etc)
 * The objective is to reduce strain on the LEK_segments & LEK_segments_meta tables by holding only the data of the segments currently processed on the installation(s) and having all state columns indexed
 */
final class Processing extends Zend_Db_Table_Abstract
{
    use ZfExtended_Models_Db_DeadLockHandlerTrait;

    private const INSERT_BATCH = 1000;

    protected $_name = 'LEK_segment_processing';

    protected $_rowClass = ProcessingRow::class;

    public $_primary = 'segmentId';

    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Retrieves all of our state columns
     * @throws Zend_Db_Table_Exception
     */
    public function getStateColumns(): array
    {
        $stateCols = [];
        foreach ($this->info(Zend_Db_Table_Abstract::COLS) as $col) {
            if (str_ends_with($col, State::COLUMN_SUFFIX)) {
                $stateCols[] = $col;
            }
        }

        return $stateCols;
    }

    /**
     * Calculates the progress for a single state
     */
    public function calculateProgress(string $taskGuid, string $serviceId): float
    {
        $db = $this->getAdapter();
        $tableName = $db->quoteIdentifier($this->_name);
        $column = $db->quoteIdentifier(State::createColumnName($serviceId));
        $row = $db->fetchRow('SELECT count(1) as overallSegs, SUM(IF(' . $column . ' > 2, 1, 0)) as processedSegs FROM ' . $tableName . ' WHERE `taskGuid` = ?', $taskGuid);

        $overallSegs = (int) $row['overallSegs'];
        $processedSegs = (int) $row['processedSegs'];

        // fix for ERROR in core: E9999 - Division by zero
        if ($overallSegs === 0) {
            return 1;
        }

        return $processedSegs / $overallSegs;
    }

    /**
     * Sets the state for several segments for the given service
     */
    public function setSegmentsToState(array $segmentIds, string $serviceId, int $processingState)
    {
        if (! empty($segmentIds)) {
            $column = State::createColumnName($serviceId);
            $this->update([
                $column => $processingState,
            ], [
                'segmentId IN (?)' => $segmentIds,
            ]);
        }
    }

    /**
     * Sets the state for all segments of a task for the given service
     */
    public function setTaskToState(string $taskGuid, string $serviceId, int $processingState)
    {
        $column = State::createColumnName($serviceId);
        $this->update([
            $column => $processingState,
        ], [
            'taskGuid = ?' => $taskGuid,
        ]);
    }

    /**
     * Retrieves the segment-id's for the passed task, service and state
     */
    public function getSegmentsForState(string $taskGuid, string $serviceId, int $processingState): array
    {
        $column = State::createColumnName($serviceId);
        $where = $this->select()
            ->from($this->_name, ['segmentId'])
            ->where('taskGuid = ?', $taskGuid)
            ->where($column . ' = ?', $processingState);
        $rows = $this->fetchAll($where)->toArray();

        return array_column($rows, 'segmentId');
    }

    /**
     * Will generate empty entries for the given task to at least store the states in
     */
    public function prepareOperation(string $taskGuid): void
    {
        $db = $this->getAdapter();
        // this clears the table for the operation. HINT: when we have multiple operations at once for the same task (what must not happen!), this will clear other running operations
        $db->query('DELETE FROM ' . $db->quoteIdentifier($this->_name) . ' WHERE taskGuid = ?', $taskGuid);
        // get segment ids
        $segmentsTable = ZfExtended_Factory::get(editor_Models_Db_Segments::class);
        $segmentIds = $segmentsTable->getAllIdsForTask($taskGuid, false);
        // in case the task has no segments, do not try to insert rows
        if (empty($segmentIds)) {
            return;
        }
        // insert in batches to avoid overruns
        $idPairs = [];
        $numSegments = count($segmentIds);
        for ($i = 0; $i < $numSegments; $i++) {
            $idPairs[] = '(' . $segmentIds[$i] . ', \'' . $taskGuid . '\')';
            if ($i > 0 && ($i % self::INSERT_BATCH) === 0) {
                $this->insertPreparedSegments($db, $idPairs);
                $idPairs = [];
            }
        }
        if (count($idPairs) > 0) {
            $this->insertPreparedSegments($db, $idPairs);
        }
    }

    /**
     * Helper, adds the given id-pairs as rows
     */
    private function insertPreparedSegments(Zend_Db_Adapter_Abstract $db, array $idPairs): void
    {
        $db->query(
            'INSERT INTO ' . $db->quoteIdentifier($this->_name)
            . ' (`segmentId`, `taskGuid`) VALUES '
            . implode(',', $idPairs)
        );
    }

    /**
     * @throws Zend_Db_Table_Exception
     */
    public function getOperationResult(string $taskGuid): array
    {
        $stateColumns = $this->getStateColumns();
        $db = $this->getAdapter();

        // retrieve report about number of processed segments per state-name
        $sql = 'SELECT count(1) as overallSegs';
        foreach ($stateColumns as $column) {
            $sql .= ', SUM(IF(' . $db->quoteIdentifier($column) . ' > ' . State::INPROGRESS . ', 1, 0)) as ' . $column . 'Num';
        }
        $row = $db->fetchRow($sql . ' FROM ' . $db->quoteIdentifier($this->_name) . ' WHERE `taskGuid` = ?', $taskGuid);
        $result = [
            'segments' => intval($row['overallSegs']),
        ];
        foreach ($stateColumns as $column) {
            $stateName = substr($column, 0, (-1 * strlen(State::COLUMN_SUFFIX)));
            $result[$stateName] = intval($row[$column . 'Num']);
        }

        return $result;
    }

    /**
     * Ends processing for the given states
     * Updates other columns for all passed states if $updates is given
     * @param int[] $segmentIds
     */
    public function endProcessingForStates(array $segmentIds, array $updates = []): int
    {
        $this->reduceDeadlocks($this);
        $updates['processing'] = 0; // ends processing
        $affectedRows = $this->retryOnDeadlock(function () use ($segmentIds, $updates) {
            return $this->update(
                $updates,
                [
                    'segmentId IN (?)' => $segmentIds,
                    'processing = ?' => 1,
                ]
            );
        });

        return (int) $affectedRows;
    }

    /**
     * Will remove all processing entries for the passed task
     */
    public function finishOperation(string $taskGuid)
    {
        // clean the processingtable
        $this->getAdapter()->query('DELETE FROM ' . $this->getAdapter()->quoteIdentifier($this->_name) . ' WHERE taskGuid = ?', $taskGuid);
    }
}
