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
 * Since Statistics are mostly only important for editable segments, the plugin provides this worker,
 * which deletes all statistics for non editable segments.
 */
class editor_Plugins_SegmentStatistics_CleanUpWorker extends editor_Plugins_SegmentStatistics_Worker {

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
        /* @var $stat editor_Plugins_SegmentStatistics_Models_Statistics */
        $db = $stat->db;
        
        $segDb = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $segDb editor_Models_Db_Segments */
        
        $this->setType();
        
        $select = $segDb->select()
            ->from($segDb, array('id'))
            ->where('taskGuid = ?', $this->taskGuid)
            ->where('editable = 0');

        $table = $db->info($db::NAME);
        $adapter = $db->getAdapter();
        
        $delete = 'DELETE FROM '.$table;
        $delete .= ' WHERE '.$adapter->quoteInto('taskGuid = ?', $this->taskGuid);
        $delete .= ' AND '.$adapter->quoteInto('type = ?', $this->type);
        $delete .= ' AND segmentId IN ('.$select.')'; 
        
        $adapter->query($delete);
        return true;
    }
}