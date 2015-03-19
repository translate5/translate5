<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        $this->writeToDisk();
        return true;
    }
}