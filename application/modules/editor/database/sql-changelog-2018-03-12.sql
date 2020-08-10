
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-03-12', 'TRANSLATE-1166', 'feature', 'New task status "unconfirmed" and confirmation procedure', 'If enabled in configuration new tasks are created with status unconfirmed und must be confirmed by a PM or a proofreader before usable for proofreading.', '14'),
('2018-03-12', 'TRANSLATE-1070', 'feature', 'Make initial values of checkboxes in task add window configurable', 'In the task creation wizard the initial values of some fields can be configured.', '12'),
('2018-03-12', 'TRANSLATE-949', 'feature', 'delete old tasks by cron job (config sql file)', 'delete old (unused for a configurable time interval) tasks by cron job. Feature must be activated via Workflow Actions.', '12'),
('2018-03-12', 'TRANSLATE-1144', 'change', 'Disable translate5 update popup for non admin users', 'Now only admin users are getting such a notice.', '12'),
('2018-03-12', 'PMs without loadAllTasks should be able to see their tasks, even without a task assoc.', 'change', 'PMs without loadAllTasks should be able to see their tasks, even without a task assoc.', 'PMs without loadAllTasks should be able to see their tasks, even without a task assoc.', '12'),
('2018-03-12', 'TRANSLATE-1114', 'change', 'TrackChanges: fast replacing selected content triggers debugger statement', 'The debugger statement was removed. ', '12'),
('2018-03-12', 'TRANSLATE-1145', 'change', 'Using TrackChanges and MatchResources was not working as expected', 'Using TrackChanges and MatchResources was not working as expected', '14'),
('2018-03-12', 'TRANSLATE-1143', 'change', 'VisualReview: The text in the tooltips with ins-del tags is not readable in visualReview layout', 'The text in the tooltips with ins-del tags is not readable in visualReview layout', '14'),
('2018-03-12', 'T5DEV-234 TrackChanges', 'change', 'TrackChanges: Fixing handling of translate5 internal keyboard shortcuts.', 'TrackChanges: Fixing handling of translate5 internal keyboard shortcuts.', '12'),
('2018-03-12', 'TRANSLATE-1178', 'bugfix', 'if there are only directories and not files in proofRead, this results in "no importable files in the task"', 'Now the proofRead folder can contain just folders.', '12'),
('2018-03-12', 'TRANSLATE-1078', 'bugfix', 'VisualReview: Upload of single PDF file in task upload wizard does not work', 'Now the single upload works also for VisualReview.', '12'),
('2018-03-12', 'TRANSLATE-1164', 'bugfix', 'VisualReview throws an exception with disabled headpanel', 'VisualReview throws an exception with disabled headpanel', '14'),
('2018-03-12', 'TRANSLATE-1155', 'bugfix', 'Adding a translation check user to a proofreading task changes workflow step to translation', 'The workflow step of a task was erroneously changed on a adding at first a translation check user in a proofreading task. This is fixed right now.', '12'),
('2018-03-12', 'TRANSLATE-1153', 'bugfix', 'Fixing Error: editor is not found', 'Fixing Error: editor is not found', '14'),
('2018-03-12', 'TRANSLATE-1148', 'bugfix', 'Maximum characters allowed in toSort column is over the limit', 'Maximum characters allowed in toSort column is over the limit', '8'),
('2018-03-12', 'TRANSLATE-969', 'bugfix', 'Calculation of next editable segment fails when sorting and filtering for a content column', 'Calculation of next editable segment fails when sorting and filtering for a content column', '14'),
('2018-03-12', 'TRANSLATE-1147', 'bugfix', 'TrackChanges: Missing translations in application', 'Missing translations added', '14'),
('2018-03-12', 'TRANSLATE-1042', 'bugfix', 'copy source to target is not working in firefox', 'copy source to target is not working in firefox', '14');