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

/**#@+ 
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * 
 */
/**
 * Dummy Index Controller
 */ 
class Editor_IndexController extends ZfExtended_Controllers_Action {
    /**
     * FIXME remove session: session is redundant here, since parent class has _session
     * @var Zend_Session_Namespace
     */
    protected $session;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * 
     This is to be able to start a worker as a developer indepently through the browser
     
     public function startworkerAction() {
     
        $this->_helper->viewRenderer->setNoRender();
        $taskGuid = $this->getParam('taskGuid');
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        
        // init worker and queue it
        if (!$worker->init($taskGuid, array('resourcePool' => 'import'))) {
            $this->log('TermTaggerImport-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue();
    }
    */
    public function indexAction() {
        $this->session = new Zend_Session_Namespace();
        $this->_helper->layout->disableLayout();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->view->pathToIMAGES = APPLICATION_RUNDIR.$this->session->runtimeOptions->server->pathToIMAGES;
        $extJs = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
              'ExtJs'
          );
        $this->view->extJsCss = $extJs->getCssPath();
        $this->view->extJsBasepath = $extJs->getHttpPath();
        $this->view->publicModulePath = APPLICATION_RUNDIR.'/modules/'.Zend_Registry::get('module');
        $this->view->locale = $this->session->locale;

        $css = $this->getAdditionalCss();
        foreach($css as $oneCss) {
          $this->view->headLink()->appendStylesheet(APPLICATION_RUNDIR."/".$oneCss);
        }

        $this->setJsVarsInView();
        $this->checkForUpdates();
    }
    
    /**
     * Logs the users userAgent and screen size for usability improvements
     */
    public function logbrowsertypeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        settype($_POST['appVersion'], 'string');
        settype($_POST['userAgent'], 'string');
        settype($_POST['browserName'], 'string');
        settype($_POST['maxWidth'], 'integer');
        settype($_POST['maxHeight'], 'integer');
        settype($_POST['usedWidth'], 'integer');
        settype($_POST['usedHeight'], 'integer');
        $userSession = new Zend_Session_Namespace('user');
        
        $log = ZfExtended_Factory::get('editor_Models_BrowserLog');
        /* @var $log editor_Models_BrowserLog */
        
        $log->setLogin($userSession->data->login);
        $log->setUserGuid($userSession->data->userGuid);
        $log->setAppVersion($_POST['appVersion']);
        $log->setUserAgent($_POST['userAgent']);
        $log->setBrowserName($_POST['browserName']);
        $log->setMaxWidth($_POST['maxWidth']);
        $log->setMaxHeight($_POST['maxHeight']);
        $log->setUsedWidth($_POST['usedWidth']);
        $log->setUsedHeight($_POST['usedHeight']);
        
        $log->save();
    }
    
    protected function checkForUpdates() {
        $downloader = ZfExtended_Factory::get('ZfExtended_Models_Installer_Downloader', array(APPLICATION_PATH.'/..'));
        /* @var $downloader ZfExtended_Models_Installer_Downloader */
        
        $userSession = new Zend_Session_Namespace('user');
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        
        $isAllowed = $acl->isInAllowedRoles($userSession->data->roles,'getUpdateNotification');
        if($isAllowed && !$downloader->applicationIsUptodate()) {
            $msgBoxConf = $this->view->Php2JsVars()->get('messageBox');
            settype($msgBoxConf->initialMessages, 'array');
            $msg = 'Eine neue Version von Translate5 ist verfügbar. Bitte benutzen Sie das Installations und Update Script um die aktuellste Version zu installieren.';
            $msgBoxConf->initialMessages[] = $this->translate->_($msg);
        }
    }

    /**
     * Gibt die zusätzlich konfigurierte CSS Dateien als Array zurück
     * @return array
     */
    protected function getAdditionalCss() {
        if(empty($this->session->runtimeOptions->publicAdditions)){
            return array();
        }
        /* @var $css Zend_Config */
        $css = $this->session->runtimeOptions->publicAdditions->css;
        if(empty($css)) {
            return array();
        }
        if(is_string($css)){
            return array($css);
        }
        return $css->toArray();
    }
    
    protected function setJsVarsInView() {
        $rop = $this->session->runtimeOptions;
        
        $restPath = APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/';
      $this->view->Php2JsVars()->set('restpath', $restPath);
      $this->view->Php2JsVars()->set('moduleFolder', $this->view->publicModulePath.'/');
      $this->view->Php2JsVars()->set('appFolder', $this->view->publicModulePath.'/js/app');
      $this->view->Php2JsVars()->set('pluginFolder', $restPath.'plugins/js');
      $extJs = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'ExtJs'
        );
      $this->view->Php2JsVars()->set('uxFolder', $extJs->getHttpPath().'/examples/ux');
      $this->view->Php2JsVars()->set('pathToHeaderFile', $rop->headerOptions->pathToHeaderFile);
      
      $disabledList = $rop->segments->disabledFields->toArray();
      $this->view->Php2JsVars()->create('segments.column');
      foreach($disabledList as $disabled){
        if(empty($disabled)){
          continue;
        }
        $this->view->Php2JsVars()->set('segments.column.'.$disabled.'.hidden', true);
      }
      
      $this->setJsSegmentFlags('segments.qualityFlags', $rop->segments->qualityFlags->toArray());
      $manualStates = $rop->segments->stateFlags->toArray();
      $manualStates[0] = $this->translate->_('Nicht gesetzt');
      $this->setJsSegmentFlags('segments.stateFlags', $manualStates);
      $this->view->Php2JsVars()->set('segments.showStatus', (boolean)$rop->segments->showStatus);
      $this->view->Php2JsVars()->set('segments.showQM', (boolean)$rop->segments->showQM);
      $states = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
      /* @var $states editor_Models_SegmentAutoStates */
      $this->setJsSegmentFlags('segments.autoStateFlags', $states->getLabelMap());
      $this->view->Php2JsVars()->set('segments.roleAutoStateMap', $states->getRoleToStateMap());

      $tagPath = APPLICATION_RUNDIR.'/'.$rop->dir->tagImagesBasePath.'/';
      $this->view->Php2JsVars()->set('segments.shortTagPath', $tagPath);
      $this->view->Php2JsVars()->set('segments.fullTagPath', $tagPath);
      
      if($rop->editor->enableQmSubSegments) {
          $this->view->Php2JsVars()->set('segments.subSegment.tagPath', $tagPath);
      }
      $this->view->Php2JsVars()->set('enable100pEditWarning', (boolean) $rop->editor->enable100pEditWarning);
      
      $this->view->Php2JsVars()->set('preferences.alikeBehaviour', $rop->alike->defaultBehaviour);
      $this->view->Php2JsVars()->set('loginUrl', APPLICATION_RUNDIR.$rop->loginUrl);
      $this->view->Php2JsVars()->set('messageBox.delayFactor', $rop->messageBox->delayFactor);
      
      $this->view->Php2JsVars()->set('headerOptions.height', (int)$rop->headerOptions->height);
      $this->view->Php2JsVars()->set('languages', $this->getAvailableLanguages());
      $this->view->Php2JsVars()->set('translations', $this->translate->getAvailableTranslations());
      
      //Editor.data.enableSourceEditing → still needed for enabling / disabling the whole feature (Checkbox at Import).
      $this->view->Php2JsVars()->set('enableSourceEditing', (boolean) $rop->import->enableSourceEditing);
      
      $this->view->Php2JsVars()->set('columns.widthFactorHeader', (float)$rop->editor->columns->widthFactorHeader);
      $this->view->Php2JsVars()->set('columns.widthOffsetEditable', (integer)$rop->editor->columns->widthOffsetEditable);
      $this->view->Php2JsVars()->set('columns.widthFactorErgonomic', (float)$rop->editor->columns->widthFactorErgonomic);
      $this->view->Php2JsVars()->set('columns.maxWidth', (integer)$rop->editor->columns->maxWidth);
      
      /*
      Format of initial preset grid filters
      can be used for every grid filter
      initialGridFilters = {
        FILTER_FTYPE: {
          FIELD_DATA_INDEX: INITIAL_CONFIG (used with applyIf)
        }
      }
      example:
      $this->view->Php2JsVars()->set('initialGridFilters.editorGridFilter.workflowStep', {value: 129});
      */
      
      $this->setJsAppData();
    }

    /**
     * Set the several data needed vor authentication / user handling in frontend
     */
    protected function setJsAppData() {
        $userSession = new Zend_Session_Namespace('user');
        $userSession->data->passwd = '********';
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
                
        $workflow = ZfExtended_Factory::get('editor_Workflow_Default');
        /* @var $workflow editor_Workflow_Default */
        
        $ed = $this->session->runtimeOptions->editor;
        
        $php2js = $this->view->Php2JsVars();
        $php2js->set('app.controllers', $this->getFrontendControllers());
        
        if(empty($this->session->taskGuid)) {
            $php2js->set('app.initMethod', 'openAdministration');
        }
        else {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            //FIXME TRANSLATE-55 if a taskguid remains in the session, 
            //the user will be caught in a zend 404 Screen instead of getting the adminpanel.
            $task->loadByTaskGuid($this->session->taskGuid);
            $taskData = $task->getDataObject();
            unset($taskData->qmSubsegmentFlags);
            $php2js->set('task', $taskData);
            $openState = $this->session->taskOpenState ? 
                    $this->session->taskOpenState : 
                    $workflow::STATE_WAITING; //in doubt read only
            $php2js->set('app.initState', $openState);
            $php2js->set('app.initMethod', 'openEditor');
        }
         
        $php2js->set('app.viewport', $ed->editorViewPort);
        $php2js->set('app.branding', (string) $this->translate->_($ed->branding));
        $php2js->set('app.user', $userSession->data);
        
        $allRoles = $acl->getRoles();
        $roles = array();
        foreach($allRoles as $role) {
            //
            if($role == 'noRights' || $role == 'basic') {
                continue;
            }
            $roles[$role] = ucfirst($role);
        }
        $php2js->set('app.roles', $roles);
        
        $wm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wm editor_Workflow_Manager */
        $php2js->set('app.workflows', $wm->getWorkflowData());
        
        $php2js->set('app.userRights', $acl->getFrontendRights($userSession->data->roles));
    }
    
    /**
     * returns a list with used JS frontend controllers
     * @return array
     */
    protected function getFrontendControllers() {
        $userSession = new Zend_Session_Namespace('user');
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        
        $ed = $this->session->runtimeOptions->editor;
        
        $controllers = array('ServerException', 'ViewModes', 'Segments', 
            'Preferences', 'MetaPanel', 'Fileorder', 'Localizer',
            'ChangeAlike', 'Comments');
        
        $pm = Zend_Registry::get('PluginManager');
        $pluginFrontendControllers = $pm->getActiveFrontendControllers();
        if(!empty($pluginFrontendControllers)) {
            $controllers = array_merge($controllers, $pluginFrontendControllers);
        }
        
        if($acl->isInAllowedRoles($userSession->data->roles,'headPanelFrontendController')){
            $controllers[] = 'HeadPanel';
        }
        if($acl->isInAllowedRoles($userSession->data->roles,'userPrefFrontendController')){
            $controllers[] = 'UserPreferences';
        }
        
        if($ed->enableQmSubSegments){
            $controllers[] = 'QmSubSegments';
        }
        if($acl->isInAllowedRoles($userSession->data->roles,'taskOverviewFrontendController')){
            $controllers[] = 'admin.TaskOverview';
            $controllers[] = 'admin.TaskPreferences'; //FIXME add a own role?
        }
        if($acl->isInAllowedRoles($userSession->data->roles,'adminUserFrontendController')){
            $controllers[] = 'admin.TaskUserAssoc';
            $controllers[] = 'admin.User';
        }
        return $controllers;
    }
    
    /**
     * Returns all configured languages in an array for displaying in frontend
     */
    protected function getAvailableLanguages() {
        /* @var $langs editor_Models_Languages */
        $langs = ZfExtended_Factory::get('editor_Models_Languages');
        $langs = $langs->loadAll();
        $result = array();
        foreach ($langs as $lang) {
            $name = $this->translate->_($lang['langName']);
            $result[$name] = array($lang['id'], $name.' ('.$lang['rfc5646'].')');
        }
        ksort($result); //sort by name of language
        if(empty($result)){
            throw new Zend_Exception('No languages defined. Please use /docs/003fill-LEK-languages-after-editor-sql or define them otherwhise.');
        }
        return array_values($result);
    }
    
    protected function setJsSegmentFlags($type, array $qualityFlags) {
      $result = array();
      foreach($qualityFlags as $key => $value){
        if(empty($value)){
          continue;
        }
        $flag = new stdClass();
        $flag->id = $key;
        $flag->label = $this->translate->_($value);
        $result[] = $flag;
      }
      
      $this->view->Php2JsVars()->set($type, $result);
    }
    
    public function applicationstateAction() {
        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender();
        $result = new stdClass();
        $downloader = ZfExtended_Factory::get('ZfExtended_Models_Installer_Downloader', array(APPLICATION_PATH.'/..'));
        /* @var $downloader ZfExtended_Models_Installer_Downloader */
        $result->isUptodate = $downloader->applicationIsUptodate();
        $versionFile = APPLICATION_PATH.'../version';
        if(file_exists($versionFile)) {
            $result->version = file_get_contents($versionFile);
        }
        else {
            $result->version = 'development';
            $result->branch = exec('cd '.APPLICATION_PATH.'; git status -bs | head -1');
        }
        
        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker ZfExtended_Models_Worker */
        $result->worker = $worker->getSummary();
        
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $result->pluginsLoaded = $pm->getActive();
        
        $this->view->applicationstate = $result;
        
    }
    
    public function generatesmalltagsAction() {
      set_time_limit(0);
      $session = new Zend_Session_Namespace();
      $path = array(APPLICATION_PATH, '..', 'public', 
          $session->runtimeOptions->dir->tagImagesBasePath.'/');
      $path = join(DIRECTORY_SEPARATOR, $path);

      /* @var $single ImageTag_Single */
      $single = ZfExtended_Factory::get('editor_ImageTag_Single');
      $single->setSaveBasePath($path);
      
      $singleLocked = ZfExtended_Factory::get('editor_ImageTag_Single');
      /* @var $singleLocked ImageTag_SingleLocked */
      $singleLocked->setSaveBasePath($path);
      
      /* @var $left ImageTag_Left */
      $left = ZfExtended_Factory::get('editor_ImageTag_Left');
      $left->setSaveBasePath($path);
      
      /* @var $right ImageTag_Right */
      $right = ZfExtended_Factory::get('editor_ImageTag_Right');
      $right->setSaveBasePath($path);
      
      for($i = 1; $i <= 100; $i++) {
        $single->create('<'.$i.'/>');
        $singleLocked->create('<locked'.$i.'/>');
        $left->create('<'.$i.'>');
        $right->create('</'.$i.'>');
        
        $single->save($i);
        $singleLocked->save('locked'.$i);
        $left->save($i);
        $right->save($i);
      }
      
      exit;
    }
    
    public function generateqmsubsegmenttagsAction() {
      set_time_limit(0);
      $session = new Zend_Session_Namespace();
      $path = array(APPLICATION_PATH, '..', 'public', 
          $session->runtimeOptions->dir->tagImagesBasePath.'/');
      $path = join(DIRECTORY_SEPARATOR, $path);

      /* @var $left editor_ImageTag_QmSubSegmentLeft */
      $left = ZfExtended_Factory::get('editor_ImageTag_QmSubSegmentLeft');
      $left->setSaveBasePath($path);
      
      /* @var $right editor_ImageTag_QmSubSegmentRight */
      $right = ZfExtended_Factory::get('editor_ImageTag_QmSubSegmentRight');
      $right->setSaveBasePath($path);
      
      for($i = 1; $i < 120; $i++) {
        $left->create('[ '.$i);
        $right->create($i.' ]');
        $left->save('qmsubsegment-'.$i);
        $right->save('qmsubsegment-'.$i);
      }
      
      exit;
    }
    
    public function localizedjsstringsAction() {
      $this->getResponse()->setHeader('Content-Type', 'text/javascript', TRUE);
      $this->session = new Zend_Session_Namespace();
      
      $this->view->frontendControllers = $this->getFrontendControllers();
      
      $this->view->appViewport = $this->session->runtimeOptions->editor->initialViewPort;
      $this->_helper->layout->disableLayout();
    }
    
    public function wdhehelpAction() {
        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender();
    }

    /**
     * To prevent LFI attacks load existing Plugin JS filenames and use them as whitelist
     * Currently this Method is not reusable, its only for JS.
     */
    public function pluginjsAction() {
        $slash = '/';
        // get requested file from router
        $js = explode($slash, $this->getParam(1)); 
        //pluginname is alpha characters only so check this for security reasons
        //ucfirst is needed, since in JS packages start per convention with lowercase, Plugins in PHP with uppercase! 
        $plugin = ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', array_shift($js))); 
        if(empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }
        //get the plugin instance to the key
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $plugin = $pm->get($plugin);
        /* @var $plugin ZfExtended_Plugin_Abstract */
        if(empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }
        //get public files of the plugin to make a whitelist check of the file string from userland
        $allowedFiles = $plugin->getPublicFiles('js', $absolutePath);
        $file = join($slash, $js);
        if(!in_array($file, $allowedFiles)) {
            throw new ZfExtended_NotFoundException();
        }
        //concat the absPath from above with filepath
        $wholePath = $absolutePath.'/'.$file;
        if(!file_exists($wholePath)){
            throw new ZfExtended_NotFoundException();
        }
        //currently this method is fixed to JS:
        header('Content-Type: text/javascript');
        readfile($wholePath);
        exit;
    }
}

