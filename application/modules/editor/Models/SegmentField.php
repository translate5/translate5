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
 * Entity Model for segment fields
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getType() getType()
 * @method void setType() setType(string $type)
 * @method string getLabel() getLabel()
 * @method void setLabel() setLabel(string $label)
 * @method string getRankable() getRankable()
 * @method void setRankable() setRankable(boolean $rankable)
 * @method string getEditable() getEditable()
 * @method void setEditable() setEditable(boolean $editable)
 */
class editor_Models_SegmentField extends ZfExtended_Models_Entity_Abstract {
    //consts also defined in GUI Model Editor.model.segment.Field 
    const TYPE_SOURCE = 'source';
    const TYPE_TARGET = 'target';
    const TYPE_RELAIS = 'relais';
    
    protected $dbInstanceClass = 'editor_Models_Db_SegmentField';

    /**
     * loads all segment fields for the given taskGuid as rowset
     * @param string $taskGuid
     * @return Zend_Db_Table_Rowset
     */
    public function loadByTaskGuidAsRowset($taskGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->order('id ASC');
        return $this->db->fetchAll($s);
    }
    
    /**
     * loads all segment fields for the given taskGuid
     * @param string $taskGuid
     * @return array
     */
    public function loadByTaskGuid($taskGuid) {
        return $this->loadByTaskGuidAsRowset($taskGuid)->toArray();
    }
    
    /**
     * loads the fields by the userprefs defined for the given taskGuid, userGuid and workflowStep
     * @param string $taskGuid
     * @param string $workflow
     * @param string $userGuid
     * @param string $taskStep
     */
    public function loadByUserPref($taskGuid, $workflow, $userGuid, $taskStep) {
        $allFields = $this->loadByTaskGuid($taskGuid);
        $userPrefs = ZfExtended_Factory::get('editor_Models_Workflow_Userpref');
        /* @var $userPrefs editor_Models_Workflow_Userpref */
        $pref = $userPrefs->loadByTaskUserAndStep($taskGuid, $workflow, $userGuid, $taskStep);
        error_log(print_r($pref,1));
        $fields = explode(',', $pref->fields);
        $result = array();
        $anon = 'A';
        foreach($allFields as $field) {
            if(! in_array($field['name'], $fields)) {
                continue;
            }
            if($pref->anonymousCols && $field['type'] == editor_Models_SegmentField::TYPE_TARGET) {
                $field['label'] = 'Spalte '.($anon++);
            }
            //FIXME how to do visibility??? Wie war das, bezieht sich die Visibility auf die nicht vorhandenen fields? Oder nur wie beschrieben auf die nicht editierbaren Felder?
            //Gleiches auf die Anon Geschichte, hier sollten doch nur die target spalten betroffen sein?
            $result[] = $field;
        }
        return $result;
        //FIXME TaskController Logic für diesen Aufruf beim Export der QM Statistics bzw. eigentlich bei jedem bisherigen loadByTaskGuid adaptieren!
    }
    
    /**
     * Segment Fields are internally processed as ZendRowObjects, so we need a way to get it.
     * @return Zend_Db_Table_Row_Abstract
     */
    public function getRowObject() {
        return $this->row;
    }
} 