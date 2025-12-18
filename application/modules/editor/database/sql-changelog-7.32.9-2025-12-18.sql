
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-12-18', 'TRANSLATE-5167', 'change', 't5memory - Create TMX processor to fix tu creation dates', 'Add TMX processor to fix TU creation dates', '15'),
('2025-12-18', 'TRANSLATE-5166', 'change', 'translate5 AI - Tag-Handling may fails for OpenAI if model response unexpectedly returned an array instead of string', 'FIX potential BUG when model LLM  responce unexpectedly represents an array', '15'),
('2025-12-18', 'TRANSLATE-5152', 'change', 'LanguageResources - Improve search of synchronisable language resource', '7.32.9: Minor fix in comparing languages
7.32.8: Improve search of synchronisable language resource', '15'),
('2025-12-18', 'TRANSLATE-4099', 'change', 'LanguageResources - Update DeepL SDK recurring issue', 'Internal update of the DeepL SDK (Internal API)', '15'),
('2025-12-18', 'TRANSLATE-5171', 'bugfix', 'TermTagger integration - Add automatic fixing on task-export for rare terminology-tag nesting errors created when termtagging', 'FIX: Rare problem when the termtagger creates invalidly nested tags are automatically fixed on export', '15'),
('2025-12-18', 'TRANSLATE-5163', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.), TermPortal - Increase the api client timeout', 'The request timeout is increased to 2mins for the internal api client.', '15'),
('2025-12-18', 'TRANSLATE-5158', 'bugfix', 'Editor general - tracked changes in closed segments not visible', 'Pure UI Fix: deleted words/symbols are now correctly displayed inside spellchecked words', '15'),
('2025-12-18', 'TRANSLATE-5142', 'bugfix', 'VisualReview / VisualTranslation - Task import fails because of comma in a filename', 'Fixed task import failing when comma in a filename was present', '15'),
('2025-12-18', 'TRANSLATE-5138', 'bugfix', 'InstantTranslate, LanguageResources, Task Management - InstantTranslate user wrong TM usage', '[🐞 Fix] When opening file translation in InstantTranslate, TMs are assigned with read access only regardless of the default settings', '15'),
('2025-12-18', 'TRANSLATE-5113', 'bugfix', 'MatchAnalysis & Pretranslation, TermPortal - Termcollection pretranslation doesn\'t regard sublanguage', '7.32.9: Implemented remaining open things to the issue
7.32.3: Fixed that the sub-language penalty is respected for pure term translations: e.g. for en-gb task the en-us term is used for pretranslation. When there are multiple target terms the best one is used.', '15'),
('2025-12-18', 'TRANSLATE-4579', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Worker queue is called frequently and chained', '7.32.9: Fix for delayed workers
7.32.6: small fix for tests
7.32.2: set processdaemon as new default worker trigger
7.21.3: Performance improvement for the workers, prevention of system overload when scheduling thousands of workers.', '15'),
('2025-12-18', 'TRANSLATE-4576', 'bugfix', 'TM Maintenance - nbsp-tag dragged to end of segment results in "undefined" in TM maintenance', '[🐞 Fix] Fixed tag drag\'n\'drop in TM Maintenance', '15'),
('2025-12-18', 'TRANSLATE-4572', 'bugfix', 'Editor general - uncaught error in new editor', '[🐞 Fix] Fixed inserting whitespace tag which could produce error in certain cases', '15'),
('2025-12-18', 'TRANSLATE-4550', 'bugfix', 'Editor general - Validate if a bad markup problem from the old editor persists in the new editor', '[🐞 Fix] Added automatic fixing for improper markup in editor', '15');