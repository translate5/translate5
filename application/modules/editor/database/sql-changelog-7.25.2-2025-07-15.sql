
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-07-15', 'TRANSLATE-4779', 'change', 't5memory - Add t5memory version 0.7 support', 'Added basic support of t5memory version 0.7', '15'),
('2025-07-15', 'TRANSLATE-4761', 'change', 'InstantTranslate - Disable editing file translations by config', 'Added new config which disallows opening task for edit from Instant Translate', '15'),
('2025-07-15', 'TRANSLATE-4760', 'change', 'Content Protection - Content Protection: content protection rule label in mouseover', 'Show used rule on hover over content protection tag', '15'),
('2025-07-15', 'TRANSLATE-4756', 'change', 'InstantTranslate, MatchAnalysis & Pretranslation - Improve speed for file translations', 'Fixed bug which caused scheduling Match analysis twice for all file translations in case runtimeOptions.plugins.MatchAnalysis.autoPretranslateOnTaskImport config is activated', '15'),
('2025-07-15', 'TRANSLATE-3827', 'change', 'Authentication - IP-based Authentication should still allow to log in as a user', '7.25.2: added tests, log out the IP based user when access to other applets are allowed for non IP users on accessing them, change available applets on customer level
7.25.1: Introduced a new config to control for which applets (editor,instanttranslate,termportal etc) the ip based authentication should be applied or the normal login page should be shown.', '15'),
('2025-07-15', 'TRANSLATE-4792', 'bugfix', 'Auto-QA - Terminology QA check fails when homonyms have no translations', 'error on terminology QA check when homonyms have no translations', '15'),
('2025-07-15', 'TRANSLATE-4783', 'bugfix', 't5memory - Error 8009 is not handled properly', 'Fixed handling t5memory error \'8009\'', '15'),
('2025-07-15', 'TRANSLATE-4782', 'bugfix', 'Import/Export - Task archiving failure for tasks with long taskNames', 'Fixed task archiving for tasks with long task names', '15'),
('2025-07-15', 'TRANSLATE-4780', 'bugfix', 'Export - Okapi error on task:export command', 'Added missing Okapi worker dependency for t5 task:export command', '15'),
('2025-07-15', 'TRANSLATE-4776', 'bugfix', 'LanguageResources - Special data field in language resource table is too small', 'Expand max size of Language Resources specificData field in DB', '15'),
('2025-07-15', 'TRANSLATE-4770', 'bugfix', 'job coordinator - Job Coordinator can\'t edit segments without user job', 'Allow Job coordinator to edit segments in task where coordinator does not have user job', '15'),
('2025-07-15', 'TRANSLATE-4768', 'bugfix', 'TBX-Import - Not all temporary TBX files are cleaned up', 'Not all tempory TBX files are removed from disk after import, so disk space is wasted here. ', '15'),
('2025-07-15', 'TRANSLATE-4765', 'bugfix', 'job coordinator - Project wizard: workflow user assignment error message', 'Fix check for task in coordinator list call', '15'),
('2025-07-15', 'TRANSLATE-4763', 'bugfix', 'Import/Export - Task Export may fails for old tasks due to non-set BCONF in ZIP', 'Old tasks may fail to export because historically  bconfInZip & bconfId are not set in task-meta', '15'),
('2025-07-15', 'TRANSLATE-4759', 'bugfix', 'Editor general, TrackChanges - Error in editor with trackchanges', 'Fixed bug which prevented editor to be opened in some cases if there are trackchanges without usertracking id', '15'),
('2025-07-15', 'TRANSLATE-4757', 'bugfix', 'User Management - pre-select "enable task overview" for creating new users', 'Made role "taskOverview" always preselected when creating a new user', '15'),
('2025-07-15', 'TRANSLATE-4754', 'bugfix', 'TermPortal - RootCause: f is null', 'problem with term search result selection by non-existing id', '15'),
('2025-07-15', 'TRANSLATE-4752', 'bugfix', 'Okapi integration - Okapi file import: uppercase document names produces error on import', 'Fix for uppercase file extensions producing import errors.', '15'),
('2025-07-15', 'TRANSLATE-4750', 'bugfix', 'Import/Export - Placeables: May be created with empty content leading to UI glitches', 'FIX: Placeables may have empty content leading to glitches in the UI', '15'),
('2025-07-15', 'TRANSLATE-4749', 'bugfix', 'openai - OpenAI model can not be trained more than 2 times in Azure cloud', 'Fixed bug which prevented OpenAI model to be trained more than 2 times in Azure cloud', '15'),
('2025-07-15', 'TRANSLATE-4748', 'bugfix', 'Content Protection - Content protected incorrectly for some xlf segments', 'Fix content protection for XLF import', '15'),
('2025-07-15', 'TRANSLATE-4746', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - No logs about e-mailing available', 'A simple log file (data/logs/mail.log) about which emails are sended when is added.', '15'),
('2025-07-15', 'TRANSLATE-4745', 'bugfix', 'TermPortal - Tooltips missing for active filters tags', 'FIXED: tooltips missing for active filters tags when browser tab opened with URL having search params', '15'),
('2025-07-15', 'TRANSLATE-4740', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'errorCode\')', 'FIXED: added a check for aborted request', '15'),
('2025-07-15', 'TRANSLATE-4724', 'bugfix', 'InstantTranslate - Task TMs should not be used for pre-translation in InstantTranslate', 'Now Task TMs are not assigned for file pretranslation in InstantTranslate', '15'),
('2025-07-15', 'TRANSLATE-4697', 'bugfix', 'Workflows - new workflow "Review and Print approval" should not have auto-assigned jobs', 'Removed default jobs autocreation for "Review and Print approval" workflow (can be configured individually)', '15');