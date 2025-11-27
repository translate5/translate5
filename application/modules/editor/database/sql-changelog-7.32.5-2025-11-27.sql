
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-11-27', 'TRANSLATE-4797', 'feature', 'Export - Add batch operation for task export "original format, translated/reviewed"', '7.32.5: Test fix related to worker problems
7.30.2: Improved queueing of batch export workers
7.30.0: Added batch operation for task export "original format, translated/reviewed"
', '15'),
('2025-11-27', 'TRANSLATE-5141', 'change', 'Docker Setup - Incompatible MariaDB update', 'Don\'t call docker pull till we provide a solution! 
MariaDB would be updated to 12.1 which is not compatible with translate5 at the moment! ', '15'),
('2025-11-27', 'TRANSLATE-5114', 'change', 't5memory - Improve fuzzy uplifting tag handling', 'Improve fuzzy uplifting tag handling', '15'),
('2025-11-27', 'TRANSLATE-5139', 'bugfix', 'Workflows - Workflow-action error for visualApprove workflow', '* Fix potentially wrong ClassName in Workflow-Configuration for Visual Workflow Actions', '15'),
('2025-11-27', 'TRANSLATE-5131', 'bugfix', 'InstantTranslate - Double encoding problem in InstantTranslate', 'FIXED: ampersand and doublequote characters are not double-encoded anymore', '15'),
('2025-11-27', 'TRANSLATE-5129', 'bugfix', 'Repetition editor - Save and open next segment with repetition editor results in race condition', 'Fix of race condition with confirmation window when using repetition editor', '15'),
('2025-11-27', 'TRANSLATE-5128', 'bugfix', 'InstantTranslate - Instant translate: customer dropdown wrongly evaluated on text translation', 'Fixed problem where client dropdown in file translation will filter the resources also for text translation.', '15'),
('2025-11-27', 'TRANSLATE-5120', 'bugfix', 'MatchAnalysis & Pretranslation - Wrong tag handler used with Term Collection', 'Set paired xliff tag handler for Term Collection usage', '15'),
('2025-11-27', 'TRANSLATE-5119', 'bugfix', 'InstantTranslate - pre-selected languages in InstantTranslate always alphabetical first', 'Fix the problem where default instant translate pre-selected languages config was not evaluated correctly.', '15'),
('2025-11-27', 'TRANSLATE-5104', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - There are more processing workers run than is actually needed for a task', 'For small tasks to much termtagger workers were scheduled leading to follow up problems', '15'),
('2025-11-27', 'TRANSLATE-5100', 'bugfix', 'Editor general - Create tests for draft status', '7.32.5: Added tests for draft status', '15'),
('2025-11-27', 'TRANSLATE-5080', 'bugfix', 'TM Maintenance - error message in case of lost session in TM maintenance', 'Redirect to login page on no auth error', '15'),
('2025-11-27', 'TRANSLATE-5062', 'bugfix', 'Editor general - Editor: drag & drop tag in open segment removes adjacent tag', '[🐞 Fix] Now dropping a tag between two other tags does not cause tag disappearing when moving tags with drag\'n\'drop', '15'),
('2025-11-27', 'TRANSLATE-4999', 'bugfix', 'TM Maintenance - segment editing faulty in TM maintenance', '[🐞 Fix] repaired editing segments in TM Maintenance', '15');