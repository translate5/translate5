<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

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
     * @param string $method method to call in editor_Models_Segment_AutoStates
     */
    public function updateAutoStates(string $taskGuid, string $method) {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        
        $states->{$method}($taskGuid, $segment);
    }
}