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
class editor_Workflow_Notification {
    /**
     * @var ZfExtended_Mail
     */
    protected $mailer;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Workflow_Abstract
     */
    protected $workflow;
    
    /**
     * @var array
     */
    protected $xmlCache = array();
    
    public function __construct(editor_Models_Task $task, editor_Workflow_Abstract $workflow) {
        $this->task = $task;
        $this->workflow = $workflow;
    }

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
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($this->task->getPmGuid());
        return array((array)$user->getDataObject());
    }
    
    /**
     * perhaps this method should be moved to another location (into the workflow?)
     */
    protected function getStepSegments(string $step) {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        return $segment->loadByWorkflowStep($this->task->getTaskGuid(), $step);
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
     * Feel free to define additional Notifications
     */
    /**
     * Workflow specific Notification after all users of a role have finished a task
     * @param string $triggeringRole
     * @param boolean $isCron
     */
    public function notifyAllFinishOfARole($triggeringRole, $isCron = false) {
        $currentStep = $this->workflow->getStepOfRole($triggeringRole);
        if($currentStep === false){
            error_log("No workflow step to Role ".$triggeringRole." found! This is actually a workflow config error!");
        }
        $segments = $this->getStepSegments($currentStep);
        $segmentHash = md5(print_r($segments,1)); //hash to identify the given segments (for internal caching)
        
        $nextRole = $this->workflow->getRoleOfStep((string)$this->workflow->getNextStep($currentStep));
        
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        $users = $tua->getUsersOfRoleOfTask($nextRole,$this->task->getTaskGuid());
        $params = array(
            'triggeringRole' => $triggeringRole,
            'nextRole' => $nextRole,
            'segmentsHash' => $segmentHash,
            'segments' => $segments,
            'isCron' => $isCron,
            'users' => $users,
            'task' => $this->task,
            'workflow' => $this->workflow
        );
        
        //send to the PM
        $pms = $this->getTaskPmUsers();
        foreach($pms as $pm) {
            $params['user'] = $pm;
            $this->createNotification('pm', __FUNCTION__, $params); //@todo PM currently not defined as WORKFLOW_ROLE, so hardcoded here
            $this->attachXliffSegmentList($segmentHash, $segments);
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
            $this->notify($user);
        }
    }
    
    /**
     * Sends a notification to users which are attached newly to a task with status open
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function notifyNewTaskAssigned(editor_Models_TaskUserAssoc $tua) {
        $wf = $this->workflow;
        $wfId = $wf::WORKFLOW_ID;
        $config = Zend_Registry::get('config');
        $wfConfig = $config->runtimeOptions->workflow;
        if(!$wfConfig->{$wfId} || !$wfConfig->{$wfId}->notification->notifyNewTaskAssigned) {
            return;
        }
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($tua->getUserGuid());
        
        
        $params = [
            'user' => (array) $user->getDataObject(),
            'task' => $this->task,
        ];
        
        if($tua->getState() == $wf::STATE_OPEN) {
            $this->createNotification($tua->getRole(), __FUNCTION__, $params);
            $this->notify((array) $user->getDataObject());
        }
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
            $this->xmlCache[$segmentHash] = $xliff = $xliffConverter->convert($this->task, $segments);
            
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
        $path = $this->task->getAbsoluteTaskDataPath();
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