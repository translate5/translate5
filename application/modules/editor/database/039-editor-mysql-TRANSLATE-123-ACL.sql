-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

INSERT INTO Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES 
('editor', 'noRights', 'error', 'all'),
('editor', 'noRights', 'editor_cron', 'all'),
('editor', 'basic', 'editor_index', 'all'),
('editor', 'basic', 'editor_qmstatistics', 'all'),
('editor', 'basic', 'frontend', 'editorShowexportmenuTask'),
('editor', 'basic', 'headPanelFrontendController', 'all'),
('editor', 'basic', 'userPrefFrontendController', 'all'),
('editor', 'basic', 'taskOverviewFrontendController', 'all'),
('editor', 'basic', 'editor_user', 'authenticated'),
('editor', 'editor', 'editor_task', 'index'),
('editor', 'editor', 'editor_task', 'get'),
('editor', 'editor', 'editor_task', 'put'),
('editor', 'editor', 'editor_file', 'all'),
('editor', 'editor', 'editor_comment', 'all'),
('editor', 'editor', 'editor_segment', 'all'),
('editor', 'editor', 'editor_segmentfield', 'all'),
('editor', 'editor', 'editor_alikesegment', 'all'),
('editor', 'editor', 'editor_referencefile', 'all'),
('editor', 'editor', 'editor_user', 'index'),
('editor', 'editor', 'frontend', 'editorFinishTask'),
('editor', 'editor', 'frontend', 'editorOpenTask'),
('editor', 'editor', 'frontend', 'editorEditTask'),
('editor', 'editor', 'frontend', 'useChangeAlikes'), -- ; right to disable changeAlikesin general per acl, diable editor_alikesegment controller also!
('editor', 'pm', 'editor_task', 'all'),
('editor', 'pm', 'editor_user', 'all'),
('editor', 'pm', 'editor_taskuserassoc', 'all'),
('editor', 'pm', 'editor_workflowuserpref', 'all'),
('editor', 'pm', 'loadAllTasks', 'all'),
('editor', 'pm', 'editAllTasks', 'all'),
('editor', 'pm', 'adminUserFrontendController', 'all'),
('editor', 'pm', 'frontend', 'editorAddTask'),
('editor', 'pm', 'frontend', 'editorExportTask'),
('editor', 'pm', 'frontend', 'editorEndTask'),
('editor', 'pm', 'frontend', 'editorReopenTask'),
('editor', 'pm', 'frontend', 'editorPreferencesTask'), -- ; enables the whole preferences dialog
('editor', 'pm', 'frontend', 'editorChangeUserAssocTask'), --  ; enables the change user assoc tab
('editor', 'pm', 'frontend', 'editorUserPrefsTask'), -- ; enables the user prefs tab (currently also workflow setting of task, since this is the only task pref)
('editor', 'pm', 'frontend', 'editorDeleteTask'),
('editor', 'pm', 'frontend', 'editorAddUser'),
('editor', 'pm', 'frontend', 'editorEditUser'),
('editor', 'pm', 'frontend', 'editorDeleteUser'),
('editor', 'pm', 'frontend', 'editorResetPwUser'),
('editor', 'pm', 'frontend', 'editorEditAllTasks');