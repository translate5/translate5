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

class editor_Plugins_LockSegmentsBasedOnConfig_Worker extends editor_Models_Task_AbstractWorker {
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
        if(empty($orWhere)){
            //if empty, nothing should be locked based on meta
            return false;
        }
        $subselect = $md->getAdapter()->quoteInto('SELECT segmentId FROM `'.$metaTable.'` WHERE taskGuid = ?', $this->taskGuid );
        $subselect .= ' and (('.join(') or (', $orWhere).'))';
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $meta editor_Models_Segment */
        $sg = $segment->db;
        $sgTable = $sg->info($sg::NAME);
        
        $query = $sg->getAdapter()->quoteInto('UPDATE `'.$sgTable.'` SET  `editable` = 0, autoStateId = '.
                editor_Models_Segment_AutoStates::BLOCKED.' WHERE taskGuid = ?', $this->taskGuid );
        $query .= ' and id in ('.$subselect.')';
        $sg->getAdapter()->query($query);
        
        $segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $segmentFieldManager editor_Models_SegmentFieldManager */
        $segmentFieldManager->initFields($this->taskGuid);
        $mv = $segmentFieldManager->getView();
        $query = $sg->getAdapter()->quoteInto('UPDATE `'.$mv->getName().'` SET  `editable` = 0, autoStateId = '.
                editor_Models_Segment_AutoStates::BLOCKED.' WHERE taskGuid = ?', $this->taskGuid );
        $query .= ' and id in ('.$subselect.')';

        $sg->getAdapter()->query($query);
        return true;
    }
}
