
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-06-14', 'TRANSLATE-2811', 'feature', 'Editor general, LanguageResources - Integrate MS Translator synonym search in editor', 'Microsoft\'s translator synonym search is now part of translate5 editor.', '15'),
('2022-06-14', 'TRANSLATE-2539', 'feature', 'Auto-QA - AutoQA: Numbers check', 'AutoQA: added 12 number-checks from SNC library', '15'),
('2022-06-14', 'TRANSLATE-2986', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Trigger callback when all users did finish the assigned role', 'After all jobs are finished, callback workflow action can be configured. How this can be configured it is explained in this link:  https://confluence.translate5.net/display/BUS/Workflow+Action+and+Notification+Customization#:~:text=Remote%20callback%20when%20all%20users%20finish%20there%20jobs', '15'),
('2022-06-14', 'TRANSLATE-2978', 'change', 'Editor Length Check - Disable automatic adding of newlines on segments by configuration', 'The automatic adding of newlines could now disabled by configuration.', '15'),
('2022-06-14', 'TRANSLATE-2985', 'bugfix', 'Editor general - Error on configuration overview filtering', 'The error which pops-up when quick-typing in configuration filter is solved.', '15'),
('2022-06-14', 'TRANSLATE-2983', 'bugfix', 'Editor general - Task action menu error after leaving a task', 'Opening the task action menu after leaving the task will no longer produce error.', '15'),
('2022-06-14', 'TRANSLATE-2982', 'bugfix', 'TermPortal, TermTagger integration - Empty term in TBX leads to crashing termtagger', 'If an imported TBX was containing empty terms (which is basically non sense) and that term collection was then used for termtagging in asian languages, the termtagger was hanging in an endless loop and was not usable anymore.', '15'),
('2022-06-14', 'TRANSLATE-2981', 'bugfix', 'TBX-Import - Importing TBX with invalid XML leads to high CPU usage', 'On importing a TBX file with invalid XML the import process was caught in an endless loop. This is fixed and the import stops now with an error message.', '15'),
('2022-06-14', 'TRANSLATE-2980', 'bugfix', 'Editor general - On task delete translate5 keeps the old route', 'Missing task message when the task is removed will no longer be shown.', '15');