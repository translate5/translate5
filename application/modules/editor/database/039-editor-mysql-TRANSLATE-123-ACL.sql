--  /*
--  START LICENSE AND COPYRIGHT
--  
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--  
--  Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
-- 
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
--  General Public License version 3.0 as specified by Sencha for Ext Js. 
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--  
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--  
--  
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--  
--  END LICENSE AND COPYRIGHT 
--  */
-- 

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