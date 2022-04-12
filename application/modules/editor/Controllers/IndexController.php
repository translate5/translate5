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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;

/**
 * Dummy Index Controller
 */
class Editor_IndexController extends ZfExtended_Controllers_Action
{
    use TaskContextTrait;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;

    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * Main Definition of core frontend controllers, each controller is required (for js compiling) but only the required ones are activated.
     * @var array
     */
    protected $frontendEndControllers = [
        'ServerException'               => true,
        'ViewModes'                     => true,
        'Segments'                      => true,
        'Preferences'                   => true,
        'MetaPanel'                     => true,
        'Editor'                        => true,
        'Fileorder'                     => true,
        'ChangeAlike'                   => true,
        'Comments'                      => true,
        'CommentNavigation'             => true,
        'SearchReplace'                 => true,
        'SnapshotHistory'               => true,
        'Termportal'                    => true, //FIXME should be moved into the termportal plugin
        'JsLogger'                      => true,
        'editor.CustomPanel'            => true,
        'admin.TaskOverview'            => ['taskOverviewFrontendController'], //controlled by ACL, enabling frontend rights given here
        'admin.TaskPreferences'         => ['taskOverviewFrontendController'], //controlled by ACL, enabling frontend rights given here
        'admin.TaskUserAssoc'           => ['taskUserAssocFrontendController'], //controlled by ACL, enabling frontend rights given here
        'admin.Customer'                => ['customerAdministration'], //controlled by ACL, enabling frontend rights given here
        'LanguageResourcesTaskassoc'    => ['languageResourcesTaskassoc'], //controlled by ACL, enabling frontend rights given here
        'LanguageResources'             => ['languageResourcesMatchQuery', 'languageResourcesSearchQuery'], //controlled by ACL, enabling frontend rights given here
        'TmOverview'                    => ['languageResourcesOverview'], //controlled by ACL, enabling frontend rights given here
        'Localizer'                     => true,
        'Quality'                       => true,
        'QualityMqm'                    => true //the check if this controller is active is task specific (runtimeOptions.autoQA.enableMqmTags, flag is task specific)
    ];

    public function init()
    {
        parent::init();
        $this->config = Zend_Registry::get('config');
    }

    /**
     *
     * This is to be able to start a worker as a developer indepently through the browser
     *
     * public function startworkerAction() {
     *
     * $this->_helper->viewRenderer->setNoRender();
     * $taskGuid = $this->getParam('taskGuid');
     * $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
     *
     * // init worker and queue it
     * if (!$worker->init($taskGuid, array('resourcePool' => 'import', 'processingMode' => editor_Segment_Processing::IMPORT))) {
     * $this->log('TermTaggerImport-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
     * return false;
     * }
     * $worker->queue();
     * }
     */
    public function indexAction()
    {
        $this->_helper->layout->disableLayout();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->view->pathToIMAGES = APPLICATION_RUNDIR . $this->config->runtimeOptions->server->pathToIMAGES;

        $userConfig = ZfExtended_Factory::get('editor_Models_Config');
        /* @var $userConfig editor_Models_Config */
        $userConfig = $userConfig->mergeUserValues(editor_User::instance()->getGuid());
        $userTheme = $userConfig['runtimeOptions.extJs.theme']['value'];
        $defaultTheme = $this->config->runtimeOptions->extJs->defaultTheme;
        $userTheme = $userTheme == 'default' ? $defaultTheme : $userTheme;

        $this->view->userTheme = $userTheme;

        $extJs = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'ExtJs'
        );
        /* @var $extJs ZfExtended_Controller_Helper_ExtJs */

        // set extjs theme from user specific config
        $extJs->setUserTheme($userTheme);

        $extJs->init();

        $this->view->extJsCss = $extJs->getCssPath();
        $this->view->extJsBasepath = $extJs->getHttpPath();
        $this->view->extJsVersion = $extJs->getVersion();

        $this->view->buildType = ZfExtended_Utils::VERSION_DEVELOPMENT;

        $this->view->publicModulePath = APPLICATION_RUNDIR . '/modules/' . Zend_Registry::get('module');
        $this->view->locale = $this->_session->locale;

        $css = $this->getAdditionalCss();
        foreach ($css as $oneCss) {
            $this->view->headLink()->appendStylesheet(APPLICATION_RUNDIR . "/" . $oneCss);
        }

        $this->view->appVersion = ZfExtended_Utils::getAppVersion();
        $this->setJsVarsInView();
        $this->setThemeVarsInView($userConfig['runtimeOptions.extJs.theme']['defaults']);
        $this->checkForUpdates($this->view->appVersion);
    }

    /**
     * Logs the users userAgent and screen size for usability improvements
     */
    public function logbrowsertypeAction()
    {
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

        $log->setDatetime(NOW_ISO);
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

    protected function checkForUpdates(string $currentVersion)
    {
        if ($currentVersion == ZfExtended_Utils::VERSION_DEVELOPMENT) {
            return;
        }
        $downloader = ZfExtended_Factory::get('ZfExtended_Models_Installer_Downloader', array(APPLICATION_PATH . '/..'));
        /* @var $downloader ZfExtended_Models_Installer_Downloader */

        $userSession = new Zend_Session_Namespace('user');
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */

        if (!$acl->isInAllowedRoles($userSession->data->roles, 'getUpdateNotification')) {
            return;
        }
        $onlineVersion = $downloader->getAvailableVersion();

        if (!empty($onlineVersion) && version_compare($onlineVersion, $currentVersion)) {
            $msgBoxConf = $this->view->Php2JsVars()->get('messageBox');
            settype($msgBoxConf->initialMessages, 'array');
            $msg = 'Translate5 ist in der Version %1$s verfügbar, verwendet wird aktuell Version %2$s. <br/>Bitte benutzen Sie das Installations und Update Script um die aktuellste Version zu installieren.';
            $msgBoxConf->initialMessages[] = sprintf($this->translate->_($msg), $onlineVersion, $currentVersion);
        }
    }

    /**
     * Gibt die zusätzlich konfigurierte CSS Dateien als Array zurück
     * @return array
     */
    protected function getAdditionalCss()
    {
        $config = Zend_Registry::get('config');
        if (empty($config->runtimeOptions->publicAdditions)) {
            return array();
        }
        /* @var $css Zend_Config */
        $css = $config->runtimeOptions->publicAdditions->css;
        if (empty($css)) {
            return array();
        }
        if (is_string($css)) {
            return array($css);
        }
        return $css->toArray();
    }

    protected function setJsVarsInView()
    {
        $rop = $this->config->runtimeOptions;

        $this->view->enableJsLogger = $rop->debug && $rop->debug->enableJsLogger;
        // Video-recording: If allowed in general, then it can be set by the user after every login.
        $this->view->Php2JsVars()->set('enableJsLoggerVideoConfig', $rop->debug && $rop->debug->enableJsLoggerVideo);

        //for initial loading we have to set the restpath to empty string to trigger relative paths in the proxy setups
        $this->view->Php2JsVars()->set('restpath', '');
        $this->view->Php2JsVars()->set('basePath', APPLICATION_RUNDIR);
        $this->view->Php2JsVars()->set('moduleFolder', $this->view->publicModulePath . '/');
        $this->view->Php2JsVars()->set('appFolder', $this->view->publicModulePath . '/js/app');
        $this->view->Php2JsVars()->set('pluginFolder', APPLICATION_RUNDIR . '/' . Zend_Registry::get('module') . '/plugins/js');
        $extJs = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'ExtJs'
        );
        $extJs->init();
        $this->view->Php2JsVars()->set('pathToHeaderFile', $rop->headerOptions->pathToHeaderFile);

        $disabledList = $rop->segments->disabledFields->toArray();
        $this->view->Php2JsVars()->create('segments.column');
        foreach ($disabledList as $disabled) {
            if (empty($disabled)) {
                continue;
            }
            $this->view->Php2JsVars()->set('segments.column.' . $disabled . '.hidden', true);
        }

        $this->setJsSegmentFlags('segments.qualityFlags', $rop->segments->qualityFlags->toArray());
        $manualStates = $rop->segments->stateFlags->toArray();
        $manualStates[0] = $this->translate->_('Nicht gesetzt');
        $this->setJsSegmentFlags('segments.stateFlags', $manualStates);
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        $this->setJsSegmentFlags('segments.autoStateFlags', $states->getLabelMap());
        $this->view->Php2JsVars()->set('segments.roleAutoStateMap', $states->getRoleToStateMap());

        $tagPath = APPLICATION_RUNDIR . '/' . $rop->dir->tagImagesBasePath . '/';
        $this->view->Php2JsVars()->set('segments.shortTagPath', $tagPath);
        $this->view->Php2JsVars()->set('segments.fullTagPath', $tagPath);

        //matchrate type to icon map
        $typesWihtIcons = array();
        foreach (editor_Models_Segment_MatchRateType::TYPES_WITH_ICONS as $type) {
            $typesWihtIcons[$type] = $this->view->publicModulePath . '/images/matchratetypes/' . $type . '.png';
        }

        $this->view->Php2JsVars()->set('segments.matchratetypes', $typesWihtIcons); //needed to give plugins the abilty to add own icons as matchrate types

        $this->view->Php2JsVars()->set('segments.subSegment.tagPath', $tagPath);

        $this->view->Php2JsVars()->set('loginUrl', APPLICATION_RUNDIR . $rop->loginUrl);
        $this->view->Php2JsVars()->set('logoutOnWindowClose', $rop->logoutOnWindowClose);

        $this->view->Php2JsVars()->set('errorCodesUrl', $rop->errorCodesUrl);

        $this->view->Php2JsVars()->set('messageBox.delayFactor', $rop->messageBox->delayFactor);

        $this->view->Php2JsVars()->set('headerOptions.height', (int)$rop->headerOptions->height);
        $this->view->Php2JsVars()->set('languages', $this->getAvailableLanguages());

        $translatsion = $this->translate->getAvailableTranslations();
        //add custom translations to the frontend locale label
        $this->view->Php2JsVars()->set('translations', $translatsion);

        //Editor.data.enableSourceEditing → still needed for enabling / disabling the whole feature (Checkbox at Import).
        $this->view->Php2JsVars()->set('enableSourceEditing', (bool)$rop->import->enableSourceEditing);

        $supportedFiles = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
        /* @var $supportedFiles editor_Models_Import_SupportedFileTypes */
        $this->view->Php2JsVars()->set('import.validExtensions', $supportedFiles->getSupportedExtensions());
        $this->view->Php2JsVars()->set('import.nativeParserExtensions', $supportedFiles->getNativeParserExtensions());

        $this->view->Php2JsVars()->set('columns.widthFactorHeader', (float)$rop->editor->columns->widthFactorHeader);
        $this->view->Php2JsVars()->set('columns.widthOffsetEditable', (int)$rop->editor->columns->widthOffsetEditable);
        $this->view->Php2JsVars()->set('columns.widthFactorErgonomic', (float)$rop->editor->columns->widthFactorErgonomic);
        $this->view->Php2JsVars()->set('columns.maxWidth', (int)$rop->editor->columns->maxWidth);

        $this->view->Php2JsVars()->set('browserAdvice', $rop->browserAdvice);
        if ($rop->showSupportedBrowsersMsg) {
            $this->view->Php2JsVars()->set('supportedBrowsers', $rop->supportedBrowsers->toArray());
        }

        //create mailto link in the task list grid pm name column
        $this->view->Php2JsVars()->set('frontend.tasklist.pmMailTo', (boolean)$rop->frontend->tasklist->pmMailTo);

        $this->view->Php2JsVars()->set('frontend.importTask.edit100PercentMatch', (bool)$rop->frontend->importTask->edit100PercentMatch);

        $this->view->Php2JsVars()->set('frontend.importTask.pivotDropdownVisible', (bool)$rop->frontend->importTask->pivotDropdownVisible);

        $this->view->Php2JsVars()->set('frontend.changeUserThemeVisible', (bool)$rop->frontend->changeUserThemeVisible);

        //is the openid data visible for the default customer
        $this->view->Php2JsVars()->set('customers.openid.showOpenIdDefaultCustomerData', (boolean)$rop->customers->openid->showOpenIdDefaultCustomerData);

        //boolean config if the logout button in the segments editor header is visible or not
        $this->view->Php2JsVars()->set('editor.toolbar.hideCloseButton', (boolean)$rop->editor->toolbar->hideCloseButton);
        //boolean config if the leave task button button in the segments editor header is visible or not
        $this->view->Php2JsVars()->set('editor.toolbar.hideLeaveTaskButton', (boolean)$rop->editor->toolbar->hideLeaveTaskButton);

        $this->view->Php2JsVars()->set('tasks.simultaneousEditingKey', editor_Models_Task::INTERNAL_LOCK . editor_Models_Task::USAGE_MODE_SIMULTANEOUS);
        $this->setLanguageResourceJsVars();

        $this->view->Php2JsVars()->set('editor.editorBrandingSource', $rop->editor->editorBrandingSource);

        $this->view->Php2JsVars()->set('editor.htmleditorCss', [
            APPLICATION_RUNDIR . '/modules/' . Zend_Registry::get('module') . '/css/htmleditor.css'
        ]);

        $helpWindowConfig = [];
        if (isset($rop->frontend->helpWindow)) {
            $helpWindowConfig = $rop->frontend->helpWindow->toArray() ?? [];
        }
        //helpWindow config values for each section (loaderUrl,documentationUrl)
        $this->view->Php2JsVars()->set('frontend.helpWindow', $helpWindowConfig);

        //show references files popup
        $this->view->Php2JsVars()->set('frontend.showReferenceFilesPopup', $rop->editor->showReferenceFilesPopup);

        $db = Zend_Db_Table::getDefaultAdapter();
        //set db version as frontend param
        $this->view->Php2JsVars()->set('dbVersion', $db->getServerVersion());

        $config = ZfExtended_Factory::get('editor_Models_Config');
        /* @var $config editor_Models_Config */
        $this->view->Php2JsVars()->set('frontend.config.configLabelMap', $config->getLabelMap());

        $tmFileUploadSizeText = $this->translate->_("Ihre Datei ist größer als das zulässige Maximum von {upload_max_filesize} MB. Um größere Dateien hochladen zu können, wenden Sie sich bitte an den translate5-Support.");
        $uploadMaxFilesize = preg_replace('/\D/', '', ini_get('upload_max_filesize'));

        $tmFileUploadSizeText = str_replace('{upload_max_filesize}',$uploadMaxFilesize,$tmFileUploadSizeText);


        //Info: custom vtype text must be translated here and set as frontend var. There is no way of doing this with localizedjsstrings
        $this->view->Php2JsVars()->set('frontend.override.VTypes.tmFileUploadSizeText', $tmFileUploadSizeText);

        // set the max allowed upload filesize into frontend variable. This is used for upload file size validation in tm import
        $this->view->Php2JsVars()->set('frontend.php.upload_max_filesize',$uploadMaxFilesize);
        
        // show Consortium Logos on application load for xyz seconds [default 3]
        $this->view->Php2JsVars()->set('startup.showConsortiumLogos', $rop->startup->showConsortiumLogos);

        // set special characters list into a front-end view variable. This should be removed after this config is moved to lvl 16
        $this->view->Php2JsVars()->set('editor.segments.editorSpecialCharacters', $rop->editor->segments?->editorSpecialCharacters ?? '');

        $this->setJsAppData();
    }

    /***
     * Add translated theme names to a frontend variable
     * @param string $themes
     * @throws Zend_Exception
     */
    protected function setThemeVarsInView(string $themes){
        $themes = explode(',',$themes);
        $translated = [];
        foreach ($themes as $item) {
            $translated[$item] = $this->translate->_($item);
        }
        $this->view->Php2JsVars()->set('frontend.config.themesName', $translated);
    }
    /***
     * Set language resources frontend vars
     */
    protected function setLanguageResourceJsVars()
    {

        $rop = $this->config->runtimeOptions;

        $this->view->Php2JsVars()->set('LanguageResources.preloadedSegments', $rop->LanguageResources?->preloadedTranslationSegments);
        $this->view->Php2JsVars()->set('LanguageResources.matchrateTypeChangedState', editor_Models_LanguageResources_LanguageResource::MATCH_RATE_TYPE_EDITED);

        //find all service names and set it to frontend var
        $services = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $services editor_Services_Manager */
        $allservices = $services->getAll();
        $servicenames = [];
        foreach ($allservices as $s) {
            $sm = ZfExtended_Factory::get($s . '_Service');
            $servicenames[] = $sm->getName();
        }
        $this->view->Php2JsVars()->set('LanguageResources.serviceNames', $servicenames);
    }

    /**
     * Set the several data needed vor authentication / user handling in frontend
     */
    protected function setJsAppData()
    {
        $userSession = new Zend_Session_Namespace('user');
        $userSession->data->passwd = '********';
        $userSession->data->openIdSubject = '';
        $userRoles = $userSession->data->roles;

        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */

        $ed = $this->config->runtimeOptions->editor;

        $php2js = $this->view->Php2JsVars();

        //the list of frontend controllers to be required for JS compiling (values should be static, so not influenced by ACLs or Plugins)
        $php2js->set('app.controllers.require', array_map(function ($item) {
            return 'Editor.controller.' . $item;
        }, array_keys($this->frontendEndControllers)));
        // the list of active controllers: is dynamic, contains only the controllers to be launched
        $php2js->set('app.controllers.active', $this->getActiveFrontendControllers());

        $this->loadCurrentTask($php2js, $userSession->data->userGuid);

        $php2js->set('app.viewport', $ed->editorViewPort);
        $php2js->set('app.startViewMode', $ed->startViewMode);
        $php2js->set('app.branding', (string)$this->translate->_($ed->branding));
        $php2js->set('app.company', $this->config->runtimeOptions->companyName);
        $php2js->set('app.name', $this->config->runtimeOptions->appName);
        $userData = (array) $userSession->data;

        // Trim TermPortal-roles if TermPortal plugin is disabled
        if (!in_array('editor_Plugins_TermPortal_Init', Zend_Registry::get('PluginManager')->getActive()))
            foreach($userData['roles'] as $idx => $role)
                if (preg_match('~^term~', $role))
                    unset($userData['roles'][$idx]);

        $userData['roles'] = array_values($userData['roles']);

        $php2js->set('app.user', $userData);
        $php2js->set('app.serverId', ZfExtended_Utils::installationHash('MessageBus'));
        $php2js->set('app.sessionKey', session_name());

        $allRoles = $acl->getAllRoles();
        $roles = array();
        foreach ($allRoles as $role) {
            if ($role == 'noRights' || $role == 'basic') {
                continue;
            }
            //set the setable, if the user is able to set/modify this role
            $roles[$role] = [
                'label' => $this->translate->_(ucfirst($role)),
                'setable' => $acl->isInAllowedRoles($userRoles, "setaclrole", $role)
            ];
        }
        $php2js->set('app.roles', $roles);

        $wm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wm editor_Workflow_Manager */
        $php2js->set('app.workflows', $wm->getWorkflowData());
        $php2js->set('app.workflow.CONST', $wm->getWorkflowConstants());

        $php2js->set('app.userRights', $acl->getFrontendRights($userRoles));

        $php2js->set('app.version', $this->view->appVersion);

        $filter = ZfExtended_Factory::get('ZfExtended_Models_Filter_ExtJs6');
        /* @var $filter ZfExtended_Models_Filter_ExtJs6 */
        $php2js->set('app.filters.translatedOperators', $filter->getTranslatedOperators());

        $config = ZfExtended_Factory::get('editor_Models_Config');
        /* @var $config editor_Models_Config */

        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->load($userSession->data->id);

        //set frontend array from the config data
        //the array is used as initial user config store data
        $php2js->set('app.configData', $config->loadAllMerged($user, 'runtimeOptions.frontend.defaultState.%'));
    }

    /**
     * @param ZfExtended_View_Helper_Php2JsVars $php2js
     * @param string $userGuid
     * @throws Exception
     */
    protected function loadCurrentTask(ZfExtended_View_Helper_Php2JsVars $php2js, string $userGuid) {
        if (!$this->isTaskProvided()) {
            $php2js->set('app.initMethod', 'openAdministration');
            return;
        }

        try {
            $this->initCurrentTask();
            $task = $this->getCurrentTask();
            $taskData = $task->getDataObject();
            unset($taskData->qmSubsegmentFlags);

            // try to load the job of the current user and task, the one with a usedState,
            $job = editor_Models_Loaders_Taskuserassoc::loadFirstInUse($userGuid, $task);
            $php2js->set('app.initState', $job->getUsedState());
        }
        catch(NoAccessException) {
            $task = $this->getCurrentTask(); //on no access exception current task is though set
            //the task may not be opened yet for editing, so just provide the id and use openEditor with edit mode to try to open it for editing via the UI
            $taskData = new stdClass();
            $taskData->id = $task->getId();
            $php2js->set('app.initState', editor_Workflow_Default::STATE_EDIT);
        }

        $php2js->set('task', $taskData);
        $php2js->set('app.initMethod', 'openEditor');
    }

    /**
     * returns a list with used JS frontend controllers
     * @return array
     * @throws Zend_Acl_Exception
     * @throws Zend_Exception
     */
    protected function getActiveFrontendControllers()
    {
        $userSession = new Zend_Session_Namespace('user');

        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */

        //ensure Localizer beeing the last one, so we remove it and add it later to be at the arrays end
        unset($this->frontendEndControllers['Localizer']);

        //store only the enabled controller names
        $activeControllers = [];
        foreach($this->frontendEndControllers as $controller => $enabled) {
            if(is_array($enabled)) {
                foreach($enabled as $neededRightForController) {
                    if($acl->isInAllowedRoles($userSession->data->roles, 'frontend', $neededRightForController)) {
                        $enabled = true;
                        break; //at least only one right is needed out of the list
                    }
                }
            }
            if($enabled === true) {
                $activeControllers[] = $controller;
            }
        }

        //add the active controllers from plugins
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $pluginFrontendControllers = $pm->getActiveFrontendControllers();
        if (!empty($pluginFrontendControllers)) {
            $activeControllers = array_merge($activeControllers, $pluginFrontendControllers);
        }

        //Localizer must be the last in the list!
        $activeControllers[] = 'Localizer';
        return $activeControllers;
    }

    /**
     * Returns all configured languages in an array for displaying in frontend
     */
    protected function getAvailableLanguages()
    {
        $model = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $model editor_Models_Languages */
        return $model->loadAllForDisplay();
    }

    protected function setJsSegmentFlags($type, array $qualityFlags)
    {
        $result = array();
        foreach ($qualityFlags as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $flag = new stdClass();
            $flag->id = $key;
            $flag->label = $this->translate->_($value);
            $result[] = $flag;
        }

        $this->view->Php2JsVars()->set($type, $result);
    }

    public function applicationstateAction()
    {
        $this->_helper->layout->disableLayout();
        $userSession = new Zend_Session_Namespace('user');
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */

        $config = Zend_Registry::get('config');
        $isCronIP = $config->runtimeOptions->cronIP === $_SERVER['REMOTE_ADDR'];
        $hasAppStateACL = $acl->isInAllowedRoles($userSession->data->roles, 'backend', 'applicationstate');
        //since application state contains sensible information we show that only to the cron TP, or with more details to the API users
        if ($isCronIP || $hasAppStateACL) {
            $this->view->applicationstate = ZfExtended_Debug::applicationState($hasAppStateACL);
        }
    }

    public function generateqmsubsegmenttagsAction()
    {
        set_time_limit(0);
        $path = array(APPLICATION_PATH, '..', 'public',
            $this->config->runtimeOptions->dir->tagImagesBasePath . '/');
        $path = join(DIRECTORY_SEPARATOR, $path);

        /* @var $left editor_ImageTag_QmSubSegmentLeft */
        $left = ZfExtended_Factory::get('editor_ImageTag_QmSubSegmentLeft');
        $left->setSaveBasePath($path);

        /* @var $right editor_ImageTag_QmSubSegmentRight */
        $right = ZfExtended_Factory::get('editor_ImageTag_QmSubSegmentRight');
        $right->setSaveBasePath($path);

        for ($i = 1; $i < 120; $i++) {
            $left->create('[ ' . $i);
            $right->create($i . ' ]');
            $left->save('qmsubsegment-' . $i);
            $right->save('qmsubsegment-' . $i);
        }

        exit;
    }

    public function localizedjsstringsAction()
    {
        $this->getResponse()->setHeader('Content-Type', 'text/javascript', TRUE);
        $this->_helper->layout->disableLayout();
    }

    public function wdhehelpAction()
    {
        $this->_helper->layout->disableLayout();
        //$this->_helper->viewRenderer->setNoRender();
    }

    /**
     * To prevent LFI attacks load existing Plugin JS filenames and use them as whitelist
     * Currently this Method is not reusable, its only for JS.
     */
    public function pluginpublicAction()
    {
        $types = array(
            'js' => 'text/javascript',
            'css' => 'text/css',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'woff' => 'application/woff',
            'woff2' => 'application/woff2',
            'ttf' => 'application/ttf',
            'eot' => 'application/eot',
            'mp3' => 'audio/mp3',
            'mp4' => 'video/mp4',
            'html' => 'text/html'
        );
        $slash = '/';
        // get requested file from router
        $requestedType = $this->getParam(1);
        $requestedFile = $this->getParam(2);
        $requestedFileParts = explode($slash, $requestedFile);
        $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
        
        //pluginname is alpha characters only so check this for security reasons
        //ucfirst is needed, since in JS packages start per convention with lowercase, Plugins in PHP with uppercase!
        $plugin = ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', array_shift($requestedFileParts)));

        // DEBUG
        // error_log("INDEXCONTROLLER: pluginpublicAction: plugin: ".$plugin." / requestedType: ".$requestedType." / requestedFile: ".$requestedFile." / extension: ".$extension);

        if (empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }

        //get the plugin instance to the key
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $plugin = $pm->get($plugin);
        /* @var $plugin ZfExtended_Plugin_Abstract */
        if (empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }
        // some plugins call their public files from the task-context with /task/1234/plugins/PluginName/... if the requested files are task-dependant and thus need the fetched task
        $config = [];
        if($this->isTaskProvided()){
            $this->initCurrentTask();
            $config['task'] = $this->getCurrentTask();
        }

        // check if requested "fileType" is allowed
        if (!$plugin->isPublicSubFolder($requestedType, $config)) {
            throw new ZfExtended_NotFoundException();
        }

        $publicFile = $plugin->getPublicFile($requestedType, $requestedFileParts, $config);
        if (empty($publicFile) || !$publicFile->isFile()) {
            throw new ZfExtended_NotFoundException();
        }
        // Override default content-type text/html https://www.php.net/manual/en/ini.core.php#ini.default-mimetype
        // Prevents problems with files without extensions as is often the case with wget downloaded websites
        header('Content-Type: ' . ($types[$extension] ?? ''));

        // Unset default caching headers here in case we exit early with 304
        header_remove('Cache-Control');
        header_remove('Expires');
        header_remove('Pragma');

        $version = ZfExtended_Utils::getAppVersion();
        if($version === 'development'){
            $version = $publicFile->getMTime();  // changed file means new version
        }
        if($_SERVER['HTTP_IF_NONE_MATCH']??-1 == $version){ // compare Etag. We use version number to fetch file every release.
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
        // TODO FIXME: UGLY: the virtual Proxy-dir is defined in the visual plugin, that might not be active
        $isStaticVRFile = ($requestedType == 'T5Proxy' && $extension !== 'html'); // pdfconverter outputs that will never change
        if($version === 'development' && !$isStaticVRFile){
            $cacheBehaviour = 'no-cache'; // check for new version always
        } else if ($isStaticVRFile || $this->getParam('_dc')){ // refreshed through url (plugin js)
            $cacheBehaviour = 'max-age=31536000, immutable'; // check after 1 year aka 'never'
        } else {
            $cacheBehaviour = 'max-age=36000, must-revalidate'; // check after 10 hours (plugin css/png, VR/scroller.js)
        }

        header('Etag: '.$version);
        header('Cache-Control: '.$cacheBehaviour);
        header('Content-Length: '.$publicFile->getSize());
        readfile($publicFile);
        exit;
    }

    public function makexliffAction()
    {
        $input = $this->getParam('input', '');
        $matchrate = (integer)$this->getParam('matchrate', 50);
        $this->view->input = $input;
        $this->view->matchrate = $matchrate;
        if (empty($input)) {
            return;
        }

        $inputKey = base64_encode($input);

        $enTrans = ZfExtended_Factory::get('ZfExtended_Zendoverwrites_Translate', ['en']);
        $enMessages = $enTrans->getAdapter()->getMessages('en');

        $enXliff = function ($key, $de, $en) {
            return "<trans-unit id='" . $key . "'>\n  <source>" . $de . "</source>\n  <target>" . $en . "</target>\n</trans-unit>\n";
        };

        $this->view->enOut = [];
        $this->view->deOut = "<trans-unit id='" . $inputKey . "'><source>" . $input . "</source><target>" . $input . "</target></trans-unit>\n";
        if (empty($enMessages[$input])) {
            $deTrans = ZfExtended_Factory::get('ZfExtended_Zendoverwrites_Translate', ['de']);
            /* @var $deTrans ZfExtended_Zendoverwrites_Translate */
            $deMessages = $deTrans->getAdapter()->getMessages('de');
            $results = [];
            foreach ($deMessages as $key => $message) {
                $percentage = 0;
                similar_text($input, $message, $percentage);
                $percentage = round($percentage);
                if ($percentage > $matchrate) {
                    $results[$key] = (integer)ceil($percentage);
                }
            }
            asort($results);
            $results = array_reverse($results, true);
            foreach ($results as $key => $percentage) {
                if (empty($enMessages[$key])) {
                    continue;
                }
                $this->view->enOut[] = ['text' => $enXliff($inputKey, $input, $enMessages[$key]), 'matchrate' => $percentage];
                if (count($this->view->enOut) >= 5) {
                    break;
                }
            }
            $this->view->exactMatch = false;
            $this->view->noMatch = false;
        } else {
            $this->view->exactMatch = true;
            $this->view->noMatch = false;
            $this->view->enOut[] = ['text' => $enXliff($inputKey, $input, $enMessages[$input]), 'matchrate' => 100];
        }
        if (empty($this->view->enOut)) {
            $this->view->exactMatch = false;
            $this->view->noMatch = true;
            $this->view->enOut[] = ['text' => $enXliff($inputKey, $input, $input), 'matchrate' => 0];
        }
    }
}

