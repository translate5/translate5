-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- base rights client-pm
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
('default', 'clientpm', 'auto_set_role', 'editor'),
('editor', 'clientpm', 'applicationconfigLevel', 'customer'),
('editor', 'clientpm', 'applicationconfigLevel', 'task'),
('editor', 'clientpm', 'applicationconfigLevel', 'taskImport'),
('editor', 'clientpm', 'applicationconfigLevel', 'user'),
('editor', 'clientpm', 'auto_set_role', 'editor'),
('editor', 'clientpm', 'backend', 'editAllTasks'),
('editor', 'clientpm', 'backend', 'loadAllTasks'),
('editor', 'clientpm', 'backend', 'seeAllUsers'),
('editor', 'clientpm', 'editor_apps', 'all'),
('editor', 'clientpm', 'editor_config', 'all'),
('editor', 'clientpm', 'editor_customer', 'index'),
('editor', 'clientpm', 'editor_customer', 'put'),
('editor', 'clientpm', 'editor_customer', 'exportresource'),
('editor', 'clientpm', 'editor_customermeta', 'all'),
('editor', 'clientpm', 'editor_file', 'all'),
('editor', 'clientpm', 'editor_filetree', 'root'),
('editor', 'clientpm', 'editor_languageresourceinstance', 'all'),
('editor', 'clientpm', 'editor_languageresourceresource', 'all'),
('editor', 'clientpm', 'editor_languageresourcetaskassoc', 'all'),
('editor', 'clientpm', 'editor_languageresourcetaskpivotassoc', 'all'),
('editor', 'clientpm', 'editor_languageresourcetaskpivotassoc', 'pretranslationOperation'),
('editor', 'clientpm', 'editor_plugins_globalesepretranslation_globalese', 'all'),
('editor', 'clientpm', 'editor_plugins_visualreview_fonts', 'all'),
('editor', 'clientpm', 'editor_plugins_visualreview_visualreview', 'all'),
('editor', 'clientpm', 'editor_task', 'all'),
('editor', 'clientpm', 'editor_task', 'analysisOperation'),
('editor', 'clientpm', 'editor_task', 'autoqaOperation'),
('editor', 'clientpm', 'editor_task', 'excelexport'),
('editor', 'clientpm', 'editor_task', 'excelreimport'),
('editor', 'clientpm', 'editor_task', 'pretranslationOperation'),
('editor', 'clientpm', 'editor_task', 'userlist'),
('editor', 'clientpm', 'editor_taskmeta', 'all'),
('editor', 'clientpm', 'editor_taskuserassoc', 'all'),
('editor', 'clientpm', 'editor_term', 'all'),
('editor', 'clientpm', 'editor_termcollection', 'all'),
('editor', 'clientpm', 'editor_user', 'all'),
('editor', 'clientpm', 'editor_userassocdefault', 'all'),
('editor', 'clientpm', 'editor_workflowuserpref', 'all'),
('editor', 'clientpm', 'frontend', 'downloadImportArchive'),
('editor', 'clientpm', 'frontend', 'editorAddLangresource'),
('editor', 'clientpm', 'frontend', 'editorAddTask'),
('editor', 'clientpm', 'frontend', 'editorAddUser'),
('editor', 'clientpm', 'frontend', 'editorChangeUserAssocTask'),
('editor', 'clientpm', 'frontend', 'editorCloneTask'),
('editor', 'clientpm', 'frontend', 'editorCustomerSwitch'),
('editor', 'clientpm', 'frontend', 'editorDeleteLangresource'),
('editor', 'clientpm', 'frontend', 'editorDeleteProject'),
('editor', 'clientpm', 'frontend', 'editorDeleteTask'),
('editor', 'clientpm', 'frontend', 'editorDeleteUser'),
('editor', 'clientpm', 'frontend', 'editorEditAllTasks'),
('editor', 'clientpm', 'frontend', 'editorEditTaskEdit100PercentMatch'),
('editor', 'clientpm', 'frontend', 'editorEditTaskOrderDate'),
('editor', 'clientpm', 'frontend', 'editorEditTaskPm'),
('editor', 'clientpm', 'frontend', 'editorEditTaskTaskName'),
('editor', 'clientpm', 'frontend', 'editorEditUser'),
('editor', 'clientpm', 'frontend', 'editorEndTask'),
('editor', 'clientpm', 'frontend', 'editorExcelreexportTask'),
('editor', 'clientpm', 'frontend', 'editorExcelreimportTask'),
('editor', 'clientpm', 'frontend', 'editorExportExcelhistory'),
('editor', 'clientpm', 'frontend', 'editorExportTask'),
('editor', 'clientpm', 'frontend', 'editorLogTask'),
('editor', 'clientpm', 'frontend', 'editorManageQualities'),
('editor', 'clientpm', 'frontend', 'editorMenuProject'),
('editor', 'clientpm', 'frontend', 'editorPreferencesTask'),
('editor', 'clientpm', 'frontend', 'editorReloadProject'),
('editor', 'clientpm', 'frontend', 'editorReopenTask'),
('editor', 'clientpm', 'frontend', 'editorResetPwUser'),
('editor', 'clientpm', 'frontend', 'editorShowexportmenuTask'),
('editor', 'clientpm', 'frontend', 'editorTaskKpi'),
('editor', 'clientpm', 'frontend', 'editorTaskLog'),
('editor', 'clientpm', 'frontend', 'editorTaskOverviewColumnMenu'),
('editor', 'clientpm', 'frontend', 'editorWorkflowPrefsTask'),
('editor', 'clientpm', 'frontend', 'languageResourcesAddFilebased'),
('editor', 'clientpm', 'frontend', 'languageResourcesTaskassoc'),
('editor', 'clientpm', 'frontend', 'languageResourcesTaskPivotAssoc'),
('editor', 'clientpm', 'frontend', 'lockSegmentBatch'),
('editor', 'clientpm', 'frontend', 'lockSegmentOperation'),
('editor', 'clientpm', 'frontend', 'plugin24Translate'),
('editor', 'clientpm', 'frontend', 'pluginDeepL'),
('editor', 'clientpm', 'frontend', 'pluginGlobalesePreTranslationGlobalese'),
('editor', 'clientpm', 'frontend', 'pluginGroupShare'),
('editor', 'clientpm', 'frontend', 'pluginInstantTranslateInstantTranslate'),
('editor', 'clientpm', 'frontend', 'pluginNecTm'),
('editor', 'clientpm', 'frontend', 'pluginPangeaMt'),
('editor', 'clientpm', 'frontend', 'pluginSpellCheck'),
('editor', 'clientpm', 'frontend', 'pluginSpellCheckMain'),
('editor', 'clientpm', 'frontend', 'pluginTextShuttle'),
('editor', 'clientpm', 'frontend', 'pluginVisualReviewAnnotations'),
('editor', 'clientpm', 'frontend', 'pluginVisualReviewGlobal'),
('editor', 'clientpm', 'frontend', 'pluginVisualReviewSegmentMapping'),
('editor', 'clientpm', 'frontend', 'readAnonymyzedUsers'),
('editor', 'clientpm', 'frontend', 'taskConfigOverwriteGrid'),
('editor', 'clientpm', 'frontend', 'taskReimport'),
('editor', 'clientpm', 'frontend', 'taskUserAssocFrontendController'),
('editor', 'clientpm', 'frontend', 'unlockSegmentBatch'),
('editor', 'clientpm', 'frontend', 'unlockSegmentOperation'),
('editor', 'clientpm', 'initial_tasktype', 'default'),
('editor', 'clientpm', 'initial_tasktype', 'project'),
('editor', 'clientpm', 'initial_tasktype', 'projectTask'),
('editor', 'clientpm', 'setaclrole', 'editor'),
('editor', 'clientpm', 'setaclrole', 'instantTranslate'),
('editor', 'clientpm', 'setaclrole', 'instantTranslateWriteTm'),
('editor', 'clientpm', 'setaclrole', 'termCustomerSearch'),
('editor', 'clientpm', 'setaclrole', 'termFinalizer'),
('editor', 'clientpm', 'setaclrole', 'termPM'),
('editor', 'clientpm', 'setaclrole', 'termPM_allClients'),
('editor', 'clientpm', 'setaclrole', 'termProposer'),
('editor', 'clientpm', 'setaclrole', 'termReviewer');

-- special sub-roles client-pm
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
('editor', 'clientpm_projects', 'frontend', 'editorProjectTask'),
('editor', 'clientpm_langresources', 'frontend', 'languageResourcesOverview'),
('editor', 'clientpm_customers', 'frontend', 'customerAdministration'),
('editor', 'clientpm_users', 'frontend', 'userAdministration');

-- neccessary rights other users
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
('editor', 'pm', 'frontend', 'editorAddCustomer'),
('editor', 'pm', 'frontend', 'editorDeleteCustomer'),
('editor', 'pm', 'frontend', 'editorAddLangresource'),
('editor', 'pm', 'frontend', 'editorDeleteLangresource'),
('editor', 'pm', 'setaclrole', 'clientpm'),
('editor', 'pm', 'setaclrole', 'clientpm_projects'),
('editor', 'pm', 'setaclrole', 'clientpm_langresources'),
('editor', 'pm', 'setaclrole', 'clientpm_customers'),
('editor', 'pm', 'setaclrole', 'clientpm_users'),
('editor', 'admin', 'setaclrole', 'clientpm'),
('editor', 'admin', 'setaclrole', 'clientpm_projects'),
('editor', 'admin', 'setaclrole', 'clientpm_langresources'),
('editor', 'admin', 'setaclrole', 'clientpm_customers'),
('editor', 'admin', 'setaclrole', 'clientpm_users'),
('editor', 'systemadmin', 'setaclrole', 'clientpm'),
('editor', 'systemadmin', 'setaclrole', 'clientpm_projects'),
('editor', 'systemadmin', 'setaclrole', 'clientpm_langresources'),
('editor', 'systemadmin', 'setaclrole', 'clientpm_customers'),
('editor', 'systemadmin', 'setaclrole', 'clientpm_users');
