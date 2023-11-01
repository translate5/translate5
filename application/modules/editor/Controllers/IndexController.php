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

use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Applet\Dispatcher;
use MittagQI\Translate5\Task\FileTypeSupport;
use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\Reimport\FileparserRegistry;
use MittagQI\Translate5\Task\TaskContextTrait;
use MittagQI\Translate5\Cronjob\CronIpFactory;
use MittagQI\ZfExtended\Acl\SetAclRoleResource as BaseRoles;
use MittagQI\ZfExtended\CsrfProtection;

/**
 * Dummy Index Controller
 */
class Editor_IndexController extends ZfExtended_Controllers_Action
{
    use TaskContextTrait;

    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected ZfExtended_Zendoverwrites_Translate $translate;

    /**
     * @var Zend_Config
     */
    protected Zend_Config $config;

    /**
     * Main Definition of core frontend controllers, each controller is required (for js compiling)
     * but only the required ones are activated.
     * @var array
     */
    protected array $frontendEndControllers = [
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
        //if value is string[], then controlled by ACL, enabling frontend rights given here
        'admin.TaskOverview'            => [Rights::TASK_OVERVIEW_FRONTEND_CONTROLLER],
        'admin.TaskPreferences'         => [Rights::TASK_OVERVIEW_FRONTEND_CONTROLLER],
        'admin.TaskUserAssoc'           => [Rights::TASK_USER_ASSOC_FRONTEND_CONTROLLER],
        'admin.Customer'                => [Rights::CUSTOMER_ADMINISTRATION],
        'LanguageResourcesTaskassoc'    => [Rights::LANGUAGE_RESOURCES_TASKASSOC],
        'LanguageResources'             => [
            Rights::LANGUAGE_RESOURCES_MATCH_QUERY,
            Rights::LANGUAGE_RESOURCES_SEARCH_QUERY
        ],
        'TmOverview'                    => [Rights::LANGUAGE_RESOURCES_OVERVIEW],
        'Localizer'                     => true,
        'Quality'                       => true,
        //the check if this controller is active is task specific
        // (runtimeOptions.autoQA.enableMqmTags, flag is task specific)
        'QualityMqm'                    => true,
        'SegmentQualitiesBase'          => true,
    ];

    private ZfExtended_Acl $acl;
    private ZfExtended_Plugin_Manager $pluginManager;

    /**
     * View object
     * @var ZfExtended_View
     * @see Zend_Controller_Action::$view
     */
    public $view;

    /**
     * @throws Zend_Exception
     */
    public function init()
    {
        parent::init();
        $this->acl = ZfExtended_Acl::getInstance();
        $this->config = Zend_Registry::get('config');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->pluginManager = Zend_Registry::get('PluginManager');
    }

    /**
     * Serve application markup
     * @throws Zend_Exception
     */
    public function indexAction()
    {
        $this->_helper->layout->disableLayout();
        $this->view->pathToIMAGES = APPLICATION_RUNDIR . $this->config->runtimeOptions->server->pathToIMAGES;

        $userConfig = ZfExtended_Factory::get(editor_Models_Config::class);
        $userConfig = $userConfig->mergeUserValues(ZfExtended_Authentication::getInstance()->getUserGuid());
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
     * @throws ZfExtended_Exception
     * @throws Zend_Exception
     * @throws Exception
     */
    public function systemstatusAction()
    {
        $this->_helper->layout->disableLayout();
        $validator = new ZfExtended_Models_SystemRequirement_Validator(false);
        $results = $validator->validate();
        $this->view->hostname = gethostname();
        $this->view->validationResults = $results;
    }

    /**
     * Logs the users userAgent and screen size for usability improvements
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
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
        $auth = ZfExtended_Authentication::getInstance();

        $log = ZfExtended_Factory::get('editor_Models_BrowserLog');
        /* @var $log editor_Models_BrowserLog */

        $log->setDatetime(NOW_ISO);
        $log->setLogin($auth->getLogin());
        $log->setUserGuid($auth->getUserGuid());
        $log->setAppVersion($_POST['appVersion']);
        $log->setUserAgent($_POST['userAgent']);
        $log->setBrowserName($_POST['browserName']);
        $log->setMaxWidth($_POST['maxWidth']);
        $log->setMaxHeight($_POST['maxHeight']);
        $log->setUsedWidth($_POST['usedWidth']);
        $log->setUsedHeight($_POST['usedHeight']);

        $log->save();
    }

    /**
     * @throws Zend_Exception
     */
    protected function checkForUpdates(string $currentVersion)
    {
        if ($currentVersion == ZfExtended_Utils::VERSION_DEVELOPMENT) {
            return;
        }
        $downloader = ZfExtended_Factory::get(
            ZfExtended_Models_Installer_Downloader::class,
            [APPLICATION_ROOT]
        );

        if (! $this->isAllowed(Rights::ID, Rights::GET_UPDATE_NOTIFICATION)) {
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
     * returns additional configured CSS files
     * @return array
     * @throws Zend_Exception
     */
    protected function getAdditionalCss(): array
    {
        $config = Zend_Registry::get('config');
        if (empty($config->runtimeOptions->publicAdditions)) {
            return [];
        }
        /* @var $css Zend_Config */
        $css = $config->runtimeOptions->publicAdditions->css;
        if (empty($css)) {
            return [];
        }
        if (is_string($css)) {
            return [$css];
        }
        return $css->toArray();
    }

    /**
     * @throws Zend_Exception
     * @throws Exception
     */
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
        $this->view->Php2JsVars()->set(
            'pluginFolder',
            APPLICATION_RUNDIR . '/' . Zend_Registry::get('module') . '/plugins/js'
        );
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
        $this->view->Php2JsVars()->set('segments.autoStates', $states->getStateMap());
        $this->view->Php2JsVars()->set('segments.roleAutoStateMap', $states->getRoleToStateMap());

        $tagPath = APPLICATION_RUNDIR . '/' . $rop->dir->tagImagesBasePath . '/';
        $this->view->Php2JsVars()->set('segments.shortTagPath', $tagPath);
        $this->view->Php2JsVars()->set('segments.fullTagPath', $tagPath);

        //matchrate type to icon map
        $typesWihtIcons = array();
        foreach (editor_Models_Segment_MatchRateType::TYPES_WITH_ICONS as $type) {
            $typesWihtIcons[$type] = $this->view->publicModulePath . '/images/matchratetypes/' . $type . '.png';
        }

        //needed to give plugins the abilty to add own icons as matchrate types
        $this->view->Php2JsVars()->set('segments.matchratetypes', $typesWihtIcons);

        $this->view->Php2JsVars()->set('segments.subSegment.tagPath', $tagPath);

        // this initializes the CSRF token for the Frontend
        $this->view->Php2JsVars()->set('csrfToken', CsrfProtection::getInstance()->getToken());
        $this->view->Php2JsVars()->set('loginUrl', APPLICATION_RUNDIR . $rop->loginUrl);
        $this->view->Php2JsVars()->set('logoutOnWindowClose', $rop->logoutOnWindowClose);

        $this->view->Php2JsVars()->set('errorCodesUrl', $rop->errorCodesUrl);

        $this->view->Php2JsVars()->set('messageBox.delayFactor', $rop->messageBox->delayFactor);

        $this->view->Php2JsVars()->set('headerOptions.height', (int)$rop->headerOptions->height);
        $this->view->Php2JsVars()->set('languages', $this->getAvailableLanguages());

        //Editor.data.enableSourceEditing → still needed for enabling / disabling the
        // whole feature (Checkbox at Import).
        $this->view->Php2JsVars()->set('enableSourceEditing', (bool)$rop->import->enableSourceEditing);

        // set supported extensions
        $this->view->Php2JsVars()->set('import.validExtensions', FileTypeSupport::defaultInstance()->getSupportedExtensions());
        $this->view->Php2JsVars()->set(
            'import.forbiddenReferenceExtensions',
            editor_Models_Import_DirectoryParser_ReferenceFiles::FORBIDDEN_EXTENSIONS
        );
        $this->view->Php2JsVars()->set('import.nativeParserExtensions', FileTypeSupport::defaultInstance()->getNativeParserExtensions());

        $this->view->Php2JsVars()->set('columns.widthFactorHeader', (float)$rop->editor->columns->widthFactorHeader);
        $this->view->Php2JsVars()->set('columns.widthOffsetEditable', (int)$rop->editor->columns->widthOffsetEditable);
        $this->view->Php2JsVars()->set(
            'columns.widthFactorErgonomic',
            (float)$rop->editor->columns->widthFactorErgonomic
        );
        $this->view->Php2JsVars()->set('columns.maxWidth', (int)$rop->editor->columns->maxWidth);

        $this->view->Php2JsVars()->set('browserAdvice', $rop->browserAdvice);
        if ($rop->showSupportedBrowsersMsg) {
            $this->view->Php2JsVars()->set('supportedBrowsers', $rop->supportedBrowsers->toArray());
        }

        //create mailto link in the task list grid pm name column
        $this->view->Php2JsVars()->set('frontend.tasklist.pmMailTo', (boolean)$rop->frontend->tasklist->pmMailTo);

        $this->view->Php2JsVars()->set(
            'frontend.importTask.edit100PercentMatch',
            (bool)$rop->frontend->importTask->edit100PercentMatch
        );

        $this->view->Php2JsVars()->set(
            'frontend.importTask.pivotDropdownVisible',
            (bool)$rop->frontend->importTask->pivotDropdownVisible
        );

        $this->view->Php2JsVars()->set('frontend.changeUserThemeVisible', (bool)$rop->frontend->changeUserThemeVisible);

        // to identify the default customer in the frontend
        $this->view->Php2JsVars()->set('customers.defaultCustomerName', 'defaultcustomer');

        //is the openid data visible for the default customer
        $this->view->Php2JsVars()->set(
            'customers.openid.showOpenIdDefaultCustomerData',
            (boolean)$rop->customers->openid->showOpenIdDefaultCustomerData
        );

        $this->editorOnlyModeConfig($rop);

        $this->view->Php2JsVars()->set(
            'tasks.simultaneousEditingKey',
            editor_Models_Task::INTERNAL_LOCK . editor_Models_Task::USAGE_MODE_SIMULTANEOUS
        );
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

        $tmFileUploadSizeText = str_replace('{upload_max_filesize}', $uploadMaxFilesize, $tmFileUploadSizeText);


        //Info: custom vtype text must be translated here and set as frontend var.
        // There is no way of doing this with localizedjsstrings
        $this->view->Php2JsVars()->set('frontend.override.VTypes.tmFileUploadSizeText', $tmFileUploadSizeText);

        // set the max allowed upload filesize into frontend variable.
        // This is used for upload file size validation in tm import
        $this->view->Php2JsVars()->set('frontend.php.upload_max_filesize', $uploadMaxFilesize);
        
        // show Consortium Logos on application load for xyz seconds [default 3]
        $this->view->Php2JsVars()->set('startup.showConsortiumLogos', $rop->startup->showConsortiumLogos);

        //sets a list of url hashes to their redirects, shortcut to the applets
        $this->view->Php2JsVars()->set('directRedirects', Dispatcher::getInstance()->getHashPathMap());
        
        // set special characters list into a front-end view variable.
        // This should be removed after this config is moved to lvl 16
        $this->view->Php2JsVars()->set(
            'editor.segments.editorSpecialCharacters',
            $rop->editor->segments?->editorSpecialCharacters ?? ''
        );

        // add the supported file extensions for task reimport as frontend variable
        $this->view->Php2JsVars()->set(
            'editor.task.reimport.supportedExtensions',
            FileparserRegistry::getInstance()->getSupportedFileTypes()
        );
        $this->setJsAppData();
        editor_Segment_Quality_Manager::instance()->addAppJsData($this->view->Php2JsVars());
    }

    /***
     * Add translated theme names to a frontend variable
     * @param string $themes
     * @throws Zend_Exception
     */
    protected function setThemeVarsInView(string $themes): void
    {
        $themes = explode(',', $themes);
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

        $this->view->Php2JsVars()->setMultiple([
            'LanguageResources.preloadedSegments' => $rop->LanguageResources?->preloadedTranslationSegments,
            'LanguageResources.matchrateTypeChangedState' =>
                editor_Models_LanguageResources_LanguageResource::MATCH_RATE_TYPE_EDITED
        ]);

        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);

        $this->view->Php2JsVars()->set('LanguageResources.serviceNames', $serviceManager->getAllNames());
    }

    /**
     * Set the several data needed vor authentication / user handling in frontend
     * @throws Exception
     */
    protected function setJsAppData()
    {
        $ed = $this->config->runtimeOptions->editor;

        $php2js = $this->view->Php2JsVars();

        //the list of frontend controllers to be required for JS compiling (values should be static,
        // so not influenced by ACLs or Plugins)
        $php2js->set(
            'app.controllers.require',
            array_map(function ($item) {
                return 'Editor.controller.' . $item;
            }, array_keys($this->frontendEndControllers))
        );
        // the list of active controllers: is dynamic, contains only the controllers to be launched
        $php2js->set('app.controllers.active', $this->getActiveFrontendControllers());

        $this->loadCurrentTask($php2js);

        $php2js->set('app.viewport', $ed->editorViewPort);
        $php2js->set('app.startViewMode', $ed->startViewMode);
        $php2js->set('app.branding', (string)$this->translate->_($ed->branding));
        $php2js->set('app.company', $this->config->runtimeOptions->companyName);
        $php2js->set('app.name', $this->config->runtimeOptions->appName);
        $userData = (array) ZfExtended_Authentication::getInstance()->getUserData();

        // Trim TermPortal-roles if TermPortal plugin is disabled
        if (! $this->pluginManager->isActive('TermPortal')) {
            foreach ($userData['roles'] as $idx => $role) {
                if (str_starts_with($role, 'term')) {
                    unset($userData['roles'][$idx]);
                    // FIXME should be implemented in termportal plugin!
                }
            }
        }

        $userData['roles'] = array_values($userData['roles']);

        $php2js->set('app.user', $userData);
        $php2js->set('app.serverId', ZfExtended_Utils::installationHash('MessageBus'));
        $php2js->set('app.sessionKey', session_name());

        $roles = [];
        foreach (Roles::getFrontendRoles() as $role) {
            //set the setable, if the user is able to set/modify this role
            $roles[$role] = [
                'label' => $this->translate->_(ucfirst($role)),
                //role name is used as right in setaclrole
                'setable' => $this->isAllowed(BaseRoles::ID, $role)
            ];
        }
        $php2js->set('app.roles', $roles);

        $clientPmSubRoles = [];
        foreach (Roles::getClientPmSubroles() as $role) {
            $clientPmSubRoles[] = [
                $role,
                $this->translate->_($role)
            ];
        }
        $php2js->set('app.clientPmSubRoles', $clientPmSubRoles);

        $wm = ZfExtended_Factory::get(editor_Workflow_Manager::class);
        $php2js->set('app.workflows', $wm->getWorkflowData());
        $php2js->set('app.workflow.CONST', $wm->getWorkflowConstants());

        $php2js->set(
            'app.userRights',
            $this->acl->getFrontendRights(ZfExtended_Authentication::getInstance()->getUserRoles())
        );

        $php2js->set('app.version', $this->view->appVersion);

        $filter = ZfExtended_Factory::get(ZfExtended_Models_Filter_ExtJs6::class);
        $php2js->set('app.filters.translatedOperators', $filter->getTranslatedOperators());

        $config = ZfExtended_Factory::get(editor_Models_Config::class);

        //set frontend array from the config data
        //the array is used as initial user config store data
        $php2js->set('app.configData', $config->loadAllMerged('runtimeOptions.frontend.defaultState.%'));
    }

    /**
     * @param ZfExtended_View_Helper_Php2JsVars $php2js
     * @throws Exception
     */
    protected function loadCurrentTask(ZfExtended_View_Helper_Php2JsVars $php2js): void
    {
        if (!$this->isTaskProvided()) {
            $php2js->set('app.initMethod', 'openAdministration');
            return;
        }

        try {
            $this->initCurrentTask();
            // try to use the job of the current user and task, the one with a usedState,
        } catch (ZfExtended_Models_Entity_NotFoundException) { //SEE TRANSLATE-2972
            $this->redirect(APPLICATION_RUNDIR);
        } catch (NoAccessException) {
            // NoAccess is thrown here only of no job with used state was found,
            // this is handled later on getting the initState
        }

        $task = $this->getCurrentTask();  //on no access exception above current task is though set
        $taskData = $task->getDataObject();
        unset($taskData->qmSubsegmentFlags);

        $php2js->set('task', $taskData);
        $php2js->set('app.initState', $this->getInitialTaskUsedState());
        $php2js->set('app.initMethod', 'openEditor');
    }

    /**
     * returns the initial used state for the current task and job
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    protected function getInitialTaskUsedState(): string
    {
        $job = $this->getCurrentJob(); //currentJob is null if no
        if (!is_null($job)) {
            // we use the used state if a used job was found
            return $job->getUsedState();
        }
        // we try to load the first suitable job
        try {
            $job = editor_Models_Loaders_Taskuserassoc::loadByTask(
                ZfExtended_Authentication::getInstance()->getUserGuid(),
                $this->getCurrentTask()
            );
            return $this->getCurrentTask()->getTaskActiveWorkflow()->getInitialUsageState($job);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return editor_Workflow_Default::STATE_EDIT;
        }
    }

    /**
     * returns a list with used JS frontend controllers
     * @return array
     */
    protected function getActiveFrontendControllers(): array
    {
        //ensure Localizer beeing the last one, so we remove it and add it later to be at the arrays end
        unset($this->frontendEndControllers['Localizer']);

        //store only the enabled controller names
        $activeControllers = [];
        foreach ($this->frontendEndControllers as $controller => $enabled) {
            if (is_array($enabled)) {
                foreach ($enabled as $neededRightForController) {
                    if ($this->isAllowed(Rights::ID, $neededRightForController)) {
                        $enabled = true;
                        break; //at least only one right is needed out of the list
                    }
                }
            }
            if ($enabled === true) {
                $activeControllers[] = $controller;
            }
        }

        //add the active controllers from plugins
        $pluginFrontendControllers = $this->pluginManager->getActiveFrontendControllers();
        if (!empty($pluginFrontendControllers)) {
            $activeControllers = array_merge($activeControllers, $pluginFrontendControllers);
        }

        //Localizer must be the last in the list!
        $activeControllers[] = 'Localizer';
        return $activeControllers;
    }

    /**
     * Returns all configured languages in an array for displaying in frontend
     * @throws Zend_Exception
     */
    protected function getAvailableLanguages(): array
    {
        $model = ZfExtended_Factory::get(editor_Models_Languages::class);
        return $model->loadAllForDisplay();
    }

    /**
     * @throws Zend_Exception
     */
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

    /**
     * Applicationstate page, frontend: /application/modules/editor/views/scripts/index/applicationstate.phtml
     * No CSRF protection needed
     * @throws Zend_Acl_Exception
     * @throws Zend_Exception
     */
    public function applicationstateAction()
    {
        $this->_helper->layout->disableLayout();

        $cronIp = CronIpFactory::create();
        $hasAppStateACL = $this->isAllowed(Rights::ID, Rights::APPLICATIONSTATE);
        //since application state contains sensible information we show that only to the cron TP,
        // or with more details to the API users
        if ($cronIp->isAllowed() || $hasAppStateACL) {
            $this->view->applicationstate = ZfExtended_Debug::applicationState($hasAppStateACL);
        }
    }

    /**
     * Editor localization, frontend: /application/modules/editor/views/scripts/index/localizedjsstrings.phtml
     * No CSRF protection needed
     */
    public function localizedjsstringsAction()
    {
        $this->getResponse()->setHeader('Content-Type', 'text/javascript', true);
        $this->_helper->layout->disableLayout();
    }

    /**
     * Help-page for the ChangeAlike editor, frontend: /application/modules/editor/views/scripts/index/wdhelp.phtml
     * No CSRF protection needed
     */
    public function wdhehelpAction()
    {
        $this->_helper->layout->disableLayout();
    }

    /**
     * To prevent LFI attacks load existing Plugin JS filenames and use them as whitelist
     * Currently this Method is not reusable, it is only for JS.
     * @throws NoAccessException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_NotFoundException
     * @throws ZfExtended_Plugin_Exception
     * @throws \MittagQI\Translate5\Task\Current\Exception
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
        $plugin = $this->pluginManager->get($plugin);
        if (empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }
        // some plugins call their public files from the task-context with /task/1234/plugins/PluginName/...
        // if the requested files are task-dependant and thus need the fetched task
        $config = [];
        if ($this->isTaskProvided()) {
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
        if ($version === 'development') {
            $version = $publicFile->getMTime();  // changed file means new version
        }
        // compare Etag. We use version number to fetch file every release.
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? -1) == $version) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
        // TODO FIXME: UGLY: the virtual Proxy-dir is defined in the visual plugin, that might not be active
        // pdfconverter outputs that will never change
        $isStaticVRFile = ($requestedType == 'T5Proxy' && $extension !== 'html');
        if ($version === 'development' && !$isStaticVRFile) {
            $cacheBehaviour = 'no-cache'; // check for new version always
        } elseif ($isStaticVRFile || $this->getParam('_dc')) { // refreshed through url (plugin js)
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

    /**
     * Provides a smart interface to generate XLF fragments for the internal translation files.
     *   Usage in the UI: Enter the german text in the form, a german XLF fragment is generated, existing
     *   translations are searched for similar texts and a english XLF fragment is generated of that. Final translation
     *   must be done manually then.
     *
     * No CSRF Protection needed: this method is protected by ACL rules and is only accessible on development machines
     *  by adding ACL access manually!
     */
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

        $localeTemplate = function ($key, $source, $target) {
            return "<trans-unit id='" . $key . "'>\n  <source>" . $source . "</source>\n  <target>"
                . $target . "</target>\n</trans-unit>\n";
        };

        $this->view->enOut = [];
        $this->view->deOut = $localeTemplate($inputKey, $input, $input);
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
                $this->view->enOut[] = [
                    'text' => $localeTemplate($inputKey, $input, $enMessages[$key]),
                    'matchrate' => $percentage
                ];
                if (count($this->view->enOut) >= 5) {
                    break;
                }
            }
            $this->view->exactMatch = false;
            $this->view->noMatch = false;
        } else {
            $this->view->exactMatch = true;
            $this->view->noMatch = false;
            $this->view->enOut[] = [
                'text' => $localeTemplate($inputKey, $input, $enMessages[$input]),
                'matchrate' => 100
            ];
        }
        if (empty($this->view->enOut)) {
            $this->view->exactMatch = false;
            $this->view->noMatch = true;
            $this->view->enOut[] = ['text' => $localeTemplate($inputKey, $input, $input), 'matchrate' => 0];
        }
    }

    private function editorOnlyModeConfig(Zend_Config $rop)
    {
        $config = $rop->editor->toolbar;
        $forceLeaveButton = $this->isAllowed(Rights::ID, Rights::EDITOR_ONLY_OVERRIDE);
        $hideClosebutton = $config->hideCloseButton || $forceLeaveButton;
        $hideLeaveButton = $config->hideLeaveTaskButton && !$forceLeaveButton;
        $this->view->Php2JsVars()->setMultiple([
            //boolean config if the logout button in the segments editor header is visible or not
            'editor.toolbar.hideCloseButton' => $hideClosebutton,
            //boolean config if the leave task button in the segments editor header is visible or not
            'editor.toolbar.hideLeaveTaskButton' => $hideLeaveButton,
            //wrong naming, is evaluated on close and leave!
            'editor.toolbar.askFinishOnClose' => (boolean) $config->askFinishOnClose,
        ]);
    }

    /**
     * convenient shortcut to ACLs
     */
    private function isAllowed(string $resource, ?string $right = null): bool
    {
        $roles = ZfExtended_Authentication::getInstance()->getUserRoles();
        try {
            return $this->acl->isInAllowedRoles($roles, $resource, $right);
        } catch (Zend_Acl_Exception) {
            return false;
        }
    }
}

