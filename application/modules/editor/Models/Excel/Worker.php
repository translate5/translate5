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
 * Contains the Excel Reimport Worker
 */
class editor_Models_Excel_Worker extends ZfExtended_Worker_Abstract {
    
    /**
     * @var editor_Models_Import_Excel
     */
    protected $reimportExcel;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        // @TODO: what needs to be check here? TL: the filename and currentUserGuid for example!
        return true;
    }
    
    /**
     * prepares the excel import
     * @param editor_Models_Task $task
     * @throws editor_Models_Excel_ExImportException
     * @return string
     */
    public function prepareImportFile(editor_Models_Task $task): string {
        $tempFilename = date('Y-m-d__H_i_s').'__'.rand().'.xslx';
        $uploadTarget = $task->getAbsoluteTaskDataPath().'/excelReimport/';
        
        // create upload target directory /data/importedTasks/<taskGuid>/excelReimport/ (if not exist already)
        if (!is_dir($uploadTarget)) {
            mkdir($uploadTarget, 0755);
        }
        
        // move uploaded excel into upload target
        if (!move_uploaded_file($_FILES['excelreimportUpload']['tmp_name'], $uploadTarget.$tempFilename)) {
            // throw exception 'E1141' => 'Excel Reimport: upload failed.'
            throw new editor_Models_Excel_ExImportException('E1141',['task' => $task]);
        }
        return $tempFilename;
    }
    
    /**
     * enable direct runs by inheriting public
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        //needed since parent is protected by design
        return parent::run();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $this->task = $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        // do nothing if task is not in state "is Excel exported"
        if ($task->getState() != editor_Models_Task::STATE_EXCELEXPORTED) {
            return false;
        }
        
        // detect the filename from the workers parameter
        $params = $this->getModel()->getParameters();
        
        $this->reimportExcel = ZfExtended_Factory::get('editor_Models_Import_Excel', [$task, $params['filename'], $params['currentUserGuid']]);
        /* @var $reimportExcel editor_Models_Import_Excel */
        
        // on error an editor_Models_Excel_ExImportException is thrown
        $this->reimportExcel->run();
        
        // unlock task and set state to 'open'
        $this->reimportExcel->taskUnlock($task);
        return true;
    }
    
    /**
     * Returns the segment errors of the reimport, empty array if none
     * @return array
     */
    public function getSegmentErrors(): array {
        return empty($this->reimportExcel) ? [] : $this->reimportExcel->getSegmentErrors();
    }
    
    /**
     * send an email to the upload with the segment errors
     * @param ZfExtended_Models_User $user
     */
    public function mailSegmentErrors(ZfExtended_Models_User $user) {
        $mailer = ZfExtended_Factory::get('ZfExtended_TemplateBasedMail');
        /* @var $mailer ZfExtended_TemplateBasedMail */
        $mailer->setParameters([
            'segmentErrors' => $this->getSegmentErrors(),
            'task' => $this->task,
        ]);
        
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        $pm->loadByGuid($this->task->getPmGuid());
        
        $mailer->setReplyTo($pm->getEmail(),$pm->getUserName());
        $mailer->setTemplate('workflow/pm/notifyExcelReimportErrors.phtml');
        $mailer->sendToUser($user);
    }
}