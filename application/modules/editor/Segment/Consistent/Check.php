<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * 
 * Checks the consistency of translations: Segments with an identical target but different sources or with identical sources but different targets
 * This Check can only be done for all segments of a task at once
 *
 */
class editor_Segment_Consistent_Check {
    
    /**
     * @var string
     */
    const SOURCE = 'source';

    /**
     * @var string
     */
    const TARGET = 'target';

    /**
     * @var array
     */
    private $states = [];

    /**
     * Materialized view name
     *
     * @var null
     */
    public $mvName = null;

    /**
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task) {

        // Get arrays of comma-separated ids of segments having inconsistent sources/targets
        $byCategory = $this->getInconsistentSegmentNrsInTask($task);

        // Collect states for each segmentId
        foreach ($byCategory as $category => $byTarget) {
            foreach ($byTarget as $target => $segmentNrInTaskListA) {
                foreach ($segmentNrInTaskListA as $list) {
                    foreach (explode(',', $list) as $segmentNrInTask) {
                        $this->states[$segmentNrInTask][$category] = $category;
                    }
                }
            }
        }
    }

    /**
     * Retrieves the evaluated states
     * @return string[]
     */
    public function getStates(){
        return $this->states;
    }

    /**
     * 
     * @return boolean
     */
    public function hasStates() {
        return count($this->states) > 0;
    }

    /**
     * Get `segmentNrInTask`-values of segments having inconsistent sources or targets, with respect to task's 'enableSourceEditing' option
     *
     * @param editor_Models_Task $task
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getInconsistentSegmentNrsInTask(editor_Models_Task $task) {

        // Get materialized view
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        $mv->setTaskGuid($task->getTaskGuid());

        // Set materialized view name
        $this->mvName = $mv->getName();

        // Db adapter shortcut
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Get target fields
        $targetA = $db->query('
            SELECT `name`
            FROM `LEK_segment_field` 
            WHERE `taskGuid` = ? AND `name` LIKE "target%"
            ORDER BY CAST(REPLACE(`name`, "target", "") AS UNSIGNED)
        ', $task->getTaskGuid())->fetchAll(PDO::FETCH_COLUMN);

        // For calculations of how big value of group_concat_max_len should be we assume the following:
        // 1.100k is the real-life maximum qty of segments in a task
        // 2.10% is the maximum fraction of segments that can have same target for different sources (or upside down)
        // 3.Docs:
        //   - Maximum Value (64-bit platforms)	18446744073709551615
        //   - Maximum Value (32-bit platforms)	4294967295
        //
        // 1.If we rely on `id`, which is int(11), we need
        //   100 000 * 10 000 000 000 / 10 = 100 000 000 000 000 = 100TB-length
        // 2.If we rely on `segmentNrInTask`, we need
        //   100 000 * 100 000 / 10 = 1 000 000 000 = 1GB-length

        // Set group_concat_max_len to be ~ 4GB
        $db->query('SET @@session.group_concat_max_len = 4294967295');

        // Foreach target field
        foreach ($targetA as $targetI) {

            // Col names
            $col['target'] = $targetI . 'EditToSort';
            $col['source'] = $task->getEnableSourceEditing() ? 'sourceEditToSort' : 'sourceToSort';

            // Get ids of segments having inconsistent targets
            $result['target'][$targetI] = $db->query('
                SELECT GROUP_CONCAT(`segmentNrInTask`) AS `ids`
                FROM `' . $this->mvName . '` 
                WHERE `' . $col['target'] . '` != "" AND `' . $col['source'] . '` != ""
                GROUP BY `' . $col['source'] . '`
                HAVING COUNT(DISTINCT `' . $col['target'] . '`) > 1
            ')->fetchAll(PDO::FETCH_COLUMN);

            // Get ids of segments having inconsistent sources
            $result['source'][$targetI] = $db->query('
                SELECT GROUP_CONCAT(`segmentNrInTask`) AS `ids`
                FROM `' . $this->mvName . '` 
                WHERE `' . $col['source'] . '` != "" AND `' . $col['target'] . '` != "" 
                GROUP BY `' . $col['target'] . '`
                HAVING COUNT(DISTINCT `' . $col['source'] . '`) > 1        
            ')->fetchAll(PDO::FETCH_COLUMN);
        }

        // Return
        return $result;
    }
}
