
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

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-09-30', 'TRANSLATE-2302', 'feature', 'Accept and reject TrackChanges', 'Plugin TrackChanges
* added capabilities for the editor, to accept/reject changes from preceiding workflow-steps
* reduced tracking of changes in the translation step, only pretranslated segments are tracked
* by default, TrackChanges is invisible in the translation step
* the visibility of changes is normally reduced to the changes of the preceiding workflow steps
* the visibility and capability to accept/reject for the editor can be set via the user assocciations on the task and customer level', '15'),
('2021-09-30', 'TRANSLATE-1405', 'feature', 'TermPortal as terminology management solution', 'Introduced the brand new TermPortal, now completely usable as terminology management solution.', '15'),
('2021-09-30', 'TRANSLATE-2629', 'change', 'Integrate beo-proposals for German names of standard tbx attributes', 'Term-portal improvement UI names of standard TBX attributes', '15'),
('2021-09-30', 'TRANSLATE-2625', 'change', 'Solve tag errors automatically on export', 'Internal Tag Errors (faulty structure) will be fixed automatically when exporting a task: Orphan opening/closing tags will be removed, structurally broken tag pairs will be corrected. The errors in the task itself will remain.', '15'),
('2021-09-30', 'TRANSLATE-2623', 'change', 'Move theme switch button and language switch button in settings panel', 'The drop-down for switching the translate5 language and translate5 theme is moved under "Preferences" ->"My settings" tab.', '15'),
('2021-09-30', 'TRANSLATE-2622', 'change', 'CLI video in settings help window', 'Integrate CLI video in preferences help page.', '15'),
('2021-09-30', 'TRANSLATE-2611', 'change', 'Check Visual Review URLs before downloading them if they are accessible', 'Added additional check for Visual Review URLs if the URL is accessible before downloading it to improve the logged error', '15'),
('2021-09-30', 'TRANSLATE-2621', 'bugfix', 'Logging task specific stuff before task is saved leads to errors', 'In seldom cases it may happen that task specific errors should be logged in the time before the task was first saved to DB, this was producing a system error on processing the initial error and the information about the initial error was lost.', '15'),
('2021-09-30', 'TRANSLATE-2618', 'bugfix', 'Rename tooltips for next segment in translate5', 'Improves tooltip text in editor meta panel segment navigation.', '15'),
('2021-09-30', 'TRANSLATE-2614', 'bugfix', 'Correct translate5 workflow names of complex workflow', 'Improve the step names and translations of the complex workflow', '15'),
('2021-09-30', 'TRANSLATE-2612', 'bugfix', 'Job status changes from open to waiting on deadline change', 'If the deadline of a job in a task is changed, the status of the job changes from "open" to "waiting". This is fixed.', '15'),
('2021-09-30', 'TRANSLATE-2609', 'bugfix', 'Import of MemoQ comments fails', 'HOTFIX: MemoQ comment parsing produces corrupt comments with single comment nodes. Add Exception to the base parsing API to prevent usage of negative length\'s', '15'),
('2021-09-30', 'TRANSLATE-2603', 'bugfix', 'Browser does not refresh cache for maintenance page', 'It could happen that users were hanging in the maintenance page - depending on their proxy / cache settings. This is solved now.', '15'),
('2021-09-30', 'TRANSLATE-2602', 'bugfix', 'msg is not defined', 'Fixed a ordinary programming error in the frontend message bus.', '15'),
('2021-09-30', 'TRANSLATE-2601', 'bugfix', 'role column is not listed in workflow mail', 'The role was not shown any more in the notification e-mails if a task was assigned to users.', '15'),
('2021-09-30', 'TRANSLATE-2599', 'bugfix', 'reviewer can not open associated task in read-only mode', 'If a user with segment ranges tries to open a task read-only due workflow state waiting or finished this was resulting in an error.', '15'),
('2021-09-30', 'TRANSLATE-2598', 'bugfix', 'Layout Change Logout', 'Changing translate5 theme will no longer logout the user.', '15'),
('2021-09-30', 'TRANSLATE-2591', 'bugfix', 'comments of translate no segments are not exported anymore', 'comments of segments with translate = no were not exported any more, this is fixed now.', '15');