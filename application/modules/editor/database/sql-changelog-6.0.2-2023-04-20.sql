
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-04-20', 'TRANSLATE-3285', 'bugfix', 'Export - Lock task on export translator package', 'Improve task locking when exporting translator package.', '15'),
('2023-04-20', 'TRANSLATE-3282', 'bugfix', 'TermPortal - Terms are not marked on imports with termcollection auto association', 'Instances with default term collections associated it might happen that terms were not checked automatically after import.', '15'),
('2023-04-20', 'TRANSLATE-3281', 'bugfix', 'LanguageResources - Wildcard escape in collection search', 'Mysql wildcards will be escaped when searching terms in term collection.', '15'),
('2023-04-20', 'TRANSLATE-3280', 'bugfix', 'Editor general - Fixing UI errors', '- Fix for error when switching customer in add task window and quickly closing the window with esc key. (me.selectedCustomersConfigStore is null)
- Fix for error when "segment qualities" are still loading but the user already left/close the task. (this.getMetaFalPosPanel() is undefined)
- Right clicking on disabled segment with spelling error leads to an error. (c is null_in_quality_context.json)
- Applying delayed quality styles to segment can lead to an error in case the user left the task before the callback/response is evaluated.', '15'),
('2023-04-20', 'TRANSLATE-3279', 'bugfix', 'TermPortal - Logic of camelCase detection needs to be fixed', 'Fixed the way of how picklist values are shown in GUI', '15'),
('2023-04-20', 'TRANSLATE-3277', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Division by zero error when calculating progress', 'Solves problem with workers crash when calculating progress.', '15'),
('2023-04-20', 'TRANSLATE-3275', 'bugfix', 'Editor general - Improve logging for no access errors on opened tasks', 'The user will get sometimes no access error when task is being opened for editing.  For that reason, the front-end error logging is improved.', '15'),
('2023-04-20', 'TRANSLATE-3256', 'bugfix', 'Editor general - False positive menu option stays visible on leaving the task', 'Fixed floating FalsePositives-panel problem', '15'),
('2023-04-20', 'TRANSLATE-3227', 'bugfix', 'Task Management - Horizontal scrollbar in project wizard pop-up is missing', 'Overflow menu is now turned On for most toolbars and tab-bars, including project wizard', '15'),
('2023-04-20', 'TRANSLATE-3061', 'bugfix', 'Test framework - FIX API Tests', 'translate5 - 5.7.13
 - Code refactoring for the testing environment. Improvements and fixes for API test cases.
translate5 - 6.0.2
 - Fixed config loading level in testing environment 
', '15'),
('2023-04-20', 'TRANSLATE-3048', 'bugfix', 'Editor general - CSRF Protection for translate5', 'translate5 - 6.0.0
- CSRF (Cross Site Request Forgery) Protection for translate5 with a CSRF-token. Important info for translate5 API users: externally the translate5 - API can only be accessed with an App-Token from now on.

translate5 - 6.0.2
- remove CSRF protection for automated cron calls', '15'),
('2023-04-20', 'TRANSLATE-2993', 'bugfix', 'LanguageResources, TermPortal - Invalid TBX causes TermPortal to crash', 'Empty termEntry/language/term attribute-nodes are now skipped if found in TBX-files', '15'),
('2023-04-20', 'TRANSLATE-2396', 'bugfix', 'Installation & Update - Diverged GUI and Backend version after update', 'translate5 - 5.1.1 - The user gets an error message if the version of the GUI is older as the backend - which may happen after an update in certain circumstances. Normally this is handled due the usage of the maintenance mode.

translate5 - 6.0.2 - Fixed missing version header on error handling. Additional fix: Return JSON on rest based exceptions instead just a string', '15');