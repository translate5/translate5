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

class editor_Plugins_NoMissingTargetTerminology_Worker extends editor_Models_Task_AbstractWorker {

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return empty($parameters);
    } 
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $meta->addMeta('noMissingTargetTermOnImport', $meta::META_TYPE_BOOLEAN, false, 'Is set to false if a term in source does not exist in target column');

        $statDb = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Db_Statistics');
        /* @var $statDb editor_Plugins_SegmentStatistics_Models_Db_Statistics */
        
        $select = $statDb->select()
            ->from($statDb, array(new Zend_Db_Expr ('1 AS noMissingTargetTermOnImport'), 'taskGuid', 'segmentId'))
            ->where('taskGuid = ?', $this->taskGuid)
            ->where("fieldType = 'source'")
            ->where('termNotFound = 0')
            ->group('segmentId');

        $md = $meta->db;
        $table = $md->info($md::NAME);
        $insert = 'INSERT INTO '.$table.' (`noMissingTargetTermOnImport`, `taskGuid`, `segmentId`) '.
                $select->assemble().
                'ON DUPLICATE KEY UPDATE
                `noMissingTargetTermOnImport` = VALUES(`noMissingTargetTermOnImport`)
                , `taskGuid` = VALUES(`taskGuid`)
                , `segmentId` = VALUES(`segmentId`)'; 
        
        $md->getAdapter()->query($insert);
        //@todo: check task data not up2date after new insert of task when assigning user
        return true;
    }
}