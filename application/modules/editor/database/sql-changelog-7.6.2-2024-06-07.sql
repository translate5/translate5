
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-06-07', 'TRANSLATE-3985', 'change', 't5memory - Add check for language resource status before migration', 'Added checking language resource status before starting migration.', '15'),
('2024-06-07', 'TRANSLATE-3983', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Use process based workers by default', 'The config runtimeOptions.worker.triggerType is changed now from http to process by default.', '15'),
('2024-06-07', 'TRANSLATE-3981', 'change', 'Installation & Update - Installation is not working anymore', 'FIXED: problem with installation', '15'),
('2024-06-07', 'TRANSLATE-3978', 'change', 'InstantTranslate - Change font in InstantTranslate help window', 'Set up font name, size and color in InstanstTranslate and TermPortal help windows', '15'),
('2024-06-07', 'TRANSLATE-3993', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Final task import workers are initialized with the wrong status', 'In seldom circumstances the import produces tasks with no segments imported due wrong order called workers.', '15'),
('2024-06-07', 'TRANSLATE-3992', 'bugfix', 'Test framework - wait for worker instead task status', 'Improvements Test-API: functionality to wait for workers to be finished', '15'),
('2024-06-07', 'TRANSLATE-3989', 'bugfix', 'MatchAnalysis & Pretranslation - OpenAI: Non-trained Models cannot be used for batch-translation', 'FIX: Non-trained OpenAI Models failed when used for batch-translation (task-import)', '15'),
('2024-06-07', 'TRANSLATE-3988', 'bugfix', 'TrackChanges - UI Crash on opening or saving a segment with track changes', 'Some weird cascading track-changes tags lead to a crash of the segment editor on startup / segment save.', '15'),
('2024-06-07', 'TRANSLATE-3986', 'bugfix', 'Import/Export - Export not possible when okapi import had errors', 'Fix an issue which prevents task export when some files of the task could not be imported with Okapi.', '15'),
('2024-06-07', 'TRANSLATE-3982', 'bugfix', 'Editor general - Transtilde may seep into internal tags', 'FIX: Special string may ends up in the segments content in the Richtext-Editor with Placeables', '15'),
('2024-06-07', 'TRANSLATE-3979', 'bugfix', 'Editor general - Users with no roles can not be deleted via UI', 'Fixes problem where users with no roles cannot be removed via UI.', '15'),
('2024-06-07', 'TRANSLATE-3977', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Error: Typed property ZfExtended_Logger_Writer_Database::$insertedData must not be accessed before initialization', 'FIXED: flooding the log due to unhandled duplicates', '15'),
('2024-06-07', 'TRANSLATE-3976', 'bugfix', 'Export - Multi segment target in SDLXLIFF handled incorrectly on export', 'Fix export of sdlxliff: provide draft config field, matchrate and related fields in multi-segment target', '15'),
('2024-06-07', 'TRANSLATE-3972', 'bugfix', 'Import/Export - XLIFF import: tags paired by RID are not paired anymore: TESTS', 'Added tests to check pairing of ept/bpt by RID on import of xliff', '15'),
('2024-06-07', 'TRANSLATE-3968', 'bugfix', 'API, Authentication - Internal API not usable with application tokens', 'Functionality which is using the Internal API is not usable with application tokens.', '15'),
('2024-06-07', 'TRANSLATE-3966', 'bugfix', 'Import/Export - Ensure that SDLXLIFF changemarks and locked segments are working well together', 'The SDLXLIFF export of changemarks applied to locked tags may lead to invalid SDLXLIFF.', '15');