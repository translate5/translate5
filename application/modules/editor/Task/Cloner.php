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
 * Clones an existing task
 */
class editor_Task_Cloner {

    protected editor_Models_Task $clone;
    protected editor_Models_Task $original;

    /**
     * Clones the given task and returns the cloned instance
     * @param editor_Models_Task $entity
     * @return editor_Models_Task
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function clone(editor_Models_Task $entity): editor_Models_Task
    {
        //store the entities internally
        $this->clone = clone $entity;
        $this->original = $entity;

        //sanitize the data of the task to be cloned
        $data = (array) $this->original->getDataObject();
        unset($data['id']);
        unset($data['taskGuid']);
        unset($data['state']);
        unset($data['workflowStep']);
        unset($data['locked']);
        unset($data['lockingUser']);
        unset($data['userCount']);
        unset($data['created']);

        $this->handleSingleTask($data);

        //finally prepare the cloned entity for import
        $data['state'] = 'import';
        $this->clone->init($data);
        $this->clone->createTaskGuidIfNeeded();
        $this->clone->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        return $this->clone;
    }

    /**
     * if the source task is a single project task (default task), then we have to convert it to a project with projectTasks
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    protected function handleSingleTask(& $data) {
        if((string) $this->original->getTaskType() !== editor_Task_Type_Default::ID || $this->original->getId() !== $this->original->getProjectId()){
            return;
        }
        // 1. we create the project out of the current task
        /** @var editor_Models_Task $project */
        $project = ZfExtended_Factory::get('editor_Models_Task');
        $project->init($data);
        $project->createTaskGuidIfNeeded();
        $project->setState($project::STATE_PROJECT);

        // 2. we set the correct taskTypes
        $taskType = editor_Task_Type::getInstance();
        $taskType->calculateImportTypes(true, $this->original->getTaskType());
        $project->setTaskType($taskType->getImportProjectType());
        $data['taskType'] = $taskType->getImportTaskType();

        // 3. get the projectId and save it also into the project itself
        $projectId = $project->save();
        $project->setProjectId($projectId);
        $project->save();
        $data['projectId'] = $projectId; //set the new projectId to be used for the cloned task

        // 4. update the original task with the new type and projectId
        $this->original->setProjectId($projectId);
        $this->original->setTaskType($data['taskType']);
        $this->original->save();
    }

    public function cloneDependencies() {
        $this->cloneLanguageResources();
        $this->cloneTaskSpecificConfig();
    }

    /**
     * Clone existing language resources from oldTaskGuid for newTaskGuid.
     */
    protected function cloneLanguageResources(){
        /** @var MittagQI\Translate5\LanguageResource\TaskAssociation $job */
        $job = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
        $jobs = $job->loadByTaskGuids([$this->original->getTaskGuid()]);
        if(empty($jobs)){
            return;
        }
        foreach($jobs as $jobData){
            unset($jobData['id']);
            if(!empty($jobData['autoCreatedOnImport'])) {
                //do not clone such TermCollection associations, since they are recreated through the cloned import package
                continue;
            }
            $jobData['taskGuid'] = $this->clone->getTaskGuid();
            $job->init($jobData);
            try {
                $job->save();
            } catch (Zend_Db_Statement_Exception | ZfExtended_Models_Entity_Exceptions_IntegrityConstraint | ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
                // do nothing here
            }
        }
    }

    /**
     * Clone all values and configs from $oldTaskGuid to $newTaskGuid
     */
    protected function cloneTaskSpecificConfig() {
        $taskConfig =ZfExtended_Factory::get('editor_Models_TaskConfig');
        /* @var $taskConfig editor_Models_TaskConfig */
        $taskConfig->cloneTaskConfig($this->original->getTaskGuid(), $this->clone->getTaskGuid());
    }

}
