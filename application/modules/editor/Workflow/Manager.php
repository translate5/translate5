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
 * Workflow Manager Class for creating workflows and getting workflow meta data
 * All available Workflows for this installation are listed in application.ini runtimeOptions.workflows
 */
class editor_Workflow_Manager {
    /**
     * a list with all available workflow classes
     * @var array
     */
    protected $workflowList;
    
    /**
     */
    public function __construct() {
        $config = Zend_Registry::get('config');
        foreach($config->runtimeOptions->workflows as $wf) {
            $this->workflowList[constant($wf.'::WORKFLOW_ID')] = $wf;
        }
    }
    
    /**
     * @param string $className
     * @return string
     */
    public function getIdToClass($className) {
        $flipped = array_flip($this->workflowList);
        if(empty($flipped[$className])) {
            throw new ZfExtended_Exception('Workflow to class "'.$className.'" not found!');
        }
        return $flipped[$className];
    }
    
    /**
     * returns an workflow instance by given string ID (e.g. default for "Default" Workflow)
     * @param string $wfId
     * @return editor_Workflow_Abstract
     */
    public function get($wfId) {
        if(empty($this->workflowList[$wfId])) {
            throw new ZfExtended_Exception('Workflow with ID "'.$wfId.'" not found!');
        }
        return ZfExtended_Factory::get($this->workflowList[$wfId]);
    }
    
    /**
     * returns the workflow for the given task
     * @param editor_Models_Task $task
     * @return editor_Workflow_Abstract
     */
    public function getByTask(editor_Models_Task $task) {
        return $this->get($task->getWorkflow());
    }
    
    /**
     * returns an hard-coded assoc array with the class names (e.g. 'default' => editor_Workflow_Default)
     * @return array
     */
    public function getWorkflows() {
        return $this->workflowList;
    }

    /**
     * returns all workflow metadata (roles, steps, etc) as array of objects
     * Warning: in backend states are ment to be all states including the pending states
     *          in frontend states are ment to be the states WITHOUT the pending states.
     *          Since this method ist the bridge between frontend and backend, 
     *          the states returned in the states field here are without the pending states!
     * @return array
     */
    public function getWorkflowData() {
        $result = array();
        $labelize = function(array $data, $cls) use (&$labels) {
            $usedLabels = array_intersect_key($labels, $data);
            ksort($usedLabels);
            ksort($data);
            if(count($data) !== count($usedLabels)) {
                throw new ZfExtended_Exception($cls.'::$labels has to much / or missing labels!');
            }
            return array_combine($data, $usedLabels);
        };
        foreach($this->workflowList as $id => $cls) {
            $wf = ZfExtended_Factory::get($cls);
            /* @var $wf editor_Workflow_Abstract */
            $labels = $wf->getLabels();
            $data = new stdClass();
            $data->id = $id;
            $data->label = $labels['WORKFLOW_ID'];
            $data->anonymousFieldLabel = false; //FIXME true | false, comes from app.ini not from wf class
            
            $data->roles = $labelize($wf->getRoles(), $cls);
            $allStates = $wf->getStates();
            $pendingStates = $wf->getPendingStates();
            //the returned states are the states without the pending ones
            $data->states = $labelize(array_diff($allStates, $pendingStates), $cls);
            $data->pendingStates = $labelize($pendingStates, $cls);
            $data->steps = $labelize($wf->getSteps(), $cls);
            $data->stepChain = $wf->getStepChain();
            $result[$id] = $data;
        }
        return $result;
    }
    
    /**
     * returns the workflow for the current "active" task, if nothing loaded take config.import.taskWorkflow as default 
     */
    public function getActive() {
        if(empty($session->taskWorkflow)) {
            $config = Zend_Registry::get('config');
            return $this->get($this->getIdToClass($config->runtimeOptions->import->taskWorkflow));
        }
        return $this->get($session->taskWorkflow);
    }
}
    /*
//FIXME → rework all places currently the workflow is instanced manually!"			1.5

editor/Workflow/Manager.php	getWorkflowData	"- returns a dynamically PHP array / object structure as described below.
- the label and step names should be translated
- for getting steps and chain see the current used flags:
→ Editor.data.[wfSteps | wfStepChain]

resulting structure in PHP
[{
  name: 'defaultWorkflow', 
  label: 'Default Workflow', 
  anonymousFieldLabel: true | false, comes from app.ini not from wf class
  roles: {'lector' => 'Lector' → current utRoles},
  states: { current utStates }
  steps:{
    lectoring=""Lektorat"",
    translatorCheck=""Übersetzer Prüfung"",
    pmCheck=""PM Prüfung""
  },
  stepChain:[""lectoring"", ""translatorCheck""]
}, {...}]"			0.5				
}*/