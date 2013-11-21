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
 *
 */
class editor_TaskController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_Task';
    
    /**
     * aktueller Datumsstring
     * @var string
     */
    protected $now;
    
    /**
     * logged in user
     * @var Zend_Session_Namespace
     */
    protected $user;

    /**
     * @var editor_Models_Task
     */
    protected $entity;
    
    /**
     * container for upload errors
     * @var array
     */
    protected $uploadErrors = array();
    
    /**
     * Path to uploaded Zip File
     * @var string
     */
    protected $pathToZip;
    
    /**
     * Cached map of userGuids to userNames
     * @var array
     */
    protected $cachedUserInfo = array();
    
    /**
     * loadAll counter buffer
     * @var integer
     */
    protected $totalCount;
    
    /**
     * Specific Task Filter Class to use
     * @var string
     */
    protected $filterClass = 'editor_Models_Filter_TaskSpecific';
    
    /**
     *
     * @var editor_Workflow_Abstract 
     */
    protected $workflow;

    public function init() {
        parent::init();
        $this->now = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $this->user = new Zend_Session_Namespace('user');
        $this->workflow = ZfExtended_Factory::get('editor_Workflow_Default');
    }
    
    /**
     * 
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $unlockedTasks = $this->entity->cleanupLockedJobs();
        if(!empty($unlockedTasks)) {
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */
            foreach($unlockedTasks as $task) {
                $tua->cleanupLocked($task['taskGuid'], $task['lockingUser']);
            }
        }
        
        $this->view->rows = $this->loadAll();
        $this->view->total = $this->totalCount;
    }
    
    /**
     * uses $this->entity->loadAll, but unsets qmSubsegmentFlags for all rows and
     * set qmSubEnabled for all rows
     */
    public function loadAll()
    {
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        $filter = $this->entity->getFilter();
        $assocFilter = $filter->isUserAssocNeeded();
        $isAllowedToLoadAll = $acl->isInAllowedRoles($this->user->data->roles,'loadAllTasks');
        if(!$assocFilter && $isAllowedToLoadAll) {
            $this->totalCount = $this->entity->getTotalCount();
            $rows = $this->entity->loadAll();
        }
        else {
            $filter->setUserAssocNeeded();
            $this->totalCount = $this->entity->getTotalCountByUserAssoc($this->user->data->userGuid);
            $rows = $this->entity->loadListByUserAssoc($this->user->data->userGuid);
        }
        $config = Zend_Registry::get('config');
        
        $taskGuids = array_map(function($item){
            return $item['taskGuid'];
        },$rows);
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos($taskGuids, $userAssocInfos);
        
        foreach ($rows as &$row) {
            //adding QM SubSegment Infos to each Task
            $row['qmSubEnabled'] = false;
            if($config->runtimeOptions->editor->enableQmSubSegments &&
                    !empty($row['qmSubsegmentFlags'])) { 
                $row['qmSubEnabled'] = true;
            }
            unset($row['qmSubsegmentFlags']);
            
            $this->addUserInfos($row, $row['taskGuid'], $userAssocInfos, $allAssocInfos);
        }
        return $rows;
    }
    
    /**
     * Fetch an array with Task User Assoc Data for the currently logged in User.
     * Returns an array with an entry for each task, key is the taskGuid
     * @return array
     */
    protected function getUserAssocInfos($taskGuids, &$userAssocInfos) {
        $userAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userAssoc editor_Models_TaskUserAssoc */
        //$assocs = $userAssoc->loadByUserGuid($this->user->data->userGuid);
        $userGuid = $this->user->data->userGuid;
        $assocs = $userAssoc->loadByTaskGuidList($taskGuids);
        $res = array();
        foreach($assocs as $assoc) {
            if(!isset($res[$assoc['taskGuid']])) {
                $res[$assoc['taskGuid']] = array(); 
            }
            if($userGuid == $assoc['userGuid']) {
                $userAssocInfos[$assoc['taskGuid']] = $assoc;
            }
            $userInfo = $this->getUserinfo($assoc['userGuid']);
            $assoc['userName'] = $userInfo['surName'].', '.$userInfo['firstName'];
            $assoc['login'] = $userInfo['login'];
            $res[$assoc['taskGuid']][] = $assoc;
        }
        $userSorter = function($first, $second){
            if($first['userName'] > $second['userName']) {
                return 1;
            }
            if($first['userName'] < $second['userName']) {
                return -1;
            }
            return 0;
        };
        foreach($res as $taskGuid => $taskUsers) {
            usort($taskUsers, $userSorter);
            $res[$taskGuid] = $taskUsers; 
        }
        return $res;
    }

    /**
     * replaces the userGuid with the username
     * Doing this on client side would be possible, but then it must be ensured that UsersStore is always available and loaded before TaskStore. 
     * @param string $userGuid
     */
    protected function getUserinfo($userGuid) {
        $notfound = array(); //should not be, but can occur after migration of old data!
        if(empty($userGuid)) {
            return $notfound;
        }
        if(isset($this->cachedUserInfo[$userGuid])) {
            return $this->cachedUserInfo[$userGuid];
        }
        if(empty($this->tmpUserDb)) {
            $this->tmpUserDb = ZfExtended_Factory::get('ZfExtended_Models_Db_User');
            /* @var $this->tmpUserDb ZfExtended_Models_Db_User */
        }
        $s = $this->tmpUserDb->select()->where('userGuid = ?', $userGuid);
        $row = $this->tmpUserDb->fetchRow($s);
        if(!$row) {
            return $notfound; 
        }
        $this->cachedUserInfo[$userGuid] = $row->toArray();
        return $row->toArray(); 
    }
    
    /**
     * returns the commonly used username: Firstname Lastname (login)
     * @param array $userinfo
     */
    protected function getUsername(array $userinfo) {
        if(empty($userinfo)) {
            return '- not found -'; //should not be, but can occur after migration of old data!
        }
        return $userinfo['firstName'].' '.$userinfo['surName'].' ('.$userinfo['login'].')';
    }

    /**
     * creates a task and starts import of the uploaded task files 
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        $this->entity->init();
        //$this->decodePutData(); → not needed, data was set directly out of params because of file upload
        $this->data = $this->_getAllParams();
        settype($this->data['wordCount'], 'integer');
        settype($this->data['enableSourceEditing'], 'boolean');
        $this->data['pmGuid'] = $this->user->data->userGuid;
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        $pm->init((array)$this->user->data);
        $this->data['pmName'] = $pm->getUsernameLong();
        $this->setDataInEntity();
        $this->entity->createTaskGuidIfNeeded();
        if($this->validate()) {
            //$this->entity->save(); => is done by the import call!
            $this->processUploadedFile();
            $this->view->success = true;
            $this->view->rows = $this->entity->getDataObject();
        }
        $this->workflow->doImport($this->entity);
    }

    /**
     * imports the uploaded file
     * @throws Exception
     */
    protected function processUploadedFile() {
        /* 
        //auskommentiert, da Serverabsturz bei inetsolutions, Zweck war die Sicherstellugn dass immer nur ein Import zur gleichen Zeit läuft.
        $config = Zend_Registry::get('config');
        $flagFile = $config->resources->cachemanager->zfExtended->backend->options->cache_dir.'/importRunning';
        while(file_exists($flagFile)){
            if(time()-filemtime($flagFile)>3600){
                unlink($flagFile);
            }
            sleep(1);
        }
        file_put_contents($flagFile, $this->getGuid());
        */
        $p = (object) $this->_request->getParams();
        
        $import = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $import editor_Models_Import */
        $import->setEdit100PercentMatches((bool) $this->entity->getEdit100PercentMatch());
        $import->setUserInfos($this->user->data->userGuid, $this->user->data->userName);

        $import->setLanguages(
                        $this->entity->getSourceLang(), 
                        $this->entity->getTargetLang(), 
                        $this->entity->getRelaisLang(), 
                        editor_Models_Languages::LANG_TYPE_ID);
        $import->setTask($this->entity);
        $dp = $this->getDataProvider($this->pathToZip);
        
        try {
            $import->import($dp);
        }
        catch (Exception $e) {
        	$dp->handleImportException($e);
        	throw $e;
        }
        #auskommentiert, da Serverabsturz bei inetsolutions
        //if(file_exists($flagFile))unlink($flagFile);
    }
    
    /**
     * @param string $zipfile
     * @return editor_Models_Import_DataProvider_Abstract
     */
    protected function getDataProvider(string $zipfile) {
        return ZfExtended_Factory::get('editor_Models_Import_DataProvider_Zip', array($zipfile));
    }
    
    /**
     * 
     * currently taskController accepts only 2 changes by REST
     * - set locked: this sets the session_id implicitly and in addition the 
     *   corresponding userGuid, if the passed locked value is set
     *   if locked = 0, task is unlocked
     * - set finished: removes locked implictly, and sets the userGuid of the "finishers" 
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        $this->entity->load($this->_getParam('id'));
        
        $taskguid = $this->entity->getTaskGuid();
        
        $oldTask = clone $this->entity;
        $this->decodePutData();
        if(isset($this->data->enableSourceEditing)){
            $this->data->enableSourceEditing = (boolean)$this->data->enableSourceEditing;
        }
        $this->setDataInEntity();
        $this->entity->validate();
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        if(!$acl->isInAllowedRoles($this->user->data->roles,'loadAllTasks')
                &&
                ($this->isOpenTaskRequest(true)&&
                    !$this->workflow->isTaskOfUser($taskguid, $this->user->data->userGuid, false,true)
                || $this->isOpenTaskRequest(false,true)&&
                    !$this->workflow->isTaskOfUser($taskguid, $this->user->data->userGuid, true,false)
                )
           ){
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        
        //the following methods check internally what to do 
        $this->openAndLock();
        $this->closeAndUnlock();
        
        $this->workflow->doWithTask($oldTask, $this->entity);
        
        if($oldTask->getState() != $this->entity->getState()) {
            editor_Models_LogTask::createWithUserGuid($taskguid, $this->entity->getState(), $this->user->data->userGuid);
        }
        
        $this->updateUserState($this->user->data->userGuid);
        
        $this->entity->save();
        $obj = $this->entity->getDataObject();
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos(array($taskguid), $userAssocInfos);
        
        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj; 
        $this->addUserInfos($row, $taskguid, $userAssocInfos, $allAssocInfos);
            
        $this->view->rows = (object)$row;
        if($this->isOpenTaskRequest()){
            $this->addQmSubToResult();
        }
        else {
            unset($this->view->rows->qmSubsegmentFlags);
        }
    }
    
    /**
     * Adds additional user based infos to the given array
     * @param array $row gets the row to modify as reference
     * @param string $taskguid
     * @param array $userAssocInfos
     * @param array $allAssocInfos
     */
    protected function addUserInfos(array &$row, $taskguid, array $userAssocInfos, array $allAssocInfos) {
        //Add actual User Assoc Infos to each Task
        if(isset($userAssocInfos[$taskguid])) {
            $row['userRole'] = $userAssocInfos[$taskguid]['role'];
            $row['userState'] = $userAssocInfos[$taskguid]['state'];
            $row['userStep'] = $this->workflow->getStepOfRole($row['userRole']);
        }
        
        //Add all User Assoc Infos to each Task
        if(isset($allAssocInfos[$taskguid])) {
            $row['users'] = $allAssocInfos[$taskguid];
        }
        
        $row['lockingUsername'] = $this->getUsername($this->getUserinfo($row['lockingUser']));
    }
    
    /**
     * returns true if PUT Requests opens a task for editing or readonly
     * 
     * - its not allowed to set both parameters to true
     * @param boolean $editOnly if set to true returns true only if its a real editing (not readonly) request
     * @param boolean $viewOnly if set to true returns true only if its a readonly request
     * 
     * FIXME Diese Methode und die noch nicht existierende isCloseTaskRequest in den Workflow packen und in this->closeAndUnlock integrieren.
     *          Dabei auch die fehlenden task stati waiting, end,open mit in isCloseTaskRequest integrieren !
     *           Ebenfalls die STATES nach workflow abstract umziehen, States dokumentieren.
     * @return boolean
     */
    protected function isOpenTaskRequest($editOnly = false,$viewOnly = false) {
        if(empty($this->data->userState)) {
            return false;
        }
        if($editOnly && $viewOnly){
            throw new Zend_Exception('editOnly and viewOnly can not both be true');
        }
        $s = $this->data->userState;
        $workflow = $this->workflow;
        return $editOnly && $s == $workflow::STATE_EDIT 
           || !$editOnly && ($s == $workflow::STATE_EDIT || $s == $workflow::STATE_VIEW)
           || $viewOnly && $s == $workflow::STATE_VIEW;
    }
    
    /**
     * locks the current task if its an editing request
     * stores the task as active task if its an opening or an editing request
     */
    protected function openAndLock() {
        $session = new Zend_Session_Namespace();
        if($this->isOpenTaskRequest(true)){
            if(!$this->entity->lock($this->now)){
                $workflow = $this->workflow;
                $this->data->userState = $workflow::STATE_VIEW;
            }
        }
        if($this->isOpenTaskRequest()){
            $this->entity->registerInSession($this->data->userState);
        }
    }
    
    
    /**
     * unlocks the current task if its an request that closes the task (set state to open, end, finish)
     * removes the task from session
     */
    protected function closeAndUnlock() {
        $workflow = $this->workflow;
        $closingStates = array(
            $workflow::STATE_FINISH,
            $workflow::STATE_OPEN
        );
        $task = $this->entity;
        $hasState = !empty($this->data->userState);
        $isEnding = isset($this->data->state) && $this->data->state == $task::STATE_END;
        if($hasState && $this->data->userState == $workflow::STATE_EDIT && $isEnding) {
            $this->data->userState = $workflow::STATE_OPEN;
        }
        if(!$isEnding && (!$hasState || !in_array($this->data->userState, $closingStates))){
            return;
        }
        if($this->entity->getLockingUser() == $this->user->data->userGuid) {
            if(!$this->entity->unlock()){
                throw new Zend_Exception('task '.$this->entity->getTaskGuid().
                        ' could not be unlocked by user '.$this->user->data->userGuid);
            }
        }
        $this->entity->unregisterInSession();
    }
    
    /**
     * Updates the transferred User Assoc State to the given userGuid (normally the current user)
     * @param string $userGuid
     */
    protected function updateUserState(string $userGuid) {
        if(empty($this->data->userState)) {
            return;
        }
        
        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userTaskAssoc editor_Models_TaskUserAssoc */
        $userTaskAssoc->loadByParams($userGuid,$this->entity->getTaskGuid());

        $oldUserTaskAssoc = clone $userTaskAssoc;
        
        if($this->workflow->isStateChangeable($userTaskAssoc)) {
            $userTaskAssoc->setState($this->data->userState);
            $userTaskAssoc->save();
        }
        $this->workflow->doWithUserAssoc($oldUserTaskAssoc, $userTaskAssoc);
        
        if($oldUserTaskAssoc->getState() != $this->data->userState){
            editor_Models_LogTask::createWithUserGuid($this->entity->getTaskGuid(), $this->data->userState, $this->user->data->userGuid);
        }
    }
    
    /**
     * Adds the Task Specific QM SUb Segment Infos to the request result.
     * Not usable for indexAction, must be called after entity->save and this->view->rows = Data
     */
    protected function addQmSubToResult() {
        $config = Zend_Registry::get('config');
        $qmSubFlags = $this->entity->getQmSubsegmentFlags();
        $this->view->rows->qmSubEnabled = false;
        if($config->runtimeOptions->editor->enableQmSubSegments &&
                !empty($qmSubFlags)) { 
            $this->view->rows->qmSubFlags = $this->entity->getQmSubsegmentIssuesTranslated(false);
            $this->view->rows->qmSubSeverities = $this->entity->getQmSubsegmentSeveritiesTranslated(false);
            $this->view->rows->qmSubEnabled = true;
        }
        unset($this->view->rows->qmSubsegmentFlags);
    }
    
    /**
     * gets and validates the uploaded zip file
     */
    protected function additionalValidations() {
        $upload = new Zend_File_Transfer_Adapter_Http();
        $uploaded = $upload->getFileInfo('importZip');
        $zip = $uploaded['importZip']['tmp_name'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($zip) != 'application/zip') {
            $this->addUploadError('noZipFile', $uploaded['importZip']['name']);
        }
        $this->throwOnUploadError();
        $this->pathToZip = $zip;
    }

    /**
     * Adds an upload error
     * @see throwOnUploadError
     * @param string $errorType
     */
    protected function addUploadError($errorType) {
        $msgs = array(
            'noZipFile' => 'Bitte eine Zip Datei auswählen.',
        );
        if(empty($msgs[$errorType])) {
            $msg = $this->view->translate->_('Unbekannter Fehler beim Dateiupload.');
        }
        else {
            $msg = $this->view->translate->_($msgs[$errorType]);
        }
        $args = func_get_args();
        array_shift($args); //remove type
        array_unshift($args, $msg); //add formatted string as first parameter
        $this->uploadErrors[$errorType] = call_user_func_array('sprintf', $args);
    }

    /**
     * throws upload errors if some occured 
     * @throws ZfExtended_ValidateException
     */
    protected function throwOnUploadError() {
        if(empty($this->uploadErrors)) {
            return;
        }
        $errors = array('importZip' => $this->uploadErrors);
        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        throw $e;
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::getAction()
     * GET with parameter export=1 does an export
     * accepts also parameter diff=0|1
     */
    public function getAction() {
        $res = parent::getAction();
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        if(!$acl->isInAllowedRoles($this->user->data->roles,'loadAllTasks') && 
                !$this->workflow->isTaskOfUser($this->entity->getTaskGuid(), 
                    $this->user->data->userGuid, true)){
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        if($this->_getParam('export', false)) {
            $this->handleExport();
        }
        else {
            return $res;
        }
    }
    
    /**
     * does the export as zip file.
     */
    protected function handleExport() {
        $diff = (boolean)$this->getRequest()->getParam('diff');

        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var $export editor_Models_Export */
        if(!$export->setTaskToExport($this->entity, $diff)){
            //@todo: this should show up in JS-Frontend in a nice way
            echo $this->view->translate->_(
                    'Derzeit läuft bereits ein Export für diesen Task. Bitte versuchen Sie es in einiger Zeit nochmals.');
            return;
        }
        $zipFile = $export->exportToZip();
        if($diff) {
            $suffix = ' - with history.zip';
        }
        else {
            $suffix = '.zip';
        }

        // disable layout and view
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$this->entity->getTasknameForDownload($suffix).'"');
        readfile($zipFile);
        exit;
    }
}
