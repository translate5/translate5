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

/**
 * Workflow Manager Class for creating workflows and getting workflow meta data
 * All available Workflows for this installation are listed in application.ini runtimeOptions.workflows
 */
class editor_Workflow_Manager {
    /**
     * a list with all available workflow classes
     * @var array
     */
    protected static $workflowList;
    
    /**
     * Caches the workflow instance to taskGuids
     * @var array
     */
    protected static $workflowTaskCache = [];
    
    /**
     */
    public function __construct() {
        /* @var $workflow editor_Models_Workflow */
        $workflow = ZfExtended_Factory::get('editor_Models_Workflow');
        $workflows = $workflow->loadAll();
        
        foreach($workflows as $workflowData) {
            //if the workflow exists already, it was added by a plug-in with a different class
            // if not, we add it as default workflow configured from database
            if(empty(self::$workflowList[$workflowData['name']])) {
                $this->addWorkflow($workflowData['name']);
            }
        }
    }
    
    /**
     * New Workflow Classes can be added to the internal list
     * @param string $wfId
     * @param string $workflowClass
     */
    public function addWorkflow($name, $workflowClass = 'editor_Workflow_Default') {
        self::$workflowList[$name] = $workflowClass;
    }
    
    /**
     * returns a new workflow instance by given string ID (e.g. default for "Default" Workflow)
     * @param string $wfName
     * @return editor_Workflow_Default
     */
    public function get($wfName) {
        if(empty(self::$workflowList[$wfName])) {
            //Workflow with ID "{workflowId}" not found!
            throw new editor_Workflow_Exception('E1252', ['workflowId' => $wfName]);
        }
        return ZfExtended_Factory::get(self::$workflowList[$wfName],[$wfName]);
    }
    
    /**
     * returns a workflow instance by given string ID, caches the workflow instances internally
     * @see self::get
     * @param string $wfId
     * @return editor_Workflow_Default
     */
    public function getCached($wfName) {
        if(empty($this->instances[$wfName])) {
            $this->instances[$wfName] = $this->get($wfName);
        }
        return $this->instances[$wfName];
    }
    
    /**
     * returns the workflow for the given task
     * @param editor_Models_Task $task
     * @return editor_Workflow_Default
     */
    public function getByTask(editor_Models_Task $task) {
        return $this->get($task->getWorkflow());
    }
    
    /**
     * returns a list of available workflows (workflow names)
     * @return [string]
     */
    public function getWorkflows(): array {
        return array_keys(self::$workflowList);
    }

    public function getWorkflowConstants() {
        //collection of programmatically needed constants in the GUI
        //only constants of the abstract and therefore always available workflow are allowed to be listed here
        //since only a subset of all constants of the abstract is needed, this manual sub selection is OK.
        return [
            'DEFAULT_WORKFLOW' => 'default',
            'STEP_NO_WORKFLOW' => editor_Workflow_Default::STEP_NO_WORKFLOW,
            'ROLE_TRANSLATOR' => editor_Workflow_Default::ROLE_TRANSLATOR,
            'ROLE_REVIEWER' => editor_Workflow_Default::ROLE_REVIEWER,
            'ROLE_TRANSLATORCHECK' => editor_Workflow_Default::ROLE_TRANSLATORCHECK,
        ];
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
        $result = [];
        $labelize = function(array $data, $cls, $labels) {
            $usedLabels = array_intersect_key($labels, $data);
            ksort($usedLabels);
            ksort($data);
            if(count($data) !== count($usedLabels)) {
                 // {className}::$labels has to much / or missing labels!',
                 throw new editor_Workflow_Exception('E1253', ['className' => $cls]);
            }
            return array_combine($data, $usedLabels);
        };
        foreach(self::$workflowList as $name => $cls) {
            $wf = $this->get($name);
            /* @var $wf editor_Workflow_Default */
            $labels = $wf->getLabels();
            $data = new stdClass();
            $data->id = $name;
            $data->label = $wf->getLabel();
            $data->anonymousFieldLabel = false; //FIXME true | false, comes from app.ini not from wf class
            
            $data->roles = $labelize($wf->getRoles(), $cls, $labels);
            
            $data->usableSteps = $labelize($wf->getUsableSteps(), $cls, $labels);
            
            $allStates = $wf->getStates();
            $pendingStates = $wf->getPendingStates();
            //the returned states are the states without the pending ones
            $data->states = $labelize(array_diff($allStates, $pendingStates), $cls, $labels);
            $data->pendingStates = $labelize($pendingStates, $cls, $labels);
            $data->steps = $labelize($wf->getSteps(), $cls, $labels);
            $data->assignableSteps = $labelize($wf->getAssignableSteps(), $cls, $labels);
            $data->steps2roles = $wf->getSteps2Roles();
            $data->stepChain = $wf->getStepChain();
            $data->stepsWithFilter = $wf->getStepsWithFilter();
            $data->initialStates = $wf->getInitialStates();
            $result[$name] = $data;
        }
        return $result;
    }

    /**
     * returns the workflow for the given taskGuid, if no taskGuid given take config.workflow.initialWorkflow as default
     * @param string|editor_Models_Task $taskOrGuid
     * @return editor_Workflow_Default
     */
    public function getActiveByTask(editor_Models_Task $task) {
        $taskGuid = $task->getTaskGuid();
        if(empty(self::$workflowTaskCache[$taskGuid])) {
            return self::$workflowTaskCache[$taskGuid] = $this->get($task->getWorkflow());
        }
        return self::$workflowTaskCache[$taskGuid];
    }
    
    public function getActive(string $taskGuid) {
        if(empty(self::$workflowTaskCache[$taskGuid])) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            return $this->getActiveByTask($task);
        }
        return self::$workflowTaskCache[$taskGuid];
    }
    
    /**
     * creates the initial userpref settings for the given task
     * @param editor_Models_Task $task
     */
    public function initDefaultUserPrefs(editor_Models_Task $task) {
        $config = Zend_Registry::get('config');
        $wfconf = $config->runtimeOptions->workflow;
        $taskGuid  = $task->getTaskGuid();
        
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($taskGuid);
        /* @var $sfm editor_Models_SegmentFieldManager */
        $fields = array_map(function(Zend_Db_Table_Row $row){
            return $row->name;
        },$sfm->getFieldList());
        
        foreach(self::$workflowList as $key => $className) {
            $userPref = ZfExtended_Factory::get('editor_Models_Workflow_Userpref');
            /* @var $userPref editor_Models_Workflow_Userpref */
            $userPref->setWorkflow($key);
            $userPref->setTaskGuid($taskGuid);
            $userPref->setWorkflowStep(null); //default entry
            $userPref->setUserGuid(null);     //default entry
            $userPref->setAnonymousCols($wfconf->$key && $wfconf->$key->anonymousColumns);
            $userPref->setNotEditContent(false);
            $userPref->setFields(join(',', $fields));
            $vis = $wfconf->$key && $wfconf->$key->visibility || 'show';
            $userPref->setVisibility($vis);
            $userPref->save();
        }
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
  roles: {'reviewer' => 'Lector' → current utRoles},
  states: { current utStates }
  steps:{
    reviewing=""Lektorat"",
    translatorCheck=""Zweites Lektorat"",
    pmCheck=""PM Prüfung""
  },
  stepChain:[""reviewing"", ""translatorCheck""]
}, {...}]"			0.5
}*/