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

insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
values  ('editor', 'pmlight', 'applicationconfigLevel', 'task'),
        ('editor', 'pmlight', 'applicationconfigLevel', 'taskImport'),
        ('editor', 'pmlight', 'applicationconfigLevel', 'user'),
        ('editor', 'pmlight', 'auto_set_role', 'editor'),
        ('editor', 'pmlight', 'backend', 'seeAllUsers'),
        ('editor', 'pmlight', 'editor_apps', 'all'),
        ('editor', 'pmlight', 'editor_config', 'all'),
        ('editor', 'pmlight', 'editor_plugins_changelog_changelog', 'all'),
        ('editor', 'pmlight', 'editor_plugins_globalesepretranslation_globalese', 'all'),
        ('editor', 'pmlight', 'editor_plugins_matchanalysis_matchanalysis', 'all'),
        ('editor', 'pmlight', 'editor_plugins_visualreview_fonts', 'all'),
        ('editor', 'pmlight', 'editor_plugins_visualreview_visualreview', 'all'),
        ('editor', 'pmlight', 'editor_task', 'all'),
        ('editor', 'pmlight', 'editor_task', 'analysisOperation'),
        ('editor', 'pmlight', 'editor_task', 'excelexport'),
        ('editor', 'pmlight', 'editor_task', 'excelreimport'),
        ('editor', 'pmlight', 'editor_task', 'pretranslationOperation'),
        ('editor', 'pmlight', 'editor_task', 'userlist'),
        ('editor', 'pmlight', 'editor_taskmeta', 'all'),
        ('editor', 'pmlight', 'editor_taskuserassoc', 'all'),
        ('editor', 'pmlight', 'editor_term', 'all'),
        ('editor', 'pmlight', 'editor_termcollection', 'all'),
        ('editor', 'pmlight', 'editor_user', 'index'),
        ('editor', 'pmlight', 'editor_customer', 'index'),
        ('editor', 'pmlight', 'editor_workflowuserpref', 'all'),
        ('editor', 'pmlight', 'editor_languageresourcetaskassoc', 'all'),
        ('editor', 'pmlight', 'frontend', 'editorAddTask'),
        ('editor', 'pmlight', 'frontend', 'editorAnalysisTask'),
        ('editor', 'pmlight', 'frontend', 'editorChangeUserAssocTask'),
        ('editor', 'pmlight', 'frontend', 'editorCloneTask'),
        ('editor', 'pmlight', 'frontend', 'editorDeleteProject'),
        ('editor', 'pmlight', 'frontend', 'editorDeleteTask'),
        ('editor', 'pmlight', 'frontend', 'editorEditTaskDeliveryDate'),
        ('editor', 'pmlight', 'frontend', 'editorEditTaskEdit100PercentMatch'),
        ('editor', 'pmlight', 'frontend', 'editorEditTaskTaskName'),
        ('editor', 'pmlight', 'frontend', 'editorEndTask'),
        ('editor', 'pmlight', 'frontend', 'editorExcelreexportTask'),
        ('editor', 'pmlight', 'frontend', 'editorExcelreimportTask'),
        ('editor', 'pmlight', 'frontend', 'editorExportTask'),
        ('editor', 'pmlight', 'frontend', 'editorLogTask'),
        ('editor', 'pmlight', 'frontend', 'editorMenuProject'),
        ('editor', 'pmlight', 'frontend', 'editorPreferencesTask'),
        ('editor', 'pmlight', 'frontend', 'editorProjectTask'),
        ('editor', 'pmlight', 'frontend', 'editorReopenTask'),
        ('editor', 'pmlight', 'frontend', 'editorShowexportmenuTask'),
        ('editor', 'pmlight', 'frontend', 'editorTaskKpi'),
        ('editor', 'pmlight', 'frontend', 'editorTaskLog'),
        ('editor', 'pmlight', 'frontend', 'editorTaskOverviewColumnMenu'),
        ('editor', 'pmlight', 'frontend', 'editorWorkflowPrefsTask'),
        ('editor', 'pmlight', 'frontend', 'pluginGlobalesePreTranslationGlobalese'),
        ('editor', 'pmlight', 'frontend', 'pluginInstantTranslateInstantTranslate'),
        ('editor', 'pmlight', 'frontend', 'pluginMatchAnalysisMatchAnalysis'),
        ('editor', 'pmlight', 'frontend', 'pluginSpellCheck'),
        ('editor', 'pmlight', 'frontend', 'pluginVisualReviewFontPrefs'),
        ('editor', 'pmlight', 'frontend', 'pluginVisualReviewGlobal'),
        ('editor', 'pmlight', 'frontend', 'pluginVisualReviewSegmentMapping'),
        ('editor', 'pmlight', 'frontend', 'languageResourcesTaskassoc'),
        ('editor', 'pmlight', 'frontend', 'taskUserAssocFrontendController'),
        ('editor', 'pmlight', 'frontend', 'readAnonymyzedUsers'),
        ('editor', 'pmlight', 'initial_tasktype', 'default'),
        ('editor', 'pmlight', 'initial_tasktype', 'project'),
        ('editor', 'pmlight', 'initial_tasktype', 'projectTask'),
        ('editor', 'pmlight', 'setaclrole', 'editor'),
        ('editor', 'pmlight', 'setaclrole', 'instantTranslate'),
        ('editor', 'pmlight', 'setaclrole', 'termCustomerSearch'),
        ('editor', 'pmlight', 'setaclrole', 'termProposer');

DELETE FROM `Zf_acl_rules` WHERE resource = 'backend' and `right` = 'customerAdministration';

-- duplication of adminUserFrontendController to separate job and user management
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
    SELECT `module`, `role`, `resource`, 'taskUserAssocFrontendController' as `right` FROM `Zf_acl_rules`
    WHERE `right` = 'adminUserFrontendController';

UPDATE `Zf_acl_rules` SET `right` = 'userAdministration' WHERE `right` = 'adminUserFrontendController';

insert into `Zf_acl_rules` (`module`, `role`, `resource`, `right`) values
('editor', 'pm', 'setaclrole', 'pmlight'),
('editor', 'admin', 'setaclrole', 'pmlight'),
('editor', 'api', 'setaclrole', 'pmlight');