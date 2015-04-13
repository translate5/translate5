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
        $this->mailer->setContentByTemplate();
    }
    
    /**
     * send the latest created notification to the list of users
     * @param array $userData
     */
    protected function notify(array $userData) {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->init($userData);
        $this->mailer->send($user->getEmail(), $user->getUserName());
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
     * attaches the segmentList as attachment to the internal mailer object
     * @param string $segmentHash
     * @param array $segments
     */
    protected function attachXliffSegmentList($segmentHash, array $segments) {
        if(empty($segments)) {
            return;
        }
        if(empty($this->xmlCache[$segmentHash])) {
            $xmlConverter = ZfExtended_Factory::get('editor_Models_Converter_XmlSegmentList');
            /* @var $xmlConverter editor_Models_Converter_XmlSegmentList */
            $this->xmlCache[$segmentHash] = $xmlConverter->convert($this->task, $segments);
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
}