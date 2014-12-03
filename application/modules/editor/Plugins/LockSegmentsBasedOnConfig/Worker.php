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
     
    public function work() {
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta 
        $meta->addMeta('noMissingTargetTermOnImport', $meta::META_TYPE_BOOLEAN, true, 'Is set to false only if all terms in source exists also in target column');

        $statDb = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Db_Statistics');
        /* @var $statDb editor_Plugins_SegmentStatistics_Models_Db_Statistics 
        
        $select = $statDb->select()
            ->from($statDb, array(new Zend_Db_Expr ('1 AS noMissingTargetTermOnImport'), 'taskGuid', 'segmentId'))
            ->where('taskGuid = ?', $this->taskGuid)
            ->where('termNotFound = 0')
            ->group('segmentId');
        
        $md = $meta->db;
        $table = $md->info($md::NAME);
        $insert = 'INSERT INTO '.$table.' (`noMissingTargetTermOnImport`, `taskGuid`, `segmentId`) '.$select; 
        $md->getAdapter()->query($insert);
        error_log('hier1');
    }*/
    /**
     * 
     * @param string $taskGuid
     */
    public function work() {
        error_log('hier1');
        $config = Zend_Registry::get('config');
        $metaToLock = $config->runtimeOptions->plugins->LockSegmentsBasedOnConfig->metaToLock;
        
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        
        $orWhere = array();
        foreach($metaToLock as $meta => $val){
            if($val == 1){
                $orWhere[] = $meta.' = 1';
            }
        }
        if(!empty($orWhere)){//nothing should be locked based on meta
            $adapater = Zend_Registry::get('db');
            $query = $adapater->quoteInto('SELECT segmentId FROM `'.$md::NAME.'` WHERE taskGuid = ?', $this->taskGuid );
            $query .= ' and (('.join(') or (', $orWhere).'))';

            $return = $adapater->query($query);

            $rows = $return->fetchAll();

            $segmentUpdateWhere = array();
            foreach ($rows as $key => $row) {
                $segmentUpdateWhere[] = 'id = '.$row->segmentId;
            }

            $query = $adapater->quoteInto('UPDATE `'.$md::NAME.'` SET  `editable` = 0 WHERE taskGuid = ?', $this->taskGuid );
            $query .= ' and (('.join(') or (', $segmentUpdateWhere).'))';
            error_log('hier2');

            $return = $adapater->query($query);
            error_log('hier3');
        }
    }
}