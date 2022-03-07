
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-03-03', 'TRANSLATE-2872', 'feature', 'Import/Export - Implement a URL callback triggered after task import is finished', 'Now a URL can be configured (runtimeOptions.import.callbackUrl) to be called after a task was imported. 
The URL is called via POST and receives the task object as JSON. So systems creating tasks via API are getting now immediate answer if the task is imported. The status of the task (error on error, or open on success) contains info about the import success. If the task import is running longer as 48 hours, the task is set to error and the callback is called too.', '15'),
('2022-03-03', 'TRANSLATE-2860', 'feature', 'TermPortal - Attribute levels should be collapsed by default', 'Entry-level images added to language-level ones in Images-column of Siblings-panel', '15'),
('2022-03-03', 'TRANSLATE-2483', 'feature', 'InstantTranslate - Save InstantTranslate translation to TM', 'Enables translation to be saved to "Instant-Translate" TM memory. For more info how this should be used, check this link: https://confluence.translate5.net/display/TAD/InstantTranslate', '15'),
('2022-03-03', 'TRANSLATE-2882', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Calling updateProgress on export triggers error in the GUI', 'The progress update was also triggered on exports, causing some strange task undefined errors in the GUI.', '15'),
('2022-03-03', 'TRANSLATE-2879', 'bugfix', 'TermPortal - termPM-role have no sufficient rights to transfer terms from TermPortal', 'Fixed: terms transfer was unavailable for termPM-users', '15'),
('2022-03-03', 'TRANSLATE-2878', 'bugfix', 'Editor general - Metadata export error with array type filter', 'Filtered tasks with multiple option filter will no longer produce an error when Export meta data is clicked.', '15'),
('2022-03-03', 'TRANSLATE-2876', 'bugfix', 'Search & Replace (editor) - Search and replace match case search', 'Error will no longer happen when searching with regular expression with match-case on.', '15'),
('2022-03-03', 'TRANSLATE-2875', 'bugfix', 'Import/Export - Task Entity not found message on sending a invalid task setup in upload wizard', 'The message "Task Entity not found" was sometimes poping up when creating a new task with invalid configuration.', '15'),
('2022-03-03', 'TRANSLATE-2874', 'bugfix', 'InstantTranslate, MatchAnalysis & Pretranslation - MT stops pre-translation at first repeated segment', 'On pre-translating against MT only, repetitions are producing an error, preventing the pre-translation to be finshed. ', '15'),
('2022-03-03', 'TRANSLATE-2871', 'bugfix', 'InstantTranslate - Instant-translate result list name problem', 'Problem with listed results in instant translate with multiple resources with same name.', '15'),
('2022-03-03', 'TRANSLATE-2870', 'bugfix', 'Task Management - Deleting a cloned task deletes the complete project', 'This bug affects only projects containing one target task. If this single task is cloned, and the original task was deleted, the whole project was deleted erroneously. This is changed now by implicitly creating a new project for such tasks. ', '15'),
('2022-03-03', 'TRANSLATE-2858', 'bugfix', 'TermPortal - Proposal for Term entries cant be completed', 'Fixed proposal creation when newTermAllLanguagesAvailable config option is Off', '15'),
('2022-03-03', 'TRANSLATE-2854', 'bugfix', 'TermPortal - Term-portal error: join(): Argument #1 ($pieces) must be of type array, string given', 'Fixed bug in loading terms.', '15');