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
use Zend_Db_Table_Abstract;
use MittagQI\Translate5\Segment\Processing\State;
use Zend_Db_Table_Exception;
use ZfExtended_Factory;

/**
 * DB Access for Segment Processing
 * This is a table holding temporary data (states, objectified markp as json) when performing task operations (import, analysis, etc)
 * The objective is to reduce strain on the LEK_segments & LEK_segments_meta tables by holding only the data of the segments currently processed on the installation(s) and having all state columns indexed
 */
final class Processing extends Zend_Db_Table_Abstract
{
    protected $_name = 'LEK_segment_processing';
    public $_primary = 'segmentId';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Retrieves all of our state columns
     * @return array
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
     * @param string $taskGuid
     * @param string $serviceId
     * @return float
     */
    public function calculateProgress(string $taskGuid, string $serviceId): float
    {
        $db = $this->getAdapter();
        $tableName = $db->quoteIdentifier($this->_name);
        $column = $db->quoteIdentifier(State::createColumnName($serviceId));
        $row = $db->fetchRow('SELECT count(1) as overallSegs, SUM(IF(' . $column . ' > 2, 1, 0)) as processedSegs FROM ' . $tableName . ' WHERE `taskGuid` = ?', $taskGuid);
        return intval($row['processedSegs']) / intval($row['overallSegs']);
    }

    /**
     * Sets the state for several segments for the given service
     * @param array $segmentIds
     * @param string $serviceId
     * @param int $processingState
     */
    public function setSegmentsToState(array $segmentIds, string $serviceId, int $processingState)
    {
        if(!empty($segmentIds)){
            $column = State::createColumnName($serviceId);
            $this->update([$column => $processingState], ['segmentId IN (?)' => $segmentIds]);
        }
    }

    /**
     * Sets the state for all segments of a task for the given service
     * @param string $taskGuid
     * @param string $serviceId
     * @param int $processingState
     */
    public function setTaskToState(string $taskGuid, string $serviceId, int $processingState)
    {
        $column = State::createColumnName($serviceId);
        $this->update([$column => $processingState], ['taskGuid = ?' => $taskGuid]);
    }

    /**
     * Retrieves the segment-id's for the passed task, service and state
     * @param string $taskGuid
     * @param string $serviceId
     * @param int $processingState
     * @return array
     */
    public function getSegmentsForState(string $taskGuid, string $serviceId, int $processingState): array
    {
        $column = State::createColumnName($serviceId);
        $where = $this->select()
            ->from($this->_name, ['segmentId'])
            ->where('taskGuid = ?', $taskGuid)
            ->where($column.' = ?', $processingState);
        $rows = $this->fetchAll($where)->toArray();
        return array_column($rows, 'segmentId');
    }

    /**
     * Will generate empty entries for the given task to at least store the states in
     * @param string $taskGuid
     */
    public function prepareOperation(string $taskGuid)
    {
        $db = $this->getAdapter();
        // this clears the table for the operation. HINT: when we have multiple operations at once for the same task (what must not happen!), this will clear other running operations
        $db->query('DELETE FROM ' . $db->quoteIdentifier($this->_name) . ' WHERE taskGuid = ?', $taskGuid);
        $rowvals = [];
        // get segment ids
        $segmentsTable = ZfExtended_Factory::get(editor_Models_Db_Segments::class);
        $segmentIds = $segmentsTable->getAllIdsForTask($taskGuid, false);

        foreach ($segmentIds as $id) {
            $rowvals[] = '(' . $id . ', \'' . $taskGuid . '\')';
        }
        $db->query('INSERT INTO ' . $db->quoteIdentifier($this->_name) . ' (`segmentId`, `taskGuid`) VALUES ' . implode(',', $rowvals));
    }

    /**
     * @param string $taskGuid
     * @return array
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
        $result = ['segments' => intval($row['overallSegs'])];
        foreach ($stateColumns as $column) {
            $stateName = substr($column, 0, (-1 * strlen(State::COLUMN_SUFFIX)));
            $result[$stateName] = intval($row[$column . 'Num']);
        }
        return $result;
    }

    /**
     * Will remove all processing entries for the passed task
     * @param string $taskGuid
     */
    public function finishOperation(string $taskGuid)
    {
        // clean the processingtable
        $this->getAdapter()->query('DELETE FROM ' . $this->getAdapter()->quoteIdentifier($this->_name) . ' WHERE taskGuid = ?', $taskGuid);
    }
}
