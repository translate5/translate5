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
 * Encapsulates the Default Actions triggered by the Workflow
 */
class editor_Workflow_Actions {
    /**
     *
     * @var editor_Workflow_Abstract 
     */
    protected $workflow;

    /**
     * 
     * @param editor_Workflow_Abstract $workflow
     */
    public function __construct(editor_Workflow_Abstract $workflow) {
        $this->workflow = $workflow;
    }
    /**
     * open all users of the other roles of a task
     * @param string $role
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function openRole(string $role, editor_Models_TaskUserAssoc $tua) {
        $wf = $this->workflow;
        $tua->setStateForRoleAndTask($wf::STATE_OPEN, $role, $tua->getTaskGuid());
    }
    
    /**
     * updates all Auto States of this task
     * currently this method supports only updating to REVIEWED_UNTOUCHED and to initial (which is NOT_TRANSLATED and TRANSLATED)
     * @param string $taskGuid
     * @param string $method method to call in editor_Models_SegmentAutoStates
     */
    public function updateAutoStates(string $taskGuid, string $method) {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        
        $states = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        /* @var $states editor_Models_SegmentAutoStates */
        
        $states->{$method}($taskGuid, $segment);
    }
}