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
    }

    /**
     * Generates response for ajax-call of /public/modules/editor/js/app/controller/PreloadImages.js
     */
    public function preloadimagesAction() {
        $this->session = new Zend_Session_Namespace();
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $start = (int)$this->_getParam('start')+10;
        $parts = explode('/', $this->_session->runtimeOptions->dir->tagImagesJsonBasePath);
        $path = array(APPLICATION_PATH, '..', 'public');
        $path = array_merge($path, $parts);
        $path = join(DIRECTORY_SEPARATOR, $path);
        $tagImages = Zend_Json::decode(file_get_contents($path.'/'.$this->session->taskGuid.'.json'));
        $goOn = !$start>=count($tagImages);
        $images2preload = array_slice($tagImages, $start, 10);
        echo Zend_Json::encode(array('goOn'=>$goOn,'start'=>$start,'images2preload'=>$images2preload));
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
        
      $this->view->Php2JsVars()->set('restpath', APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/');
      $this->view->Php2JsVars()->set('moduleFolder', $this->view->publicModulePath.'/');
      $this->view->Php2JsVars()->set('appFolder', $this->view->publicModulePath.'/js/app');
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
      $this->setJsSegmentFlags('segments.stateFlags', $rop->segments->stateFlags->toArray());
      $this->view->Php2JsVars()->set('segments.showStatus', (boolean)$rop->segments->showStatus);
      $states = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
      /* @var $states editor_Models_SegmentAutoStates */
      $this->setJsSegmentFlags('segments.autoStateFlags', $states->getLabelMap());
      $this->view->Php2JsVars()->set('segments.roleAutoStateMap', $states->getRoleToStateMap());

      $tagPath = APPLICATION_RUNDIR.'/'.$rop->dir->tagImagesBasePath.'/';
      $this->view->Php2JsVars()->set('segments.shortTagPath', $tagPath);
      $this->view->Php2JsVars()->set('segments.fullTagPath', $tagPath);
      $this->view->Php2JsVars()->set('segments.imagePreloader', APPLICATION_RUNDIR.'/'.$rop->dir->tagImagesJsonBasePath.'/');
      
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
        $php2js->set('app.branding', (string) $ed->branding);
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
      $this->getResponse()->setHeader('Content-Type', 'text/javascript');
      $this->session = new Zend_Session_Namespace();
      
      $this->view->frontendControllers = $this->getFrontendControllers();
      
      $this->view->appViewport = $this->session->runtimeOptions->editor->initialViewPort;
      $this->_helper->layout->disableLayout();
    }
    
    public function wdhehelpAction() {
        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender();
    }
}

