
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-06-28', 'TRANSLATE-3360', 'feature', 'Auto-QA, Editor general - AutoQA must be 0 errors to finish task', 'It is now configurable for the PM to create a list of qualities on system, client, import and task level, for which AutoQA check 0 errors is required to finish the task. Errors that are set to false positive are allowed and do not count.', '15'),
('2023-06-28', 'TRANSLATE-3321', 'feature', 'InstantTranslate - InstantTranslate with DeepL: Detect source language automatically', 'ENHANCEMENT: InstantTranslate now supports auto-detection of the source language', '15'),
('2023-06-28', 'TRANSLATE-3218', 'feature', 'API - Hotfolder-based connector solution, that mimics Across hotfolder', '6.4.0: Several fixes, introducing an API endpoint to trigger the hotfolder check manually
6.3.1: New AcrossHotfolder plugin that watches hotfolders for tasks, that should be created in translate5 - and re-exported to the hotfolder, once they are ready', '15'),
('2023-06-28', 'TRANSLATE-3393', 'change', 'Editor general - Include new German editor documentation in translate5', 'The new German documentation about the translate5 editor has been linked in the help section of the editor', '15'),
('2023-06-28', 'TRANSLATE-3391', 'change', 't5memory - Add 500 status code to automatically trigger reorganize TM', 'Add t5memory 500 error to trigger TM reorganization automatically.', '15'),
('2023-06-28', 'TRANSLATE-3381', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Start workers as plain processes instead using HTTP requests', '6.4.0: The current approach of triggering workers via HTTP is hard to debug and has a big overhead due the HTTP connections. Now the worker invocation can be switched to use raw processes - which is still under development and disabled by default but can be enabled for testing purposes in production.', '15'),
('2023-06-28', 'TRANSLATE-3377', 'change', 'Editor general, Repetition editor - Repetition editor window is annoying', 'New info message how to disable the repetition editor is added to the repetition editor pop-up.', '15'),
('2023-06-28', 'TRANSLATE-3375', 'change', 'Configuration, Editor general - Warning about editing a 100%-Match: Disable it by default', 'The warning about editing 100% matches will be disabled by default on system level.', '15'),
('2023-06-28', 'TRANSLATE-3397', 'bugfix', 'Configuration - Correct configuration default values', 'The default value of some configurations was changed in the past, but the comparator (for the is changed check) in the DB was not updated. This is fixed now.', '15'),
('2023-06-28', 'TRANSLATE-3394', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Bug in exception handling in looped workers leads to exceptions that should have been retried', 'FIX: Looped processing workers may threw exceptions when the request should have been retried ', '15'),
('2023-06-28', 'TRANSLATE-2101', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Disable automated translation xliff creation from notFountTranslation xliff in production instances', 'translate5 - 6.4.0: Disabling the not found translation log writer for production instances.

translate5 - 5.0.3: Deactivating a logging facility for missing internal UI translations in production and clean the huge log files. Also enable caching for UI translations in production instances only.', '15');