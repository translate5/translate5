
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-07-31', 'TRANSLATE-2270', 'feature', 'LanguageResources - Translation Memory Maintenance', 'New plugin TMMaintenance for managing segments in t5memory', '15'),
('2024-07-31', 'TRANSLATE-4092', 'change', 'Translate5 CLI - CLI tool for testing SFTP and task archiving config', 'Implement filesystem:external:check and task:archive commands for testing and manual usage of external file systems and the task archiving stuff.', '15'),
('2024-07-31', 'TRANSLATE-4079', 'change', 'LanguageResources - Only one request per concordance search', 'TM-records are now loaded one-by-one until 20 loaded or nothing left', '15'),
('2024-07-31', 'TRANSLATE-4069', 'change', 't5memory - Add comparing sent and received data during update request to t5memory', 'translate5 - 7.6.6: When updating the segment it is now checked if the received data equals what we expect
translate5 - 7.7.0: Disable t5memory data check because of to many logs', '15'),
('2024-07-31', 'TRANSLATE-4065', 'change', 'MatchAnalysis & Pretranslation - Use empty TM for internal fuzzy', 'translate5 - 7.6.6: Use empty TM to save internal fuzzy results instead cloning the current one
translate5 - 7.7.0: Improve logging for removed memory.', '15'),
('2024-07-31', 'TRANSLATE-4062', 'change', 'Workflows - Add archive config to use import date instead task modified date', 'Extend task archiving functionality to filter for created timestamp also, instead only modified timestamp. Configurable in Workflow configuration.', '15'),
('2024-07-31', 'TRANSLATE-4011', 'change', 'Export - Plugin ConnectWorldserver: no state error on task export for re-transfer to Worldserver', 'task is not set to state error on export', '15'),
('2024-07-31', 'TRANSLATE-4115', 'bugfix', 'InstantTranslate - InstantTranslate: switch to manual-mode only if several requests took too long', 'FIX: InstantTranslate now switches to manual-mode (not "instant" anymore) only when several requests in a row took longer than the configured threshold


', '15'),
('2024-07-31', 'TRANSLATE-4113', 'bugfix', 'Editor general - Improve Logging of Invalid Markup for Sanitization', 'FIX: Improved logging of invalid markup sent from segment editing - not leading to security error anymore', '15'),
('2024-07-31', 'TRANSLATE-4097', 'bugfix', 'Editor general - RootCause: response.getAllResponseHeaders is not a function', 'FIXED: fixed problem popping when maintenance mode is going to be enabled', '15'),
('2024-07-31', 'TRANSLATE-4095', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Log cron job calls and add system check', 'Add log entries for each cron job call and a check to the system check if crons are triggered or not.', '15'),
('2024-07-31', 'TRANSLATE-4088', 'bugfix', 'Configuration - In-Context fonts - search by task name throws error', 'FIX: searching in In-Context fonts may lead to exception', '15'),
('2024-07-31', 'TRANSLATE-4086', 'bugfix', 'OpenId Connect - OpenID connect: wrong error handling with empty user info', 'Error handling fix in OpenID connect', '15'),
('2024-07-31', 'TRANSLATE-4084', 'bugfix', 'Editor general - Change level of branding config', 'Branding config can be adjusted via the UI.', '15'),
('2024-07-31', 'TRANSLATE-4080', 'bugfix', 'Okapi integration - FIX: Okapi fails exporting custom subfilters', 'FIX: OKAPI failed to export Files processed with a Filter using a customized Subfilter. This is only a temporary fix until the issue is solved within OKAPI', '15'),
('2024-07-31', 'TRANSLATE-4074', 'bugfix', 'VisualReview / VisualTranslation - Visual does not reflect Pivot language in case target segments are empty', 'FIX: Visual did not show pivot language in case the target segments were empty', '15'),
('2024-07-31', 'TRANSLATE-4066', 'bugfix', 't5memory - Change save2disk behavior when reimporting task to t5memory', 'TM is now flushed to disk only when reimport is finished', '15'),
('2024-07-31', 'TRANSLATE-4048', 'bugfix', 'LanguageResources - It is possible to create language resource for down server', 'It is now not possible to create a Language resource when the corresponding server is unreachable', '15'),
('2024-07-31', 'TRANSLATE-4029', 'bugfix', 'Editor general - Customer specific theme overwrite is not working', 'Theme name as CSS class in body tag.', '15'),
('2024-07-31', 'TRANSLATE-4024', 'bugfix', 'Configuration - Missing config-type', 'If the type of a certain config can not be detected, "string" will be set as default.', '15'),
('2024-07-31', 'TRANSLATE-4023', 'bugfix', 'Auto-QA - AutoQA portlet should dissappear with no active checks', 'Editor\'s AutoQA leftside portlet is now hidden if no autoQA enabled for the task', '15'),
('2024-07-31', 'TRANSLATE-4020', 'bugfix', 'VisualReview / VisualTranslation - PDF converter fails to cleanup JOB and thus does not respond with a proper log', 'FIX visual: pdf converter did not write a proper log & failed to clean up the workfiles in the converter container', '15');