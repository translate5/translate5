
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-07-23', 'TRANSLATE-4816', 'change', 'LanguageResources - Implement duration logging of language resources', 'Implement the logging of needed times for using language resources in pivot filling, analysis and pre-translation.', '15'),
('2025-07-23', 'TRANSLATE-4811', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Implement php level profiling for workers', 'Import performance needs improvement; bottlenecks must be identified first, for which a PHP profiler suitable for production will be implemented.', '15'),
('2025-07-23', 'TRANSLATE-4812', 'bugfix', 't5connect, Workflows - Make the relevant t5connect steps configurable', 'Avoid duplicate optional workflow step labels in job\'s workflow steps list', '15'),
('2025-07-23', 'TRANSLATE-4810', 'bugfix', 'Import/Export - White spaces and line breaks appear on xlf import', 'Fixed import of line breaks in XLF import', '15'),
('2025-07-23', 'TRANSLATE-4809', 'bugfix', 'Workflows - changeEdit100PercentMatch workflow action should set task property', 'The execution of changeEdit100PercentMatch workflow action now sets the related property in the task', '15'),
('2025-07-23', 'TRANSLATE-4808', 'bugfix', 'Editor general - RootCause: task is null', 'Fixed error on \'Notify users\' button click in Users-tab', '15'),
('2025-07-23', 'TRANSLATE-4807', 'bugfix', 'Content Protection - Content protection internal tag regex is no longer valid', 'Fixed content protection internal tag regex', '15'),
('2025-07-23', 'TRANSLATE-4804', 'bugfix', 'Editor general - Nextsegments HTTP request fails for PM', 'Fixed error when fetching info about next segments for PM user', '15'),
('2025-07-23', 'TRANSLATE-4802', 'bugfix', 'Editor general - RootCause: can\'t access property "get", task is undefined', 'Fixed task being not deselected in project tasks grid anymore when project\'s batch-editing checkbox (in project grid) is clicked, so link to currently selected task is not broken', '15'),
('2025-07-23', 'TRANSLATE-4801', 'bugfix', 'Editor general - Internal tag numbers are processed wrong when saving segment', 'Fixed bug which may cause wrong tag numbering saved to t5memory', '15'),
('2025-07-23', 'TRANSLATE-4800', 'bugfix', 't5memory - Diff in matches in match table are shown incorrectly', 'Fixed incorrect displaying of diff in match table\'s matches', '15'),
('2025-07-23', 'TRANSLATE-4796', 'bugfix', 'Editor general - RootCause: Cannot read properties of null (reading \'get\')', 'Fixed an error about missing task data in UI', '15'),
('2025-07-23', 'TRANSLATE-4790', 'bugfix', 'file format settings - OKAPI BCONF Import: SRX-files might be not imported if pipeline not correct', 'Fixed case when BCONFs imported from RAINBOW could have SRX-files not detected when the PIPELINE settings were not valid (what can easily happen in Rainbow but has no effect in Longhorn)', '15'),
('2025-07-23', 'TRANSLATE-4781', 'bugfix', 'AI - translate5 AI: do not merge user defined system prompts on fine tuning', 'When generating jsonl file, training pairs are now sent with assigned system message from the prompt', '15'),
('2025-07-23', 'TRANSLATE-4762', 'bugfix', 'Content Protection - Change display of content protection tags in fuzzy match panel', 'Show protected content tags from TM in match table even if task segment does not have corresponding tag', '15'),
('2025-07-23', 'TRANSLATE-4669', 'bugfix', 'openai - translate5 AI: Show already trained prompts separated in training window', 'Prompts that have been sent to training remain now separated in training window after training', '15');