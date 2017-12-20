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
 * Encapsulates the Default Notifications triggered by the Workflow
 * Basicly the Notifications are E-Mail based. But this class can be overwritten
 * to redirect the generated mailer texts to over notification channels
 */
class editor_Workflow_Notification extends editor_Workflow_Actions_Abstract {
    /**
     * @var ZfExtended_Mail
     */
    protected $mailer;
    
    /**
     * @var array
     */
    protected $xmlCache = array();
    
    /**
     * reusable $tua instance, instanced if needed, must be set explictly by the called notify method
     * @var editor_Models_TaskUserAssoc
     */
    protected $tua;
    
    /**
     * generates and returns the template path.
     * @param string $role the affected workflow role string
     * @param string $template the template name
     */
    protected function getMailTemplate(string $role, string $template) {
        return 'workflow/'.$role.'/'.$template.'.phtml';
    }
    
    /**
     * returns a list with PM Users (currently only one)
     * @return [array] array with Pm User Data Arrays
     */
    protected function getTaskPmUsers(){
        $task = $this->config->task;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($task->getPmGuid());
        return array((array)$user->getDataObject());
    }
    
    /**
     * perhaps this method should be moved to another location (into the workflow?)
     */
    protected function getStepSegments(string $step) {
        $task = $this->config->task;
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        return $segment->loadByWorkflowStep($task->getTaskGuid(), $step);
    }
    
    /**
     * creates the Notification and stores it internally
     * @param string $role
     * @param string $template
     * @param array $parameters
     */
    protected function createNotification(string $role, string $template, array $parameters) {
        $this->mailer = ZfExtended_Factory::get('ZfExtended_Mail');
        $this->mailer->setParameters($parameters);
        $this->mailer->setTemplate($this->getMailTemplate($role, $template));
    }
    
    /**
     * send the latest created notification to the list of users
     * @param array $userData
     */
    protected function notify(array $userData) {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->init($userData);
        $this->mailer->sendToUser($user);
        return;
    }
    
    /**
     * Adds the users of the given cc/bcc role config to the email - if receiverRole is configured in config
     * @param stdClass $triggerConfig the config object given in action matrix
     * @param string $receiverRole the original receiver role of the notification to be sended
     */
    protected function addCopyReceivers(stdClass $triggerConfig, $receiverRole) {
        $task = $this->config->task;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        $addReceivers = function($receiverRoleMap, $bcc = false) use ($receiverRole, $task, $user) {
            $users = [];
            foreach($receiverRoleMap as $recRole => $roles) {
                if($recRole == '*' || $recRole == $receiverRole) {
                    foreach($roles as $role) {
                        $users = $this->tua->getUsersOfRoleOfTask($role, $task->getTaskGuid());
                    }
                }
            }
            foreach($users as $userData) {
                $user->init($userData);
                if($bcc) {
                    $this->mailer->addBcc($user->getEmail());
                }
                else {
                    $this->mailer->addCc($user->getEmail(), $user->getUserName());
                }
            }
        };
        
        $addReceivers($triggerConfig->cc);
        $addReceivers($triggerConfig->bcc, true);
    }
    
    /**
     * Initiales the internal trigger configuration through the given parameters and returns it
     * currently the following configuration parameters exist:
     * pmBcc boolean, true if the pm of the task should also receive the notification
     * rolesBcc array, list of workflow roles which also should receive the notification
     * @param $config
     * @return stdClass
     */
    protected function initTriggerConfig(array $config) {
        $defaultConfig = new stdClass();
        $defaultConfig->cc = [];
        $defaultConfig->bcc = [];
        if(empty($config)) {
            return $defaultConfig;
        }
        $config = reset($config);
        foreach($config as $key => $v) {
            $defaultConfig->{$key} = $v;
        }
        return $defaultConfig;
    }
    
    /**
     * Feel free to define additional Notifications
     */
    /**
     * Workflow specific Notification after all users of a role have finished a task
     * @param string $triggeringRole
     * @param boolean $isCron
     */
    public function notifyAllFinishOfARole() {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $workflow = $this->config->workflow;
        $isCron = $workflow->isCalledByCron();
        $triggeringRole = $this->config->newTua->getRole();
        $this->tua = clone $this->config->newTua; //we just reuse the already used entity
        $currentStep = $workflow->getStepOfRole($triggeringRole);
        if($currentStep === false){
            error_log("No workflow step to Role ".$triggeringRole." found! This is actually a workflow config error!");
        }
        $segments = $this->getStepSegments($currentStep);
        
        //START TEST HERE
        //$this->alexTestXliff2($task,$segments,$currentStep);
        //END TEST HERE
        
        $segmentHash = md5(print_r($segments,1)); //hash to identify the given segments (for internal caching)
        
        $nextRole = $workflow->getRoleOfStep((string)$workflow->getNextStep($currentStep));
        
        $users = $this->tua->getUsersOfRoleOfTask($nextRole,$task->getTaskGuid());
        $previousUsers = $this->tua->getUsersOfRoleOfTask($triggeringRole,$task->getTaskGuid());
        $params = array(
            'triggeringRole' => $triggeringRole,
            'nextRole' => $nextRole,
            'segmentsHash' => $segmentHash,
            'segments' => $segments,
            'isCron' => $isCron,
            'users' => $users,
            'previousUsers' => $previousUsers,
            'task' => $task,
            'workflow' => $workflow
        );
        //send to the PM
        $pms = $this->getTaskPmUsers();
        foreach($pms as $pm) {
            $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params); //@todo PM currently not defined as WORKFLOW_ROLE, so hardcoded here
            $this->attachXliffSegmentList($segmentHash, $segments);
            $this->addCopyReceivers($triggerConfig, ACL_ROLE_PM);
            $this->notify($pm);
        }
        
        if(is_null($nextRole)){
            return;
        }
        
        //send to each user of the targetRole
        foreach($users as $user) {
            $params['user'] = $user;
            $this->createNotification($nextRole, __FUNCTION__, $params);
            $this->attachXliffSegmentList($segmentHash, $segments);
            $this->addCopyReceivers($triggerConfig, $nextRole);
            $this->notify($user);
        }
    }
    
    
    public function alexTestXliff2(editor_Models_Task $task,array $segments,$workflowStep){
        $xliffConf = [
                editor_Models_Converter_SegmentsToXliff2::CONFIG_ADD_TERMINOLOGY=>true,
                editor_Models_Converter_SegmentsToXliff2::CONFIG_INCLUDE_DIFF=>false,
                editor_Models_Converter_SegmentsToXliff2::CONFIG_ADD_QM=>true,
        ];
        $xliffConverter = ZfExtended_Factory::get('editor_Models_Converter_SegmentsToXliff2', [$xliffConf]);
        /* @var $xliffConverter editor_Models_Converter_SegmentsToXliff2 */
        
        $xliffConverter->workflowStep=$workflowStep;
        $xliff=$xliffConverter->convert($task, $segments);
        error_log($xliff);
    }
    
    /**
     * Sends a notification to users which are attached newly to a task with status open
     * The User to be notified is gathered from the current active TaskUserAssociation
     */
    public function notifyNewTaskAssigned() {
        $tua = $this->config->newTua;
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($tua->getUserGuid());
        
        $params = [
            'task' => $this->config->task,
        ];
        
        $this->createNotification($tua->getRole(), __FUNCTION__, $params);
        $this->notify((array) $user->getDataObject());
    }
    
    /**
     * Notifies all associated users about the task association
     * Main difference to notifyNewTaskAssigned to a single user:
     *  This notification contains a list of all assigned users.
     */
    public function notifyAllAssociatedUsers() {
        $task = $this->config->task;
        $this->tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        $this->tua->setTaskGuid($task->getTaskGuid());
        $tuas = $this->tua->loadAllUsers(['state','role']);
        $roles = array_column($tuas, 'role');
        array_multisort($roles, SORT_ASC, SORT_STRING, $tuas);
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        
        $params = [
            'task' => $this->config->task,
            'associatedUsers' => $tuas,
        ];
        
        //we assume the PM user for all roles, since it is always the same template
        $this->createNotification(ACL_ROLE_PM, 'notifyNewTaskAssigned', $params);
        
        foreach($tuas as $tua) {
            $user->loadByGuid($tua['userGuid']);
            $this->notify((array) $user->getDataObject());
        }
    }
    
    /**
     * Notifies the tasks PM over the new task, but only if PM != the user who has uploaded the task
     */
    public function notifyNewTaskForPm() {
        $task = $this->config->task;
        $pmGuid = $task->getPmGuid();
        $importConf = $this->config->importConfig;
        
        //if the user who imports the task is the same as the PM, we don't send the mail
        // also this mail is not possible at all, if no import config is given
        if(empty($importConf) || $importConf->userGuid == $pmGuid) {
            return;
        }
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($pmGuid);
        
        $params = [
            'task' => $task,
            'user' => (array) $user->getDataObject(),
            'sourceLanguage' => $importConf->sourceLang->getLangName(),
            'targetLanguage' => $importConf->targetLang->getLangName(),
            'relaisLanguage' => (empty($importConf->relaisLang) ? '' : $importConf->relaisLang->getLangName())
        ];
        
        $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params);
        $this->notify((array) $user->getDataObject());
    }
    
    /**
     * attaches the segmentList as attachment to the internal mailer object
     * @param string $segmentHash
     * @param array $segments
     */
    protected function attachXliffSegmentList($segmentHash, array $segments) {
        $config = Zend_Registry::get('config');
        $xlfAttachment = (boolean) $config->runtimeOptions->notification->enableSegmentXlfAttachment;
        $xlfFile =       (boolean) $config->runtimeOptions->editor->notification->saveXmlToFile;
        
        if(empty($segments) || (!$xlfAttachment && !$xlfFile)) {
            return;
        }
        if(empty($this->xmlCache[$segmentHash])) {
            $xliffConf = [
                editor_Models_Converter_SegmentsToXliff::CONFIG_INCLUDE_DIFF => (boolean) $config->runtimeOptions->editor->notification->includeDiff,
                editor_Models_Converter_SegmentsToXliff::CONFIG_PLAIN_INTERNAL_TAGS => true,
                editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_ALTERNATIVES => true,
                editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_TERMINOLOGY => true,
            ];
            $xliffConverter = ZfExtended_Factory::get('editor_Models_Converter_SegmentsToXliff', [$xliffConf]);
            /* @var $xliffConverter editor_Models_Converter_SegmentsToXliff */
            $this->xmlCache[$segmentHash] = $xliff = $xliffConverter->convert($this->config->task, $segments);
            
            if($xlfFile) {
                $this->saveXmlToFile($xliff);
            }
        }
        
        if(!$xlfAttachment) {
            return;
        }
        $attachment = array(
            'body' => $this->xmlCache[$segmentHash],
            'mimeType' => Zend_Mime::TYPE_OCTETSTREAM,
            'disposition' => Zend_Mime::DISPOSITION_ATTACHMENT,
            'encoding' => Zend_Mime::ENCODING_BASE64,
            'filename' => 'changes.xliff',
        );
        $this->mailer->setAttachment(array($attachment));
    }
    
    protected function saveXmlToFile($xml) {
        $path = $this->config->task->getAbsoluteTaskDataPath();
        if(!is_dir($path) || !is_writeable($path)) {
            error_log('cant write changes.xliff file to path: '.$path);
            return;
        }
        $suffix = '.xliff';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //windows can not deal with the : in the filename
            $filename = 'changes-'.date('Y-m-d\TH-i-s');
        } else {
            //for linux we leave it for compatibility reasons
            $filename = 'changes-'.date('Y-m-d\TH:i:s');
        }
        $i = 0;
        $outFile = $path.DIRECTORY_SEPARATOR.$filename.$suffix;
        while(file_exists($outFile)) {
            $outFile = $path.DIRECTORY_SEPARATOR.$filename.'-'.($i++).$suffix;
        }
        if(file_put_contents($outFile, $xml) == 0) {
            error_log('Error on writing XML File: '.$outFile);
        }
    }
}