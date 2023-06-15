
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-06-15', 'TRANSLATE-3218', 'feature', 'API - Hotfolder-based connector solution, that mimics Across hotfolder', 'New AcrossHotfolder plugin that watches hotfolders for tasks, that should be created in translate5 - and re-exported to the hotfolder, once they are ready', '15'),
('2023-06-15', 'TRANSLATE-2551', 'feature', 'Import/Export - Update Task with xliff', '5.7.14
Enable existing file to be replaced and with this the segments will be updated in the task.
6.3.0
Fix for getting the correct export class when changed segments are collected for e-mail.', '15'),
('2023-06-15', 'TRANSLATE-3372', 'change', 'Client management - Prevent TEST calls with root-rights', 'ENHANCEMENT: prevent calling the API-test CLI-command to be used with root-rights', '15'),
('2023-06-15', 'TRANSLATE-3346', 'change', 'file format settings - Add cleanup command for invalid BCONF entries', 'ENHANCEMENT: Add CLI command to clean and fix invalid BCONF entries', '15'),
('2023-06-15', 'TRANSLATE-3331', 'change', 'Test framework - Base-architecture to provide test-configs from plugins (especially private plugins), Improved Service Architecture', 'ENHANCEMENT: add test-config provider from plugins and plugin-services', '15'),
('2023-06-15', 'TRANSLATE-3368', 'bugfix', 'VisualReview / VisualTranslation - Pdfconverter fail to process pdf', '  - PDFconverter command was changed to be run by watchman for immediate conversion and by cron for periodical
  - PDFconverter command now has capability to be run miltiple times, max amount of parallel runs is configurable via MAX_PARALLEL_PROCESSES environment variable of the pdfconverter container', '15'),
('2023-06-15', 'TRANSLATE-3366', 'bugfix', 'file format settings - Add the internal tag "hyper" to the figma file format settings', 'Improved FIGMA file-format settings to support "hyper" attributes in figma-files', '15'),
('2023-06-15', 'TRANSLATE-3355', 'bugfix', 'InstantTranslate - Auto-deletion of instant-translate pre-translated tasks is not correct', 'Replace order date with created date when fetching InstantTranslate pre-translated tasks to remove', '15'),
('2023-06-15', 'TRANSLATE-3348', 'bugfix', 'Import/Export - Plugin-ConnectWorldserver error on import', '6.2.2
improved download files from Worldserver and error-, notification-handling
6.3.0
Fix error on task import.', '15'),
('2023-06-15', 'TRANSLATE-3345', 'bugfix', 'Configuration - Wrong or non existing config type class error', 'Not found config class for configuration will be logged as warnings', '15'),
('2023-06-15', 'TRANSLATE-3336', 'bugfix', 'Auto-QA - False positive pop-up and tooltip need readjustment for grey theme', 'FIXED: false positives style problem in Gray and Neptune themes', '15'),
('2023-06-15', 'TRANSLATE-3312', 'bugfix', 'Editor general - Active project grid filter leads to an error in task add window', 'Filtered tasks grid lead to an error when creating new project.', '15'),
('2023-06-15', 'TRANSLATE-3311', 'bugfix', 'I10N - Add Bengali for Bangladesh and India to LEK_languages', '- Bengali `bn` set as main language
- Added two sublanguages for Bengali: India and Bangladesh
- Added locale translations for sublanguage names', '15'),
('2023-06-15', 'TRANSLATE-3309', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Consolidate session lifetime configuration', 'Consolidate session configuration and make it accessible over the UI.', '15'),
('2023-06-15', 'TRANSLATE-3308', 'bugfix', 'TermTagger integration - Missing locale causes sql error', 'FIXED: error on missing user locale', '15'),
('2023-06-15', 'TRANSLATE-2992', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - PHP\'s setlocale has different default values', '5.7.4
The PHP\'s system locale was not correctly set. This is due a strange behaviour setting the default locale randomly.
6.3.0
Some small code improvements', '15'),
('2023-06-15', 'TRANSLATE-2190', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - PHP ERROR in core: E9999 - Cannot refresh row as parent is missing - fixed in DbDeadLockHandling context', '6.3.0
Fix for back-end workers error.', '15');