
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-07-20', 'TRANSLATE-2518', 'feature', 'Add project description to project and tasks', 'A project description can be added on project creation.', '15'),
('2021-07-20', 'TRANSLATE-2477', 'feature', 'Language resource to task assoc: Set default for pre-translation and internal-fuzzy options in system config', 'Default values for "internal fuzzy", "translate MT" and "translate TM and Term" checkboxes  can be defined as system configuration configuration (overwritable on client level).', '15'),
('2021-07-20', 'TRANSLATE-992', 'feature', 'New Keyboard shortcuts for process / cancel repetition editor', 'Adding keyboard shortcuts to save (ctrl+s) or cancel (esc) the processing of repetitions in the repetition editor.', '15'),
('2021-07-20', 'TRANSLATE-2566', 'change', 'Integrate Theme-Switch in translate5', 'Users are able to change the translate5 theme.', '15'),
('2021-07-20', 'TRANSLATE-2381', 'change', 'Visual: Enhance the reflow mechanism for overlapping elements', 'Visual: Improved Text-Reflow. This signifantly reduces the rate of PDFs that cannot be imported with a functional WYSIWIG preview. There now is a threshhold for detected reflow-rendering errors that can be raised for individual tasks that had to many errors on Import as a last ressort. Although that will rarely be neccessary.', '15'),
('2021-07-20', 'TRANSLATE-1808', 'change', 'Installer should set the timezone', 'The installer always set timezone europe/berlin, know the  user is asked on installation which timezone should be used.', '15'),
('2021-07-20', 'TRANSLATE-2581', 'bugfix', 'Task user assoc workflow step drop-down filtering', 'If a user was added twice to a task, and the workflow step of the second user was changed to the same step of the first user, this led to a duplicated key error message.', '15'),
('2021-07-20', 'TRANSLATE-2578', 'bugfix', 'Reload users to task association grid after task import finishes', 'Refresh users to task association grid after the task import is done.', '15'),
('2021-07-20', 'TRANSLATE-2576', 'bugfix', 'Notify associated user button does not work', 'Fixes problem with "Notify users" button not sending emails.', '15'),
('2021-07-20', 'TRANSLATE-2575', 'bugfix', 'System default configuration on instance or client level has no influence on Multiple user setting in import wizard', 'The default value for the "multiple user" setting drop-down was not correctly preset from config.', '15'),
('2021-07-20', 'TRANSLATE-2573', 'bugfix', 'User assignment entry disappears in import wizard, when pre-assigned deadline is changed', 'Edited user association in import wizard was disappearing after switching the workflow.', '15'),
('2021-07-20', 'TRANSLATE-2571', 'bugfix', 'ERROR in core: E9999 - TimeOut on waiting for the following materialized view to be filled', 'There was a problem when editing a default associated user of a task in the task add wizard. This is fixed now.', '15'),
('2021-07-20', 'TRANSLATE-2568', 'bugfix', 'ModelFront plug-in is defect and prevents language resource usage', 'The ModelFront plug-in was defect and stopped match analysis and pre-translation from working.', '15'),
('2021-07-20', 'TRANSLATE-2567', 'bugfix', 'TagProtection can not deal with line breaks in HTML attributes', 'When using TagProtection (protect plain HTML code in XLF as tags) line breaks in HTML attributes were not probably resolved.', '15'),
('2021-07-20', 'TRANSLATE-2565', 'bugfix', 'GroupShare: Wrong tag order using the groupshare language resource', 'Nested internal tags were restored in wrong order if using a segment containing such tags from the groupshare language resource. ', '15'),
('2021-07-20', 'TRANSLATE-2546', 'bugfix', 'New uuid column of match analysis is not filled up for existing analysis', 'The new uuid database column of the match analysis table is not filled up for existing analysis.', '15'),
('2021-07-20', 'TRANSLATE-2544', 'bugfix', 'Focus new project after creating it', 'After task/project creation the created project will be focused in the project overview', '15'),
('2021-07-20', 'TRANSLATE-2525', 'bugfix', 'npsp spaces outside of mrk-tags of mtype "seg" should be allowed', 'Due to invalid XLIFF from Across there is a check in import, that checks, if there is text outside of mrk-tags of mtype "seg" inside of seg-source or target tags. Spaces and tags are allowed, but nbsp characters were not so far. This is changed and all other masked whitespace tags are allowed to be outside of mrk tags too.', '15'),
('2021-07-20', 'TRANSLATE-2388', 'bugfix', 'Ensure config overwrite works for "task usage mode"', 'The task usageMode can now be set via API on task creation.', '15');