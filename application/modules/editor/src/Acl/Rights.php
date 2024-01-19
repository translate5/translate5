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

namespace MittagQI\Translate5\Acl;

use MittagQI\ZfExtended\Acl\AbstractResource;

/**
 * Holds and documents the translate5 ACL rights
 */
final class Rights extends AbstractResource {
    /**
     * Basic resource for business logic rights
     */
    public const ID = 'frontend';

//region Applets
    /**
     * enables the Editor Applet as initial page
     * @group Applet
     */
    public const APPLET_EDITOR = 'editor';
//endregion

//region qualities
    /**
     * Allows the user to use the quality overview for a task and re-check qualities
     * @group Qualities
     */
    public const EDITOR_MANAGE_QUALITIES = 'editorManageQualities';
//endregion

//region Main tabs
    /**
     * allows the usage of the project grid (as tab in the main window)
     * @group Task administration
     */
    public const EDITOR_PROJECT_TASK = 'editorProjectTask';

    /**
     * allows the usage of the task grid (as tab in the main window)
     * @group Task usage
     */
    public const TASK_OVERVIEW_FRONTEND_CONTROLLER = 'taskOverviewFrontendController';

    /**
     * allows the usage of the user administration (as tab in the main window)
     * @group Administration
     */
    public const USER_ADMINISTRATION = 'userAdministration';
//endregion

//region Administration
    /**
     * allows the usage of the customer administration (as tab in the main window)
     * @group Customer Administration
     */
    public const CUSTOMER_ADMINISTRATION = 'customerAdministration';

    /**
     * allows the adding of customers
     * @group Customer Administration
     */
    public const EDITOR_ADD_CUSTOMER = 'editorAddCustomer';

    /**
     * allows the deletion of customers
     * @group Customer Administration
     */
    public const EDITOR_DELETE_CUSTOMER = 'editorDeleteCustomer';

    /**
     * allows reading the auth hash via task user assoc API
     * @group API
     */
    public const READ_AUTH_HASH = 'readAuthHash';

    /**
     * enables the notification in the UI if a new update of translate5 is available
     * @group API
     */
    public const GET_UPDATE_NOTIFICATION = 'getUpdateNotification';
//endregion

//region Task usage
    /**
     * allows the usage of the task add window in the UI
     * @group Task usage
     */
    public const EDITOR_ADD_TASK = 'editorAddTask';

    /**
     * allows seeing all tasks, even if not assigned to task as user or as direct task PM
     * @group Task usage
     */
    public const LOAD_ALL_TASKS = 'loadAllTasks';

    /**
     * allows opening all tasks for editing, even if not assigned to task as user or as direct task PM
     * @group Task usage
     */
    public const EDIT_ALL_TASKS = 'editAllTasks';

    /**
     * allows opening all tasks for editing, even if not assigned to task as user or as direct task PM
     * @group Task usage
     */
    public const EDITOR_EDIT_ALL_TASKS = 'editorEditAllTasks';

    /**
     * allows editing the task attribute PM
     * @group Task attributes usage
     */
    public const EDITOR_EDIT_TASK_PM = 'editorEditTaskPm';

    /**
     * allows editing the task attribute order date
     * @group Task attributes usage
     */
    public const EDITOR_EDIT_TASK_ORDER_DATE = 'editorEditTaskOrderDate';

    /**
     * allows editing the task name
     * @group Task attributes usage
     */
    public const EDITOR_EDIT_TASK_TASK_NAME = 'editorEditTaskTaskName';

    /**
     * allows editing the task attribute Edit100PercentMatch
     * @group Task attributes usage
     */
    public const EDITOR_EDIT_TASK_EDIT_100_PERCENT_MATCH = 'editorEditTaskEdit100PercentMatch';

    /**
     * allows viewing the task preferences tab for a selected project task
     * @group Task preferences usage
     */
    public const EDITOR_PREFERENCES_TASK = 'editorPreferencesTask';

    /**
     * allows viewing the task workflow prefs (special user prefs) button
     * @group Task preferences usage
     */
    public const EDITOR_WORKFLOW_PREFS_TASK = 'editorWorkflowPrefsTask';

    /**
     * allows assigning language resources to tasks (UI)
     * @group Task preferences usage
     */
    public const LANGUAGE_RESOURCES_TASKASSOC = 'languageResourcesTaskassoc';

    /**
     * allows assigning language resources as pivot source to tasks (UI)
     * @group Task preferences usage
     */
    public const LANGUAGE_RESOURCES_TASK_PIVOT_ASSOC = 'languageResourcesTaskPivotAssoc';

    /**
     * allows the usage of the task user associations (UI)
     * @group Task preferences usage
     */
    public const TASK_USER_ASSOC_FRONTEND_CONTROLLER = 'taskUserAssocFrontendController';

    /**
     * allows the usage of the task custom fields (UI)
     * @group Task custom fields management
     */
    public const TASK_CUSTOM_FIELD_FRONTEND_CONTROLLER = 'taskCustomField';

    /**
     * allows usage of GET param force on DELETE task via API,
     *  which deletes the task even if the current task state prohibits it
     * @group Task usage
     */
    public const TASK_FORCE_DELETE = 'taskForceDelete';

    /**
     * allows cancelling a task in status import
     * @group Task usage
     */
    public const EDITOR_CANCEL_IMPORT = 'editorCancelImport';

    /**
     * allows opening the match-analysis panel as a standalone window (not as task preferences)
     * @group Task usage
     */
    public const EDITOR_ANALYSIS_TASK = 'editorAnalysisTask';
//endregion

//region Task editing

    /**
     * allows locking a single segment
     * @group Task editing
     */
    public const LOCK_SEGMENT_OPERATION = 'lockSegmentOperation';

    /**
     * allows un-locking a single segment
     * @group Task editing
     */
    public const UNLOCK_SEGMENT_OPERATION = 'unlockSegmentOperation';

    /**
     * allows segment batch locking on the filtered segment list
     * @group Task editing
     */
    public const LOCK_SEGMENT_BATCH = 'lockSegmentBatch';

    /**
     * allows segment batch un-locking on the filtered segment list
     * @group Task editing
     */
    public const UNLOCK_SEGMENT_BATCH = 'unlockSegmentBatch';

    /**
     * allows using change alikes (repetitions) functionality when editing repeated segments in a task
     * @group Task editing
     */
    public const USE_CHANGE_ALIKES = 'useChangeAlikes';
//endregion

//region Task export rights
    /**
     * Allow downloading a task in general (task download menu in the UI)
     * @group Task export
     */
    public const EDITOR_SHOWEXPORTMENU_TASK = 'editorShowexportmenuTask';

    /**
     * Allow exporting a task: default export, diff export, termtranslation export)
     * @group Task export
     */
    public const EDITOR_EXPORT_TASK = 'editorExportTask';

    /**
     * Download the tasks content as spreadsheet containing all segments, with the pre-translated target
     * and the target content after each workflow step.
     * @group Task export
     */
    public const EDITOR_EXPORT_EXCELHISTORY = 'editorExportExcelhistory';

    /**
     * Download the import archive
     * @group Task export
     */
    public const DOWNLOAD_IMPORT_ARCHIVE = 'downloadImportArchive';

    /**
     * Download the task as re-importable package
     * @group Task export
     */
    public const EDITOR_PACKAGE_EXPORT = 'editorPackageExport';

    /**
     * Re-import the exported task package
     * @group Task re-import
     */
    public const EDITOR_PACKAGE_REIMPORT = 'editorPackageReimport';
//endregion

//region Workflow
    /**
     * allows user to task assignment in the UI (and listing of changeable workflow steps in the assignment)
     * @group Workflow
     */
    public const EDITOR_CHANGE_USER_ASSOC_TASK = 'editorChangeUserAssocTask';

    /**
     * allows see original data behind anonymized user data
     * @group Workflow
     */
    public const READ_ANONYMYZED_USERS = 'readAnonymyzedUsers';

    /**
     * allows opening a task in general (read-only)
     * @group Workflow
     */
    public const EDITOR_OPEN_TASK = 'editorOpenTask';

    /**
     * allows editing a task in general
     * @group Workflow
     */
    public const EDITOR_EDIT_TASK = 'editorEditTask';

    /**
     * allows finishing a task
     * @group Workflow
     */
    public const EDITOR_FINISH_TASK = 'editorFinishTask';

    /**
     * allows ending a task
     * @group Workflow
     */
    public const EDITOR_END_TASK = 'editorEndTask';

    /**
     * allows to reopen (un finish) a task
     * @group Workflow
     */
    public const EDITOR_REOPEN_TASK = 'editorReopenTask';
//endregion

    /**
     * shows the application state data on the corresponding API endpoint
     * the usage of the applicationstate API endpoint itself is allowed to everybody for application pinging
     */
    public const APPLICATIONSTATE = 'applicationstate';

    /**
     * task leaving in UI is possible, although we are in editor only mode (useful for sysadmins etc.)
     */
    public const EDITOR_ONLY_OVERRIDE = 'editorOnlyOverride';

    //region PURE FRONTEND
    /**
     * enable the app token management grid in the preferences view
     * @group Preferences
     */
    public const TOKEN_GRID = 'tokenGrid';

    /**
     * enable the system log grid in the preferences view
     * @group Preferences
     */
    public const SYSTEM_LOG = 'systemLog';

    /**
     * enable the system status page in the preferences view
     * @group Preferences
     */
    public const SYSTEM_STATUS = 'systemStatus';

    /**
     * enables the users personal preferences page (change password / layout / language)
     * @group Preferences
     */
    public const USER_PREF_FRONTEND_CONTROLLER = 'userPrefFrontendController';

    /**
     * allows the usage of the user add window in the UI
     * @group User administration
     */
    public const EDITOR_ADD_USER = 'editorAddUser';

    /**
     * allows the deletion of users in the UI
     * @group User administration
     */
    public const EDITOR_DELETE_USER = 'editorDeleteUser';

    /**
     * allows the deletion of users in the UI
     * @group User administration
     */
    public const EDITOR_EDIT_USER = 'editorEditUser';

    /**
     * allows the deletion of users in the UI
     * @group User administration
     */
    public const EDITOR_RESET_PW_USER = 'editorResetPwUser';
//region Configuration
//FIXME link the rights somehow between the UI and PHP, the used-by ones are used only in the UI

    /**
     * enable the system configuration panel in the preferences view
     * @group configuration
     */
    public const CONFIG_OVERWRITE_GRID = 'configOverwriteGrid';

    /**
     * enables the task specific configuration panel in the preferences view
     * @group configuration
     */
    public const TASK_CONFIG_OVERWRITE_GRID = 'taskConfigOverwriteGrid';
//endregion


    /**
     * enable the customer change switch (multi-tenancy)
     * @group multi-tenancy
     */
    public const EDITOR_CUSTOMER_SWITCH = 'editorCustomerSwitch';

    /**
     * export task as Excel file and lock task for external processing
     * @group Task excel external processing
     */
    public const EDITOR_EXCELREEXPORT_TASK = 'editorExcelreexportTask';

    /**
     * allows re-import of a task-Excel exported file
     * @group Task excel external processing
     */
    public const EDITOR_EXCELREIMPORT_TASK = 'editorExcelreimportTask';

    /**
     * allows the user to have the Events Action item in the action menu
     * FIXME should be merged with editorTaskLog????
     * @group Task action menu
     */
    public const EDITOR_LOG_TASK = 'editorLogTask';

    /**
     * allows to use the task events panel
     * FIXME should be merged with editorLogTask????
     * @group Task meta data
     */
    public const EDITOR_TASK_LOG = 'editorTaskLog';

    /**
     * allows the usage of the task files panel
     * @group Task usage
     */
    public const TASK_REIMPORT = 'taskReimport';

    /**
     * allows to use the task KPI and download task KPI as Excel file
     * @group Task meta data
     */
    public const EDITOR_TASK_KPI = 'editorTaskKpi';

    /**
     * make the taskoverview column menu visibility configurable via ACL
     * allows the usage of the task grid header menu (filter / sort / column customization)
     * @group Task usage
     */
    public const EDITOR_TASK_OVERVIEW_COLUMN_MENU = 'editorTaskOverviewColumnMenu';

    /**
     * allows the usage of the burger menu of a task in the task/project overview grid
     * @group Task usage
     */
    public const EDITOR_MENU_TASK = 'editorMenuTask';

    /**
     * usage unclear, seems not to be used anymore...
     * @group Task usage
     * @deprecated TODO check if used somewhere
     */
    public const EDITOR_MENU_PROJECT = 'editorMenuProject';

    /**
     * allows reloading a project
     * @group Task usage
     */
    public const EDITOR_RELOAD_PROJECT = 'editorReloadProject';

    /**
     * allows the usage of task clone functionality in the UI
     * @group Task usage
     */
    public const EDITOR_CLONE_TASK = 'editorCloneTask';

    /**
     * allows the deletion of tasks in the UI
     * @group Task usage
     */
    public const EDITOR_DELETE_TASK = 'editorDeleteTask';

    /**
     * allows the deletion of projects in the UI
     * @group Project usage
     */
    public const EDITOR_DELETE_PROJECT = 'editorDeleteProject';


//endregion

//region language resources
    /**
     * Allows the adding of filebased language resources (mostly TMs)
     * @group Language Resources
     */
    public const LANGUAGE_RESOURCES_ADD_FILEBASED = 'languageResourcesAddFilebased';

    /**
     * Allows the adding of non filebased language resources (mostly MTs)
     * @group Language Resources
     */
    public const LANGUAGE_RESOURCES_ADD_NON_FILEBASED = 'languageResourcesAddNonFilebased';

    /**
     * allows the adding of Language Resources in general
     * @group Language Resources
     */
    public const EDITOR_ADD_LANGRESOURCE = 'editorAddLangresource';

    /**
     * allows the deletion of Language Resources in general
     * @group Language Resources
     */
    public const EDITOR_DELETE_LANGRESOURCE = 'editorDeleteLangresource';

    /**
     * allows the usage of the language resource match panel in the editor
     * @group Language Resources
     */
    public const LANGUAGE_RESOURCES_MATCH_QUERY = 'languageResourcesMatchQuery';

    /**
     * allows the usage of the language resource concordance search panel in the editor
     * @group Language Resources
     */
    public const LANGUAGE_RESOURCES_SEARCH_QUERY = 'languageResourcesSearchQuery';

    /**
     * allows the usage of the language resource synonym search panel
     * in the editor (if supported by the language resource, currently only MS)
     * @group Language Resources
     */
    public const LANGUAGE_RESOURCES_SYNONYM_SEARCH = 'languageResourcesSynonymSearch';

    /**
     * allows the administration of the language resource in general (as tab in the main window)
     * @group Language Resources
     */
    public const LANGUAGE_RESOURCES_OVERVIEW = 'languageResourcesOverview';
//endregion

//region PLUGINS
// TODO MOVE INTO THE ORIGINATION PLUGIN!

    /**
     * Enables the UI invocation of the spell-check in the editor (Editor.plugins.SpellCheck.controller.Editor)
     * @group Plug-In SpellCheck
     */
    public const PLUGIN_SPELL_CHECK = 'pluginSpellCheck';

    /**
     * Enables the UI invocation of the spell-check in the editor (Editor.plugins.SpellCheck.controller.Main)
     * @group Plug-In SpellCheck
     * FIXME makes no sense to have both controllers separatly ACL protected, needed both anyway
     */
    public const PLUGIN_SPELL_CHECK_MAIN = 'pluginSpellCheckMain';

    /**
     * Enables the UI invocation of the match analysis pricing presets
     * @group Plug-In Matchanalysis
     */
    public const PLUGIN_MATCH_ANALYSIS_PRICING_PRESET = 'pluginMatchAnalysisPricingPreset';

    /**
     * Enables the UI invocation of the match analysis in general
     * @group Plug-In Matchanalysis
     */
    public const PLUGIN_MATCH_ANALYSIS_MATCH_ANALYSIS = 'pluginMatchAnalysisMatchAnalysis';

    /**
     * Enables the UI invocation of the spell-check in the editor (Editor.plugins.SpellCheck.controller.Editor)
     * @group Plug-In SpellCheck
     */
    public const PLUGIN_GLOBALESE_PRE_TRANSLATION = 'pluginGlobalesePreTranslationGlobalese';

    /**
     * Allows the administration of the BCONF preferences
     * @group Plug-In Okapi
     */
    public const PLUGIN_OKAPI_BCONF_PREFS = 'pluginOkapiBconfPrefs';

    /**
     * enables the corresponding JS controller
     * @group frontend controller
     */
    public const ACL_RIGHT_PLUGIN_CHANGE_LOG_CHANGELOG = 'pluginChangeLogChangelog';
//endregion
}
