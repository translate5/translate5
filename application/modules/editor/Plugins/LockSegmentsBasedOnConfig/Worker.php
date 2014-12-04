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
class editor_Plugins_LockSegmentsBasedOnConfig_Worker extends ZfExtended_Worker_Abstract{
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return empty($parameters);
    } 
    
    /**
     * 
     * @param string $taskGuid
     */
    public function work() {
        $config = Zend_Registry::get('config');
        $metaToLock = $config->runtimeOptions->plugins->LockSegmentsBasedOnConfig->metaToLock;
        
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $md = $meta->db;
        $metaTable = $md->info($md::NAME);
        
        $orWhere = array();
        foreach($metaToLock as $metadate => $val){
            if($val == 1){
                $orWhere[] = $metadate.' = 1';
            }
        }
        if(!empty($orWhere)){//if empty, nothing should be locked based on meta
            $query = $md->getAdapter()->quoteInto('SELECT segmentId FROM `'.$metaTable.'` WHERE taskGuid = ?', $this->taskGuid );
            $query .= ' and (('.join(') or (', $orWhere).'))';
            $return = $md->getAdapter()->query($query);
            $rows = $return->fetchAll();
            
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $meta editor_Models_Segment */
            $sg = $segment->db;
            $sgTable = $sg->info($sg::NAME);
            

            $segmentUpdateWhere = array();
            foreach ($rows as $key => $row) {
                $segmentUpdateWhere[] = 'id = '.$row["segmentId"];
            }
            $query = $sg->getAdapter()->quoteInto('UPDATE `'.$sgTable.'` SET  `editable` = 0, autoStateId = '.
                    editor_Models_SegmentAutoStates::BLOCKED.' WHERE taskGuid = ?', $this->taskGuid );
            $joinSegmentUpdateWhere = ' and (('.join(') or (', $segmentUpdateWhere).'))';
            $query .= $joinSegmentUpdateWhere;
            $sg->getAdapter()->query($query);
            
            $segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
            /* @var $segmentFieldManager editor_Models_SegmentFieldManager */
            $segmentFieldManager->initFields($this->taskGuid);
            $mv = $segmentFieldManager->getView();
            $query = $sg->getAdapter()->quoteInto('UPDATE `'.$mv->getName().'` SET  `editable` = 0, autoStateId = '.
                    editor_Models_SegmentAutoStates::BLOCKED.' WHERE taskGuid = ?', $this->taskGuid );
            $query .= $joinSegmentUpdateWhere;

            $sg->getAdapter()->query($query);
        }
    }
}
